<?php

/**
 * ARPay pending-order preflight used when the ARPay tab is selected.
 *
 * This installation uses the same local UPI payment page for ARPay and does
 * not keep a separate external ARPay session URL. Returning null tells the
 * bundled frontend that no older ARPay order needs to be resumed.
 */

require_once __DIR__ . '/_common.php';

api_require_post();
$body = api_input();
api_require_signature($body);
api_user();

api_send(null);

