#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
FIX #18 — ASLI (aur aakhri) WAJAH: API KA COUNTER ROZ UTC MIDNIGHT PAR
          RESET HOTA HAI

LIVE MEASUREMENT 09-Oct-2026 10:43:44 IST (= 05:13:44 UTC), maan1win:

    game        API (drawer)          site (purana formula)     farq
    WinGo_30S   20261009100050627     20261009100053507         2880
    WinGo_1M    20261009100010313     20261009100011753         1440
    WinGo_3M    20261009100020104     20261009100020584          480

    2880 = 86400/30   (30 second ke kitne period ek din me)
    1440 = 86400/60
     480 = 86400/180

Farq BILKUL "ek din ke period" jitna hai -> matlab API ka counter
har UTC midnight par RESET ho jata hai, site wala nahi.

PURANA FORMULA (galat):
    tail = (absolute_slot + C) % 100000     C = 36080 / 53040 / 67680 ...

ASLI FORMULA (naap kar nikalaa):
    tsUtcMidnight = floor(ts / 86400) * 86400
    tail = floor((ts - tsUtcMidnight) / interval) + BASE
    issue = gmdate('Ymd', ts) + MID + pad(tail, 5)

    BASE (interval ke hisaab se):
        30 second -> 50000
        1 minute  -> 10000
        3 minute  -> 20000
        5 minute  -> 30000
        10 minute -> 40000
    MID (game family): WinGo 1000 | K3 1010 | D5 1020 | TrxWinGo 1030 |
                       MotoRace 1050     (ye pehle jaisa hi hai)

VERIFY — 6 live points (2 alag din, 3 game):
    08-Oct 20:51:04 UTC: 30S 52502 | 1M 11251 | 3M 20417   <- API ne diya
        naya formula   : 30S 52502 | 1M 11251 | 3M 20417   ✅ 3/3
    09-Oct 05:13:44 UTC: 30S 50627 | 1M 10313 | 3M 20104   <- API ne diya
        naya formula   : 30S 50627 | 1M 10313 | 3M 20104   ✅ 3/3
    08-Oct 09:14 UTC (5M/10M, purane record se):
        5M 30110/30111 (base 30000 ✅) | 10M 40055 (base 40000 ✅)

KYUN "fix ke time sahi, baad me apne aap badal gaya":
    08-Oct ko (ts_mid/30) % 100000 = 13920 tha, jo C (36080) ke sath
    mil kar wahi number deta tha. UTC midnight ke baad ts_mid badla,
    to dono formula alag ho gaye — period number 2880 aage, result ki
    tail-match miss -> random result -> DB me pakka -> baad me repair.
"""
import io, os, sys

ROOT = "/home/user/maanfix2"
ENG = os.path.join(ROOT, "api/_core/lottery_engine.php")
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


# ---------------------------------------------------------------- 1
# BASE + daily-counter helpers, le_issue_offset() ke theek baad
rep(ENG,
"""function le_issue_offset(string $gameCode): int
{
    $map = [
        30  => 36080,   // 30 second
        60  => 53040,   // 1 minute
        180 => 67680,   // 3 minute
        300 => 58608,   // 5 minute
        600 => 54304,   // 10 minute
    ];
    $iv = le_game_interval($gameCode);
    return isset($map[$iv]) ? (int)$map[$iv] : 0;
}
""",
"""function le_issue_offset(string $gameCode): int
{
    $map = [
        30  => 36080,   // 30 second
        60  => 53040,   // 1 minute
        180 => 67680,   // 3 minute
        300 => 58608,   // 5 minute
        600 => 54304,   // 10 minute
    ];
    $iv = le_game_interval($gameCode);
    return isset($map[$iv]) ? (int)$map[$iv] : 0;
}

