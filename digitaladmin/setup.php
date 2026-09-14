<?php
define('VA_ADMIN',true); require __DIR__.'/lib/bootstrap.php';
if (($_SERVER['REQUEST_METHOD']??'')==='POST') {
    va_post();
    $key=va_input('setup_key');
    $expected=require __DIR__.'/lib/setup-config.php';
    if(strlen($key)<32 || strlen($key)>128 || !hash_equals($expected,hash('sha256',$key))) va_fail(403,'Setup key is invalid.');
    $username=trim(va_input('username'));$password=va_input('password');
    if(!preg_match('/^[A-Za-z0-9_.-]{3,64}$/D',$username) || strlen($password)<12 || strlen($password)>72 || $password!==va_input('password_confirm')) va_fail(400,'Choose a 3–64 character username and matching 12–72 byte passwords.');
    $db=va_db(); $lock='va_setup_'.substr(hash('sha256',(string)va_one('SELECT DATABASE() AS d')['d']),0,32);
    $locked=va_one('SELECT GET_LOCK(?,8) AS ok',[$lock]);
    if((int)$locked['ok']!==1) va_fail(409,'Setup is already running. Try again later.');
    try {
        require __DIR__.'/lib/schema.php';va_install();
        $db->begin_transaction();
        if(va_one('SELECT id FROM veegame_admin_accounts LIMIT 1 FOR UPDATE')) { $db->rollback(); va_fail(409,'Admin is already set up. Setup cannot replace an existing account.'); }
        va_stmt("INSERT INTO veegame_admin_accounts (id,username,password_hash,role,active,session_version,created_at) VALUES (1,?,?,'owner',1,1,NOW())",[$username,password_hash($password,PASSWORD_DEFAULT)]);
        va_audit('setup.complete','admin:1',[],1);$db->commit();
    } catch(Throwable $e) { $db->rollback(); throw $e; }
    finally { va_stmt('SELECT RELEASE_LOCK(?)',[$lock]); }
    $_SESSION['csrf']=bin2hex(random_bytes(32));va_redirect('login.php?setup=done');
}
if(va_ready()) va_fail(409,'Admin is already set up. Sign in using your admin account.');
va_login_head('First-time setup'); ?>
<p class="setup-note">Use the private setup key supplied separately. Choose your own admin credentials. Setup creates only three separate admin security tables; it does not change users, wallets or WinGo.</p>
<form method="post" action="setup.php"><?=va_csrf()?>
<?php va_login_field('setup_key','One-time setup key','password','off'); va_login_field('username','New admin username','text','username'); va_login_field('password','New password (12+ characters)','password','new-password'); va_login_field('password_confirm','Confirm password','password','new-password'); ?>
<button class="btn-premium-auth" type="submit">Create admin account</button></form>
<?php va_login_end(); ?>
