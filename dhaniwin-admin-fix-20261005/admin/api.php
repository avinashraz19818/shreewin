<?php
declare(strict_types=1);

require_once __DIR__ . '/../api/_bootstrap.php';

// Ensure session
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

header('Content-Type: application/json; charset=utf-8');

// Load controllers
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/DashboardController.php';
require_once __DIR__ . '/controllers/UserController.php';
require_once __DIR__ . '/controllers/AgentController.php';
require_once __DIR__ . '/controllers/FinanceController.php';
require_once __DIR__ . '/controllers/GameController.php';
require_once __DIR__ . '/controllers/SupportController.php';
require_once __DIR__ . '/controllers/SettingsController.php';
require_once __DIR__ . '/controllers/ReportController.php';

// Helper function to verify CSRF
if (!function_exists('verify_csrf')) {
    function verify_csrf(): bool
    {
        $token = '';
        if (isset($_REQUEST['csrf'])) {
            $token = (string)$_REQUEST['csrf'];
        } elseif (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = (string)$_SERVER['HTTP_X_CSRF_TOKEN'];
        } else {
            $headers = function_exists('getallheaders') ? getallheaders() : [];
            foreach ($headers as $key => $value) {
                if (strcasecmp($key, 'X-CSRF-Token') === 0) {
                    $token = (string)$value;
                    break;
                }
            }
        }
        $sessionToken = (string)($_SESSION['csrf'] ?? '');
        return $sessionToken !== '' && hash_equals($sessionToken, $token);
    }
}

