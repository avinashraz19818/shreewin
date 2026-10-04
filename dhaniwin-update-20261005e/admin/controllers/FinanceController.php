<?php
declare(strict_types=1);

class FinanceController
{
    public static function listRecharges(array $params): array
    {
        $pdo = api_pdo();
        if (!$pdo) {
            return ['draw' => 0, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []];
        }

        $draw = (int)($params['draw'] ?? 1);
        $start = (int)($params['start'] ?? 0);
        $length = (int)($params['length'] ?? 10);
        $search = trim((string)($params['search']['value'] ?? ''));
        $statusFilter = trim((string)($params['status'] ?? ''));

        try {
            $conditions = [];
            $args = [];
            
            if ($search !== "") {
                $conditions[] = "(r.order_no LIKE ? OR r.utr LIKE ? OR u.username LIKE ? OR u.phone LIKE ?)";
                $searchWild = "%$search%";
                $args = array_merge($args, [$searchWild, $searchWild, $searchWild, $searchWild]);
            }
            
            if ($statusFilter !== "") {
                $conditions[] = "r.status = ?";
                $args[] = $statusFilter;
            }

            $whereClause = !empty($conditions) ? " WHERE " . implode(" AND ", $conditions) : "";

            $totalQuery = "SELECT COUNT(*) FROM recharge_orders";
            $totalRecords = (int)$pdo->query($totalQuery)->fetchColumn();

            $filteredQuery = "SELECT COUNT(*) FROM recharge_orders r LEFT JOIN api_users u ON u.id = r.user_id" . $whereClause;
            $stmt = $pdo->prepare($filteredQuery);
            $stmt->execute($args);
            $filteredRecords = (int)$stmt->fetchColumn();

            // Fetch records
            $dataQuery = "
                SELECT r.*, u.username, u.nickname, u.phone AS user_phone, u.user_id AS player_id
                FROM recharge_orders r 
                LEFT JOIN api_users u ON u.id = r.user_id 
                " . $whereClause . " 
                ORDER BY r.id DESC 
                LIMIT $length OFFSET $start
            ";
            $stmt = $pdo->prepare($dataQuery);
            $stmt->execute($args);
            $rows = $stmt->fetchAll();

            $data = [];
            foreach ($rows as $row) {
                $data[] = [
                    'id' => (int)$row['id'],
                    'order_no' => $row['order_no'],
                    'player_id' => (int)$row['player_id'],
                    'username' => htmlspecialchars((string)($row['username'] ?? 'Anonymous')),
                    'nickname' => htmlspecialchars((string)($row['nickname'] ?? 'Member')),
                    'amount' => (float)$row['amount'],
                    'payment_type' => (string)($row['payment_type'] ?? ($row['method_name'] ?? 'UPI')),
                    'method_name' => $row['method_name'] ?? 'UPI Gateway',
                    'status' => $row['status'],
                    'utr' => htmlspecialchars((string)($row['utr'] ?? '')),
                    'screenshot_url' => (string)($row['screenshot_url'] ?? ''),
                    'bonus_amount' => (float)($row['bonus_amount'] ?? 0),
                    'remarks' => htmlspecialchars((string)($row['remarks'] ?? '')),
                    'created_at' => $row['created_at']
                ];
            }

            return [
                'draw' => $draw,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data
            ];
        } catch (Throwable $e) {
            return ['error' => $e->getMessage(), 'data' => []];
        }
    }

