#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
MAANWIN — FIX #11 (2026-10-08)
================================
User (binding): "Ab to same ho gya lekin 1 RESULT PICHE to kro ...
                 kyunki 1 period piche hai n"

FIX #10 se period number drawer (draw.ar-lottery01.com) ke barabar ho gaya.
Ab user chahta hai ki RESULT ek period PICHE ho:

    period N chal raha ho  ->  dikhne wala result = period (N-1) ka
    period N khatam hote hi N ka result aa jata hai (tab current period N+1)

Isse current period ka result timer khatam hone se PAHLE nazar nahi aata.

Switch:  api/_core/config.php
    LE_RESULT_SHIFT = -1   -> result 1 period piche   (DEFAULT, user ki demand)
    LE_RESULT_SHIFT = 0    -> result bilkul upstream jaisa (koi shift nahi)
    LE_RESULT_SHIFT = -2   -> 2 period piche (zarurat pade to)

Shift ek hi jagah lagaya gaya hai (le_result_for_issue ke andar), isliye
game screen, period history, trend AUR settlement SAB ek hi result dekhte
hain — kahin bhi mismatch nahi ho sakta.
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
    """// 5) DRAWER (draw.ar-lottery01.com) AUR GAME — EK HI PERIOD / RESULT""",
    """// 6) RESULT KITNE PERIOD PICHE DIKHE  (2026-10-08, user ki demand)
//      -1 = result EK period piche.  Period N chal raha ho to screen par
//           period (N-1) ka result dikhega; N ka result N khatam hote hi
//           (tab current period N+1 ho jata hai) aa jata hai.
//           Isse current period ka result timer khatam hone se PAHLE nahi
//           dikhata — betting ke liye sahi behaviour.
//        0 = koi shift nahi (result bilkul upstream jaisa, FIX #10 wala)
//       -2 = 2 period piche (zarurat pade to)
if (!defined('LE_RESULT_SHIFT'))       define('LE_RESULT_SHIFT', -1);
//
// 5) DRAWER (draw.ar-lottery01.com) AUR GAME — EK HI PERIOD / RESULT""",
)

# ------------------------------------------------------------ 2. engine helpers
rep(
    "api/_core/lottery_engine.php",
    """/** result row ko lottery_results me save (INSERT IGNORE) */""",
    """// =======================================================================
// FIX #11 — RESULT KO EK PERIOD PICHE KARNA
//   Period N chal raha ho to result (N-1) ka dikhana hai, taaki current
//   period ka result timer khatam hone se pehle na dikhe.
//   Shift EK HI jagah (le_result_for_issue) me lagaya gaya hai, isliye
//   game screen, period history, trend aur settlement SABHI ko ek hi
//   result milega — kahin mismatch nahi ho sakta.
// =======================================================================
function le_result_shift(): int
{
    if (!defined('LE_RESULT_SHIFT')) return -1;
    $n = (int)LE_RESULT_SHIFT;
    if ($n > 5)  $n = 5;
    if ($n < -5) $n = -5;
    return $n;
}

function le_issue_next(string $issue): string
{
    $issue = trim((string)$issue);
    if (!preg_match('/^(\\d{8})(\\d{4})(\\d{5})$/', $issue, $m)) return '';
    $date = $m[1];
    $mid  = $m[2];
    $tail = (int)$m[3] + 1;
    if ($tail > 99999) {
        $tail = 0;
        $ts = strtotime($date . ' 00:00:00');
        if ($ts !== false) $date = date('Ymd', $ts + 86400);
    }
    return $date . $mid . str_pad((string)$tail, 5, '0', STR_PAD_LEFT);
}

/** issue number me $delta period jodo (negative = piche) */
function le_issue_add(string $issue, int $delta): string
{
    if ($delta === 0) return $issue;
    $cur  = $issue;
    $steps = abs($delta);
    if ($steps > 60) $steps = 60;
    for ($i = 0; $i < $steps; $i++) {
        $cur = ($delta > 0) ? le_issue_next($cur) : le_issue_prev($cur);
        if ($cur === '') return $issue;
    }
    return $cur;
}

/** result kis period ka dikhana hai (shift lagane ke baad) */
function le_result_issue_for(string $gameCode, string $issueNumber): string
{
    $shift = le_result_shift();
    if ($shift === 0) return $issueNumber;
    $out = le_issue_add($issueNumber, $shift);
    return $out !== '' ? $out : $issueNumber;
}

/** result row ko lottery_results me save (INSERT IGNORE) */""",
)

# ------------------------------------------------------------ 3. shift in result
rep(
    "api/_core/lottery_engine.php",
    """function le_result_for_issue(string $gameCode, string $issueNumber, bool $save = true): array
{
    $conn = db();""",
    """function le_result_for_issue(string $gameCode, string $issueNumber, bool $save = true): array
{
    // FIX #11 — result ek period PICHE. Shift yahin lagaya gaya hai, isliye
    // game screen / history / trend / settlement sabko EK HI result milega.
    $issueNumber = le_result_issue_for($gameCode, $issueNumber);

    $conn = db();""",
)

# ------------------------------------------------------------ 4. diag [11]
rep(
    "maandiag.php",
    """echo "\\n" . $line . "\\n";
echo "END. Is file ko ab delete kar dena.\\n";""",
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
echo "\\n";

echo "\\n" . $line . "\\n";
echo "END. Is file ko ab delete kar dena.\\n";""",
)

if FAILS:
    print("\n\nFAILED:")
    for f in FAILS:
        print("  -", f)
    sys.exit(1)

print("\nfix#11 patch applied OK to", SRC)
