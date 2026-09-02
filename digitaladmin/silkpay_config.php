<?php
// SilkPay Configuration
return [
    'merchantId' => 'TEST', // Replace with your production merchant ID
    'secretKey' => 'SIb3DQEBAQ', // Replace with your production secret key
    'payoutApiUrl' => 'https://api.dev.silkpay.in/transaction/payout', // Use https://api.silkpay.in for production
    'balanceApiUrl' => 'https://api.dev.silkpay.in/transaction/balance', // Use https://api.silkpay.in for production
    'queryApiUrl' => 'https://api.dev.silkpay.in/transaction/payout/query', // Use https://api.silkpay.in for production
    'notifyUrl' => 'https://yourdomain.com/callback.php' // Replace with your public callback URL
];
?>