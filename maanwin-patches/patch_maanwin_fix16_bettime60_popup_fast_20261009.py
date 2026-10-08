#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
FIX #16 — DO CHEEZEIN THEEK:

 1) BET KA TIME = 1 MINUTE PICHE  (user ne khud likh kar diya)
        galat : 04:34:20 -> 04:34:19      (1 second)
        SAHI  : 04:34:20 -> 04:33:20      (1 minute)
    LE_BET_TIME_LEAD_SEC = 60

 2) WIN / LOSS POPUP — 5 SECOND KI DELAY HATANA
    Frontend (assets/js/useWinGo3-*.js) me ye likha hai:
        countdown 1 par:
          await sleep(800)  ->  history fetch  ->  setTimeout(800)
                                               ->  setTimeout(2200)
    Matlab app khud 3 second ka rasta rakhta hai. Upar se hamari
    /api/Lottery/GetHistoryIssuePage har baar 3 PAGE upstream
    (draw.ar-lottery01.com) se mangwa rahi thi kyunki
    LE_UPSTREAM_CACHE_SEC sirf 3 second tha  ->  3 HTTP call ~3 second.
    Total ~5 second. YAHI "5 sec baad popup" KI WAJAH HAI.

    FIX (frontend ko chhue bina — JS file server par alag ho sakti hai):
      * LE_UPSTREAM_CACHE_SEC  3  -> 15   (har period 30 second chalta hai,
                                          15 second purana data chal jata hai)
      * LE_UPSTREAM_TIMEOUT    3  ->  2   (slow ho to jaldi chhod do)
      * upstream FAIL ho ya cache purana ho -> PURANA CACHED DATA hi de do
        (site rukti nahi, result galat nahi hota, aur LATENCY nahi badhti)
      * fail marker ab alag file me  ->  purana data miss nahi hota

    Nateeja: history call ab ~50-200 ms me aayegi, popup ~0.5 second ke
    andar (app ka 800 ms ka wait alag hai, wo JS me hai).

 3) maanwin.par patch lagane ke liye installer aur tez:
    mw_targets() ab /home ke andar 3 level tak dhoondhta hai
    (addon-domain /home/user/domains/site, /home/user/public_html waghera).
"""
import io, os, sys

ROOT = "/home/user/maanfix2"
ENG = os.path.join(ROOT, "api/_core/lottery_engine.php")
CFG = os.path.join(ROOT, "api/_core/config.php")
DIAG = os.path.join(ROOT, "maandiag.php")
GEN = "/home/user/shreewin/maanwin-patches/gen_maaninstall.py"

ok = 0
fail = []


def rep(path, old, new, count=1, tag=""):
    global ok
    with io.open(path, "r", encoding="utf-8") as f:
        s = f.read()
    n = s.count(old)
    if n != count:
        fail.append("%s  (%s): expected %d, found %d" % (tag, os.path.basename(path), count, n))
        return
    s = s.replace(old, new, count)
    with io.open(path, "w", encoding="utf-8") as f:
        f.write(s)
    ok += 1
    print("  OK  %-58s  (%s)" % (tag, os.path.basename(path)))


# =====================================================================
# 1) config.php — bet time 1 MINUTE + upstream cache/timeout
# =====================================================================
rep(CFG,
"""//       1 = 1 second piche   (DEFAULT)   jaise 04:14:30  ->  04:14:29
//      60 = 1 minute piche               jaise 04:14:30  ->  04:13:30
//       0 = asli time (koi badlav nahi)""",
"""//      60 = 1 MINUTE piche  (DEFAULT)   jaise 04:34:20  ->  04:33:20
//       1 = 1 second piche               jaise 04:34:20  ->  04:34:19
//       0 = asli time (koi badlav nahi)""",
    1, "config: comment (1 minute default)")

rep(CFG,
"""if (!defined('LE_BET_TIME_LEAD_SEC'))   define('LE_BET_TIME_LEAD_SEC', 1);""",
"""if (!defined('LE_BET_TIME_LEAD_SEC'))   define('LE_BET_TIME_LEAD_SEC', 60);""",
    1, "config: LE_BET_TIME_LEAD_SEC = 60")