    public static function listWithdrawals(array $params): array
    {
        $pdo = api_pdo();
        if (!$pdo) {
            return ['draw' => 0, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []];
        }

        $draw = (int)($params['draw'] ?? 1);
        $start = (int)($params['start'] ?? 0);
        $length = (int)($params['length'] ?? 10);
        $search = trim((string)($params['search']['value'] ?? ''));
        $statusFilter = trim((string)($params['status'] ?? ''));

        try {
            $conditions = [];
            $args = [];
            
            if ($search !== "") {
                $conditions[] = "(w.order_no LIKE ? OR u.username LIKE ? OR u.phone LIKE ?)";
                $searchWild = "%$search%";
                $args = array_merge($args, [$searchWild, $searchWild, $searchWild]);
            }
            
            if ($statusFilter !== "") {
                $conditions[] = "w.status = ?";
                $args[] = $statusFilter;
            }

            $typeFilter = trim((string)($params['withdraw_type'] ?? ''));
            if ($typeFilter !== "") {
                $conditions[] = "w.withdraw_type = ?";
                $args[] = $typeFilter;
            }

            $whereClause = !empty($conditions) ? " WHERE " . implode(" AND ", $conditions) : "";

            $totalQuery = "SELECT COUNT(*) FROM withdraw_orders";
            $totalRecords = (int)$pdo->query($totalQuery)->fetchColumn();

            $filteredQuery = "SELECT COUNT(*) FROM withdraw_orders w LEFT JOIN api_users u ON u.id = w.user_id" . $whereClause;
            $stmt = $pdo->prepare($filteredQuery);
            $stmt->execute($args);
            $filteredRecords = (int)$stmt->fetchColumn();

            // Fetch records
            $dataQuery = "
                SELECT w.*, u.username, u.nickname, u.phone AS user_phone, u.user_id AS player_id
                FROM withdraw_orders w 
                LEFT JOIN api_users u ON u.id = w.user_id 
                " . $whereClause . " 
                ORDER BY w.id DESC 
                LIMIT $length OFFSET $start
            ";
            $stmt = $pdo->prepare($dataQuery);
            $stmt->execute($args);
            $rows = $stmt->fetchAll();

            $data = [];
            foreach ($rows as $row) {
                $data[] = [
                    'id' => (int)$row['id'],
                    'order_no' => $row['order_no'],
                    'player_id' => (int)$row['player_id'],
                    'username' => htmlspecialchars((string)($row['username'] ?? 'Anonymous')),
                    'nickname' => htmlspecialchars((string)($row['nickname'] ?? 'Member')),
                    'amount' => (float)$row['amount'],
                    'payment_type' => (string)($row['withdraw_type'] ?? $row['payment_type'] ?? 'UPI'),
                    'withdraw_type' => (string)($row['withdraw_type'] ?? 'UPI'),
                    'status' => $row['status'],
                    'account_json' => $row['account_json'],
                    'remarks' => htmlspecialchars((string)($row['remarks'] ?? '')),
                    'processed_at' => (string)($row['processed_at'] ?? ''),
                    'created_at' => $row['created_at']
                ];
            }

            return [
                'draw' => $draw,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data
            ];
        } catch (Throwable $e) {
            return ['error' => $e->getMessage(), 'data' => []];
        }
    }

    public static function updateRechargeStatus(int $id, string $status, string $remarks = ''): array
    {
        $pdo = api_pdo();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database not available'];
        }

