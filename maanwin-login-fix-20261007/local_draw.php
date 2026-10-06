<?php
/**
 * ============================================================================
 *  maanwin — LOCAL LOTTERY DRAW ENDPOINT
 * ============================================================================
 *  Ye file lottery ka period / result JSON aapke apne MySQL se banati hai
 *  (bilkul purani site jaisa — jisme login bhi kaam karta tha).
 *
 *  Isse period number aur bet DONO ek hi jagah se aate hain,
 *  isliye bet hamesha sahi period par lagta hai aur settlement theek hota hai.
 *
 *  SETTING:
 *      AR_PERIOD_OFFSET = 0   -> current period (PURANI SITE JAISA)  <= DEFAULT
 *      AR_PERIOD_OFFSET = 1   -> ek period piche
 * ============================================================================
 */

require_once __DIR__ . '/api/_core/bootstrap.php';

if (!defined('AR_PERIOD_OFFSET')) {
    define('AR_PERIOD_OFFSET', 0);
}

$AR_URI  = (string) ($_SERVER['REQUEST_URI'] ?? '');
$AR_PATH = trim((string) parse_url($AR_URI, PHP_URL_PATH), '/');

// /webapi/kv/issue/XXX , /lottery-draw/XXX , /WinGo/WinGo_30S.json  sab yahin aate hain
$AR_PATH = preg_replace('#^(lottery-draw|webapi/kv/issue)/#i', '', $AR_PATH);
$AR_PATH = trim($AR_PATH, '/');

$AR_PARTS = explode('/', $AR_PATH);
$AR_RAW   = (string) ((($AR_PARTS[1] ?? '') !== '') ? $AR_PARTS[1] : ($AR_PARTS[0] ?? 'WinGo_30S'));

$gameCode = preg_replace('/\.json$/i', '', $AR_RAW);
$gameCode = preg_replace('/[^A-Za-z0-9_]/', '', $gameCode);
if ($gameCode === '') {
    $gameCode = 'WinGo_30S';
}

$AR_OFFSET = (int) AR_PERIOD_OFFSET;

// ---------- History (GetHistoryIssuePage) ----------
if (stripos($AR_PATH, 'GetHistoryIssuePage') !== false) {
    le_settle_pending_bets($gameCode);

    $list     = [];
    $pageNo   = max(1, (int) ($_GET['pageNo'] ?? $_GET['page'] ?? 1));
    $set      = site_settings();
    $pageSize = max(1, min(10, (int) ($set['game_history_page_size'] ?? 10)));
    $interval = le_game_interval($gameCode);

    for ($i = 1; $i <= $pageSize; $i++) {
        $o        = (($pageNo - 1) * $pageSize) + $i + $AR_OFFSET;
        $issueNum = le_issue_by_offset($gameCode, $o);
        $r        = le_result_for_issue($gameCode, $issueNum, true);
        $list[]   = lottery_public_result($gameCode, (string) $issueNum, $r, now_ms() - ($o * $interval * 1000));
    }

    api_success(
        ['list' => $list, 'pageNo' => $pageNo, 'pageSize' => $pageSize, 'totalPage' => 999, 'totalCount' => 9990],
        'Succeed',
        ['serviceTime' => now_ms()]
    );
}

// ---------- Current period ----------
$issue = lottery_issue($gameCode);

if ($AR_OFFSET !== 0) {
    // Sirf period number shift hota hai, time nahi —
    // isse countdown kabhi negative nahi hota.
    $issue['issueNumber'] = le_issue_by_offset($gameCode, $AR_OFFSET);
}

api_success($issue);
