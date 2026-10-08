#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
MAANWIN — FIX #10 (2026-10-08)  — ASLI FIX
===========================================
MEASURED ON THE LIVE SERVER (08-Oct-2026, 14:44 IST, same second):

  game        maanwin ORIGINAL build   draw.ar-lottery01.com row0   local (patched)
  WinGo_30S   20261008100051108        20261008100051108            20261008100015028
  WinGo_1M    20261008100010554        20261008100010554            20261008100057514
  WinGo_3M    20261008100020184        20261008100020184            20261008100052504

=> maanwin ka ORIGINAL build EXACTLY upstream ke sath tha.
   FIX #8 ne use "slot % 100000" par force kar diya, isliye mismatch
   SHURU hua ("tumne result kharab kar diya").

UPSTREAM KA FORMULA (measured, har game aur har family par match):
    issue = date('Ymd', slot*interval) + MID + pad((slot + C) mod 100000, 5)
      slot     = floor(unix_time / interval)
      MID      = WinGo 1000 | K3 1010 | D5 1020 | TrxWinGo 1030 | MotoRace 1050
      C(interval): 30s -> 36080 | 1m -> 53040 | 3m -> 67680 | 5m -> 58608 | 10m -> 54304

FIX #10
  1) le_issue_for_time() ab upar wala formula use karta hai (MID + C).
  2) le_issue_drift(): upstream ki row0 se roz-khud CORRECTION nikalta hai
     (2 minute cache) — agar upstream kabhi skip/khud badle to number apne aap
     theek ho jayega. Network na ho to correction 0 (formula hi kaafi hai).
  3) LE_REVEAL_AT_COUNTDOWN = 999 => history ki pehli row me CURRENT period,
     bilkul upstream/drawer jaisa (upstream row0 bhi current period hai).
  4) Result: fix #9 already upstream se la raha hai (200 OK) — ab period
     number match hone se result bhi match hoga.
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


# ------------------------------------------------------- 1. engine numbering
rep(
    "api/_core/lottery_engine.php",
    """function le_issue_for_time(int $ts, string $gameCode = ''): string
{
    $interval = le_game_interval($gameCode);
    $slot = intdiv($ts, $interval);
    return date('Ymd', $slot * $interval) . '1000' . str_pad((string)($slot % 100000), 5, '0', STR_PAD_LEFT);
}

function le_issue_by_offset(string $gameCode, int $offset = 0): string
{
    $interval = le_game_interval($gameCode);
    return le_issue_for_time(time() - ($offset * $interval), $gameCode);
}""",
    """// =======================================================================
// FIX #10 — PERIOD NUMBER BILKUL UPSTREAM (draw.ar-lottery01.com) JAISA
//
//   NAAPA HUA FORMULA (08-Oct-2026 14:44 IST, teeno game ek hi second me):
//       issue = date('Ymd', slot*interval) + MID + pad((slot + C) % 100000)
//       slot  = floor(unix / interval)
//       MID   : WinGo 1000 | K3 1010 | D5 1020 | TrxWinGo 1030 | MotoRace 1050
//       C     : 30s 36080 | 1m 53040 | 3m 67680 | 5m 58608 | 10m 54304
//
//   Isse local period number == drawer (upstream) ka period number.
//   Upar se le_issue_drift() upstream ki nayi row se correction lagata hai,
//   taaki kabhi bhi farq na pade.
// =======================================================================
function le_issue_mid(string $gameCode): string
{
    $c = strtolower(trim($gameCode));
    if (strpos($c, 'trxwingo') === 0) return '1030';
    if (strpos($c, 'k3') === 0)       return '1010';
    if (strpos($c, '5d') === 0)       return '1020';
    if (strpos($c, 'd5') === 0)       return '1020';
    if (strpos($c, 'motorace') === 0) return '1050';
    if (strpos($c, 'moto') === 0)     return '1050';
    return '1000';
}

function le_issue_offset(string $gameCode): int
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

/**
 * Upstream (draw.ar-lottery01.com) ki sabse NAYI row se correction.
 * 2 minute cache. Network/403/slow => 0 (tab formula hi kaafi hai).
 */
function le_issue_drift(string $gameCode): int
{
    static $mem = [];
    if (array_key_exists($gameCode, $mem)) return $mem[$gameCode];
    $mem[$gameCode] = 0;
    if (!function_exists('le_upstream_enabled') || !le_upstream_enabled()) return 0;
    if (!function_exists('le_upstream_newest') || !function_exists('le_upstream_cache_file')) return 0;

    $file = le_upstream_cache_file('drift_' . md5($gameCode));
    if (is_file($file)) {
        $c = json_decode((string)@file_get_contents($file), true);
        if (is_array($c) && isset($c['t'], $c['d']) && (time() - (int)$c['t']) <= 120) {
            $mem[$gameCode] = (int)$c['d'];
            return $mem[$gameCode];
        }
    }
    $row = le_upstream_newest($gameCode);
    if (!is_array($row)) return 0;
    $up = trim((string)($row['issueNumber'] ?? $row['issueNo'] ?? $row['period'] ?? ''));
    if ($up === '') return 0;

    $interval = le_game_interval($gameCode);
    $slot     = intdiv(time(), $interval);
    $n        = ($slot + le_issue_offset($gameCode)) % 100000;
    if ($n < 0) $n += 100000;
    $locTail  = (int)substr(str_pad((string)$n, 5, '0', STR_PAD_LEFT), -5);
    $upTail   = (int)substr($up, -5);

    $d = ($upTail - $locTail) % 100000;
    if ($d < 0) $d += 100000;
    if ($d > 50000) $d -= 100000;
    if ($d > 5 || $d < -5) $d = 0;          // pagal value => formula hi theek hai

    @file_put_contents($file, json_encode(['t' => time(), 'd' => $d]), LOCK_EX);
    $mem[$gameCode] = $d;
    return $d;
}

/** bina drift ke (drift function ke andar use hota hai — recursion se bachne ke liye) */
function le_issue_slot_number(string $gameCode, int $slot): string
{
    $interval = le_game_interval($gameCode);
    $n = ($slot + le_issue_offset($gameCode)) % 100000;
    if ($n < 0) $n += 100000;
    return date('Ymd', $slot * $interval) . le_issue_mid($gameCode) . str_pad((string)$n, 5, '0', STR_PAD_LEFT);
}

function le_issue_for_time(int $ts, string $gameCode = ''): string
{
    $interval = le_game_interval($gameCode);
    $slot = intdiv($ts, $interval);
    $n = ($slot + le_issue_offset($gameCode) + le_issue_drift($gameCode)) % 100000;
    if ($n < 0) $n += 100000;
    return date('Ymd', $slot * $interval) . le_issue_mid($gameCode) . str_pad((string)$n, 5, '0', STR_PAD_LEFT);
}

function le_issue_by_offset(string $gameCode, int $offset = 0): string
{
    $interval = le_game_interval($gameCode);
    return le_issue_for_time(time() - ($offset * $interval), $gameCode);
}""",
)

