<?php

/*

This file contains database config.phpuration assuming you are running mysql using user "root" and password ""

*/

date_default_timezone_set('Asia/Kolkata');



define('DB_SERVER', 'localhost');

define('DB_USERNAME', 'club532583_veergame');

define('DB_PASSWORD', 'club532583_veergame');

define('DB_NAME', 'club532583_veergame');



// Try connecting to the Database

$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);



//Check the connection

if($conn == false){

    dir('Error: Cannot connect');

    Echo"Fail";

}




// VeerGame hotfix: mysqli_stmt::get_result() is missing on non-mysqlnd PHP
// builds. Load the portable replacement for every DB caller in this tree.
$__appMysqliCompat = dirname(__DIR__, 2) . '/mysqli_compat.php';
if (is_file($__appMysqliCompat)) {
    require_once $__appMysqliCompat;
}
unset($__appMysqliCompat);

?>
