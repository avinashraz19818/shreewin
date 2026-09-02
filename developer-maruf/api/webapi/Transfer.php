<?php

require_once __DIR__ . '/_common.php';
api_require_post();
$body = api_input();
api_require_signature($body);
$user = api_user();

// This installation uses one unified balance. The client still calls Transfer
// before entering/recovering a game, so acknowledge the synchronization without
// deducting or duplicating funds.
api_send(['amount' => api_wallet_balance((int)$user['id'])]);

