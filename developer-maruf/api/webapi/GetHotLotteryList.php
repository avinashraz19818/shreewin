<?php

require_once __DIR__ . '/_common.php';
api_require_post();
$body = api_input();
api_require_signature($body);
api_user();

function hot_lottery_window(int $minutes): array
{
    $seconds = $minutes * 60;
    $now = time();
    $start = intdiv($now, $seconds) * $seconds;
    return [date('Y-m-d H:i:s', $start), date('Y-m-d H:i:s', $start + $seconds)];
}

[$d5Start, $d5End] = hot_lottery_window(10);
[$k3Start, $k3End] = hot_lottery_window(3);
[$k310Start, $k310End] = hot_lottery_window(10);
$serviceNow = date('Y-m-d H:i:s');

echo json_encode([
    'data' => [
        ['categoryId' => 3, 'typeId' => 8, 'gameCode' => 'D5_10M', 'startTime' => $d5Start, 'endTime' => $d5End, 'serverTime' => $serviceNow],
        ['categoryId' => 2, 'typeId' => 10, 'gameCode' => 'K3_3M', 'startTime' => $k3Start, 'endTime' => $k3End, 'serverTime' => $serviceNow],
        ['categoryId' => 2, 'typeId' => 12, 'gameCode' => 'K3_10M', 'startTime' => $k310Start, 'endTime' => $k310End, 'serverTime' => $serviceNow],
    ],
    'code' => 0,
    'msg' => 'Succeed',
    'msgCode' => 0,
    'traceId' => '',
    'serviceNowTime' => $serviceNow,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

