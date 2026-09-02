<?php
require_once __DIR__ . '/_common.php';
api_require_post();
$body=api_input(); api_require_signature($body); $user=api_user(); $userId=(int)$user['id'];$freeSpins=max(0,(int)app_setting('invite_wheel_free_spins',2));$target=(float)app_setting('invite_wheel_target',500);
$name='Member'.$userId;
$stmt=$conn->prepare("SELECT COALESCE(codechorkamukala,'') FROM shonu_subjects WHERE id=?");
if($stmt){$stmt->bind_param('i',$userId);$stmt->execute();$v='';$stmt->bind_result($v);if($stmt->fetch()&&trim($v)!=='')$name=$v;$stmt->close();}
$stmt=$conn->prepare('INSERT IGNORE INTO shonu_turntable(user_id,total_spins,invited_wheel_amount,created_at,updated_at) VALUES (?,?,0,NOW(),NOW())');
$stmt->bind_param('ii',$userId,$freeSpins);$stmt->execute();$stmt->close();
$stmt=$conn->prepare('UPDATE shonu_turntable SET total_spins=GREATEST(total_spins,?),updated_at=NOW() WHERE user_id=? AND NOT EXISTS (SELECT 1 FROM shonu_turntable_spins WHERE user_id=? LIMIT 1)');
$stmt->bind_param('iii',$freeSpins,$userId,$userId);$stmt->execute();$stmt->close();
$stmt=$conn->prepare('SELECT owncode FROM shonu_subjects WHERE id=? LIMIT 1');$stmt->bind_param('i',$userId);$stmt->execute();$ownCode='';$stmt->bind_result($ownCode);$stmt->fetch();$stmt->close();
if($ownCode!==''){$stmt=$conn->prepare('INSERT IGNORE INTO app_invite_spin_grants(owner_user_id,invited_user_id,created_at) SELECT ?,id,NOW() FROM shonu_subjects WHERE code=?');$stmt->bind_param('is',$userId,$ownCode);$stmt->execute();$newInviteSpins=max(0,$stmt->affected_rows);$stmt->close();if($newInviteSpins>0){$stmt=$conn->prepare('UPDATE shonu_turntable SET total_spins=total_spins+?,updated_at=NOW() WHERE user_id=?');$stmt->bind_param('ii',$newInviteSpins,$userId);$stmt->execute();$stmt->close();}}
$stmt=$conn->prepare('SELECT total_spins,invited_wheel_amount FROM shonu_turntable WHERE user_id=? LIMIT 1');
$stmt->bind_param('i',$userId);$stmt->execute();$spins=0;$amount=0.0;$stmt->bind_result($spins,$amount);$stmt->fetch();$stmt->close();
$count=0;$history=[];
$stmt=$conn->prepare('SELECT COUNT(*) FROM shonu_turntable_spins WHERE user_id=?');$stmt->bind_param('i',$userId);$stmt->execute();$stmt->bind_result($count);$stmt->fetch();$stmt->close();
$stmt=$conn->prepare('SELECT prize_amount,spin_time FROM shonu_turntable_spins WHERE user_id=? ORDER BY id DESC LIMIT 10');
if($stmt){$stmt->bind_param('i',$userId);$stmt->execute();$result=$stmt->get_result();$running=(float)$amount;while($row=$result->fetch_assoc()){$history[]=['userId'=>$userId,'userName'=>$name,'invitedWheelAmount'=>round($running,4),'prizeAmount'=>(float)$row['prize_amount'],'createTime'=>$row['spin_time']];$running-=((float)$row['prize_amount']);}$stmt->close();}
api_send([
 'isOpenInvitedWheel'=>true,'isFirstInvitedWheel'=>$count===0,'userInvitedWheelCount'=>(int)$spins,
 'userInvitedWheelAmount'=>(float)$amount,'invitedWheelTotalPrizeAmount'=>$target,
 'invitedWheelAmountofcodeAmount'=>1.0,'expiredTime'=>date('Y-m-d H:i:s',strtotime('+3 days')),
 'diskDisplayAmount'=>[$target,5.0,10.0,20.0,30.0,50.0,80.0],
 'noWinningRandomAmount'=>[0.0,10.0],'lastWheelRecordList'=>$history
]);
