<?php
declare(strict_types=1);

class DashboardController
{
    public static function getStats(): array
    {
        $pdo = api_pdo();
        if (!$pdo) {
            return [];
        }

        try {
            // "Today" has to be spelled per driver. The old query mixed both
            // dialects in one string (DATE('now','start of day') OR CURDATE()),
            // so it failed on MySQL AND on SQLite and the dashboard showed the
            // error text instead of the tiles.
            $driver = api_db_driver($pdo);
            $todayStart = $driver === 'mysql'
                ? "created_at >= CURDATE() AND created_at < DATE_ADD(CURDATE(), INTERVAL 1 DAY)"
                : "date(created_at) = date('now')";

            $totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM api_users")->fetchColumn();
            $todayUsers = (int)$pdo->query("SELECT COUNT(*) FROM api_users WHERE " . $todayStart)->fetchColumn();
            
            $userWallet = (float)$pdo->query("SELECT COALESCE(SUM(wallet_balance), 0) FROM api_users")->fetchColumn();
            
            // Recharge totals
            $totalRecharge = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM recharge_orders WHERE LOWER(status) IN ('approved','success','completed','complete','paid')")->fetchColumn();
            $todayRecharge = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM recharge_orders WHERE LOWER(status) IN ('approved','success','completed','complete','paid') AND " . $todayStart)->fetchColumn();
            $pendingRecharge = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM recharge_orders WHERE LOWER(status) IN ('pending', 'pendingreview')")->fetchColumn();
            $pendingRechargeCount = (int)$pdo->query("SELECT COUNT(*) FROM recharge_orders WHERE LOWER(status) IN ('pending', 'pendingreview')")->fetchColumn();
            
            // Withdraw totals
            $totalWithdraw = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM withdraw_orders WHERE LOWER(status) IN ('approved','success','completed','complete','paid')")->fetchColumn();
            $todayWithdraw = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM withdraw_orders WHERE LOWER(status) IN ('approved','success','completed','complete','paid') AND " . $todayStart)->fetchColumn();
            
            $todayProfit = $todayRecharge - $todayWithdraw;

            return [
                'users' => ['total' => $totalUsers, 'today' => $todayUsers],
                'user_wallet' => ['total' => $userWallet],
                'recharge' => ['total' => $totalRecharge, 'today' => $todayRecharge, 'pending' => $pendingRecharge, 'pending_count' => $pendingRechargeCount],
                'withdraw' => ['total' => $totalWithdraw, 'today' => $todayWithdraw],
                'profit' => ['today' => $todayProfit]
            ];
        } catch (Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public static function getChartsData(int $days = 7): array
    {
        $pdo = api_pdo();
        if (!$pdo) {
            return [];
        }

        $driver = api_db_driver($pdo);
        $labels = [];
        $recharges = [];
        $withdrawals = [];
        $registrations = [];
        $betVolumes = [];

        try {
            // Generate last N days labels
            for ($i = $days - 1; $i >= 0; $i--) {
                $labels[] = date('Y-m-d', strtotime("-$i days"));
            }

            foreach ($labels as $date) {
                // Registrations
                if ($driver === 'mysql') {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM api_users WHERE DATE(created_at) = ?");
                } else {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM api_users WHERE strftime('%Y-%m-%d', created_at) = ?");
                }
                $stmt->execute([$date]);
                $registrations[] = (int)$stmt->fetchColumn();

                // Recharges
                if ($driver === 'mysql') {
                    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM recharge_orders WHERE LOWER(status) IN ('approved','success','completed','complete','paid') AND DATE(created_at) = ?");
                } else {
                    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM recharge_orders WHERE LOWER(status) IN ('approved','success','completed','complete','paid') AND strftime('%Y-%m-%d', created_at) = ?");
                }
                $stmt->execute([$date]);
                $recharges[] = (float)$stmt->fetchColumn();

                // Withdrawals
                if ($driver === 'mysql') {
                    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM withdraw_orders WHERE LOWER(status) IN ('approved','success','completed','complete','paid') AND DATE(created_at) = ?");
                } else {
                    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM withdraw_orders WHERE LOWER(status) IN ('approved','success','completed','complete','paid') AND strftime('%Y-%m-%d', created_at) = ?");
                }
                $stmt->execute([$date]);
                $withdrawals[] = (float)$stmt->fetchColumn();

                // Bet Volume
                if ($driver === 'mysql') {
                    $stmt = $pdo->prepare("SELECT COALESCE(SUM(stake_amount), 0) FROM lottery_bets WHERE DATE(created_at) = ?");
                } else {
                    $stmt = $pdo->prepare("SELECT COALESCE(SUM(stake_amount), 0) FROM lottery_bets WHERE strftime('%Y-%m-%d', created_at) = ?");
                }
                $stmt->execute([$date]);
                $betVolumes[] = (float)$stmt->fetchColumn();
            }

            return [
                'labels' => $labels,
                'registrations' => $registrations,
                'recharges' => $recharges,
                'withdrawals' => $withdrawals,
                'bet_volumes' => $betVolumes
            ];
        } catch (Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
