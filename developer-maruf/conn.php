<?php
require_once __DIR__ . '/error_logger.php';

/*

This file contains database config.phpuration assuming you are running mysql using user "root" and password ""

*/

date_default_timezone_set('Asia/Kolkata');



define('DB_SERVER', 'localhost');

define('DB_USERNAME', 'club532583_shreewin');

define('DB_PASSWORD', 'club532583_shreewin');

define('DB_NAME', 'club532583_shreewin');



// Try connecting to the Database

$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);



//Check the connection

if ($conn === false) {
    app_log_event('critical', 'Database connection failed', [
        'database_error' => mysqli_connect_error(),
        'database_error_number' => mysqli_connect_errno(),
    ]);
    http_response_code(500);
    exit('Database connection unavailable');
}

require_once dirname(__DIR__) . '/database_auto.php';



?>
