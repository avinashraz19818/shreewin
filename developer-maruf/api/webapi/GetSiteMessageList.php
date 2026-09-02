<?php // Updated by System - 2026-05-03 21:58:31 ?>
<?php // Updated by System - 2026-05-03 21:58:14 ?>
<?php
	include "../../conn.php";
	require_once __DIR__ . '/_shreewin_brand.php';
			
	header('Content-Type: application/json; charset=utf-8');
	header('Strict-Transport-Security: max-age=31536000');
	header('Cache-Control: no-cache, no-store, must-revalidate');
	header('Pragma: no-cache');
	header('Expires: 0');
	header('Access-Control-Allow-Headers: *');
	header('Access-Control-Allow-Credentials: true');
	$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
	header('Access-Control-Allow-Origin: ' . $origin);
	header('vary: Origin');
	
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
		if (isset($shonupost['language']) && isset($shonupost['pageNo']) && isset($shonupost['pageSize']) && isset($shonupost['random']) && isset($shonupost['signature']) && isset($shonupost['timestamp'])) {
			$language = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['language']));
			$pageNo = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['pageNo']));
			$pageSize = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['pageSize']));
			$random = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['random']));
			$signature = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['signature']));
			$shonustr = '{"language":'.$language.',"pageNo":'.$pageNo.',"pageSize":'.$pageSize.',"random":"'.$random.'"}';
			$shonusign = strtoupper(md5($shonustr));
			if ($shonusign == $signature) {

    $list = [];
    $query = mysqli_query($conn, "SELECT * FROM website_messages WHERE status = 1 ORDER BY created_at DESC");

    if ($query instanceof mysqli_result) {
        while ($row = mysqli_fetch_assoc($query)) {
            $list[] = [
                'title' => shreewin_brand_content((string)($row['title'] ?? '')),
                'siteMessage' => shreewin_brand_content((string)($row['message'] ?? '')),
                'type' => $row['message_type'] ?? 'system',
                'addtime' => $row['created_at'] ?? $shnunc
            ];
        }
    }

    if (!$list) {
        $list[] = [
            'title' => 'Welcome to Shree Win',
            'siteMessage' => 'Welcome to Shree Win. Please use only the official website and never share your password or OTP.',
            'type' => 'system',
            'addtime' => $shnunc
        ];
    }

    $data['list'] = $list;
    $data['pageNo'] = (int)$pageNo;
    $data['pageSize'] = (int)$pageSize;
    $data['totalCount'] = count($list);

    $res = [
        'data' => $data,
        'code' => 0,
        'msg' => 'Succeed',
        'msgCode' => 0
    ];

    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

			else{
				$res['code'] = 5;
				$res['msg'] = 'Wrong signature';
				$res['msgCode'] = 3;
				http_response_code(200);
				echo json_encode($res);
			}
		}
		else{
			$res['code'] = 7;
			$res['msg'] = 'Param is Invalid';
			$res['msgCode'] = 6;
			http_response_code(200);
			echo json_encode($res);
		}		
	} else {		
		http_response_code(405);
		echo json_encode($res);
	}
?>
