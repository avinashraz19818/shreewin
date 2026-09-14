<?php
// Keep original records separate: old settlement code overwrites tiarikala, so it
// cannot honestly be relabelled as the original bet-placement timestamp.
function vee_legacy_sources(): array
{
    return ['bajikattuttate','bajikattuttate_drei','bajikattuttate_funf','bajikattuttate_zehn','bajikattuttate30'];
}
function vee_legacy_snapshot(): array
{
    global $conn;
    $report=[];
    foreach (vee_legacy_sources() as $table) {
        if (!app_table_exists($table)) continue;
        foreach (['parichaya','byabaharkarta','ergebnis','tiarikala'] as $column) {
            if (!app_column_exists($table,$column)) throw new RuntimeException('Legacy schema requires review: '.$table.'.'.$column);
        }
        $rows=$conn->query("SELECT * FROM `$table` ORDER BY parichaya",MYSQLI_USE_RESULT);$ctx=hash_init('sha256');$count=0;$pending=0;
        while ($row=$rows->fetch_assoc()) {
            $raw=json_encode($row,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
            hash_update($ctx,$raw."\n");$count++;
            if ((int)$row['parichaya']<1) throw new RuntimeException('Non-positive legacy record ID requires review');
            if (!preg_match('/^[0-9]$/',(string)($row['ergebnis']??''))) $pending++;
        }
        $report[$table]=['count'=>$count,'pending'=>$pending,'sha256'=>hash_final($ctx)];
    }
    return $report;
}
function vee_wallet_snapshot(): array
{
    global $conn;
    $rows=$conn->query('SELECT balakedara,motta FROM shonu_kaichila ORDER BY balakedara',MYSQLI_USE_RESULT);$ctx=hash_init('sha256');$count=0;
    while ($row=$rows->fetch_assoc()) { hash_update($ctx,json_encode($row)."\n");$count++; }
    return ['count'=>$count,'sha256'=>hash_final($ctx)];
}
function vee_archive_legacy(): array
{
    global $conn;
    $counts=[];
    foreach (vee_legacy_sources() as $table) {
        if (!app_table_exists($table)) continue;
        $n=0;$last=0;
        do {
        $rows=$conn->query("SELECT * FROM `$table` WHERE parichaya>$last ORDER BY parichaya LIMIT 500");$batch=$rows->num_rows;
        while ($row=$rows->fetch_assoc()) {
            $id=(int)$row['parichaya'];$userId=(int)$row['byabaharkarta'];
            $raw=json_encode($row,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);$hash=hash('sha256',$raw);
            $s=$conn->prepare('INSERT IGNORE INTO veegame_legacy_archive(source_table,source_id,user_id,raw_record,record_hash,archived_at) VALUES (?,?,?,?,?,NOW())');
            $s->bind_param('siiss',$table,$id,$userId,$raw,$hash);$s->execute();$s->close();
            $s=$conn->prepare('SELECT record_hash FROM veegame_legacy_archive WHERE source_table=? AND source_id=?');
            $s->bind_param('si',$table,$id);$s->execute();$saved=null;$s->bind_result($saved);$s->fetch();$s->close();
            if (!is_string($saved) || !hash_equals($hash,$saved)) throw new RuntimeException('Legacy record changed since archive; manual review required');
            $last=$id;$n++;
        }
        $rows->free();
        } while ($batch===500);
        $counts[$table]=$n;
    }
    return $counts;
}
function vee_legacy_page($userId,$input): array
{
    global $conn;
    $page=max(1,(int)($input['pageNo']??1));$size=20;$offset=($page-1)*$size;
    $s=$conn->prepare('SELECT COUNT(*) FROM veegame_legacy_archive WHERE user_id=?');$s->bind_param('i',$userId);$s->execute();$s->bind_result($count);$s->fetch();$s->close();
    $s=$conn->prepare('SELECT source_table,source_id,raw_record FROM veegame_legacy_archive WHERE user_id=? ORDER BY archived_at DESC,source_table,source_id DESC LIMIT ? OFFSET ?');
    $s->bind_param('iii',$userId,$size,$offset);$s->execute();$rows=app_stmt_result($s);$list=[];
    while ($r=$rows->fetch_assoc()) {
        $old=json_decode($r['raw_record'],true);
        $list[]=['sourceTable'=>$r['source_table'],'sourceId'=>(string)$r['source_id'],'issueNumber'=>(string)($old['kalaparichaya']??''),
            'selection'=>(string)($old['ojana']??''),'stake'=>(string)($old['ketebida']??''),'recordedTime'=>(string)($old['tiarikala']??''),
            'result'=>$old['ergebnis']??null,'legacyStatus'=>(string)($old['phalaphala']??''),'recordedAmount'=>(string)($old['sesabida']??'')];
    }
    $s->close();return ['list'=>$list,'pageNo'=>$page,'totalPage'=>(int)ceil($count/$size),'totalCount'=>(int)$count];
}
