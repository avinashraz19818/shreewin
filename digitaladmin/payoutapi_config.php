<?php
// Payout API Configuration - Load credentials from RupeeRush
// Based on provided merchant details

define('PAY_URL', 'https://api.rupeerush.cc'); // Gateway Domain
define('MERCHANT_ID', 'RUP250917093320265'); // Merchant ID
define('SECRET_KEY', 'rup2025'); // Secret Key (Replace with actual from Security Center)
define('NOTIFY_URL', 'https://shreewin.club9.eu.cc/adminab/notify.php'); // Production callback URL
define('CURRENCY_CODE', 'INR'); // Indian Rupee for INRO/INRT methods

// Callback IPs for whitelisting (security)
define('ALLOWED_CALLBACK_IPS', [
    '8.222.246.219',
    '47.245.81.104',
    '47.236.92.61'
]);

// Payment Methods (from docs)
define('PAYMENT_METHODS', [
    'INRO' => 'Native INR',
    'INRT' => 'Wake-up',
    'SCAN' => 'Scan code'
]);

// Error logging disabled for production
define('LOG_PAYOUT_ERRORS', false);

// Security Notes:
// - Login: https://merch.rupeerush.cc/ (rudrashiv0011@proton.me / 20250917)
// - Change password and bind Google OTP immediately
// - Update SECRET_KEY from Merchant Backend > Security Center
