<?php
require_once 'libs/GoogleAuthenticator.php';

$gAuth = new PHPGangsta_GoogleAuthenticator();
$secret = $gAuth->createSecret(); // Generate Secret Key

echo "Generated Secret Key: $secret<br>";




$qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=otpauth://totp/Admin%20Panel?secret={$secret}";

echo "<h2>Scan this QR Code in Google Authenticator App:</h2>";
echo "<img src='{$qrCodeUrl}' alt='QR Code' />";
?>
