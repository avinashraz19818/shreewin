<?php
/**
 * database.php — single MySQL touchpoint
 * Ops (POST JSON or form-data):
 *   op=ensure_row        username
 *   op=get_amount        username
 *   op=set_amount        username, amount
 *   op=add_amount        username, delta
 *   op=save_betlog       username + betlog fields...
 *   op=save_betlogs_bulk username + rows:[{...}, ...]
 *   op=get_betlog_checkpoint username
 *   op=set_betlog_checkpoint username, ts
 */

date_default_timezone_set('Asia/Kolkata');
header('Content-Type: application/json; charset=utf-8');

// ---- bring your DB connection ($conn = mysqli) ----
include "../../../conn.php";

// ---- table/field names ----
// User balance table/fields as per your request:
$BAL_TABLE   = 'shonu_kaichila';
$BAL_USERCOL = 'balakedara'; // username
$BAL_AMTCOL  = 'motta';      // balance amount

// Betlogs & checkpoint (keep as in your snippet, change if you want):
$TABLE_LOGS = 'nines_betlogs';
$TABLE_CP   = 'nines_betlogs_checkpoint';

// // Optional shared token (disabled by default)
// $SHARED_TOKEN = 'CHANGE_ME_RANDOM';

// ---- read input ----
$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body)) $body = $_POST;

$op       = $body['op'] ?? null;
$username = isset($body['username']) ? trim((string)$body['username']) : null;

// if (isset($SHARED_TOKEN) && (($body['token'] ?? null) !== $SHARED_TOKEN)) {
//   http_response_code(403);
//   echo json_encode(['ok'=>false,'err'=>'forbidden']);
//   exit;
// }

if (!$op || $username === null || $username === '') {
  http_response_code(400);
  echo json_encode(['ok'=>false,'err'=>'missing_params']);
  exit;
}

// ---- helpers ----
function ensure_user_row(mysqli $conn, string $table, string $usercol, string $amtcol, string $username): bool {
  // Try UPDATE first; if no row exists, INSERT one
  $sql = "UPDATE `$table` SET `$amtcol`=`$amtcol` WHERE `$usercol`=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('s', $username);
  $stmt->execute();
  $aff = $stmt->affected_rows;
  $stmt->close();

  if ($aff >= 0) {
    // If row existed, we're done; if no row existed, aff==0 (could be unchanged), so check existence.
    $sql = "SELECT 1 FROM `$table` WHERE `$usercol`=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    if ($exists) return true;
  }

  // Insert a fresh row with 0.00
  $sql = "INSERT INTO `$table` (`$usercol`,`$amtcol`) VALUES (?,0.00)";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('s', $username);
  $ok = $stmt->execute();
  $stmt->close();
  return $ok;
}

