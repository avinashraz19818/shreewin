<?php
// SilkPay Configuration - PRODUCTION SETUP
// Log in at https://merchant.silkpay.in/ with Account: F7032, Password: 279b1d33
// Change password immediately, add IP whitelist, and get secret key from Console → Merchant Manage
return [
    'merchantId' => 'F7032',  // Production merchant ID
    'secretKey' => '8Kl9U148h2',  // Get this from Merchant Console → Merchant Manage (replace this placeholder!)
    'payoutApiUrl' => 'https://api.silkpay.in/transaction/payout',  // Production payout API
    'balanceApiUrl' => 'https://api.silkpay.in/transaction/balance',  // Production balance API
    'queryApiUrl' => 'https://api.silkpay.in/transaction/payout/query',  // Production query API
    'notifyUrl' => 'https://shreewin.club9.eu.cc/adminab/api/callback.php'  // Your callback URL (ensure it's public and HTTPS)
];
