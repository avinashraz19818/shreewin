<?php
// path: /api/webapi/GetNewUPIBindMobileNo.php
include "../../conn.php";
include "../../functions2.php";

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, token, Token");
header("Access-Control-Allow-Methods: POST, GET");

date_default_timezone_set("Asia/Kolkata");
$now = date("Y-m-d H:i:s");

// helper: normalize mobile (remove spaces, +, dashes)
function normalize_mobile($m) {
    if ($m === null) return null;
    $m = trim($m);
    $m = str_replace(array(' ', '-', '(', ')', '+'), '', $m);
    return $m;
}

try {

    // Read incoming body (json)
    $body  = file_get_contents("php://input");
    $input = json_decode($body, true);

    // Accept GET query too
    $query = $_GET;

    // Gather candidate keys from multiple possible names
    $candidates = array();

    // From JSON body
    if (is_array($input)) {
        foreach (array('data','mobile','mobileNo','phone','msisdn','phoneNumber','mobile_no') as $k) {
            if (isset($input[$k]) && $input[$k] !== '') {
                $candidates[] = normalize_mobile($input[$k]);
            }
        }
        // also sometimes nested
        if (isset($input['payload']) && is_array($input['payload'])) {
            foreach ($input['payload'] as $k => $v) {
                if (in_array($k, array('data','mobile','mobileNo','phone','msisdn'))) {
                    $candidates[] = normalize_mobile($v);
                }
            }
        }
    }

    // From POST form (application/x-www-form-urlencoded)
    if (!empty($_POST)) {
        foreach (array('data','mobile','mobileNo','phone','msisdn','mobile_no') as $k) {
            if (isset($_POST[$k]) && $_POST[$k] !== '') {
                $candidates[] = normalize_mobile($_POST[$k]);
            }
        }
    }

    // From query string
    foreach (array('data','mobile','mobileNo','phone','msisdn','mobile_no') as $k) {
        if (isset($query[$k]) && $query[$k] !== '') {
            $candidates[] = normalize_mobile($query[$k]);
        }
    }

    // ---------------- TOKEN PICK KARO (MAXIMUM COMPATIBILITY) ----------------
    $token = "";

    // Standard Authorization: Bearer xxx
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $parts = explode(" ", $_SERVER['HTTP_AUTHORIZATION']);
        if (isset($parts[1])) $token = trim($parts[1]);
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $parts = explode(" ", $_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
        if (isset($parts[1])) $token = trim($parts[1]);
    }

    // Some apps send "token" header
    if (!$token && isset($_SERVER['HTTP_TOKEN'])) {
        $token = trim($_SERVER['HTTP_TOKEN']);
    }

    // Body / POST me token param ho sakta hai
    if (!$token) {
        if (is_array($input) && isset($input['token']) && $input['token'] !== '') {
            $token = trim($input['token']);
        } elseif (!empty($_POST['token'])) {
            $token = trim($_POST['token']);
        } elseif (!empty($query['token'])) {
            $token = trim($query['token']);
        }
    }

    $mobile_found = null;

    // ---------------- JWT SE USER ID NIKALO AUR DB SE MOBILE ----------------
    if ($token && function_exists('is_jwt_valid')) {
        $valid = is_jwt_valid($token);

        // Kai scripts me is_jwt_valid array return karta hai, kai me JSON
        if (is_array($valid)) {
            $auth = $valid;
        } else {
            $auth = json_decode($valid, true);
        }

        if (is_array($auth) && isset($auth['status']) && $auth['status'] === 'Success' && !empty($auth['payload']['id'])) {
            $user_id = intval($auth['payload']['id']);

            if (isset($conn) && $conn) {
                // 1) try upi_withdrawal table for mobile
                $q1 = @mysqli_query($conn, "SELECT mobile, upi_id FROM upi_withdrawal WHERE user_id = '$user_id' LIMIT 1");
                if ($q1 && mysqli_num_rows($q1) > 0) {
                    $r1 = mysqli_fetch_assoc($q1);
                    if (!empty($r1['mobile'])) {
                        $mobile_found = normalize_mobile($r1['mobile']);
                    }
                }

                // 2) try upi_bind (older table) if not found
                if (!$mobile_found) {
                    $q2 = @mysqli_query($conn, "SELECT mobile FROM upi_bind WHERE user_id = '$user_id' LIMIT 1");
                    if ($q2 && mysqli_num_rows($q2) > 0) {
                        $r2 = mysqli_fetch_assoc($q2);
                        if (!empty($r2['mobile'])) {
                            $mobile_found = normalize_mobile($r2['mobile']);
                        }
                    }
                }

                // 3) fallback - khate table: use duravani
                if (!$mobile_found) {
                    $q3 = @mysqli_query($conn, "SELECT duravani FROM khate WHERE byabaharkarta = '$user_id' ORDER BY shonu DESC LIMIT 1");
                    if ($q3 && mysqli_num_rows($q3) > 0) {
                        $r3 = mysqli_fetch_assoc($q3);
                        if (!empty($r3['duravani'])) {
                            $mobile_found = normalize_mobile($r3['duravani']);
                        }
                    }
                }
            }
        }
    }

    // ---------------- REQUEST SE DIRECT MOBILE NIKALNE KI KOSHISH ----------------
    if (!$mobile_found && !empty($candidates)) {
        foreach ($candidates as $c) {
            if ($c === null || $c === '') continue;
            $digits = preg_replace('/\D+/', '', $c);
            $len    = strlen($digits);
            if ($len >= 10 && $len <= 13) {
                $mobile_found = $digits;
                break;
            }
        }
    }

    // ---------------- FINAL RESPONSE LOGIC ----------------

    // Agar kuch bhi mobile nahi mila:
    // Is API ka typical behaviour: success + empty data (no binding yet)
    if (!$mobile_found) {
        echo json_encode(array(
            "data"           => "",          // koi mobile bind nahi
            "code"           => 0,
            "msg"            => "Succeed",
            "msgCode"        => 0,
            "serviceNowTime" => $now
        ));
        exit;
    }

    // Agar mobile mila, aur 10 digit ka hai, toh waise hi bhej do
    if (strlen($mobile_found) == 10) {
        // agar force country code chahiye ho toh yahan add kar sakte ho:
        // $mobile_found = '91' . $mobile_found;
    }

    echo json_encode(array(
        "data"           => $mobile_found,
        "code"           => 0,
        "msg"            => "Succeed",
        "msgCode"        => 0,
        "serviceNowTime" => $now
    ));
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(array(
        "code"           => 500,
        "msg"            => "Internal Server Error",
        "msgCode"        => 500,
        "serviceNowTime" => $now,
        // Debug ke liye:
        "error"          => $e->getMessage()
    ));
    exit;
}
