<?php
// Payout API configuration. Add these values in cPanel/PHP configuration
// before enabling the gateway; no live merchant secret is shipped.

define('PAY_URL', 'https://api.rupeerush.cc'); // Gateway Domain
define('MERCHANT_ID', getenv('RUPEERUSH_MERCHANT_ID') ?: '');
define('SECRET_KEY', getenv('RUPEERUSH_SECRET_KEY') ?: '');
define('NOTIFY_URL', (getenv('APP_PUBLIC_URL') ?: '') . '/digitaladmin/notify.php');
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

// Security Notes: configure credentials only on the server, never in source
// control or chat.
// - Update SECRET_KEY from Merchant Backend > Security Center
