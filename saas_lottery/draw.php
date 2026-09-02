<?php
require_once dirname(__DIR__) . '/api/Lottery/_common.php';
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$parts = array_values(array_filter(explode('/', trim($uri, '/'))));
$lottery = $parts[0] ?? 'WinGo';
$file = end($parts) ?: 'WinGo_30S.json';
$history = strcasecmp($file, 'GetHistoryIssuePage.json') === 0;
$gameCode = $history ? ($parts[count($parts) - 2] ?? 'WinGo_30S') : preg_replace('/\.json$/i', '', $file);
if (!isset(saas_game_definitions()[$gameCode])) {
    $gameCode = $lottery === 'K3' ? 'K3_1M' : ($lottery === 'D5' ? 'D5_1M' : 'WinGo_30S');
}
if ($history) {
    saas_send(saas_history($gameCode, (int)($_GET['pageSize'] ?? 20)));
}
saas_send(saas_issue($gameCode));
