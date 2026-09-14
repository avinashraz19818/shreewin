<?php
include "../../conn.php";
include "../../functions2.php";

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, ar-origin, noloading, timestamp, signature, random, user-agent, *');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header('Content-Type: application/json; charset=utf-8');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
date_default_timezone_set("Asia/Kolkata");

$shnunc = date("Y-m-d H:i:s");

$res = [
    'code' => 11,
    'msg' => 'Method not allowed',
    'msgCode' => 12,
    'serviceNowTime' => $shnunc,
    'data' => null
];

$shonubody = file_get_contents("php://input");
$shonupost = json_decode($shonubody, true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode($res);
    exit();
}

if (!isset($shonupost['language'], $shonupost['logintype'], $shonupost['phonetype'],
          $shonupost['pwd'], $shonupost['username'])) {
    $res['msg']     = 'Missing parameters';
    $res['msgCode'] = 10;
    echo json_encode($res);
    exit();
}

$language  = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['language']));
$logintype = strtolower(trim($shonupost['logintype']));
$phonetype = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['phonetype']));
$pwd       = $shonupost['pwd'];
$username  = trim($shonupost['username']);

// captchaId aur track optional — naye app bheje to verify, purana app skip
if (!empty($shonupost['captchaId']) && !empty($shonupost['track'])) {
    $captchaId    = mysqli_real_escape_string($conn, $shonupost['captchaId']);
    $trackData    = $shonupost['track'];
    $captchaQuery = "SELECT correctPositionx FROM captcha_data WHERE captchaId = '$captchaId' LIMIT 1";
    $captchaResult = $conn->query($captchaQuery);

    if ($captchaResult && mysqli_num_rows($captchaResult) === 1) {
        $captchaRow       = mysqli_fetch_assoc($captchaResult);
        $correctPositionx = $captchaRow['correctPositionx'];
        $userTracks       = $trackData['tracks'] ?? [];
        $lastTrack        = end($userTracks);
        $userPositionx    = $lastTrack['x'] ?? 0;

        if (abs($userPositionx - $correctPositionx) > 5) {
            $res['code']    = 1;
            $res['msg']     = 'Verification failed, please try again';
            $res['msgCode'] = 31;
            echo json_encode($res);
            exit();
        }
    } else {
        $res['code']    = 2;
        $res['msg']     = 'Captcha not found or invalid';
        $res['msgCode'] = 2;
        echo json_encode($res);
        exit();
    }
}

if ($logintype === 'mobile' && substr($username, 0, 2) === "91") {
    $username = substr($username, 2);
}

if ($logintype === 'mobile') {
    $sql = "SELECT id, password, status, ishonup, codechorkamukala, mobile, email 
            FROM shonu_subjects WHERE mobile = ? LIMIT 1";
} elseif ($logintype === 'email') {
    $sql = "SELECT id, password, status, ishonup, codechorkamukala, mobile, email 
            FROM shonu_subjects WHERE email = ? LIMIT 1";
} else {
    $res['code']    = 1;
    $res['msg']     = 'Invalid login type';
    $res['msgCode'] = 100;
    echo json_encode($res);
    exit();
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = app_stmt_result($stmt);

if ($result->num_rows !== 1) {
    $res['code']    = 1;
    $res['msg']     = 'User not exists';
    $res['msgCode'] = 101;
    echo json_encode($res);
    exit();
}

$user = $result->fetch_assoc();

if ($user['password'] !== md5($pwd)) {
    $conn->query("UPDATE shonu_subjects SET shonupwderr = shonupwderr + 1 WHERE id = '{$user['id']}'");
    $err = $conn->query("SELECT shonupwderr FROM shonu_subjects WHERE id = '{$user['id']}'")->fetch_assoc();

    $data = [
        'token'               => null,
        'refreshToken'        => null,
        'expiresIn'           => 0,
        'passwordErrorNum'    => (int)($err['shonupwderr'] ?? 1),
        'passwordErrorMaxNum' => 30
    ];

    $res['data']    = $data;
    $res['code']    = 1;
    $res['msg']     = 'Password incorrect';
    $res['msgCode'] = 117;
    echo json_encode($res);
    exit();
}

if ($user['status'] != 1) {
    $res['code']    = 1;
    $res['msg']     = 'User suspended';
    $res['msgCode'] = 116;
    echo json_encode($res);
    exit();
}

$expiresIn = time() + 86400;

$payload = [
    'id'               => $user['id'],
    'mobile'           => $user['mobile'] ?? '',
    'email'            => $user['email'] ?? '',
    'status'           => $user['status'],
    'expire'           => $expiresIn,
    'ishonup'          => $user['ishonup'],
    'codechorkamukala' => $user['codechorkamukala']
];

$refreshPayload = [
    'id'     => $user['id'],
    'expire' => time() + 604800
];

$data = [
    'expiresIn'           => $expiresIn,
    'tokenHeader'         => 'Bearer ',
    'token'               => generate_jwt(['alg' => 'HS256', 'typ' => 'JWT'], $payload),
    'refreshToken'        => generate_jwt(['alg' => 'HS256', 'typ' => 'JWT'], $refreshPayload),
    'passwordErrorNum'    => 0,
    'passwordErrorMaxNum' => 30
];

$ipaddress = 'UNKNOWN';
if (!empty($_SERVER['HTTP_CLIENT_IP']))           $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
elseif (!empty($_SERVER['REMOTE_ADDR']))           $ipaddress = $_SERVER['REMOTE_ADDR'];

$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

$update_sql = "UPDATE shonu_subjects SET 
                shonupwderr = 0,
                ishonup = '$ipaddress',
                shonullgnt = '$shnunc',
                akshinak = '{$data['token']}',
                tnegaresunohs = '$user_agent'
               WHERE id = '{$user['id']}'";
$conn->query($update_sql);

$loginMsg = "User logged in at $shnunc";
if (!empty($user['mobile'])) $loginMsg .= " (Mobile: {$user['mobile']})";
if (!empty($user['email']))   $loginMsg .= " (Email: {$user['email']})";

$notif_sql = "INSERT INTO notification (state, title, user_id, message, created_at) 
              VALUES (0, 'Login Alert', '{$user['id']}', '$loginMsg', '$shnunc')";
$conn->query($notif_sql);

$res['data']    = $data;
$res['code']    = 0;
$res['msg']     = 'Succeed';
$res['msgCode'] = 0;

http_response_code(200);
echo json_encode($res, JSON_UNESCAPED_SLASHES);
exit();
?>