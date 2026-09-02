<?php 
include "../../conn.php";
include "../../functions2.php";

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kolkata");

// Base URL
$K3_BASE = "https://draw.ar-lottery01.com/K3";

$K3_MAP = [
    9  => ['history' => "K3_1M/GetHistoryIssuePage.json"],
    10 => ['history' => "K3_3M/GetHistoryIssuePage.json"],
    11 => ['history' => "K3_5M/GetHistoryIssuePage.json"],
    12 => ['history' => "K3_10M/GetHistoryIssuePage.json"]
];

// Fetch JSON with cURL
function get_remote_json($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $raw = curl_exec($ch);
    curl_close($ch);
    return json_decode($raw, true);
}

// Detect GameType
function classify_k3($val) {
    $val = preg_replace('/\D/', '', $val);
    if (strlen($val) !== 3) return 0;

    $d = str_split($val);
    $d1 = (int)$d[0]; $d2 = (int)$d[1]; $d3 = (int)$d[2];

    if ($d1==$d2 && $d2==$d3) return 3; // TRIPLE
    if ($d1==$d2 || $d1==$d3 || $d2==$d3) return 2; // DOUBLE

    $mx = max($d1,$d2,$d3);
    $mn = min($d1,$d2,$d3);
    if (($mx-$mn)==2 && (abs($d1-$d2)==1 || abs($d1-$d3)==1 || abs($d2-$d3)==1))
        return 1; // SEQUENCE

    return 0; // MIXED
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['code'=>11,'msg'=>"Method not allowed"]);
    exit;
}

$body = json_decode(file_get_contents("php://input"), true);

if (!isset($body['language'],$body['random'],$body['signature'],$body['timestamp'],$body['pageNo'],$body['pageSize'],$body['typeId'])) {
    echo json_encode(['code'=>7,'msg'=>"Param Invalid"]);
    exit;
}

// SIGNATURE CHECK
$lang = $body['language'];
$pageNo = intval($body['pageNo']);
$pageSize = intval($body['pageSize']);
$random = $body['random'];
$typeId = intval($body['typeId']);
$sig = strtoupper($body['signature']);

$rawStr = '{"language":'.$lang.',"pageNo":'.$pageNo.',"pageSize":'.$pageSize.',"random":"'.$random.'","typeId":'.$typeId.'}';
if (strtoupper(md5($rawStr)) !== $sig) {
    echo json_encode(['code'=>5,'msg'=>"Wrong signature"]);
    exit;
}

// AUTH CHECK
$auth = explode(" ", $_SERVER['HTTP_AUTHORIZATION'] ?? '');
$token = $auth[1] ?? '';
$jwt = json_decode(is_jwt_valid($token), true);

if (!isset($jwt['status']) || $jwt['status'] !== "Success") {
    echo json_encode(['code'=>4,'msg'=>"No operation permission"]);
    exit;
}

// Validate typeId
if (!isset($K3_MAP[$typeId])) {
    echo json_encode(['code'=>7,'msg'=>"Invalid typeId"]);
    exit;
}

// REMOTE URL
$remoteUrl = $K3_BASE . "/" . $K3_MAP[$typeId]['history'] . "?ts=" . time();
$remote = get_remote_json($remoteUrl);

if (!$remote) {
    echo json_encode(['code'=>500,'msg'=>"Remote API failed"]);
    exit;
}

// Extract list
$list = [];

if (isset($remote['data']['list'])) {
    $remoteList = $remote['data']['list'];
} elseif (isset($remote['list'])) {
    $remoteList = $remote['list'];
} else {
    $remoteList = [];
}

$i = 0;
foreach ($remoteList as $item) {
    if ($i >= $pageSize) break;

    $issue = $item['issueNumber'] ?? $item['issue'] ?? null;
    $premium = $item['premium'] ?? $item['bele'] ?? null;
    $number = $item['number'] ?? $item['phalitansa'] ?? null;

    $src = $premium ?? $number;
    $gameType = classify_k3($src);
    $sum = preg_replace('/\D/', '', ($item['sum'] ?? $item['phalitansa'] ?? $number));

    $list[] = [
        'issueNumber' => $issue,
        'gameType' => $gameType,
        'sumCount' => intval($sum),
        'premium' => $premium
    ];

    $i++;
}

// Final Output
echo json_encode([
    'code' => 0,
    'msg' => 'Succeed',
    'msgCode' => 0,
    'serviceNowTime' => date("Y-m-d H:i:s"),
    'data' => [
        'list' => $list,
        'pageNo' => $pageNo,
        'totalPage' => 50,
        'totalCount' => 500
    ]
], JSON_PRETTY_PRINT);

exit;
?>