# ------------------------------------------------------- 2. upstream rows/newest
rep(
    "api/_core/lottery_engine.php",
    """/** upstream ki history: issueNumber => row  (2 page = 20 period) */
function le_upstream_results(string $gameCode): array
{
    static $mem = [];
    if (!le_upstream_enabled()) return [];
    if (isset($mem[$gameCode])) return $mem[$gameCode];
    $out = [];
    $fam = le_upstream_family($gameCode);
    for ($p = 1; $p <= 2; $p++) {
        $j = le_upstream_get('/' . $fam . '/' . rawurlencode($gameCode) . '/GetHistoryIssuePage.json?pageNo=' . $p . '&pageSize=10');
        $list = (is_array($j) && isset($j['data']['list']) && is_array($j['data']['list'])) ? $j['data']['list'] : [];
        foreach ($list as $row) {
            if (!is_array($row)) continue;
            $num = trim((string)($row['issueNumber'] ?? $row['issueNo'] ?? $row['period'] ?? ''));
            if ($num !== '') $out[$num] = $row;
        }
    }
    $mem[$gameCode] = $out;
    return $out;
}""",
    """/** upstream ki history — ORDERED list (nayi row sabse pehle). 2 page = 20 period */
function le_upstream_rows(string $gameCode): array
{
    static $mem = [];
    if (!le_upstream_enabled()) return [];
    if (isset($mem[$gameCode])) return $mem[$gameCode];
    $rows = [];
    $fam = le_upstream_family($gameCode);
    for ($p = 1; $p <= 2; $p++) {
        $j = le_upstream_get('/' . $fam . '/' . rawurlencode($gameCode) . '/GetHistoryIssuePage.json?pageNo=' . $p . '&pageSize=10');
        $list = (is_array($j) && isset($j['data']['list']) && is_array($j['data']['list'])) ? $j['data']['list'] : [];
        foreach ($list as $row) {
            if (is_array($row)) $rows[] = $row;
        }
    }
    $mem[$gameCode] = $rows;
    return $rows;
}

/** upstream ki SABSE NAYI row (= current period, upstream row0 current hota hai) */
function le_upstream_newest(string $gameCode): ?array
{
    $rows = le_upstream_rows($gameCode);
    return (isset($rows[0]) && is_array($rows[0])) ? $rows[0] : null;
}

/** upstream ki history: issueNumber => row */
function le_upstream_results(string $gameCode): array
{
    static $mem = [];
    if (isset($mem[$gameCode])) return $mem[$gameCode];
    $out = [];
    foreach (le_upstream_rows($gameCode) as $row) {
        $num = trim((string)($row['issueNumber'] ?? $row['issueNo'] ?? $row['period'] ?? ''));
        if ($num !== '') $out[$num] = $row;
    }
    $mem[$gameCode] = $out;
    return $out;
}""",
)

