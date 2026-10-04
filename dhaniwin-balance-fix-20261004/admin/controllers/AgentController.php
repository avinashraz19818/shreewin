<?php
declare(strict_types=1);

class AgentController
{
    public static function getAgentSummary(int $userId): array
    {
        $pdo = api_pdo();
        if (!$pdo) {
            return [];
        }

        try {
            // Count team sizes at each level L1-L6
            $team = [1 => [], 2 => [], 3 => [], 4 => [], 5 => [], 6 => []];
            
            // Level 1
            $stmt = $pdo->prepare("SELECT user_id, nickname, username, wallet_balance, game_balance, created_at FROM api_users WHERE referrer_id = ?");
            $stmt->execute([$userId]);
            $level1 = $stmt->fetchAll();
            foreach ($level1 as $u) {
                $team[1][] = $u;
            }

            // Levels 2 to 6
            for ($level = 2; $level <= 6; $level++) {
                $prevUsers = array_column($team[$level - 1], 'user_id');
                if (empty($prevUsers)) {
                    break;
                }
                
                $placeholders = implode(',', array_fill(0, count($prevUsers), '?'));
                $stmt = $pdo->prepare("SELECT user_id, nickname, username, wallet_balance, game_balance, created_at, referrer_id FROM api_users WHERE referrer_id IN ($placeholders)");
                $stmt->execute($prevUsers);
                $rows = $stmt->fetchAll();
                foreach ($rows as $u) {
                    $team[$level][] = $u;
                }
            }

            $totalTeamSize = 0;
            $levelsCount = [];
            foreach ($team as $level => $users) {
                $count = count($users);
                $levelsCount["L$level"] = $count;
                $totalTeamSize += $count;
            }

            // Sum total commissions received
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(commission_amount), 0) FROM agent_commissions WHERE user_id = ?");
            $stmt->execute([$userId]);
            $totalComm = (float)$stmt->fetchColumn();

            // Sum today's commission
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(commission_amount), 0) FROM agent_commissions WHERE user_id = ? AND (created_at >= DATE('now', 'start of day') OR created_at >= CURDATE())");
            $stmt->execute([$userId]);
            $todayComm = (float)$stmt->fetchColumn();

            return [
                'user_id' => $userId,
                'total_team' => $totalTeamSize,
                'levels_count' => $levelsCount,
                'total_commission' => $totalComm,
                'today_commission' => $todayComm,
                'team_details' => $team
            ];
        } catch (Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public static function processBetCommissions(string $orderNo): void
    {
        $pdo = api_pdo();
        if (!$pdo || !api_setting_bool('agent_rebate_enabled', true)) {
            return;
        }

        try {
            // Fetch bet
            $stmt = $pdo->prepare("SELECT * FROM lottery_bets WHERE order_no = ? LIMIT 1");
            $stmt->execute([$orderNo]);
            $bet = $stmt->fetch();
            if (!$bet) return;

            $userId = (int)$bet['user_id'];
            $stake = (float)$bet['stake_amount'];

            // Fetch user info to start referrer chain
            $stmt = $pdo->prepare("SELECT referrer_id FROM api_users WHERE user_id = ? LIMIT 1");
            $stmt->execute([$userId]);
            $referrerId = $stmt->fetchColumn();
            if (!$referrerId) return;

            // Commission ratios L1 to L6
            // e.g. L1: 1.0%, L2: 0.5%, L3: 0.25%, L4: 0.12%, L5: 0.06%, L6: 0.03%
            // Can be configured via settings
            $commissionRates = [
                1 => api_setting_float('agent_rate_l1', 1.0) / 100,
                2 => api_setting_float('agent_rate_l2', 0.5) / 100,
                3 => api_setting_float('agent_rate_l3', 0.25) / 100,
                4 => api_setting_float('agent_rate_l4', 0.12) / 100,
                5 => api_setting_float('agent_rate_l5', 0.06) / 100,
                6 => api_setting_float('agent_rate_l6', 0.03) / 100,
            ];

            $currentRef = (int)$referrerId;
            $pdo->beginTransaction();

            for ($level = 1; $level <= 6; $level++) {
                if ($currentRef <= 0) break;

                // Check rate
                $rate = $commissionRates[$level] ?? 0.0;
                if ($rate <= 0) continue;

                $commAmount = round($stake * $rate, 4);
                if ($commAmount > 0) {
                    // Update agent's wallet
                    $stmt = $pdo->prepare("UPDATE api_users SET wallet_balance = wallet_balance + ?, game_balance = game_balance + ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
                    $stmt->execute([$commAmount, $commAmount, $currentRef]);

                    // Insert commission log
                    $stmt = $pdo->prepare("INSERT INTO agent_commissions (user_id, from_user_id, bet_order_no, commission_level, bet_amount, commission_amount) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$currentRef, $userId, $orderNo, $level, $stake, $commAmount]);
                }

                // Get next referrer
                $stmt = $pdo->prepare("SELECT referrer_id FROM api_users WHERE user_id = ? LIMIT 1");
                $stmt->execute([$currentRef]);
                $nextRef = $stmt->fetchColumn();
                $currentRef = $nextRef ? (int)$nextRef : 0;
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        }
    }

    public static function listCommissions(int $limit = 50): array
    {
        $pdo = api_pdo();
        if (!$pdo) return [];
        try {
            $stmt = $pdo->prepare("
                SELECT c.*, u.nickname AS agent_name, f.nickname AS player_name 
                FROM agent_commissions c
                LEFT JOIN api_users u ON u.user_id = c.user_id
                LEFT JOIN api_users f ON f.user_id = c.from_user_id
                ORDER BY c.id DESC LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll() ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }
}
