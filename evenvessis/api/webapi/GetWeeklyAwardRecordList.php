<?php
include "../../conn.php";
include "../../functions2.php";

header('Content-Type: application/json; charset=utf-8');
header('Strict-Transport-Security: max-age=31536000');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if ($origin) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Vary: Origin');

date_default_timezone_set("Asia/Dhaka");
$serviceNowTime = date("Y-m-d H:i:s");

$res = [
    'code'           => 11,
    'msg'            => 'Method not allowed',
    'msgCode'        => 12,
    'serviceNowTime' => $serviceNowTime,
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // ---- Auth header check ----
    if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $res = [
            'code'           => 4,
            'msg'            => 'Authorization header missing',
            'msgCode'        => 2,
            'serviceNowTime' => $serviceNowTime
        ];
        http_response_code(401);
        echo json_encode($res);
        exit();
    }

    $bearer = explode(" ", $_SERVER['HTTP_AUTHORIZATION']);
    $author = $bearer[1] ?? '';

    if (empty($author)) {
        $res = [
            'code'           => 4,
            'msg'            => 'Token missing',
            'msgCode'        => 2,
            'serviceNowTime' => $serviceNowTime
        ];
        http_response_code(401);
        echo json_encode($res);
        exit();
    }

    // JWT validate
    $is_jwt_valid = is_jwt_valid($author);
    $data_auth = json_decode($is_jwt_valid, true);

    if ($data_auth && isset($data_auth['status']) && $data_auth['status'] === 'Success') {

        $userId = (int)$data_auth['payload']['id'];

        // ============================================
        // 🆕 PEHLE LATEST BET DATA SE UPDATE KARO
        // ============================================
        include_once "weekly_task_functions.php";
        updateWeeklyTaskRecord($conn, $userId);

        // Body read + pagination
        $rawBody   = file_get_contents("php://input");
        $shonupost = json_decode($rawBody, true);

        $pageNo   = isset($shonupost['pageNo'])   ? max(1, (int)$shonupost['pageNo'])   : 1;
        $pageSize = isset($shonupost['pageSize']) ? max(1, (int)$shonupost['pageSize']) : 10;
        $offset   = ($pageNo - 1) * $pageSize;

        try {

            // ------------------- TOTAL COUNT --------------------
            $countQuery = "SELECT COUNT(*) AS total FROM hyper_weekly_award_record WHERE userId = ?";
            $countStmt = $conn->prepare($countQuery);
            $countStmt->bind_param("i", $userId);
            $countStmt->execute();
            $countResult = app_stmt_result($countStmt);
            $totalCount  = ($countResult && $row = $countResult->fetch_assoc()) ? (int)$row['total'] : 0;
            $totalPage   = $pageSize > 0 ? ceil($totalCount / $pageSize) : 1;

            $list = [];

            if ($totalCount > 0) {

                // ------------------- LIST QUERY --------------------
                $sql = "
                    SELECT
                        id, userId, mainConfigId, configId, schedule, status,
                        taskTitle, taskDescribe, taskId, taskTarget, taskAwardAmount,
                        createDate, targetTwo, targetItem, targetSubItem,
                        rechargeCategories, scheduleTwo, lId
                    FROM hyper_weekly_award_record
                    WHERE userId = ?
                    ORDER BY createDate DESC
                    LIMIT ?, ?
                ";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iii", $userId, $offset, $pageSize);
                $stmt->execute();
                $resList = app_stmt_result($stmt);

                while ($r = $resList->fetch_assoc()) {
                    $list[] = [
                        "id"                 => isset($r["id"]) ? (int)$r["id"] : 0,
                        "userId"             => isset($r["userId"]) ? (int)$r["userId"] : 0,
                        "mainConfigId"       => isset($r["mainConfigId"]) ? (int)$r["mainConfigId"] : 0,
                        "configId"           => isset($r["configId"]) ? (int)$r["configId"] : 0,
                        "schedule"           => isset($r["schedule"]) ? (float)$r["schedule"] : 0.0,
                        "status"             => isset($r["status"]) ? (int)$r["status"] : 0,
                        "taskTitle"          => $r["taskTitle"] ?? "",
                        "taskDescribe"       => $r["taskDescribe"] ?? "",
                        "taskId"             => $r["taskId"] ?? "",
                        "taskTarget"         => isset($r["taskTarget"]) ? (float)$r["taskTarget"] : 0.0,
                        "awardAmount"        => isset($r["taskAwardAmount"]) ? (float)$r["taskAwardAmount"] : 0.0,
                        "taskAwardAmount"    => isset($r["taskAwardAmount"]) ? (float)$r["taskAwardAmount"] : 0.0,
                        "createDate"         => $r["createDate"] ?? "",
                        "targetTwo"          => isset($r["targetTwo"]) ? (float)$r["targetTwo"] : null,
                        "targetItem"         => isset($r["targetItem"]) ? (int)$r["targetItem"] : null,
                        "targetSubItem"      => isset($r["targetSubItem"]) ? (int)$r["targetSubItem"] : null,
                        "rechargeCategories" => $r["rechargeCategories"] ?? null,
                        "scheduleTwo"        => isset($r["scheduleTwo"]) ? (float)$r["scheduleTwo"] : 0.0,
                        "lId"                => isset($r["lId"]) ? (int)$r["lId"] : null,
                    ];
                }
                $stmt->close();
            }

            // ------------------- RESPONSE --------------------
            $res = [
                'data' => [
                    'list'       => $list,
                    'pageNo'     => $pageNo,
                    'totalPage'  => $totalPage,
                    'totalCount' => $totalCount
                ],
                'code'           => 0,
                'msg'            => 'Succeed',
                'msgCode'        => 0,
                'serviceNowTime' => $serviceNowTime
            ];

            echo json_encode($res);
            exit();

        } catch (Exception $e) {
            $res = [
                'code'           => 500,
                'msg'            => 'Database error: ' . $e->getMessage(),
                'msgCode'        => 500,
                'serviceNowTime' => $serviceNowTime
            ];
            http_response_code(500);
            echo json_encode($res);
            exit();
        }

    } else {
        $res = [
            'code'           => 4,
            'msg'            => 'Invalid or expired token',
            'msgCode'        => 2,
            'serviceNowTime' => $serviceNowTime
        ];
        http_response_code(401);
        echo json_encode($res);
        exit();
    }

} else {
    // OPTIONS preflight
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        http_response_code(200);
        exit();
    }
    http_response_code(405);
    echo json_encode($res);
    exit();
}
?>