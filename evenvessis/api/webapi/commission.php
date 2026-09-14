<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

$logFile = 'commission_debug_' . date('Y-m-d') . '.log';

function debug_log($msg) {
    global $logFile;
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $msg . "\n", FILE_APPEND);
}

debug_log("========== COMMISSION.PHP STARTED ==========");
debug_log("Bet amount: $totalamount, User ID: $byabaharkarta");

// Commission rates
$level1commission = 0.40;
$level2commission = 0.25;
$level3commission = 0.08;
$level4commission = 0.01;
$level5commission = 0.005;
$level6commission = 0.002;

$level1 = (floatval($totalamount) * floatval($level1commission) / 100);
$level2 = (floatval($totalamount) * floatval($level2commission) / 100);
$level3 = (floatval($totalamount) * floatval($level3commission) / 100);
$level4 = (floatval($totalamount) * floatval($level4commission) / 100);
$level5 = (floatval($totalamount) * floatval($level5commission) / 100);
$level6 = (floatval($totalamount) * floatval($level6commission) / 100);

debug_log("Level commissions: L1=$level1, L2=$level2, L3=$level3, L4=$level4, L5=$level5, L6=$level6");

// Get user's referral codes
$codesquery = "SELECT code, code1, code2, code3, code4, code5 FROM shonu_subjects WHERE id = '".$byabaharkarta."'";
debug_log("Codes query: $codesquery");
$codesresult = $conn->query($codesquery);

if(!$codesresult) {
    debug_log("ERROR in codes query: " . $conn->error);
} else {
    $codesarr = mysqli_fetch_array($codesresult);
    $code = isset($codesarr['code']) ? $codesarr['code'] : null;
    $code1 = isset($codesarr['code1']) ? $codesarr['code1'] : null;
    $code2 = isset($codesarr['code2']) ? $codesarr['code2'] : null;
    $code3 = isset($codesarr['code3']) ? $codesarr['code3'] : null;
    $code4 = isset($codesarr['code4']) ? $codesarr['code4'] : null;
    $code5 = isset($codesarr['code5']) ? $codesarr['code5'] : null;
    debug_log("Referral codes: code=$code, code1=$code1, code2=$code2, code3=$code3, code4=$code4, code5=$code5");
}

$nextDate = date('Y-m-d', strtotime('+1 day'));
debug_log("Next process date: $nextDate");

// ============================================
// LEVEL 1 COMMISSION
// ============================================
if($code != null && $code != ''){
    $codeqr = "SELECT id FROM shonu_subjects WHERE owncode = '".$code."'";
    debug_log("Level1 - Find upline query: $codeqr");
    $coders = $conn->query($codeqr);
    
    if($coders && mysqli_num_rows($coders) > 0) {
        $codear = mysqli_fetch_array($coders);
        $codeid = $codear['id'];
        debug_log("Level1 - Upline found: $codeid");
        
        // Get current balance for this user to use as purba
        $balQuery = "SELECT motta FROM shonu_kaichila WHERE balakedara = '$codeid'";
        $balResult = $conn->query($balQuery);
        $balRow = mysqli_fetch_array($balResult);
        $currentBalance = isset($balRow['motta']) ? floatval($balRow['motta']) : 0;
        $newBalance = $currentBalance + $level1;
        
        // ✅ FIXED: Added purba and bartaman columns
        $insert = "INSERT INTO vyavahara (
            balakedara, ketebida, prakara, purba, bartaman, ayoga, koduvavanu, tiarikala, commission_status, process_date
        ) VALUES (
            '$codeid', '$totalamount', 'LVLCOMM1', '$currentBalance', '$newBalance', '$level1', '$byabaharkarta', '$shnunc', 0, '$nextDate'
        )";
        debug_log("Level1 - Insert query: $insert");
        
        $tathya = mysqli_query($conn, $insert);
        if($tathya) {
            debug_log("Level1 - ✅ INSERT SUCCESSFUL");
        } else {
            debug_log("Level1 - ❌ INSERT FAILED: " . mysqli_error($conn));
        }
    } else {
        debug_log("Level1 - No upline found for code: $code");
    }
}

