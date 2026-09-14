<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (empty($_SESSION['unohs'])) {
    header("location: api/login.php?msg=unauthorized");
    exit();
}
include("api/conn.php");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bot_token = mysqli_real_escape_string($conn, $_POST['bot_token']);
    $chat_id = mysqli_real_escape_string($conn, $_POST['chat_id']);
    $deposit_notification = isset($_POST['deposit_notification']) ? 1 : 0;
    $withdrawal_notification = isset($_POST['withdrawal_notification']) ? 1 : 0;
    
    // Check if settings exist
    $check = mysqli_query($conn, "SELECT id FROM finance_bot_settings LIMIT 1");
    
    if (mysqli_num_rows($check) > 0) {
        $query = "UPDATE finance_bot_settings SET 
                  bot_token = '$bot_token',
                  chat_id = '$chat_id',
                  deposit_notification = $deposit_notification,
                  withdrawal_notification = $withdrawal_notification,
                  updated_at = NOW()";
    } else {
        $query = "INSERT INTO finance_bot_settings 
                  (bot_token, chat_id, deposit_notification, withdrawal_notification) 
                  VALUES ('$bot_token', '$chat_id', $deposit_notification, $withdrawal_notification)";
    }
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Settings saved successfully!";
    } else {
        $_SESSION['error'] = "Error: " . mysqli_error($conn);
    }
    
    // Process pending transactions
    processFinanceNotifications();
    
    header("Location: finance_bot.php");
    exit();
}

// Handle manual run action
if (isset($_GET['action']) && $_GET['action'] == 'run_now') {
    processFinanceNotifications();
    $_SESSION['success'] = "Bot executed successfully!";
    header("Location: finance_bot.php");
    exit();
}

// Function to process notifications
function processFinanceNotifications() {
    global $conn;
    
    // Get bot settings
    $settings_query = mysqli_query($conn, "SELECT * FROM finance_bot_settings LIMIT 1");
    if (mysqli_num_rows($settings_query) == 0) return false;
    
    $settings = mysqli_fetch_assoc($settings_query);
    
    // Process deposit notifications if enabled
    if ($settings['deposit_notification'] == 1) {
        processDepositNotifications($settings);
    }
    
    // Process withdrawal notifications if enabled
    if ($settings['withdrawal_notification'] == 1) {
        processWithdrawalNotifications($settings);
    }
    
    return true;
}

