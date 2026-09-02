<?php
/** Betting rebate helpers. 0.85 means 0.85 percent of valid wager volume. */
function betting_rebate_rate(): float { return 0.85; }

function betting_rebate_legacy_tables(): array {
    return [
        'bajikattuttate','bajikattuttate_drei','bajikattuttate_funf','bajikattuttate_zehn',
        'bajikattuttate_trx','bajikattuttate_trx3','bajikattuttate_trx5','bajikattuttate_trx10',
        'bajikattuttate_aidudi','bajikattuttate_aidudi_drei','bajikattuttate_aidudi_funf','bajikattuttate_aidudi_zehn',
        'bajikattuttate_kemuru','bajikattuttate_kemuru_drei','bajikattuttate_kemuru_funf','bajikattuttate_kemuru_zehn'
    ];
}

function betting_rebate_total_wager(int $userId): float {
    global $conn;
    $total = 0.0;
    if (api_table_exists('saas_lottery_bets')) {
        $stmt = $conn->prepare("SELECT COALESCE(SUM(stake),0) FROM saas_lottery_bets WHERE user_id=? AND status <> 'cancelled'");
        if ($stmt) { $stmt->bind_param('i',$userId); $stmt->execute(); $v=0.0; $stmt->bind_result($v); if($stmt->fetch()) $total += (float)$v; $stmt->close(); }
    }
    foreach (betting_rebate_legacy_tables() as $table) {
        if (!api_table_exists($table)) continue;
        $stmt = $conn->prepare("SELECT COALESCE(SUM(ketebida),0) FROM `{$table}` WHERE byabaharkarta=?");
        if (!$stmt) continue;
        $stmt->bind_param('i',$userId); $stmt->execute(); $v=0.0; $stmt->bind_result($v); if($stmt->fetch()) $total += (float)$v; $stmt->close();
    }
    return round(max(0.0,$total),4);
}

function betting_rebate_claimed_volume(int $userId): float {
    global $conn;
    if (!api_table_exists('rebetrec')) return 0.0;
    $stmt=$conn->prepare('SELECT COALESCE(SUM(rebet),0) FROM rebetrec WHERE user_id=?');
    if(!$stmt) return 0.0;
    $stmt->bind_param('i',$userId); $stmt->execute(); $v=0.0; $stmt->bind_result($v); $stmt->fetch(); $stmt->close();
    return round((float)$v,4);
}

function betting_rebate_available_volume(int $userId): float {
    return round(max(0.0, betting_rebate_total_wager($userId)-betting_rebate_claimed_volume($userId)),4);
}

function betting_rebate_summary(int $userId): array {
    global $conn;
    $rate=betting_rebate_rate();
    $volume=betting_rebate_available_volume($userId);
    $day=0.0; $total=0.0; $list=[];
    if (api_table_exists('rebetrec')) {
        $stmt=$conn->prepare('SELECT COALESCE(SUM(motta),0),COALESCE(SUM(CASE WHEN DATE(created_at)=CURDATE() THEN motta ELSE 0 END),0) FROM rebetrec WHERE user_id=?');
        if($stmt){$stmt->bind_param('i',$userId);$stmt->execute();$stmt->bind_result($total,$day);$stmt->fetch();$stmt->close();}
        $stmt=$conn->prepare('SELECT rebet,rate,motta,created_at FROM rebetrec WHERE user_id=? ORDER BY id DESC LIMIT 3');
        if($stmt){$stmt->bind_param('i',$userId);$stmt->execute();$rs=$stmt->get_result();while($r=$rs->fetch_assoc()){$list[]=['codeType'=>3,'washVolume'=>(float)$r['rebet'],'washRate'=>(float)$r['rate'],'rebateAmount'=>(float)$r['motta'],'addTime'=>(string)$r['created_at']];}$stmt->close();}
    }
    return ['codeWashAmount'=>(float)$volume,'dayRebate'=>round((float)$day,2),'totalRebate'=>round((float)$total,2),'washRate'=>$rate,'washList'=>$list ?: null];
}
