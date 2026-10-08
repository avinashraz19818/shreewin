#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Generates maaninstall.php — a single, self-contained browser installer.

Why: the user has twice extracted the patch zip into the WRONG docroot
(maan1win instead of maanwin), so nothing changed on the site he plays on.
This installer runs from ANY docroot, scans the server for every folder that
contains api/_core/lottery_engine.php, and writes the patched files into all
of them (with .bak-<timestamp> backups).

Usage: python3 gen_maaninstall.py <build_dir> <out.php>
"""
import base64
import os
import sys
import zlib

SRC = sys.argv[1] if len(sys.argv) > 1 else "/home/user/maanfix2"
OUT = sys.argv[2] if len(sys.argv) > 2 else os.path.join(SRC, "maaninstall.php")

FILES = [
    "api/_core/config.php",
    "api/_core/bootstrap.php",
    "api/_core/lottery_engine.php",
    "api/_router.php",
    "api/_draw_router.php",
    "maancheck.php",
    "maandiag.php",
    "maanupdiag.php",
    "PADHO.txt",
]

MARKS = {
    "api/_core/config.php": "LE_REVEAL_AT_COUNTDOWN",
    "api/_core/bootstrap.php": "le_issue_for_time",
    "api/_core/lottery_engine.php": "le_history_page",
    "api/_router.php": "le_history_page",
    "api/_draw_router.php": "le_history_page",
    "maancheck.php": "<?php",
    "maandiag.php": "le_issue_for_time",
    "maanupdiag.php": "maanupdiag.php",
    "PADHO.txt": "FIX #8",
}

import hashlib
payload_lines = []
md5_lines = []
for rel in FILES:
    with open(os.path.join(SRC, rel), "rb") as f:
        raw = f.read()
    md5_lines.append("    %-32s => '%s'," % ("'" + rel + "'", hashlib.md5(raw).hexdigest()))
    co = zlib.compressobj(9, zlib.DEFLATED, -15)   # raw deflate -> PHP gzinflate()
    enc = base64.b64encode(co.compress(raw) + co.flush()).decode("ascii")
    payload_lines.append("    %-32s => '%s'," % ("'" + rel + "'", enc))
PAYLOAD = "\n".join(payload_lines)
MD5S = "\n".join(md5_lines)

mark_lines = []
for rel in FILES:
    mark_lines.append("    %-32s => '%s'," % ("'" + rel + "'", MARKS[rel]))
MARKS_PHP = "\n".join(mark_lines)

PHP = r"""<?php
/**
 * maaninstall.php — MAANWIN 8-FIX AUTO INSTALLER   (2026-10-08)
 * ==============================================================
 * Ye file khud server ke SAARE site-folder dhoondh kar patch laga deti hai,
 * isliye "zip galat folder me extract ho gayi" wali problem khatam.
 *
 * STEP 1: is file ko cPanel File Manager se KISI BHI site ke root me upload
 *         karo (maanwin ho ya maan1win — dono chalega).
 * STEP 2: browser me kholo:   https://<domain>/maaninstall.php
 * STEP 3: "SAB ME INSTALL KARO" dabao.  (Purani file ka backup ban jayega:
 *         <file>.bak-YYYYMMDD-HHMMSS)
 * STEP 4: neeche "DELETE INSTALLER" link se is file ko hata do.
 *
 * Ye installer sirf apne sath packed 8 files hi likhta hai. DB me koi
 * change nahi karta.
 */
ini_set('display_errors', '1');
error_reporting(E_ALL);
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$SELF  = basename(__FILE__);
$HERE  = dirname(__FILE__);
$STAMP = date('Ymd-His');
$LABEL = 'MAANWIN 8-FIX  (fix #8 = ek hi period number har jagah)';

/* ---------------------------------------------------------------- payload */
$PAYLOAD = array(
__PAYLOAD__
);

/* md5: likhne ke baad file theek hai ya nahi, isi se confirm hota hai */
$MD5 = array(
__MD5__
);

/* marker: naye code me ye string maujood honi chahiye (verify ke liye) */
$MARK = array(
__MARKS__
);

