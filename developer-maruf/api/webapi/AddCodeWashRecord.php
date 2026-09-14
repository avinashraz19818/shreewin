<?php
require_once __DIR__ . '/_common.php';
require_once __DIR__ . '/_betting_rebate.php';
api_require_post();
$body=api_input();
api_require_signature($body);
$user=api_user();
$userId=(int)$user['id'];
$rate=betting_rebate_rate();
$conn->begin_transaction();
try {
    $wallet=$conn->prepare('SELECT motta FROM shonu_kaichila WHERE balakedara=? FOR UPDATE');
    if(!$wallet) throw new RuntimeException('Wallet query failed');
    $wallet->bind_param('i',$userId);$wallet->execute();$wallet->bind_result($currentBalance);$found=$wallet->fetch();$wallet->close();
    if(!$found) throw new RuntimeException('Wallet not found');

    // The wallet row lock serializes claims for this user. Recalculate while locked
    // so repeated taps cannot claim the same wager volume twice.
    $volume=betting_rebate_available_volume($userId);
    $rebate=round($volume*$rate/100.0,2);
    if($volume > 0.0000 && $rebate > 0.0){
        $level='BETTING';
        $stmt=$conn->prepare('INSERT INTO rebetrec(user_id,rebet,lvl,motta,rate,created_at) VALUES (?,?,?,?,?,NOW())');
        if(!$stmt) throw new RuntimeException('Rebate log query failed');
        $stmt->bind_param('idsdd',$userId,$volume,$level,$rebate,$rate);
        if(!$stmt->execute()) throw new RuntimeException('Rebate log failed');
        $stmt->close();
        $stmt=$conn->prepare('UPDATE shonu_kaichila SET motta=ROUND(motta+?,2) WHERE balakedara=?');
        $stmt->bind_param('di',$rebate,$userId);
        if(!$stmt->execute()) throw new RuntimeException('Wallet credit failed');
        $stmt->close();
    }
    $conn->commit();
    api_send(['rebateAmount'=>(float)$rebate,'washVolume'=>(float)$volume,'washRate'=>$rate,'amount'=>api_wallet_balance($userId)]);
} catch(Throwable $e){
    try{$conn->rollback();}catch(Throwable $ignored){}
    error_log('[betting-rebate-claim] '.$e->getMessage());
    api_send(null,8,'Service temporarily unavailable',503,8);
}
