<?php
if (!defined('VA_ADMIN')) { http_response_code(404); exit; }
function va_catalog(): array {
    return [
        'users'=>['table'=>'shonu_subjects','key'=>'id','title'=>'Users','icon'=>'ri-team-line','cols'=>['id'=>'User ID','mobile'=>'Mobile','email'=>'Email','codechorkamukala'=>'Nickname','status'=>'Status','createdate'=>'Registered','shonullgnt'=>'Last login']],
        'wallets'=>['table'=>'shonu_kaichila','key'=>'balakedara','title'=>'Wallet records','icon'=>'ri-wallet-3-line','cols'=>['balakedara'=>'User ID','motta'=>'Balance (raw)','turnover'=>'Turnover (raw)','bonus'=>'Bonus (raw)']],
        'deposits'=>['table'=>'thevani','key'=>'shonu','title'=>'Deposits','icon'=>'ri-arrow-left-down-line','cols'=>['shonu'=>'Row ID','balakedara'=>'User ID','dharavahi'=>'Order number','motta'=>'Amount (raw)','sthiti'=>'Status code','madari'=>'Type code','mula'=>'Method','ullekha'=>'Reference','dinankavannuracisi'=>'Recorded time']],
        'withdrawals'=>['table'=>'hintegedukolli','key'=>'shonu','title'=>'Withdrawals','icon'=>'ri-arrow-right-up-line','cols'=>['shonu'=>'Row ID','balakedara'=>'User ID','dharavahi'=>'Order number','motta'=>'Amount (raw)','sthiti'=>'Status code','madari'=>'Type code','remarks'=>'Remarks','dinankavannuracisi'=>'Recorded time']],
        'bets'=>['table'=>'veegame_saas_bets','key'=>'id','title'=>'WinGo bet records','icon'=>'ri-gamepad-line','cols'=>['id'=>'Bet ID','user_id'=>'User ID','game_code'=>'Game','issue_number'=>'Period','issue'=>'Period','stake'=>'Stake','amount'=>'Amount','status'=>'Status','bet_content'=>'Selection','payout'=>'Payout','created_at'=>'Created','placed_at'=>'Placed','settled_at'=>'Settled']],
        'ledger'=>['table'=>'veegame_saas_wallet_ledger','key'=>'id','title'=>'WinGo ledger','icon'=>'ri-file-list-3-line','cols'=>['id'=>'Entry ID','user_id'=>'User ID','bet_id'=>'Bet ID','entry_type'=>'Type','entry_key'=>'Entry key','amount'=>'Amount','balance_before'=>'Before','balance_after'=>'After','created_at'=>'Recorded']],
        'audit'=>['table'=>'veegame_admin_audit','key'=>'id','title'=>'Admin audit log','icon'=>'ri-shield-check-line','cols'=>['id'=>'Event','admin_id'=>'Admin ID','action'=>'Action','target'=>'Target','detail'=>'Details','created_at'=>'Time']]
    ];
}
function va_columns(string $table): array {
    static $cache=[];
    if (!array_key_exists($table,$cache)) $cache[$table]=va_rows('SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,NUMERIC_PRECISION,NUMERIC_SCALE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? ORDER BY ORDINAL_POSITION',[$table]);
    return $cache[$table];
}
function va_colnames(string $table): array { return array_column(va_columns($table),'COLUMN_NAME'); }
function va_available(string $table, array $required): bool { return !array_diff($required,va_colnames($table)); }
function va_unique(string $table, string $column): bool {
    return (bool)va_one('SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? GROUP BY INDEX_NAME HAVING MAX(NON_UNIQUE)=0 AND COUNT(*)=1 AND MIN(COLUMN_NAME)=? AND MAX(COALESCE(SUB_PART,0))=0',[$table,$column]);
}
function va_count(string $table): ?string {
    $allowed=array_column(va_catalog(),'table');
    if (!in_array($table,$allowed,true) || !va_columns($table)) return null;
    return (string)va_one('SELECT COUNT(*) AS n FROM `'.$table.'`')['n'];
}
function va_page_data(string $page): array {
    $d=va_catalog()[$page] ?? null;
    if (!$d) va_fail(404,'Page not found.');
    $cols=array_intersect_key($d['cols'],array_flip(va_colnames($d['table'])));
    if (!isset($cols[$d['key']])) return $d+['available'=>false,'columns'=>$cols,'rows'=>[]];
    $q=trim(va_input('q',$_GET)); if (strlen($q)>100) va_fail(400,'Search is too long.');
    $where='';$params=[];
    if ($q!=='') {
        $search=[];
        foreach(['id','balakedara','mobile','email','codechorkamukala','dharavahi','user_id','issue_number','issue','target','action'] as $c) {
            if (isset($cols[$c])) { $search[]='CAST(`'.$c.'` AS CHAR) LIKE ?'; $params[]='%'.strtr($q,['='=>'==','%'=>'=%','_'=>'=_']).'%'; }
        }
        if ($search) $where=' WHERE ('.implode(" ESCAPE '=' OR ",$search)." ESCAPE '=')";
    }
    $status=va_input('status',$_GET);
    $statusColumn=$page==='users'?'status':(in_array($page,['deposits','withdrawals'],true)?'sthiti':null);
    if ($status!=='' && $statusColumn && isset($cols[$statusColumn])) {
        if (!preg_match('/^[0-3]$/D',$status)) va_fail(400,'Invalid status filter.');
        $where.=($where?' AND ':' WHERE ').'`'.$statusColumn.'`=?';$params[]=$status;
    }
    $p=va_input('p',$_GET);$p=$p===''?1:filter_var($p,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>100000]]);
    if ($p===false) va_fail(400,'Invalid page number.');
    $total=(int)va_one('SELECT COUNT(*) AS n FROM `'.$d['table'].'`'.$where,$params)['n'];
    $pages=max(1,(int)ceil($total/25));$p=min($p,$pages);
    $select=implode(',',array_map(static function($c){return '`'.$c.'`';},array_keys($cols)));
    $rows=va_rows('SELECT '.$select.' FROM `'.$d['table'].'`'.$where.' ORDER BY `'.$d['key'].'` DESC LIMIT 25 OFFSET '.(($p-1)*25),$params);
    return $d+['available'=>true,'columns'=>$cols,'rows'=>$rows,'total'=>$total,'page'=>$p,'pages'=>$pages,'q'=>$q,'filter'=>$status];
}
function va_user(int $id): ?array {
    if (!va_available('shonu_subjects',['id','codechorkamukala','status'])) return null;
    $cols=array_intersect(['id','mobile','email','codechorkamukala','status','createdate','shonullgnt'],va_colnames('shonu_subjects'));
    $rows=va_rows('SELECT `'.implode('`,`',$cols).'` FROM shonu_subjects WHERE id=? LIMIT 2',[$id]);
    return count($rows)===1?$rows[0]:null;
}
function va_user_version(array $u): string { return hash_hmac('sha256',json_encode($u,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),$_SESSION['csrf']); }
function va_checks(): array {
    $engine=va_one("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='shonu_kaichila'");
    $amount=null;
    foreach(va_columns('shonu_kaichila') as $c) if($c['COLUMN_NAME']==='motta')$amount=$c;
    $state='Not installed';
    if(va_available('veegame_saas_settings',['setting_key','setting_value'])) {
        $r=va_one("SELECT setting_value FROM veegame_saas_settings WHERE setting_key='migration_state'");$state=$r['setting_value']??'Not set';
    }
    return [
        'Wallet engine'=>$engine['ENGINE']??'Table missing',
        'Wallet amount column'=>$amount['COLUMN_TYPE']??'Column missing',
        'Unique wallet user index'=>va_unique('shonu_kaichila','balakedara')?'YES':'NO',
        'WinGo migration state'=>$state,
        'Admin user editing'=>(va_unique('shonu_subjects','id') && va_available('shonu_subjects',['id','status','codechorkamukala','akshinak']))?'Compatible':'Unavailable — required columns / unique ID missing',
        'Wallet adjustments'=>'Not enabled — financial cutover/reconciliation not completed',
        'Deposit approvals / payout'=>'Not enabled — gateway, reward rules and wallet safety need verification',
        'WinGo activation'=>'Existing migration safeguards retained — no automatic activation',
        'Site settings'=>'Existing frontend has static / hard-coded settings; not connected in this release'
    ];
}
function va_schema_report(): array {
    // Schema metadata only; never data, defaults, credentials, account hashes or tokens.
    return ['report'=>'Veegame integration structure','tables'=>va_rows('SELECT TABLE_NAME,ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE=\'BASE TABLE\' ORDER BY TABLE_NAME'),
    'columns'=>va_rows('SELECT TABLE_NAME,COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,COLUMN_KEY,EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME,ORDINAL_POSITION'),
    'indexes'=>va_rows('SELECT TABLE_NAME,INDEX_NAME,NON_UNIQUE,SEQ_IN_INDEX,COLUMN_NAME,SUB_PART FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME,INDEX_NAME,SEQ_IN_INDEX')];
}
