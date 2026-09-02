<?php
/**
 * Transaction History -> Bet synchronizer.
 * Keeps the legacy bet tables and SaaS lottery bets in one paginated feed.
 */

if (!function_exists('daman_tx_table_exists')) {
    function daman_tx_table_exists(mysqli $conn, string $table): bool
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

if (!function_exists('send_bet_transactions_with_saas_if_requested')) {
    function send_bet_transactions_with_saas_if_requested(
        mysqli $conn,
        int $userId,
        int $type,
        int $pageNo,
        int $pageSize,
        string $date,
        string $serviceNow
    ): void {
        if ($type !== 0) {
            return;
        }

        $pageNo = max(1, $pageNo);
        $pageSize = max(1, min(100, $pageSize));
        $offset = ($pageNo - 1) * $pageSize;
        $date = trim($date);
        $dateSql = $date !== '' ? $conn->real_escape_string(substr($date, 0, 10)) : '';

        $legacyTables = [
            'bajikattuttate',
            'bajikattuttate_drei',
            'bajikattuttate_funf',
            'bajikattuttate_zehn',
            'bajikattuttate_aidudi',
            'bajikattuttate_aidudi_drei',
            'bajikattuttate_aidudi_funf',
            'bajikattuttate_aidudi_zehn',
            'bajikattuttate_kemuru',
            'bajikattuttate_kemuru_drei',
            'bajikattuttate_kemuru_funf',
            'bajikattuttate_kemuru_zehn',
            'bajikattuttate_trx',
            'bajikattuttate_trx3',
            'bajikattuttate_trx5',
            'bajikattuttate_trx10',
        ];

        $parts = [];
        foreach ($legacyTables as $table) {
            if (!daman_tx_table_exists($conn, $table)) {
                continue;
            }
            $whereDate = $dateSql !== '' ? " AND DATE(tiarikala) = '{$dateSql}'" : '';
            $parts[] = "SELECT CAST(parichaya AS CHAR) AS order_num, CAST(ketebida AS DECIMAL(18,4)) AS amount, tiarikala AS add_time, '' AS remark FROM `{$table}` WHERE byabaharkarta = {$userId}{$whereDate}";
        }

        if (daman_tx_table_exists($conn, 'saas_lottery_bets')) {
            $whereDate = $dateSql !== '' ? " AND DATE(created_at) = '{$dateSql}'" : '';
            $parts[] = "SELECT CONCAT('SL', id) AS order_num, CAST(stake AS DECIMAL(18,4)) AS amount, created_at AS add_time, CONCAT(game_code, ' / ', issue_number) AS remark FROM saas_lottery_bets WHERE user_id = {$userId}{$whereDate}";
        }

        if (!$parts) {
            echo json_encode([
                'data' => [
                    'list' => [],
                    'pageNo' => $pageNo,
                    'totalPage' => 0,
                    'totalCount' => 0,
                ],
                'code' => 0,
                'msg' => 'Succeed',
                'msgCode' => 0,
                'serviceNowTime' => $serviceNow,
            ]);
            exit;
        }

        $union = implode(' UNION ALL ', $parts);
        $countResult = $conn->query("SELECT COUNT(*) AS total_count FROM ({$union}) AS tx_count");
        if (!$countResult) {
            error_log('Bet transaction count failed: ' . $conn->error);
            return; // Fall back to the legacy endpoint logic instead of breaking the page.
        }
        $countRow = $countResult->fetch_assoc();
        $totalCount = (int)($countRow['total_count'] ?? 0);

        $list = [];
        if ($totalCount > 0) {
            $dataResult = $conn->query("SELECT order_num, amount, add_time, remark FROM ({$union}) AS tx_data ORDER BY add_time DESC LIMIT {$pageSize} OFFSET {$offset}");
            if (!$dataResult) {
                error_log('Bet transaction data failed: ' . $conn->error);
                return;
            }
            while ($row = $dataResult->fetch_assoc()) {
                $list[] = [
                    'amount' => (float)$row['amount'],
                    'type' => 0,
                    'typeName' => 'Bet amount reduced',
                    'typeNameCode' => '8000',
                    'orderNum' => (string)$row['order_num'],
                    'addTime' => (string)$row['add_time'],
                    'remark' => (string)$row['remark'],
                ];
            }
        }

        echo json_encode([
            'data' => [
                'list' => $list,
                'pageNo' => $pageNo,
                'totalPage' => $totalCount > 0 ? (int)ceil($totalCount / $pageSize) : 0,
                'totalCount' => $totalCount,
            ],
            'code' => 0,
            'msg' => 'Succeed',
            'msgCode' => 0,
            'serviceNowTime' => $serviceNow,
        ]);
        exit;
    }
}
