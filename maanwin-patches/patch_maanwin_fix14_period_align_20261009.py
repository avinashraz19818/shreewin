#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
FIX #14 — PERIOD 1 (na ki 2) PICHE, AUR RESULT USI PERIOD KA

LIVE MEASUREMENT 09-Oct-2026 02:42-02:47 IST (dono site + API ek hi second me):

    WinGo_30S  02:43:59   API 20261008100052547 | maanwin 20261008100052548
                                                | maan1win(13fix) 20261008100052546
    WinGo_1M   02:44:12   API 20261008100011275 | maanwin 20261008100011275
                                                | maan1win(13fix) 20261008100011274
    WinGo_30S  02:46:50   maanwin history row0 = 20261009100016473   <<< BILKUL ALAG
                          (maanwin abhi tak UNPATCHED hai — uska formula
                           "slot % 100000" + IST date hai, API wala nahi)

USER NE BOLA: "period 1 piche karna tha, tumne 2 kar diya"
    -> site ka CURRENT period ek AAGE laya gaya  (LE_PERIOD_SHIFT -1 -> 0)
    -> ab site ka current period == API ki row0 (asli current period)

USER NE PEHLE BOLA THA: "1 result piche kar do"  (round 54/55)
    -> wo ab REVEAL se hota hai, period se nahi:
       LE_REVEAL_AT_COUNTDOWN = 0  =>  current period ki result-row list me
       sirf timer khatam hone se 1 second pehle aati hai. Pure period bhar
       list ki pehli row = PICHLA period + USI ka API wala result.
       Isse jodi KABHI nahi tootti: "jis period ka result, usi ka".

NAYE DIAG:
    maandiag.php  [13] — ek hi jagah: API row0, site ka current period,
                         site ki list ki pehli row, "API se X period piche",
                         aur "agar LE_PERIOD_SHIFT -1/-2 hota to kya hota".
    maanupdiag.php [D] — openTime API me nahi milta (0 aata tha, jisse
                         "DONO SE ALAG" chap raha tha) — ab date compare
                         UTC vs site-TZ se hoti hai.