# ------------------------------------------------------- 3. config reveal = 999
rep(
    "api/_core/config.php",
    """//      FIX #8 (2026-10-08): ab default -1 (BAND).
//      Kyun: 1 par current period ka result timer khatam hone se 1-2 second
//      PAHLE history ki pehli row me aa jata tha — isse "period/result badal
//      gaya" dikhta tha. -1 par current period kabhi running timer ke sath
//      list me nahi aata; period khatam hote hi wo pehli row me aa jata hai,
//      aur popup bhi theek waqt par (timer khatam hone par) dikhta hai.
//      -1 = default (recommended)   |   1 = purana (jaldi reveal) behaviour
if (!defined('LE_REVEAL_AT_COUNTDOWN')) define('LE_REVEAL_AT_COUNTDOWN', -1);""",
    """//      FIX #10 (2026-10-08): ab default 999 = HAMESHA ON.
//      Kyun: drawer/upstream ki pehli row me CURRENT period hota hai (uska
//      result bhi usi waqt published hota hai). Game ki list bhi ab wahin se
//      shuru hogi, tabhi drawer aur game BILKUL ek dikhenge.
//      Popup bhi theek waqt par (timer khatam hone par) dikhta hai.
//      999 = default (recommended)  |  -1 = current period list me nahi aayega
if (!defined('LE_REVEAL_AT_COUNTDOWN')) define('LE_REVEAL_AT_COUNTDOWN', 999);""",
)

# ------------------------------------------------------- 4. diag: [B] use row0
rep(
    "maanupdiag.php",
    """    $j = json_decode($r['body'], true);
    $ui = '';
    if (is_array($j)) {
        $d = $j['data'] ?? null;
        if (is_array($d)) {
            $ui = trim((string)($d['issueNumber'] ?? $d['issueNo'] ?? $d['issue'] ?? $d['period'] ?? ''));
            echo '      upstream -> issueNumber=' . $ui
                 . '  start=' . ($d['startTime'] ?? '-')
                 . '  end=' . ($d['endTime'] ?? '-')
                 . '  countdown=' . ($d['countdown'] ?? '-') . "\\n";
        }
    }""",
    """    $j = json_decode($r['body'], true);
    $ui = '';
    if (is_array($j)) {
        $d = $j['data'] ?? null;
        if (is_array($d)) {
            $ui = trim((string)($d['issueNumber'] ?? $d['issueNo'] ?? $d['issue'] ?? $d['period'] ?? ''));
            echo '      upstream -> issueNumber=' . $ui
                 . '  start=' . ($d['startTime'] ?? '-')
                 . '  end=' . ($d['endTime'] ?? '-')
                 . '  countdown=' . ($d['countdown'] ?? '-') . "\\n";
        }
    }
    // /webapi/kv/issue is bucket par 404 hai — tab upstream ki SABSE NAYI
    // history row (= current period) use karo.
    if ($ui === '' && function_exists('le_upstream_newest')) {
        $nr = le_upstream_newest($g);
        if (is_array($nr)) {
            $ui = trim((string)($nr['issueNumber'] ?? $nr['issueNo'] ?? $nr['period'] ?? ''));
            if ($ui !== '') echo '      upstream -> NEWEST HISTORY ROW issueNumber=' . $ui . "\\n";
        }
    }""",
)

# ------------------------------------------------------- 5. diag: drift line
rep(
    "maanupdiag.php",
    """echo '    temp dir            : ' . (function_exists('sys_get_temp_dir') ? sys_get_temp_dir() : 'n/a') . "\\n\\n";""",
    """echo '    temp dir            : ' . (function_exists('sys_get_temp_dir') ? sys_get_temp_dir() : 'n/a') . "\\n";
if (function_exists('le_issue_offset')) {
    foreach (['WinGo_30S', 'WinGo_1M', 'WinGo_3M', 'WinGo_5M'] as $g) {
        echo '    offset ' . str_pad($g, 10) . ' : mid=' . le_issue_mid($g)
             . '  C=' . le_issue_offset($g) . '  drift=' . le_issue_drift($g) . "\\n";
    }
}
echo "\\n";""",
)

if FAILS:
    print("\n\nFAILED:")
    for f in FAILS:
        print("  -", f)
    sys.exit(1)

print("\nfix#10 patch applied OK to", SRC)