// ---- switch ops ----
switch ($op) {

  case 'ensure_row': {
    $ok = ensure_user_row($conn, $BAL_TABLE, $BAL_USERCOL, $BAL_AMTCOL, $username);
    echo json_encode(['ok'=>$ok]);
    exit;
  }

  case 'get_amount': {
    // Use EXACTLY: SELECT motta FROM shonu_kaichila WHERE balakedara = ?
    $sql = "SELECT `$BAL_AMTCOL` FROM `$BAL_TABLE` WHERE `$BAL_USERCOL`=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->bind_result($amount);
    $found = $stmt->fetch();
    $stmt->close();
    echo json_encode(['ok'=>true, 'amount'=> $found ? (float)$amount : 0.00]);
    exit;
  }

  case 'set_amount': {
    if (!isset($body['amount'])) {
      http_response_code(400);
      echo json_encode(['ok'=>false,'err'=>'missing_amount']); exit;
    }
    $amount = (float)$body['amount'];
    ensure_user_row($conn, $BAL_TABLE, $BAL_USERCOL, $BAL_AMTCOL, $username);

    $sql = "UPDATE `$BAL_TABLE` SET `$BAL_AMTCOL`=? WHERE `$BAL_USERCOL`=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ds', $amount, $username);
    $ok = $stmt->execute();
    $stmt->close();

    echo json_encode(['ok'=>$ok, 'amount'=>$amount]);
    exit;
  }

  case 'add_amount': {
    if (!isset($body['delta'])) {
      http_response_code(400);
      echo json_encode(['ok'=>false,'err'=>'missing_delta']); exit;
    }
    $delta = (float)$body['delta'];
    ensure_user_row($conn, $BAL_TABLE, $BAL_USERCOL, $BAL_AMTCOL, $username);

    // UPDATE ... SET motta = motta + ?
    $sql = "UPDATE `$BAL_TABLE` SET `$BAL_AMTCOL`=`$BAL_AMTCOL`+? WHERE `$BAL_USERCOL`=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ds', $delta, $username);
    $ok = $stmt->execute();
    $stmt->close();

    echo json_encode(['ok'=>$ok, 'delta'=>$delta]);
    exit;
  }

  // ---------- Betlogs (unchanged logic, uses your existing $conn) ----------
  case 'save_betlog': {
    $NoPrimary = (string)($body['NoPrimary'] ?? '');
    if ($NoPrimary === '') {
      http_response_code(400);
      echo json_encode(['ok'=>false,'err'=>'missing_NoPrimary']); exit;
    }
    $uidIndex  = (string)($body['uidIndex'] ?? $username);
    $gameDate  = isset($body['gameDateIndex']) ? (string)$body['gameDateIndex'] : null;
    $bet       = isset($body['bet'])       ? (float)$body['bet']       : null;
    $validbet  = isset($body['validbet'])  ? (float)$body['validbet']  : null;
    $win       = isset($body['win'])       ? (float)$body['win']       : null;
    $netWin    = isset($body['netWin'])    ? (float)$body['netWin']    : null;
    $gameName  = isset($body['gameName'])  ? (string)$body['gameName'] : null;
    $gameCode  = isset($body['gameCode'])  ? (string)$body['gameCode'] : null;
    $PreAmount = isset($body['PreAmount']) ? (float)$body['PreAmount'] : null;
    $AftAmount = isset($body['AftAmount']) ? (float)$body['AftAmount'] : null;

    $sql = "INSERT INTO `$TABLE_LOGS`
      (`NoPrimary`,`uidIndex`,`gameDateIndex`,`bet`,`validbet`,`win`,`netWin`,`gameName`,`gameCode`,`PreAmount`,`AftAmount`)
      VALUES (?,?,?,?,?,?,?,?,?,?,?)
      ON DUPLICATE KEY UPDATE
        `gameDateIndex`=VALUES(`gameDateIndex`),
        `bet`=VALUES(`bet`),
        `validbet`=VALUES(`validbet`),
        `win`=VALUES(`win`),
        `netWin`=VALUES(`netWin`),
        `gameName`=VALUES(`gameName`),
        `gameCode`=VALUES(`gameCode`),
        `PreAmount`=VALUES(`PreAmount`),
        `AftAmount`=VALUES(`AftAmount`)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
      'sssddddssdd',
      $NoPrimary,$uidIndex,$gameDate,$bet,$validbet,$win,$netWin,$gameName,$gameCode,$PreAmount,$AftAmount
    );
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['ok'=>$ok]);
    exit;
  }

  case 'save_betlogs_bulk': {
    $rows = $body['rows'] ?? null;
    if (!is_array($rows) || empty($rows)) {
      http_response_code(400);
      echo json_encode(['ok'=>false,'err'=>'missing_rows']); exit;
    }

    $sql = "INSERT INTO `$TABLE_LOGS`
      (`NoPrimary`,`uidIndex`,`gameDateIndex`,`bet`,`validbet`,`win`,`netWin`,`gameName`,`gameCode`,`PreAmount`,`AftAmount`)
      VALUES (?,?,?,?,?,?,?,?,?,?,?)
      ON DUPLICATE KEY UPDATE
        `gameDateIndex`=VALUES(`gameDateIndex`),
        `bet`=VALUES(`bet`),
        `validbet`=VALUES(`validbet`),
        `win`=VALUES(`win`),
        `netWin`=VALUES(`netWin`),
        `gameName`=VALUES(`gameName`),
        `gameCode`=VALUES(`gameCode`),
        `PreAmount`=VALUES(`PreAmount`),
        `AftAmount`=VALUES(`AftAmount`)";
    $stmt = $conn->prepare($sql);

    $saved = 0;
    $conn->begin_transaction();
    foreach ($rows as $r) {
      $NoPrimary = (string)($r['NoPrimary'] ?? '');
      if ($NoPrimary === '') continue;
      $uidIndex  = (string)($r['uidIndex'] ?? $username);
      $gameDate  = isset($r['gameDateIndex']) ? (string)$r['gameDateIndex'] : null;
      $bet       = isset($r['bet'])       ? (float)$r['bet']       : null;
      $validbet  = isset($r['validbet'])  ? (float)$r['validbet']  : null;
      $win       = isset($r['win'])       ? (float)$r['win']       : null;
      $netWin    = isset($r['netWin'])    ? (float)$r['netWin']    : null;
      $gameName  = isset($r['gameName'])  ? (string)$r['gameName'] : null;
      $gameCode  = isset($r['gameCode'])  ? (string)$r['gameCode'] : null;
      $PreAmount = isset($r['PreAmount']) ? (float)$r['PreAmount'] : null;
      $AftAmount = isset($r['AftAmount']) ? (float)$r['AftAmount'] : null;

      $stmt->bind_param(
        'sssddddssdd',
        $NoPrimary,$uidIndex,$gameDate,$bet,$validbet,$win,$netWin,$gameName,$gameCode,$PreAmount,$AftAmount
      );
      if ($stmt->execute()) $saved++;
    }
    $conn->commit();
    $stmt->close();

    echo json_encode(['ok'=>true,'saved'=>$saved]);
    exit;
  }

  case 'get_betlog_checkpoint': {
    $sql = "SELECT last_synced_ts FROM `$TABLE_CP` WHERE uid=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->bind_result($ts);
    $found = $stmt->fetch();
    $stmt->close();
    echo json_encode(['ok'=>true,'ts'=> $found ? (int)$ts : null]);
    exit;
  }

  case 'set_betlog_checkpoint': {
    if (!isset($body['ts'])) {
      http_response_code(400);
      echo json_encode(['ok'=>false,'err'=>'missing_ts']); exit;
    }
    $ts = (int)$body['ts'];
    $sql = "INSERT INTO `$TABLE_CP`(uid,last_synced_ts)
            VALUES (?,?)
            ON DUPLICATE KEY UPDATE last_synced_ts=VALUES(last_synced_ts)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $username, $ts);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['ok'=>$ok,'ts'=>$ts]);
    exit;
  }

  default:
    http_response_code(400);
    echo json_encode(['ok'=>false,'err'=>'unknown_op']);
    exit;
}
