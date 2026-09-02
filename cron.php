<?php 
// Credit: https://t.me/zayro_o

function run($path) {
    // Credit: https://t.me/zayro_o
    $php = "/usr/local/bin/php"; // Adjust if needed
    exec("$php $path > /dev/null 2>&1 &");
}

// Credit: https://t.me/zayro_o
// Get current minute
$minute = (int) date('i');
$hour = (int) date('H');

// Settle the previous day's six-level SaaS team commission once after midnight.
if ($hour === 0 && $minute < 10) {
    run(__DIR__ . "/team_commission_cron.php");
}

// Always run every-minute scripts
// Credit: https://t.me/zayro_o
run("/home/even216186/public_html/niyamitakelasa_zehn.php");
sleep(30);
run("/home/even216186/public_html/niyamitakelasa_zehn.php");
run("/home/even216186/public_html/niyamitakelasa.php");
run("/home/even216186/public_html/niyamitakelasa_aidudi.php");
run("/home/even216186/public_html/niyamitakelasa_kemuru.php");
run("/home/even216186/public_html/ktrx.php");

// Every 3 minutes
if ($minute % 3 === 0) {
    // Credit: https://t.me/zayro_o
    run("/home/even216186/public_html/niyamitakelasa_drei.php");
    run("/home/even216186/public_html/niyamitakelasa_aidudi_drei.php");
    run("/home/even216186/public_html/niyamitakelasa_kemuru_drei.php");
    run("/home/even216186/public_html/ktrx3.php");
}

// Every 5 minutes
if ($minute % 5 === 0) {
    // Credit: https://t.me/zayro_o
    run("/home/even216186/public_html/niyamitakelasa_funf.php");
    run("/home/even216186/public_html/niyamitakelasa_aidudi_funf.php");
    run("/home/even216186/public_html/niyamitakelasa_kemuru_funf.php");
    run("/home/even216186/public_html/ktrx5.php");
}

// Every 10 minutes
if ($minute % 10 === 0) {
    // Credit: https://t.me/zayro_o
    run("/home/even216186/public_html/niyamitakelasa_aidudi_zehn.php");
    run("/home/even216186/public_html/niyamitakelasa_kemuru_zehn.php");
    run("/home/even216186/public_html/ktrx10.php");
}
?>