// ============================================
// LEVEL 2 COMMISSION
// ============================================
if($code1 != null && $code1 != ''){
    $codeqr = "SELECT id FROM shonu_subjects WHERE owncode = '".$code1."'";
    debug_log("Level2 - Find upline query: $codeqr");
    $coders = $conn->query($codeqr);
    
    if($coders && mysqli_num_rows($coders) > 0) {
        $codear = mysqli_fetch_array($coders);
        $codeid = $codear['id'];
        debug_log("Level2 - Upline found: $codeid");
        
        // Get current balance
        $balQuery = "SELECT motta FROM shonu_kaichila WHERE balakedara = '$codeid'";
        $balResult = $conn->query($balQuery);
        $balRow = mysqli_fetch_array($balResult);
        $currentBalance = isset($balRow['motta']) ? floatval($balRow['motta']) : 0;
        $newBalance = $currentBalance + $level2;
        
        // ✅ FIXED: Added purba and bartaman columns
        $insert = "INSERT INTO vyavahara (
            balakedara, ketebida, prakara, purba, bartaman, ayoga, koduvavanu, tiarikala, commission_status, process_date
        ) VALUES (
            '$codeid', '$totalamount', 'LVLCOMM2', '$currentBalance', '$newBalance', '$level2', '$byabaharkarta', '$shnunc', 0, '$nextDate'
        )";
        debug_log("Level2 - Insert query: $insert");
        
        $tathya = mysqli_query($conn, $insert);
        if($tathya) {
            debug_log("Level2 - ✅ INSERT SUCCESSFUL");
        } else {
            debug_log("Level2 - ❌ INSERT FAILED: " . mysqli_error($conn));
        }
    } else {
        debug_log("Level2 - No upline found for code: $code1");
    }
}

// ============================================
// LEVEL 3 COMMISSION
// ============================================
if($code2 != null && $code2 != ''){
    $codeqr = "SELECT id FROM shonu_subjects WHERE owncode = '".$code2."'";
    debug_log("Level3 - Find upline query: $codeqr");
    $coders = $conn->query($codeqr);
    
    if($coders && mysqli_num_rows($coders) > 0) {
        $codear = mysqli_fetch_array($coders);
        $codeid = $codear['id'];
        debug_log("Level3 - Upline found: $codeid");
        
        // Get current balance
        $balQuery = "SELECT motta FROM shonu_kaichila WHERE balakedara = '$codeid'";
        $balResult = $conn->query($balQuery);
        $balRow = mysqli_fetch_array($balResult);
        $currentBalance = isset($balRow['motta']) ? floatval($balRow['motta']) : 0;
        $newBalance = $currentBalance + $level3;
        
        // ✅ FIXED: Added purba and bartaman columns
        $insert = "INSERT INTO vyavahara (
            balakedara, ketebida, prakara, purba, bartaman, ayoga, koduvavanu, tiarikala, commission_status, process_date
        ) VALUES (
            '$codeid', '$totalamount', 'LVLCOMM3', '$currentBalance', '$newBalance', '$level3', '$byabaharkarta', '$shnunc', 0, '$nextDate'
        )";
        debug_log("Level3 - Insert query: $insert");
        
        $tathya = mysqli_query($conn, $insert);
        if($tathya) {
            debug_log("Level3 - ✅ INSERT SUCCESSFUL");
        } else {
            debug_log("Level3 - ❌ INSERT FAILED: " . mysqli_error($conn));
        }
    } else {
        debug_log("Level3 - No upline found for code: $code2");
    }
}

