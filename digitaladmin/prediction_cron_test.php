<?php
/**
 * Test Script for Prediction Bot
 * Use this to manually test the bot without setting up cron
 */

include("api/conn.php");
date_default_timezone_set("Asia/Kolkata");

echo "<pre>";
echo "=== PREDICTION BOT TEST ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

// Get bot settings
$result = mysqli_query($conn, "SELECT * FROM auto_prediction_settings LIMIT 1");
if (mysqli_num_rows($result) === 0) {
    echo "❌ No bot settings found\n";
    exit;
}

$settings = mysqli_fetch_assoc($result);
echo "✓ Bot Settings Loaded\n";
echo "Bot Status: " . ($settings['is_active'] ? "Active ✅" : "Inactive ❌") . "\n";
echo "Schedule Type: " . $settings['schedule_type'] . "\n";
echo "Enabled Games: " . $settings['enabled_games'] . "\n\n";

// Check schedule
$current_minute = (int)date('i');
$current_hour = (int)date('H');
$current_time = date('H:i');

$should_run = false;
switch($settings['schedule_type']) {
    case '10min':
        $should_run = ($current_minute % 10 == 0);
        echo "10min Schedule Check: minute $current_minute -> " . ($should_run ? "SHOULD RUN" : "SHOULD NOT RUN") . "\n";
        break;
    case '1hour':
        $should_run = ($current_minute == 0);
        echo "1hour Schedule Check: minute $current_minute -> " . ($should_run ? "SHOULD RUN" : "SHOULD NOT RUN") . "\n";
        break;
    case '6hour':
        $should_run = ($current_minute == 0 && in_array($current_hour, [0, 6, 12, 18]));
        echo "6hour Schedule Check: hour $current_hour -> " . ($should_run ? "SHOULD RUN" : "SHOULD NOT RUN") . "\n";
        break;
    case '24hour':
        $start_time = !empty($settings['start_time']) ? $settings['start_time'] : '00:00';
        $should_run = ($current_time == $start_time);
        echo "24hour Schedule Check: current $current_time, target $start_time -> " . ($should_run ? "SHOULD RUN" : "SHOULD NOT RUN") . "\n";
        break;
    case 'custom':
        if (!empty($settings['start_time']) && !empty($settings['end_time'])) {
            $within_time = ($current_time >= $settings['start_time'] && $current_time < $settings['end_time']);
            $should_run = $within_time && ($current_minute % 10 == 0);
            echo "Custom Schedule Check: $current_time between $settings[start_time]-$settings[end_time] -> " . ($should_run ? "SHOULD RUN" : "SHOULD NOT RUN") . "\n";
        }
        break;
}

echo "\n";

// Test Telegram connection
if (!empty($settings['bot_token'])) {
    echo "Testing Telegram Connection...\n";
    
    $test_url = "https://api.telegram.org/bot{$settings['bot_token']}/getMe";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $test_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    if (isset($data['ok']) && $data['ok']) {
        echo "✅ Telegram Bot: " . $data['result']['first_name'] . " (@{$data['result']['username']})\n";
    } else {
        echo "❌ Telegram Error: " . ($data['description'] ?? 'Unknown error') . "\n";
    }
}

echo "\n=== TEST COMPLETE ===\n";
echo "</pre>";

// Option to force run
echo "<form method='POST' style='margin-top: 20px;'>
    <button type='submit' name='force_run' style='padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;'>
        🔥 FORCE RUN BOT NOW
    </button>
</form>";

if (isset($_POST['force_run'])) {
    echo "<hr><h3>Force Running Bot...</h3><pre>";
    
    // Include and run cron_bot.php functions
    include("cron_bot.php");
    
    echo "</pre>";
}
?>