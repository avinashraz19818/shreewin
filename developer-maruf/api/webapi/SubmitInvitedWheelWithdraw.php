<?php
require_once __DIR__ . '/_common.php';
api_require_post();
$body=api_input(); api_require_signature($body); $user=api_user(); $userId=(int)$user['id'];
$target=(float)app_setting('invite_wheel_target',500);
$conn->begin_transaction();
try{
 $stmt=$conn->prepare('SELECT invited_wheel_amount FROM shonu_turntable WHERE user_id=? FOR UPDATE');$stmt->bind_param('i',$userId);$stmt->execute();$wheel=0.0;$stmt->bind_result($wheel);$found=$stmt->fetch();$stmt->close();
 if(!$found||(float)$wheel<$target)throw new DomainException('Reach ₹500.00 to cash out');
 $stmt=$conn->prepare('SELECT motta FROM shonu_kaichila WHERE balakedara=? FOR UPDATE');$stmt->bind_param('i',$userId);$stmt->execute();$before=0.0;$stmt->bind_result($before);if(!$stmt->fetch())throw new RuntimeException('wallet not found');$stmt->close();
 $after=round((float)$before+$target,2);
 $stmt=$conn->prepare('UPDATE shonu_turntable SET invited_wheel_amount=invited_wheel_amount-?,updated_at=NOW() WHERE user_id=?');$stmt->bind_param('di',$target,$userId);$stmt->execute();$stmt->close();
 $stmt=$conn->prepare('UPDATE shonu_kaichila SET motta=? WHERE balakedara=?');$stmt->bind_param('di',$after,$userId);$stmt->execute();$stmt->close();
 $stmt=$conn->prepare('INSERT INTO app_spin_transfers(user_id,amount,balance_before,balance_after,created_at) VALUES (?,?,?,?,NOW())');$stmt->bind_param('iddd',$userId,$target,$before,$after);$stmt->execute();$transferId=$conn->insert_id;$stmt->close();
 $conn->commit();api_send(['withdrawalId'=>(int)$transferId,'amount'=>$target,'newBalance'=>round((float)$wheel-$target,2),'walletBalance'=>$after,'withdrawTime'=>date('Y-m-d H:i:s')]);
}catch(DomainException $e){$conn->rollback();api_send(null,1,$e->getMessage(),200,1);}catch(Throwable $e){$conn->rollback();error_log('[invite_wheel_cashout] '.$e->getMessage());api_send(null,8,'Service temporarily unavailable',503,8);}
