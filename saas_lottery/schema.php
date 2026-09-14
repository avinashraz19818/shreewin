<?php
function sl_install_schema(): void
{
    global $conn;
    static $ready=false;
    if ($ready) return;
    app_install_schema($conn);
    $install=app_setting('integration_schema_version','')!=='v1';
    if ($install) {
        $lock=$conn->query("SELECT GET_LOCK('veegame-saas-schema-v1',10) AS acquired")->fetch_assoc();
        if ((int)$lock['acquired']!==1) throw new RuntimeException('Schema installation busy');
    }
    try {
        if ($install) {
        foreach (explode(';',file_get_contents(__DIR__.'/schema.sql')) as $sql) if (trim($sql)!=='') $conn->query($sql);
        $conn->query("CREATE TABLE IF NOT EXISTS veegame_legacy_archive (source_table VARCHAR(64) NOT NULL,source_id BIGINT NOT NULL,user_id BIGINT NOT NULL,raw_record LONGTEXT NOT NULL,record_hash CHAR(64) NOT NULL,archived_at DATETIME NOT NULL,PRIMARY KEY(source_table,source_id),KEY by_user(user_id,source_table,source_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        // Refuse incompatible pre-existing schemas rather than rewriting their money/status columns.
        $required=[
            'veegame_legacy_archive'=>['source_table','source_id','user_id','raw_record','record_hash','archived_at'],
            'veegame_saas_bets'=>['id','user_id','game_code','issue_number','bet_content','amount','bet_multiple','bet_units','stake','request_group_key','request_key','status','result_premium','payout','tax_fee','created_at','settled_at'],
            'veegame_saas_requests'=>['request_group_key','user_id','game_code','issue_number','created_at'],
            'veegame_saas_wallet_ledger'=>['entry_key','entry_type','user_id','amount','balance_before','balance_after','created_at'],
            'veegame_saas_results'=>['game_code','issue_number','premium','number','color','result_sum','provider_seen_at','created_at'],
        ];
        if (!app_schema_columns_exist($required,$conn)) throw new RuntimeException('Incompatible pre-existing migration schema; manual review required');
        $keys=['veegame_saas_bets'=>['request_key'],'veegame_saas_requests'=>['request_group_key'],
            'veegame_saas_wallet_ledger'=>['entry_key'],'veegame_saas_results'=>['game_code','issue_number'],
            'veegame_legacy_archive'=>['source_table','source_id']];
        if (in_array(app_setting('migration_state','preview'),['active','paused'],true)) $keys['shonu_kaichila']=['balakedara'];
        $names=array_keys($keys);$marks=implode(',',array_fill(0,count($names),'?'));
        $q=$conn->prepare('SELECT TABLE_NAME,ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('.$marks.')');
        $q->bind_param(str_repeat('s',count($names)),...$names);$q->execute();$rows=app_stmt_result($q);$engines=[];
        while ($r=$rows->fetch_assoc()) $engines[$r['TABLE_NAME']]=$r['ENGINE'];$q->close();
        $q=$conn->prepare('SELECT TABLE_NAME,INDEX_NAME,COLUMN_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND NON_UNIQUE=0 AND TABLE_NAME IN ('.$marks.') ORDER BY TABLE_NAME,INDEX_NAME,SEQ_IN_INDEX');
        $q->bind_param(str_repeat('s',count($names)),...$names);$q->execute();$rows=app_stmt_result($q);$unique=[];
        while ($r=$rows->fetch_assoc()) $unique[$r['TABLE_NAME']][$r['INDEX_NAME']][]=$r['COLUMN_NAME'];$q->close();
        foreach ($keys as $table=>$requiredKey) {
            if (strtoupper((string)($engines[$table]??''))!=='INNODB') throw new RuntimeException('Transactional engine required: '.$table);
            if (!in_array($requiredKey,array_values($unique[$table]??[]),true)) throw new RuntimeException('Required unique index missing: '.$table);
        }
        if ($install) $conn->query("INSERT INTO veegame_saas_settings VALUES ('integration_schema_version','v1',NOW()) ON DUPLICATE KEY UPDATE setting_value='v1',updated_at=NOW()");
        $ready=true;
    } finally { if ($install) $conn->query("SELECT RELEASE_LOCK('veegame-saas-schema-v1')"); }
}
function sl_game_enabled($gameCode): bool
{
    global $SL_CONFIG;
    return isset($SL_CONFIG['games'][$gameCode]) && app_setting_bool('game_'.$gameCode.'_enabled',true);
}