// =======================================================================
// FIX #18 — API KA COUNTER ROZ UTC MIDNIGHT PAR RESET HOTA HAI  <<< ASLI
//
//   PURANA FORMULA:  tail = (absolute_slot + C) % 100000
//     Ye 08-Oct ko coincidentally sahi number deta tha, par UTC midnight
//     ke baad tail "ek din ke period" (30S me 2880) AAGE nikal jata tha:
//         API : 20261009100050627      site : 20261009100053507
//     -> result ki tail-match miss -> RANDOM result -> DB me pakka ->
//        baad me repair -> "result apne aap badal gaya".
//
//   ASLI FORMULA (drawer ke 6 live points se naap kar nikalaa):
//         tail = floor((ts - UTC_midnight) / interval) + BASE
//         issue = gmdate('Ymd', ts) + MID + pad(tail, 5)
// =======================================================================
function le_issue_base(string $gameCode): int
{
    // interval ke hisaab se counter kahan se shuru hota hai (UTC 00:00)
    $map = [
        30  => 50000,   // 30 second
        60  => 10000,   // 1 minute
        180 => 20000,   // 3 minute
        300 => 30000,   // 5 minute
        600 => 40000,   // 10 minute
    ];
    $iv = le_game_interval($gameCode);
    return isset($map[$iv]) ? (int)$map[$iv] : 10000;
}

/** UTC midnight ka unix time (unix din ki shuruat hi UTC midnight hai) */
function le_issue_day_start(int $ts): int
{
    return intdiv($ts, 86400) * 86400;
}

/** API wala 5-digit counter (bina drift/shift ke) */
function le_issue_tail(string $gameCode, int $ts): int
{
    $interval = le_game_interval($gameCode);
    $ssm = $ts - le_issue_day_start($ts);
    if ($ssm < 0) $ssm = 0;
    return intdiv($ssm, $interval) + le_issue_base($gameCode);
}
""",
    1, "engine: le_issue_base/day_start/tail")

# ---------------------------------------------------------------- 2
rep(ENG,
"""function le_issue_slot_number(string $gameCode, int $slot): string
{
    $interval = le_game_interval($gameCode);
    $n = ($slot + le_issue_offset($gameCode)) % 100000;
    if ($n < 0) $n += 100000;
    return le_issue_date($slot * $interval) . le_issue_mid($gameCode) . str_pad((string)$n, 5, '0', STR_PAD_LEFT);
}

function le_issue_for_time(int $ts, string $gameCode = ''): string
{
    $interval = le_game_interval($gameCode);
    $slot = intdiv($ts, $interval);
    // FIX #12: PERIOD + RESULT DONO EK SATH piche -> LE_PERIOD_SHIFT yahan.
    // (le_issue_slot_number me SHAMIL NAHI: drift sirf API vs formula ka
    //  farq nikalta hai, warna drift shift ko wapas aage kar deta.)
    $n = ($slot + le_issue_offset($gameCode) + le_issue_drift($gameCode) + le_period_shift()) % 100000;
    if ($n < 0) $n += 100000;
    return le_issue_date($slot * $interval) . le_issue_mid($gameCode) . str_pad((string)$n, 5, '0', STR_PAD_LEFT);
}""",
"""function le_issue_slot_number(string $gameCode, int $slot): string
{
    $interval = le_game_interval($gameCode);
    $n = le_issue_tail($gameCode, $slot * $interval);
    return le_issue_date($slot * $interval) . le_issue_mid($gameCode) . str_pad((string)$n, 5, '0', STR_PAD_LEFT);
}

