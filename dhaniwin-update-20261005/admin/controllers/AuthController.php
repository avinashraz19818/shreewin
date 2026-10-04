<?php
declare(strict_types=1);

class AuthController
{
    public static function login(string $username, string $password, bool $remember = false): array
    {
        $pdo = api_pdo();
        if (!$pdo) {
            $err = $GLOBALS['db_connection_error'] ?? 'Connection failed';
            return ['success' => false, 'message' => 'Database not available: ' . $err];
        }

        try {
            $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if (!$admin) {
                self::logLogin(0, $username, 'failed', 'Invalid username');
                return ['success' => false, 'message' => 'Invalid username or password'];
            }

            if ((int)$admin['status'] !== 1) {
                self::logLogin((int)$admin['id'], $username, 'failed', 'Account disabled');
                return ['success' => false, 'message' => 'Account is disabled'];
            }

            if (!password_verify($password, $admin['password_hash'])) {
                self::logLogin((int)$admin['id'], $username, 'failed', 'Wrong password');
                return ['success' => false, 'message' => 'Invalid username or password'];
            }

            // Successful login
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = (int)$admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_role_id'] = (int)$admin['role_id'];

            self::logLogin((int)$admin['id'], $username, 'success', 'Login successful');

            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $stmt = $pdo->prepare("UPDATE admin_users SET remember_token = ? WHERE id = ?");
                $stmt->execute([$token, $admin['id']]);
                setcookie('admin_remember', $token, time() + (86400 * 30), '/'); // 30 days
            }

            // Activity Log
            self::logActivity((int)$admin['id'], 'login', 'admin_users', null, null);

            return ['success' => true, 'message' => 'Logged in successfully'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public static function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $adminId = $_SESSION['admin_id'] ?? 0;
        if ($adminId > 0) {
            self::logActivity((int)$adminId, 'logout', 'admin_users', null, null);
        }

        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        @session_destroy();

        if (isset($_COOKIE['admin_remember'])) {
            $pdo = api_pdo();
            if ($pdo) {
                try {
                    $stmt = $pdo->prepare("UPDATE admin_users SET remember_token = NULL WHERE remember_token = ?");
                    $stmt->execute([$_COOKIE['admin_remember']]);
                } catch (Throwable $e) {}
            }
            setcookie('admin_remember', '', time() - 3600, '/');
        }
    }

    public static function checkRememberMe(): bool
    {
        if (!empty($_SESSION['admin_logged_in'])) {
            return true;
        }

        $token = $_COOKIE['admin_remember'] ?? null;
        if (!$token) {
            return false;
        }

        $pdo = api_pdo();
        if (!$pdo) {
            return false;
        }

        try {
            $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE remember_token = ? LIMIT 1");
            $stmt->execute([$token]);
            $admin = $stmt->fetch();

            if ($admin && (int)$admin['status'] === 1) {
                if (session_status() === PHP_SESSION_NONE) {
                    @session_start();
                }
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = (int)$admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_role_id'] = (int)$admin['role_id'];

                self::logLogin((int)$admin['id'], $admin['username'], 'success', 'Remember-Me auto login');
                return true;
            }
        } catch (Throwable $e) {}

        return false;
    }

    public static function changePassword(int $adminId, string $oldPassword, string $newPassword): array
    {
        $pdo = api_pdo();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database not available'];
        }

        try {
            $stmt = $pdo->prepare("SELECT password_hash FROM admin_users WHERE id = ? LIMIT 1");
            $stmt->execute([$adminId]);
            $hash = $stmt->fetchColumn();

            if (!$hash || !password_verify($oldPassword, $hash)) {
                return ['success' => false, 'message' => 'Incorrect old password'];
            }

            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$newHash, $adminId]);

            self::logActivity($adminId, 'change_password', 'admin_users', 'Password changed', null);

