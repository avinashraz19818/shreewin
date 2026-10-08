#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
FIX #15 — SIRF DO KAAM (user ke shabdon me):

  1) "bet me jo time likha rehta hai, jaise bet lagaya to 4:14 likha rehta
     hai to wo 4:13 show karega — wo bhi 1 piche rahega"
     -> My History / bet record me jo TIME dikhta hai (betTime / createTime)
        wo ab 1 SECOND piche hoga.
        Asli:  2026-10-09 04:14:30
        Dikhe: 2026-10-09 04:14:29
        Switch: LE_BET_TIME_LEAD_SEC  (1 = 1 second, 60 = ek minute)

  2) "win / loss popup timer 0 hote hi aa jaaye, koi delay nahi"
     -> LE_REVEAL_AT_COUNTDOWN 0 -> 1.
        Current period ki result-row ab tab aayegi jab countdown <= 1 ho,
        yani jab screen par timer 0 dikhega to row PEHLE SE MAUJOOD hogi.
        Frontend ka popup usi row se chalta hai, isliye 0 hote hi popup.
        (Result 1 second pehle dikhna koi risk nahi: API/drawer to result
         period shuru hote hi publish kar deta hai.)

KUCH AUR NAHI CHHUA — na period number, na result, na tax, na order id.
"""
import io, os, sys

ROOT = "/home/user/maanfix2"
ENG = os.path.join(ROOT, "api/_core/lottery_engine.php")
CFG = os.path.join(ROOT, "api/_core/config.php")
DIAG = os.path.join(ROOT, "maandiag.php")

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
# 1) config.php — naya switch + popup ka waqt
# =====================================================================
rep(CFG,
"""if (!defined('LE_TIMER_LEAD_MS'))       define('LE_TIMER_LEAD_MS', 1000);""",
"""if (!defined('LE_TIMER_LEAD_MS'))       define('LE_TIMER_LEAD_MS', 1000);
//
//   BET KA TIME (My History me "Time" wali line) kitna PICHE DIKHE  (FIX #15)
//       1 = 1 second piche   (DEFAULT)   jaise 04:14:30  ->  04:14:29
//      60 = 1 minute piche               jaise 04:14:30  ->  04:13:30
//       0 = asli time (koi badlav nahi)
//     Ye SIRF DIKHANE KE LIYE hai — DB me asli created_at hi rehta hai,
//     settlement / hisaab par koi asar nahi.
if (!defined('LE_BET_TIME_LEAD_SEC'))   define('LE_BET_TIME_LEAD_SEC', 1);""",
    1, "config: LE_BET_TIME_LEAD_SEC = 1")

rep(CFG,
"""//      0  = current period ki row tabhi aayegi jab timer khatam ho
//           (DEFAULT) — poore period bhar pehli row = PICHLA period aur
//           USI ka API wala result. Jodi kabhi nahi tootti.
//           Timer khatam hone se 1 second pehle row aa jati hai, isliye
//           WIN / LOSS popup bhi theek waqt par dikhta hai.
if (!defined('LE_REVEAL_AT_COUNTDOWN')) define('LE_REVEAL_AT_COUNTDOWN', 0);""",
"""//      0  = current period ki row tabhi aayegi jab timer khatam ho.
//      1  = row tab aayegi jab countdown <= 1 ho (DEFAULT, FIX #15) —
//           matlab screen par timer "0" dikhne tak row PEHLE SE MAUJOOD
//           hoti hai, isliye WIN / LOSS POPUP bina kisi delay ke 0 par
//           aa jata hai. Poore period bhar pehli row phir bhi PICHLA
//           period + USI ka API wala result hi hoti hai (jodi sahi).
//           (Result 1 second pehle dikhne ka koi nuksan nahi — API/drawer
//            to result period shuru hote hi publish kar deta hai.)
if (!defined('LE_REVEAL_AT_COUNTDOWN')) define('LE_REVEAL_AT_COUNTDOWN', 1);""",
    1, "config: LE_REVEAL_AT_COUNTDOWN 0 -> 1")

# =====================================================================
# 2) engine — bet ka time 1 second piche
# =====================================================================
rep(ENG,
"""function le_response_row(array $r): array
{""",
"""// =======================================================================
// FIX #15 — BET KA TIME EK SECOND PICHE  (My History ki "Time" wali line)
//
//   Sirf DIKHANE ke liye. DB me asli created_at hi rehta hai — settlement,
//   period, result, tax, order id kuch bhi nahi badalta.
//   LE_BET_TIME_LEAD_SEC = 1  ->  04:14:30 ki jagah 04:14:29 dikhega
//   LE_BET_TIME_LEAD_SEC = 60 ->  ek minute piche
// =======================================================================
function le_bet_time_lead(): int
{
    if (!defined('LE_BET_TIME_LEAD_SEC')) return 1;
    $n = (int)LE_BET_TIME_LEAD_SEC;
    if ($n < 0) $n = 0;
    if ($n > 3600) $n = 3600;
    return $n;
}

/** bet row me dikhane layak time (ek second piche) — unix seconds */
function le_bet_row_time(array $r): int
{
    $ts = strtotime((string)($r['created_at'] ?? ''));
    if ($ts === false || $ts <= 0) $ts = time();
    return $ts - le_bet_time_lead();
}

function le_response_row(array $r): array
{""",
    1, "engine: le_bet_time_lead() + le_bet_row_time()")

rep(ENG,
"""        'betTime' => strtotime((string)$r['created_at']) * 1000,
        'createTime' => strtotime((string)$r['created_at']) * 1000,""",
"""        // FIX #15 — 1 second PICHE (My History me "Time" isi se banta hai)
        'betTime' => le_bet_row_time($r) * 1000,
        'createTime' => le_bet_row_time($r) * 1000,""",
    1, "engine: betTime/createTime 1 sec piche")

# =====================================================================
# 3) maandiag.php — naya section [14] (verify karne ke liye)
# =====================================================================
rep(DIAG,
"""echo "\\n" . $line . "\\n";
echo "END. Is file ko ab delete kar dena.\\n";""",
"""// ------------------------------------------------------------------ 14
echo "[14] FIX #15 — BET KA TIME 1 SECOND PICHE  +  POPUP TIMER 0 PAR\\n";
echo '    LE_BET_TIME_LEAD_SEC  : ' . (function_exists('le_bet_time_lead') ? (string)le_bet_time_lead() . ' second' : '<< NOT DEFINED >>') . "\\n";
echo '    LE_REVEAL_AT_COUNTDOWN: ' . (defined('LE_REVEAL_AT_COUNTDOWN') ? (string)LE_REVEAL_AT_COUNTDOWN : '<< NOT DEFINED >>')
     . "   (row is countdown par aa jati hai -> 0 par popup turant)\\n";
if (function_exists('db')) {
    $conn = db();
    if ($conn) {
        $rs = @$conn->query('SELECT id, order_no, game_code, created_at FROM lottery_bets ORDER BY id DESC LIMIT 3');
        if ($rs) {
            echo "    PICHLE 3 BET — DB ka asli time  vs  app me dikhne wala time\\n";
            while ($r = $rs->fetch_assoc()) {
                $real = strtotime((string)$r['created_at']);
                if ($real === false || $real <= 0) continue;
                $shown = $real - (function_exists('le_bet_time_lead') ? le_bet_time_lead() : 0);
                echo '      id=' . str_pad((string)$r['id'], 6) . ' ' . str_pad((string)$r['game_code'], 10)
                     . ' DB: ' . date('Y-m-d H:i:s', $real)
                     . '   ->  app me: ' . date('Y-m-d H:i:s', $shown) . "\\n";
            }
        }
    }
}
echo "    (farq sirf DIKHANE me hai — DB me asli time hi rehta hai)\\n";
echo "\\n";

echo "\\n" . $line . "\\n";
echo "END. Is file ko ab delete kar dena.\\n";""",
    1, "maandiag: naya section [14]")

print("")
print("replacements OK : %d" % ok)
if fail:
    print("FAILED          : %d" % len(fail))
    for f in fail:
        print("   - " + f)
    sys.exit(1)
print("SAB THEEK")
