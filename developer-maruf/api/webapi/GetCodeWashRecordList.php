<?php
require_once __DIR__ . '/_common.php';
require_once __DIR__ . '/_betting_rebate.php';
api_require_post();
$body=api_input();
api_require_signature($body);
$user=api_user();
$codeType=(int)($body['codeType'] ?? -1);
$pageNo=max(1,(int)($body['pageNo'] ?? 1));
$pageSize=min(100,max(1,(int)($body['pageSize'] ?? 10)));
$offset=($pageNo-1)*$pageSize;
$userId=(int)$user['id'];
if($codeType!==-1 && $codeType!==3){ api_send(['list'=>[],'pageNo'=>$pageNo,'pageSize'=>$pageSize,'totalPage'=>0,'totalCount'=>0]); }
$count=0;
$stmt=$conn->prepare('SELECT COUNT(*) FROM rebetrec WHERE user_id=?');
if($stmt){$stmt->bind_param('i',$userId);$stmt->execute();$stmt->bind_result($count);$stmt->fetch();$stmt->close();}
$list=[];
$stmt=$conn->prepare('SELECT id,rebet,rate,motta,created_at FROM rebetrec WHERE user_id=? ORDER BY id DESC LIMIT ? OFFSET ?');
if($stmt){$stmt->bind_param('iii',$userId,$pageSize,$offset);$stmt->execute();$rs=$stmt->get_result();while($r=$rs->fetch_assoc()){$list[]=['id'=>(int)$r['id'],'codeType'=>3,'washVolume'=>(float)$r['rebet'],'washRate'=>(float)$r['rate'],'rebateAmount'=>(float)$r['motta'],'addTime'=>(string)$r['created_at']];}$stmt->close();}
api_send(['list'=>$list,'pageNo'=>$pageNo,'pageSize'=>$pageSize,'totalPage'=>(int)ceil($count/$pageSize),'totalCount'=>(int)$count]);
