<?php

require_once dirname(__DIR__, 2) . '/developer-maruf/api/webapi/_common.php';

function saas_game_definitions(): array
{
    return [
        'WinGo_30S' => ['lottery' => 'WinGo', 'categoryId' => 1, 'typeId' => 1, 'seconds' => 30, 'table' => 'gellaluhogiondu_phalitansa30'],
        'WinGo_1M'  => ['lottery' => 'WinGo', 'categoryId' => 1, 'typeId' => 2, 'seconds' => 60, 'table' => 'gellaluhogiondu_phalitansa'],
        'WinGo_3M'  => ['lottery' => 'WinGo', 'categoryId' => 1, 'typeId' => 3, 'seconds' => 180, 'table' => 'gellaluhogiondu_phalitansa_drei'],
        'WinGo_5M'  => ['lottery' => 'WinGo', 'categoryId' => 1, 'typeId' => 4, 'seconds' => 300, 'table' => 'gellaluhogiondu_phalitansa_funf'],
        'WinGo_10M' => ['lottery' => 'WinGo', 'categoryId' => 1, 'typeId' => 5, 'seconds' => 600, 'table' => 'gellaluhogiondu_phalitansa_zehn'],
        'K3_1M'     => ['lottery' => 'K3', 'categoryId' => 2, 'typeId' => 9, 'seconds' => 60, 'table' => 'gellaluhogiondu_kemeru_phalitansa'],
        'K3_3M'     => ['lottery' => 'K3', 'categoryId' => 2, 'typeId' => 10, 'seconds' => 180, 'table' => 'gellaluhogiondu_kemeru_phalitansa_drei'],
        'K3_5M'     => ['lottery' => 'K3', 'categoryId' => 2, 'typeId' => 11, 'seconds' => 300, 'table' => 'gellaluhogiondu_kemeru_phalitansa_funf'],
        'K3_10M'    => ['lottery' => 'K3', 'categoryId' => 2, 'typeId' => 12, 'seconds' => 600, 'table' => 'gellaluhogiondu_kemeru_phalitansa_zehn'],
        'D5_1M'     => ['lottery' => 'D5', 'categoryId' => 3, 'typeId' => 6, 'seconds' => 60, 'table' => 'gellaluhogiondu_aidudi_phalitansa'],
        'D5_3M'     => ['lottery' => 'D5', 'categoryId' => 3, 'typeId' => 7, 'seconds' => 180, 'table' => 'gellaluhogiondu_aidudi_phalitansa_drei'],
        'D5_5M'     => ['lottery' => 'D5', 'categoryId' => 3, 'typeId' => 8, 'seconds' => 300, 'table' => 'gellaluhogiondu_aidudi_phalitansa_funf'],
        'D5_10M'    => ['lottery' => 'D5', 'categoryId' => 3, 'typeId' => 8, 'seconds' => 600, 'table' => 'gellaluhogiondu_aidudi_phalitansa_zehn'],
        'TrxWinGo_1M' => ['lottery' => 'TrxWinGo', 'categoryId' => 4, 'typeId' => 13, 'seconds' => 60, 'table' => 'gellaluhogiondu_trx'],
        'TrxWinGo_3M' => ['lottery' => 'TrxWinGo', 'categoryId' => 4, 'typeId' => 14, 'seconds' => 180, 'table' => 'gellaluhogiondu_trx3'],
        'TrxWinGo_5M' => ['lottery' => 'TrxWinGo', 'categoryId' => 4, 'typeId' => 15, 'seconds' => 300, 'table' => 'gellaluhogiondu_trx5'],
        'TrxWinGo_10M' => ['lottery' => 'TrxWinGo', 'categoryId' => 4, 'typeId' => 16, 'seconds' => 600, 'table' => 'gellaluhogiondu_trx10'],
    ];
}

function saas_definition(string $gameCode): array
{
    $defs = saas_game_definitions();
    return $defs[$gameCode] ?? $defs['WinGo_30S'];
}

function saas_issue(string $gameCode): array
{
    $def = saas_definition($gameCode);
    $seconds = (int)$def['seconds'];
    $now = time();
    $start = intdiv($now, $seconds) * $seconds;
    $seq = intdiv($start - strtotime(date('Y-m-d 00:00:00', $start)), $seconds) + 1;
    $issue = date('Ymd', $start) . str_pad((string)$seq, 5, '0', STR_PAD_LEFT);
    $nextStart = $start + $seconds;
    return [
        'intervalMinute' => $seconds / 60,
        'current' => [
            'issueNumber' => $issue,
            'startTime' => date('Y-m-d H:i:s', $start),
            'endTime' => date('Y-m-d H:i:s', $nextStart),
        ],
        'next' => [
            'issueNumber' => date('Ymd', $nextStart) . str_pad((string)($seq + 1), 5, '0', STR_PAD_LEFT),
            'startTime' => date('Y-m-d H:i:s', $nextStart),
            'endTime' => date('Y-m-d H:i:s', $nextStart + $seconds),
        ],
    ];
}

function saas_history(string $gameCode, int $pageSize = 20): array
{
    global $conn;
    $def = saas_definition($gameCode);
    $table = (string)$def['table'];
    $pageSize = max(1, min(100, $pageSize));
    $list = [];
    $total = 0;
    if (api_table_exists($table)) {
        $count = $conn->query("SELECT COUNT(*) AS total FROM `{$table}`");
        if ($count instanceof mysqli_result) {
            $total = (int)($count->fetch_assoc()['total'] ?? 0);
        }
        $result = $conn->query("SELECT * FROM `{$table}` ORDER BY shonu DESC LIMIT {$pageSize}");
        if ($result instanceof mysqli_result) {
            while ($row = $result->fetch_assoc()) {
                $number = (string)($row['phalitansa'] ?? '0');
                $list[] = [
                    'issueNumber' => (string)($row['kalaparichaya'] ?? ''),
                    'number' => $number,
                    'colour' => (string)($row['banna'] ?? ''),
                    'color' => (string)($row['banna'] ?? ''),
                    'premium' => (string)($row['bele'] ?? $number),
                    'sumCount' => (int)$number,
                    'blockID' => (string)($row['hash'] ?? ''),
                    'blockNumber' => (string)($row['bh'] ?? ''),
                    'blockTime' => (string)($row['dinankavannuracisi'] ?? ''),
                ];
            }
        }
    }
    return ['list' => $list, 'pageNo' => 1, 'totalPage' => (int)ceil($total / $pageSize), 'totalCount' => $total];
}

function saas_send($data = null, int $code = 0, string $msg = 'Succeed'): void
{
    http_response_code(200);
    echo json_encode([
        'data' => $data,
        'code' => $code,
        'msg' => $msg,
        'msgCode' => $code === 0 ? 0 : $code,
        'traceId' => '',
        'serviceTime' => (int)round(microtime(true) * 1000),
        'serviceNowTime' => date('Y-m-d H:i:s'),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function saas_request(bool $auth = true): array
{
    $body = array_merge($_GET, api_input());
    if ($auth) {
        if (isset($body['signature'])) {
            api_require_signature($body);
        }
        $body['_user'] = api_user();
    }
    return $body;
}
