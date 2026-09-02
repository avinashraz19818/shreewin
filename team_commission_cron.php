<?php
require_once __DIR__ . '/developer-maruf/conn.php';
require_once __DIR__ . '/developer-maruf/app_core_live_v4.php';
date_default_timezone_set('Asia/Kolkata');
app_install_schema($conn);
$cronToken=(string)app_setting('cron_web_token','disabled');
if (PHP_SAPI !== 'cli' && ($cronToken==='' || $cronToken==='disabled' || !hash_equals($cronToken,(string)($_GET['token']??'')))) { http_response_code(403); exit('Forbidden'); }
$processed=0;
while(true){
 $conn->begin_transaction();
 try{
  $row=null;$result=$conn->query("SELECT id,source_user_id,beneficiary_user_id,level_no,turnover,commission_amount FROM app_saas_commissions WHERE credited_at IS NULL AND created_at<CURDATE() ORDER BY id LIMIT 1 FOR UPDATE");if($result)$row=$result->fetch_assoc();
  if(!$row){$conn->commit();break;}
  $beneficiary=(int)$row['beneficiary_user_id'];$source=(int)$row['source_user_id'];$level=(int)$row['level_no'];$turnover=(float)$row['turnover'];$commission=(float)$row['commission_amount'];$id=(int)$row['id'];
  $stmt=$conn->prepare('SELECT motta FROM shonu_kaichila WHERE balakedara=? FOR UPDATE');$stmt->bind_param('i',$beneficiary);$stmt->execute();$before=0.0;$stmt->bind_result($before);if(!$stmt->fetch())throw new RuntimeException('Commission wallet missing');$stmt->close();$after=round((float)$before+$commission,4);
  $stmt=$conn->prepare('UPDATE shonu_kaichila SET motta=? WHERE balakedara=?');$stmt->bind_param('di',$after,$beneficiary);$stmt->execute();$stmt->close();
  if(app_table_exists('vyavahara')){$kind='LVLCOMM'.$level;$now=date('Y-m-d H:i:s');$stmt=$conn->prepare('INSERT INTO vyavahara(balakedara,ketebida,prakara,purba,bartaman,ayoga,koduvavanu,tiarikala) VALUES (?,?,?,?,?,?,?,?)');if($stmt){$stmt->bind_param('idsdddis',$beneficiary,$turnover,$kind,$before,$after,$commission,$source,$now);$stmt->execute();$stmt->close();}}
  $stmt=$conn->prepare('UPDATE app_saas_commissions SET credited_at=NOW() WHERE id=? AND credited_at IS NULL');$stmt->bind_param('i',$id);$stmt->execute();$stmt->close();$conn->commit();$processed++;
 }catch(Throwable $e){$conn->rollback();error_log('[team_commission_cron] '.$e->getMessage());break;}
}
echo json_encode(['ok'=>true,'processed'=>$processed,'time'=>date('Y-m-d H:i:s')]);