function mw_dec($s) {
    $raw = base64_decode($s, true);
    if ($raw === false) return false;
    if (function_exists('gzinflate'))    { $t = @gzinflate($raw);    if ($t !== false && $t !== '') return $t; }
    if (function_exists('gzdecode'))     { $t = @gzdecode($raw);     if ($t !== false && $t !== '') return $t; }
    if (function_exists('gzuncompress')) { $t = @gzuncompress($raw); if ($t !== false && $t !== '') return $t; }
    return $raw;
}

/** server par saare site-folder dhoondho jahan api/_core/lottery_engine.php ho */
function mw_targets() {
    global $HERE;
    $out = array();
    if (is_dir($HERE_G . '/api/_core')) $out[$HERE_G] = 1;
    $seen = array();
    $dirs = array(dirname($HERE_G), dirname(dirname($HERE_G)), '/home');
    foreach ($dirs as $d) {
        if (!is_dir($d)) continue;
        $g = @glob(rtrim($d, '/') . '/*/api/_core/lottery_engine.php');
        if (!$g) $g = array();
        $g2 = @glob(rtrim($d, '/') . '/*/*/api/_core/lottery_engine.php');
        if (!$g2) $g2 = array();
        foreach (array_merge($g, $g2) as $hit) {
            $root = dirname(dirname(dirname($hit)));
            if (is_dir($root)) $out[$root] = 1;
        }
    }
    $out = array_keys($out);
    sort($out);
    return $out;
}

/** kya is folder me patch laga hai? */
function mw_state($dir) {
    $f = rtrim($dir, '/') . '/api/_core/lottery_engine.php';
    if (!is_file($f)) return 'no-engine';
    $s = @file_get_contents($f);
    if ($s === false) return 'unreadable';
    $ok = 0;
    foreach (array('le_issue_sequence', 'le_issue_prev', 'le_history_page', 'le_bet_period_is_closed') as $m) {
        if (strpos($s, $m) !== false) $ok++;
    }
    return $ok >= 4 ? 'PATCHED (8-fix)' : ($ok > 0 ? 'PARTIAL (' . $ok . '/4)' : 'OLD / PATCH NAHI LAGA');
}

function mw_writable($dir) {
    $t = rtrim($dir, '/') . '/api/_core/lottery_engine.php';
    if (is_file($t)) return is_writable($t);
    return is_writable(rtrim($dir, '/'));
}

/* ------------------------------------------------------------ self delete */
if (isset($_GET['mwdel'])) {
    $ok = @unlink(__FILE__);
    echo '<!doctype html><meta charset="utf-8"><body style="font-family:system-ui;padding:24px">';
    echo '<h2>' . ($ok ? 'maaninstall.php DELETE ho gaya.' : 'DELETE FAIL — cPanel File Manager se manually delete kar do.') . '</h2>';
    echo '<p>Files install rehti hain. Ab <code>/maandiag.php</code> kholo.</p></body>';
    exit;
}

