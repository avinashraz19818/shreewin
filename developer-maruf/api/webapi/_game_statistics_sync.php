<?php
/**
 * Unified game statistics for legacy WinGo/K3/5D/TRX and SaaS lottery bets.
 */

if (!function_exists('daman_stats_table_exists')) {
    function daman_stats_table_exists(mysqli $conn, string $table): bool
    {
        $stmt = $conn->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1');
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('s', $table);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result && $result->num_rows > 0;
        $stmt->close();
        return $exists;
    }
}

if (!function_exists('send_synced_game_statistics')) {
    function send_synced_game_statistics(mysqli $conn, int $userId, string $startDate, string $endDate, string $serviceNow): void
    {
        $start = trim(substr($startDate, 0, 10));
        $end = trim(substr($endDate, 0, 10));
        if ($start !== '') {
            $start = $conn->real_escape_string($start);
        }
        if ($end !== '') {
            $end = $conn->real_escape_string($end);
        }

        $dateCondition = static function (string $column) use ($start, $end): string {
            if ($start !== '' && $end !== '') {
                return " AND DATE({$column}) >= '{$start}' AND DATE({$column}) <= '{$end}'";
            }
            if ($start !== '') {
                return " AND DATE({$column}) >= '{$start}'";
            }
            if ($end !== '') {
                return " AND DATE({$column}) <= '{$end}'";
            }
            return '';
        };

        $legacy = [
            ['bajikattuttate', 1, 'WinGo_1M'],
            ['bajikattuttate_drei', 4, 'WinGo_3M'],
            ['bajikattuttate_funf', 7, 'WinGo_5M'],
            ['bajikattuttate_zehn', 10, 'WinGo_10M'],
            ['bajikattuttate_aidudi', 2, 'D5_1M'],
            ['bajikattuttate_aidudi_drei', 6, 'D5_3M'],
            ['bajikattuttate_aidudi_funf', 9, 'D5_5M'],
            ['bajikattuttate_aidudi_zehn', 12, 'D5_10M'],
            ['bajikattuttate_kemuru', 3, 'K3_1M'],
            ['bajikattuttate_kemuru_drei', 5, 'K3_3M'],
            ['bajikattuttate_kemuru_funf', 8, 'K3_5M'],
            ['bajikattuttate_kemuru_zehn', 11, 'K3_10M'],
            ['bajikattuttate_trx', 13, 'TrxWinGo_1M'],
            ['bajikattuttate_trx3', 14, 'TrxWinGo_3M'],
            ['bajikattuttate_trx5', 15, 'TrxWinGo_5M'],
            ['bajikattuttate_trx10', 16, 'TrxWinGo_10M'],
        ];

        $parts = [];
        foreach ($legacy as [$table, $gameType, $gameCode]) {
            if (!daman_stats_table_exists($conn, $table)) {
                continue;
            }
            $gameCodeSql = $conn->real_escape_string($gameCode);
            $parts[] = "SELECT CAST(parichaya AS CHAR) AS order_id, CAST(ketebida AS DECIMAL(18,4)) AS bet_amount, sesabida AS win_loss, tiarikala AS add_time, {$gameType} AS game_type, '{$gameCodeSql}' AS game_code FROM `{$table}` WHERE byabaharkarta = {$userId}" . $dateCondition('tiarikala');
        }

        if (daman_stats_table_exists($conn, 'saas_lottery_bets')) {
            $case = "CASE game_code
                WHEN 'WinGo_30S' THEN 1 WHEN 'WinGo_1M' THEN 1 WHEN 'WinGo_3M' THEN 4 WHEN 'WinGo_5M' THEN 7 WHEN 'WinGo_10M' THEN 10
                WHEN 'D5_1M' THEN 2 WHEN 'D5_3M' THEN 6 WHEN 'D5_5M' THEN 9 WHEN 'D5_10M' THEN 12
                WHEN 'K3_1M' THEN 3 WHEN 'K3_3M' THEN 5 WHEN 'K3_5M' THEN 8 WHEN 'K3_10M' THEN 11
                WHEN 'TrxWinGo_1M' THEN 13 WHEN 'TrxWinGo_3M' THEN 14 WHEN 'TrxWinGo_5M' THEN 15 WHEN 'TrxWinGo_10M' THEN 16
                WHEN 'MotoRace_1M' THEN 17 ELSE 0 END";
            $parts[] = "SELECT CONCAT('SL', id) AS order_id, CAST(stake AS DECIMAL(18,4)) AS bet_amount,
                CASE WHEN status = 'won' THEN CAST(payout - stake AS DECIMAL(18,4)) WHEN status = 'lost' THEN CAST(0 - stake AS DECIMAL(18,4)) ELSE 0 END AS win_loss,
                created_at AS add_time, {$case} AS game_type, game_code AS game_code
                FROM saas_lottery_bets WHERE user_id = {$userId}" . $dateCondition('created_at');
        }

        $gameStatis = [];
        $sumBetAmount = 0.0;
        $dateWise = [];

        if ($parts) {
            $union = implode(' UNION ALL ', $parts);
            $result = $conn->query("SELECT order_id, bet_amount, win_loss, add_time, game_type, game_code FROM ({$union}) AS game_data ORDER BY add_time DESC");
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $betAmount = (float)$row['bet_amount'];
                    $winLoss = is_numeric($row['win_loss']) ? (float)$row['win_loss'] : 0.0;
                    $day = substr((string)$row['add_time'], 0, 10);
                    $gameStatis[] = [
                        'gameType' => (int)$row['game_type'],
                        'gameTypeName' => 'lottery',
                        'gameCode' => (string)$row['game_code'],
                        'betAmount' => $betAmount,
                        'betCount' => 1,
                        'betWinLossAmount' => $winLoss,
                        'orderNumber' => (string)$row['order_id'],
                        'addTime' => (string)$row['add_time'],
                        'date' => $day,
                    ];
                    $sumBetAmount += $betAmount;
                    if (!isset($dateWise[$day])) {
                        $dateWise[$day] = [
                            'date' => $day,
                            'betAmount' => 0.0,
                            'betCount' => 0,
                            'betWinLossAmount' => 0.0,
                        ];
                    }
                    $dateWise[$day]['betAmount'] += $betAmount;
                    $dateWise[$day]['betCount']++;
                    $dateWise[$day]['betWinLossAmount'] += $winLoss;
                }
            } else {
                error_log('Game statistics sync query failed: ' . $conn->error);
            }
        }

        foreach ($dateWise as &$daily) {
            $daily['betAmount'] = round((float)$daily['betAmount'], 4);
            $daily['betWinLossAmount'] = round((float)$daily['betWinLossAmount'], 4);
        }
        unset($daily);

        echo json_encode([
            'data' => [
                'gameStatis' => $gameStatis,
                'sumBetAmount' => round($sumBetAmount, 4),
                'totalBet' => round($sumBetAmount, 4),
                'totalBetCount' => count($gameStatis),
                'dateWise' => array_values($dateWise),
            ],
            'code' => 0,
            'msg' => 'Succeed',
            'msgCode' => 0,
            'serviceNowTime' => $serviceNow,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
