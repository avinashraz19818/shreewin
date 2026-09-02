<?php
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
include 'conn.php';
$type=(string)($_GET['type']??''); $period=trim((string)($_GET['periodid']??''));
$gameMap=array('wingo30'=>'WinGo_30S','wingo1'=>'WinGo_1M','wingo3'=>'WinGo_3M','wingo5'=>'WinGo_5M','k31'=>'K3_1M','k33'=>'K3_3M','k35'=>'K3_5M','k310'=>'K3_10M','5d1'=>'D5_1M','5d3'=>'D5_3M','5d5'=>'D5_5M','5d10'=>'D5_10M','ktrx'=>'TrxWinGo_1M','ktrx3'=>'TrxWinGo_3M','ktrx5'=>'TrxWinGo_5M','ktrx10'=>'TrxWinGo_10M','motoracing'=>'MotoRace_1M');
if(!isset($gameMap[$type])||!preg_match('/^[0-9]{17}$/',$period)){http_response_code(400);echo '0.00';exit;}
try{$stmt=$conn->prepare('SELECT COALESCE(SUM(b.stake),0) FROM saas_lottery_bets b LEFT JOIN demo d ON d.balakedara=b.user_id WHERE b.game_code=? AND b.issue_number=? AND d.balakedara IS NULL');if(!$stmt)throw new RuntimeException('query unavailable');$stmt->bind_param('ss',$gameMap[$type],$period);$stmt->execute();$stmt->bind_result($total);$stmt->fetch();$stmt->close();echo number_format((float)$total,2,'.','');}catch(Throwable $e){error_log('[admin total bet] '.$e->getMessage());echo '0.00';}
