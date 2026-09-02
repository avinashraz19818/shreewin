<?php
session_start();
require_once 'libs/GoogleAuthenticator.php';



if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $enteredOTP = $_POST['otp'];

    $gAuth = new PHPGangsta_GoogleAuthenticator();
    $secret = '5LAHG427PS7E4ALK'; // Database se fetched secret key

    // OTP Verify Karna
    $checkResult = $gAuth->verifyCode($secret, $enteredOTP, 2); // 2 = 2-minute window

    if ($checkResult) {
        // OTP successful hua — Dashboard le jayein
        $_SESSION['otp_verified'] = true;
        header("Location: dashboard.php");
        exit;
    } else {
        // OTP fail hua — Error message
        $error = "Invalid OTP. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OTP Verification</title>
</head>
<body>
    <h2>Enter OTP from Google Authenticator</h2>
    <form method="POST">
        <label for="otp">Enter OTP:</label>
        <input type="text" id="otp" name="otp" required>
        <button type="submit">Verify OTP</button>
    </form>
    
    <?php if(isset($error)) { ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php } ?>
</body>
</html>
