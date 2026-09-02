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

// Handle RUN/STOP bot action
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    if ($action == 'run_now') {
        // Run bot immediately
        runPredictionBot();
        $_SESSION['success'] = "Bot executed successfully!";
        header("Location: prediction_bot.php");
        exit();
        
    } elseif ($action == 'toggle_status') {
        // Toggle bot active/inactive
        $status = isset($_GET['status']) ? intval($_GET['status']) : 0;
        mysqli_query($conn, "UPDATE auto_prediction_settings SET is_active = $status");
        
        if ($status == 1) {
            $_SESSION['success'] = "Bot activated successfully!";
        } else {
            $_SESSION['success'] = "Bot deactivated successfully!";
        }
        header("Location: prediction_bot.php");
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bot_token = mysqli_real_escape_string($conn, $_POST['bot_token']);
    $chat_id = mysqli_real_escape_string($conn, $_POST['chat_id']);
    
    // Handle games array properly
    $enabled_games = [];
    if (isset($_POST['games']) && is_array($_POST['games'])) {
        $enabled_games = $_POST['games'];
    }
    $enabled_games_str = implode(',', $enabled_games);
    
    $prediction_message = mysqli_real_escape_string($conn, $_POST['prediction_message']);
    $amount = intval($_POST['amount']);
    $step = intval($_POST['step']);
    
    // Time scheduling fields
    $schedule_type = mysqli_real_escape_string($conn, $_POST['schedule_type']);
    $start_time = !empty($_POST['start_time']) ? $_POST['start_time'] : NULL;
    $end_time = !empty($_POST['end_time']) ? $_POST['end_time'] : NULL;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Check if settings already exist
    $check = mysqli_query($conn, "SELECT id FROM auto_prediction_settings LIMIT 1");
    
    if (mysqli_num_rows($check) > 0) {
        // Update existing settings
        $query = "UPDATE auto_prediction_settings SET 
                  bot_token = '$bot_token',
                  chat_id = '$chat_id',
                  enabled_games = '$enabled_games_str',
                  prediction_message = '$prediction_message',
                  amount = $amount,
                  step = $step,
                  schedule_type = '$schedule_type',
                  start_time = " . ($start_time ? "'$start_time'" : "NULL") . ",
                  end_time = " . ($end_time ? "'$end_time'" : "NULL") . ",
                  is_active = $is_active,
                  updated_at = NOW()";
    } else {
        // Insert new settings
        $query = "INSERT INTO auto_prediction_settings 
                  (bot_token, chat_id, enabled_games, prediction_message, amount, step, 
                   schedule_type, start_time, end_time, is_active, status) 
                  VALUES ('$bot_token', '$chat_id', '$enabled_games_str', '$prediction_message', $amount, $step,
                          '$schedule_type', " . ($start_time ? "'$start_time'" : "NULL") . ", 
                          " . ($end_time ? "'$end_time'" : "NULL") . ", $is_active, 'active')";
    }
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Settings saved successfully!";
    } else {
        $_SESSION['error'] = "Error saving settings: " . mysqli_error($conn);
    }
    
    header("Location: prediction_bot.php");
    exit();
}

