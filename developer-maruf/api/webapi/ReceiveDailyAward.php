<?php
require_once __DIR__ . '/_common.php';
require_once __DIR__ . '/_daily_awards.php';
api_require_post();
$body = api_input();
api_require_signature($body);
$user = api_user();
$userId = (int)$user['id'];
$requested = (int)($body['dailyAwardId'] ?? $body['configId'] ?? $body['id'] ?? 0);
$tier = null;
foreach (daily_award_tiers() as $candidate) if ((int)$candidate['configId'] === $requested || (int)$candidate['mainConfigId'] === $requested) $tier = $candidate;
if (!$tier) api_send(null, 7, 'Param is Invalid', 200, 6);
$schedule = daily_valid_bet_amount($userId);
if ($schedule < (float)$tier['target']) api_send(null, 1, 'Task is not completed', 200, 1);

$conn->begin_transaction();
try {
    $configId = (int)$tier['configId'];
    $target = (float)$tier['target'];
    $award = (float)$tier['award'];
    $claim = $conn->prepare('INSERT INTO app_daily_award_claims(user_id,claim_date,config_id,task_target,award_amount,created_at) VALUES (?,CURDATE(),?,?,?,NOW())');
    if (!$claim) throw new RuntimeException('claim prepare failed');
    $claim->bind_param('iidd', $userId, $configId, $target, $award);
    if (!$claim->execute()) throw new RuntimeException('already claimed');
    $claim->close();

    $wallet = $conn->prepare('SELECT motta FROM shonu_kaichila WHERE balakedara=? FOR UPDATE');
    if (!$wallet) throw new RuntimeException('wallet unavailable');
    $wallet->bind_param('i', $userId);
    $wallet->execute();
    $before = 0.0;
    $wallet->bind_result($before);
    if (!$wallet->fetch()) throw new RuntimeException('wallet not found');
    $wallet->close();
    $after = round((float)$before + $award, 2);
    $update = $conn->prepare('UPDATE shonu_kaichila SET motta=? WHERE balakedara=?');
    $update->bind_param('di', $after, $userId);
    if (!$update->execute()) throw new RuntimeException('wallet update failed');
    $update->close();

    if (api_table_exists('vyavahara')) {
        $kind = 'DAILYAWARD';
        $now = date('Y-m-d H:i:s');
        $ledger = $conn->prepare('INSERT INTO vyavahara(balakedara,ketebida,prakara,purba,bartaman,ayoga,koduvavanu,tiarikala) VALUES (?,?,?,?,?,?,?,?)');
        if ($ledger) {
            $zero = 0;
            $ledger->bind_param('idsdddss', $userId, $target, $kind, $before, $after, $award, $zero, $now);
            $ledger->execute();
            $ledger->close();
        }
    }
    $conn->commit();
    api_send(['configId'=>$configId, 'awardAmount'=>$award, 'amount'=>$after]);
} catch (Throwable $e) {
    $conn->rollback();
    if ((int)$conn->errno === 1062 || str_contains($e->getMessage(), 'already claimed')) api_send(null, 1, 'Award already received', 200, 1);
    error_log('[daily_award_claim] ' . $e->getMessage());
    api_send(null, 8, 'Service temporarily unavailable', 503, 8);
}
