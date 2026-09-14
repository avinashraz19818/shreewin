<?php
include "../../conn.php";
include "../../functions2.php";

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
date_default_timezone_set("Asia/Kolkata");
$now = date("Y-m-d H:i:s");

$body = file_get_contents("php://input");
$post = json_decode($body, true);

$required = [
    "beneficiaryName",
    "accountNo",
    "mobileNo",
    "random",
    "signature",
    "timestamp"
];

foreach ($required as $key) {
    if (!isset($post[$key])) {
        echo json_encode([
            "code" => 7,
            "msg" => "Param Invalid",
            "msgCode" => 6,
            "serviceNowTime" => $now
        ]);
        exit;
    }
}

$token = "";
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $b = explode(" ", $_SERVER['HTTP_AUTHORIZATION']);
    if (isset($b[1])) $token = $b[1];
}

$valid = is_jwt_valid($token);
$auth = json_decode($valid, true);

if ($auth["status"] != "Success") {
    echo json_encode([
        "code" => 4,
        "msg" => "No operation permission",
        "msgCode" => 2,
        "serviceNowTime" => $now
    ]);
    exit;
}

$user_id = $auth["payload"]["id"];

$name    = mysqli_real_escape_string($conn, $post["beneficiaryName"]);
$account = mysqli_real_escape_string($conn, $post["accountNo"]);
$mobile  = mysqli_real_escape_string($conn, $post["mobileNo"]);

$sql = "INSERT INTO upi_withdrawal (user_id, upi_id, mobile, name)
        VALUES('$user_id','$account','$mobile','$name')
        ON DUPLICATE KEY UPDATE upi_id='$account', mobile='$mobile', name='$name'";

mysqli_query($conn, $sql);

echo json_encode([
    "code" => 0,
    "msg" => "Succeed",
    "msgCode" => 0,
    "serviceNowTime" => $now
]);
exit;