            return ['success' => true, 'message' => 'Password updated successfully'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public static function listAdmins(): array
    {
        $pdo = api_pdo();
        if (!$pdo) return [];
        try {
            $stmt = $pdo->query("
                SELECT u.id, u.username, u.email, u.status, u.role_id, u.created_at, r.role_name, r.role_label
                FROM admin_users u
                LEFT JOIN admin_roles r ON r.id = u.role_id
                ORDER BY u.id ASC
            ");
            $admins = $stmt->fetchAll() ?: [];
            foreach ($admins as &$admin) {
                $adminId = (int)$admin['id'];
                $roleId = (int)$admin['role_id'];
                if ($roleId === 1) {
                    $admin['permissions'] = ['*'];
                    $admin['is_super_admin'] = true;
                } else {
                    $admin['is_super_admin'] = false;
                    $stmtPerm = $pdo->prepare("
                        SELECT ap.permission_key, ap.permission_label 
                        FROM admin_user_permissions aup
                        JOIN admin_permissions ap ON ap.id = aup.permission_id
                        WHERE aup.admin_id = ?
                    ");
                    $stmtPerm->execute([$adminId]);
                    $admin['permissions'] = $stmtPerm->fetchAll() ?: [];
                }
            }
            return $admins;
        } catch (Throwable $e) {
            return [];
        }
    }

    public static function createAdmin(array $data): array
    {
        $pdo = api_pdo();
        if (!$pdo) return ['success' => false, 'message' => 'Database not available'];
        $username = trim((string)($data['username'] ?? ''));
        $password = (string)($data['password'] ?? '');
        $email = trim((string)($data['email'] ?? ''));
        $roleId = (int)($data['role_id'] ?? 2);
        $permissions = $data['permissions'] ?? [];
        if (!is_array($permissions)) {
            $permissions = json_decode((string)$permissions, true) ?: [];
        }

        if ($username === '' || strlen($username) < 3) {
            return ['success' => false, 'message' => 'Username must be at least 3 characters'];
        }
        if ($password === '' || strlen($password) < 4) {
            return ['success' => false, 'message' => 'Password must be at least 4 characters'];
        }

        try {
            $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Admin username already exists'];
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash, role_id, email, status) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$username, $hash, $roleId, $email ?: null]);
            $newAdminId = (int)$pdo->lastInsertId();

            // Assign permissions
            if ($roleId !== 1 && !empty($permissions)) {
                $ignoreVerb = api_db_driver($pdo) === 'sqlite' ? 'INSERT OR IGNORE' : 'INSERT IGNORE';
                $stmtPerm = $pdo->prepare($ignoreVerb . " INTO admin_user_permissions (admin_id, permission_id) VALUES (?, ?)");
                foreach ($permissions as $permKeyOrId) {
                    if (is_numeric($permKeyOrId)) {
                        $stmtPerm->execute([$newAdminId, (int)$permKeyOrId]);
                    } else {
                        $stmtGet = $pdo->prepare("SELECT id FROM admin_permissions WHERE permission_key = ? LIMIT 1");
                        $stmtGet->execute([(string)$permKeyOrId]);
                        $pid = $stmtGet->fetchColumn();
                        if ($pid) {
                            $stmtPerm->execute([$newAdminId, (int)$pid]);
                        }
                    }
                }
            }

            return ['success' => true, 'message' => 'Admin user created successfully', 'admin_id' => $newAdminId];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public static function updateAdminPermissions(int $adminId, array $permissions): array
    {
        $pdo = api_pdo();
        if (!$pdo) return ['success' => false, 'message' => 'Database not available'];
        if ($adminId <= 0) return ['success' => false, 'message' => 'Invalid Admin ID'];

        try {
            $stmt = $pdo->prepare("SELECT role_id FROM admin_users WHERE id = ? LIMIT 1");
            $stmt->execute([$adminId]);
            $roleId = (int)$stmt->fetchColumn();
            if ($roleId === 1) {
                return ['success' => false, 'message' => 'Super Admin already has full unrestricted access'];
            }

            $pdo->prepare("DELETE FROM admin_user_permissions WHERE admin_id = ?")->execute([$adminId]);
            $ignoreVerb = api_db_driver($pdo) === 'sqlite' ? 'INSERT OR IGNORE' : 'INSERT IGNORE';
            $stmtPerm = $pdo->prepare($ignoreVerb . " INTO admin_user_permissions (admin_id, permission_id) VALUES (?, ?)");
            foreach ($permissions as $permKeyOrId) {
                if (is_numeric($permKeyOrId)) {
                    $stmtPerm->execute([$adminId, (int)$permKeyOrId]);
                } else {
                    $stmtGet = $pdo->prepare("SELECT id FROM admin_permissions WHERE permission_key = ? LIMIT 1");
                    $stmtGet->execute([(string)$permKeyOrId]);
                    $pid = $stmtGet->fetchColumn();
                    if ($pid) {
                        $stmtPerm->execute([$adminId, (int)$pid]);
                    }
                }
            }
            return ['success' => true, 'message' => 'Permissions updated successfully'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public static function toggleAdminStatus(int $adminId): array
    {
        $pdo = api_pdo();
        if (!$pdo) return ['success' => false, 'message' => 'Database not available'];
        if ($adminId === 1) return ['success' => false, 'message' => 'Cannot disable primary Super Admin'];

        try {
            $stmt = $pdo->prepare("SELECT status FROM admin_users WHERE id = ? LIMIT 1");
            $stmt->execute([$adminId]);
            $curr = $stmt->fetchColumn();
            if ($curr === false) return ['success' => false, 'message' => 'Admin not found'];
            $newStatus = ((int)$curr === 1) ? 0 : 1;
            $pdo->prepare("UPDATE admin_users SET status = ? WHERE id = ?")->execute([$newStatus, $adminId]);
            return ['success' => true, 'message' => 'Admin status updated', 'status' => $newStatus];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public static function resetAdminPassword(int $adminId, string $newPassword): array
    {
        $pdo = api_pdo();
        if (!$pdo) return ['success' => false, 'message' => 'Database not available'];
        if (strlen($newPassword) < 4) return ['success' => false, 'message' => 'Password must be at least 4 characters'];
        try {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?")->execute([$hash, $adminId]);
            return ['success' => true, 'message' => 'Admin password reset successfully'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public static function deleteAdmin(int $adminId): array
    {
        $pdo = api_pdo();
        if (!$pdo) return ['success' => false, 'message' => 'Database not available'];
        if ($adminId === 1) return ['success' => false, 'message' => 'Cannot delete primary Super Admin'];
        if (session_status() === PHP_SESSION_NONE) @session_start();
        if ((int)($_SESSION['admin_id'] ?? 0) === $adminId) return ['success' => false, 'message' => 'Cannot delete your own logged-in account'];

        try {
            $pdo->prepare("DELETE FROM admin_user_permissions WHERE admin_id = ?")->execute([$adminId]);
            $pdo->prepare("DELETE FROM admin_users WHERE id = ?")->execute([$adminId]);
            return ['success' => true, 'message' => 'Admin deleted successfully'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    private static function logLogin(int $adminId, string $username, string $status, string $message): void
    {
        $pdo = api_pdo();
        if (!$pdo) return;
        try {
            $ip = api_client_ip();
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $stmt = $pdo->prepare("INSERT INTO admin_login_history (admin_id, ip_address, user_agent, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$adminId, $ip, $ua, $status . ': ' . $message]);
        } catch (Throwable $e) {}
    }

    public static function logActivity(int $adminId, string $action, string $target, ?string $before = null, ?string $after = null): void
    {
        $pdo = api_pdo();
        if (!$pdo) return;
        try {
            $stmt = $pdo->prepare("INSERT INTO admin_activity_logs (admin_id, action, target, before_state, after_state) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$adminId, $action, $target, $before, $after]);
        } catch (Throwable $e) {}
    }
}