// Function to run prediction bot
function runPredictionBot() {
    global $conn;
    
    // Get bot settings
    $settings_query = mysqli_query($conn, 
        "SELECT * FROM auto_prediction_settings WHERE is_active = 1 LIMIT 1"
    );
    
    if (mysqli_num_rows($settings_query) == 0) {
        return false;
    }
    
    $settings = mysqli_fetch_assoc($settings_query);
    
    // Check schedule
    if (!shouldRunBasedOnSchedule($settings)) {
        return false;
    }
    
    // Get enabled games
    $enabled_games = !empty($settings['enabled_games']) ? explode(',', $settings['enabled_games']) : [];
    
    if (empty($enabled_games)) {
        return false;
    }
    
    // Game period mapping
    $game_periods = [
        'gelluonduhogu_zehn' => 30,
        'gelluonduhogu' => 60,
        'gelluonduhogu_drei' => 180,
        'gelluonduhogu_funf' => 300
    ];
    
    // Send predictions for each game
    foreach ($enabled_games as $game_type) {
        if (!isset($game_periods[$game_type])) continue;
        
        // Generate prediction
        $prediction_data = generatePrediction($game_type);
        
        // Prepare message
        $message = str_replace(
            ['{period_id}', '{color}', '{number}', '{type}', '{amount}', '{step}'],
            [
                $prediction_data['period_id'],
                $prediction_data['color'],
                $prediction_data['number'],
                $prediction_data['type'],
                $settings['amount'],
                $settings['step']
            ],
            $settings['prediction_message']
        );
        
        // Send to Telegram
        $telegram_sent = sendTelegramMessage($settings['bot_token'], $settings['chat_id'], $message);
        
        // Save to history
        savePredictionHistory(
            $game_type,
            $prediction_data['period_id'],
            $prediction_data['color'],
            $prediction_data['number'],
            $prediction_data['type'],
            $settings['amount'],
            $settings['step'],
            $telegram_sent ? 'sent' : 'failed'
        );
        
        // Small delay between games
        sleep(1);
    }
    
    return true;
}

// Function to check if bot should run based on schedule
function shouldRunBasedOnSchedule($settings) {
    $current_time = date('H:i');
    $current_minute = (int)date('i');
    $current_hour = (int)date('H');
    
    switch($settings['schedule_type']) {
        case '10min':
            // Run every 10 minutes (at :00, :10, :20, :30, :40, :50)
            return ($current_minute % 10 == 0);
            
        case '1hour':
            // Run every hour at :00
            return ($current_minute == 0);
            
        case '6hour':
            // Run every 6 hours at 0:00, 6:00, 12:00, 18:00
            return ($current_minute == 0 && in_array($current_hour, [0, 6, 12, 18]));
            
        case '24hour':
            // Run once daily at the saved time
            if (!empty($settings['start_time'])) {
                return ($current_time == $settings['start_time']);
            }
            // If no time set, run at midnight
            return ($current_hour == 0 && $current_minute == 0);
            
        case 'custom':
            // Run every 10 minutes within custom time range
            if (!empty($settings['start_time']) && !empty($settings['end_time'])) {
                $within_time = ($current_time >= $settings['start_time'] && 
                               $current_time < $settings['end_time']);
                return $within_time && ($current_minute % 10 == 0);
            }
            return false;
            
        default:
            return false;
    }
}

// Function to generate prediction
function generatePrediction($game_type) {
    $colors = ['Red', 'Green', 'Violet'];
    $types = ['Big', 'Small', 'Odd', 'Even'];
    
    // Generate random prediction
    $color = $colors[array_rand($colors)];
    $number = rand(0, 9);
    $type = $types[array_rand($types)];
    
    // Generate period ID based on game type
    $period_id = generatePeriodId($game_type);
    
    return [
        'period_id' => $period_id,
        'color' => $color,
        'number' => $number,
        'type' => $type
    ];
}

// Function to generate period ID
function generatePeriodId($game_type) {
    $timestamp = time();
    
    // Different formats based on game
    switch($game_type) {
        case 'gelluonduhogu_zehn': // 30 sec
            return date('YmdHis', $timestamp);
        case 'gelluonduhogu': // 1 min
            return date('YmdHi', $timestamp);
        case 'gelluonduhogu_drei': // 3 min
            return floor($timestamp / 180) . rand(100, 999);
        case 'gelluonduhogu_funf': // 5 min
            return floor($timestamp / 300) . rand(1000, 9999);
        default:
            return date('YmdHis', $timestamp);
    }
}

// Function to send Telegram message
function sendTelegramMessage($bot_token, $chat_id, $message) {
    $api_url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
    
    $data = [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
        ],
    ];
    
    $context = stream_context_create($options);
    $result = @file_get_contents($api_url, false, $context);
    
    return $result !== false;
}

// Function to save prediction history
function savePredictionHistory($game_type, $period_id, $color, $number, $type, $amount, $step, $status) {
    global $conn;
    
    $query = "INSERT INTO auto_prediction_history 
              (game_type, period_id, prediction_color, prediction_number, type, amount, step, status) 
              VALUES ('$game_type', '$period_id', '$color', '$number', '$type', $amount, $step, '$status')";
    
    return mysqli_query($conn, $query);
}

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
        'schedule_type' => '10min',
        'start_time' => '',
        'end_time' => '',
        'is_active' => 1
    ];
}