rep(CFG,
"""if (!defined('LE_UPSTREAM_TIMEOUT'))    define('LE_UPSTREAM_TIMEOUT', 3);
if (!defined('LE_UPSTREAM_CACHE_SEC'))  define('LE_UPSTREAM_CACHE_SEC', 3);""",
"""if (!defined('LE_UPSTREAM_TIMEOUT'))    define('LE_UPSTREAM_TIMEOUT', 2);
//
//   CACHE KITNE SECOND (FIX #16 — popup ki 5 second ki delay yahin se thi)
//     3  = har baar 3 page upstream se mangwana -> ~3 second lagta tha
//     15 = DEFAULT. Ek period 30 second (ya usse zyada) chalta hai, isliye
//          15 second purana data bilkul theek chal jata hai.
//          Natija: history call ~50-200 ms, popup ~0.5 second me.
//     30 = aur tez, bas naya period 30 second tak late dikh sakta hai
if (!defined('LE_UPSTREAM_CACHE_SEC'))  define('LE_UPSTREAM_CACHE_SEC', 15);""",
    1, "config: LE_UPSTREAM_TIMEOUT=2, CACHE_SEC=15")

# =====================================================================
# 2) engine — cache purana ho ya upstream fail ho to PURANA DATA de do
# =====================================================================
rep(ENG,
"""    $file = le_upstream_cache_file(md5($url));
    if (!$raw && isset($mem[$url])) {
        $m = $mem[$url];
        if (is_array($m) && (time() - (int)$m[0]) <= $ttl) return $m[1];
    }
    if (!$raw && is_file($file)) {
        $age = time() - (int)@filemtime($file);
        $txt = @file_get_contents($file);
        if (is_string($txt) && $txt !== '') {
            $c = json_decode($txt, true);
            if (is_array($c) && isset($c['t'])) {
                $isFail = !empty($c['fail']);
                $limit  = $isFail ? $failTtl : $ttl;
                if ($age <= $limit) {
                    $mem[$url] = [time(), $isFail ? null : ($c['data'] ?? null)];
                    return $isFail ? null : ($c['data'] ?? null);
                }
            }
        }
    }
""",
"""    $file  = le_upstream_cache_file(md5($url));
    $ffile = $file . '.fail';
    // FIX #16 — purana (stale) data yaad rakho. Cache expire ho jaye ya
    // upstream slow/403 ho, tab bhi PURANA data hi de dete hain: result
    // galat nahi hota (period ka result badalta nahi) aur LATENCY nahi
    // badhti — popup 5 second late hone ki wajah yahi thi.
    $stale = null;
    if (!$raw && isset($mem[$url])) {
        $m = $mem[$url];
        if (is_array($m) && (time() - (int)$m[0]) <= $ttl) return $m[1];
    }
    if (!$raw && is_file($file)) {
        $age = time() - (int)@filemtime($file);
        $txt = @file_get_contents($file);
        if (is_string($txt) && $txt !== '') {
            $c = json_decode($txt, true);
            if (is_array($c) && isset($c['t'], $c['data']) && is_array($c['data'])) {
                $stale = $c['data'];
                if ($age <= $ttl) {
                    $mem[$url] = [time(), $stale];
                    return $stale;
                }
            }
        }
    }
    // pichli call fail hui thi to thodi der tak dobara mat karo —
    // purana data hi de do (site rukti nahi).
    if (!$raw && is_file($ffile)) {
        $age = time() - (int)@filemtime($ffile);
        if ($age <= $failTtl) {
            $mem[$url] = [time(), $stale];
            return $stale;
        }
    }
""",
    1, "engine: stale-data fallback (popup fast)")

