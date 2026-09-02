<?php
function send_saas_bet_history_if_present(mysqli $conn, int $userId, int $gameType, int $pageNo, int $pageSize, string $startDate='', string $endDate=''): void
{
    $exists=$conn->query("SHOW TABLES LIKE 'saas_lottery_bets'");
    if(!$exists||$exists->num_rows===0)return;
    $prefixes=[1=>'WinGo\_%',13=>'TrxWinGo\_%',5=>'D5\_%',9=>'K3\_%',17=>'MotoRace\_%'];
    if(!isset($prefixes[$gameType]))return;
    $pattern=$prefixes[$gameType];$pageNo=max(1,$pageNo);$pageSize=min(100,max(1,$pageSize));$offset=($pageNo-1)*$pageSize;
    $dateSql='';$types='is';$params=[$userId,$pattern];
    if($startDate!==''&&$endDate!==''){$dateSql=' AND DATE(created_at)>=? AND DATE(created_at)<=?';$types.='ss';$params[]=$startDate;$params[]=$endDate;}
    $stmt=$conn->prepare("SELECT COUNT(*) FROM saas_lottery_bets WHERE user_id=? AND game_code LIKE ?{$dateSql}");
    $stmt->bind_param($types,...$params);$stmt->execute();$total=0;$stmt->bind_result($total);$stmt->fetch();$stmt->close();
    if((int)$total===0)return;
    $taxColumn=$conn->query("SHOW COLUMNS FROM saas_lottery_bets LIKE 'tax_fee'");
    $taxExpr=($taxColumn&&$taxColumn->num_rows>0)?'tax_fee':'0 AS tax_fee';
    $queryTypes=$types.'ii';$queryParams=array_merge($params,[$pageSize,$offset]);
    $stmt=$conn->prepare("SELECT id,game_code,issue_number,bet_content,amount,bet_multiple,bet_units,stake,status,result_premium,payout,{$taxExpr},created_at,settled_at FROM saas_lottery_bets WHERE user_id=? AND game_code LIKE ?{$dateSql} ORDER BY id DESC LIMIT ? OFFSET ?");
    $stmt->bind_param($queryTypes,...$queryParams);$stmt->execute();$result=$stmt->get_result();$list=[];
    $typeIds=['WinGo_30S'=>30,'WinGo_1M'=>1,'WinGo_3M'=>3,'WinGo_5M'=>5,'TrxWinGo_1M'=>13,'TrxWinGo_3M'=>14,'TrxWinGo_5M'=>15,'TrxWinGo_10M'=>16,'D5_1M'=>5,'D5_3M'=>6,'D5_5M'=>7,'D5_10M'=>8,'K3_1M'=>9,'K3_3M'=>10,'K3_5M'=>11,'K3_10M'=>12,'MotoRace_1M'=>17];
    while($row=$result->fetch_assoc()){
        $stake=(float)$row['stake'];$payout=(float)$row['payout'];$state=$row['status']==='pending'?2:($row['status']==='won'?1:0);$parts=explode('_',(string)$row['bet_content'],2);$select=(string)($parts[1]??$parts[0]??'');$premium=$row['result_premium']===null?'':(string)$row['result_premium'];
        $tax=round((float)($row['tax_fee']??0),4);
        if($tax<=0&&$stake>0){$tax=round($stake*0.02,4);}
        $list[]=['betID'=>(int)$row['id'],'orderNumber'=>(string)$row['id'],'userID'=>$userId,'issueNumber'=>(string)$row['issue_number'],'typeID'=>(int)($typeIds[$row['game_code']]??$gameType),'gameType'=>$gameType,'gameCode'=>$row['game_code'],'amount'=>(float)$row['amount'],'betCount'=>(int)$row['bet_multiple'],'selectType'=>$select,'realAmount'=>max(0.0,round($stake-$tax,4)),'serviceCharge'=>$tax,'figure'=>$stake,'state'=>$state,'profitAmount'=>$state===2?null:round($payout-$stake,2),'winAmount'=>$payout,'addTime'=>$row['created_at'],'settlementTime'=>$row['settled_at'],'fee'=>$tax,'tax'=>$tax,'taxAmount'=>$tax,'taxRate'=>2.0,'premium'=>$premium,'number'=>$premium,'sumCount'=>$premium];
    }
    $stmt->close();
    echo json_encode(['data'=>['list'=>$list,'pageNo'=>$pageNo,'totalPage'=>(int)ceil($total/$pageSize),'totalCount'=>(int)$total],'code'=>0,'msg'=>'Succeed','msgCode'=>0,'serviceNowTime'=>date('Y-m-d H:i:s')],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);exit;
}