// 1. Check logged in status
if (!AuthController::checkRememberMe() && empty($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please login.']);
    exit;
}

$action = (string)($_REQUEST['action'] ?? '');
if ($action === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Action parameter is required']);
    exit;
}

// CSRF check for write actions
$isWriteAction = $_SERVER['REQUEST_METHOD'] === 'POST' && !in_array($action, ['login', 'logout'], true);
if ($isWriteAction && !verify_csrf()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden: Invalid CSRF token']);
    exit;
}

$adminId = (int)($_SESSION['admin_id'] ?? 0);

// Plain HTML form posts (the settings/gateway tables submit without AJAX) get a
// redirect back to the page they came from plus a flash message, instead of raw
// JSON in the browser window.
$isAjaxRequest = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || str_contains((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')
    || !empty($_POST['ajax']);
$bufferPlainPost = ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isAjaxRequest);

if ($bufferPlainPost) {
    ob_start();
}

try {
    switch ($action) {
        // --- Dashboard ---
        case 'get_stats':
            if (!admin_has_permission('dashboard')) {
                throw new Exception('Permission denied: dashboard');
            }
            echo json_encode(['success' => true, 'data' => DashboardController::getStats()]);
            break;

        case 'get_charts':
            if (!admin_has_permission('dashboard')) {
                throw new Exception('Permission denied: dashboard');
            }
            $days = (int)($_GET['days'] ?? 7);
            echo json_encode(['success' => true, 'data' => DashboardController::getChartsData($days)]);
            break;

        // --- Users ---
        case 'list_users':
            if (!admin_has_permission('user_management')) {
                throw new Exception('Permission denied: user_management');
            }
            echo json_encode(UserController::listUsers($_GET));
            break;

        case 'save_user':
            if (!admin_has_permission('user_management')) {
                throw new Exception('Permission denied: user_management');
            }
            $res = UserController::saveUser($_POST);
            if ($res['success']) {
                AuthController::logActivity($adminId, 'save_user', 'api_users', null, json_encode($_POST));
            }
            echo json_encode($res);
            break;

        case 'adjust_balance':
            if (!admin_has_permission('finance')) {
                throw new Exception('Permission denied: finance');
            }
            $userId = (int)($_POST['user_id'] ?? 0);
            $type = (string)($_POST['type'] ?? 'game'); // game or wallet
            $amount = (float)($_POST['amount'] ?? 0);
            $notes = trim((string)($_POST['notes'] ?? ''));
            $res = UserController::adjustBalance($userId, $type, $amount, $notes);
            if ($res['success']) {
                AuthController::logActivity($adminId, 'adjust_balance', 'api_users', null, "Adjusted $type balance of user $userId by $amount. Notes: $notes");
            }
            echo json_encode($res);
            break;

        case 'set_target_control':
            if (!admin_has_permission('user_control')) {
                throw new Exception('Permission denied: user_control');
            }
            $userId = (int)($_POST['user_id'] ?? 0);
            $winRate = (int)($_POST['win_rate_percent'] ?? 50);
            $status = (int)($_POST['status'] ?? 1);
            $res = UserController::setTargetControl($userId, $winRate, $status);
            if ($res['success']) {
                AuthController::logActivity($adminId, 'set_target_control', 'user_control', null, "Set user $userId win-rate to $winRate% (Status: $status)");
            }
            echo json_encode($res);
            break;

        // --- Finance ---
        case 'list_recharges':
            if (!admin_has_permission('finance')) {
                throw new Exception('Permission denied: finance');
            }
            echo json_encode(FinanceController::listRecharges($_GET));
            break;

        case 'list_withdrawals':
            if (!admin_has_permission('finance')) {
                throw new Exception('Permission denied: finance');
            }
            echo json_encode(FinanceController::listWithdrawals($_GET));
            break;

        case 'update_recharge':
            if (!admin_has_permission('finance')) {
                throw new Exception('Permission denied: finance');
            }
            $id = (int)($_POST['id'] ?? 0);
            $status = (string)($_POST['status'] ?? 'Pending');
            $remarks = (string)($_POST['remarks'] ?? '');
            $res = FinanceController::updateRechargeStatus($id, $status, $remarks);
            if ($res['success']) {
                AuthController::logActivity($adminId, 'update_recharge', 'recharge_orders', "ID: $id", "Status: $status, Remarks: $remarks");
            }
            echo json_encode($res);
            break;

        case 'update_withdrawal':
            if (!admin_has_permission('finance')) {
                throw new Exception('Permission denied: finance');
            }
            $id = (int)($_POST['id'] ?? 0);
            $status = (string)($_POST['status'] ?? 'Pending');
            $remarks = (string)($_POST['remarks'] ?? '');
            $res = FinanceController::updateWithdrawalStatus($id, $status, $remarks);
            if ($res['success']) {
                AuthController::logActivity($adminId, 'update_withdrawal', 'withdraw_orders', "ID: $id", "Status: $status, Remarks: $remarks");
            }
            echo json_encode($res);
            break;

        case 'bulk_approve_recharges':
            if (!admin_has_permission('finance')) {
                throw new Exception('Permission denied: finance');
            }
            $ids = $_POST['ids'] ?? [];
            if (!is_array($ids)) $ids = json_decode((string)$ids, true) ?: [];
            $res = FinanceController::bulkApproveRecharges($ids);
            AuthController::logActivity($adminId, 'bulk_approve_recharges', 'recharge_orders', null, json_encode($ids));
            echo json_encode($res);
            break;

        case 'bulk_reject_recharges':
            if (!admin_has_permission('finance')) {
                throw new Exception('Permission denied: finance');
            }
            $ids = $_POST['ids'] ?? [];
            $reason = (string)($_POST['reason'] ?? '');
            if (!is_array($ids)) $ids = json_decode((string)$ids, true) ?: [];
            $res = FinanceController::bulkRejectRecharges($ids, $reason);
            AuthController::logActivity($adminId, 'bulk_reject_recharges', 'recharge_orders', null, json_encode(['ids' => $ids, 'reason' => $reason]));
            echo json_encode($res);
            break;

        // --- Game Control ---
        case 'get_profit_projections':
            if (!admin_has_permission('game_control')) {
                throw new Exception('Permission denied: game_control');
            }
            $gameCode = (string)($_GET['game_code'] ?? 'WinGo_30S');
            $issueNumber = (string)($_GET['issue_number'] ?? '');
            if ($issueNumber === '') {
                $issueNumber = api_lottery_issue_data($gameCode)['issueNumber'];
            }
            $data = GameController::calculateProfitProjections($gameCode, $issueNumber);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'list_queue':
            if (!admin_has_permission('game_control')) {
                throw new Exception('Permission denied: game_control');
            }
            echo json_encode(['success' => true, 'data' => GameController::listQueue()]);
            break;

        case 'add_to_queue':
            if (!admin_has_permission('game_control')) {
                throw new Exception('Permission denied: game_control');
            }
            $gameCode = (string)($_POST['game_code'] ?? '');
            $issueNumber = (string)($_POST['issue_number'] ?? '');
            $premium = (string)($_POST['premium'] ?? '');
            $res = GameController::addToQueue($gameCode, $issueNumber, $premium);
            if ($res['success']) {
                AuthController::logActivity($adminId, 'add_to_queue', 'result_queue', null, "$gameCode issue $issueNumber force to $premium");
            }
            echo json_encode($res);
            break;

        case 'delete_from_queue':
            if (!admin_has_permission('game_control')) {
                throw new Exception('Permission denied: game_control');
            }
            $id = (int)($_POST['id'] ?? 0);
            $res = GameController::deleteFromQueue($id);
            if ($res['success']) {
                AuthController::logActivity($adminId, 'delete_from_queue', 'result_queue', "ID: $id", null);
            }
            echo json_encode($res);
            break;

        // --- Support Ticketing ---
        case 'list_tickets':
            if (!admin_has_permission('support')) {
                throw new Exception('Permission denied: support');
            }
            echo json_encode(SupportController::listTickets($_GET));
            break;

        case 'get_ticket':
            if (!admin_has_permission('support')) {
                throw new Exception('Permission denied: support');
            }
            $ticketId = (int)($_GET['ticket_id'] ?? 0);
            echo json_encode(['success' => true, 'data' => SupportController::getTicketDetails($ticketId)]);
            break;

        case 'reply_ticket':
            if (!admin_has_permission('support')) {
                throw new Exception('Permission denied: support');
            }
            $ticketId = (int)($_POST['ticket_id'] ?? 0);
            $message = (string)($_POST['message'] ?? '');
            $res = SupportController::replyToTicket($ticketId, $adminId, $message);
            if ($res['success']) {
                AuthController::logActivity($adminId, 'reply_ticket', 'support_tickets', "Ticket: $ticketId", $message);
            }
            echo json_encode($res);
            break;

        case 'close_ticket':
            if (!admin_has_permission('support')) {
                throw new Exception('Permission denied: support');
            }
            $ticketId = (int)($_POST['ticket_id'] ?? 0);
            $res = SupportController::closeTicket($ticketId);
            if ($res['success']) {
                AuthController::logActivity($adminId, 'close_ticket', 'support_tickets', "Ticket: $ticketId", null);
            }
            echo json_encode($res);
            break;

        // --- Settings & Gateway ---
        case 'save_setting':
            if (!admin_has_permission('settings')) {
                throw new Exception('Permission denied: settings');
            }
            $key = (string)($_POST['setting_key'] ?? '');
            $val = (string)($_POST['setting_value'] ?? '');
            $res = SettingsController::saveSetting($key, $val);
            if ($res['success']) {
                AuthController::logActivity($adminId, 'save_setting', 'api_settings', $key, $val);
            }
            echo json_encode($res);
            break;

        case 'change_own_password':
            // Available to every logged in admin, also the read-only staff role.
            $oldPassword = (string)($_POST['old_password'] ?? '');
            $newPassword = (string)($_POST['new_password'] ?? '');
            if (strlen($newPassword) < 6) {
                echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters']);
                break;
            }
            $res = AuthController::changePassword($adminId, $oldPassword, $newPassword);
            echo json_encode($res);
            break;

        case 'save_admin_profile':
            // Change the logged in admin's own username / email.
            $newUsername = trim((string)($_POST['username'] ?? ''));
            $newEmail = trim((string)($_POST['email'] ?? ''));
            if ($newUsername === '') {
                echo json_encode(['success' => false, 'message' => 'Username cannot be empty']);
                break;
            }
            $pdo = api_pdo();
            try {
                $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = ? AND id <> ? LIMIT 1");
                $stmt->execute([$newUsername, $adminId]);
                if ($stmt->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'That username is already taken']);
                    break;
                }
                $stmt = $pdo->prepare("UPDATE admin_users SET username = ?, email = ? WHERE id = ?");
                $stmt->execute([$newUsername, $newEmail !== '' ? $newEmail : null, $adminId]);
                $_SESSION['admin_username'] = $newUsername;
                AuthController::logActivity($adminId, 'save_admin_profile', 'admin_users', (string)$adminId, json_encode(['username' => $newUsername, 'email' => $newEmail]));
                echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
            } catch (Throwable $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;

        case 'save_payment':
            if (!admin_has_permission('settings')) {
                throw new Exception('Permission denied: settings');
            }
            $res = SettingsController::savePaymentMethod($_POST);
            if ($res['success']) {
                AuthController::logActivity($adminId, 'save_payment_method', 'payment_methods', null, json_encode($_POST));
            }
            echo json_encode($res);
            break;

        case 'save_usdt':
            if (!admin_has_permission('settings')) {
                throw new Exception('Permission denied: settings');
            }
            $res = SettingsController::saveUsdtMethod($_POST);
            if ($res['success']) {
                AuthController::logActivity($adminId, 'save_usdt_method', 'usdt_methods', null, json_encode($_POST));
            }
            echo json_encode($res);
            break;

        case 'delete_payment':
            if (!admin_has_permission('settings')) {
                throw new Exception('Permission denied: settings');
            }
            $id = (int)($_POST['id'] ?? 0);
            $res = SettingsController::deletePaymentMethod($id);
            if ($res['success']) {
                AuthController::logActivity($adminId, 'delete_payment_method', 'payment_methods', "ID: $id", null);
            }
            echo json_encode($res);
            break;

        case 'delete_usdt':
            if (!admin_has_permission('settings')) {
                throw new Exception('Permission denied: settings');
            }
            $id = (int)($_POST['id'] ?? 0);
            $res = SettingsController::deleteUsdtMethod($id);
            if ($res['success']) {
                AuthController::logActivity($adminId, 'delete_usdt_method', 'usdt_methods', "ID: $id", null);
            }
            echo json_encode($res);
            break;

        // --- Sub-Admin & Role Management ---
        case 'list_admins':
            if (!admin_has_permission('settings')) {
                throw new Exception('Permission denied: settings');
            }
            echo json_encode(['success' => true, 'data' => AuthController::listAdmins()]);
            break;

        case 'create_admin':
            if (!admin_has_permission('settings')) {
                throw new Exception('Permission denied: settings');
            }
            $res = AuthController::createAdmin($_POST);
            if ($res['success']) {
                AuthController::logActivity($adminId, 'create_admin', 'admin_users', null, json_encode(['username' => $_POST['username'] ?? '', 'role' => $_POST['role_id'] ?? 2]));
            }
            echo json_encode($res);
            break;

        case 'update_admin_permissions':
            if (!admin_has_permission('settings')) {
                throw new Exception('Permission denied: settings');
            }
            $targetAdminId = (int)($_POST['admin_id'] ?? 0);
            $permissions = $_POST['permissions'] ?? [];
            if (!is_array($permissions)) {
                $permissions = json_decode((string)$permissions, true) ?: [];
            }
            $res = AuthController::updateAdminPermissions($targetAdminId, $permissions);
            if ($res['success']) {
                AuthController::logActivity($adminId, 'update_admin_permissions', 'admin_users', "Admin ID: $targetAdminId", json_encode($permissions));
            }
            echo json_encode($res);
            break;

        case 'toggle_admin_status':
            if (!admin_has_permission('settings')) {
                throw new Exception('Permission denied: settings');
            }
            $targetAdminId = (int)($_POST['admin_id'] ?? 0);
            $res = AuthController::toggleAdminStatus($targetAdminId);
            if ($res['success']) {
                AuthController::logActivity($adminId, 'toggle_admin_status', 'admin_users', "Admin ID: $targetAdminId", json_encode($res));
            }
            echo json_encode($res);
            break;

        case 'reset_admin_password':
            if (!admin_has_permission('settings')) {
                throw new Exception('Permission denied: settings');
            }
            $targetAdminId = (int)($_POST['admin_id'] ?? 0);
            $newPassword = (string)($_POST['new_password'] ?? '');
            $res = AuthController::resetAdminPassword($targetAdminId, $newPassword);
            if ($res['success']) {
                AuthController::logActivity($adminId, 'reset_admin_password', 'admin_users', "Admin ID: $targetAdminId", 'Password updated by admin');
            }
            echo json_encode($res);
            break;

        case 'delete_admin':
            if (!admin_has_permission('settings')) {
                throw new Exception('Permission denied: settings');
            }
            $targetAdminId = (int)($_POST['admin_id'] ?? 0);
            $res = AuthController::deleteAdmin($targetAdminId);
            if ($res['success']) {
                AuthController::logActivity($adminId, 'delete_admin', 'admin_users', "Admin ID: $targetAdminId", null);
            }
            echo json_encode($res);
            break;

        // --- Reports & Export ---
        case 'export_report':
            if (!admin_has_permission('reports')) {
                throw new Exception('Permission denied: reports');
            }
            $type = (string)($_GET['type'] ?? 'users');
            ReportController::exportCSV($type);
            break;

        case 'list_activity_logs':
            if (!admin_has_permission('reports')) {
                throw new Exception('Permission denied: reports');
            }
            echo json_encode(['success' => true, 'data' => ReportController::listActivityLogs()]);
            break;

        case 'list_login_history':
            if (!admin_has_permission('reports')) {
                throw new Exception('Permission denied: reports');
            }
            echo json_encode(['success' => true, 'data' => ReportController::listLoginHistory()]);
            break;

        case 'get_game_state':
            if (!admin_has_permission('game_control')) {
                throw new Exception('Permission denied: game_control');
            }
            $gameCode = (string)($_GET['game_code'] ?? 'WinGo_30S');
            $issue = api_lottery_issue_data($gameCode);
            
            $pdo = api_pdo();
            $stmt = $pdo->prepare("SELECT premium FROM result_queue WHERE game_code = ? AND issue_number = ? LIMIT 1");
            $stmt->execute([$gameCode, $issue['issueNumber']]);
            $forced = $stmt->fetchColumn();
            
            $bets = GameController::getActiveBets($gameCode, $issue['issueNumber']);
            $totalBetsPool = 0.0;
            foreach ($bets as $b) {
                $totalBetsPool += (float)$b['stake_amount'];
            }
            
            $liveBets = [];
            foreach ($bets as $b) {
                $stmtUser = $pdo->prepare("SELECT username, phone FROM api_users WHERE user_id = ? LIMIT 1");
                $stmtUser->execute([$b['user_id']]);
                $user = $stmtUser->fetch();
                $liveBets[] = [
                    'id' => $b['id'],
                    'order_no' => $b['order_no'],
                    'player_id' => $b['user_id'],
                    'username' => htmlspecialchars((string)($user['username'] ?? 'Guest')),
                    'phone' => htmlspecialchars((string)($user['phone'] ?? '')),
                    'bet_content' => $b['bet_content'],
                    'stake_amount' => (float)$b['stake_amount'],
                    'created_at' => $b['created_at']
                ];
            }
            
            $stmtHist = $pdo->prepare("SELECT * FROM lottery_results WHERE game_code = ? ORDER BY id DESC LIMIT 10");
            $stmtHist->execute([$gameCode]);
            $history = $stmtHist->fetchAll() ?: [];
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'issue' => $issue,
                    'forced_outcome' => $forced !== false ? $forced : null,
                    'total_bets_pool' => $totalBetsPool,
                    'live_bets' => $liveBets,
                    'history' => $history
                ]
            ]);
            break;

        case 'unset_forced_outcome':
            if (!admin_has_permission('game_control')) {
                throw new Exception('Permission denied: game_control');
            }
            $gameCode = (string)($_POST['game_code'] ?? '');
            $issueNumber = (string)($_POST['issue_number'] ?? '');
            $res = GameController::deleteFromQueueByGameAndIssue($gameCode, $issueNumber);
            if ($res['success']) {
                AuthController::logActivity($adminId, 'unset_forced_outcome', 'result_queue', null, "$gameCode issue $issueNumber cleared force");
            }
            echo json_encode($res);
            break;

        case 'toggle_maintenance':
            if (!admin_has_permission('settings')) {
                throw new Exception('Permission denied: settings');
            }
            $enabled = (int)($_POST['enabled'] ?? 0);
            $res = SettingsController::saveSetting('site_maintenance', (string)$enabled);
            if ($res['success']) {
                AuthController::logActivity($adminId, 'toggle_maintenance', 'api_settings', 'site_maintenance', (string)$enabled);
            }
            echo json_encode($res);
            break;

        case 'list_gift_codes':
            if (!admin_has_permission('settings')) {
                throw new Exception('Permission denied: settings');
            }
            $pdo = api_pdo();
            $stmt = $pdo->query("SELECT * FROM gift_codes ORDER BY id DESC");
            $codes = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $codes]);
            break;

        case 'save_gift_code':
            if (!admin_has_permission('settings')) {
                throw new Exception('Permission denied: settings');
            }
            $pdo = api_pdo();
            $code = strtoupper(trim((string)($_POST['code'] ?? '')));
            $amount = (float)($_POST['prize_amount'] ?? 0);
            $maxRedeem = (int)($_POST['max_redeem'] ?? 1);
            if ($code === '' || $amount <= 0 || $maxRedeem <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
                break;
            }
            try {
                $stmt = $pdo->prepare("INSERT INTO gift_codes (code, prize_amount, max_redeem, redeemed_count) VALUES (?, ?, ?, 0)");
                $stmt->execute([$code, $amount, $maxRedeem]);
                AuthController::logActivity($adminId, 'create_gift_code', 'gift_codes', $code, "$amount with max $maxRedeem");
                echo json_encode(['success' => true, 'message' => 'Gift code created successfully']);
            } catch (Throwable $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;

        case 'list_gift_redemptions':
            if (!admin_has_permission('settings')) {
                throw new Exception('Permission denied: settings');
            }
            $pdo = api_pdo();
            try {
                $stmt = $pdo->query("
                    SELECT r.id, r.code, r.user_id, r.amount, r.balance_after, r.created_at,
                           u.username, u.nickname, u.phone
                    FROM gift_code_redemptions r
                    LEFT JOIN api_users u ON u.id = r.user_id
                    ORDER BY r.id DESC
                    LIMIT 200
                ");
                $rows = $stmt->fetchAll() ?: [];
            } catch (Throwable $e) {
                $rows = [];
            }
            $data = [];
            foreach ($rows as $row) {
                $data[] = [
                    'id' => (int)$row['id'],
                    'code' => htmlspecialchars((string)$row['code']),
                    'username' => htmlspecialchars((string)($row['username'] ?? ('User #' . (int)$row['user_id']))),
                    'nickname' => htmlspecialchars((string)($row['nickname'] ?? '')),
                    'phone' => htmlspecialchars((string)($row['phone'] ?? '')),
                    'amount' => (float)$row['amount'],
                    'balance_after' => (float)$row['balance_after'],
                    'created_at' => (string)$row['created_at'],
                ];
            }
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'delete_gift_code':
            if (!admin_has_permission('settings')) {
                throw new Exception('Permission denied: settings');
            }
            $id = (int)($_POST['id'] ?? 0);
            $pdo = api_pdo();
            try {
                $stmt = $pdo->prepare("DELETE FROM gift_codes WHERE id = ?");
                $stmt->execute([$id]);
                AuthController::logActivity($adminId, 'delete_gift_code', 'gift_codes', (string)$id, null);
                echo json_encode(['success' => true, 'message' => 'Gift code deleted successfully']);
            } catch (Throwable $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;

        // --- Demo users ---------------------------------------------------
        case 'list_demo_users':
            if (!admin_has_permission('user_management')) {
                throw new Exception('Permission denied: user_management');
            }
            echo json_encode(UserController::listDemoUsers($_GET));
            break;

        case 'create_demo_user':
            if (!admin_has_permission('user_management')) {
                throw new Exception('Permission denied: user_management');
            }
            $res = UserController::createDemoUser($_POST);
            if (!empty($res['success'])) {
                AuthController::logActivity($adminId, 'create_demo_user', 'api_users', null, json_encode(['username' => $_POST['username'] ?? '', 'balance' => $_POST['balance'] ?? '']));
            }
            echo json_encode($res);
            break;

        case 'delete_demo_user':
            if (!admin_has_permission('user_management')) {
                throw new Exception('Permission denied: user_management');
            }
            $res = UserController::deleteDemoUser((int)($_POST['id'] ?? 0));
            if (!empty($res['success'])) {
                AuthController::logActivity($adminId, 'delete_demo_user', 'api_users', (string)($_POST['id'] ?? ''), null);
            }
            echo json_encode($res);
            break;

        case 'reset_demo_balance':
            if (!admin_has_permission('user_management')) {
                throw new Exception('Permission denied: user_management');
            }
            $res = UserController::resetDemoBalance((int)($_POST['id'] ?? 0), (float)($_POST['balance'] ?? 0));
            if (!empty($res['success'])) {
                AuthController::logActivity($adminId, 'reset_demo_balance', 'api_users', (string)($_POST['id'] ?? ''), 'Balance: ' . ($_POST['balance'] ?? ''));
            }
            echo json_encode($res);
            break;

        // --- Agent / promoter users ---------------------------------------
        case 'list_agents':
            if (!admin_has_permission('agent_management')) {
                throw new Exception('Permission denied: agent_management');
            }
            echo json_encode(UserController::listAgents($_GET));
            break;

        case 'create_agent_user':
            if (!admin_has_permission('agent_management')) {
                throw new Exception('Permission denied: agent_management');
            }
            $res = UserController::createAgentUser($_POST);
            if (!empty($res['success'])) {
                AuthController::logActivity($adminId, 'create_agent_user', 'api_users', null, json_encode(['username' => $_POST['username'] ?? '', 'rate' => $_POST['agent_rate'] ?? '']));
            }
            echo json_encode($res);
            break;

        // --- Banned / blocked users ---------------------------------------
        case 'list_banned_users':
            if (!admin_has_permission('user_management')) {
                throw new Exception('Permission denied: user_management');
            }
            echo json_encode(UserController::listBannedUsers($_GET));
            break;

        case 'set_user_ban':
            if (!admin_has_permission('user_management')) {
                throw new Exception('Permission denied: user_management');
            }
            $banFlag = (int)($_POST['ban'] ?? 1) === 1;
            $res = UserController::setUserBan((int)($_POST['id'] ?? 0), $banFlag, (string)($_POST['reason'] ?? ''));
            if (!empty($res['success'])) {
                AuthController::logActivity($adminId, $banFlag ? 'ban_user' : 'unban_user', 'api_users', (string)($_POST['id'] ?? ''), (string)($_POST['reason'] ?? ''));
            }
            echo json_encode($res);
            break;

        case 'delete_user':
            if (!admin_has_permission('user_management')) {
                throw new Exception('Permission denied: user_management');
            }
            $res = UserController::deleteUser((int)($_POST['id'] ?? 0));
            if (!empty($res['success'])) {
                AuthController::logActivity($adminId, 'delete_user', 'api_users', (string)($_POST['id'] ?? ''), null);
            }
            echo json_encode($res);
            break;

        case 'list_user_options':
            if (!admin_has_permission('user_management') && !admin_has_permission('support')) {
                throw new Exception('Permission denied: user_management');
            }
            echo json_encode(['success' => true, 'data' => UserController::listUserOptions()]);
            break;

        // --- Gateway / UPI / USDT helper actions --------------------------
        case 'list_payments':
            if (!admin_has_permission('settings')) {
                throw new Exception('Permission denied: settings');
            }
            echo json_encode(['success' => true, 'data' => SettingsController::getPaymentMethods()]);
            break;

        case 'list_usdt_methods':
            if (!admin_has_permission('settings')) {
                throw new Exception('Permission denied: settings');
            }
            echo json_encode(['success' => true, 'data' => SettingsController::getUsdtMethods()]);
            break;

        case 'save_payment_image':
            if (!admin_has_permission('settings')) {
                throw new Exception('Permission denied: settings');
            }
            $res = SettingsController::saveGatewayImage('payment_methods', (int)($_POST['id'] ?? 0), $_POST, $_FILES);
            if (!empty($res['success'])) {
                AuthController::logActivity($adminId, 'save_payment_image', 'payment_methods', (string)($_POST['id'] ?? ''), json_encode($res));
            }
            echo json_encode($res);
            break;

        case 'save_usdt_image':
            if (!admin_has_permission('settings')) {
                throw new Exception('Permission denied: settings');
            }
            $res = SettingsController::saveGatewayImage('usdt_methods', (int)($_POST['id'] ?? 0), $_POST, $_FILES);
            if (!empty($res['success'])) {
                AuthController::logActivity($adminId, 'save_usdt_image', 'usdt_methods', (string)($_POST['id'] ?? ''), json_encode($res));
            }
            echo json_encode($res);
            break;

        // --- Support tickets per category ---------------------------------
        case 'support_category_counts':
            if (!admin_has_permission('support')) {
                throw new Exception('Permission denied: support');
            }
            echo json_encode(['success' => true, 'data' => SupportController::categoryCounts()]);
            break;

        case 'create_ticket':
            if (!admin_has_permission('support')) {
                throw new Exception('Permission denied: support');
            }
            $res = SupportController::createTicket($_POST);
            if (!empty($res['success'])) {
                AuthController::logActivity($adminId, 'create_ticket', 'support_tickets', (string)($res['ticket_id'] ?? ''), (string)($_POST['category'] ?? ''));
            }
            echo json_encode($res);
            break;

        case 'check_same_ip':
            if (!admin_has_permission('security')) {
                throw new Exception('Permission denied: security');
            }
            $pdo = api_pdo();
            $stmt = $pdo->query("SELECT id, user_id, username, nickname, raw_json FROM api_users WHERE raw_json IS NOT NULL AND raw_json <> ''");
            $users = $stmt->fetchAll();
            $ipMap = [];
            foreach ($users as $user) {
                $raw = json_decode($user['raw_json'], true);
                if (is_array($raw)) {
                    $ips = [];
                    if (!empty($raw['register_ip'])) {
                        $ips[] = $raw['register_ip'];
                    }
                    if (!empty($raw['last_login_ip'])) {
                        $ips[] = $raw['last_login_ip'];
                    }
                    if (!empty($raw['login_ips']) && is_array($raw['login_ips'])) {
                        $ips = array_merge($ips, $raw['login_ips']);
                    }
                    $ips = array_unique($ips);
                    foreach ($ips as $ip) {
                        if ($ip === '') continue;
                        if (!isset($ipMap[$ip])) {
                            $ipMap[$ip] = [];
                        }
                        $ipMap[$ip][] = [
                            'id' => $user['id'],
                            'user_id' => $user['user_id'],
                            'username' => $user['username'],
                            'nickname' => $user['nickname']
                        ];
                    }
                }
            }
            $result = [];
            foreach ($ipMap as $ip => $associatedUsers) {
                if (count($associatedUsers) > 1) {
                    $result[] = [
                        'ip' => $ip,
                        'count' => count($associatedUsers),
                        'users' => $associatedUsers
                    ];
                }
            }
            usort($result, function($a, $b) {
                return $b['count'] <=> $a['count'];
            });
            echo json_encode(['success' => true, 'data' => $result]);
            break;

        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => "Action '$action' not found"]);
            break;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Plain HTML form post: send the browser back to the page it came from with a
// flash message instead of printing JSON.
if ($bufferPlainPost) {
    $rawOutput = (string) ob_get_clean();
    $decoded = json_decode($rawOutput, true);
    $ok = is_array($decoded) && !empty($decoded['success']);
    $message = is_array($decoded) ? (string)($decoded['message'] ?? '') : 'Request failed';
    if ($message === '') {
        $message = $ok ? 'Saved successfully' : 'Request failed';
    }

    $back = (string)($_SERVER['HTTP_REFERER'] ?? '');
    if ($back === '' || strpos($back, '/admin/') === false) {
        $back = '/admin/?tab=' . rawurlencode((string)($_REQUEST['return_tab'] ?? 'dashboard'));
    }
    $separator = strpos($back, '?') === false ? '?' : '&';
    $target = $back . $separator . 'flash=' . rawurlencode($message) . '&flash_type=' . ($ok ? 'ok' : 'err');

    if (PHP_SAPI === 'cli') {
        // CLI/wasm test harnesses cannot read headers, so also echo the target.
        echo json_encode(['success' => $ok, 'redirect' => $target, 'message' => $message]);
    } else {
        header('Location: ' . $target);
    }
    exit;
}