rep(ENG,
"""    if (!is_string($body) || trim($body) === '') {
        if (!$raw) @file_put_contents($file, json_encode(['t' => time(), 'fail' => 1]), LOCK_EX);
        $mem[$url] = [time(), null];
        return null;
    }
    $json = json_decode($body, true);
    if (!is_array($json)) {
        if (!$raw) @file_put_contents($file, json_encode(['t' => time(), 'fail' => 1]), LOCK_EX);
        $mem[$url] = [time(), null];
        return null;
    }
    if (!$raw) @file_put_contents($file, json_encode(['t' => time(), 'fail' => 0, 'data' => $json]), LOCK_EX);
    $mem[$url] = [time(), $json];
    return $json;""",
"""    if (!is_string($body) || trim($body) === '') {
        // FIX #16 — fail marker ALAG file me, taaki purana data bacha rahe
        if (!$raw) @file_put_contents($ffile, json_encode(['t' => time()]), LOCK_EX);
        $mem[$url] = [time(), $stale];
        return $stale;
    }
    $json = json_decode($body, true);
    if (!is_array($json)) {
        if (!$raw) @file_put_contents($ffile, json_encode(['t' => time()]), LOCK_EX);
        $mem[$url] = [time(), $stale];
        return $stale;
    }
    if (!$raw) {
        @file_put_contents($file, json_encode(['t' => time(), 'fail' => 0, 'data' => $json]), LOCK_EX);
        if (is_file($ffile)) @unlink($ffile);
    }
    $mem[$url] = [time(), $json];
    return $json;""",
    1, "engine: fail-marker alag file me")

# =====================================================================
# 3) maandiag [14] — 60 second / 1 minute saaf likha ho
# =====================================================================
rep(DIAG,
"""echo '    LE_BET_TIME_LEAD_SEC  : ' . (function_exists('le_bet_time_lead') ? (string)le_bet_time_lead() . ' second' : '<< NOT DEFINED >>') . "\\n";""",
"""$btl = function_exists('le_bet_time_lead') ? le_bet_time_lead() : 0;
echo '    LE_BET_TIME_LEAD_SEC  : ' . (string)$btl . ' second' . ($btl >= 60 ? '   (= ' . ($btl / 60) . ' MINUTE)' : '') . "\\n";
echo '    LE_UPSTREAM_CACHE_SEC : ' . (defined('LE_UPSTREAM_CACHE_SEC') ? (string)LE_UPSTREAM_CACHE_SEC . ' second' : '<< NOT DEFINED >>')
     . "   (3 tha -> popup 5 sec late; ab 15)\\n";""",
    1, "maandiag [14]: minute + cache line")

# =====================================================================
# 4) installer — maanwin ka folder dhoondhne ke liye deep scan
# =====================================================================
rep(GEN,
"""    $dirs = array(dirname($HERE_G), dirname(dirname($HERE_G)), '/home');
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
    }""",
"""    $dirs = array(dirname($HERE_G), dirname(dirname($HERE_G)), dirname(dirname(dirname($HERE_G))), '/home', '/home1', '/var/www');
    foreach ($dirs as $d) {
        if (!is_dir($d)) continue;
        // FIX #16 — GAHRA scan: addon domain kabhi /home/user/domains/site
        // ya /home/user/public_html me hota hai. 3 level tak dhoondo.
        for ($depth = 1; $depth <= 3; $depth++) {
            $g = @glob(rtrim($d, '/') . str_repeat('/*', $depth) . '/api/_core/lottery_engine.php');
            if (!$g) continue;
            foreach ($g as $hit) {
                $root = dirname(dirname(dirname($hit)));
                if (is_dir($root)) $out[$root] = 1;
            }
        }
    }""",
    1, "gen_maaninstall: deep scan (maanwin milega)")

rep(GEN,
"""    "maanupdiag.php",
    "PADHO.txt",
]""",
"""    "maanupdiag.php",
    "maanwhere.php",
    "PADHO.txt",
]""",
    1, "gen_maaninstall: maanwhere.php file list me")

rep(GEN,
"""    "maanupdiag.php": "maanupdiag.php",
    "PADHO.txt": "FIX #8",""",
"""    "maanupdiag.php": "maanupdiag.php",
    "maanwhere.php": "<?php",
    "PADHO.txt": "FIX #8",""",
    1, "gen_maaninstall: maanwhere.php marker")

print("")
print("replacements OK : %d" % ok)
if fail:
    print("FAILED          : %d" % len(fail))
    for f in fail:
        print("   - " + f)
    sys.exit(1)
print("SAB THEEK")
