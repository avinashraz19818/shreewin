<?php
declare(strict_types=1);
date_default_timezone_set('Asia/Kolkata');
header('Content-Type: application/json; charset=utf-8');

// Include the shared connection from conn.php
include "../../../conn.php";

if (!isset($conn) || $conn->connect_error) {
    exit(json_encode(['success' => false, 'error' => 'DB connect']));
}

$action = strtolower($_GET['action'] ?? '');

// 1) getlocal
if ($action === 'getlocal') {
    $uid = $_GET['userid'] ?? '';
    $stmt = $conn->prepare('SELECT motta FROM shonu_kaichila WHERE balakedara = ?');
    $stmt->bind_param('s', $uid);
    $stmt->execute();
    $stmt->bind_result($mt);
    $bal = $stmt->fetch() ? $mt : 0;
    $stmt->close();
    exit(json_encode(['localBalance' => floatval($bal)]));
}

// 2) clearlocal
if ($action === 'clearlocal') {
    $uid = $_GET['userid'] ?? '';
    $upd = $conn->prepare('UPDATE shonu_kaichila SET motta = 0 WHERE balakedara = ?');
    $upd->bind_param('s', $uid);
    $upd->execute();
    exit(json_encode(['success' => true, 'action' => 'cleared']));
}

// 3) creditlocal
if ($action === 'creditlocal') {
    // sanitize inputs
    $uid = intval($_GET['userid'] ?? 0);
    $amt = floatval($_GET['amount'] ?? 0);

    // only update existing record’s motta
    $upd = $conn->prepare("
        UPDATE shonu_kaichila
           SET motta = motta + ?
         WHERE balakedara = ?
    ");
    $upd->bind_param('ds', $amt, $uid);
    $upd->execute();

    if ($upd->affected_rows > 0) {
        exit(json_encode([
            'success' => true,
            'action'  => 'credited',
            'amount'  => $amt
        ]));
    } else {
        // no row matched your userid
        exit(json_encode([
            'success' => false,
            'error'   => 'No record found for userid ' . $uid
        ]));
    }
}

// 4) CQ9 callback (optional—ignored since v4.php does sync updates)
exit(json_encode(['success' => true, 'info' => 'no action']));