// ============================================
// LEVEL 4 COMMISSION
// ============================================
if($code3 != null && $code3 != ''){
    $codeqr = "SELECT id FROM shonu_subjects WHERE owncode = '".$code3."'";
    $coders = $conn->query($codeqr);
    
    if($coders && mysqli_num_rows($coders) > 0) {
        $codear = mysqli_fetch_array($coders);
        $codeid = $codear['id'];
        
        // Get current balance
        $balQuery = "SELECT motta FROM shonu_kaichila WHERE balakedara = '$codeid'";
        $balResult = $conn->query($balQuery);
        $balRow = mysqli_fetch_array($balResult);
        $currentBalance = isset($balRow['motta']) ? floatval($balRow['motta']) : 0;
        $newBalance = $currentBalance + $level4;
        
        // ✅ FIXED: Added purba and bartaman columns
        $insert = "INSERT INTO vyavahara (
            balakedara, ketebida, prakara, purba, bartaman, ayoga, koduvavanu, tiarikala, commission_status, process_date
        ) VALUES (
            '$codeid', '$totalamount', 'LVLCOMM4', '$currentBalance', '$newBalance', '$level4', '$byabaharkarta', '$shnunc', 0, '$nextDate'
        )";
        $tathya = mysqli_query($conn, $insert);
        if($tathya) {
            debug_log("Level4 - ✅ INSERT SUCCESSFUL");
        } else {
            debug_log("Level4 - ❌ INSERT FAILED: " . mysqli_error($conn));
        }
    }
}

// ============================================
// LEVEL 5 COMMISSION
// ============================================
if($code4 != null && $code4 != ''){
    $codeqr = "SELECT id FROM shonu_subjects WHERE owncode = '".$code4."'";
    $coders = $conn->query($codeqr);
    
    if($coders && mysqli_num_rows($coders) > 0) {
        $codear = mysqli_fetch_array($coders);
        $codeid = $codear['id'];
        
        // Get current balance
        $balQuery = "SELECT motta FROM shonu_kaichila WHERE balakedara = '$codeid'";
        $balResult = $conn->query($balQuery);
        $balRow = mysqli_fetch_array($balResult);
        $currentBalance = isset($balRow['motta']) ? floatval($balRow['motta']) : 0;
        $newBalance = $currentBalance + $level5;
        
        // ✅ FIXED: Added purba and bartaman columns
        $insert = "INSERT INTO vyavahara (
            balakedara, ketebida, prakara, purba, bartaman, ayoga, koduvavanu, tiarikala, commission_status, process_date
        ) VALUES (
            '$codeid', '$totalamount', 'LVLCOMM5', '$currentBalance', '$newBalance', '$level5', '$byabaharkarta', '$shnunc', 0, '$nextDate'
        )";
        $tathya = mysqli_query($conn, $insert);
        if($tathya) {
            debug_log("Level5 - ✅ INSERT SUCCESSFUL");
        } else {
            debug_log("Level5 - ❌ INSERT FAILED: " . mysqli_error($conn));
        }
    }
}

// ============================================
// LEVEL 6 COMMISSION
// ============================================
if($code5 != null && $code5 != ''){
    $codeqr = "SELECT id FROM shonu_subjects WHERE owncode = '".$code5."'";
    $coders = $conn->query($codeqr);
    
    if($coders && mysqli_num_rows($coders) > 0) {
        $codear = mysqli_fetch_array($coders);
        $codeid = $codear['id'];
        
        // Get current balance
        $balQuery = "SELECT motta FROM shonu_kaichila WHERE balakedara = '$codeid'";
        $balResult = $conn->query($balQuery);
        $balRow = mysqli_fetch_array($balResult);
        $currentBalance = isset($balRow['motta']) ? floatval($balRow['motta']) : 0;
        $newBalance = $currentBalance + $level6;
        
        // ✅ FIXED: Added purba and bartaman columns
        $insert = "INSERT INTO vyavahara (
            balakedara, ketebida, prakara, purba, bartaman, ayoga, koduvavanu, tiarikala, commission_status, process_date
        ) VALUES (
            '$codeid', '$totalamount', 'LVLCOMM6', '$currentBalance', '$newBalance', '$level6', '$byabaharkarta', '$shnunc', 0, '$nextDate'
        )";
        $tathya = mysqli_query($conn, $insert);
        if($tathya) {
            debug_log("Level6 - ✅ INSERT SUCCESSFUL");
        } else {
            debug_log("Level6 - ❌ INSERT FAILED: " . mysqli_error($conn));
        }
    }
}

debug_log("========== COMMISSION.PHP ENDED ==========\n");
?>