<?php
include "../../conn.php";
include "../../functions2.php";

// ----------------- Headers -----------------
header('Content-Type: application/json; charset=utf-8');
header('Strict-Transport-Security: max-age=31536000');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
header('Access-Control-Allow-Credentials: true');

// Allow any origin (CORS)
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if ($origin) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Vary: Origin');

date_default_timezone_set("Asia/Dhaka");
$shnunc = date("Y-m-d H:i:s");

// ----------------- Default Response -----------------
$res = [
    'code' => 11,
    'msg' => 'Method not allowed',
    'msgCode' => 12,
    'serviceNowTime' => $shnunc,
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // ----------------- Check Authorization Header -----------------
    if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $res['code'] = 4;
        $res['msg'] = 'Authorization header missing';
        $res['msgCode'] = 2;
        http_response_code(401);
        echo json_encode($res);
        exit();
    }

    $bearer = explode(" ", $_SERVER['HTTP_AUTHORIZATION']);
    $author = $bearer[1] ?? '';

    if (empty($author)) {
        $res['code'] = 4;
        $res['msg'] = 'Token missing';
        $res['msgCode'] = 2;
        http_response_code(401);
        echo json_encode($res);
        exit();
    }

    $is_jwt_valid = is_jwt_valid($author);
    $data_auth = json_decode($is_jwt_valid, true);

    // ----------------- JWT Valid -----------------
    if ($data_auth && isset($data_auth['status']) && $data_auth['status'] === 'Success') {

        $userId = $data_auth['payload']['id'];

        // Get POST body
        $shonubody = file_get_contents("php://input");
        $shonupost = json_decode($shonubody, true);

        // Pagination
        $pageNo   = isset($shonupost['pageNo']) ? max(1, (int)$shonupost['pageNo']) : 1;
        $pageSize = isset($shonupost['pageSize']) ? max(1, (int)$shonupost['pageSize']) : 10;
        $offset   = ($pageNo - 1) * $pageSize;

        try {

            // ----------------- TOTAL COUNT (table name starts with hyper) -----------------
            $countQuery = "
                SELECT COUNT(*) AS total 
                FROM hyper_withdraw 
                WHERE user_id = ? 
                  AND withdrawal_type = 'turntable'
            ";
            $countStmt = $conn->prepare($countQuery);
            $countStmt->bind_param("s", $userId);
            $countStmt->execute();
            $countResult = app_stmt_result($countStmt);
            $totalCount = ($countResult && $row = $countResult->fetch_assoc()) ? (int)$row['total'] : 0;
            $totalPage  = $pageSize > 0 ? ceil($totalCount / $pageSize) : 0;

            // ----------------- LIST DATA -----------------
            $list = [];

            if ($totalCount > 0) {

                // NOTE:
                // Assuming DB columns:
                //   order_no        -> orderNo
                //   user_id         -> userId
                //   withdraw_amount -> withdrawAmount
                //   audit_state     -> auditState
                //   create_time     -> createTime
                //   reason          -> reason
                //
                // Table: hyper_withdraw
                $query = "
                    SELECT 
                        order_no,
                        user_id,
                        withdraw_amount,
                        audit_state,
                        create_time,
                        reason
                    FROM hyper_withdraw
                    WHERE user_id = ?
                      AND withdrawal_type = 'turntable'
                    ORDER BY create_time DESC
                    LIMIT ?, ?
                ";

                $stmt = $conn->prepare($query);
                $stmt->bind_param("sii", $userId, $offset, $pageSize);
                $stmt->execute();
                $result = app_stmt_result($stmt);

                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $list[] = [
                            'orderNo'        => $row['order_no'],
                            'userId'         => (int)$row['user_id'],
                            'withdrawAmount' => (float)$row['withdraw_amount'],
                            'auditState'     => (int)$row['audit_state'],
                            'createTime'     => $row['create_time'],
                            'reason'         => $row['reason'] ?? ''
                        ];
                    }
                }
            }

            // ----------------- FINAL RESPONSE -----------------
            $res = [
                'code' => 0,
                'msg'  => 'Success',
                'msgCode' => 0,
                'serviceNowTime' => $shnunc,
                'data' => [
                    'list'       => $list,
                    'pageNo'     => $pageNo,
                    'totalPage'  => $totalPage,
                    'totalCount' => $totalCount
                ]
            ];

            echo json_encode($res);
            exit();

        } catch (Exception $e) {
            $res['code'] = 500;
            $res['msg'] = 'Database error';
            $res['msgCode'] = 500;
            http_response_code(500);
            echo json_encode($res);
            exit();
        }

    } else {
        $res['code'] = 4;
        $res['msg'] = 'Invalid or expired token';
        $res['msgCode'] = 2;
        http_response_code(401);
        echo json_encode($res);
        exit();
    }

} elseif ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

    // Preflight ke liye
    http_response_code(200);
    exit();

} else {

    $res['code'] = 11;
    $res['msg'] = 'Method not allowed';
    $res['msgCode'] = 12;
    http_response_code(405);
    echo json_encode($res);
    exit();
}
?>
