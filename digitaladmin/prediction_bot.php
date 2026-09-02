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

// UTF-8 connection ensure karo
mysqli_set_charset($conn, "utf8mb4");

// CRON job function to run bot automatically (can be called via cron or manual trigger)
function runAutoPredictionCron() {
    global $conn;
    
    // Get settings
    $settings_query = mysqli_query($conn, "SELECT * FROM auto_prediction_settings LIMIT 1");
    if (mysqli_num_rows($settings_query) == 0) {
        error_log("Bot Error: No settings found in database");
        return false;
    }
    
    $settings = mysqli_fetch_assoc($settings_query);
    
    // Check if bot is active
    if (!isset($settings['is_active']) || $settings['is_active'] == 0) {
        error_log("Bot is not active");
        return false;
    }
    
    // Check current time within range
    date_default_timezone_set('Asia/Kolkata');
    $current_time = date('H:i');
    $current_time_ts = strtotime($current_time);
    $start_time_ts = strtotime($settings['start_time']);
    $end_time_ts = strtotime($settings['end_time']);
    
    // Handle overnight range (e.g., 22:00 to 06:00)
    if ($end_time_ts < $start_time_ts) {
        $end_time_ts += 86400; // Add 24 hours
        if ($current_time_ts < $start_time_ts) {
            $current_time_ts += 86400;
        }
    }
    
    if ($current_time_ts < $start_time_ts || $current_time_ts >= $end_time_ts) {
        error_log("Bot outside active hours: $current_time not between {$settings['start_time']} and {$settings['end_time']}");
        return false;
    }
    
    // Check bot token and chat ID
    if (empty($settings['bot_token']) || empty($settings['chat_id'])) {
        error_log("Bot Error: Bot token or chat ID not configured");
        return false;
    }
    
    $enabled_games = !empty($settings['enabled_games']) ? explode(',', $settings['enabled_games']) : [];
    
    if (empty($enabled_games)) {
        error_log("Bot Error: No games enabled");
        return false;
    }
    
    error_log("Auto bot starting with " . count($enabled_games) . " games enabled at " . date('Y-m-d H:i:s'));
    
    // Game table mapping with intervals
    $game_tables = [
        'gelluonduhogu_zehn' => [
            'table' => 'gelluonduhogu_zehn',
            'interval' => 30,
            'name' => 'Wingo 30Sec'
        ],
        'gelluonduhogu' => [
            'table' => 'gelluonduhogu',
            'interval' => 60,
            'name' => 'Wingo 1Min'
        ],
        'gelluonduhogu_drei' => [
            'table' => 'gelluonduhogu_drei',
            'interval' => 180,
            'name' => 'Wingo 3Min'
        ],
        'gelluonduhogu_funf' => [
            'table' => 'gelluonduhogu_funf',
            'interval' => 300,
            'name' => 'Wingo 5Min'
        ]
    ];
    
    $success_count = 0;
    
    foreach ($enabled_games as $game_type) {
        if (!isset($game_tables[$game_type])) {
            error_log("Bot Error: Invalid game type: $game_type");
            continue;
        }
        
        $game_info = $game_tables[$game_type];
        $table_name = $game_info['table'];
        $game_name = $game_info['name'];
        
        // Get latest period ID
        $res = mysqli_query($conn, "SELECT atadaaidi FROM $table_name ORDER BY kramasankhye DESC LIMIT 1");
        if (mysqli_num_rows($res) === 0) {
            error_log("❌ No data for $game_name");
            continue;
        }
        
        $row = mysqli_fetch_assoc($res);
        $latest_id = $row['atadaaidi'];

        // Calculate NEXT period ID (last period + 1)
        $prefix = substr($latest_id, 0, -4);
        $number = (int)substr($latest_id, -4);
        $next_number = $number + 1;
        $next_period_id = $prefix . str_pad($next_number, 4, "0", STR_PAD_LEFT);

        // Check duplicate - FIXED: Check for next period ID
        $check = mysqli_query($conn, "SELECT * FROM auto_prediction_history WHERE period_id = '$next_period_id' AND game_type = '$game_type' AND DATE(created_at) = CURDATE()");
        if (mysqli_num_rows($check) > 0) {
            error_log("⏭️ Already predicted $game_name for $next_period_id");
            continue;
        }

        // Generate prediction
        $digit = rand(0, 9);
        $color = ($digit == 5) ? "green,violet" : (in_array($digit, [1, 3, 7, 9]) ? "green" : "red");
        $type = ($digit <= 4) ? "Small" : "Big";
        $amount = $settings['amount'];
        $step = $settings['step'];

        // Save history first
        $status = 'pending';
        $insertHistory = mysqli_query($conn, "INSERT INTO auto_prediction_history 
            (game_type, period_id, prediction_number, prediction_color, amount, step, type, status, created_at) 
            VALUES ('$game_type', '$next_period_id', '$digit', '$color', $amount, $step, '$type', '$status', NOW())");
        
        if (!$insertHistory) {
            error_log("❌ Failed to save history for $game_name: " . mysqli_error($conn));
            continue;
        }

        // Prepare message
        $message = str_replace(
            ['{period_id}', '{color}', '{number}', '{type}', '{amount}', '{step}'],
            [$next_period_id, $color, $digit, $type, $amount, $step],
            $settings['prediction_message']
        );

        error_log("Sending prediction for $game_name - Period: $next_period_id, Digit: $digit, Color: $color");

        // Send Telegram
        $telegram_sent = sendTelegram($message, $settings['bot_token'], $settings['chat_id']);
        
        // Update status based on telegram result
        $status = $telegram_sent ? 'sent' : 'failed';
        mysqli_query($conn, "UPDATE auto_prediction_history SET status = '$status' WHERE period_id = '$next_period_id' AND game_type = '$game_type'");
        
        if ($telegram_sent) {
            $success_count++;
            error_log("✅ Telegram message sent successfully for $game_name - Period: $next_period_id");
        } else {
            error_log("❌ Telegram message failed for $game_name - Period: $next_period_id");
        }
        
        // Small delay between games
        usleep(100000); // 0.1 second
    }
    
    error_log("Auto bot execution completed. Successfully sent: $success_count messages");
    return $success_count > 0;
}

// Handle RUN/STOP bot action
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    if ($action == 'run_now') {
        // Run bot immediately
        runPredictionBotNow();
        $_SESSION['success'] = "Bot executed successfully!";
        header("Location: prediction_bot.php");
        exit();
        
    } elseif ($action == 'toggle_status') {
        // Toggle bot active/inactive
        $status = isset($_GET['status']) ? intval($_GET['status']) : 0;
        mysqli_query($conn, "UPDATE auto_prediction_settings SET is_active = $status");
        
        $_SESSION['success'] = $status ? "Bot activated!" : "Bot deactivated!";
        header("Location: prediction_bot.php");
        exit();
        
    } elseif ($action == 'run_cron') {
        // Run auto prediction cron
        runAutoPredictionCron();
        $_SESSION['success'] = "Auto prediction cron executed!";
        header("Location: prediction_bot.php");
        exit();
    }
}