// Game types mapping
$game_types = [
    'gelluonduhogu_zehn' => 'Wingo 30Sec',
    'gelluonduhogu' => 'Wingo 1Min', 
    'gelluonduhogu_drei' => 'Wingo 3Min',
    'gelluonduhogu_funf' => 'Wingo 5Min'
];

// Schedule options
$schedule_options = [
    '10min' => '10 Minutes (30 sec game only)',
    '1hour' => '1 Hour (All games)',
    '6hour' => '6 Hours (All games)',
    '24hour' => '24 Hours (All games)',
    'custom' => 'Custom Time Range'
];

// Calculate next run time
$next_run_time = calculateNextRunTime($settings);

// Function to calculate next run time
function calculateNextRunTime($settings) {
    $now = time();
    $current_minute = (int)date('i', $now);
    $current_hour = (int)date('H', $now);
    $current_time = date('H:i', $now);
    
    if (empty($settings['is_active']) || $settings['is_active'] == 0) {
        return 'Bot is inactive';
    }
    
    switch($settings['schedule_type']) {
        case '10min':
            // Next 10 minute interval
            $next_minute = ceil($current_minute / 10) * 10;
            if ($next_minute >= 60) {
                $next_minute = 0;
                $next_hour = $current_hour + 1;
            } else {
                $next_hour = $current_hour;
            }
            return sprintf("%02d:%02d", $next_hour, $next_minute);
            
        case '1hour':
            // Next hour at :00
            $next_hour = $current_hour + 1;
            return sprintf("%02d:00", $next_hour);
            
        case '6hour':
            // Next 6 hour interval
            $next_hour = ceil($current_hour / 6) * 6;
            if ($next_hour >= 24) {
                $next_hour = 0;
            }
            return sprintf("%02d:00", $next_hour);
            
        case '24hour':
            if (!empty($settings['start_time'])) {
                $today_time = date('Y-m-d') . ' ' . $settings['start_time'];
                if (strtotime($today_time) > $now) {
                    return $settings['start_time'] . ' (Today)';
                } else {
                    $tomorrow = date('Y-m-d', strtotime('+1 day')) . ' ' . $settings['start_time'];
                    return date('H:i', strtotime($tomorrow)) . ' (Tomorrow)';
                }
            }
            return '00:00 (Tomorrow)';
            
        case 'custom':
            if (!empty($settings['start_time']) && !empty($settings['end_time'])) {
                if ($current_time >= $settings['start_time'] && $current_time < $settings['end_time']) {
                    // Within time range
                    $next_minute = ceil($current_minute / 10) * 10;
                    if ($next_minute >= 60) {
                        $next_minute = 0;
                        $next_hour = $current_hour + 1;
                    } else {
                        $next_hour = $current_hour;
                    }
                    
                    // Check if next time is still within range
                    $next_time_str = sprintf("%02d:%02d", $next_hour, $next_minute);
                    if ($next_time_str < $settings['end_time']) {
                        return $next_time_str;
                    } else {
                        return $settings['start_time'] . ' (Tomorrow)';
                    }
                } elseif ($current_time < $settings['start_time']) {
                    return $settings['start_time'] . ' (Today)';
                } else {
                    return $settings['start_time'] . ' (Tomorrow)';
                }
            }
            return 'Not configured';
            
        default:
            return 'N/A';
    }
}

// Get bot status
$bot_status = getBotStatus($settings);

function getBotStatus($settings) {
    if (empty($settings['is_active']) || $settings['is_active'] == 0) {
        return ['status' => 'inactive', 'class' => 'danger', 'icon' => '⭕', 'text' => 'Bot is inactive'];
    }
    
    $should_run = shouldRunBasedOnSchedule($settings);
    
    if ($should_run) {
        return ['status' => 'active', 'class' => 'success', 'icon' => '✅', 'text' => 'Bot is active and running'];
    } else {
        return ['status' => 'idle', 'class' => 'warning', 'icon' => '⏸️', 'text' => 'Bot is active but idle (outside schedule)'];
    }
}

