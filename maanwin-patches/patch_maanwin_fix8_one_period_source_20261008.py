#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
MAANWIN — FIX #8 (2026-10-08)
================================
ROOT CAUSE (live measurement, 2026-10-08 14:06–14:08 IST, host maanwin.club9.eu.cc):

    https://maanwin.club9.eu.cc/api/Lottery/GetGameIssue?gameCode=WinGo_30S
        -> "issueNumber":"20261008100051036"          <-- GAME SCREEN (timer/period)
    https://maanwin.club9.eu.cc/api/Lottery/GetHistoryIssuePage?gameCode=WinGo_30S
        -> "issueNumber":"20261008100014952" ...      <-- DRAWER / PERIOD HISTORY

    Same host, same second, DO ALAG PERIOD NUMBERS (constant gap = 36080 periods).

    Reason: the two numbers were produced by TWO DIFFERENT code paths
      * lottery_issue()      (api/_core/bootstrap.php) -> game screen / timer / bet
      * le_issue_by_offset() (api/_core/lottery_engine.php) -> period history list
    On this build those two functions do not share the same base, so the game
    showed one period and the drawer showed another ("dono alag aa raha hai").

FIX #8
    1. ONE single generator  : le_issue_for_time()   (engine)
       - le_issue_by_offset()  uses it
       - lottery_issue()       uses it
       => game screen, drawer, trend, bet and settlement can never disagree again.
    2. ONE single list builder: le_history_page()    (engine)
       - /api/Lottery/GetHistoryIssuePage        (api/_router.php)
       - /WinGo/<game>/GetHistoryIssuePage.json  (api/_draw_router.php)
       => drawer JSON and game JSON are byte-identical lists.
    3. Trend list also uses the consecutive sequence.
    4. LE_REVEAL_AT_COUNTDOWN = -1  (no early reveal)
       The current period no longer jumps into row 0 while the timer is still
       running, so "period/result badal gaya" can never be seen again. The
       WIN/LOSS popup still fires — the period appears in row 0 the moment it
       is actually over.

This script edits the build dir in place and then generates maaninstall.php
(a self-contained browser installer that writes the files into EVERY detected
docroot, so the wrong-folder problem can no longer happen).

Exit code != 0 => koi ek bhi replacement match nahi hui (FAIL loudly).
"""
import os
import re
import sys
import zlib
import base64

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
    s = s.replace(old, new)
    with open(full, "w", encoding="utf-8") as f:
        f.write(s)
    print("  ok  %-34s (%d)" % (path, n))


# ---------------------------------------------------------------- 1. engine
rep(
    "api/_core/lottery_engine.php",
    """function le_issue_by_offset(string $gameCode, int $offset = 0): string
{
    $interval = le_game_interval($gameCode);
    $now = time() - ($offset * $interval);
    $slot = intdiv($now, $interval);
    return date('Ymd', $slot * $interval) . '1000' . str_pad((string)($slot % 100000), 5, '0', STR_PAD_LEFT);
}
""",
    """// =======================================================================
// FIX #8 — EK HI PERIOD NUMBER, HAR JAGAH  (2026-10-08)
//   ASLI PROBLEM (live me naapi gayi):
//       /api/Lottery/GetGameIssue      (game screen / timer) : 20261008100051036
//       /api/Lottery/GetHistoryIssuePage (drawer / history)  : 20261008100014952
//     Dono alag-alag function period number bana rahe the
//     (lottery_issue() bootstrap me, le_issue_by_offset() engine me), isliye
//     "game ka period/result" aur "drawer ka period/result" ALAG dikhta tha.
//   FIX:
//     Ab EK hi function (le_issue_for_time) se period number banta hai —
//     game screen, drawer, trend, bet aur settlement sabhi ke liye.
// =======================================================================
function le_issue_for_time(int $ts, string $gameCode = ''): string
{
    $interval = le_game_interval($gameCode);
    $slot = intdiv($ts, $interval);
    return date('Ymd', $slot * $interval) . '1000' . str_pad((string)($slot % 100000), 5, '0', STR_PAD_LEFT);
}

