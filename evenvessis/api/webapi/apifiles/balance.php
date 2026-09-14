<?php declare(strict_types=1);
date_default_timezone_set('Asia/Kolkata');
header('Content-Type: application/json; charset=utf-8');

include "../../../conn.php";
include "/apibaseurl.php";
if (!isset($conn) || $conn === false || $conn->connect_errno) {
    http_response_code(500);
    echo json_encode(["error" => "DB connection failed"]);
    exit();
}

/* ---------- Helpers ---------- */
function generateUrl(string $fileName): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    // If behind proxy, prefer X-Forwarded-Proto
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $scheme = rtrim($_SERVER['HTTP_X_FORWARDED_PROTO'], ':') . '://';
    }
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $uri  = $_SERVER['REQUEST_URI'] ?? '/';
    $parts = explode('/', trim($uri, '/'));
    if (count($parts) > 1) array_pop($parts);
    return $scheme . $host . '/' . implode('/', $parts) . '/' . $fileName;
}

function prepareWithdrawHandle(string $service, string $callbackUrl, string $userName) {
    // URL-encode both user and callback
    $encodedUser = rawurlencode($userName);
    $encodedCb   = rawurlencode($callbackUrl);
    $url = "{$apibaseurl}{$service}?action=withdraw&callbackurl={$encodedCb}&userid={$encodedUser}";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 2,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 12,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_HTTPHEADER     => ['Accept: */*','User-Agent: wallet-merge/1.0'],
    ]);
    return $ch;
}

/* ---------- Build local handler URLs ---------- */
$jili      = generateUrl("jili.php");
$jdb       = generateUrl("jdb.php");
$spribe    = generateUrl("jdbpro.php");   // you mapped to spribe in calls below
$aio       = generateUrl("aio.php");
$evoslots  = generateUrl("evoslots.php");
$evo       = generateUrl("evo.php");
$cq9       = generateUrl("cq9.php");
$mt        = generateUrl("mt.php");
$ninesgame = generateUrl("ninesgame.php");       // available if you want to call it later

/* ---------- Input ---------- */
$userName = trim((string)($_GET['user'] ?? ''));
if ($userName === '') {
    http_response_code(400);
    echo json_encode(["error" => "Missing `user` parameter"]);
    exit();
}

/* ---------- Transaction: lock row, compute, update ---------- */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

try {
    $conn->begin_transaction();

    // Lock the user row
    $sel = $conn->prepare("SELECT motta, IFNULL(wll_jdb,0) AS wll_jdb, IFNULL(wll_jili,0) AS wll_jili
                             FROM shonu_kaichila
                            WHERE balakedara = ?
                            FOR UPDATE");
    $sel->bind_param('s', $userName);
    $sel->execute();
    $res = app_stmt_result($sel);
    if ($res->num_rows === 0) {
        $conn->rollback();
        http_response_code(404);
        echo json_encode(["error" => "User not found"]);
        exit();
    }
    $row = $res->fetch_assoc();
    $userBalance = (float)$row['motta'];
    $wllJdb      = max(0.0, (float)$row['wll_jdb']);   // clamp negatives
    $wllJili     = max(0.0, (float)$row['wll_jili']);

    $totalThirdParty = $wllJdb + $wllJili;
    $newBalance = $userBalance + $totalThirdParty;

    // Single atomic update: add sum and zero wallets
    $upd = $conn->prepare("
        UPDATE shonu_kaichila
           SET motta = motta + ?, wll_jdb = 0, wll_jili = 0
         WHERE balakedara = ?
        LIMIT 1
    ");
    $upd->bind_param('ds', $totalThirdParty, $userName);
    $upd->execute();

    $conn->commit();
} catch (Throwable $e) {
    if ($conn->errno) $conn->rollback();
    http_response_code(500);
    echo json_encode(["error" => "DB error", "detail" => $e->getMessage()]);
    exit();
}

/* ---------- Fire parallel withdraws (best-effort, outside TX) ---------- */
/* Map which services to call. Keep exactly those you want. */
$handles = [];

// Only add if you actually need to pull back residual balances from providers
if ($cq9)      $handles[] = prepareWithdrawHandle("cq9",       $cq9,       $userName);
if ($aio)      $handles[] = prepareWithdrawHandle("aio",       $aio,       $userName);
if ($spribe)   $handles[] = prepareWithdrawHandle("spribe",    $jdbpro,    $userName);   // your mapping
if ($evoslots) $handles[] = prepareWithdrawHandle("evoslots",  $evoslots,  $userName);
if ($evo)      $handles[] = prepareWithdrawHandle("evo",       $evo,       $userName);
// Example if you also want to withdraw from ninesgame:
 if ($ninesgame) $handles[] = prepareWithdrawHandle("ninesgame", $ninesgame, $userName);

if ($handles) {
    $mh = curl_multi_init();
    foreach ($handles as $ch) curl_multi_add_handle($mh, $ch);

    $active = null;
    do {
        $mrc = curl_multi_exec($mh, $active);
        if ($mrc === CURLM_OK) {
            // Prevent 100% CPU loop; handle select() returning -1
            if (curl_multi_select($mh) === -1) usleep(100000);
        } else {
            break;
        }
    } while ($active);

    foreach ($handles as $ch) {
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);
}

/* ---------- Response ---------- */
echo json_encode([
    "status"              => "success",
    "oldBalance"          => $userBalance,
    "addedFromThirdParty" => $totalThirdParty,
    "newBalance"          => $newBalance,
    "message"             => "Balances merged and third-party withdrawals triggered in parallel."
], JSON_UNESCAPED_SLASHES);

@$conn->close();
