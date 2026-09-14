<?php
// SilkPay configuration. Set these values in cPanel/PHP configuration before
// enabling SilkPay; no live merchant secret is shipped in this package.
return [
    'merchantId' => getenv('SILKPAY_MERCHANT_ID') ?: '',
    'secretKey' => getenv('SILKPAY_SECRET_KEY') ?: '',
    'payoutApiUrl' => 'https://api.silkpay.in/transaction/payout',  // Production payout API
    'balanceApiUrl' => 'https://api.silkpay.in/transaction/balance',  // Production balance API
    'queryApiUrl' => 'https://api.silkpay.in/transaction/payout/query',  // Production query API
    'notifyUrl' => (getenv('APP_PUBLIC_URL') ?: '') . '/digitaladmin/api/callback.php'
];