// Function to process deposit notifications
function processDepositNotifications($settings) {
    global $conn;
    
    // Get all deposits (we'll check history for duplicates)
    $query = "SELECT * FROM thevani ORDER BY dinankavannuracisi DESC LIMIT 100";
    $result = mysqli_query($conn, $query);
    
    while ($deposit = mysqli_fetch_assoc($result)) {
        // Check if already notified for this status
        $check = mysqli_query($conn, "SELECT id FROM finance_notification_history 
                WHERE transaction_type = 'deposit' 
                AND order_no = '{$deposit['dharavahi']}' 
                AND status = '" . ($deposit['sthiti'] == 0 ? 'pending' : 'success') . "'");
        
        if (mysqli_num_rows($check) == 0) {
            // Send notification based on status
            if ($deposit['sthiti'] == 0) {
                // Pending deposit
                $message = "🟡 <b>New Deposit is on pending</b>\n" .
                          "💰 Amount: ₹" . number_format($deposit['motta'], 2) . "\n" .
                          "👤 UserId: " . $deposit['balakedara'] . "\n" .
                          "📄 Order No: " . $deposit['dharavahi'] . "\n" .
                          "📅 Date & Time: " . date('d-m-Y H:i:s', strtotime($deposit['dinankavannuracisi'])) . "\n" .
                          "----------------------------------\n" .
                          "Status: ⏳ Pending";
                
                $status = 'pending';
                $notification_type = 'pending';
                
            } else {
                // Successful deposit
                $message = "✅ <b>Deposit Successfully Completed</b>\n" .
                          "💰 Amount: ₹" . number_format($deposit['motta'], 2) . "\n" .
                          "👤 UserId: " . $deposit['balakedara'] . "\n" .
                          "📄 Order No: " . $deposit['dharavahi'] . "\n" .
                          "📅 Date & Time: " . date('d-m-Y H:i:s', strtotime($deposit['dinankavannuracisi'])) . "\n" .
                          "----------------------------------\n" .
                          "Status: ✅ Successfully deposited";
                
                $status = 'success';
                $notification_type = 'completed';
            }
            
            sendTelegram($message, $settings['bot_token'], $settings['chat_id']);
            
            // Save to history
            mysqli_query($conn, "INSERT INTO finance_notification_history 
                (transaction_type, order_no, user_id, amount, status, notification_type, created_at) 
                VALUES ('deposit', '{$deposit['dharavahi']}', '{$deposit['balakedara']}', 
                '{$deposit['motta']}', '$status', '$notification_type', NOW())");
            
            sleep(1);
        }
    }
}

// Function to process withdrawal notifications
function processWithdrawalNotifications($settings) {
    global $conn;
    
    // Get all withdrawals
    $query = "SELECT * FROM hintegedukolli ORDER BY dinankavannuracisi DESC LIMIT 100";
    $result = mysqli_query($conn, $query);
    
    while ($withdrawal = mysqli_fetch_assoc($result)) {
        // Check if already notified for this status
        $status_text = ($withdrawal['sthiti'] == 0) ? 'pending' : 
                      (($withdrawal['sthiti'] == 1) ? 'success' : 'rejected');
        
        $check = mysqli_query($conn, "SELECT id FROM finance_notification_history 
                WHERE transaction_type = 'withdrawal' 
                AND order_no = '{$withdrawal['dharavahi']}' 
                AND status = '$status_text'");
        
        if (mysqli_num_rows($check) == 0) {
            $withdraw_type = getWithdrawType($withdrawal['madari']);
            $status_text = ($withdrawal['sthiti'] == 0) ? 'pending' : 
                          (($withdrawal['sthiti'] == 1) ? 'success' : 'rejected');
            $notification_type = ($withdrawal['sthiti'] == 0) ? 'pending' : 'completed';
            
            // Send notification based on status
            if ($withdrawal['sthiti'] == 0) {
                // Pending withdrawal
                $message = "🟡 <b>New Withdrawal is on pending</b>\n" .
                          "💰 Amount: ₹" . number_format($withdrawal['motta'], 2) . "\n" .
                          "👤 UserId: " . $withdrawal['balakedara'] . "\n" .
                          "💳 Withdraw Type: " . $withdraw_type . "\n" .
                          "📄 Order No: " . $withdrawal['dharavahi'] . "\n" .
                          "📅 Date & Time: " . date('d-m-Y H:i:s', strtotime($withdrawal['dinankavannuracisi'])) . "\n" .
                          "----------------------------------\n" .
                          "Status: ⏳ Pending";
                
            } elseif ($withdrawal['sthiti'] == 1) {
                // Successful withdrawal
                $message = "✅ <b>Withdrawal Successfully Completed</b>\n" .
                          "💰 Amount: ₹" . number_format($withdrawal['motta'], 2) . "\n" .
                          "👤 UserId: " . $withdrawal['balakedara'] . "\n" .
                          "💳 Withdraw Type: " . $withdraw_type . "\n" .
                          "📄 Order No: " . $withdrawal['dharavahi'] . "\n" .
                          "📅 Date & Time: " . date('d-m-Y H:i:s', strtotime($withdrawal['dinankavannuracisi'])) . "\n" .
                          "----------------------------------\n" .
                          "Status: ✅ Successfully Withdrawn";
                
            } else {
                // Rejected withdrawal
                $message = "❌ <b>Withdrawal Rejected</b>\n" .
                          "💰 Amount: ₹" . number_format($withdrawal['motta'], 2) . "\n" .
                          "👤 UserId: " . $withdrawal['balakedara'] . "\n" .
                          "💳 Withdraw Type: " . $withdraw_type . "\n" .
                          "📄 Order No: " . $withdrawal['dharavahi'] . "\n" .
                          "📅 Date & Time: " . date('d-m-Y H:i:s', strtotime($withdrawal['dinankavannuracisi'])) . "\n" .
                          "----------------------------------\n" .
                          "Status: ❌ Rejected";
            }
            
            sendTelegram($message, $settings['bot_token'], $settings['chat_id']);
            
            // Save to history
            mysqli_query($conn, "INSERT INTO finance_notification_history 
                (transaction_type, order_no, user_id, amount, withdraw_type, status, notification_type, created_at) 
                VALUES ('withdrawal', '{$withdrawal['dharavahi']}', '{$withdrawal['balakedara']}', 
                '{$withdrawal['motta']}', '$withdraw_type', '$status_text', '$notification_type', NOW())");
            
            sleep(1);
        }
    }
}

// Function to get withdraw type
function getWithdrawType($madari) {
    switch ($madari) {
        case 1: return 'Bank Card';
        case 2: return 'UPI';
        case 3: return 'USDT';
        case 4: return 'E-Wallet';
        default: return 'Unknown';
    }
}

// Telegram function
function sendTelegram($message, $bot_token, $chat_id) {
    $url = "https://api.telegram.org/bot$bot_token/sendMessage";
    $data = [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_exec($ch);
    curl_close($ch);
}

// Get current settings
$settings = [];
$result = mysqli_query($conn, "SELECT * FROM finance_bot_settings LIMIT 1");
if (mysqli_num_rows($result) > 0) {
    $settings = mysqli_fetch_assoc($result);
} else {
    $settings = [
        'bot_token' => '',
        'chat_id' => '',
        'deposit_notification' => 1,
        'withdrawal_notification' => 1
    ];
}

// Get stats
$deposit_stats_query = "SELECT 
    COUNT(CASE WHEN sthiti = 0 THEN 1 END) as pending_deposits,
    COUNT(CASE WHEN sthiti = 1 THEN 1 END) as completed_deposits
    FROM thevani";
$deposit_stats_result = mysqli_query($conn, $deposit_stats_query);
$deposit_stats = mysqli_fetch_assoc($deposit_stats_result);

$withdrawal_stats_query = "SELECT 
    COUNT(CASE WHEN sthiti = 0 THEN 1 END) as pending_withdrawals,
    COUNT(CASE WHEN sthiti = 1 THEN 1 END) as completed_withdrawals,
    COUNT(CASE WHEN sthiti = 2 THEN 1 END) as rejected_withdrawals
    FROM hintegedukolli";
$withdrawal_stats_result = mysqli_query($conn, $withdrawal_stats_query);
$withdrawal_stats = mysqli_fetch_assoc($withdrawal_stats_result);

// Pagination for history
$limit = 50;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

$count_query = "SELECT COUNT(*) as total FROM finance_notification_history";
$count_result = mysqli_query($conn, $count_query);
$total_rows = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_rows / $limit);

$history_query = "SELECT * FROM finance_notification_history ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$history_result = mysqli_query($conn, $history_query);

// Get recent transactions
$recent_deposits_query = "SELECT * FROM thevani ORDER BY dinankavannuracisi DESC LIMIT 10";
$recent_deposits = mysqli_query($conn, $recent_deposits_query);

$recent_withdrawals_query = "SELECT * FROM hintegedukolli ORDER BY dinankavannuracisi DESC LIMIT 10";
$recent_withdrawals = mysqli_query($conn, $recent_withdrawals_query);
?>

<!doctype html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr" 
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Finance Bot Notification System</title>
     <meta name="description" content="" />
    <link rel="icon" type="image/x-icon" href="assets/img/favicon/favicon.ico?v=20260814-admin" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&ampdisplay=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="assets/vendor/fonts/remixicon/remixicon.css" />
    <link rel="stylesheet" href="assets/vendor/fonts/flag-icons.css" />
    <link rel="stylesheet" href="assets/vendor/libs/node-waves/node-waves.css" />
    <link rel="stylesheet" href="assets/vendor/css/rtl/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="assets/vendor/css/rtl/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="assets/css/demo.css" />
    <link rel="stylesheet" href="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="assets/vendor/libs/typeahead-js/typeahead.css" />
    <link rel="stylesheet" href="assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
    <link rel="stylesheet" href="assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
    <link rel="stylesheet" href="assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css" />
    <link rel="stylesheet" href="assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.css" />
    <link rel="stylesheet" href="assets/vendor/libs/select2/select2.css" />
    <link rel="stylesheet" href="assets/vendor/libs/@form-validation/form-validation.css" />
    <script src="assets/vendor/js/helpers.js"></script>
    <script src="assets/vendor/js/template-customizer.js"></script>
    <script src="assets/js/config.js"></script>
    <style>
        .form-section { border-radius: 0.35rem; padding: 1.5rem; margin-bottom: 1.5rem; border: 1px solid #e3e6f0; }
        .stats-card { border-left: 4px solid; padding: 1rem; margin-bottom: 1rem; }
        .stats-card.deposit { border-color: #28a745; background: rgba(40, 167, 69, 0.1); }
        .stats-card.withdrawal { border-color: #007bff; background: rgba(0, 123, 255, 0.1); }
        .stats-card.rejected { border-color: #dc3545; background: rgba(220, 53, 69, 0.1); }
        .stats-number { font-size: 1.8rem; font-weight: bold; margin-bottom: 0.5rem; }
        .recent-transactions { max-height: 300px; overflow-y: auto; }
        .notification-badge { font-size: 0.75rem; padding: 0.25rem 0.5rem; }
    </style>
</head>

<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <?php require_once("layout-menu.php"); ?>
            <div class="layout-page">
                <?php require_once("nav.php"); ?>
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible" role="alert">
                                <?= $_SESSION['success']; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['success']); ?>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible" role="alert">
                                <?= $_SESSION['error']; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['error']); ?>
                        <?php endif; ?>

                        <!-- Header with Run Button -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0">
                                <i class="ri-bank-card-line me-2"></i>
                                Finance Bot Notification System
                            </h4>
                            <a href="?action=run_now" class="btn btn-primary" onclick="return confirm('Process all transactions now?')">
                                <i class="ri-play-line me-1"></i> Process Now
                            </a>
                        </div>

                        <!-- Stats Cards -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="stats-card deposit">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="stats-number"><?= $deposit_stats['pending_deposits'] ?? 0; ?></div>
                                            <div>Pending Deposits</div>
                                        </div>
                                        <i class="ri-money-rupee-circle-line text-success" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card deposit">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="stats-number"><?= $deposit_stats['completed_deposits'] ?? 0; ?></div>
                                            <div>Completed Deposits</div>
                                        </div>
                                        <i class="ri-checkbox-circle-line text-success" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card withdrawal">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="stats-number"><?= $withdrawal_stats['pending_withdrawals'] ?? 0; ?></div>
                                            <div>Pending Withdrawals</div>
                                        </div>
                                        <i class="ri-bank-card-line text-primary" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card withdrawal">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="stats-number"><?= $withdrawal_stats['completed_withdrawals'] ?? 0; ?></div>
                                            <div>Completed Withdrawals</div>
                                        </div>
                                        <i class="ri-checkbox-circle-line text-primary" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bot Settings Form -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="ri-robot-line me-2"></i>
                                    Bot Settings
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" id="financeBotForm">
                                    <!-- Telegram Settings -->
                                    <div class="form-section">
                                        <h5 class="text-primary mb-3">
                                            <i class="ri-telegram-line me-2"></i>
                                            Telegram Bot Settings
                                        </h5>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Bot Token <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="bot_token" 
                                                       value="<?= htmlspecialchars($settings['bot_token']); ?>" required>
                                                <div class="form-text">Get this from @BotFather on Telegram</div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Chat ID <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="chat_id" 
                                                       value="<?= htmlspecialchars($settings['chat_id']); ?>" required>
                                                <div class="form-text">Channel/Group ID</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Notification Toggles -->
                                    <div class="form-section">
                                        <h5 class="text-primary mb-3">
                                            <i class="ri-notification-3-line me-2"></i>
                                            Notification Settings
                                        </h5>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="deposit_notification" 
                                                           id="deposit_notification" <?= (!empty($settings['deposit_notification']) && $settings['deposit_notification'] == 1) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="deposit_notification">
                                                        <strong>Deposit Notifications</strong>
                                                    </label>
                                                    <div class="form-text">
                                                        Send notifications for deposit status changes
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="withdrawal_notification" 
                                                           id="withdrawal_notification" <?= (!empty($settings['withdrawal_notification']) && $settings['withdrawal_notification'] == 1) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="withdrawal_notification">
                                                        <strong>Withdrawal Notifications</strong>
                                                    </label>
                                                    <div class="form-text">
                                                        Send notifications for withdrawal status changes
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Recent Transactions -->
                                    <div class="form-section">
                                        <h5 class="text-primary mb-3">
                                            <i class="ri-time-line me-2"></i>
                                            Recent Transactions
                                        </h5>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <h6 class="text-success">Recent Deposits</h6>
                                                <div class="recent-transactions">
                                                    <?php if (mysqli_num_rows($recent_deposits) > 0): ?>
                                                        <table class="table table-sm table-borderless">
                                                            <thead>
                                                                <tr>
                                                                    <th>Order No</th>
                                                                    <th>User ID</th>
                                                                    <th>Amount</th>
                                                                    <th>Status</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php while ($deposit = mysqli_fetch_assoc($recent_deposits)): ?>
                                                                    <tr>
                                                                        <td><?= substr($deposit['dharavahi'], 0, 10) . '...'; ?></td>
                                                                        <td><?= $deposit['balakedara']; ?></td>
                                                                        <td>₹<?= number_format($deposit['motta'], 2); ?></td>
                                                                        <td>
                                                                            <?php if ($deposit['sthiti'] == 0): ?>
                                                                                <span class="badge bg-warning notification-badge">Pending</span>
                                                                            <?php else: ?>
                                                                                <span class="badge bg-success notification-badge">Success</span>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                    </tr>
                                                                <?php endwhile; ?>
                                                            </tbody>
                                                        </table>
                                                    <?php else: ?>
                                                        <div class="text-center text-muted py-3">
                                                            <i class="ri-inbox-line display-4"></i>
                                                            <p class="mt-2">No deposits found</p>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <h6 class="text-primary">Recent Withdrawals</h6>
                                                <div class="recent-transactions">
                                                    <?php if (mysqli_num_rows($recent_withdrawals) > 0): ?>
                                                        <table class="table table-sm table-borderless">
                                                            <thead>
                                                                <tr>
                                                                    <th>Order No</th>
                                                                    <th>User ID</th>
                                                                    <th>Amount</th>
                                                                    <th>Status</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php while ($withdrawal = mysqli_fetch_assoc($recent_withdrawals)): ?>
                                                                    <tr>
                                                                        <td><?= substr($withdrawal['dharavahi'], 0, 10) . '...'; ?></td>
                                                                        <td><?= $withdrawal['balakedara']; ?></td>
                                                                        <td>₹<?= number_format($withdrawal['motta'], 2); ?></td>
                                                                        <td>
                                                                            <?php if ($withdrawal['sthiti'] == 0): ?>
                                                                                <span class="badge bg-warning notification-badge">Pending</span>
                                                                            <?php elseif ($withdrawal['sthiti'] == 1): ?>
                                                                                <span class="badge bg-success notification-badge">Success</span>
                                                                            <?php else: ?>
                                                                                <span class="badge bg-danger notification-badge">Rejected</span>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                    </tr>
                                                                <?php endwhile; ?>
                                                            </tbody>
                                                        </table>
                                                    <?php else: ?>
                                                        <div class="text-center text-muted py-3">
                                                            <i class="ri-inbox-line display-4"></i>
                                                            <p class="mt-2">No withdrawals found</p>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-end mt-4">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="ri-save-line me-2"></i>Save Settings & Process
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Notification History -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="ri-history-line me-2"></i>
                                    Notification History
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if ($total_pages > 1): ?>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        Showing <?= ($offset + 1); ?> to <?= min($offset + $limit, $total_rows); ?> of <?= $total_rows; ?> entries
                                    </div>
                                    <nav>
                                        <ul class="pagination mb-0">
                                            <?php if ($page > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?= $page - 1; ?>">Previous</a>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                                <li class="page-item <?= $i == $page ? 'active' : ''; ?>">
                                                    <a class="page-link" href="?page=<?= $i; ?>"><?= $i; ?></a>
                                                </li>
                                            <?php endfor; ?>
                                            
                                            <?php if ($page < $total_pages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?= $page + 1; ?>">Next</a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                </div>
                                <?php endif; ?>

                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>#</th>
                                                <th>Type</th>
                                                <th>Order No</th>
                                                <th>User ID</th>
                                                <th>Amount</th>
                                                <th>Withdraw Type</th>
                                                <th>Notification</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (mysqli_num_rows($history_result) > 0): ?>
                                                <?php $counter = $offset + 1; ?>
                                                <?php while ($row = mysqli_fetch_assoc($history_result)): ?>
                                                    <tr>
                                                        <td><?= $counter++; ?></td>
                                                        <td>
                                                            <?php if ($row['transaction_type'] == 'deposit'): ?>
                                                                <span class="badge bg-success">Deposit</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-primary">Withdrawal</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?= htmlspecialchars($row['order_no']); ?></td>
                                                        <td><?= htmlspecialchars($row['user_id']); ?></td>
                                                        <td>₹<?= number_format($row['amount'], 2); ?></td>
                                                        <td><?= !empty($row['withdraw_type']) ? htmlspecialchars($row['withdraw_type']) : 'N/A'; ?></td>
                                                        <td>
                                                            <?php if ($row['notification_type'] == 'pending'): ?>
                                                                <span class="badge bg-warning">Pending</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-info">Completed</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($row['status'] == 'pending'): ?>
                                                                <span class="badge bg-warning">⏳ Pending</span>
                                                            <?php elseif ($row['status'] == 'success'): ?>
                                                                <span class="badge bg-success">✅ Success</span>
                                                            <?php elseif ($row['status'] == 'Successful'): ?>
                                                                <span class="badge bg-success">✅ Success</span>
                                                            <?php elseif ($row['status'] == 'rejected'): ?>
                                                                <span class="badge bg-danger">❌ Rejected</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary"><?= $row['status']; ?></span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?= date('d-m-Y H:i:s', strtotime($row['created_at'])); ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="9" class="text-center py-4">
                                                        <div class="text-muted">
                                                            <i class="ri-inbox-line display-4"></i>
                                                            <p class="mt-2 mb-0">No notification history found</p>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                    <?php require_once("footer.php"); ?>
                </div>
            </div>
        </div>
    </div>

<!-- Core JS -->
    <script src="assets/vendor/libs/jquery/jquery.js"></script>
    <script src="assets/vendor/libs/popper/popper.js"></script>
    <script src="assets/vendor/js/bootstrap.js"></script>
    <script src="assets/vendor/libs/node-waves/node-waves.js"></script>
    <script src="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="assets/vendor/libs/hammer/hammer.js"></script>
    <script src="assets/vendor/libs/i18n/i18n.js"></script>
    <script src="assets/vendor/libs/typeahead-js/typeahead.js"></script>
    <script src="assets/vendor/js/menu.js"></script>
    <script src="assets/vendor/libs/moment/moment.js"></script>
    <script src="assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>
    <script src="assets/vendor/libs/select2/select2.js"></script>
    <script src="assets/vendor/libs/@form-validation/popular.js"></script>
    <script src="assets/vendor/libs/@form-validation/bootstrap5.js"></script>
    <script src="assets/vendor/libs/@form-validation/auto-focus.js"></script>
    <script src="assets/vendor/libs/cleavejs/cleave.js"></script>
    <script src="assets/vendor/libs/cleavejs/cleave-phone.js"></script>
    <script src="assets/js/main.js"></script>
<script>
    document.getElementById('financeBotForm').addEventListener('submit', function(e) {
        const botToken = document.querySelector('input[name="bot_token"]').value;
        const chatId = document.querySelector('input[name="chat_id"]').value;
        
        if (!botToken || !chatId) {
            e.preventDefault();
            alert('Please fill Bot Token and Chat ID');
            return;
        }
        
        const depositNotification = document.getElementById('deposit_notification').checked;
        const withdrawalNotification = document.getElementById('withdrawal_notification').checked;
        
        if (!depositNotification && !withdrawalNotification) {
            e.preventDefault();
            alert('Please enable at least one notification type');
            return;
        }
        
        if (!confirm('Save settings and process all transactions?')) {
            e.preventDefault();
        }
    });
</script>
</body>
</html>