/* ---------------------------------------------------------------- install */
$report = array();
$done   = false;
if (isset($_POST['do'])) {
    $targets = array();
    if (!empty($_POST['t']) && is_array($_POST['t'])) {
        foreach ($_POST['t'] as $t) { $t = trim($t); if ($t !== '' && is_dir($t)) $targets[] = $t; }
    }
    if (!empty($_POST['extra'])) {
        $t = trim($_POST['extra']);
        if ($t !== '' && is_dir($t)) $targets[] = rtrim($t, '/');
    }
    $targets = array_values(array_unique($targets));
    if (!$targets) {
        $report[] = array('SKIP', '-', 'Koi folder select nahi hua.');
    }
    foreach ($targets as $dir) {
        foreach ($PAYLOAD as $rel => $blob) {
            $data = mw_dec($blob);
            $dest = rtrim($dir, '/') . '/' . $rel;
            if ($data === false || $data === '') { $report[] = array('FAIL', $dest, 'packed data khol nahi saka (zlib?)'); continue; }
            if (isset($MD5[$rel]) && md5($data) !== $MD5[$rel]) { $report[] = array('FAIL', $dest, 'packed data kharaab (md5 mismatch)'); continue; }
            $ddir = dirname($dest);
            if (!is_dir($ddir)) { @mkdir($ddir, 0755, true); }
            if (!is_dir($ddir)) { $report[] = array('FAIL', $dest, 'folder nahi ban saka: ' . $ddir); continue; }
            if (is_file($dest)) {
                $bak = $dest . '.bak-' . $STAMP;
                if (!@copy($dest, $bak)) { $report[] = array('FAIL', $dest, 'backup nahi ban saka (permission?)'); continue; }
            }
            $w = @file_put_contents($dest, $data, LOCK_EX);
            if ($w === false || $w < 1) { $report[] = array('FAIL', $dest, 'write nahi ho saka (permission?)'); continue; }
            @chmod($dest, 0644);
            if (function_exists('opcache_invalidate')) { @opcache_invalidate($dest, true); }
            $back = @file_get_contents($dest);
            $mark = isset($MARK[$rel]) ? $MARK[$rel] : '';
            $good = ($back !== false && md5($back) === md5($data) && ($mark === '' || strpos($back, $mark) !== false));
            $report[] = array($good ? 'OK' : 'CHECK', $dest, $good ? (strlen($data) . ' bytes — md5 OK') : 'write hua par verify fail (md5 mismatch)');
        }
    }
    clearstatcache();
    $done = true;
}

/* ------------------------------------------------------------------- view */
$targets = mw_targets();
if (!$targets) $targets = array($HERE);
$reportText = '';
foreach ($report as $r) { $reportText .= $r[0] . "\t" . $r[1] . "\t" . $r[2] . "\n"; }

echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
echo '<title>MAANWIN installer</title>';
echo '<style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#0f1115;color:#e6e6e6;margin:0;padding:18px}';
echo 'h1{font-size:19px;margin:0 0 4px}h2{font-size:15px;margin:22px 0 8px;color:#9ad}';
echo '.box{background:#171a21;border:1px solid #2a2f3a;border-radius:10px;padding:14px;margin-bottom:14px}';
echo 'table{border-collapse:collapse;width:100%;font-size:13px}th,td{border-bottom:1px solid #2a2f3a;padding:7px 6px;text-align:left;vertical-align:top}';
echo 'th{color:#9ad;font-weight:600}.mono{font-family:ui-monospace,Menlo,Consolas,monospace;font-size:12px;word-break:break-all}';
echo 'button{background:#1f7a4d;color:#fff;border:0;border-radius:8px;padding:11px 18px;font-size:15px;cursor:pointer}';
echo 'button.alt{background:#3a3f4b}.ok{color:#5fd08a;font-weight:600}.bad{color:#ff7a7a;font-weight:600}.warn{color:#ffd166}';
echo 'textarea{width:100%;height:120px;background:#0b0d11;color:#cfe;border:1px solid #2a2f3a;border-radius:8px;font-family:ui-monospace,monospace;font-size:12px}';
echo 'a{color:#7db9ff}</style></head><body>';

echo '<div class="box"><h1>' . $LABEL . '</h1>';
echo '<div class="mono">installer chal raha hai: <b>' . htmlspecialchars($HERE) . '</b><br>';
echo 'domain: <b>' . htmlspecialchars(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '-') . '</b> &nbsp;|&nbsp; ';
echo 'PHP ' . phpversion() . ' &nbsp;|&nbsp; ' . date('Y-m-d H:i:s T') . '</div></div>';

