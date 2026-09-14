<?php
define('VA_ADMIN',true);require __DIR__.'/lib/bootstrap.php';va_post();$a=va_auth();va_audit('logout','admin:'.$a['id']);va_clear_session();va_redirect('login.php');
