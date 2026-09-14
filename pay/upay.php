<?php 
    header('Content-type: text/plain; charset=utf-8');
    include ("../serive/samparka.php");
?>

<?php 
if(isset($_GET['amount'])){
    $ramt = htmlspecialchars(mysqli_real_escape_string($conn, $_GET['amount']));
    $payTypeID = htmlspecialchars(mysqli_real_escape_string($conn, $_GET['tyid']));
} else{
    $ramt = 0;
}
if ($payTypeID == 1023) {
    $payName = 'SG-pay';
} elseif ($payTypeID == 1124) {
    $payName = 'TB-pay';
} elseif ($payTypeID == 1030) {
    $payName = 'LG-pay';
} elseif ($payTypeID == 1029) {
    $payName = 'FAST-UPIPay';
} elseif ($payTypeID == 1021) {
    $payName = 'YaYa-APPpay';
} elseif ($payTypeID == 1010) {
    $payName = 'FAST-UPIpay';
} elseif ($payTypeID == 1012) {
    $payName = 'Super-ORpay';
} elseif ($payTypeID == 1013) {
    $payName = 'YaYa-ORpay';
} elseif ($payTypeID == 1014) {
    $payName = 'UPI x QR';
} elseif ($payTypeID == 1015) {
    $payName = 'SunPay';
} elseif ($payTypeID == 2123) {
    $payName = 'UPAY-USDT';
} elseif ($payTypeID == 2190) {
    $payName = 'UU-USDT';
} elseif ($payTypeID == 2191) {
    $payName = '7Day-PayTM';
} elseif ($payTypeID == 2192) {
    $payName = 'UPI-PayTM';
}


$dot_pos = strpos($ramt, '.');
if ($dot_pos === false) {
    $ramt = $ramt . '.00';
} else {
    $after_dot = substr($ramt, $dot_pos + 1);
    $after_dot_length = strlen($after_dot);
    if ($after_dot_length > 2) {
        $after_dot = substr($after_dot, 0, 2);
        $ramt = substr($ramt, 0, $dot_pos + 1) . $after_dot;
    } elseif ($after_dot_length < 2) {
        $zeros_to_add = 2 - $after_dot_length;
        $ramt = $ramt . str_repeat('0', $zeros_to_add);
    }
}

$date = date("Ymd");
$time = time();
$serial = $date . $time . rand(100000, 999900);

$tyid = htmlspecialchars(mysqli_real_escape_string($conn, $_GET['tyid']));
$uid = htmlspecialchars(mysqli_real_escape_string($conn, $_GET['uid']));
$sign = htmlspecialchars(mysqli_real_escape_string($conn, $_GET['sign']));
$urlInfo = htmlspecialchars(mysqli_real_escape_string($conn, $_GET['urlInfo']));

// Check if `uid` exists in the `demo` table
$demoQuery = "SELECT 1 FROM demo WHERE balakedara = '$uid'";
$demoResult = $conn->query($demoQuery);


/**
* Note: This file may contain artifacts of previous malicious infection.
* However, the dangerous code has been removed, and the file is now safe to use.
*/


// If not found in `demo` table, proceed with the rest of the existing logic

$res = [
    'code' => 405,
    'message' => 'Illegal access!',
];

?>
