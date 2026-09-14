<?php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'bpfhntqg_diuwin1234');
define('DB_PASSWORD', 'bpfhntqg_diuwin1234');
define('DB_NAME', 'bpfhntqg_diuwin1234');

function getDBConnection() {
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}
?>
