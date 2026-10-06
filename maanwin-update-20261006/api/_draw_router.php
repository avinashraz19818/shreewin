<?php
require_once __DIR__ . '/_core/bootstrap.php';

$gameCode = $_GET['gameCode'] ?? '';
$path = $_GET['path'] ?? '';

if (!$gameCode && $path) {
    $parts = explode('/', trim($path, '/'));
    // /WinGo/WinGo_30S.json OR /WinGo/WinGo_30S/GetHistoryIssuePage.json
    $gameCode = $parts[1] ?? ($parts[0] ?? 'WinGo_30S');
}
$gameCode = preg_replace('/\.json$/i', '', $gameCode ?: 'WinGo_30S');
$gameCode = preg_replace('/[^A-Za-z0-9_]/', '', $gameCode ?: 'WinGo_30S');

if (str_contains($path, 'GetHistoryIssuePage')) {
    le_settle_pending_bets($gameCode);
    $list = [];
    $pageNo = max(1, (int)($_GET['pageNo'] ?? $_GET['page'] ?? 1));
    $set = site_settings();
    $pageSize = max(1, min(100, (int)($_GET['pageSize'] ?? $_GET['page_size'] ?? ($set['game_history_page_size'] ?? 10))));
    // offset 0 = the round being played right now (result already drawn)
    for ($i = 0; $i < $pageSize; $i++) {
        $offset = (($pageNo - 1) * $pageSize) + $i;
        $issueNum = le_issue_by_offset($gameCode, $offset);
        $r = le_result_for_issue($gameCode, $issueNum, true);
        $list[] = lottery_public_result($gameCode, (string)$issueNum, $r, now_ms() - $offset * le_game_interval($gameCode) * 1000);
    }
    api_success(['list'=>$list,'pageNo'=>$pageNo,'pageSize'=>$pageSize,'totalPage'=>999,'totalCount'=>9990], 'Succeed', ['serviceTime'=>now_ms()]);
}

// /webapi/kv/issue/WinGo_30S and /WinGo/WinGo_30S.json dono same response denge.
api_success(lottery_issue($gameCode));
