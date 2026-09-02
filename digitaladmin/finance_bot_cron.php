<?php
// cron_finance_bot.php
include("api/conn.php");

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

function processDepositNotifications($settings) {
    global $conn;
    
    // Get recent deposits
    $query = "SELECT * FROM thevani ORDER BY dinankavannuracisi DESC LIMIT 50";
    $result = mysqli_query($conn, $query);
    
    while ($deposit = mysqli_fetch_assoc($result)) {
        // Check if already notified for this status
        $check = mysqli_query($conn, "SELECT id FROM finance_notification_history 
                WHERE transaction_type = 'deposit' 
                AND order_no = '{$deposit['dharavahi']}' 
                AND status = '" . ($deposit['sthiti'] == 0 ? 'pending' : 'success') . "'");
        
        if (mysqli_num_rows($check) == 0) {
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
            
            mysqli_query($conn, "INSERT INTO finance_notification_history 
                (transaction_type, order_no, user_id, amount, status, notification_type, created_at) 
                VALUES ('deposit', '{$deposit['dharavahi']}', '{$deposit['balakedara']}', 
                '{$deposit['motta']}', '$status', '$notification_type', NOW())");
            
            sleep(1);
        }
    }
}

function processWithdrawalNotifications($settings) {
    global $conn;
    
    // Get recent withdrawals
    $query = "SELECT * FROM hintegedukolli ORDER BY dinankavannuracisi DESC LIMIT 50";
    $result = mysqli_query($conn, $query);
    
    while ($withdrawal = mysqli_fetch_assoc($result)) {
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
            
            mysqli_query($conn, "INSERT INTO finance_notification_history 
                (transaction_type, order_no, user_id, amount, withdraw_type, status, notification_type, created_at) 
                VALUES ('withdrawal', '{$withdrawal['dharavahi']}', '{$withdrawal['balakedara']}', 
                '{$withdrawal['motta']}', '$withdraw_type', '$status_text', '$notification_type', NOW())");
            
            sleep(1);
        }
    }
}

function getWithdrawType($madari) {
    switch ($madari) {
        case 1: return 'Bank Card';
        case 2: return 'UPI';
        case 3: return 'USDT';
        case 4: return 'E-Wallet';
        default: return 'Unknown';
    }
}

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

// Run the bot
processFinanceNotifications();

// Log execution
file_put_contents('finance_bot_cron.log', date('Y-m-d H:i:s') . " - Finance bot executed\n", FILE_APPEND);