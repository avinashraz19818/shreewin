<?php
// Auto Prediction Bot - 30 Second Cron File

ini_set('max_execution_time', 60);
ini_set('memory_limit', '256M');
date_default_timezone_set("Asia/Kolkata");

// Include database connection
include("api/conn.php");
mysqli_set_charset($conn, "utf8mb4");

// Log file with rotation
$log_dir = "cron_logs";
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}
$log_file = $log_dir . "/cron_" . date("Y-m-d") . ".txt";

function logMessage($message) {
    global $log_file;
    $timestamp = date("Y-m-d H:i:s");
    $log_entry = "[$timestamp] $message\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
    echo $log_entry;
}

logMessage("=== CRON STARTED ===");

// Prevent multiple instances
$lock_file = "/tmp/prediction_bot.lock";
if (file_exists($lock_file)) {
    $lock_time = filemtime($lock_file);
    if (time() - $lock_time < 25) {
        logMessage("⚠️ Another instance is running. Exiting.");
        exit();
    }
}
file_put_contents($lock_file, getmypid());

try {
    // Get bot settings
    $settings_result = mysqli_query($conn, "SELECT * FROM auto_prediction_settings WHERE is_active = 1 LIMIT 1");
    
    if (mysqli_num_rows($settings_result) === 0) {
        logMessage("❌ Bot is not active");
        unlink($lock_file);
        exit();
    }

    $settings = mysqli_fetch_assoc($settings_result);
    logMessage("✓ Bot settings loaded");
    
    if (empty($settings['bot_token']) || empty($settings['chat_id'])) {
        logMessage("❌ Bot token or chat ID not configured");
        unlink($lock_file);
        exit();
    }

    // Check time range
    $current_time = date('H:i');
    $start_time = $settings['start_time'] ?? '09:00';
    $end_time = $settings['end_time'] ?? '21:00';
    
    $current_timestamp = strtotime($current_time);
    $start_timestamp = strtotime($start_time);
    $end_timestamp = strtotime($end_time);
    
    if ($end_timestamp < $start_timestamp) {
        $end_timestamp += 86400;
        if ($current_timestamp < $start_timestamp) {
            $current_timestamp += 86400;
        }
    }
    
    if ($current_timestamp < $start_timestamp || $current_timestamp >= $end_timestamp) {
        logMessage("⏭️ Skipped: Outside time range ($start_time - $end_time)");
        unlink($lock_file);
        exit();
    }
    
    $enabled_games = !empty($settings['enabled_games']) ? explode(',', $settings['enabled_games']) : [];

    if (empty($enabled_games)) {
        logMessage("❌ No games enabled");
        unlink($lock_file);
        exit();
    }

    // Game table mapping
    $game_configs = [
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

    $schedule_type = $settings['schedule_type'] ?? 'continuous';
    $predictions_sent = 0;
    
    logMessage("Schedule: $schedule_type, Games: " . implode(', ', $enabled_games));

    foreach ($enabled_games as $game_type) {
        if (!isset($game_configs[$game_type])) {
            logMessage("⚠️ Unknown game type: $game_type");
            continue;
        }
        
        $game_config = $game_configs[$game_type];
        $table_name = $game_config['table'];
        $game_name = $game_config['name'];
        
        // Check schedule timing
        if (!shouldRunGame($game_config['interval'], $schedule_type, $start_time)) {
            continue;
        }
        
        // Get the LATEST period from game table
        $res = mysqli_query($conn, "SELECT atadaaidi FROM $table_name ORDER BY kramasankhye DESC LIMIT 1");
        if (mysqli_num_rows($res) === 0) {
            logMessage("❌ No data in $table_name");
            continue;
        }
        
        $row = mysqli_fetch_assoc($res);
        $latest_period = $row['atadaaidi'];
        
        logMessage("Game: $game_name - Latest Period: $latest_period");
        
        // Parse the period ID correctly
        // Example: 20251218100010980
        // We need to extract the actual period number
        
        // Method 1: Try to get last 3 digits as period number
        $period_length = strlen($latest_period);
        
        // For 20251218100010980 (17 digits)
        // Last 5 digits: 10980
        // But we need 1098 as period number
        
        if ($period_length == 17) {
            // Format: YYYYMMDDXXXXNNNNX (17 digits)
            $prefix = substr($latest_period, 0, 12); // First 12 chars: 202512181000
            $period_part = substr($latest_period, 12, 4); // Next 4 chars: 1098
            $last_digit = substr($latest_period, -1); // Last digit: 0
            
            $current_period_num = (int)$period_part; // 1098
            $next_period_num = $current_period_num + 1; // 1099
            
            // Format next period ID
            $next_period_id = $prefix . str_pad($next_period_num, 4, '0', STR_PAD_LEFT) . $last_digit;
            
            logMessage("Parsed: Prefix='$prefix', Period='$period_part', Last='$last_digit'");
            logMessage("Next Period ID: $next_period_id");
            
        } elseif ($period_length == 16) {
            // Format: YYYYMMDDXXXXNNNN (16 digits)
            $prefix = substr($latest_period, 0, 12); // First 12 chars
            $period_part = substr($latest_period, 12, 4); // Next 4 chars
            
            $current_period_num = (int)$period_part;
            $next_period_num = $current_period_num + 1;
            
            $next_period_id = $prefix . str_pad($next_period_num, 4, '0', STR_PAD_LEFT);
            
        } else {
            // Generic fallback - just increment the number at the end
            // Find the last sequence of digits
            if (preg_match('/(\d+)$/', $latest_period, $matches)) {
                $last_number = $matches[1];
                $next_number = (int)$last_number + 1;
                $next_period_id = preg_replace('/\d+$/', str_pad($next_number, strlen($last_number), '0', STR_PAD_LEFT), $latest_period);
                logMessage("Generic parse: Last num='$last_number', Next='$next_number'");
            } else {
                logMessage("❌ Cannot parse period ID: $latest_period");
                continue;
            }
        }
        
        // Check if already predicted for this next period today
        $check = mysqli_query($conn, "
            SELECT id FROM auto_prediction_history 
            WHERE period_id = '$next_period_id' 
            AND game_type = '$game_type' 
            AND DATE(created_at) = CURDATE()
        ");
        
        if (mysqli_num_rows($check) > 0) {
            logMessage("⏭️ Already predicted for $next_period_id");
            continue;
        }
        
        // Check if next period already exists in game table
        $check_exists = mysqli_query($conn, "
            SELECT atadaaidi FROM $table_name 
            WHERE atadaaidi = '$next_period_id'
        ");
        
        if (mysqli_num_rows($check_exists) > 0) {
            logMessage("⚠️ Period $next_period_id already exists in database");
            continue;
        }
        
        // Generate prediction
        $digit = rand(0, 9);
        $color = ($digit == 5) ? "green,violet" : (in_array($digit, [1, 3, 7, 9]) ? "green" : "red");
        $type = ($digit <= 4) ? "Small" : "Big";
        $amount = $settings['amount'];
        $step = $settings['step'];
        
        // Save history
        $insert_query = "INSERT INTO auto_prediction_history 
                        (game_type, period_id, prediction_number, prediction_color, amount, step, type, status, created_at) 
                        VALUES ('$game_type', '$next_period_id', '$digit', '$color', $amount, $step, '$type', 'pending', NOW())";
        
        if (!mysqli_query($conn, $insert_query)) {
            logMessage("❌ Database error: " . mysqli_error($conn));
            continue;
        }
        
        // Prepare message
        $message = str_replace(
            ['{period_id}', '{color}', '{number}', '{type}', '{amount}', '{step}'],
            [$next_period_id, $color, $digit, $type, $amount, $step],
            $settings['prediction_message']
        );
        
        // Send Telegram
        $telegram_sent = sendTelegram($message, $settings['bot_token'], $settings['chat_id']);
        
        // Update status
        $last_id = mysqli_insert_id($conn);
        $status = $telegram_sent ? 'sent' : 'failed';
        mysqli_query($conn, "UPDATE auto_prediction_history SET status = '$status' WHERE id = $last_id");
        
        if ($telegram_sent) {
            logMessage("✅ Sent $game_name: $next_period_id (Digit: $digit, Color: $color, Type: $type)");
            $predictions_sent++;
        } else {
            logMessage("❌ Telegram failed for $next_period_id");
        }
        
        usleep(100000);
    }

    logMessage("=== COMPLETED: $predictions_sent predictions sent ===");

} catch (Exception $e) {
    logMessage("🔥 ERROR: " . $e->getMessage());
} finally {
    if (file_exists($lock_file)) {
        unlink($lock_file);
    }
}

// Schedule check function
function shouldRunGame($game_interval, $schedule_type, $start_time) {
    $current_timestamp = time();
    $current_second = date('s', $current_timestamp);
    $current_minute = date('i', $current_timestamp);
    $current_hour = date('H', $current_timestamp);
    
    switch($schedule_type) {
        case 'continuous':
            return true;
        case '10min':
            return ($current_minute % 10 == 0 && $current_second < 5);
        case '1hour':
            return ($current_minute == 0 && $current_second < 5);
        case '6hour':
            $valid_hours = [0, 6, 12, 18];
            return (in_array($current_hour, $valid_hours) && $current_minute == 0 && $current_second < 5);
        case '24hour':
            $start_datetime = date('Y-m-d') . ' ' . $start_time . ':00';
            $current_datetime = date('Y-m-d H:i:s');
            return (strtotime($current_datetime) >= strtotime($start_datetime) && 
                    strtotime($current_datetime) < strtotime($start_datetime) + 5);
        default:
            return true;
    }
}

// Telegram function
function sendTelegram($message, $bot_token, $chat_id) {
    if (empty($bot_token) || empty($chat_id)) return false;
    
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
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ($http_code == 200);
}

// Cleanup old logs
function cleanupOldLogs() {
    $log_dir = "cron_logs";
    if (is_dir($log_dir)) {
        $files = glob($log_dir . "/cron_*.txt");
        $now = time();
        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file) >= 604800)) {
                unlink($file);
            }
        }
    }
}

cleanupOldLogs();
logMessage("=== CRON ENDED ===");
?>