<?php

/**
 * Database bootstrap for the developer-maruf application tree.
 *
 * IMPORTANT (VeerGame hotfix): the error logger is OPTIONAL. A missing include
 * used to abort the request with a PHP fatal error before the connection was
 * even attempted, which made every lottery/webapi call return an HTML 500
 * instead of JSON (the lottery screen then loads forever / shows an error).
 * The includes below can therefore never take the site down again.
 */

$__appErrorLogger = __DIR__ . '/error_logger.php';
if (is_file($__appErrorLogger)) {
    require_once $__appErrorLogger;
} else {
    // Minimal fallback so app_log_event() is always callable.
    if (!function_exists('app_log_event')) {
        function app_log_event($level, $message, array $context = array())
        {
            error_log('[' . strtoupper((string) $level) . '] ' . $message . ' ' . json_encode($context));
        }
    }
    if (!function_exists('app_request_id')) {
        function app_request_id()
        {
            return str_replace('.', '', uniqid('req', true));
        }
    }
}
unset($__appErrorLogger);

/*
 * This file contains database configuration assuming you are running mysql using user "root" and password ""
 */

date_default_timezone_set('Asia/Kolkata');

if (!defined('DB_SERVER')) {
    define('DB_SERVER', 'localhost');
    define('DB_USERNAME', 'club532583_veergame');
    define('DB_PASSWORD', 'club532583_veergame');
    define('DB_NAME', 'club532583_veergame');
}

// Try connecting to the Database
// Keep mysqli from throwing before the explicit error handling below runs.
if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

$conn = @mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check the connection
if ($conn === false) {
    if (function_exists('app_log_event')) {
        app_log_event('critical', 'Database connection failed', array(
            'database_error' => mysqli_connect_error(),
            'database_error_number' => mysqli_connect_errno(),
        ));
    }
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(array(
        'data' => null,
        'code' => 8,
        'msg' => 'Database connection unavailable',
        'msgCode' => 8,
        'serviceNowTime' => date('Y-m-d H:i:s'),
    ));
    exit;
}

if ($conn instanceof mysqli) {
    @$conn->set_charset('utf8mb4');
}

// mysqli_stmt::get_result() is unavailable on non-mysqlnd PHP builds.
// Load the portable replacement before any query code runs.
$__appMysqliCompat = dirname(__DIR__) . '/mysqli_compat.php';
if (is_file($__appMysqliCompat)) {
    require_once $__appMysqliCompat;
}
unset($__appMysqliCompat);

$__appDatabaseAuto = dirname(__DIR__) . '/database_auto.php';
if (is_file($__appDatabaseAuto)) {
    require_once $__appDatabaseAuto;
}
unset($__appDatabaseAuto);