// Handle Delete Actions
if (isset($_POST['delete_action'])) {
    $delete_action = $_POST['delete_action'];
    
    if ($delete_action == 'delete_single' && isset($_POST['history_id'])) {
        $history_id = intval($_POST['history_id']);
        mysqli_query($conn, "DELETE FROM auto_prediction_history WHERE id = $history_id");
        $_SESSION['success'] = "History deleted successfully!";
        
    } elseif ($delete_action == 'delete_multiple' && isset($_POST['selected_ids'])) {
        $selected_ids = implode(',', array_map('intval', $_POST['selected_ids']));
        mysqli_query($conn, "DELETE FROM auto_prediction_history WHERE id IN ($selected_ids)");
        $_SESSION['success'] = "Selected histories deleted successfully!";
        
    } elseif ($delete_action == 'clear_all') {
        mysqli_query($conn, "DELETE FROM auto_prediction_history");
        $_SESSION['success'] = "All history cleared successfully!";
    }
    
    header("Location: prediction_bot.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bot_token'])) {
    $bot_token = mysqli_real_escape_string($conn, $_POST['bot_token']);
    $chat_id = mysqli_real_escape_string($conn, $_POST['chat_id']);
    
    // Handle games array - ALL games available
    $enabled_games = [];
    if (isset($_POST['games']) && is_array($_POST['games'])) {
        $enabled_games = $_POST['games'];
    }
    $enabled_games_str = implode(',', $enabled_games);
    
    // UTF-8 safe message handling
    $prediction_message = isset($_POST['prediction_message']) ? 
        mysqli_real_escape_string($conn, $_POST['prediction_message']) : 
        '';
    
    $amount = intval($_POST['amount']);
    $step = intval($_POST['step']);
    
    // New schedule fields
    $schedule_type = mysqli_real_escape_string($conn, $_POST['schedule_type']);
    $start_time = !empty($_POST['start_time']) ? $_POST['start_time'] : date('H:i');
    $end_time = !empty($_POST['end_time']) ? $_POST['end_time'] : date('H:i', strtotime('+5 hours'));
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Time validation
    $start_timestamp = strtotime($start_time);
    $end_timestamp = strtotime($end_time);
    $min_difference = 5 * 60; // 5 minutes in seconds
    $max_difference = 48 * 3600; // 48 hours in seconds
    
    // Handle overnight time
    if ($end_timestamp < $start_timestamp) {
        $end_timestamp += 86400; // Add 24 hours for comparison
    }
    
    $time_difference = $end_timestamp - $start_timestamp;
    
    if ($time_difference < $min_difference) {
        $_SESSION['error'] = "End time must be at least 5 minutes after start time!";
        header("Location: prediction_bot.php");
        exit();
    }
    
    if ($time_difference > $max_difference) {
        $_SESSION['error'] = "Maximum time difference is 48 hours!";
        header("Location: prediction_bot.php");
        exit();
    }
    
    // Reset end time to normal format if overnight
    if ($end_timestamp > 86400) {
        $end_time = date('H:i', strtotime($end_time));
    }
    
    // Check if settings exist
    $check = mysqli_query($conn, "SELECT id FROM auto_prediction_settings LIMIT 1");
    
    if (mysqli_num_rows($check) > 0) {
        $query = "UPDATE auto_prediction_settings SET 
                  bot_token = '$bot_token',
                  chat_id = '$chat_id',
                  enabled_games = '$enabled_games_str',
                  prediction_message = '$prediction_message',
                  amount = $amount,
                  step = $step,
                  schedule_type = '$schedule_type',
                  start_time = '$start_time',
                  end_time = '$end_time',
                  is_active = $is_active,
                  updated_at = NOW()";
    } else {
        $query = "INSERT INTO auto_prediction_settings 
                  (bot_token, chat_id, enabled_games, prediction_message, amount, step, 
                   schedule_type, start_time, end_time, is_active, status) 
                  VALUES ('$bot_token', '$chat_id', '$enabled_games_str', '$prediction_message', $amount, $step,
                          '$schedule_type', '$start_time', '$end_time', $is_active, 'active')";
    }
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Settings saved successfully!";
    } else {
        $_SESSION['error'] = "Error saving settings: " . mysqli_error($conn);
    }
    
    header("Location: prediction_bot.php");
    exit();
}

