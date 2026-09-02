<?php
require_once __DIR__ . '/_common.php';
$body = saas_request();
$uid = (int)$body['_user']['id'];
$amount = (float)($body['amount'] ?? $body['betAmount'] ?? 0);
if ($amount <= 0 || $amount > api_wallet_balance($uid)) {
    saas_send(null, 7, 'Invalid or insufficient amount');
}
$gameCode = (string)($body['gameCode'] ?? 'WinGo_30S');
$issue = (string)($body['issueNumber'] ?? saas_issue($gameCode)['current']['issueNumber']);
$selection = json_encode($body['betContent'] ?? $body['selectType'] ?? $body['number'] ?? '', JSON_UNESCAPED_UNICODE);
$conn->begin_transaction();
try {
    $wallet = $conn->prepare('UPDATE shonu_kaichila SET motta=motta-? WHERE balakedara=? AND motta>=?');
    $wallet->bind_param('did', $amount, $uid, $amount);
    $wallet->execute();
    if ($wallet->affected_rows !== 1) { throw new RuntimeException('Insufficient balance'); }
    $bet = $conn->prepare('INSERT INTO saas_lottery_bets (user_id, game_code, issue_number, selection, amount, status) VALUES (?,?,?,?,?,0)');
    $bet->bind_param('isssd', $uid, $gameCode, $issue, $selection, $amount);
    $bet->execute();
    $betId = $bet->insert_id;
    $conn->commit();
    saas_send(['orderId' => $betId, 'amount' => api_wallet_balance($uid)]);
} catch (Throwable $error) {
    $conn->rollback();
    saas_send(null, 8, $error->getMessage());
}
