<?php
require_once __DIR__ . '/_common.php';
api_require_post();
$body=api_input(); api_require_signature($body); $user=api_user(); $userId=(int)$user['id'];$freeSpins=max(0,(int)app_setting('invite_wheel_free_spins',2));
$name='Member'.$userId;
$stmt=$conn->prepare("SELECT COALESCE(codechorkamukala,'') FROM shonu_subjects WHERE id=?");
if($stmt){$stmt->bind_param('i',$userId);$stmt->execute();$v='';$stmt->bind_result($v);if($stmt->fetch()&&trim($v)!=='')$name=$v;$stmt->close();}
$conn->begin_transaction();
try{
 $stmt=$conn->prepare('SELECT total_spins,invited_wheel_amount FROM shonu_turntable WHERE user_id=? FOR UPDATE');$stmt->bind_param('i',$userId);$stmt->execute();$spins=0;$balance=0.0;$stmt->bind_result($spins,$balance);$found=$stmt->fetch();$stmt->close();
 if(!$found){$stmt=$conn->prepare('INSERT INTO shonu_turntable(user_id,total_spins,invited_wheel_amount,created_at,updated_at) VALUES (?,?,0,NOW(),NOW())');$stmt->bind_param('ii',$userId,$freeSpins);$stmt->execute();$stmt->close();$spins=$freeSpins;$balance=0.0;}
 if((int)$spins<1)throw new DomainException('Invite friends to get spin');
 $stmt=$conn->prepare('SELECT COUNT(*) FROM shonu_turntable_spins WHERE user_id=?');$stmt->bind_param('i',$userId);$stmt->execute();$used=0;$stmt->bind_result($used);$stmt->fetch();$stmt->close();
 $isFirst=((int)$used===0);
 if($isFirst && (int)$spins<$freeSpins){$spins=$freeSpins;$stmt=$conn->prepare('UPDATE shonu_turntable SET total_spins=?,updated_at=NOW() WHERE user_id=?');$stmt->bind_param('ii',$freeSpins,$userId);$stmt->execute();$stmt->close();}
 if($isFirst){$boxes=[478.10,462.45,489.60,495.20];$selected=random_int(0,3);$prize=$boxes[$selected];$boxData=[];foreach($boxes as $i=>$value)$boxData[]=['amount'=>$value,'isSelected'=>$i===$selected];}
 else{$small=[0.12,0.24,0.36,0.48,0.62,0.78,0.90];$prize=$small[array_rand($small)];$boxData=null;}
 $stmt=$conn->prepare('INSERT INTO shonu_turntable_spins(user_id,prize_amount,spin_time,user_name,is_credited) VALUES (?,?,NOW(),?,0)');$stmt->bind_param('ids',$userId,$prize,$name);if(!$stmt->execute())throw new RuntimeException('spin record failed');$stmt->close();
 $stmt=$conn->prepare('UPDATE shonu_turntable SET total_spins=total_spins-1,invited_wheel_amount=invited_wheel_amount+?,updated_at=NOW() WHERE user_id=? AND total_spins>0');$stmt->bind_param('di',$prize,$userId);$stmt->execute();if($stmt->affected_rows!==1)throw new RuntimeException('spin update failed');$stmt->close();
 $conn->commit(); api_send(['isFirstInvitedWheel'=>$isFirst,'prizeAmount'=>(float)$prize,'isWin'=>true,'firstInvitedWheelDatas'=>$boxData]);
}catch(DomainException $e){$conn->rollback();api_send(null,1,$e->getMessage(),200,1);}catch(Throwable $e){$conn->rollback();error_log('[invite_wheel_spin] '.$e->getMessage());api_send(null,8,'Service temporarily unavailable',503,8);}
