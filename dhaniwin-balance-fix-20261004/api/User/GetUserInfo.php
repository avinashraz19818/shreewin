<?php
require_once dirname(__DIR__) . '/_bootstrap.php';

// Balance endpoints always answer from the database (no saved snapshot).

$payload = api_success(api_user_info_data());
api_refresh_times($payload);
api_emit($payload);
