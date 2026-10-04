<?php
declare(strict_types=1);

class UserController
{
    public static function listUsers(array $params): array
    {
        $pdo = api_pdo();
        if (!$pdo) {
            return ['draw' => 0, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []];
        }

        $draw = (int)($params['draw'] ?? 1);
        $start = (int)($params['start'] ?? 0);
        $length = (int)($params['length'] ?? 10);
        $search = trim((string)($params['search']['value'] ?? ''));

        try {
            $whereClause = "";
            $args = [];
            if ($search !== "") {
                $whereClause = " WHERE username LIKE ? OR nickname LIKE ? OR phone LIKE ? OR CAST(user_id AS CHAR) LIKE ? ";
                $searchWild = "%$search%";
                $args = [$searchWild, $searchWild, $searchWild, $searchWild];
            }

            $totalQuery = "SELECT COUNT(*) FROM api_users";
            $totalRecords = (int)$pdo->query($totalQuery)->fetchColumn();

            $filteredQuery = "SELECT COUNT(*) FROM api_users" . $whereClause;
            $stmt = $pdo->prepare($filteredQuery);
            $stmt->execute($args);
            $filteredRecords = (int)$stmt->fetchColumn();

            // Fetch records
            $dataQuery = "
                SELECT u.*, uc.win_rate_percent, uc.status AS control_status 
                FROM api_users u 
                LEFT JOIN user_control uc ON uc.user_id = u.user_id 
                " . $whereClause . " 
                ORDER BY u.id DESC 
                LIMIT $length OFFSET $start
            ";
            $stmt = $pdo->prepare($dataQuery);
            $stmt->execute($args);
            $rows = $stmt->fetchAll();

            $data = [];
            foreach ($rows as $row) {
                $data[] = [
                    'id' => (int)$row['id'],
                    'user_id' => (int)$row['user_id'],
                    'username' => htmlspecialchars((string)$row['username']),
                    'nickname' => htmlspecialchars((string)$row['nickname']),
                    'phone' => htmlspecialchars((string)($row['phone'] ?? '')),
                    'wallet_balance' => (float)$row['wallet_balance'],
                    'game_balance' => (float)$row['game_balance'],
                    'can_bet' => (int)$row['can_bet'],
                    'status' => (int)($row['status'] ?? 1),
                    'win_rate_percent' => $row['win_rate_percent'] !== null ? (int)$row['win_rate_percent'] : 50,
                    'control_status' => $row['control_status'] !== null ? (int)$row['control_status'] : 0,
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

    public static function saveUser(array $post): array
    {
        $pdo = api_pdo();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database not available'];
        }

        $id = (int)($post['id'] ?? 0);
        $userId = (int)($post['user_id'] ?? 0);
        $username = trim((string)($post['username'] ?? ''));
        $nickname = trim((string)($post['nickname'] ?? ''));
        $phone = trim((string)($post['phone'] ?? ''));
        $password = trim((string)($post['password'] ?? ''));
        $status = isset($post['status']) ? (int)$post['status'] : 1;
        $canBet = isset($post['can_bet']) ? (int)$post['can_bet'] : 1;
        $banReason = trim((string)($post['ban_reason'] ?? ''));
        $isDemo = isset($post['is_demo']) ? (int)$post['is_demo'] : 0;
        $isAgent = isset($post['is_agent']) ? (int)$post['is_agent'] : 0;
        $agentRate = isset($post['agent_rate']) ? (float)$post['agent_rate'] : 0.0;

        if ($id <= 0 && $username === '') {
            return ['success' => false, 'message' => 'Username is required'];
        }

        try {
            if ($id > 0) {
                // Partial edit: switches in the user table (Can Bet / Ban) send
                // only what they change, so untouched fields stay as they are.
                $stmt = $pdo->prepare("SELECT * FROM api_users WHERE id = ? LIMIT 1");
                $stmt->execute([$id]);
                $current = $stmt->fetch();
                if (!$current) {
                    return ['success' => false, 'message' => 'User not found'];
                }

                $fields = [];
                $args = [];
                if ($username !== '') {
                    $fields[] = 'username = ?';
                    $args[] = $username;
                }
                if (array_key_exists('nickname', $post)) {
                    $fields[] = 'nickname = ?';
                    $args[] = $nickname;
                }
                if (array_key_exists('phone', $post)) {
                    $fields[] = 'phone = ?';
                    $args[] = $phone;
                }
                if ($password !== '') {
                    $fields[] = 'password = ?';
                    $args[] = $password;
                }
                if (array_key_exists('status', $post)) {
                    $fields[] = 'status = ?';
                    $args[] = $status;
                }
                if (array_key_exists('can_bet', $post)) {
                    $fields[] = 'can_bet = ?';
                    $args[] = $canBet;
                }
                if (array_key_exists('ban_reason', $post)) {
                    $fields[] = 'ban_reason = ?';
                    $args[] = $banReason;
                }
                if (array_key_exists('agent_rate', $post)) {
                    $fields[] = 'agent_rate = ?';
                    $args[] = $agentRate;
                }
                if (array_key_exists('is_agent', $post)) {
                    $fields[] = 'is_agent = ?';
                    $args[] = $isAgent;
                }

                if (!empty($fields)) {
                    $args[] = $id;
                    $stmt = $pdo->prepare("UPDATE api_users SET " . implode(', ', $fields) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                    $stmt->execute($args);
                }

                // If the panel also sends a target balance, move the difference
                // through the shared helper so both columns stay in sync.
                if (isset($post['wallet_balance']) && $post['wallet_balance'] !== '') {
                    $delta = (float)$post['wallet_balance'] - api_wallet_balance_of($current);
                    if (abs($delta) > 0.0000001) {
                        $applied = api_wallet_apply_change($id, $delta, 'wallet', 'Balance corrected from admin panel');
                        if (empty($applied['success'])) {
                            return $applied;
                        }
                    }
                }

                return ['success' => true, 'message' => 'User updated successfully'];
            }

            // --- Create a new player -----------------------------------------
            if ($userId <= 0) {
                $userId = mt_rand(100000, 999999);
            }
            if ($nickname === '') {
                $nickname = 'Member' . strtoupper(substr(md5((string)$userId), 0, 8));
            }
            if ($password === '') {
                $password = 'admin123';
            }

            $stmt = $pdo->prepare("SELECT id FROM api_users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Username already exists'];
            }

            $stmt = $pdo->prepare("INSERT INTO api_users (user_id, username, nickname, phone, wallet_balance, game_balance, can_bet, password, status, is_demo, is_agent, agent_rate) VALUES (?, ?, ?, ?, 0.0, 0.0, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $username, $nickname, $phone, $canBet, $password, $status, $isDemo, $isAgent, $agentRate]);
            $newRowId = (int)$pdo->lastInsertId();

            // Optional starting balance (used by the demo-user creator)
            $startBalance = isset($post['start_balance']) ? (float)$post['start_balance'] : 0.0;
            if ($startBalance > 0 && $newRowId > 0) {
                api_wallet_apply_change($newRowId, $startBalance, 'wallet', $isDemo ? 'Demo user started with demo balance' : 'Starting balance from admin panel');
            }

            return ['success' => true, 'message' => 'User created successfully', 'id' => $newRowId, 'user_id' => $userId];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /** Users created from the panel as demo/test accounts. */
    public static function listDemoUsers(array $params = []): array
    {
        $pdo = api_pdo();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database not available', 'data' => []];
        }
        $search = trim((string)($params['search'] ?? ''));
        try {
            $sql = "SELECT id, user_id, username, nickname, phone, wallet_balance, game_balance, can_bet, status, created_at FROM api_users WHERE is_demo = 1";
            $args = [];
            if ($search !== '') {
                $sql .= " AND (username LIKE ? OR nickname LIKE ? OR phone LIKE ?)";
                $wild = "%$search%";
                $args = [$wild, $wild, $wild];
            }
            $sql .= " ORDER BY id DESC LIMIT 200";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($args);
            $rows = $stmt->fetchAll() ?: [];
            $data = [];
            foreach ($rows as $row) {
                $data[] = [
                    'id' => (int)$row['id'],
                    'user_id' => (int)$row['user_id'],
                    'username' => htmlspecialchars((string)$row['username']),
                    'nickname' => htmlspecialchars((string)($row['nickname'] ?? '')),
                    'phone' => htmlspecialchars((string)($row['phone'] ?? '')),
                    'balance' => api_wallet_balance_of($row),
                    'can_bet' => (int)$row['can_bet'],
                    'status' => (int)($row['status'] ?? 1),
                    'created_at' => (string)$row['created_at'],
                ];
            }
            return ['success' => true, 'data' => $data];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'data' => []];
        }
    }

    /** Create a demo user with a starting demo balance (one click). */
    public static function createDemoUser(array $post): array
    {
        $username = trim((string)($post['username'] ?? ''));
        if ($username === '') {
            $username = 'demo' . mt_rand(1000, 9999);
        }
        $balance = isset($post['balance']) && $post['balance'] !== '' ? (float)$post['balance'] : 10000.0;

        $post['is_demo'] = 1;
        $post['can_bet'] = 1;
        $post['status'] = 1;
        $post['start_balance'] = $balance;
        if (trim((string)($post['password'] ?? '')) === '') {
            $post['password'] = 'demo123';
        }
        if (trim((string)($post['nickname'] ?? '')) === '') {
            $post['nickname'] = 'Demo ' . $username;
        }

        $res = self::saveUser($post);
        if (!empty($res['success'])) {
            $res['message'] = 'Demo user ' . $username . ' created with ₹' . number_format($balance, 2) . ' demo balance';
        }
        return $res;
    }

    public static function deleteDemoUser(int $id): array
    {
        $pdo = api_pdo();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database not available'];
        }
        try {
            $stmt = $pdo->prepare("SELECT id, username FROM api_users WHERE id = ? AND is_demo = 1 LIMIT 1");
            $stmt->execute([$id]);
            $user = $stmt->fetch();
            if (!$user) {
                return ['success' => false, 'message' => 'Demo user not found'];
            }
            $pdo->prepare("DELETE FROM wallet_logs WHERE user_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM api_users WHERE id = ?")->execute([$id]);
            return ['success' => true, 'message' => 'Demo user ' . $user['username'] . ' deleted'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /** Reset (or set) the balance of a demo user. */
    public static function resetDemoBalance(int $id, float $balance): array
    {
        $pdo = api_pdo();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database not available'];
        }
        if ($balance < 0) {
            return ['success' => false, 'message' => 'Balance cannot be negative'];
        }
        try {
            $stmt = $pdo->prepare("SELECT * FROM api_users WHERE id = ? AND is_demo = 1 LIMIT 1");
            $stmt->execute([$id]);
            $user = $stmt->fetch();
            if (!$user) {
                return ['success' => false, 'message' => 'Demo user not found'];
            }
            $delta = $balance - api_wallet_balance_of($user);
            if (abs($delta) > 0.0000001) {
                $applied = api_wallet_apply_change($id, $delta, 'wallet', 'Demo balance reset from admin panel');
                if (empty($applied['success'])) {
                    return $applied;
                }
            }
            return ['success' => true, 'message' => 'Demo balance set to ₹' . number_format($balance, 2)];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /** Agent / promoter accounts created from the panel. */
    public static function listAgents(array $params = []): array
    {
        $pdo = api_pdo();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database not available', 'data' => []];
        }
        try {
            $rows = $pdo->query("SELECT id, user_id, username, nickname, phone, wallet_balance, game_balance, can_bet, status, is_agent, agent_rate, created_at FROM api_users WHERE is_agent = 1 ORDER BY id DESC LIMIT 200")->fetchAll() ?: [];
            $data = [];
            foreach ($rows as $row) {
                $teamSize = 0;
                try {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM api_users WHERE referrer_id = ?");
                    $stmt->execute([(int)$row['user_id']]);
                    $teamSize = (int)$stmt->fetchColumn();
                } catch (Throwable $e) {
                }
                $commission = 0.0;
                try {
                    $stmt = $pdo->prepare("SELECT COALESCE(SUM(commission_amount), 0) FROM agent_commissions WHERE user_id = ?");
                    $stmt->execute([(int)$row['user_id']]);
                    $commission = (float)$stmt->fetchColumn();
                } catch (Throwable $e) {
                }
                $data[] = [
                    'id' => (int)$row['id'],
                    'user_id' => (int)$row['user_id'],
                    'username' => htmlspecialchars((string)$row['username']),
                    'nickname' => htmlspecialchars((string)($row['nickname'] ?? '')),
                    'phone' => htmlspecialchars((string)($row['phone'] ?? '')),
                    'balance' => api_wallet_balance_of($row),
                    'agent_rate' => (float)($row['agent_rate'] ?? 0),
                    'team_size' => $teamSize,
                    'commission' => $commission,
                    'status' => (int)($row['status'] ?? 1),
                    'can_bet' => (int)($row['can_bet'] ?? 1),
                    'created_at' => (string)$row['created_at'],
                ];
            }
            return ['success' => true, 'data' => $data];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'data' => []];
        }
    }

    public static function createAgentUser(array $post): array
    {
        $username = trim((string)($post['username'] ?? ''));
        if ($username === '') {
            $username = 'agent' . mt_rand(1000, 9999);
        }
        if (trim((string)($post['password'] ?? '')) === '') {
            $post['password'] = 'agent123';
        }
        if (trim((string)($post['nickname'] ?? '')) === '') {
            $post['nickname'] = 'Agent ' . $username;
        }
        $post['is_agent'] = 1;
        $post['can_bet'] = 1;
        $post['status'] = 1;
        $post['agent_rate'] = isset($post['agent_rate']) ? (float)$post['agent_rate'] : 0.0;

        $res = self::saveUser($post);
        if (!empty($res['success'])) {
            $res['message'] = 'Agent user ' . $username . ' created';
        }
        return $res;
    }

    /** Banned / blocked players. */
    public static function listBannedUsers(array $params = []): array
    {
        $pdo = api_pdo();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database not available', 'data' => []];
        }
        try {
            $rows = $pdo->query("SELECT id, user_id, username, nickname, phone, wallet_balance, game_balance, can_bet, status, ban_reason, created_at FROM api_users WHERE can_bet = 0 OR status = 0 ORDER BY id DESC LIMIT 300")->fetchAll() ?: [];
            $data = [];
            foreach ($rows as $row) {
                $data[] = [
                    'id' => (int)$row['id'],
                    'user_id' => (int)$row['user_id'],
                    'username' => htmlspecialchars((string)$row['username']),
                    'nickname' => htmlspecialchars((string)($row['nickname'] ?? '')),
                    'phone' => htmlspecialchars((string)($row['phone'] ?? '')),
                    'balance' => api_wallet_balance_of($row),
                    'can_bet' => (int)$row['can_bet'],
                    'status' => (int)($row['status'] ?? 1),
                    'ban_reason' => htmlspecialchars((string)($row['ban_reason'] ?? '')),
                    'created_at' => (string)$row['created_at'],
                ];
            }
            return ['success' => true, 'data' => $data];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'data' => []];
        }
    }

    /** Ban or unban a player (ban blocks betting + login, unban restores both). */
    public static function setUserBan(int $id, bool $ban, string $reason = ''): array
    {
        $pdo = api_pdo();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database not available'];
        }
        try {
            $stmt = $pdo->prepare("SELECT username FROM api_users WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $username = $stmt->fetchColumn();
            if ($username === false) {
                return ['success' => false, 'message' => 'User not found'];
            }

            if ($ban) {
                $pdo->prepare("UPDATE api_users SET can_bet = 0, status = 0, ban_reason = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")
                    ->execute([$reason !== '' ? $reason : 'Blocked by admin', $id]);
                return ['success' => true, 'message' => 'User ' . $username . ' banned'];
            }

            $pdo->prepare("UPDATE api_users SET can_bet = 1, status = 1, ban_reason = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$id]);
            return ['success' => true, 'message' => 'User ' . $username . ' unbanned'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public static function deleteUser(int $id): array
    {
        $pdo = api_pdo();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database not available'];
        }
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid user'];
        }
        try {
            $stmt = $pdo->prepare("SELECT username FROM api_users WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $username = $stmt->fetchColumn();
            if ($username === false) {
                return ['success' => false, 'message' => 'User not found'];
            }
            $pdo->prepare("DELETE FROM api_users WHERE id = ?")->execute([$id]);
            return ['success' => true, 'message' => 'User ' . $username . ' deleted'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /** Small id/name list used by the admin panel dropdowns. */
    public static function listUserOptions(): array
    {
        $pdo = api_pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $rows = $pdo->query("SELECT id, user_id, username, nickname FROM api_users ORDER BY id DESC LIMIT 500")->fetchAll() ?: [];
        } catch (Throwable $e) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id' => (int)$row['id'],
                'user_id' => (int)$row['user_id'],
                'username' => (string)$row['username'],
                'nickname' => (string)($row['nickname'] ?? ''),
            ];
        }
        return $out;
    }

    public static function adjustBalance(int $userId, string $type, float $amount, string $notes): array
    {
        $pdo = api_pdo();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database not available'];
        }

        if ($amount === 0.0) {
            return ['success' => false, 'message' => 'Amount cannot be zero'];
        }

        try {
            $stmt = $pdo->prepare("SELECT id FROM api_users WHERE user_id = ? LIMIT 1");
            $stmt->execute([$userId]);
            $rowId = $stmt->fetchColumn();
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }

        if ($rowId === false) {
            return ['success' => false, 'message' => 'User not found'];
        }

        // Wallet and game balance show the same money, so the helper mirrors the
        // change into both columns and writes the wallet log in a best-effort way.
        $result = api_wallet_apply_change((int) $rowId, $amount, $type === 'game' ? 'game' : 'wallet', $notes);
        if (empty($result['success'])) {
            return $result;
        }

        return [
            'success' => true,
            'message' => 'Balance adjusted successfully',
            'new_balance' => $result['new_balance'] ?? null,
        ];
    }

    public static function setTargetControl(int $userId, int $winRate, int $status): array
    {
        $pdo = api_pdo();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database not available'];
        }

        if ($winRate < 0 || $winRate > 100) {
            return ['success' => false, 'message' => 'Win rate must be between 0 and 100'];
        }

        try {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT user_id FROM api_users WHERE user_id = ? LIMIT 1");
            $stmt->execute([$userId]);
            if (!$stmt->fetch()) {
                return ['success' => false, 'message' => 'User not found'];
            }

            $driver = api_db_driver($pdo);
            if ($driver === 'mysql') {
                $stmt = $pdo->prepare("INSERT INTO user_control (user_id, win_rate_percent, status) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE win_rate_percent = VALUES(win_rate_percent), status = VALUES(status), updated_at = CURRENT_TIMESTAMP");
                $stmt->execute([$userId, $winRate, $status]);
            } else {
                $stmt = $pdo->prepare("SELECT id FROM user_control WHERE user_id = ? LIMIT 1");
                $stmt->execute([$userId]);
                if ($stmt->fetch()) {
                    $stmt = $pdo->prepare("UPDATE user_control SET win_rate_percent = ?, status = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
                    $stmt->execute([$winRate, $status, $userId]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO user_control (user_id, win_rate_percent, status, updated_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
                    $stmt->execute([$userId, $winRate, $status]);
                }
            }

            return ['success' => true, 'message' => 'User win-rate target control updated successfully'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}
