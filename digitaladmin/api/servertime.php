<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
include 'conn.php';
require_once dirname(__DIR__, 2) . '/saas_lottery/admin_override.php';

$type = isset($_GET['type']) ? (string)$_GET['type'] : '';
$typeMap = array(
    'wingo30'=>array('WinGo_30S',30),'wingo1'=>array('WinGo_1M',60),
    'wingo3'=>array('WinGo_3M',180),'wingo5'=>array('WinGo_5M',300),
    'k31'=>array('K3_1M',60),'k33'=>array('K3_3M',180),
    'k35'=>array('K3_5M',300),'k310'=>array('K3_10M',600),
    '5d1'=>array('D5_1M',60),'5d3'=>array('D5_3M',180),
    '5d5'=>array('D5_5M',300),'5d10'=>array('D5_10M',600),
    'ktrx'=>array('TrxWinGo_1M',60),'ktrx3'=>array('TrxWinGo_3M',180),
    'ktrx5'=>array('TrxWinGo_5M',300),'ktrx10'=>array('TrxWinGo_10M',600),
    'motoracing'=>array('MotoRace_1M',60),
);

if (!isset($typeMap[$type])) {
    http_response_code(400);
    echo json_encode(array('error'=>'Unsupported game type'));
    exit;
}

$gameCode = $typeMap[$type][0];
$interval = (int)$typeMap[$type][1];
$now = time();
$startTimestamp = (int)(floor($now / $interval) * $interval);
$period = sl_admin_override_period_id($gameCode, $startTimestamp);
$prediction = null;

try {
    $pending = sl_admin_override_get_pending($conn, $gameCode);
    if ($pending && hash_equals($period, (string)$pending['issue_number'])) {
        $prediction = (string)$pending['premium'];
    }
} catch (Throwable $ignored) {
    $prediction = null;
}

echo json_encode(array(
    'startTime'=>gmdate('Y-m-d\TH:i:s\Z', $startTimestamp),
    'period'=>$period,
    'endTime'=>gmdate('Y-m-d\TH:i:s\Z', $startTimestamp + $interval),
    'serverTime'=>$now * 1000,
    'prediction'=>$prediction,
), JSON_UNESCAPED_SLASHES);