// Date filtering for history
$date_filter = '';
if (isset($_GET['history_date']) && !empty($_GET['history_date'])) {
    $history_date = mysqli_real_escape_string($conn, $_GET['history_date']);
    $date_filter = " AND DATE(created_at) = '$history_date'";
}

// Get prediction history with pagination
$limit = 50;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Total count for pagination
$count_query = "SELECT COUNT(*) as total FROM auto_prediction_history WHERE 1 $date_filter";
$count_result = mysqli_query($conn, $count_query);
$total_rows = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_rows / $limit);

$history_query = "SELECT * FROM auto_prediction_history WHERE 1 $date_filter ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$history_result = mysqli_query($conn, $history_query);
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
        .form-section {
            border-radius: 0.35rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid #e3e6f0;
        }
        .game-container {
            border: 1px solid #d1d7e0;
            border-radius: 0.5rem;
            padding: 1rem;
        }
        .selected-games {
            min-height: 50px;
            border: 1px dashed #d1d7e0;
            border-radius: 0.35rem;
            padding: 0.75rem;
            margin-top: 1rem;
        }
        .game-tag {
            display: inline-block;
            background: #7367f0;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            margin: 0.25rem;
            font-size: 0.875rem;
        }
        .game-tag .remove-btn {
            margin-left: 0.5rem;
            cursor: pointer;
            font-weight: bold;
        }
        .bot-status-card {
            border-left: 4px solid;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .bot-status-card.success {
            border-color: #28a745;
            background: rgba(40, 167, 69, 0.1);
        }
        .bot-status-card.warning {
            border-color: #ffc107;
            background: rgba(255, 193, 7, 0.1);
        }
        .bot-status-card.danger {
            border-color: #dc3545;
            background: rgba(220, 53, 69, 0.1);
        }
        .next-run-badge {
            font-size: 1.2rem;
            font-weight: bold;
        }
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
                        
                        <!-- Success/Error Messages -->
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

                        <!-- Bot Status Card -->
                        <div class="bot-status-card <?= $bot_status['class']; ?>">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-1">
                                        <span class="me-2"><?= $bot_status['icon']; ?></span>
                                        Bot Status: <?= ucfirst($bot_status['status']); ?>
                                    </h5>
                                    <p class="mb-0"><?= $bot_status['text']; ?></p>
                                    <p class="mb-0"><strong>Next Run:</strong> 
                                        <span class="next-run-badge text-<?= $bot_status['class']; ?>">
                                            <?= $next_run_time; ?>
                                        </span>
                                    </p>
                                </div>
                                <div class="d-flex gap-2">
                                    <?php if ($settings['is_active'] == 1): ?>
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
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title mb-0">
                                    <i class="ri-robot-line me-2"></i>
                                    Auto Bot Prediction Management
                                </h4>
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
                                                       value="<?= htmlspecialchars($settings['bot_token']); ?>" required
                                                       placeholder="Enter Telegram Bot Token">
                                                <div class="form-text">Get this from @BotFather on Telegram</div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Chat ID <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="chat_id" 
                                                       value="<?= htmlspecialchars($settings['chat_id']); ?>" required
                                                       placeholder="Enter Telegram Chat ID">
                                                <div class="form-text">Channel/Group ID where predictions will be sent</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Game Selection -->
                                    <div class="form-section">
                                        <h5 class="text-primary mb-3">
                                            <i class="ri-gamepad-line me-2"></i>
                                            Select Games for Prediction
                                        </h5>
                                        <div class="game-container">
                                            <label class="form-label">Available Games</label>
                                            <select class="form-select mb-3" id="gameSelector">
                                                <option value="">-- Select Game to Add --</option>
                                                <?php foreach ($game_types as $key => $name): ?>
                                                    <?php if (!in_array($key, $enabled_games)): ?>
                                                        <option value="<?= $key; ?>"><?= $name; ?></option>
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
                                                                <?= $game_types[$game_key]; ?>
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
                                        </div>
                                    </div>

                                    <!-- Time Scheduling Section -->
                                    <div class="form-section">
                                        <h5 class="text-primary mb-3">
                                            <i class="ri-time-line me-2"></i>
                                            Prediction Schedule Settings
                                        </h5>
                                        
                                        <div class="row">
                                            <!-- Active Status -->
                                            <div class="col-md-12 mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="is_active" 
                                                           id="is_active" <?= (!empty($settings['is_active']) && $settings['is_active'] == 1) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="is_active">
                                                        <strong>Enable Auto Prediction</strong>
                                                    </label>
                                                    <div class="form-text">
                                                        <?php if (!empty($settings['is_active']) && $settings['is_active'] == 1): ?>
                                                            <span class="text-success">✅ Bot is currently active</span>
                                                        <?php else: ?>
                                                            <span class="text-danger">❌ Bot is currently inactive</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Schedule Type -->
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
                                                <div class="form-text" id="schedule_hint">
                                                    <!-- Dynamic hint will be shown here -->
                                                </div>
                                            </div>
                                            
                                            <!-- Next Run Time Display -->
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Next Run Time</label>
                                                <div class="form-control bg-light">
                                                    <strong id="next_run_display"><?= $next_run_time; ?></strong>
                                                </div>
                                                <div class="form-text">Calculated based on current schedule</div>
                                            </div>
                                        </div>
                                        
                                        <!-- Custom Time Range (Initially Hidden) -->
                                        <div id="custom_time_range" class="row mt-3" style="display: <?= (isset($settings['schedule_type']) && $settings['schedule_type'] == 'custom') ? 'block' : 'none'; ?>;">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Start Time</label>
                                                <input type="time" class="form-control" name="start_time" 
                                                       value="<?= !empty($settings['start_time']) ? $settings['start_time'] : '09:00'; ?>">
                                                <div class="form-text">Bot will start sending predictions from this time</div>
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">End Time</label>
                                                <input type="time" class="form-control" name="end_time" 
                                                       value="<?= !empty($settings['end_time']) ? $settings['end_time'] : '21:00'; ?>">
                                                <div class="form-text">Bot will stop sending predictions after this time</div>
                                            </div>
                                            
                                            <div class="col-md-12">
                                                <div class="alert alert-info">
                                                    <i class="ri-information-line me-2"></i>
                                                    <strong>Note:</strong> During this time range, bot will send predictions every 10 minutes. 
                                                    Outside this range, bot will be inactive.
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Schedule Info Card -->
                                        <div class="alert alert-warning mt-3">
                                            <div class="d-flex align-items-start">
                                                <i class="ri-alert-line me-2 mt-1"></i>
                                                <div>
                                                    <strong>Schedule Information:</strong>
                                                    <ul class="mb-0 mt-1">
                                                        <li><strong>10 Minutes:</strong> Predictions every 10 minutes (30 sec game only)</li>
                                                        <li><strong>1 Hour:</strong> Predictions every hour at :00 (All games)</li>
                                                        <li><strong>6 Hours:</strong> Predictions every 6 hours at 0:00, 6:00, 12:00, 18:00 (All games)</li>
                                                        <li><strong>24 Hours:</strong> Predictions once daily at current time (All games)</li>
                                                        <li><strong>Custom:</strong> Set your own start and end time for daily operation</li>
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
                                                <label class="form-label">Default Amount</label>
                                                <input type="number" class="form-control" name="amount" 
                                                       value="<?= $settings['amount']; ?>" min="1" required>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label">Step</label>
                                                <input type="number" class="form-control" name="step" 
                                                       value="<?= $settings['step']; ?>" min="1" required>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Message Template -->
                                    <div class="form-section">
                                        <h5 class="text-primary mb-3">
                                            <i class="ri-message-2-line me-2"></i>
                                            Telegram Message Template
                                        </h5>
                                        <div class="mb-3">
                                            <textarea class="form-control" name="prediction_message" rows="6" 
                                                      placeholder="Enter your prediction message template"><?= htmlspecialchars($settings['prediction_message']); ?></textarea>
                                            <div class="form-text">
                                                Available variables: 
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
                        
                        <!-- Prediction History -->
                        <div class="card mt-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="ri-history-line me-2"></i>
                                    Prediction History
                                </h5>
                                <form method="GET" class="d-flex gap-2">
                                    <input type="date" class="form-control" name="history_date" 
                                           value="<?= isset($_GET['history_date']) ? $_GET['history_date'] : ''; ?>">
                                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                                    <?php if (isset($_GET['history_date'])): ?>
                                        <a href="prediction_bot.php" class="btn btn-secondary btn-sm">Clear</a>
                                    <?php endif; ?>
                                </form>
                            </div>
                            <div class="card-body">
                                <!-- Pagination -->
                                <?php if ($total_pages > 1): ?>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        Showing <?= ($offset + 1); ?> to <?= min($offset + $limit, $total_rows); ?> of <?= $total_rows; ?> entries
                                    </div>
                                    <nav>
                                        <ul class="pagination mb-0">
                                            <?php if ($page > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?= $page - 1; ?><?= isset($_GET['history_date']) ? '&history_date=' . $_GET['history_date'] : ''; ?>">Previous</a>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                                <li class="page-item <?= $i == $page ? 'active' : ''; ?>">
                                                    <a class="page-link" href="?page=<?= $i; ?><?= isset($_GET['history_date']) ? '&history_date=' . $_GET['history_date'] : ''; ?>"><?= $i; ?></a>
                                                </li>
                                            <?php endfor; ?>
                                            
                                            <?php if ($page < $total_pages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?= $page + 1; ?><?= isset($_GET['history_date']) ? '&history_date=' . $_GET['history_date'] : ''; ?>">Next</a>
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
                                                <th>Game Type</th>
                                                <th>Period ID</th>
                                                <th>Prediction</th>
                                                <th>Number</th>
                                                <th>Type</th>
                                                <th>Amount</th>
                                                <th>Step</th>
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
                                                            <span class="badge bg-label-primary">
                                                                <?= htmlspecialchars($game_types[$row['game_type']] ?? $row['game_type']); ?>
                                                            </span>
                                                        </td>
                                                        <td><?= htmlspecialchars($row['period_id']); ?></td>
                                                        <td>
                                                            <span class="badge 
                                                                <?= strpos($row['prediction_color'], 'green') !== false ? 'bg-success' : 'bg-danger'; ?>">
                                                                <?= htmlspecialchars($row['prediction_color']); ?>
                                                            </span>
                                                        </td>
                                                        <td><strong><?= $row['prediction_number']; ?></strong></td>
                                                        <td>
                                                            <span class="badge bg-label-info">
                                                                <?= htmlspecialchars($row['type']); ?>
                                                            </span>
                                                        </td>
                                                        <td>₹<?= $row['amount']; ?></td>
                                                        <td>
                                                            <span class="badge bg-label-warning">
                                                                <?= $row['step']; ?>x
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="badge 
                                                                <?= $row['status'] == 'sent' ? 'bg-label-success' : 'bg-label-danger'; ?>">
                                                                <?= ucfirst($row['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td><?= date('d-m-Y H:i:s', strtotime($row['created_at'])); ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="10" class="text-center py-4">
                                                        <div class="text-muted">
                                                            <i class="ri-inbox-line display-4"></i>
                                                            <p class="mt-2 mb-0">No prediction history found</p>
                                                            <?php if (isset($_GET['history_date'])): ?>
                                                                <p>for date: <?= $_GET['history_date']; ?></p>
                                                            <?php endif; ?>
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
                    <div class="content-backdrop fade"></div>
                </div>
            </div>
        </div>
        <div class="layout-overlay layout-menu-toggle"></div>
        <div class="drag-target"></div>
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
        // Game selection functionality
        const gameTypes = <?= json_encode($game_types); ?>;
        const scheduleOptions = <?= json_encode($schedule_options); ?>;
        
        document.getElementById('addGameBtn').addEventListener('click', function() {
            const selector = document.getElementById('gameSelector');
            const selectedValue = selector.value;
            
            if (!selectedValue) {
                alert('Please select a game first');
                return;
            }
            
            // Check schedule type
            const scheduleType = document.getElementById('schedule_type').value;
            if (scheduleType === '10min' && selectedValue !== 'gelluonduhogu_zehn') {
                alert('10 minute schedule only allows Wingo 30Sec game');
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
                ${gameTypes[selectedValue]}
                <input type="hidden" name="games[]" value="${selectedValue}">
                <span class="remove-btn" onclick="removeGame(this)">×</span>
            `;
            container.appendChild(gameTag);
            
            // Remove from selector
            selector.remove(selector.selectedIndex);
            selector.value = '';
            
            // Remove "No games selected" message if exists
            const noGamesMsg = container.querySelector('.text-muted');
            if (noGamesMsg) {
                noGamesMsg.remove();
            }
        });
        
        function removeGame(element) {
            const gameTag = element.parentElement;
            const gameValue = gameTag.querySelector('input').value;
            const gameName = gameTypes[gameValue];
            
            // Remove from selected
            gameTag.remove();
            
            // Add back to selector
            const selector = document.getElementById('gameSelector');
            const option = document.createElement('option');
            option.value = gameValue;
            option.textContent = gameName;
            selector.appendChild(option);
            
            // Show "No games selected" message if empty
            const container = document.getElementById('selectedGamesContainer');
            if (container.children.length === 0) {
                container.innerHTML = '<div class="text-muted">No games selected yet</div>';
            }
        }
        
        // Schedule type change handler
        document.getElementById('schedule_type').addEventListener('change', function() {
            const scheduleType = this.value;
            const customTimeRange = document.getElementById('custom_time_range');
            const scheduleHint = document.getElementById('schedule_hint');
            
            // Show/hide custom time fields
            if (scheduleType === 'custom') {
                customTimeRange.style.display = 'block';
                scheduleHint.innerHTML = '<span class="text-info">Set custom start and end time for daily operation</span>';
            } else {
                customTimeRange.style.display = 'none';
                
                // Show schedule hint
                const hints = {
                    '10min': 'Predictions will be sent every 10 minutes (30 sec game only)',
                    '1hour': 'Predictions will be sent every hour at :00 (All games)',
                    '6hour': 'Predictions every 6 hours at 0:00, 6:00, 12:00, 18:00 (All games)',
                    '24hour': 'Predictions once daily at current time (All games)'
                };
                scheduleHint.innerHTML = `<span class="text-success">${hints[scheduleType] || ''}</span>`;
            }
            
            // If 10min schedule selected, filter games
            if (scheduleType === '10min') {
                filterGamesFor10MinSchedule();
            } else {
                enableAllGames();
            }
            
            // Update next run time
            updateNextRunTime();
        });
        
        function filterGamesFor10MinSchedule() {
            const selector = document.getElementById('gameSelector');
            const selectedGamesContainer = document.getElementById('selectedGamesContainer');
            const selectedGames = selectedGamesContainer.querySelectorAll('.game-tag');
            
            // Hide non-30sec games from selector
            Array.from(selector.options).forEach(option => {
                if (option.value && option.value !== 'gelluonduhogu_zehn') {
                    option.style.display = 'none';
                    option.disabled = true;
                }
            });
            
            // Remove non-30sec games from selected
            selectedGames.forEach(gameTag => {
                const gameInput = gameTag.querySelector('input[name="games[]"]');
                if (gameInput && gameInput.value !== 'gelluonduhogu_zehn') {
                    // Add back to selector
                    const option = document.createElement('option');
                    option.value = gameInput.value;
                    option.textContent = gameTypes[gameInput.value];
                    selector.appendChild(option);
                    
                    // Remove from selected
                    gameTag.remove();
                }
            });
            
            // Show warning if no 30sec game selected
            if (!selectedGamesContainer.querySelector('input[value="gelluonduhogu_zehn"]')) {
                const warning = document.createElement('div');
                warning.className = 'alert alert-warning alert-sm mt-2';
                warning.innerHTML = '<i class="ri-alert-line me-1"></i>10 minute schedule only works with 30 second game. Please add Wingo 30Sec game.';
                selectedGamesContainer.appendChild(warning);
            }
        }
        
        function enableAllGames() {
            const selector = document.getElementById('gameSelector');
            Array.from(selector.options).forEach(option => {
                option.style.display = 'block';
                option.disabled = false;
            });
            
            // Remove warnings
            const selectedGamesContainer = document.getElementById('selectedGamesContainer');
            const warnings = selectedGamesContainer.querySelectorAll('.alert-warning');
            warnings.forEach(warning => warning.remove());
        }
        
        // Form validation
        document.getElementById('predictionForm').addEventListener('submit', function(e) {
            const botToken = document.querySelector('input[name="bot_token"]').value;
            const chatId = document.querySelector('input[name="chat_id"]').value;
            const games = document.querySelectorAll('input[name="games[]"]');
            const scheduleType = document.getElementById('schedule_type').value;
            
            if (!botToken || !chatId) {
                e.preventDefault();
                alert('Please fill in all required fields (Bot Token and Chat ID)');
                return;
            }
            
            if (games.length === 0) {
                e.preventDefault();
                alert('Please select at least one game for prediction');
                return;
            }
            
            // Validate schedule-specific rules
            if (scheduleType === '10min') {
                const has30SecGame = Array.from(games).some(input => input.value === 'gelluonduhogu_zehn');
                if (!has30SecGame) {
                    e.preventDefault();
                    alert('10 minute schedule requires Wingo 30Sec game to be selected');
                    return;
                }
            }
            
            if (scheduleType === 'custom') {
                const startTime = document.querySelector('input[name="start_time"]').value;
                const endTime = document.querySelector('input[name="end_time"]').value;
                
                if (!startTime || !endTime) {
                    e.preventDefault();
                    alert('Please set both start and end time for custom schedule');
                    return;
                }
                
                if (startTime >= endTime) {
                    e.preventDefault();
                    alert('End time must be after start time');
                    return;
                }
            }
        });
        
        // Update next run time display
        function updateNextRunTime() {
            const scheduleType = document.getElementById('schedule_type').value;
            const isActive = document.getElementById('is_active').checked;
            
            if (!isActive) {
                document.getElementById('next_run_display').textContent = 'Bot is inactive';
                return;
            }
            
            const now = new Date();
            let nextRun = '';
            
            switch(scheduleType) {
                case '10min':
                    const next10Min = new Date(Math.ceil(now.getTime() / (10 * 60000)) * (10 * 60000));
                    nextRun = next10Min.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    break;
                    
                case '1hour':
                    const nextHour = new Date(now.getTime() + 60 * 60000);
                    nextRun = nextHour.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    break;
                    
                case '6hour':
                    const currentHour = now.getHours();
                    let next6Hour = Math.ceil(currentHour / 6) * 6;
                    if (next6Hour >= 24) next6Hour = 0;
                    nextRun = next6Hour.toString().padStart(2, '0') + ':00';
                    break;
                    
                case '24hour':
                    nextRun = 'Tomorrow ' + now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    break;
                    
                case 'custom':
                    const startTime = document.querySelector('input[name="start_time"]')?.value || '09:00';
                    const currentTimeStr = now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    
                    if (currentTimeStr < startTime) {
                        nextRun = startTime + ' (Today)';
                    } else {
                        nextRun = startTime + ' (Tomorrow)';
                    }
                    break;
            }
            
            document.getElementById('next_run_display').textContent = nextRun;
        }
        
        // Update next run time when inputs change
        document.getElementById('schedule_type').addEventListener('change', updateNextRunTime);
        document.getElementById('is_active').addEventListener('change', updateNextRunTime);
        
        // Initial setup
        document.addEventListener('DOMContentLoaded', function() {
            // Trigger change event to set initial state
            const scheduleSelect = document.getElementById('schedule_type');
            if (scheduleSelect) {
                scheduleSelect.dispatchEvent(new Event('change'));
            }
            
            // Auto-refresh page every 60 seconds to update status
            setInterval(() => {
                // Reload only if not in form editing mode
                if (!document.querySelector('.form-control:focus')) {
                    location.reload();
                }
            }, 60000);
        });
    </script>
</body>
</html>