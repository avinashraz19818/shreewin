<?php
/**
 * Atomic manual withdrawal action used by all admin withdrawal queues.
 * State: pending=0, completed=1, rejected=2, processing=3.
 * A rejected request is refunded exactly once.
 */
function admin_manual_withdraw_action(mysqli $conn, int $withdrawId, string $action, string $remark = ''): array
{
    if ($withdrawId < 1 || !in_array($action, ['accept', 'reject', 'processing'], true)) {
        return [false, 'Invalid withdrawal request'];
    }

    try {
        $conn->begin_transaction();
        $stmt = $conn->prepare('SELECT balakedara,motta,sthiti FROM hintegedukolli WHERE shonu=? FOR UPDATE');
        if (!$stmt) {
            throw new RuntimeException('Withdrawal lookup prepare failed');
        }
        $stmt->bind_param('i', $withdrawId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            $conn->rollback();
            return [false, 'Withdrawal request not found'];
        }

        $currentState = (int)$row['sthiti'];
        if ($action === 'processing') {
            if ($currentState !== 0) {
                $conn->rollback();
                return [false, 'Only a pending withdrawal can be moved to processing'];
            }
        } elseif (!in_array($currentState, [0, 3], true)) {
            $conn->rollback();
            return [false, 'Withdrawal request is already processed'];
        }

        $uid = (int)$row['balakedara'];
        $amount = round((float)$row['motta'], 2);
        if ($uid < 1 || $amount <= 0) {
            throw new RuntimeException('Invalid withdrawal row');
        }

        if ($action === 'reject') {
            $wallet = $conn->prepare('SELECT motta FROM shonu_kaichila WHERE balakedara=? FOR UPDATE');
            if (!$wallet) {
                throw new RuntimeException('Wallet lookup prepare failed');
            }
            $wallet->bind_param('i', $uid);
            $wallet->execute();
            $walletRow = $wallet->get_result()->fetch_assoc();
            $wallet->close();
            if (!$walletRow) {
                throw new RuntimeException('Wallet row not found');
            }

            $refund = $conn->prepare('UPDATE shonu_kaichila SET motta=ROUND(motta+?,2) WHERE balakedara=?');
            if (!$refund) {
                throw new RuntimeException('Refund prepare failed');
            }
            $refund->bind_param('di', $amount, $uid);
            $refund->execute();
            if ($refund->affected_rows < 1) {
                $refund->close();
                throw new RuntimeException('Withdrawal refund failed');
            }
            $refund->close();

            $newState = 2;
            $ticket = 'Rejected';
            $message = 'Withdrawal request rejected and amount returned to wallet.';
        } elseif ($action === 'processing') {
            $newState = 3;
            $ticket = 'Processing';
            $message = 'Withdrawal request moved to processing.';
        } else {
            $newState = 1;
            $ticket = 'Completed';
            $message = 'Withdrawal request accepted successfully!';
        }

        $update = $conn->prepare('UPDATE hintegedukolli SET sthiti=?,tike=?,remarks=?,updated_at=NOW() WHERE shonu=? AND sthiti=?');
        if (!$update) {
            throw new RuntimeException('Withdrawal status prepare failed');
        }
        $update->bind_param('issii', $newState, $ticket, $remark, $withdrawId, $currentState);
        $update->execute();
        if ($update->affected_rows !== 1) {
            $update->close();
            throw new RuntimeException('Withdrawal status update failed');
        }
        $update->close();

        $conn->commit();
        return [true, $message];
    } catch (Throwable $e) {
        try { $conn->rollback(); } catch (Throwable $ignored) {}
        error_log('[manual-withdraw] ' . $e->getMessage());
        return [false, 'Error updating withdrawal request!'];
    }
}
