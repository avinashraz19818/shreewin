<?php
session_start();
if (empty($_SESSION['unohs'])) { header('Location: api/login.php?msg=unauthorized'); exit; }
include 'api/conn.php';
$message=''; $messageType='success';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $id=max(0,(int)($_POST['id']??0)); $name=trim((string)($_POST['display_name']??''));
    $url=trim((string)($_POST['api_url']??'')); $merchant=trim((string)($_POST['merchant_id']??''));
    $secret=trim((string)($_POST['secret_key']??'')); $notify=trim((string)($_POST['notify_url']??''));
    $currency=strtoupper(trim((string)($_POST['currency_code']??'INR'))); $active=isset($_POST['is_active'])?1:0;
    if ($name===''||!filter_var($url,FILTER_VALIDATE_URL)||!filter_var($notify,FILTER_VALIDATE_URL)||$merchant===''||($id===0&&$secret==='')) {
        $message='Please complete all gateway fields with valid URLs.'; $messageType='danger';
    } else {
        $conn->begin_transaction();
        try {
            if ($active) $conn->query('UPDATE auto_payout_gateways SET is_active=0');
            if ($id>0) {
                if ($secret==='') {
                    $stmt=$conn->prepare('UPDATE auto_payout_gateways SET display_name=?,api_url=?,merchant_id=?,notify_url=?,currency_code=?,is_active=?,updated_at=NOW() WHERE id=?');
                    $stmt->bind_param('sssssii',$name,$url,$merchant,$notify,$currency,$active,$id);
                } else {
                    $stmt=$conn->prepare('UPDATE auto_payout_gateways SET display_name=?,api_url=?,merchant_id=?,secret_key=?,notify_url=?,currency_code=?,is_active=?,updated_at=NOW() WHERE id=?');
                    $stmt->bind_param('ssssssii',$name,$url,$merchant,$secret,$notify,$currency,$active,$id);
                }
            } else {
                $code='gateway_'.substr(hash('sha256',$name.microtime(true)),0,12);
                $stmt=$conn->prepare('INSERT INTO auto_payout_gateways(gateway_name,display_name,api_url,merchant_id,secret_key,notify_url,currency_code,is_active,updated_at) VALUES(?,?,?,?,?,?,?,?,NOW())');
                $stmt->bind_param('sssssssi',$code,$name,$url,$merchant,$secret,$notify,$currency,$active);
            }
            if(!$stmt||!$stmt->execute())throw new RuntimeException('Save failed');$stmt->close();$conn->commit();$message='Payout gateway saved and synchronized.';
        } catch(Throwable $e){$conn->rollback();error_log('[payout gateway] '.$e->getMessage());$message='Gateway could not be saved.';$messageType='danger';}
    }
}
$gateways=array();$res=$conn->query('SELECT id,display_name,api_url,merchant_id,secret_key,notify_url,currency_code,is_active,updated_at FROM auto_payout_gateways ORDER BY is_active DESC,id DESC');while($res&&($row=$res->fetch_assoc()))$gateways[]=$row;
?>
<!doctype html><html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Auto Payout Gateways</title>
<link rel="stylesheet" href="assets/vendor/fonts/remixicon/remixicon.css"><link rel="stylesheet" href="assets/vendor/css/rtl/core.css"><link rel="stylesheet" href="assets/vendor/css/rtl/theme-default.css"><link rel="stylesheet" href="assets/css/demo.css"><script src="assets/vendor/js/helpers.js"></script><script src="assets/js/config.js"></script></head>
<body><div class="layout-wrapper layout-content-navbar"><div class="layout-container"><?php require 'layout-menu.php'; ?><div class="layout-page"><?php require 'nav.php'; ?><div class="content-wrapper"><div class="container-xxl flex-grow-1 container-p-y">
<div class="d-flex justify-content-between align-items-center mb-4"><h4 class="mb-0">Auto Payout Gateways</h4><span class="badge bg-label-success">SQL synchronized</span></div>
<?php if($message!==''):?><div class="alert alert-<?=$messageType?>"><?=htmlspecialchars($message)?></div><?php endif;?>
<div class="card mb-5"><div class="card-body"><h5>Add / Update Gateway</h5><form method="post" class="row g-4"><input type="hidden" name="id" id="gateway-id" value="0">
<div class="col-md-6"><label class="form-label">Display name</label><input class="form-control" name="display_name" id="display-name" required></div><div class="col-md-6"><label class="form-label">API URL</label><input class="form-control" type="url" name="api_url" id="api-url" required></div>
<div class="col-md-6"><label class="form-label">Merchant ID</label><input class="form-control" name="merchant_id" id="merchant-id" required></div><div class="col-md-6"><label class="form-label">Secret Key</label><input class="form-control" type="password" name="secret_key" id="secret-key" required autocomplete="new-password"></div>
<div class="col-md-8"><label class="form-label">Callback URL</label><input class="form-control" type="url" name="notify_url" id="notify-url" required></div><div class="col-md-2"><label class="form-label">Currency</label><input class="form-control" name="currency_code" id="currency" value="INR" maxlength="10"></div><div class="col-md-2 d-flex align-items-end"><div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="is_active" id="is-active"><label class="form-check-label">Active</label></div></div>
<div class="col-12"><button class="btn btn-primary w-100">Save Gateway</button></div></form></div></div>
<div class="row g-4"><?php foreach($gateways as $g): $publicGateway=array('id'=>$g['id'],'display_name'=>$g['display_name'],'api_url'=>$g['api_url'],'merchant_id'=>$g['merchant_id'],'notify_url'=>$g['notify_url'],'currency_code'=>$g['currency_code'],'is_active'=>$g['is_active']); ?><div class="col-lg-6"><div class="card h-100 <?=$g['is_active']?'border border-success':''?>"><div class="card-body"><div class="d-flex justify-content-between"><h5><?=htmlspecialchars($g['display_name'])?></h5><span class="badge <?=$g['is_active']?'bg-success':'bg-secondary'?>"><?=$g['is_active']?'ACTIVE':'INACTIVE'?></span></div><p class="text-muted mb-2"><?=htmlspecialchars($g['api_url'])?></p><p class="mb-3">Merchant: <?=htmlspecialchars($g['merchant_id'])?> · Secret: ••••••••</p><button type="button" class="btn btn-outline-primary edit-gateway" data-record='<?=htmlspecialchars(json_encode($publicGateway),ENT_QUOTES,'UTF-8')?>'>Edit</button></div></div></div><?php endforeach;?></div>
</div></div></div></div></div><script src="assets/vendor/libs/jquery/jquery.js"></script><script src="assets/vendor/js/bootstrap.js"></script><script src="assets/js/main.js"></script><script>document.querySelectorAll('.edit-gateway').forEach(function(b){b.addEventListener('click',function(){const g=JSON.parse(this.dataset.record);document.getElementById('gateway-id').value=g.id;document.getElementById('display-name').value=g.display_name;document.getElementById('api-url').value=g.api_url;document.getElementById('merchant-id').value=g.merchant_id;document.getElementById('secret-key').value='';document.getElementById('secret-key').placeholder='Leave blank to keep current key';document.getElementById('secret-key').required=false;document.getElementById('notify-url').value=g.notify_url;document.getElementById('currency').value=g.currency_code;document.getElementById('is-active').checked=Number(g.is_active)===1;window.scrollTo({top:0,behavior:'smooth'});});});</script></body></html>