        try {
            $stmt = $pdo->prepare("SELECT * FROM recharge_orders WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $order = $stmt->fetch();

            if (!$order) {
                return ['success' => false, 'message' => 'Order not found'];
            }

            $oldStatus = $order['status'];
            if (self::isFinalStatus($oldStatus)) {
                return ['success' => false, 'message' => 'Order already has a final status: ' . $oldStatus];
            }

            // "1"/"apprved"/"Approved" - whatever the caller sent, the money
            // must move. A status the code did not recognise used to mark the
            // order approved WITHOUT crediting the player.
            $status = self::normalizeStatus($status);

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("UPDATE recharge_orders SET status = ?, remarks = COALESCE(NULLIF(?, ''), remarks), updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$status, $remarks, $id]);

            // If approved, add balance to user
            if (in_array(strtolower($status), ['approved', 'success', 'completed', 'paid'], true)) {
                $bonus = 0.0;
                if (api_setting_bool('first_recharge_bonus_enabled', true)) {
                    // Check if it's their first approved recharge
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM recharge_orders WHERE user_id = ? AND id <> ? AND LOWER(status) IN ('approved','success','completed','complete','paid')");
                    $stmt->execute([$order['user_id'], $id]);
                    $priorApproved = (int)$stmt->fetchColumn();
                    if ($priorApproved === 0) {
                        $bonus = min(
                            (float)$order['amount'] * api_setting_float('first_recharge_bonus_percent', 10.0) / 100,
                            api_setting_float('first_recharge_bonus_max', 500.0)
                        );
                    }
                }

                $totalAdd = (float)$order['amount'] + $bonus;
                if (session_status() === PHP_SESSION_NONE) {
                    @session_start();
                }
                try {
                    $pdo->prepare("UPDATE recharge_orders SET bonus_amount = ?, processed_by = ?, processed_at = CURRENT_TIMESTAMP WHERE id = ?")
                        ->execute([$bonus, (int)($_SESSION['admin_id'] ?? 0), $id]);
                } catch (Throwable $e) {
                }

                // Credit the user. api_wallet_apply_change mirrors the amount into
                // both balance columns (site wallet + game) and logs best-effort.
                // If the credit cannot be applied the order must NOT end up
                // approved - otherwise the player's deposit is silently lost.
                $credit = api_wallet_apply_change(
                    (int) $order['user_id'],
                    $totalAdd,
                    'wallet',
                    "Recharge deposit approved (Order: " . $order['order_no'] . ($bonus > 0 ? " including first deposit bonus " . $bonus : "") . ")"
                );
                if (empty($credit['success'])) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    return ['success' => false, 'message' => 'Could not credit the player, order not approved: ' . ($credit['message'] ?? 'unknown error')];
                }
            }

            $pdo->commit();
            return ['success' => true, 'message' => 'Recharge status updated to ' . $status];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public static function updateWithdrawalStatus(int $id, string $status, string $remarks = ''): array
    {
        $pdo = api_pdo();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database not available'];
        }

        try {
            $stmt = $pdo->prepare("SELECT * FROM withdraw_orders WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $order = $stmt->fetch();

            if (!$order) {
                return ['success' => false, 'message' => 'Order not found'];
            }

            $oldStatus = $order['status'];
            if (self::isFinalStatus($oldStatus)) {
                return ['success' => false, 'message' => 'Order already has a final status: ' . $oldStatus];
            }

            // Same as deposits: normalise first so a rejected withdrawal always
            // refunds the player, whatever spelling the caller used.
            $status = self::normalizeStatus($status);

            $pdo->beginTransaction();

            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $processedBy = (int)($_SESSION['admin_id'] ?? 0);
            $stmt = $pdo->prepare("UPDATE withdraw_orders SET status = ?, remarks = ?, processed_by = ?, processed_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$status, $remarks, $processedBy, $id]);

            // If rejected, refund the user's wallet balance
            if (in_array(strtolower($status), ['rejected', 'cancelled', 'canceled', 'failed'], true)) {
                $amount = (float)$order['amount'];

                // Refund through the shared helper so both balance columns move.
                $refund = api_wallet_apply_change(
                    (int) $order['user_id'],
                    $amount,
                    'wallet',
                    "Withdrawal order rejected refund (Order: " . $order['order_no'] . "). Reason: " . $remarks
                );
                if (empty($refund['success'])) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    return ['success' => false, 'message' => 'Could not refund the player, order left pending: ' . ($refund['message'] ?? 'unknown error')];
                }
            }

            $pdo->commit();
            return ['success' => true, 'message' => 'Withdrawal status updated to ' . $status];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public static function bulkApproveRecharges(array $ids): array
    {
        $success = 0;
        $failed = 0;
        foreach ($ids as $id) {
            $res = self::updateRechargeStatus((int)$id, 'Approved');
            if ($res['success']) {
                $success++;
            } else {
                $failed++;
            }
        }
        return ['success' => true, 'message' => "Bulk process complete: $success approved, $failed failed"];
    }

    public static function bulkRejectRecharges(array $ids, string $reason = ''): array
    {
        $success = 0;
        $failed = 0;
        foreach ($ids as $id) {
            $res = self::updateRechargeStatus((int)$id, 'Rejected', $reason);
            if ($res['success']) {
                $success++;
            } else {
                $failed++;
            }
        }
        return ['success' => true, 'message' => "Bulk process complete: $success rejected, $failed failed"];
    }

    /** Accept the status in any shape the panel (or a script) may send. */
    private static function normalizeStatus(string $status): string
    {
        $key = strtolower(trim($status));
        $map = [
            '' => 'Pending',
            '0' => 'Pending',
            '1' => 'Approved',
            '2' => 'Rejected',
            '3' => 'Cancelled',
            'pendingreview' => 'Pending',
            'review' => 'Pending',
            'approve' => 'Approved',
            'approved' => 'Approved',
            'accept' => 'Approved',
            'accepted' => 'Approved',
            'reject' => 'Rejected',
            'rejected' => 'Rejected',
            'cancel' => 'Cancelled',
            'cancelled' => 'Cancelled',
            'canceled' => 'Cancelled',
            'fail' => 'Failed',
            'failed' => 'Failed',
            'success' => 'Approved',
            'completed' => 'Approved',
            'complete' => 'Approved',
            'paid' => 'Approved',
        ];
        return $map[$key] ?? ucfirst($key);
    }

    private static function isFinalStatus(string $status): bool
    {
        $finalStates = ['approved', 'success', 'completed', 'complete', 'paid', 'rejected', 'cancelled', 'canceled', 'failed'];
        return in_array(strtolower(self::normalizeStatus($status)), $finalStates, true);
    }
}
