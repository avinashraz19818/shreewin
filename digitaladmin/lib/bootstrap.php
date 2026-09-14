<?php
// Veegame adapter. No donor credentials, automatic legacy schema changes or game hooks.
if (!defined('VA_ADMIN')) { http_response_code(404); exit; }
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
function va_headers(): void {
    header_remove('Access-Control-Allow-Origin');
    header_remove('Access-Control-Allow-Credentials');
    header('Cache-Control: no-store, private, max-age=0');
    header('Pragma: no-cache');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: no-referrer');
    header("Content-Security-Policy: default-src 'none'; style-src 'self' 'unsafe-inline'; font-src 'self'; img-src 'self' data:; script-src 'self'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}
va_headers();
function va_e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function va_fail(int $code, string $message): void {
    http_response_code($code); va_headers();
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Veegame Admin</title><body><h1>Veegame Admin</h1><p>'.va_e($message).'</p><p><a href="login.php">Admin login</a></p></body></html>'; exit;
}
set_exception_handler(static function (Throwable $e): void {
    // Deliberately no raw DB errors, parameters, paths or credentials in responses/logs.
    error_log('[veegame-admin] Request failed: '.get_class($e));
    va_fail(503, 'Admin service unavailable. Check the existing site database connection and admin setup. No financial action was performed.');
});
function va_test(): bool { return PHP_SAPI === 'cli-server' && getenv('VEEGAME_ADMIN_TEST_MODE') === '1'; }
if (PHP_SAPI !== 'cli' && !va_test() && !in_array(strtolower((string)($_SERVER['HTTPS'] ?? '')), ['on','1'], true) && ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') !== 'https' && (int)($_SERVER['SERVER_PORT'] ?? 0) !== 443) {
    va_fail(400, 'Use the HTTPS admin address.');
}
ini_set('session.use_strict_mode','1');
ini_set('session.use_only_cookies','1');
ini_set('session.cookie_httponly','1');
ini_set('session.cookie_samesite','Strict');
session_name('VEEGAME_ADMIN_V1');
session_set_cookie_params(['lifetime'=>0,'path'=>'/digitaladmin/','secure'=>!va_test(),'httponly'=>true,'samesite'=>'Strict']);
session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
function va_csrf(): string { return '<input type="hidden" name="csrf" value="'.va_e($_SESSION['csrf']).'">'; }
function va_post(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') va_fail(405, 'POST required.');
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf'], $token)) va_fail(403, 'Session check failed. Reload the form and try again.');
}
function va_input(string $key, ?array $source = null): string {
    $source = $source ?? $_POST;
    $value = $source[$key] ?? '';
    if (!is_string($value)) va_fail(400,'Invalid input.');
    return $value;
}
function va_redirect(string $to): void { header('Location: '.$to, true, 303); exit; }
function va_db(): mysqli {
    static $db;
    if ($db instanceof mysqli) return $db;
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    // Reuse this site's already-installed connection; never copy ShreeWin conn.php.
    ob_start();
    try { require dirname(__DIR__,2).'/evenvessis/conn.php'; }
    finally { ob_end_clean(); }
    if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_errno) throw new RuntimeException('Connection unavailable');
    $conn->set_charset('utf8mb4');
    $conn->query("SET time_zone = '+05:30'");
    // Override any CORS policy registered by the shared legacy connection.
    header_register_callback('va_headers');
    return $db = $conn;
}
function va_stmt(string $sql, array $params = []): mysqli_stmt {
    $s=va_db()->prepare($sql);
    if ($params) { $types=''; foreach($params as $p) $types .= is_int($p) ? 'i' : 's'; $s->bind_param($types, ...$params); }
    $s->execute(); return $s;
}
function va_rows(string $sql, array $params = []): array {
    $s=va_stmt($sql,$params);$meta=$s->result_metadata();
    if(!$meta) { $s->close(); return []; }
    $row=[];$refs=[];foreach($meta->fetch_fields() as $field) { $row[$field->name]=null;$refs[]=&$row[$field->name]; }
    $s->bind_result(...$refs);$rows=[];
    while($s->fetch()) { $copy=[];foreach($row as $k=>$v)$copy[$k]=$v;$rows[]=$copy; }
    $s->close();return $rows;
}
function va_one(string $sql, array $params = []): ?array { return va_rows($sql,$params)[0] ?? null; }
function va_ready(): bool {
    static $ready;
    if ($ready !== null) return $ready;
    $n=va_one("SELECT COUNT(DISTINCT TABLE_NAME) AS n FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('veegame_admin_accounts','veegame_admin_audit','veegame_admin_rate') AND ENGINE='InnoDB'");
    if ((int)$n['n'] !== 3) return $ready=false;
    return $ready=(bool)va_one('SELECT id FROM veegame_admin_accounts LIMIT 1');
}
function va_audit(string $action, string $target, array $detail = [], ?int $adminId = null): void {
    va_stmt('INSERT INTO veegame_admin_audit (admin_id,action,target,detail,created_at) VALUES (?,?,?,?,NOW())', [$adminId ?? (int)($_SESSION['admin_id'] ?? 0),$action,$target,json_encode($detail,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)]);
}
function va_clear_session(): void {
    $_SESSION=[];
    if (session_status() === PHP_SESSION_ACTIVE) {
        $p=session_get_cookie_params();
        setcookie(session_name(),'', ['expires'=>time()-3600,'path'=>$p['path'],'secure'=>$p['secure'],'httponly'=>true,'samesite'=>'Strict']);
        session_destroy();
    }
}
function va_auth(): array {
    if (!va_ready()) va_redirect('setup.php');
    if (empty($_SESSION['admin_id'])) va_redirect('login.php');
    $now=time();
    if ($now-(int)($_SESSION['last_seen'] ?? 0)>1800 || $now-(int)($_SESSION['started'] ?? 0)>28800) { va_clear_session(); va_redirect('login.php?expired=1'); }
    $a=va_one('SELECT id,username,password_hash,role,active,session_version FROM veegame_admin_accounts WHERE id=?',[(int)$_SESSION['admin_id']]);
    if (!$a || !(int)$a['active'] || (int)$a['session_version'] !== (int)($_SESSION['version'] ?? 0)) { va_clear_session(); va_redirect('login.php'); }
    $_SESSION['last_seen']=$now; return $a;
}
function va_owner(array $a): void { if ($a['role'] !== 'owner') va_fail(403,'Owner permission required.'); }
function va_confirm_password(array $a): void {
    $p=va_input('current_password');
    if (strlen($p)>128 || !password_verify($p,$a['password_hash'])) va_fail(403,'Admin password confirmation failed.');
}
function va_rate(string $name): bool {
    $db=va_db(); $now=time();
    $keys=[hash('sha256','ip:'.($_SERVER['REMOTE_ADDR'] ?? 'unknown'))=>30,hash('sha256','name:'.strtolower($name))=>10];
    $db->begin_transaction();
    try {
        $ok=true;
        foreach($keys as $key=>$limit) {
            va_stmt('INSERT IGNORE INTO veegame_admin_rate (bucket,window_start,hits) VALUES (?,?,0)',[$key,$now]);
            $r=va_one('SELECT window_start,hits FROM veegame_admin_rate WHERE bucket=? FOR UPDATE',[$key]);
            $start=(int)$r['window_start']; $hits=(int)$r['hits'];
            if ($now-$start>=900) { $start=$now; $hits=0; }
            $hits++; if ($hits>$limit) $ok=false;
            va_stmt('UPDATE veegame_admin_rate SET window_start=?,hits=? WHERE bucket=?',[$start,$hits,$key]);
        }
        $db->commit();
        va_stmt('DELETE FROM veegame_admin_rate WHERE window_start < ? LIMIT 100',[$now-86400]);
        return $ok;
    } catch(Throwable $e) { $db->rollback(); throw $e; }
}
require_once __DIR__.'/data.php';
require_once __DIR__.'/view.php';