function le_issue_for_time(int $ts, string $gameCode = ''): string
{
    $interval = le_game_interval($gameCode);
    $slot     = intdiv($ts, $interval);
    $start    = $slot * $interval;
    $per      = intdiv(86400, $interval);          // ek din me kitne period
    $base     = le_issue_base($gameCode);
    // FIX #12: PERIOD + RESULT DONO EK SATH piche -> LE_PERIOD_SHIFT yahan.
    $idx = intdiv($start - le_issue_day_start($start), $interval)
         + le_issue_drift($gameCode) + le_period_shift();
    $day = le_issue_day_start($start);
    // din ki had paar kare to date bhi saath badlo (30S: 50000-1 = pichle
    // din ka 52879)
    if ($per > 0) {
        while ($idx < 0)    { $idx += $per; $day -= 86400; }
        while ($idx >= $per){ $idx -= $per; $day += 86400; }
    }
    if ($idx < 0) $idx = 0;
    return le_issue_date($day) . le_issue_mid($gameCode) . str_pad((string)($base + $idx), 5, '0', STR_PAD_LEFT);
}""",
    1, "engine: slot_number + for_time daily counter")

# ---------------------------------------------------------------- 3
rep(ENG,
"""    $tailOf = function (int $slot) use ($gameCode): int {
        $n = ($slot + le_issue_offset($gameCode)) % 100000;
        if ($n < 0) $n += 100000;
        return (int)substr(str_pad((string)$n, 5, '0', STR_PAD_LEFT), -5);
    };""",
"""    $tailOf = function (int $slot) use ($gameCode): int {
        return le_issue_tail($gameCode, $slot * le_game_interval($gameCode));
    };""",
    1, "engine: drift tail bhi daily counter se")

# ---------------------------------------------------------------- 4
# game-aware prev (din ki had par sahi wrap) + sequence me use
rep(ENG,
"""function le_issue_by_offset(string $gameCode, int $offset = 0): string""",
"""/**
 * FIX #18 — ek period PICHLE, lekin DIN KI HAD ka dhyan rakhte hue.
 *   API ka counter roz UTC 00:00 par BASE se shuru hota hai, isliye
 *   30S ka 50000-1 = PICHLE DIN ka 52879 hota hai (49999 nahi).
 */
function le_issue_prev_game(string $issue, string $gameCode): string
{
    $issue = trim((string)$issue);
    if (!preg_match('/^(\\d{8})(\\d{4})(\\d{5})$/', $issue, $m)) return '';
    $date = $m[1];
    $mid  = $m[2];
    $tail = (int)$m[3] - 1;
    $base = le_issue_base($gameCode);
    if ($tail < $base) {
        $per = intdiv(86400, le_game_interval($gameCode));
        if ($per > 0) {
            $tail = $base + $per - 1;
            $date = le_issue_shift_day($date, -1);
        }
    }
    return $date . $mid . str_pad((string)$tail, 5, '0', STR_PAD_LEFT);
}

function le_issue_by_offset(string $gameCode, int $offset = 0): string""",
    1, "engine: le_issue_prev_game()")

rep(ENG,
"""    for ($i = 1; $i < $count; $i++) {
        $prev = le_issue_prev($cur);
        if ($prev === '') $prev = (string)le_issue_by_offset($gameCode, $startOffset + $i);
        $cur = $prev;
        $out[] = $cur;
    }""",
"""    for ($i = 1; $i < $count; $i++) {
        $prev = function_exists('le_issue_prev_game') ? le_issue_prev_game($cur, $gameCode) : '';
        if ($prev === '') $prev = le_issue_prev($cur);
        if ($prev === '') $prev = (string)le_issue_by_offset($gameCode, $startOffset + $i);
        $cur = $prev;
        $out[] = $cur;
    }""",
    1, "engine: sequence game-aware prev")

# ---------------------------------------------------------------- 5
rep(UPDIAG,
"""        echo '    offset ' . str_pad($g, 10) . ' : mid=' . le_issue_mid($g)
             . '  C=' . le_issue_offset($g) . '  drift=' . le_issue_drift($g) . "\\n";""",
"""        echo '    offset ' . str_pad($g, 10) . ' : mid=' . le_issue_mid($g)
             . '  BASE=' . (function_exists('le_issue_base') ? le_issue_base($g) : '-')
             . '  puranaC=' . le_issue_offset($g) . '  drift=' . le_issue_drift($g) . "\\n";""",
    1, "maanupdiag [A]: BASE bhi dikhe")

rep(UPDIAG,
"""$games  = ['WinGo_30S', 'WinGo_1M', 'WinGo_3M'];""",
"""$games  = ['WinGo_30S', 'WinGo_1M', 'WinGo_3M', 'WinGo_5M'];""",
    1, "maanupdiag: 5M bhi check ho")

rep(UPDIAG,
"""    foreach (['WinGo_30S', 'WinGo_1M', 'WinGo_3M', 'WinGo_5M'] as $g) {
        echo '    offset '""",
"""    foreach ($games as $g) {
        echo '    offset '""",
    1, "maanupdiag [A]: $games list use kare")

print("")
print("replacements OK : %d" % ok)
if fail:
    print("FAILED          : %d" % len(fail))
    for f in fail:
        print("   - " + f)
    sys.exit(1)
print("SAB THEEK")
