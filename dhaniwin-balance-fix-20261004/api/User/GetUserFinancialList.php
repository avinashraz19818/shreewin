<?php
require_once dirname(__DIR__) . '/_bootstrap.php';

// Live transaction list (recharges, withdrawals, bets) - no saved snapshot.

$input = api_request_input();
$payload = api_user_financial_payload($input);
api_refresh_times($payload);
api_emit($payload);
