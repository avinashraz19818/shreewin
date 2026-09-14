<?php 
include "../../conn.php";
include "../../functions2.php";

header('Content-Type: application/json; charset=utf-8');
header('Strict-Transport-Security: max-age=31536000');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, ar-origin');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Credentials: true');

date_default_timezone_set('Asia/Kolkata');
$shnunc = date("Y-m-d H:i:s");

$res = [
    'code' => 11,
    'msg' => 'Method not allowed',
    'msgCode' => 12,
    'serviceNowTime' => $shnunc,
];

$shonubody = file_get_contents("php://input");
$shonupost = json_decode($shonubody, true);

if ($_SERVER['REQUEST_METHOD'] != 'GET') {
    if (isset($shonupost['language'], $shonupost['random'], $shonupost['signature'], $shonupost['timestamp'])) {
        $language = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['language']));
        $random = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['random']));
        $signature = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['signature']));

        // Build the string for signature verification
        $shonustr = '{"language":' . $language . ',"random":"' . $random . '"}';
        $shonusign = strtoupper(md5($shonustr));

        if ($shonusign === $signature) {
            $bearer = explode(" ", $_SERVER['HTTP_AUTHORIZATION'] ?? '');
            $author = $bearer[1] ?? null;

            if (!$author) {
                $res['code'] = 403;
                $res['msg'] = 'Token missing';
                $res['msgCode'] = 99;
                http_response_code(403);
                echo json_encode($res);
                exit;
            }

            $is_jwt_valid = is_jwt_valid($author);
            $data_auth = json_decode($is_jwt_valid, true);

            if ($data_auth['status'] === 'Success') {
                $userId = (int)$data_auth['payload']['id'];

                $sesquery = "SELECT akshinak FROM shonu_subjects WHERE akshinak = '$author'";
                $sesresult = $conn->query($sesquery);

                if ($sesresult && $sesresult->num_rows === 1) {

                    // 🎯 ONLY ARGame balance query
                    $arQuery = "SELECT balance FROM argame_balances WHERE user_id = '$userId'";
                    $arResult = $conn->query($arQuery);
                    $arBalance = 0.0;
                    if ($arResult && $arResult->num_rows > 0) {
                        $arRow = $arResult->fetch_assoc();
                        $arBalance = (float)$arRow['balance'];
                    }

                    $data = [
                        "vendorCode" => "ARGame",
                        "balance" => $arBalance
                    ];

                    $res['data'] = $data;
                    $res['code'] = 0;
                    $res['msg'] = 'Succeed';
                    $res['msgCode'] = 0;
                    http_response_code(200);
                } else {
                    $res['code'] = 4;
                    $res['msg'] = 'No operation permission';
                    $res['msgCode'] = 2;
                    http_response_code(401);
                }
            } else {
                $res['code'] = 4;
                $res['msg'] = 'Invalid JWT token';
                $res['msgCode'] = 2;
                http_response_code(401);
            }
        } else {
            $res['code'] = 5;
            $res['msg'] = 'Wrong signature';
            $res['msgCode'] = 3;
            http_response_code(200);
        }
    } else {
        $res['code'] = 7;
        $res['msg'] = 'Param is Invalid';
        $res['msgCode'] = 6;
        http_response_code(200);
    }
} else {
    http_response_code(405);
}

echo json_encode($res);
?>