if ($done) {
    echo '<div class="box"><h2>RESULT</h2><table><tr><th>state</th><th>file</th><th>detail</th></tr>';
    foreach ($report as $r) {
        $cls = $r[0] === 'OK' ? 'ok' : ($r[0] === 'CHECK' ? 'warn' : 'bad');
        echo '<tr><td class="' . $cls . '">' . htmlspecialchars($r[0]) . '</td><td class="mono">' . htmlspecialchars($r[1]) . '</td><td>' . htmlspecialchars($r[2]) . '</td></tr>';
    }
    echo '</table>';
    $allok = true;
    foreach ($report as $r) { if ($r[0] !== 'OK') $allok = false; }
    echo '<p class="' . ($allok ? 'ok' : 'bad') . '">' . ($allok ? 'SAB THEEK — ab niche diye gaye CHECK links kholo.' : 'KUCH FILES FAIL HUI HAIN — neeche report copy kar ke developer ko bhejo.') . '</p>';
    echo '<p>Ab ye karo: har site par <span class="mono">/maandiag.php</span> kholo aur section <b>[9]</b> me <span class="ok">MATCH? : YES</span> dekho. Phir cPanel me <b>Purge All</b> (cache clear) kar do.</p>';
    echo '<h2>REPORT (copy kar ke developer ko bhej sakte ho)</h2><textarea readonly>' . htmlspecialchars($reportText) . '</textarea>';
    echo '<p><a href="?mwdel=1"><button class="alt" type="button">DELETE INSTALLER (maaninstall.php hata do)</button></a></p></div>';
}

echo '<form method="post">';
echo '<div class="box"><h2>MILE HUE SITE FOLDER</h2>';
echo '<p style="font-size:13px;color:#9aa">Server par jitne bhi folder me <span class="mono">api/_core/lottery_engine.php</span> mila, wo sab niche hain. Dono site (maanwin + maan1win) select rehne do — dono me EK HI period number chalega, tabhi drawer aur game match karenge.</p>';
echo '<table><tr><th>install?</th><th>folder (docroot)</th><th>abhi kya chal raha hai</th><th>likh sakte hain?</th></tr>';
$i = 0;
foreach ($targets as $t) {
    $st = mw_state($t);
    $wr = mw_writable($t);
    $cls = (strpos($st, 'PATCHED') === 0) ? 'ok' : ((strpos($st, 'PARTIAL') === 0) ? 'warn' : 'bad');
    echo '<tr><td><input type="checkbox" name="t[]" value="' . htmlspecialchars($t) . '"' . ($i === 0 ? ' checked' : ' checked') . '></td>';
    echo '<td class="mono">' . htmlspecialchars($t) . '</td>';
    echo '<td class="' . $cls . '">' . htmlspecialchars($st) . '</td>';
    echo '<td class="' . ($wr ? 'ok' : 'bad') . '">' . ($wr ? 'yes' : 'NO (permission problem)') . '</td></tr>';
    $i++;
}
echo '</table>';
echo '<p style="font-size:13px">Agar upar koi folder missing hai to yahan manually path likho (jaise <span class="mono">/home/club532583/maanwin.club9.eu.cc</span>):<br>';
echo '<input type="text" name="extra" style="width:100%;max-width:520px;padding:8px;background:#0b0d11;color:#cfe;border:1px solid #2a2f3a;border-radius:6px" placeholder="/home/club532583/........"></p>';
echo '<p><button type="submit" name="do" value="1">SAB ME INSTALL KARO</button></p></div>';
echo '</form>';

echo '<div class="box"><h2>INSTALL KE BAAD CHECK KARO</h2><ul style="font-size:13px;line-height:1.7">';
foreach ($targets as $t) {
    $host = basename($t);
    echo '<li><span class="mono">https://' . htmlspecialchars($host) . '/maandiag.php</span> &nbsp;→ section <b>[9]</b>: <span class="ok">MATCH? : YES</span> hona chahiye</li>';
}
echo '<li>cPanel → <b>Purge All</b> / cache clear, aur app me ek baar logout-login</li>';
echo '</ul><p style="font-size:13px;color:#9aa">Kaam ho jane ke baad is file ko delete kar dena (upar DELETE INSTALLER button).</p></div>';

echo '</body></html>';
"""

PHP = PHP.replace("__PAYLOAD__", PAYLOAD).replace("__MARKS__", MARKS_PHP).replace("__MD5__", MD5S)
PHP = PHP.replace("$HERE_G", "$HERE")

with open(OUT, "w", encoding="utf-8") as f:
    f.write(PHP)

print("wrote", OUT, os.path.getsize(OUT), "bytes")
for rel in FILES:
    print("   packed %-32s %7d bytes" % (rel, os.path.getsize(os.path.join(SRC, rel))))