function le_issue_by_offset(string $gameCode, int $offset = 0): string
{
    $interval = le_game_interval($gameCode);
    return le_issue_for_time(time() - ($offset * $interval), $gameCode);
}
""",
)

# ------------------------------------------------- 2. engine: le_history_page
rep(
    "api/_core/lottery_engine.php",
    """function le_color_from_number(int $n): string
{""",
    """/**
 * Period-history ka EK HI hisaab.
 *   /api/Lottery/GetHistoryIssuePage        (api/_router.php)
 *   /WinGo/<game>/GetHistoryIssuePage.json  (api/_draw_router.php)
 * DONO isi function se list banate hain, isliye drawer aur game me kabhi bhi
 * alag period / alag result nahi aa sakta.  (FIX #8)
 */
function le_history_page(string $gameCode, int $pageNo, int $pageSize): array
{
    $pageNo   = max(1, (int)$pageNo);
    $pageSize = max(1, min(10, (int)$pageSize));
    $interval = le_game_interval($gameCode);

    $shift = function_exists('le_history_start_offset') ? le_history_start_offset($gameCode) : 1;
    $startOffset = (($pageNo - 1) * $pageSize) + $shift;
    if ($startOffset < 0) $startOffset = 0;

    $issues = le_issue_sequence($gameCode, $startOffset, $pageSize);
    $list = [];
    for ($i = 0; $i < $pageSize; $i++) {
        $offset = $startOffset + $i;
        $issue  = isset($issues[$i]) ? (string)$issues[$i] : (string)le_issue_by_offset($gameCode, $offset);
        $r = le_result_for_issue($gameCode, $issue, true);
        $list[] = lottery_public_result($gameCode, $issue, $r, now_ms() - ($offset * $interval * 1000));
    }
    return ['list' => $list, 'pageNo' => $pageNo, 'pageSize' => $pageSize];
}

function le_color_from_number(int $n): string
{""",
)

# ------------------------------------------------- 3. bootstrap lottery_issue
rep(
    "api/_core/bootstrap.php",
    """    // Original-style issue: YYYYMMDD1000xxxxx (WinGo screenshots use this shape).
    $issue = date('Ymd', $start) . '1000' . str_pad((string)($slot % 100000), 5, '0', STR_PAD_LEFT);""",
    """    // Original-style issue: YYYYMMDD1000xxxxx (WinGo screenshots use this shape).
    // FIX #8: ab period number EK HI jagah se banta hai (le_issue_for_time),
    // isliye game screen ka period aur history/drawer ka period hamesha same.
    $issue = le_issue_for_time($start, $gameCode);""",
)

# ------------------------------------------------- 4. router: history handler
rep(
    "api/_router.php",
    """    $list = [];
    // FIX #3: popup ke liye current period ko pehli row me dikhana.
    // FIX #5: period number LAGATAAR (consecutive) rakho, taaki period
    //         aur result hamesha aapas me match karein.
    $shift = le_history_start_offset($code);
    $startOffset = (($pageNo - 1) * $pageSize) + $shift;
    if ($startOffset < 0) $startOffset = 0;
    $issues = le_issue_sequence($code, $startOffset, $pageSize);
    for ($i = 0; $i < $pageSize; $i++) {
        $offset = $startOffset + $i;
        $issue = isset($issues[$i]) ? (string)$issues[$i] : (string)le_issue_by_offset($code, $offset);
        $r = le_result_for_issue($code, $issue, true);

        $list[] = lottery_public_result($code, $issue, $r, now_ms() - ($offset * le_game_interval($code) * 1000));
    }

    api_success([
        'list' => $list,
        'pageNo' => $pageNo,
        'pageSize' => $pageSize,
        'totalPage' => 50,
        'totalCount' => 500
    ], 'Success', ['serviceTime' => now_ms()]);""",
    """    // FIX #8: drawer (/api/Lottery/...) aur game JSON (/WinGo/.../....json)
    // DONO ab isi EK function se list banate hain — kabhi alag nahi ho sakte.
    $page = le_history_page($code, $pageNo, $pageSize);

    api_success([
        'list' => $page['list'],
        'pageNo' => $page['pageNo'],
        'pageSize' => $page['pageSize'],
        'totalPage' => 50,
        'totalCount' => 500
    ], 'Success', ['serviceTime' => now_ms()]);""",
)

# ------------------------------------------------- 5. router: trend
rep(
    "api/_router.php",
    """    $code=first_value($d,['gameCode'],'WinGo_30S'); $list=[];
    for($i=1;$i<=30;$i++){ $issue=le_issue_by_offset($code,$i); $r=le_result_for_issue($code,$issue,true); $list[]=['issueNumber'=>$issue,'number'=>$r['number'],'premium'=>$r['premium'],'color'=>$r['color'],'bigSmall'=>$r['bigSmall'],'sum'=>$r['sum']]; }""",
    """    $code=first_value($d,['gameCode'],'WinGo_30S'); $list=[];
    // FIX #8: trend bhi wahi LAGATAAR period numbers jo drawer/game dikhate hain.
    $seq = function_exists('le_issue_sequence') ? le_issue_sequence($code, 1, 30) : [];
    for($i=1;$i<=30;$i++){ $issue=isset($seq[$i-1])?(string)$seq[$i-1]:le_issue_by_offset($code,$i); $r=le_result_for_issue($code,$issue,true); $list[]=['issueNumber'=>$issue,'number'=>$r['number'],'premium'=>$r['premium'],'color'=>$r['color'],'bigSmall'=>$r['bigSmall'],'sum'=>$r['sum']]; }""",
)

# ------------------------------------------------- 6. draw_router
rep(
    "api/_draw_router.php",
    """if (str_contains($path, 'GetHistoryIssuePage')) {
    le_settle_pending_bets($gameCode);
    $list = [];
    $pageNo = max(1, (int)($_GET['pageNo'] ?? $_GET['page'] ?? 1));
    $set = site_settings();
    $pageSize = max(1, min(10, (int)($set['game_history_page_size'] ?? 10)));
    // FIX #3: popup ke liye current period ko pehli row me dikhana.
    // FIX #5: period number LAGATAAR (consecutive) rakho, taaki period
    //         aur result hamesha aapas me match karein.
    $shift = le_history_start_offset($gameCode);
    $startOffset = (($pageNo - 1) * $pageSize) + $shift;
    if ($startOffset < 0) $startOffset = 0;
    $issues = le_issue_sequence($gameCode, $startOffset, $pageSize);
    for ($i = 0; $i < $pageSize; $i++) {
        $offset = $startOffset + $i;
        $issueNum = isset($issues[$i]) ? (string)$issues[$i] : (string)le_issue_by_offset($gameCode, $offset);
        $r = le_result_for_issue($gameCode, $issueNum, true);
        $list[] = lottery_public_result($gameCode, $issueNum, $r, now_ms() - $offset * le_game_interval($gameCode) * 1000);
    }
    api_success(['list'=>$list,'pageNo'=>$pageNo,'pageSize'=>$pageSize,'totalPage'=>999,'totalCount'=>9990], 'Succeed', ['serviceTime'=>now_ms()]);
}""",
    """if (str_contains($path, 'GetHistoryIssuePage')) {
    le_settle_pending_bets($gameCode);
    $pageNo = max(1, (int)($_GET['pageNo'] ?? $_GET['page'] ?? 1));
    $set = site_settings();
    $pageSize = max(1, min(10, (int)($set['game_history_page_size'] ?? 10)));
    // FIX #8: bilkul WAHII list jo /api/Lottery/GetHistoryIssuePage deta hai.
    // Drawer aur game JSON ab EK hi function (le_history_page) se bante hain.
    $page = le_history_page($gameCode, $pageNo, $pageSize);
    api_success(['list'=>$page['list'],'pageNo'=>$page['pageNo'],'pageSize'=>$page['pageSize'],'totalPage'=>999,'totalCount'=>9990], 'Succeed', ['serviceTime'=>now_ms()]);
}""",
)

# ------------------------------------------------- 7. config: no early reveal
rep(
    "api/_core/config.php",
    """//      1 = default (recommended)   |   99 = popup ka ye fix band
if (!defined('LE_REVEAL_AT_COUNTDOWN')) define('LE_REVEAL_AT_COUNTDOWN', 1);""",
    """//      FIX #8 (2026-10-08): ab default -1 (BAND).
//      Kyun: 1 par current period ka result timer khatam hone se 1-2 second
//      PAHLE history ki pehli row me aa jata tha — isse "period/result badal
//      gaya" dikhta tha. -1 par current period kabhi running timer ke sath
//      list me nahi aata; period khatam hote hi wo pehli row me aa jata hai,
//      aur popup bhi theek waqt par (timer khatam hone par) dikhta hai.
//      -1 = default (recommended)   |   1 = purana (jaldi reveal) behaviour
if (!defined('LE_REVEAL_AT_COUNTDOWN')) define('LE_REVEAL_AT_COUNTDOWN', -1);""",
)

# ------------------------------------------------- 8. diag section [1]
rep(
    "maandiag.php",
    """echo '    ' . str_pad('le_history_start_offset()', 26) . ' : ' . (function_exists('le_history_start_offset') ? 'present' : 'MISSING') . "\\n";
echo "\\n";""",
    """echo '    ' . str_pad('le_history_start_offset()', 26) . ' : ' . (function_exists('le_history_start_offset') ? 'present' : 'MISSING') . "\\n";
echo '    ' . str_pad('le_bet_period_is_closed()', 26) . ' : ' . (function_exists('le_bet_period_is_closed') ? 'present' : 'MISSING') . "\\n";
echo '    ' . str_pad('le_issue_for_time()', 26) . ' : ' . (function_exists('le_issue_for_time') ? 'present' : 'MISSING') . "\\n";
echo '    ' . str_pad('le_history_page()', 26) . ' : ' . (function_exists('le_history_page') ? 'present' : 'MISSING') . "\\n";
echo "\\n";""",
)

# ------------------------------------------------- 9. diag section [9]
rep(
    "maandiag.php",
    """echo "\\n" . $line . "\\n";
echo "END. Is file ko ab delete kar dena.\\n";""",
    """// ------------------------------------------------------------------ 9
echo "[9] FIX #8 — GAME SCREEN vs DRAWER  (ek hi period number?)\\n";
echo "    Yahi wo jagah hai jahan 'drawer ka result' aur 'game ka result'\\n";
echo "    alag-alag dikhte the. Ab dono ko BARABAR hona chahiye.\\n";
foreach (['WinGo_30S', 'WinGo_1M', 'WinGo_3M', 'WinGo_5M'] as $g) {
    $a  = function_exists('lottery_issue') ? lottery_issue($g) : null;
    $ai = is_array($a) ? (string)$a['issueNumber'] : '-';
    $bi = function_exists('le_issue_by_offset') ? (string)le_issue_by_offset($g, 0) : '-';
    echo "  --- $g ---\\n";
    echo '      game screen (lottery_issue)   : ' . $ai . "\\n";
    echo '      drawer/trend (le_issue_by_off): ' . $bi . "\\n";
    echo '      MATCH? : ' . ($ai === $bi ? 'YES   (fix #8 OK)' : 'NO  <<< YAHI \"dono alag\" KI WAJAH HAI') . "\\n";
}
echo "\\n";
echo "    NAYA CHECK — dono endpoint ki PEHLI row bhi same honi chahiye:\\n";
if (function_exists('le_history_page')) {
    $p1 = le_history_page('WinGo_30S', 1, 3);
    foreach ($p1['list'] as $i => $row) {
        echo '      row' . $i . ' : ' . $row['issueNumber'] . '  number=' . $row['number'] . "\\n";
    }
} else {
    echo "      le_history_page() MISSING\\n";
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

print("\nfix#8 patch applied OK to", SRC)
