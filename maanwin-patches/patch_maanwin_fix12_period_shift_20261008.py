#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
MAANWIN — FIX #12 (2026-10-08)  — FIX #11 KI GALTI THEEK
==========================================================
FIX #11 me maine sirf RESULT ko ek period piche kar diya tha:
    period N  ->  result(N-1)          <-- GALAT: jodi toot gayi
User: "result piche karna tha, tumne to result hi change kar diya —
       ab dusra aa raha hai, API wala nahi."

SAHI MATLAB:
    site par period bhi ek piche chalega AUR uska result bhi —
    DONO EK SATH. Jodi kabhi nahi tootti:

        API period N chal raha ho
          -> site par period (N-1) chalega
          -> screen par (N-1) ka API WALA result dikhega

    (pehle: site par period N tha aur result(N) dikhta tha)

FIX #12:
  * LE_PERIOD_SHIFT = -1  (naya switch, default)
        site ka period number = API ka period number - 1
        timer/start/end wahi rahte hain (sirf LABEL piche jata hai)
  * LE_RESULT_SHIFT = 0   (FIX #11 wala shift BAND)
        period N par hamesha N ka API wala result
  * le_issue_drift() me PERIOD_SHIFT SHAMIL NAHI — nahi to drift usko
    wapas aage kar deta. Drift sirf "API aur formula me farq" nikalta hai.
"""
import os
import sys

SRC = sys.argv[1] if len(sys.argv) > 1 else "/home/user/maanfix2"
FAILS = []


def rep(path, old, new, count=1):
    full = os.path.join(SRC, path)
    with open(full, "r", encoding="utf-8") as f:
        s = f.read()
    n = s.count(old)
    if n != count:
        FAILS.append("%s: expected %d match(es), found %d for %r" % (path, count, n, old[:70]))
        return
    with open(full, "w", encoding="utf-8") as f:
        f.write(s.replace(old, new))
    print("  ok  %-34s (%d)" % (path, n))


# ------------------------------------------------------------ 1. config
rep(
    "api/_core/config.php",
    """// 6) RESULT KITNE PERIOD PICHE DIKHE  (2026-10-08, user ki demand)
//      -1 = result EK period piche.  Period N chal raha ho to screen par
//           period (N-1) ka result dikhega; N ka result N khatam hote hi
//           (tab current period N+1 ho jata hai) aa jata hai.
//           Isse current period ka result timer khatam hone se PAHLE nahi
//           dikhata — betting ke liye sahi behaviour.
//        0 = koi shift nahi (result bilkul upstream jaisa, FIX #10 wala)
//       -2 = 2 period piche (zarurat pade to)
if (!defined('LE_RESULT_SHIFT'))       define('LE_RESULT_SHIFT', -1);""",
    """// 6) SITE, API (draw.ar-lottery01.com) SE KITNE PERIOD PICHE CHALE
//      -1 = site hamesha API se EK period piche chalegi:
//             API par period N chal raha ho  ->  site par period (N-1)
//             aur screen par (N-1) ka API WALA result dikhega.
//           PERIOD aur RESULT DONO EK SATH piche jate hain, isliye
//           "period + result" ki jodi KABHI nahi tootti.
//        0 = site API ke barabar chalegi (FIX #10 wala behaviour)
//       -2 = 2 period piche (zarurat pade to)
if (!defined('LE_PERIOD_SHIFT'))       define('LE_PERIOD_SHIFT', -1);
//
//    RESULT ka apna alag shift — ZARURAT NA HO TO 0 RAKHO:
//        0 = period N par N ka result  (DEFAULT — hamesha API wala result)
//       -1 = period N par N-1 ka result (jodi TODTA hai — FIX #11 ki galti)
if (!defined('LE_RESULT_SHIFT'))       define('LE_RESULT_SHIFT', 0);""",
)

# ------------------------------------------------------------ 2. engine: period shift
rep(
    "api/_core/lottery_engine.php",
    """function le_issue_next(string $issue): string""",
    """/**
 * FIX #12 — site API se kitne period PICHE chale (-1 = ek period piche).
 * Ye sirf period ke NUMBER (label) par lagta hai; start/end time (timer)
 * wahi rahta hai, isliye countdown kabhi galat nahi hota.
 */
function le_period_shift(): int
{
    if (!defined('LE_PERIOD_SHIFT')) return -1;
    $n = (int)LE_PERIOD_SHIFT;
    if ($n > 5)  $n = 5;
    if ($n < -5) $n = -5;
    return $n;
}

function le_issue_next(string $issue): string""",
)

rep(
    "api/_core/lottery_engine.php",
    """    $interval = le_game_interval($gameCode);
    $slot = intdiv($ts, $interval);
    $n = ($slot + le_issue_offset($gameCode) + le_issue_drift($gameCode)) % 100000;
    if ($n < 0) $n += 100000;
    return date('Ymd', $slot * $interval) . le_issue_mid($gameCode) . str_pad((string)$n, 5, '0', STR_PAD_LEFT);""",
    """    $interval = le_game_interval($gameCode);
    $slot = intdiv($ts, $interval);
    // FIX #12: PERIOD + RESULT DONO EK SATH piche -> LE_PERIOD_SHIFT yahan.
    // (le_issue_slot_number me SHAMIL NAHI: drift sirf API vs formula ka
    //  farq nikalta hai, warna drift shift ko wapas aage kar deta.)
    $n = ($slot + le_issue_offset($gameCode) + le_issue_drift($gameCode) + le_period_shift()) % 100000;
    if ($n < 0) $n += 100000;
    return date('Ymd', $slot * $interval) . le_issue_mid($gameCode) . str_pad((string)$n, 5, '0', STR_PAD_LEFT);""",
)

# ------------------------------------------------------------ 3. diag [11]
rep(
    "maandiag.php",
    """// ------------------------------------------------------------------ 11
echo "[11] FIX #11 — RESULT SHIFT (result kitne period piche)\\n";
echo '    LE_RESULT_SHIFT : ' . (defined('LE_RESULT_SHIFT') ? (string)LE_RESULT_SHIFT : '<< NOT DEFINED >>') . "\\n";
echo "    (-1 = period N chal raha ho to N-1 ka result dikhega)\\n";
foreach (['WinGo_30S', 'WinGo_1M'] as $g) {
    $iss = function_exists('lottery_issue') ? lottery_issue($g) : null;
    if (!is_array($iss)) continue;
    $cur  = (string)$iss['issueNumber'];
    $prev = function_exists('le_issue_add') ? le_issue_add($cur, -1) : '';
    $rCur = le_result_for_issue($g, $cur, false);
    echo "  --- $g ---\\n";
    echo '      current period        : ' . $cur . "\\n";
    echo '      us par dikhne wala    : ' . (string)$rCur['number'] . "   (= period " . $prev . " ka result)\\n";
    if (function_exists('le_upstream_results')) {
        $up = le_upstream_results($g);
        $a = isset($up[$cur])  ? (string)($up[$cur]['premium']  ?? $up[$cur]['number']  ?? '-') : '-';
        $b = isset($up[$prev]) ? (string)($up[$prev]['premium'] ?? $up[$prev]['number'] ?? '-') : '-';
        echo '      upstream ' . $cur . ' : ' . $a . "\\n";
        echo '      upstream ' . $prev . ' : ' . $b . "\\n";
        echo '      SAHI? : ' . ($b !== '-' && (string)$rCur['number'] === $b
                ? 'YES  (screen par N-1 ka result = ' . $b . ')' : 'CHECK') . "\\n";
    }
}
echo "\\n";""",
    """// ------------------------------------------------------------------ 11
echo "[11] FIX #12 — SITE API SE KITNE PERIOD PICHE  (PERIOD + RESULT DONO)\\n";
echo '    LE_PERIOD_SHIFT : ' . (defined('LE_PERIOD_SHIFT') ? (string)LE_PERIOD_SHIFT : '<< NOT DEFINED >>') . "\\n";
echo '    LE_RESULT_SHIFT : ' . (defined('LE_RESULT_SHIFT') ? (string)LE_RESULT_SHIFT : '<< NOT DEFINED >>') . "\\n";
echo "    -1 = API par N chal raha ho to site par N-1 chalega AUR usi N-1 ka\\n";
echo "         API WALA result dikhega — jodi kabhi nahi tootti.\\n";
foreach (['WinGo_30S', 'WinGo_1M'] as $g) {
    $iss = function_exists('lottery_issue') ? lottery_issue($g) : null;
    if (!is_array($iss)) continue;
    $cur = (string)$iss['issueNumber'];
    $r   = le_result_for_issue($g, $cur, false);
    $upNew = function_exists('le_upstream_newest') ? le_upstream_newest($g) : null;
    $upN   = is_array($upNew) ? trim((string)($upNew['issueNumber'] ?? '')) : '';
    $upMap = function_exists('le_upstream_results') ? le_upstream_results($g) : [];
    $upRes = isset($upMap[$cur]) ? (string)($upMap[$cur]['premium'] ?? $upMap[$cur]['number'] ?? '-') : '-';
    echo "  --- $g ---\\n";
    echo '      API ka naya period     : ' . ($upN !== '' ? $upN : '-') . "\\n";
    echo '      site ka period         : ' . $cur . "\\n";
    echo '      site par dikhne wala   : ' . (string)$r['number'] . "\\n";
    echo '      API me usi period ka   : ' . $upRes . "\\n";
    echo '      JODI SAHI? : ' . ($upRes !== '-' && (string)$r['number'] === $upRes
            ? 'YES  (period + result DONO API wale)' : 'NO  (API me nahi mila)') . "\\n";
}
echo "\\n";""",
)

if FAILS:
    print("\n\nFAILED:")
    for f in FAILS:
        print("  -", f)
    sys.exit(1)

print("\nfix#12 patch applied OK to", SRC)
