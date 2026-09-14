<?php
// editnickname.php — Update nickname in `codechorkamukala` using JWT + signature
include "../../conn.php";
include "../../functions2.php";

header('Content-Type: application/json; charset=utf-8');
header('Strict-Transport-Security: max-age=31536000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header('Access-Control-Allow-Origin: ' . $origin);
header('vary: Origin');

date_default_timezone_set("Asia/Kolkata");
$now = date("Y-m-d H:i:s");

// Handle CORS preflight
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'OPTIONS') { http_response_code(204); exit; }
if ($method !== 'POST') {
  http_response_code(405);
  echo json_encode(['code'=>11,'msg'=>'Method not allowed','msgCode'=>12,'serviceNowTime'=>$now]);
  exit;
}

// Read body (JSON or form)
$rawBody = file_get_contents("php://input");
$in = json_decode($rawBody, true);
if (!is_array($in) || empty($in)) { $in = $_POST; }

// Envelope check
foreach (['language','random','signature','timestamp'] as $k) {
  if (!array_key_exists($k, $in)) {
    http_response_code(200);
    echo json_encode(['code'=>7,'msg'=>'Param is Invalid','msgCode'=>6,'serviceNowTime'=>$now]);
    exit;
  }
}

// ---------- Signature verification ----------
// NOTE: Your client signs: {"language":<num>,"nikeName":"<nick>","random":"<rand>"}
$language_raw  = (string)$in['language'];                 // client sends 0 (number)
$random_raw    = (string)$in['random'];
$signature_raw = strtoupper((string)$in['signature']);
$ts_raw        = (string)$in['timestamp'];                // not used by client signing
$nick_raw      = (string)($in['nikeName'] ?? $in['nickname'] ?? '');

// Build primary preimage EXACTLY like client (NO spaces, language as-is)
$preimages = [];
if ($nick_raw !== '') {
  // EXACT: {"language":0,"nikeName":"<nick>","random":"<rand>"}
  $preimages[] = '{"language":' . $language_raw . ',"nikeName":"' . $nick_raw . '","random":"' . $random_raw . '"}';

  // Fallbacks (in case some clients quote language or include timestamp)
  $preimages[] = '{"language":"' . $language_raw . '","nikeName":"' . $nick_raw . '","random":"' . $random_raw . '"}';
  $preimages[] = '{"language":' . $language_raw . ',"nikeName":"' . $nick_raw . '","random":"' . $random_raw . '","timestamp":' . $ts_raw . '}';
  $preimages[] = '{"language":"' . $language_raw . '","nikeName":"' . $nick_raw . '","random":"' . $random_raw . '","timestamp":' . $ts_raw . '}';
  $preimages[] = '{"language":' . $language_raw . ',"nikeName":"' . $nick_raw . '","random":"' . $random_raw . '","timestamp":"' . $ts_raw . '"}';
  $preimages[] = '{"language":"' . $language_raw . '","nikeName":"' . $nick_raw . '","random":"' . $random_raw . '","timestamp":"' . $ts_raw . '"}';
} else {
  // Legacy: without nikeName in preimage
  $preimages[] = '{"language":' . $language_raw . ',"random":"' . $random_raw . '"}';
  $preimages[] = '{"language":"' . $language_raw . '","random":"' . $random_raw . '"}';
  $preimages[] = '{"language":' . $language_raw . ',"random":"' . $random_raw . '","timestamp":' . $ts_raw . '}';
  $preimages[] = '{"language":"' . $language_raw . '","random":"' . $random_raw . '","timestamp":' . $ts_raw . '}';
  $preimages[] = '{"language":' . $language_raw . ',"random":"' . $random_raw . '","timestamp":"' . $ts_raw . '"}';
  $preimages[] = '{"language":"' . $language_raw . '","random":"' . $random_raw . '","timestamp":"' . $ts_raw . '"}';
}

// Compare
$ok = false;
foreach ($preimages as $s) {
  if (strtoupper(md5($s)) === $signature_raw) { $ok = true; break; }
}
if (!$ok) {
  http_response_code(200);
  echo json_encode(['code'=>5,'msg'=>'Wrong signature','msgCode'=>3,'serviceNowTime'=>$now]);
  exit;
}

// ---------- Authorization: Bearer <JWT> ----------
$authHeader =
  ($_SERVER['HTTP_AUTHORIZATION'] ?? '') ?:
  ($_SERVER['Authorization'] ?? '') ?:
  ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
$parts = explode(' ', trim($authHeader));
$token = $parts[1] ?? '';
if ($token === '') {
  http_response_code(401);
  echo json_encode(['code'=>4,'msg'=>'No operation permission','msgCode'=>2,'serviceNowTime'=>$now]);
  exit;
}

// Validate JWT
$jwtCheck = is_jwt_valid($token);
$auth = json_decode($jwtCheck, true);
if (!is_array($auth) || ($auth['status'] ?? '') !== 'Success') {
  http_response_code(401);
  echo json_encode(['code'=>4,'msg'=>'No operation permission','msgCode'=>2,'serviceNowTime'=>$now]);
  exit;
}
$userId = (int)$auth['payload']['id'];

// Verify token exists for this user
$chk = $conn->prepare("SELECT id FROM shonu_subjects WHERE akshinak = ? AND id = ? LIMIT 1");
$chk->bind_param('si', $token, $userId);
$chk->execute();
$chkRes = app_stmt_result($chk);
$chk->close();
if (!$chkRes || $chkRes->num_rows !== 1) {
  http_response_code(401);
  echo json_encode(['code'=>4,'msg'=>'No operation permission','msgCode'=>2,'serviceNowTime'=>$now]);
  exit;
}

// ---------- New nickname to SAVE (from body) ----------
$newNick = trim((string)($in['nikeName'] ?? $in['nickname'] ?? ''));
if ($newNick === '') {
  http_response_code(200);
  echo json_encode(['code'=>7,'msg'=>'nickname is required','msgCode'=>6,'serviceNowTime'=>$now]);
  exit;
}
if (mb_strlen($newNick) > 50) {
  http_response_code(200);
  echo json_encode(['code'=>7,'msg'=>'Nickname too long (max 50)','msgCode'=>6,'serviceNowTime'=>$now]);
  exit;
}

// ---------- Read current nickname ----------
$sel = $conn->prepare("SELECT codechorkamukala FROM shonu_subjects WHERE id = ? LIMIT 1");
$sel->bind_param('i', $userId);
$sel->execute();
$cur = app_stmt_result($sel)->fetch_assoc();
$sel->close();
$currentNick = (string)($cur['codechorkamukala'] ?? '');

// Same? Return success (unchanged)
if ($currentNick === $newNick) {
  http_response_code(200);
  echo json_encode([
    'code'=>0,'msg'=>'Succeed','msgCode'=>0,'serviceNowTime'=>$now,
    'data'=>['userId'=>$userId,'nickName'=>$newNick,'changed'=>false]
  ]);
  exit;
}

// ---------- Update nickname ----------
$upd = $conn->prepare("UPDATE shonu_subjects SET codechorkamukala = ? WHERE id = ?");
$upd->bind_param('si', $newNick, $userId);
$upd->execute();
$rows = $upd->affected_rows;
$upd->close();

// ---------- Response ----------
http_response_code(200);
echo json_encode([
  'code'=>0, 'msg'=>'Succeed', 'msgCode'=>0, 'serviceNowTime'=>$now,
  'data'=>['userId'=>$userId, 'nickName'=>$newNick, 'changed'=>($rows>0)]
]);
