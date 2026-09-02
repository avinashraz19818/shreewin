<?php
function promotion_level_members(int $userId, int $level, int $pageNo, int $pageSize): array
{
    global $conn;
    $level=max(1,min(6,$level));$pageNo=max(1,$pageNo);$pageSize=min(100,max(1,$pageSize));$offset=($pageNo-1)*$pageSize;
    $stmt=$conn->prepare('SELECT owncode FROM shonu_subjects WHERE id=? LIMIT 1');$stmt->bind_param('i',$userId);$stmt->execute();$own='';$stmt->bind_result($own);$stmt->fetch();$stmt->close();
    if($own==='')return ['list'=>[],'pageNo'=>$pageNo,'totalPage'=>0,'totalCount'=>0];
    $column=$level===1?'code':'code'.($level-1);
    $stmt=$conn->prepare("SELECT COUNT(*) FROM shonu_subjects WHERE `{$column}`=?");$stmt->bind_param('s',$own);$stmt->execute();$total=0;$stmt->bind_result($total);$stmt->fetch();$stmt->close();
    $stmt=$conn->prepare("SELECT id,mobile,codechorkamukala,createdate FROM shonu_subjects WHERE `{$column}`=? ORDER BY id DESC LIMIT ? OFFSET ?");$stmt->bind_param('sii',$own,$pageSize,$offset);$stmt->execute();$result=$stmt->get_result();$list=[];
    while($row=$result->fetch_assoc()){$memberId=(int)$row['id'];$bet=0.0;if(api_table_exists('saas_lottery_bets')){$sum=$conn->prepare('SELECT COALESCE(SUM(stake),0) FROM saas_lottery_bets WHERE user_id=?');$sum->bind_param('i',$memberId);$sum->execute();$sum->bind_result($bet);$sum->fetch();$sum->close();}$mobile=preg_replace('/\D+/','',(string)$row['mobile']);$list[]=['userId'=>$memberId,'uid'=>$memberId,'userName'=>$row['codechorkamukala']?:('Member'.$memberId),'mobile'=>strlen($mobile)>6?substr($mobile,0,3).'****'.substr($mobile,-3):$mobile,'level'=>$level,'lv'=>$level,'registerTime'=>$row['createdate'],'createTime'=>$row['createdate'],'depositAmount'=>0.0,'rechargeAmount'=>0.0,'betAmount'=>(float)$bet,'lotteryAmount'=>(float)$bet,'commissionAmount'=>0.0];}
    $stmt->close();return ['list'=>$list,'pageNo'=>$pageNo,'totalPage'=>$total?(int)ceil($total/$pageSize):0,'totalCount'=>(int)$total];
}
