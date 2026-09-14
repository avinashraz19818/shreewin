<?php
define('VA_ADMIN',true);require __DIR__.'/lib/bootstrap.php';va_post();$a=va_auth();va_owner($a);
$action=va_input('action');
if(!in_array($action,['user_update','password_change','schema_export'],true)) va_fail(400,'Unsupported action. No change made.');
if(!va_rate('confirm:'.$a['id'])) va_fail(429,'Too many confirmation attempts. Try again in 15 minutes.');
va_confirm_password($a);
if($action==='schema_export') {
    $report=va_schema_report();va_audit('schema.export','schema');
    header('Content-Type: application/json; charset=utf-8');header('Content-Disposition: attachment; filename="veegame-structure-report.json"');
    echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);exit;
}
if($action==='password_change') {
    $password=va_input('new_password');
    if(strlen($password)<12 || strlen($password)>72 || $password!==va_input('password_confirm')) va_fail(400,'Use matching passwords with 12–72 bytes.');
    if(password_verify($password,$a['password_hash'])) va_fail(400,'Choose a different password.');
    $db=va_db();$db->begin_transaction();
    try {
        $s=va_stmt('UPDATE veegame_admin_accounts SET password_hash=?,session_version=session_version+1 WHERE id=? AND session_version=?',[password_hash($password,PASSWORD_DEFAULT),(int)$a['id'],(int)$a['session_version']]);
        if($s->affected_rows!==1) throw new RuntimeException('Stale admin session');
        va_audit('password.changed','admin:'.$a['id']);$db->commit();
    } catch(Throwable $e) { $db->rollback(); throw $e; }
    va_clear_session();va_redirect('login.php');
}
$id=filter_var(va_input('id'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>2147483647]]);
if(!$id || !va_unique('shonu_subjects','id') || !va_available('shonu_subjects',['id','status','codechorkamukala','akshinak'])) va_fail(409,'User editing needs a unique ID and compatible columns. No change made.');
$before=va_user($id); if(!$before) va_fail(404,'User not found.');
if(!hash_equals(va_user_version($before),va_input('version'))) va_fail(409,'This user changed after you opened the form. Reload before editing.');
$nickname=trim(va_input('nickname'));$status=va_input('status');$reason=trim(va_input('reason'));
if(!preg_match('//u',$nickname) || mb_strlen($nickname)>32 || strlen($reason)<5 || strlen($reason)>300 || !in_array($status,['0','1'],true)) va_fail(400,'Use a nickname up to 32 characters, an active/blocked status and a reason of 5–300 characters.');
$changed=(string)$before['codechorkamukala']!==$nickname || (string)$before['status']!==$status;
if(!$changed) { $_SESSION['flash']='No changes to save.';va_redirect('index.php?page=user&id='.$id); }
$operation=bin2hex(random_bytes(12));
// Legacy user tables may be MyISAM. Persist an intent BEFORE the mutation;
// use compare-and-set, not a false claim of cross-engine transactional auditing.
va_audit('user.update.intent','user:'.$id,['operation'=>$operation,'before'=>['nickname'=>$before['codechorkamukala'],'status'=>$before['status']],'after'=>['nickname'=>$nickname,'status'=>$status],'reason'=>$reason]);
$invalidate=(string)$before['status']!==$status;
$sql='UPDATE shonu_subjects SET codechorkamukala=?,status=?'.($invalidate?",akshinak=''":'').' WHERE id=? AND codechorkamukala <=> ? AND status <=> ? LIMIT 1';
$s=va_stmt($sql,[$nickname,$status,$id,$before['codechorkamukala'],$before['status']]);
if($s->affected_rows!==1) {
    va_audit('user.update.conflict','user:'.$id,['operation'=>$operation]);va_fail(409,'User changed concurrently. Reload the profile.');
}
va_audit('user.update.applied','user:'.$id,['operation'=>$operation,'session_revoked'=>$invalidate]);
$_SESSION['flash']='User updated.'.($invalidate?' Existing account token cleared.':'');va_redirect('index.php?page=user&id='.$id);