"""
import io, os, sys

ROOT = "/home/user/maanfix2"
ENG = os.path.join(ROOT, "api/_core/lottery_engine.php")
CFG = os.path.join(ROOT, "api/_core/config.php")
DIAG = os.path.join(ROOT, "maandiag.php")
UPDIAG = os.path.join(ROOT, "maanupdiag.php")

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
# 1) config.php — period ek aage, result piche REVEAL se
# =====================================================================
rep(CFG,
"""//      -1 = site hamesha API se EK period piche chalegi:
//             API par period N chal raha ho  ->  site par period (N-1)
//             aur screen par (N-1) ka API WALA result dikhega.
//           PERIOD aur RESULT DONO EK SATH piche jate hain, isliye
//           "period + result" ki jodi KABHI nahi tootti.
//        0 = site API ke barabar chalegi (FIX #10 wala behaviour)
//       -2 = 2 period piche (zarurat pade to)
if (!defined('LE_PERIOD_SHIFT'))       define('LE_PERIOD_SHIFT', -1);""",
"""//      NAYA DEFAULT 0 (FIX #14) — site ka CURRENT period == API ki row0,
//      yani API ka ASLI current period. Aapne bola tha "1 piche karna tha,
//      tumne 2 kar diya" — to period ko EK AAGE laya gaya hai.
//      Result ko piche rakhne ka kaam ab LE_REVEAL_AT_COUNTDOWN karta hai
//      (niche dekho), isliye "jis period ka result usi ka" BANA RAHTA hai.
//
//        0 = site API ke BARABAR  (DEFAULT — asli current period)
//       -1 = site API se 1 period piche
//       -2 = 2 period piche (zarurat pade to)
if (!defined('LE_PERIOD_SHIFT'))       define('LE_PERIOD_SHIFT', 0);""",
    1, "config: LE_PERIOD_SHIFT -1 -> 0")

rep(CFG,
"""if (!defined('LE_REVEAL_AT_COUNTDOWN')) define('LE_REVEAL_AT_COUNTDOWN', 999);""",
"""//   RESULT KAB DIKHEGA  (FIX #14 — yahi "1 result piche" ka asli switch)
//     999 = current period HAMESHA history/trend ki pehli row me
//           (matlab chal rahe period ka result betting ke dauran hi dikhega)
//      0  = current period ki row tabhi aayegi jab timer khatam ho
//           (DEFAULT) — poore period bhar pehli row = PICHLA period aur
//           USI ka API wala result. Jodi kabhi nahi tootti.
//           Timer khatam hone se 1 second pehle row aa jati hai, isliye
//           WIN / LOSS popup bhi theek waqt par dikhta hai.
if (!defined('LE_REVEAL_AT_COUNTDOWN')) define('LE_REVEAL_AT_COUNTDOWN', 0);""",
    1, "config: LE_REVEAL_AT_COUNTDOWN 999 -> 0")

# =====================================================================
# 2) maandiag.php — [11] ke note theek + naya [13]
# =====================================================================
rep(DIAG,
"""echo "    -1 = API par N chal raha ho to site par N-1 chalega AUR usi N-1 ka\\n";
echo "         API WALA result dikhega — jodi kabhi nahi tootti.\\n";""",
"""echo "    0  = site ka current period == API ka asli current period (DEFAULT)\\n";
echo "    -1 = site API se 1 period piche\\n";
echo "    RESULT hamesha USI period ka hota hai jiske sath dikhta hai.\\n";""",
    1, "maandiag [11]: note")

rep(DIAG,
"""echo "\\n" . $line . "\\n";
echo "END. Is file ko ab delete kar dena.\\n";""",
"""// ------------------------------------------------------------------ 13
echo "[13] PERIOD KITNA PICHE?  — EK HI JAGAH POORA HISAAB\\n";
echo '    LE_PERIOD_SHIFT       : ' . (defined('LE_PERIOD_SHIFT') ? (string)LE_PERIOD_SHIFT : '<< NOT DEFINED >>') . "\\n";
echo '    LE_REVEAL_AT_COUNTDOWN: ' . (defined('LE_REVEAL_AT_COUNTDOWN') ? (string)LE_REVEAL_AT_COUNTDOWN : '<< NOT DEFINED >>') . "\\n";
echo '    LE_RESULT_SHIFT       : ' . (defined('LE_RESULT_SHIFT') ? (string)LE_RESULT_SHIFT : '<< NOT DEFINED >>') . "\\n";
echo '    server time           : ' . date('Y-m-d H:i:s') . ' ' . date('T') . '   (UTC ' . gmdate('Y-m-d H:i:s') . ")\\n";
foreach (['WinGo_30S', 'WinGo_1M', 'WinGo_3M', 'WinGo_5M'] as $g) {
    $iss = function_exists('lottery_issue') ? lottery_issue($g) : null;
    if (!is_array($iss)) continue;
    $cur = (string)$iss['issueNumber'];
    $upNew = function_exists('le_upstream_newest') ? le_upstream_newest($g) : null;
    $upN   = is_array($upNew) ? trim((string)($upNew['issueNumber'] ?? '')) : '';
    $upR0  = is_array($upNew) ? (string)($upNew['premium'] ?? $upNew['number'] ?? '-') : '-';
    echo "  --- $g ---\\n";
    echo '      API row0 (abhi chal raha)  : ' . ($upN !== '' ? $upN : '-') . '   result=' . $upR0 . "\\n";
    echo '      site ka CURRENT period     : ' . $cur . "\\n";
    $lag = '-';
    if ($upN !== '') {
        $a = (int)substr($upN, -5);
        $b = (int)substr($cur, -5);
        $d = $a - $b;                       // + => site API se itne piche
        if ($d > 50000)  $d -= 100000;
        if ($d < -50000) $d += 100000;
        $lag = $d;
    }
    echo '      => site CURRENT period API se ' . ($lag === '-' ? '-' : (string)$lag . ' PERIOD PICHE')
         . ($lag === 0 ? '   (BARABAR)' : '') . "\\n";
    if (function_exists('le_history_page')) {
        $hp = le_history_page($g, 1, 3);
        if (isset($hp['list'][0])) {
            $r0 = $hp['list'][0];
            $num = (string)$r0['issueNumber'];
            $up  = function_exists('le_upstream_result_for') ? le_upstream_result_for($g, $num) : '';
            echo '      site ki LIST ki pehli row  : ' . $num . '   result=' . (string)$r0['number'] . "\\n";
            echo '      API me USI period ka       : ' . ($up !== '' ? $up : '<< nahi mila >>')
                 . '   ' . ($up !== '' && $up === (string)$r0['number'] ? 'SAME  (jodi sahi)' : 'ALAG <<<') . "\\n";
        }
    }
    if ($upN !== '' && function_exists('le_issue_add')) {
        echo '      --- agar aapko aur piche chahiye to ---' . "\\n";
        foreach ([-1, -2] as $s) {
            echo '          LE_PERIOD_SHIFT = ' . $s . '  ->  site ka period ' . (string)le_issue_add($upN, $s) . "\\n";
        }
    }
    echo "\\n";
}

