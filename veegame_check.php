<?php
/**
 * VeerGame deployment self-check (open in a browser, then delete).
 *
 *   https://your-domain/veegame_check.php
 *
 * It answers the questions that decide whether WinGo / K3 / 5D / TrxWinGo /
 * MotoRace can work on this server:
 *   1. PHP version and the mysqli features the code needs (mysqlnd/get_result)
 *   2. Whether the files the lottery engine loads are actually uploaded
 *   3. Whether the database is reachable and the core tables exist
 *   4. Whether the server can reach the public draw feed (results provider)
 *   5. Whether the URL rewrites this deployment relies on are effective
 *
 * The script only reads: it never writes, never bets and never prints secrets.
 * Remove the file once everything shows OK.
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

header('Content-Type: text/plain; charset=utf-8');

$root = __DIR__;
$lines = array();
$warn = 0;
$fail = 0;

function line($status, $label, $detail = '')
{
    global $lines, $warn, $fail;
    $tag = $status === 'OK' ? 'OK   ' : ($status === 'WARN' ? 'WARN ' : 'FAIL ');
    if ($status === 'WARN') {
        $warn++;
    } elseif ($status === 'FAIL') {
        $fail++;
    }
    $lines[] = $tag . str_pad($label, 46, ' ') . ($detail !== '' ? ' | ' . $detail : '');
}

$lines[] = 'VeerGame deployment check - ' . date('Y-m-d H:i:s');
$lines[] = 'Document root: ' . $root;
$lines[] = str_repeat('-', 100);

/* ------------------------------------------------------------------ 1. PHP */
line('OK', 'PHP version', PHP_VERSION . ' (' . PHP_SAPI . ')');
line(version_compare(PHP_VERSION, '7.4', '>=') ? 'OK' : 'FAIL', 'PHP >= 7.4 required', '');

line(extension_loaded('mysqli') ? 'OK' : 'FAIL', 'mysqli extension', extension_loaded('mysqli') ? 'loaded' : 'missing - install/enable mysqli');

$mysqlnd = function_exists('mysqli_get_client_info') && stripos((string) mysqli_get_client_info(), 'mysqlnd') !== false;
line($mysqlnd ? 'OK' : 'WARN', 'mysqlnd driver', $mysqlnd ? 'present' : 'missing - the included mysqli_compat.php patch covers get_result()');

if (class_exists('mysqli_stmt')) {
    $hasGetResult = method_exists('mysqli_stmt', 'get_result');
    line($hasGetResult ? 'OK' : 'WARN', 'mysqli_stmt::get_result()', $hasGetResult ? 'native' : 'not native - app_stmt_result()/mysqli_compat.php is used instead');
}

line(function_exists('curl_init') ? 'OK' : 'WARN', 'curl extension', function_exists('curl_init') ? 'present' : 'missing - falling back to file_get_contents for the draw feed');
line(function_exists('json_decode') ? 'OK' : 'FAIL', 'json extension', '');
line(function_exists('openssl_encrypt') ? 'OK' : 'WARN', 'openssl extension', '');
line(date_default_timezone_get() !== '' ? 'OK' : 'WARN', 'default timezone', date_default_timezone_get());

/* ------------------------------------------------------------- 2. Files */
$required = array(
    'mysqli_compat.php'                                  => 'mysqlnd-free database layer',
    'same-origin-lottery.js'                             => 'frontend API/draw bridge',
    'index.html'                                         => 'application shell',
    'developer-maruf/conn.php'                           => 'database bootstrap',
    'developer-maruf/error_logger.php'                   => 'error logger (was missing before the fix)',
    'developer-maruf/functions2.php'                     => 'shared helpers',
    'developer-maruf/app_core_live_v4.php'               => 'application core',
    'developer-maruf/vip_core.php'                       => 'VIP core',
    'saas_lottery/bootstrap_live_v4.php'                 => 'lottery engine',
    'saas_lottery/admin_override.php'                    => 'lottery admin override',
    'saas_lottery/config_live_v4.php'                    => 'lottery config',
    'api-live-v4/Lottery/index.php'                      => 'authenticated lottery API',
    'draw-live-v4/index.php'                             => 'public draw feed',
    'web/config'                                         => 'runtime domain config',
    '.htaccess'                                          => 'rewrite rules',
);
foreach ($required as $rel => $why) {
    $path = $root . '/' . $rel;
    line(is_file($path) ? 'OK' : 'FAIL', $rel, is_file($path) ? filesize($path) . ' bytes' : 'MISSING (' . $why . ')');
}

