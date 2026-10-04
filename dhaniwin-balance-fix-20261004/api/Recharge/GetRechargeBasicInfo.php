<?php
require_once dirname(__DIR__) . '/_bootstrap.php';

// Balance endpoints always answer from the database (no saved snapshot, no
// hard-coded amounts) so an approved deposit is visible right away.

$payload = api_recharge_basic_info_payload();
api_refresh_times($payload);
api_emit($payload);