echo "\\n" . $line . "\\n";
echo "END. Is file ko ab delete kar dena.\\n";""",
    1, "maandiag: naya section [13]")

# =====================================================================
# 3) maanupdiag.php — [D] ka jhootha "DONO SE ALAG" theek
# =====================================================================
rep(UPDIAG,
"""    $num = trim((string)($row['issueNumber'] ?? $row['issueNo'] ?? $row['period'] ?? ''));
    $ot  = (int)($row['openTime'] ?? 0);
    $ots = $ot > 1000000000000 ? (int)floor($ot / 1000) : $ot;
    echo '      API row0 issueNumber : ' . $num . "\\n";
    echo '      API row0 openTime    : ' . $ot . '  -> UTC ' . gmdate('Y-m-d H:i:s', $ots)
         . '  |  site TZ ' . date('Y-m-d H:i:s', $ots) . "\\n";
    echo '      API row0 date  UTC   : ' . gmdate('Ymd', $ots) . "\\n";
    echo '      API row0 date  site  : ' . date('Ymd', $ots) . "\\n";
    echo '      API row0 date in no. : ' . substr($num, 0, 8) . "\\n";
    echo '      SAMAN? : ' . (substr($num, 0, 8) === gmdate('Ymd', $ots)
            ? 'UTC   (LE_ISSUE_DATE_TZ = utc SAHI hai)'
            : (substr($num, 0, 8) === date('Ymd', $ots)
                ? 'SITE TZ  (LE_ISSUE_DATE_TZ = local chahiye)' : 'DONO SE ALAG <<<')) . "\\n";""",
"""    $num = trim((string)($row['issueNumber'] ?? $row['issueNo'] ?? $row['period'] ?? ''));
    echo '      API row0 issueNumber : ' . $num . "\\n";
    // API ki list me openTime nahi milta (0 aata hai). Isliye date ko
    // "aaj ki UTC date" aur "aaj ki site-TZ date" se compare karte hain —
    // API ki row0 hamesha ABHI chal raha period hoti hai.
    $uToday = gmdate('Ymd');
    $sToday = date('Ymd');
    echo '      API row0 ki date     : ' . substr($num, 0, 8) . "\\n";
    echo '      aaj UTC ki date      : ' . $uToday . "\\n";
    echo '      aaj site TZ ki date  : ' . $sToday . "\\n";
    if ($uToday === $sToday) {
        echo '      SAMAN? : abhi UTC aur site TZ EK HI din hain — subah 00:00-05:30\\n';
        echo '               IST ke beech dobara chala kar confirm kar lena.\\n';
    } else {
        echo '      SAMAN? : ' . (substr($num, 0, 8) === $uToday
                ? 'UTC   (LE_ISSUE_DATE_TZ = utc SAHI hai)'
                : (substr($num, 0, 8) === $sToday
                    ? 'SITE TZ  (LE_ISSUE_DATE_TZ = local chahiye)' : 'DONO SE ALAG <<<')) . "\\n";
    }""",
    1, "maanupdiag [D]: date compare theek")

print("")
print("replacements OK : %d" % ok)
if fail:
    print("FAILED          : %d" % len(fail))
    for f in fail:
        print("   - " + f)
    sys.exit(1)
print("SAB THEEK")
