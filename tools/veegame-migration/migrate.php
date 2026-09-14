<?php
/** CLI only. Defaults to a READ-ONLY preflight. Never imports a bet as payable or changes wallet amounts. */
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require_once dirname(__DIR__,2).'/saas_lottery/bootstrap_live_v4.php';
function vee_set($key,$value): void {
    global $conn;
    $s=$conn->prepare('INSERT INTO veegame_saas_settings(setting_key,setting_value,updated_at) VALUES (?,?,NOW()) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_at=NOW()');
    $s->bind_param('ss',$key,$value);$s->execute();$s->close();
}
function vee_preflight(): array {
    global $conn;
    $legacy=vee_legacy_snapshot();$blockers=[];
    foreach ($legacy as $table=>$r) if ($r['pending']) $blockers[]=$table.': '.$r['pending'].' pending legacy records';
    foreach (['saas_wingo_bets','saas_lottery_bets'] as $table) {
        if (app_table_exists($table) && (int)$conn->query("SELECT COUNT(*) AS n FROM `$table`")->fetch_assoc()['n']>0) $blockers[]='Unmapped existing ledger: '.$table;
    }
    $row=$conn->query("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='shonu_kaichila'")->fetch_assoc();
    if (strtoupper((string)($row['ENGINE']??''))!=='INNODB') $blockers[]='Wallet table must use InnoDB before activation';
    $money=$conn->query("SELECT DATA_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='shonu_kaichila' AND COLUMN_NAME='motta'")->fetch_assoc();
    if (!in_array(strtolower((string)($money['DATA_TYPE']??'')),['decimal','numeric'],true)) $blockers[]='Wallet amount must use an exact DECIMAL type; existing precision must be reviewed first';
    $indexes=$conn->query('SHOW INDEX FROM shonu_kaichila')->fetch_all(MYSQLI_ASSOC);$unique=[];
    foreach ($indexes as $i) if (!(int)$i['Non_unique']) $unique[$i['Key_name']][]=$i['Column_name'];
    if (!in_array(['balakedara'],array_values($unique),true)) $blockers[]='Wallet requires a UNIQUE single-column index on balakedara; do not add until duplicates are reviewed';
    return ['legacy'=>$legacy,'wallet'=>vee_wallet_snapshot(),'blockers'=>$blockers];
}
$args=array_slice($argv,1);$command=$args[0]??'--status';
try {
    if ($command==='--status') {
        $result=vee_preflight();$result['state']=app_table_exists('veegame_saas_settings')?app_setting('migration_state','preview'):'not-prepared';
    } elseif ($command==='--prepare') {
        sl_install_schema();$result=['state'=>app_setting('migration_state','preview'),'walletWrites'=>0];
    } else {
        sl_install_schema();
        $lock=$conn->query("SELECT GET_LOCK('veegame-migration-cutover-v1',0) AS acquired")->fetch_assoc();
        if ((int)$lock['acquired']!==1) throw new RuntimeException('Another cutover command is running');
        $state=app_setting('migration_state','preview');
        if ($command==='--freeze-legacy') {
            if ($state!=='preview' && $state!=='frozen') throw new RuntimeException('Cannot freeze after activation; use --pause');
            vee_set('migration_state','frozen');vee_set('legacy_frozen_at',(string)time());
            $result=['state'=>'frozen','next'=>'Let old requests finish; reconcile pending bets, then stop legacy WinGo crons and money operations before archiving.'];
        } elseif ($command==='--archive') {
            if ($state!=='frozen') throw new RuntimeException('Freeze legacy betting first');
            if (!in_array('--maintenance-confirmed',$args,true) || !in_array('--legacy-cron-stopped',$args,true)) throw new RuntimeException('Maintenance and stopped legacy WinGo crons must be explicitly confirmed');
            if (time()-(int)app_setting('legacy_frozen_at',time())<30) throw new RuntimeException('Wait at least 30 seconds after freeze, and verify no in-flight legacy requests remain');
            $before=vee_preflight();
            if ($before['blockers']) throw new RuntimeException(implode('; ',$before['blockers']));
            $conn->begin_transaction();
            try {
                $counts=vee_archive_legacy();
                foreach ($before['legacy'] as $table=>$snapshot) if (($counts[$table]??-1)!==$snapshot['count']) throw new RuntimeException('Archive row-count mismatch');
                $after=vee_preflight();
                if ($before!==$after) throw new RuntimeException('Source records/wallet changed during archive; cutover aborted');
                vee_set('archive_snapshot',json_encode($after,JSON_THROW_ON_ERROR));
                vee_set('archived_at',date('Y-m-d H:i:s'));
                $conn->commit();
            } catch (Throwable $e) { $conn->rollback();throw $e; }
            $result=['archiveCounts'=>$counts,'walletBefore'=>$before['wallet'],'walletAfter'=>$after['wallet'],'walletWrites'=>0,'legacyRecordsReplayed'=>0];
        } elseif ($command==='--activate') {
            if (!in_array('--rewards-scope-reviewed',$args,true)) throw new RuntimeException('Review WinGo-only scope: VIP, referral commissions and deposit-turnover tracking are not migrated');
            if ($state!=='frozen') throw new RuntimeException('Activation requires a frozen, reconciled archive');
            if (!in_array('--maintenance-confirmed',$args,true) || !in_array('--legacy-cron-stopped',$args,true)) throw new RuntimeException('Maintenance and stopped legacy crons must be confirmed');
            $archived=json_decode(app_setting('archive_snapshot','null'),true);$current=vee_preflight();
            if (!$archived || $current!==$archived || $current['blockers']) throw new RuntimeException('Archive missing or wallet/source changed; reconcile before activation');
            foreach (array_keys($SL_CONFIG['games']) as $game) {
                if (!sl_provider_current($game) || !sl_provider_history($game)) throw new RuntimeException('Provider unavailable for '.$game);
            }
            vee_set('migration_state','active');vee_set('activated_at',date('Y-m-d H:i:s'));
            $result=['state'=>'active','legacyReplay'=>false,'walletWrites'=>0,'newLedger'=>'veegame_saas_bets'];
        } elseif ($command==='--pause') {
            if (!in_array($state,['active','paused'],true)) throw new RuntimeException('Not yet active');
            vee_set('migration_state','paused');$result=['state'=>'paused','settlement'=>'continues for existing SaaS bets'];
        } elseif ($command==='--settle') {
            if (!in_array($state,['active','paused'],true)) throw new RuntimeException('Not active');
            foreach (array_keys($SL_CONFIG['games']) as $game) {
                $currentPeriod=sl_provider_current($game);if (!$currentPeriod) continue;
                $currentIssue=$currentPeriod['current']['issueNumber'];$lastPageHash='';
                // Backfill only genuine provider history. Never synthesize results for expired provider retention.
                for ($page=1;$page<=50;$page++) {
                    $s=$conn->prepare("SELECT COUNT(*) FROM veegame_saas_bets WHERE game_code=? AND status='pending' AND issue_number<?");$s->bind_param('ss',$game,$currentIssue);$s->execute();$s->bind_result($pending);$s->fetch();$s->close();
                    if (!$pending) break;
                    $payload=sl_provider_history($game,$page,10);if (!$payload) break;
                    $pageHash=hash('sha256',json_encode($payload['data']['list']));if ($pageHash===$lastPageHash) break;$lastPageHash=$pageHash;
                    sl_save_and_settle_results($game,$payload['data']['list']);
                }
            }
            $result=['pending'=>(int)$conn->query("SELECT COUNT(*) AS n FROM veegame_saas_bets WHERE status='pending'")->fetch_assoc()['n']];
        } else throw new RuntimeException('Unknown command');
        $conn->query("SELECT RELEASE_LOCK('veegame-migration-cutover-v1')");
    }
    echo json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n";
} catch (Throwable $e) {
    fwrite(STDERR,json_encode(['ok'=>false,'error'=>$e->getMessage()])."\n");exit(1);
}