function runPredictionBotNow() {
    global $conn;
    
    // Get settings
    $settings_query = mysqli_query($conn, "SELECT * FROM auto_prediction_settings LIMIT 1");
    if (mysqli_num_rows($settings_query) == 0) {
        error_log("Bot Error: No settings found in database");
        return false;
    }
    
    $settings = mysqli_fetch_assoc($settings_query);
    
    // Check bot token and chat ID
    if (empty($settings['bot_token']) || empty($settings['chat_id'])) {
        error_log("Bot Error: Bot token or chat ID not configured");
        return false;
    }
    
    $enabled_games = !empty($settings['enabled_games']) ? explode(',', $settings['enabled_games']) : [];
    
    if (empty($enabled_games)) {
        error_log("Bot Error: No games enabled");
        return false;
    }
    
    error_log("Manual bot starting with " . count($enabled_games) . " games enabled");
    
    // Game table mapping with intervals
    $game_tables = [
        'gelluonduhogu_zehn' => [
            'table' => 'gelluonduhogu_zehn',
            'interval' => 30,
            'name' => 'Wingo 30Sec'
        ],
        'gelluonduhogu' => [
            'table' => 'gelluonduhogu',
            'interval' => 60,
            'name' => 'Wingo 1Min'
        ],
        'gelluonduhogu_drei' => [
            'table' => 'gelluonduhogu_drei',
            'interval' => 180,
            'name' => 'Wingo 3Min'
        ],
        'gelluonduhogu_funf' => [
            'table' => 'gelluonduhogu_funf',
            'interval' => 300,
            'name' => 'Wingo 5Min'
        ]
    ];
    
    $success_count = 0;
    
    foreach ($enabled_games as $game_type) {
        if (!isset($game_tables[$game_type])) {
            error_log("Bot Error: Invalid game type: $game_type");
            continue;
        }
        
        $game_info = $game_tables[$game_type];
        $table_name = $game_info['table'];
        $game_name = $game_info['name'];
        
        // Get latest period ID - Corrected to get the LAST result (not latest by ID)
        $res = mysqli_query($conn, "SELECT atadaaidi FROM $table_name ORDER BY kramasankhye DESC LIMIT 1");
        if (mysqli_num_rows($res) === 0) {
            error_log("❌ No data for $game_name");
            continue;
        }
        
        $row = mysqli_fetch_assoc($res);
        $latest_id = $row['atadaaidi'];

        // Calculate NEXT period ID (last prediction + 1)
        $prefix = substr($latest_id, 0, -4);
        $number = (int)substr($latest_id, -4);
        $next_number = $number + 1;
        $next_period_id = $prefix . str_pad($next_number, 4, "0", STR_PAD_LEFT);

        // Log the IDs for debugging
        error_log("Game: $game_name - Last Period: $latest_id - Next Period: $next_period_id");

        // Check duplicate - FIXED: Check for next period ID
        $check = mysqli_query($conn, "SELECT * FROM auto_prediction_history WHERE period_id = '$next_period_id' AND game_type = '$game_type' AND DATE(created_at) = CURDATE()");
        if (mysqli_num_rows($check) > 0) {
            error_log("⏭️ Already predicted $game_name for $next_period_id");
            continue;
        }

        // Generate prediction
        date_default_timezone_set("Asia/Kolkata");
        $digit = rand(0, 9);
        $color = ($digit == 5) ? "green,violet" : (in_array($digit, [1, 3, 7, 9]) ? "green" : "red");
        $type = ($digit <= 4) ? "Small" : "Big";
        $amount = $settings['amount'];
        $step = $settings['step'];

        // Save history first
        $status = 'pending';
        $insertHistory = mysqli_query($conn, "INSERT INTO auto_prediction_history 
            (game_type, period_id, prediction_number, prediction_color, amount, step, type, status, created_at) 
            VALUES ('$game_type', '$next_period_id', '$digit', '$color', $amount, $step, '$type', '$status', NOW())");
        
        if (!$insertHistory) {
            error_log("❌ Failed to save history for $game_name: " . mysqli_error($conn));
            continue;
        }

        // Prepare message
        $message = str_replace(
            ['{period_id}', '{color}', '{number}', '{type}', '{amount}', '{step}'],
            [$next_period_id, $color, $digit, $type, $amount, $step],
            $settings['prediction_message']
        );

        error_log("Sending prediction for $game_name - Period: $next_period_id, Digit: $digit, Color: $color");

        // Send Telegram
        $telegram_sent = sendTelegram($message, $settings['bot_token'], $settings['chat_id']);
        
        // Update status based on telegram result
        $status = $telegram_sent ? 'sent' : 'failed';
        mysqli_query($conn, "UPDATE auto_prediction_history SET status = '$status' WHERE period_id = '$next_period_id' AND game_type = '$game_type'");
        
        if ($telegram_sent) {
            $success_count++;
            error_log("✅ Telegram message sent successfully for $game_name - Period: $next_period_id");
        } else {
            error_log("❌ Telegram message failed for $game_name - Period: $next_period_id");
        }
        
        sleep(1);
    }
    
    error_log("Manual bot execution completed. Successfully sent: $success_count messages");
    return $success_count > 0;
}

// Function to check for new periods and send predictions automatically
function checkAndSendNewPeriodPredictions() {
    global $conn;
    
    // Get settings
    $settings_query = mysqli_query($conn, "SELECT * FROM auto_prediction_settings LIMIT 1");
    if (mysqli_num_rows($settings_query) == 0) {
        return false;
    }
    
    $settings = mysqli_fetch_assoc($settings_query);
    
    // Check if bot is active
    if (!isset($settings['is_active']) || $settings['is_active'] == 0) {
        return false;
    }
    
    // Check current time within range
    date_default_timezone_set('Asia/Kolkata');
    $current_time = date('H:i');
    $current_time_ts = strtotime($current_time);
    $start_time_ts = strtotime($settings['start_time']);
    $end_time_ts = strtotime($settings['end_time']);
    
    // Handle overnight range
    if ($end_time_ts < $start_time_ts) {
        $end_time_ts += 86400;
        if ($current_time_ts < $start_time_ts) {
            $current_time_ts += 86400;
        }
    }
    
    if ($current_time_ts < $start_time_ts || $current_time_ts >= $end_time_ts) {
        return false;
    }
    
    $enabled_games = !empty($settings['enabled_games']) ? explode(',', $settings['enabled_games']) : [];
    
    if (empty($enabled_games)) {
        return false;
    }
    
    // Game table mapping
    $game_tables = [
        'gelluonduhogu_zehn' => 'gelluonduhogu_zehn',
        'gelluonduhogu' => 'gelluonduhogu',
        'gelluonduhogu_drei' => 'gelluonduhogu_drei',
        'gelluonduhogu_funf' => 'gelluonduhogu_funf'
    ];
    
    $predictions_sent = 0;
    
    foreach ($enabled_games as $game_type) {
        if (!isset($game_tables[$game_type])) {
            continue;
        }
        
        $table_name = $game_tables[$game_type];
        
        // Get latest period from game table
        $current_period_res = mysqli_query($conn, "SELECT atadaaidi FROM $table_name ORDER BY kramasankhye DESC LIMIT 1");
        if (mysqli_num_rows($current_period_res) == 0) {
            continue;
        }
        
        $current_period = mysqli_fetch_assoc($current_period_res)['atadaaidi'];
        
        // Get last predicted period for this game today
        $last_predicted_res = mysqli_query($conn, "SELECT period_id FROM auto_prediction_history 
            WHERE game_type = '$game_type' AND DATE(created_at) = CURDATE() 
            ORDER BY id DESC LIMIT 1");
        
        $last_predicted = $last_predicted_res && mysqli_num_rows($last_predicted_res) > 0 
            ? mysqli_fetch_assoc($last_predicted_res)['period_id'] 
            : null;
        
        // If we haven't predicted for this current period yet, send prediction for next period
        if ($last_predicted != $current_period) {
            // Calculate next period ID (current + 1)
            $prefix = substr($current_period, 0, -4);
            $number = (int)substr($current_period, -4);
            $next_number = $number + 1;
            $next_period_id = $prefix . str_pad($next_number, 4, "0", STR_PAD_LEFT);
            
            // Check if already predicted for next period
            $check = mysqli_query($conn, "SELECT * FROM auto_prediction_history 
                WHERE period_id = '$next_period_id' AND game_type = '$game_type' AND DATE(created_at) = CURDATE()");
            
            if (mysqli_num_rows($check) == 0) {
                // Generate prediction
                $digit = rand(0, 9);
                $color = ($digit == 5) ? "green,violet" : (in_array($digit, [1, 3, 7, 9]) ? "green" : "red");
                $type = ($digit <= 4) ? "Small" : "Big";
                $amount = $settings['amount'];
                $step = $settings['step'];
                
                // Save history
                $status = 'pending';
                mysqli_query($conn, "INSERT INTO auto_prediction_history 
                    (game_type, period_id, prediction_number, prediction_color, amount, step, type, status, created_at) 
                    VALUES ('$game_type', '$next_period_id', '$digit', '$color', $amount, $step, '$type', '$status', NOW())");
                
                // Prepare and send message
                $message = str_replace(
                    ['{period_id}', '{color}', '{number}', '{type}', '{amount}', '{step}'],
                    [$next_period_id, $color, $digit, $type, $amount, $step],
                    $settings['prediction_message']
                );
                
                $telegram_sent = sendTelegram($message, $settings['bot_token'], $settings['chat_id']);
                
                // Update status
                $status = $telegram_sent ? 'sent' : 'failed';
                mysqli_query($conn, "UPDATE auto_prediction_history SET status = '$status' 
                    WHERE period_id = '$next_period_id' AND game_type = '$game_type'");
                
                if ($telegram_sent) {
                    $predictions_sent++;
                    error_log("Auto-prediction sent for $game_type - Period: $next_period_id");
                }
            }
        }
    }
    
    return $predictions_sent > 0;
}

// Enhanced Telegram function with logging
function sendTelegram($message, $bot_token, $chat_id) {
    global $conn;
    
    // Validate inputs
    if (empty($bot_token) || empty($chat_id) || empty($message)) {
        error_log("Telegram Error: Missing parameters");
        return false;
    }
    
    // Clean bot token (remove spaces)
    $bot_token = trim($bot_token);
    $chat_id = trim($chat_id);
    
    $url = "https://api.telegram.org/bot$bot_token/sendMessage";
    $data = [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; Bot)'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ($http_code == 200);
}

// Check for new periods and send predictions (called on every page load)
checkAndSendNewPeriodPredictions();

// Get current settings
$settings = [];
$result = mysqli_query($conn, "SELECT * FROM auto_prediction_settings LIMIT 1");
if (mysqli_num_rows($result) > 0) {
    $settings = mysqli_fetch_assoc($result);
    $enabled_games = !empty($settings['enabled_games']) ? explode(',', $settings['enabled_games']) : [];
} else {
    $enabled_games = [];
    $settings = [
        'bot_token' => '',
        'chat_id' => '',
        'prediction_message' => "🎯 <b>Prediction: {period_id} {color}</b>\n🎲 Number: <b>{number} ({type})</b>\n💸 Amount: ₹{amount} (x{step})",
        'amount' => 10,
        'step' => 1,
        'schedule_type' => 'continuous',
        'start_time' => date('H:i'),
        'end_time' => date('H:i', strtotime('+5 hours')),
        'is_active' => 1
    ];
}

// Game types with intervals
$game_types = [
    'gelluonduhogu_zehn' => ['name' => 'Wingo 30Sec', 'interval' => 30],
    'gelluonduhogu' => ['name' => 'Wingo 1Min', 'interval' => 60],
    'gelluonduhogu_drei' => ['name' => 'Wingo 3Min', 'interval' => 180],
    'gelluonduhogu_funf' => ['name' => 'Wingo 5Min', 'interval' => 300]
];

// Schedule options
$schedule_options = [
    'continuous' => 'Continuous (Within Time Range)',
    '10min' => '10 Minutes (All games)',
    '1hour' => '1 Hour (All games)',
    '6hour' => '6 Hours (All games)',
    '24hour' => '24 Hours (All games)'
];

// Calculate next run time
function calculateNextRunTime($settings) {
    if (!isset($settings['is_active']) || $settings['is_active'] == 0) {
        return 'Bot is inactive';
    }
    
    date_default_timezone_set('Asia/Kolkata');
    $now = time();
    $current_time = date('H:i', $now);
    $current_time_ts = strtotime($current_time);
    $start_time_ts = strtotime($settings['start_time']);
    $end_time_ts = strtotime($settings['end_time']);
    
    // Handle overnight range
    if ($end_time_ts < $start_time_ts) {
        $end_time_ts += 86400;
        if ($current_time_ts < $start_time_ts) {
            $current_time_ts += 86400;
        }
    }
    
    // If current time is before start time
    if ($current_time_ts < $start_time_ts) {
        return date('H:i', $start_time_ts) . ' (Today)';
    }
    
    // If current time is within time range
    if ($current_time_ts >= $start_time_ts && $current_time_ts < $end_time_ts) {
        $schedule_type = isset($settings['schedule_type']) ? $settings['schedule_type'] : 'continuous';
        
        switch($schedule_type) {
            case 'continuous':
                return 'Running Now (Continuous)';
                
            case '10min':
                $current_minute = (int)date('i', $now);
                $next_minute = ceil($current_minute / 10) * 10;
                if ($next_minute >= 60) $next_minute = 0;
                return sprintf("%02d:%02d", date('H', $now), $next_minute);
                
            case '1hour':
                $next_hour = ((int)date('H', $now) + 1) % 24;
                return sprintf("%02d:00", $next_hour);
                
            case '6hour':
                $current_hour = (int)date('H', $now);
                $next_hour = ceil($current_hour / 6) * 6;
                if ($next_hour >= 24) $next_hour = 0;
                return sprintf("%02d:00", $next_hour);
                
            case '24hour':
                $tomorrow_start = date('Y-m-d', strtotime('+1 day')) . ' ' . $settings['start_time'];
                return $settings['start_time'] . ' (Tomorrow)';
                
            default:
                return 'Running Now';
        }
    }
    
    // If current time is after end time
    $tomorrow_start = date('Y-m-d', strtotime('+1 day')) . ' ' . $settings['start_time'];
    return $settings['start_time'] . ' (Tomorrow)';
}

$next_run_time = calculateNextRunTime($settings);

// Get bot status
function getBotStatus($settings) {
    if (!isset($settings['is_active']) || $settings['is_active'] == 0) {
        return ['status' => 'inactive', 'class' => 'danger', 'icon' => '⭕', 'text' => 'Bot is inactive'];
    }
    
    date_default_timezone_set('Asia/Kolkata');
    $now = time();
    $current_time = date('H:i', $now);
    $current_time_ts = strtotime($current_time);
    $start_time_ts = isset($settings['start_time']) ? strtotime($settings['start_time']) : strtotime('09:00');
    $end_time_ts = isset($settings['end_time']) ? strtotime($settings['end_time']) : strtotime('21:00');
    
    // Handle overnight range
    if ($end_time_ts < $start_time_ts) {
        $end_time_ts += 86400;
        if ($current_time_ts < $start_time_ts) {
            $current_time_ts += 86400;
        }
    }
    
    if ($current_time_ts >= $start_time_ts && $current_time_ts < $end_time_ts) {
        return ['status' => 'active', 'class' => 'success', 'icon' => '✅', 'text' => 'Bot is actively sending predictions'];
    } else {
        return ['status' => 'waiting', 'class' => 'warning', 'icon' => '⏰', 'text' => 'Bot waiting for next active period'];
    }
}

$bot_status = getBotStatus($settings);

// ========== HISTORY SECTION WITH SEARCH AND PAGINATION ==========

// Search parameters
$search_query = '';
$search_sql = '';
$search_params = [];

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = mysqli_real_escape_string($conn, $_GET['search']);
    $search_sql = " AND (game_type LIKE '%$search_term%' 
                OR period_id LIKE '%$search_term%' 
                OR prediction_number LIKE '%$search_term%'
                OR prediction_color LIKE '%$search_term%'
                OR type LIKE '%$search_term%'
                OR status LIKE '%$search_term%')";
    $search_query = $search_term;
}

// Date filtering
$date_filter = '';
if (isset($_GET['history_date']) && !empty($_GET['history_date'])) {
    $history_date = mysqli_real_escape_string($conn, $_GET['history_date']);
    $date_filter = " AND DATE(created_at) = '$history_date'";
}

// Status filtering
$status_filter = '';
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $status = mysqli_real_escape_string($conn, $_GET['status']);
    $status_filter = " AND status = '$status'";
}

// Game type filtering
$game_filter = '';
if (isset($_GET['game_type']) && !empty($_GET['game_type'])) {
    $game_type = mysqli_real_escape_string($conn, $_GET['game_type']);
    $game_filter = " AND game_type = '$game_type'";
}

// Pagination settings
$limit = 20; // 20 per page
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Build WHERE clause
$where_clause = "WHERE 1 $search_sql $date_filter $status_filter $game_filter";

// Get total count
$count_query = "SELECT COUNT(*) as total FROM auto_prediction_history $where_clause";
$count_result = mysqli_query($conn, $count_query);
$total_rows = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_rows / $limit);

// Get history data
$history_query = "SELECT * FROM auto_prediction_history $where_clause ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$history_result = mysqli_query($conn, $history_query);

// Get unique game types for filter dropdown
$game_types_result = mysqli_query($conn, "SELECT DISTINCT game_type FROM auto_prediction_history ORDER BY game_type");
$unique_game_types = [];
while ($row = mysqli_fetch_assoc($game_types_result)) {
    $unique_game_types[] = $row['game_type'];
}

// Status options
$status_options = ['sent', 'failed', 'pending'];
?>

<!doctype html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr" 
    data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template" data-style="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Auto Bot Prediction Management</title>
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
        .game-container { border: 1px solid #d1d7e0; border-radius: 0.5rem; padding: 1rem; }
        .selected-games { min-height: 50px; border: 1px dashed #d1d7e0; border-radius: 0.35rem; padding: 0.75rem; margin-top: 1rem; }
        .game-tag { display: inline-block; background: #7367f0; color: white; padding: 0.25rem 0.75rem; border-radius: 1rem; margin: 0.25rem; font-size: 0.875rem; }
        .game-tag .remove-btn { margin-left: 0.5rem; cursor: pointer; font-weight: bold; }
        .bot-status-card { border-left: 4px solid; padding: 1rem; margin-bottom: 1rem; }
        .bot-status-card.success { border-color: #28a745; background: rgba(40, 167, 69, 0.1); }
        .bot-status-card.warning { border-color: #ffc107; background: rgba(255, 193, 7, 0.1); }
        .bot-status-card.danger { border-color: #dc3545; background: rgba(220, 53, 69, 0.1); }
        .next-run-badge { font-size: 1.2rem; font-weight: bold; }
        .time-range-badge { font-size: 0.9rem; padding: 0.25rem 0.75rem; }
        .interval-badge { font-size: 0.8rem; background: #e9ecef; color: #495057; margin-left: 5px; }
        .search-box { max-width: 400px; }
        .table-checkbox { width: 40px; }
        .action-buttons { white-space: nowrap; }
        .bulk-actions { background: #f8f9fa; padding: 1rem; border-radius: 0.35rem; margin-bottom: 1rem; }
        .select-all-checkbox { margin-right: 10px; }
        .status-filter-badge { cursor: pointer; }
        .time-input-group { position: relative; }
        .time-input-group .btn-now { position: absolute; right: 5px; top: 5px; z-index: 10; font-size: 0.8rem; padding: 0.25rem 0.5rem; }
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

                        <!-- Bot Status -->
                        <div class="bot-status-card <?= $bot_status['class']; ?>">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-1">
                                        <span class="me-2"><?= $bot_status['icon']; ?></span>
                                        Bot Status: <?= ucfirst($bot_status['status']); ?>
                                    </h5>
                                    <p class="mb-0"><?= $bot_status['text']; ?></p>
                                    
                                    <p class="mb-1">
                                        <span class="badge bg-primary time-range-badge">
                                            <i class="ri-time-line me-1"></i>
                                            Active Hours: <?= isset($settings['start_time']) ? $settings['start_time'] : '09:00'; ?> to <?= isset($settings['end_time']) ? $settings['end_time'] : '21:00'; ?>
                                        </span>
                                    </p>
                                    
                                    <p class="mb-0"><strong>Next Run:</strong> 
                                        <span class="next-run-badge text-<?= $bot_status['class']; ?>">
                                            <?= $next_run_time; ?>
                                        </span>
                                    </p>
                                    <p class="mb-0 mt-2">
                                        <small class="text-muted">
                                            <i class="ri-information-line"></i> Bot automatically sends predictions when new periods are detected
                                        </small>
                                    </p>
                                </div>
                                <div class="d-flex gap-2">
                                    <?php if (isset($settings['is_active']) && $settings['is_active'] == 1): ?>
                                        <a href="?action=toggle_status&status=0" class="btn btn-danger">
                                            <i class="ri-stop-circle-line me-1"></i> Stop Bot
                                        </a>
                                    <?php else: ?>
                                        <a href="?action=toggle_status&status=1" class="btn btn-success">
                                            <i class="ri-play-circle-line me-1"></i> Start Bot
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="?action=run_now" class="btn btn-primary" onclick="return confirm('Run bot immediately?')">
                                        <i class="ri-play-line me-1"></i> Run Now
                                    </a>
                                    <a href="?action=run_cron" class="btn btn-warning" onclick="return confirm('Run auto prediction check?')">
                                        <i class="ri-refresh-line me-1"></i> Check Now
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title mb-0">
                                    <i class="ri-robot-line me-2"></i>
                                    Auto Bot Prediction Management
                                </h4>
                                <p class="text-muted mb-0">Bot automatically sends predictions when new periods are detected within active time range</p>
                            </div>
                            <div class="card-body">
                                <form method="POST" id="predictionForm">
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

                                    <!-- Game Selection -->
                                    <div class="form-section">
                                        <h5 class="text-primary mb-3">
                                            <i class="ri-gamepad-line me-2"></i>
                                            Select Games (All Available)
                                        </h5>
                                        <div class="game-container">
                                            <label class="form-label">Available Games</label>
                                            <select class="form-select mb-3" id="gameSelector">
                                                <option value="">-- Select Game to Add --</option>
                                                <?php foreach ($game_types as $key => $game_info): ?>
                                                    <?php if (!in_array($key, $enabled_games)): ?>
                                                        <option value="<?= $key; ?>">
                                                            <?= $game_info['name']; ?> 
                                                            <span class="interval-badge"><?= $game_info['interval'] ?>s</span>
                                                        </option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="button" class="btn btn-sm btn-primary" id="addGameBtn">
                                                <i class="ri-add-line me-1"></i>Add Game
                                            </button>
                                            
                                            <div class="selected-games mt-3">
                                                <div class="mb-2"><strong>Selected Games:</strong></div>
                                                <div id="selectedGamesContainer">
                                                    <?php foreach ($enabled_games as $game_key): ?>
                                                        <?php if (isset($game_types[$game_key])): ?>
                                                            <span class="game-tag">
                                                                <?= $game_types[$game_key]['name']; ?>
                                                                <span class="interval-badge"><?= $game_types[$game_key]['interval'] ?>s</span>
                                                                <input type="hidden" name="games[]" value="<?= $game_key; ?>">
                                                                <span class="remove-btn" onclick="removeGame(this)">×</span>
                                                            </span>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </div>
                                                <?php if (empty($enabled_games)): ?>
                                                    <div class="text-muted">No games selected yet</div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="alert alert-info mt-2">
                                                <i class="ri-information-line me-2"></i>
                                                Select any combination of games. All games work with all schedule types.
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Schedule Settings -->
                                    <div class="form-section">
                                        <h5 class="text-primary mb-3">
                                            <i class="ri-time-line me-2"></i>
                                            Schedule Settings
                                        </h5>
                                        
                                        <div class="row">
                                            <div class="col-md-12 mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="is_active" 
                                                           id="is_active" <?= (isset($settings['is_active']) && $settings['is_active'] == 1) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="is_active">
                                                        <strong>Enable Auto Prediction</strong>
                                                    </label>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Schedule Type</label>
                                                <select class="form-control" name="schedule_type" id="schedule_type" required>
                                                    <?php foreach ($schedule_options as $key => $name): ?>
                                                        <option value="<?= $key; ?>" 
                                                                <?= (isset($settings['schedule_type']) && $settings['schedule_type'] == $key) ? 'selected' : '' ?>>
                                                            <?= $name; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="form-text" id="schedule_hint">All games work with all schedule types</div>
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Next Run Time</label>
                                                <div class="form-control bg-light">
                                                    <strong id="next_run_display"><?= $next_run_time; ?></strong>
                                                </div>
                                                <div class="form-text" id="time_range_status"></div>
                                            </div>
                                        </div>
                                        
                                        <div class="row mt-3">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Start Time <span class="text-danger">*</span></label>
                                                <div class="time-input-group">
                                                    <input type="time" class="form-control" name="start_time" 
                                                           id="start_time" value="<?= !empty($settings['start_time']) ? $settings['start_time'] : date('H:i'); ?>" required>
                                                    <button type="button" class="btn btn-sm btn-outline-primary btn-now" onclick="setNowTime('start_time')">
                                                        Now
                                                    </button>
                                                </div>
                                                <div class="form-text" id="start_time_hint">Bot will start sending predictions from this time</div>
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">End Time <span class="text-danger">*</span></label>
                                                <div class="time-input-group">
                                                    <input type="time" class="form-control" name="end_time" 
                                                           id="end_time" value="<?= !empty($settings['end_time']) ? $settings['end_time'] : date('H:i', strtotime('+5 hours')); ?>" required>
                                                    <button type="button" class="btn btn-sm btn-outline-primary btn-now" onclick="setNowTime('end_time')">
                                                        Now
                                                    </button>
                                                </div>
                                                <div class="form-text" id="end_time_hint">Bot will stop sending predictions at this time</div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="alert alert-warning">
                                                    <i class="ri-alert-line me-2"></i>
                                                    <strong>Time Range Requirements:</strong>
                                                    <ul class="mb-0 mt-1">
                                                        <li>Minimum difference: 5 minutes</li>
                                                        <li>Maximum difference: 48 hours</li>
                                                        <li>Bot runs automatically when new periods are detected</li>
                                                        <li>All schedule types work with all games</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Prediction Settings -->
                                    <div class="form-section">
                                        <h5 class="text-primary mb-3">
                                            <i class="ri-settings-3-line me-2"></i>
                                            Prediction Settings
                                        </h5>
                                        <div class="row">
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label">Amount</label>
                                                <input type="number" class="form-control" name="amount" 
                                                       value="<?= isset($settings['amount']) ? $settings['amount'] : 10; ?>" min="1" required>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label">Step</label>
                                                <input type="number" class="form-control" name="step" 
                                                       value="<?= isset($settings['step']) ? $settings['step'] : 1; ?>" min="1" required>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Message Template -->
                                    <div class="form-section">
                                        <h5 class="text-primary mb-3">
                                            <i class="ri-message-2-line me-2"></i>
                                            Message Template
                                        </h5>
                                        <div class="mb-3">
                                            <textarea class="form-control" name="prediction_message" rows="6" style="font-family: monospace;"><?= htmlspecialchars(isset($settings['prediction_message']) ? $settings['prediction_message'] : ''); ?></textarea>
                                            <div class="form-text">
                                                Available Variables: 
                                                <code>{period_id}</code>, 
                                                <code>{color}</code>, 
                                                <code>{number}</code>, 
                                                <code>{type}</code>, 
                                                <code>{amount}</code>, 
                                                <code>{step}</code>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-end mt-4">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="ri-save-line me-2"></i>Save Settings
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- History -->
                        <div class="card mt-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="ri-history-line me-2"></i>
                                    Prediction History
                                </h5>
                                <div class="d-flex gap-2">
                                    <form method="GET" class="d-flex gap-2 me-3">
                                        <div class="input-group input-group-merge search-box">
                                            <span class="input-group-text"><i class="ri-search-line"></i></span>
                                            <input type="text" class="form-control" name="search" 
                                                   placeholder="Search..." value="<?= htmlspecialchars($search_query); ?>">
                                            <button type="submit" class="btn btn-primary">Search</button>
                                            <?php if ($search_query || isset($_GET['history_date']) || isset($_GET['status']) || isset($_GET['game_type'])): ?>
                                                <a href="prediction_bot.php" class="btn btn-secondary">Clear</a>
                                            <?php endif; ?>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="card-body">
                                
                                <!-- Bulk Actions -->
                                <div class="bulk-actions" id="bulkActions" style="display: none;">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span id="selectedCount">0</span> items selected
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-danger btn-sm" onclick="deleteSelected()">
                                                <i class="ri-delete-bin-line me-1"></i> Delete Selected
                                            </button>
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="clearSelection()">
                                                <i class="ri-close-line me-1"></i> Clear Selection
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Filters -->
                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <form method="GET" class="mb-2">
                                            <input type="hidden" name="search" value="<?= htmlspecialchars($search_query); ?>">
                                            <input type="date" class="form-control" name="history_date" 
                                                   value="<?= isset($_GET['history_date']) ? $_GET['history_date'] : ''; ?>"
                                                   onchange="this.form.submit()">
                                        </form>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="dropdown">
                                            <button class="btn btn-outline-secondary dropdown-toggle w-100" type="button" 
                                                    data-bs-toggle="dropdown">
                                                Game Type <?= isset($_GET['game_type']) ? "(" . $_GET['game_type'] . ")" : "" ?>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="?<?= buildQueryString(['game_type' => '']) ?>">All Games</a></li>
                                                <?php foreach ($unique_game_types as $game_type): ?>
                                                    <li>
                                                        <a class="dropdown-item" href="?<?= buildQueryString(['game_type' => $game_type]) ?>">
                                                            <?= $game_types[$game_type]['name'] ?? $game_type; ?>
                                                        </a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex gap-2">
                                            <?php foreach ($status_options as $status): ?>
                                                <a href="?<?= buildQueryString(['status' => $status]) ?>" 
                                                   class="badge status-filter-badge <?= (isset($_GET['status']) && $_GET['status'] == $status) ? 'bg-primary' : 'bg-secondary'; ?>">
                                                    <?= ucfirst($status); ?>
                                                </a>
                                            <?php endforeach; ?>
                                            <a href="?<?= buildQueryString(['status' => '']) ?>" class="badge bg-secondary status-filter-badge">All Status</a>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Clear All Button -->
                                <div class="mb-3 text-end">
                                    <form method="POST" id="clearAllForm" onsubmit="return confirm('Are you sure you want to delete ALL prediction history? This action cannot be undone.')">
                                        <input type="hidden" name="delete_action" value="clear_all">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="ri-delete-bin-line me-1"></i> Clear All History
                                        </button>
                                    </form>
                                </div>

                                <?php if ($total_rows > 0): ?>
                                <!-- Pagination Info -->
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        Showing <?= ($offset + 1); ?> to <?= min($offset + $limit, $total_rows); ?> of <?= number_format($total_rows); ?> entries
                                    </div>
                                    
                                    <!-- Pagination -->
                                    <?php if ($total_pages > 1): ?>
                                    <nav>
                                        <ul class="pagination mb-0">
                                            <!-- First Page -->
                                            <?php if ($page > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?<?= buildQueryString(['page' => 1]) ?>">
                                                        <i class="ri-skip-back-line"></i>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <!-- Previous Page -->
                                            <?php if ($page > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?<?= buildQueryString(['page' => $page - 1]) ?>">
                                                        <i class="ri-arrow-left-line"></i>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <!-- Page Numbers -->
                                            <?php 
                                            $start_page = max(1, $page - 2);
                                            $end_page = min($total_pages, $page + 2);
                                            
                                            for ($i = $start_page; $i <= $end_page; $i++): ?>
                                                <li class="page-item <?= $i == $page ? 'active' : ''; ?>">
                                                    <a class="page-link" href="?<?= buildQueryString(['page' => $i]) ?>"><?= $i; ?></a>
                                                </li>
                                            <?php endfor; ?>
                                            
                                            <!-- Next Page -->
                                            <?php if ($page < $total_pages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?<?= buildQueryString(['page' => $page + 1]) ?>">
                                                        <i class="ri-arrow-right-line"></i>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <!-- Last Page -->
                                            <?php if ($page < $total_pages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?<?= buildQueryString(['page' => $total_pages]) ?>">
                                                        <i class="ri-skip-forward-line"></i>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>

                                <div class="table-responsive">
                                    <form method="POST" id="deleteForm">
                                        <input type="hidden" name="delete_action" id="deleteAction" value="">
                                        <input type="hidden" name="history_id" id="deleteHistoryId" value="">
                                        
                                        <table class="table table-striped table-bordered">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th class="table-checkbox">
                                                        <input type="checkbox" id="selectAll" class="select-all-checkbox">
                                                    </th>
                                                    <th>#</th>
                                                    <th>Game</th>
                                                    <th>Period ID</th>
                                                    <th>Prediction</th>
                                                    <th>Number</th>
                                                    <th>Type</th>
                                                    <th>Amount</th>
                                                    <th>Step</th>
                                                    <th>Status</th>
                                                    <th>Date</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (mysqli_num_rows($history_result) > 0): ?>
                                                    <?php $counter = $offset + 1; ?>
                                                    <?php while ($row = mysqli_fetch_assoc($history_result)): ?>
                                                        <tr>
                                                            <td>
                                                                <input type="checkbox" class="history-checkbox" name="selected_ids[]" value="<?= $row['id']; ?>">
                                                            </td>
                                                            <td><?= $counter++; ?></td>
                                                            <td>
                                                                <span class="badge bg-label-primary">
                                                                    <?= htmlspecialchars($game_types[$row['game_type']]['name'] ?? $row['game_type']); ?>
                                                                </span>
                                                            </td>
                                                            <td><?= htmlspecialchars($row['period_id']); ?></td>
                                                            <td>
                                                                <span class="badge <?= strpos($row['prediction_color'], 'green') !== false ? 'bg-success' : 'bg-danger'; ?>">
                                                                    <?= htmlspecialchars($row['prediction_color']); ?>
                                                                </span>
                                                            </td>
                                                            <td><strong><?= $row['prediction_number']; ?></strong></td>
                                                            <td><span class="badge bg-label-info"><?= htmlspecialchars($row['type']); ?></span></td>
                                                            <td>₹<?= $row['amount']; ?></td>
                                                            <td><span class="badge bg-label-warning"><?= $row['step']; ?>x</span></td>
                                                            <td>
                                                                <span class="badge <?= $row['status'] == 'sent' ? 'bg-label-success' : ($row['status'] == 'failed' ? 'bg-label-danger' : 'bg-label-warning'); ?>">
                                                                    <?= ucfirst($row['status']); ?>
                                                                </span>
                                                            </td>
                                                            <td><?= date('d-m-Y H:i:s', strtotime($row['created_at'])); ?></td>
                                                            <td class="action-buttons">
                                                                <button type="button" class="btn btn-sm btn-danger" 
                                                                        onclick="deleteSingle(<?= $row['id']; ?>)">
                                                                    <i class="ri-delete-bin-line"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="12" class="text-center py-4">
                                                            <div class="text-muted">
                                                                <i class="ri-inbox-line display-4"></i>
                                                                <p class="mt-2 mb-0">No prediction history found</p>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </form>
                                </div>
                                
                                <!-- Bottom Pagination -->
                                <?php if ($total_rows > 0 && $total_pages > 1): ?>
                                <div class="d-flex justify-content-center mt-3">
                                    <nav>
                                        <ul class="pagination mb-0">
                                            <?php if ($page > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?<?= buildQueryString(['page' => $page - 1]) ?>">Previous</a>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                                <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                                    <li class="page-item <?= $i == $page ? 'active' : ''; ?>">
                                                        <a class="page-link" href="?<?= buildQueryString(['page' => $i]) ?>"><?= $i; ?></a>
                                                    </li>
                                                <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                                    <li class="page-item disabled">
                                                        <span class="page-link">...</span>
                                                    </li>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                            
                                            <?php if ($page < $total_pages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?<?= buildQueryString(['page' => $page + 1]) ?>">Next</a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                </div>
                                <?php endif; ?>
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
    // Helper function to build query string
    function buildQueryString(params) {
        const urlParams = new URLSearchParams(window.location.search);
        
        // Update with new params
        Object.keys(params).forEach(key => {
            if (params[key]) {
                urlParams.set(key, params[key]);
            } else {
                urlParams.delete(key);
            }
        });
        
        return urlParams.toString();
    }

    // Game selection functions
    const gameTypes = <?= json_encode($game_types); ?>;
    
    document.getElementById('addGameBtn').addEventListener('click', function() {
        const selector = document.getElementById('gameSelector');
        const selectedValue = selector.value;
        
        if (!selectedValue) {
            alert('Please select a game first');
            return;
        }
        
        // Check if already added
        const existingInputs = document.querySelectorAll('input[name="games[]"]');
        for (let input of existingInputs) {
            if (input.value === selectedValue) {
                alert('This game is already selected');
                return;
            }
        }
        
        // Add to selected games
        const container = document.getElementById('selectedGamesContainer');
        const gameTag = document.createElement('span');
        gameTag.className = 'game-tag';
        gameTag.innerHTML = `
            ${gameTypes[selectedValue].name}
            <span class="interval-badge">${gameTypes[selectedValue].interval}s</span>
            <input type="hidden" name="games[]" value="${selectedValue}">
            <span class="remove-btn" onclick="removeGame(this)">×</span>
        `;
        container.appendChild(gameTag);
        
        selector.remove(selector.selectedIndex);
        selector.value = '';
        
        const noGamesMsg = container.querySelector('.text-muted');
        if (noGamesMsg) noGamesMsg.remove();
    });
    
    function removeGame(element) {
        const gameTag = element.parentElement;
        const gameValue = gameTag.querySelector('input').value;
        const gameName = gameTypes[gameValue].name;
        
        gameTag.remove();
        
        const selector = document.getElementById('gameSelector');
        const option = document.createElement('option');
        option.value = gameValue;
        option.innerHTML = `${gameName} <span class="interval-badge">${gameTypes[gameValue].interval}s</span>`;
        selector.appendChild(option);
        
        const container = document.getElementById('selectedGamesContainer');
        if (container.children.length === 0) {
            container.innerHTML = '<div class="text-muted">No games selected yet</div>';
        }
    }
    
    // Set current time
    function setNowTime(fieldId) {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const timeString = `${hours}:${minutes}`;
        
        document.getElementById(fieldId).value = timeString;
        validateTimeRange();
    }

    // Validate time range
    function validateTimeRange() {
        const startTime = document.getElementById('start_time').value;
        const endTime = document.getElementById('end_time').value;
        const timeRangeStatus = document.getElementById('time_range_status');
        
        if (!startTime || !endTime) {
            timeRangeStatus.innerHTML = '';
            return true;
        }
        
        const startDate = new Date(`2000-01-01T${startTime}`);
        const endDate = new Date(`2000-01-01T${endTime}`);
        
        // Handle overnight
        if (endDate < startDate) {
            endDate.setDate(endDate.getDate() + 1);
        }
        
        const diffMs = endDate - startDate;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        
        if (diffMins < 5) {
            timeRangeStatus.innerHTML = `<span class="text-danger">❌ Minimum 5 minutes required (currently ${diffMins} minutes)</span>`;
            return false;
        } else if (diffHours > 48) {
            timeRangeStatus.innerHTML = `<span class="text-danger">❌ Maximum 48 hours allowed (currently ${diffHours} hours)</span>`;
            return false;
        } else {
            timeRangeStatus.innerHTML = `<span class="text-success">✓ Time range: ${diffMins} minutes (${diffHours} hours ${diffMins % 60} minutes)</span>`;
            return true;
        }
    }
    
    // Schedule type change
    document.getElementById('schedule_type').addEventListener('change', function() {
        const scheduleType = this.value;
        const scheduleHint = document.getElementById('schedule_hint');
        
        // Update hints
        const hints = {
            'continuous': 'Bot will send predictions continuously when new periods are detected within the time range',
            '10min': 'Bot will send predictions every 10 minutes for all selected games within the time range',
            '1hour': 'Bot will send predictions every hour at :00 for all games within the time range',
            '6hour': 'Bot will send predictions every 6 hours for all games within the time range',
            '24hour': 'Bot will send predictions once daily at start time for all games'
        };
        
        scheduleHint.innerHTML = `<span class="text-info">${hints[scheduleType] || ''}</span>`;
        
        // ALL games work with ALL schedule types - no restrictions
        enableAllGames();
    });
    
    function enableAllGames() {
        const selector = document.getElementById('gameSelector');
        Array.from(selector.options).forEach(option => {
            option.style.display = 'block';
            option.disabled = false;
        });
        
        const selectedGamesContainer = document.getElementById('selectedGamesContainer');
        const warnings = selectedGamesContainer.querySelectorAll('.alert-warning');
        warnings.forEach(warning => warning.remove());
    }
    
    // Form validation
    document.getElementById('predictionForm').addEventListener('submit', function(e) {
        const botToken = document.querySelector('input[name="bot_token"]').value;
        const chatId = document.querySelector('input[name="chat_id"]').value;
        const games = document.querySelectorAll('input[name="games[]"]');
        const startTime = document.getElementById('start_time').value;
        const endTime = document.getElementById('end_time').value;
        
        if (!botToken || !chatId) {
            e.preventDefault();
            alert('Please fill Bot Token and Chat ID');
            return;
        }
        
        if (!startTime || !endTime) {
            e.preventDefault();
            alert('Please fill Start Time and End Time');
            return;
        }
        
        if (games.length === 0) {
            e.preventDefault();
            alert('Please select at least one game');
            return;
        }
        
        if (!validateTimeRange()) {
            e.preventDefault();
            alert('Please fix the time range issues');
            return;
        }
        
        if (startTime >= endTime) {
            e.preventDefault();
            alert('End time must be after start time');
            return;
        }
    });
    
    // Auto update time range status
    document.getElementById('start_time').addEventListener('change', validateTimeRange);
    document.getElementById('end_time').addEventListener('change', validateTimeRange);

    // ========== HISTORY DELETE FUNCTIONS ==========
    
    // Single delete
    function deleteSingle(historyId) {
        if (confirm('Are you sure you want to delete this history?')) {
            document.getElementById('deleteAction').value = 'delete_single';
            document.getElementById('deleteHistoryId').value = historyId;
            document.getElementById('deleteForm').submit();
        }
    }
    
    // Multiple delete
    function deleteSelected() {
        const selectedCheckboxes = document.querySelectorAll('.history-checkbox:checked');
        if (selectedCheckboxes.length === 0) {
            alert('Please select at least one item to delete');
            return;
        }
        
        if (confirm(`Are you sure you want to delete ${selectedCheckboxes.length} selected items?`)) {
            document.getElementById('deleteAction').value = 'delete_multiple';
            document.getElementById('deleteForm').submit();
        }
    }
    
    // Clear all
    function clearAllHistory() {
        if (confirm('Are you sure you want to delete ALL history? This action cannot be undone.')) {
            document.getElementById('clearAllForm').submit();
        }
    }
    
    // Bulk selection handling
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.history-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateBulkActions();
    });
    
    // Update bulk actions when checkboxes change
    document.querySelectorAll('.history-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActions);
    });
    
    function updateBulkActions() {
        const selectedCheckboxes = document.querySelectorAll('.history-checkbox:checked');
        const bulkActions = document.getElementById('bulkActions');
        const selectedCount = document.getElementById('selectedCount');
        
        if (selectedCheckboxes.length > 0) {
            bulkActions.style.display = 'block';
            selectedCount.textContent = selectedCheckboxes.length;
        } else {
            bulkActions.style.display = 'none';
        }
        
        // Update select all checkbox
        const totalCheckboxes = document.querySelectorAll('.history-checkbox').length;
        const selectAll = document.getElementById('selectAll');
        selectAll.checked = selectedCheckboxes.length === totalCheckboxes;
        selectAll.indeterminate = selectedCheckboxes.length > 0 && selectedCheckboxes.length < totalCheckboxes;
    }
    
    function clearSelection() {
        document.querySelectorAll('.history-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        updateBulkActions();
    }
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateBulkActions();
        validateTimeRange();
        
        // Initialize schedule type
        const scheduleSelect = document.getElementById('schedule_type');
        if (scheduleSelect) scheduleSelect.dispatchEvent(new Event('change'));
        
        // Auto-refresh page every 30 seconds to check for new periods
        setInterval(() => {
            // Only refresh if bot is active
            fetch(window.location.href + '?check=1')
                .then(response => response.text())
                .then(data => {
                    console.log('Auto-check completed');
                })
                .catch(error => console.log('Auto-check error:', error));
        }, 30000);
    });
</script>
</body>
</html>

<?php
// Helper function to build query string for pagination and filters
function buildQueryString($new_params = []) {
    $params = $_GET;
    
    // Remove page if it's not in new_params
    if (!isset($new_params['page'])) {
        unset($params['page']);
    }
    
    // Merge with new params
    $params = array_merge($params, $new_params);
    
    // Remove empty values
    $params = array_filter($params, function($value) {
        return $value !== '' && $value !== null;
    });
    
    return http_build_query($params);
}
?>