/* Frontend chunk referenced by index.html must exist. */
$shell = @file_get_contents($root . '/index.html');
if (is_string($shell)) {
    if (preg_match_all('~<(?:script|link)[^>]+(?:src|href)="(/assets/(?:js|css)/[^"]+)"~', $shell, $m)) {
        $missing = array();
        foreach (array_unique($m[1]) as $asset) {
            if (!is_file($root . $asset)) {
                $missing[] = $asset;
            }
        }
        line($missing ? 'FAIL' : 'OK', 'shell assets present', $missing ? implode(', ', array_slice($missing, 0, 5)) : count(array_unique($m[1])) . ' files');
    }
    line(strpos($shell, 'same-origin-lottery.js') !== false ? 'OK' : 'FAIL', 'index.html loads the API bridge', '');
}

/* ---------------------------------------------------------- 3. Database */
$dbOk = false;
$conn = null;
$connFile = $root . '/developer-maruf/conn.php';
if (is_file($connFile)) {
    $cfg = @file_get_contents($connFile);
    $user = 'unknown';
    $name = 'unknown';
    if (is_string($cfg)) {
        if (preg_match("~define\\('DB_USERNAME',\\s*'([^']+)'~", $cfg, $m)) {
            $user = $m[1];
        }
        if (preg_match("~define\\('DB_NAME',\\s*'([^']+)'~", $cfg, $m)) {
            $name = $m[1];
        }
    }
    line('OK', 'database config detected', 'user=' . $user . ' db=' . $name);
    if (function_exists('mysqli_report')) {
        mysqli_report(MYSQLI_REPORT_OFF);
    }
    if (preg_match("~define\\('DB_SERVER',\\s*'([^']+)'~", (string) $cfg, $ms)
        && preg_match("~define\\('DB_PASSWORD',\\s*'([^']*)'~", (string) $cfg, $mp)) {
        $conn = @mysqli_connect($ms[1], $user, $mp[1], $name);
        if ($conn instanceof mysqli) {
            $dbOk = true;
            line('OK', 'database connection', 'connected');
            $tables = array('shonu_subjects', 'shonu_kaichila', 'saas_lottery_bets', 'saas_lottery_results');
            foreach ($tables as $t) {
                $res = @$conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($t) . "'");
                $exists = $res instanceof mysqli_result && $res->num_rows > 0;
                line($exists ? 'OK' : 'WARN', 'table ' . $t, $exists ? 'present' : 'missing - will be created on first use');
            }
            $res = @$conn->query('SELECT COUNT(*) AS c FROM saas_lottery_bets');
            if ($res instanceof mysqli_result) {
                $row = $res->fetch_assoc();
                line('OK', 'bets already stored', (string) ($row['c'] ?? '0') . ' rows');
            } else {
                line('WARN', 'bets table readable', 'no (created on first bet)');
            }
        } else {
            line('FAIL', 'database connection', mysqli_connect_error());
        }
    } else {
        line('WARN', 'database config parse', 'could not read DB_SERVER/DB_PASSWORD');
    }
}

/* ------------------------------------------------ 4. Draw feed (provider) */
$gameCode = 'WinGo_30S';
$base = 'https://draw.ar-lottery01.com';
if (is_file($root . '/saas_lottery/config_live_v4.php')) {
    $conf = @file_get_contents($root . '/saas_lottery/config_live_v4.php');
    if (is_string($conf) && preg_match("~'draw_base_url'\\s*=>\\s*'([^']+)'~", $conf, $m)) {
        $base = rtrim($m[1], '/');
    }
}
line('OK', 'draw feed base url', $base);

$probe = function ($url) {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; VeerGame-Check/1.0)',
            CURLOPT_HTTPHEADER => array('Accept: application/json'),
        ));
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        return array($status, is_string($body) ? $body : '', $err);
    }
    $body = @file_get_contents($url);
    return array(is_string($body) ? 200 : 0, is_string($body) ? $body : '', 'file_get_contents');
};

list($status, $body, $err) = $probe($base . '/WinGo/' . $gameCode . '.json?ts=' . (int) (microtime(true) * 1000));
if ($status === 200) {
    $json = json_decode($body, true);
    if (is_array($json) && !empty($json['current']['issueNumber'])) {
        line('OK', 'current draw feed reachable', 'issue ' . $json['current']['issueNumber']);
    } else {
        line('WARN', 'current draw feed body', substr((string) $body, 0, 120));
    }
} else {
    line('FAIL', 'current draw feed reachable', 'HTTP ' . $status . ' ' . $err);
}

list($status2, $body2, $err2) = $probe($base . '/WinGo/' . $gameCode . '/GetHistoryIssuePage.json?pageNo=1&pageSize=10&ts=' . (int) (microtime(true) * 1000));
if ($status2 === 200) {
    $json2 = json_decode($body2, true);
    $count = is_array($json2) && isset($json2['list']) && is_array($json2['list']) ? count($json2['list']) : 0;
    line($count > 0 ? 'OK' : 'WARN', 'history feed reachable', $count . ' rows');
} else {
    line('FAIL', 'history feed reachable', 'HTTP ' . $status2 . ' ' . $err2);
}

/* ------------------------------------------------- 5. Local endpoints */
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
$selfBase = $scheme . '://' . $host;
line('OK', 'checking against', $selfBase);

$local = array(
    'draw feed endpoint'   => '/draw-live-v4/index.php?lottery=WinGo&gameCode=WinGo_30S',
    'history endpoint'     => '/draw-live-v4/index.php?lottery=WinGo&gameCode=WinGo_30S&history=1&pageNo=1&pageSize=10',
    'game list route'      => '/api/Lottery/GetGameList',
    'aliased api route'    => '/api-live-v4/Lottery/index.php?action=GetGameList',
    'frontend bridge file' => '/same-origin-lottery.js',
);
foreach ($local as $label => $path) {
    list($code, $out, $e) = $probe($selfBase . $path);
    $snippet = trim(substr((string) $out, 0, 90));
    $ok = $code === 200 && stripos($snippet, '<?php') === false && stripos($snippet, '<br') === false;
    line($ok ? 'OK' : 'FAIL', $label, 'HTTP ' . $code . ($snippet !== '' ? ' | ' . $snippet : ' | empty response'));
}

/* ---------------------------------------------------------- 6. Log hint */
$logFiles = array('error.log', 'developer-maruf/error_log', 'draw-live-v4/error_log', 'api-live-v4/Lottery/error_log');
foreach ($logFiles as $lf) {
    $p = $root . '/' . $lf;
    if (is_file($p) && filesize($p) > 0) {
        $tail = @file($p);
        $last = is_array($tail) && $tail ? trim((string) $tail[count($tail) - 1]) : '';
        line('WARN', 'log ' . $lf, substr($last, 0, 110));
    }
}

$lines[] = str_repeat('-', 100);
$lines[] = 'Result: ' . $fail . ' failure(s), ' . $warn . ' warning(s).';
$lines[] = $fail === 0
    ? 'If warnings are only about mysqlnd or missing tables, the lottery can still run: the patches handle them.'
    : 'Fix the FAIL lines first - the lottery API cannot answer while they are present.';
$lines[] = 'Delete this file (veegame_check.php) from the server when you are done.';

echo implode("\n", $lines) . "\n";
