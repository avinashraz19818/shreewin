<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
include 'conn.php';

$type = (string)($_GET['type'] ?? '');
$period = trim((string)($_GET['periodid'] ?? ''));
$filter = strtolower(trim((string)($_GET['betfilter'] ?? '')));
$gameMap = array(
    'wingo30'=>'WinGo_30S','wingo1'=>'WinGo_1M','wingo3'=>'WinGo_3M','wingo5'=>'WinGo_5M',
    'k31'=>'K3_1M','k33'=>'K3_3M','k35'=>'K3_5M','k310'=>'K3_10M',
    '5d1'=>'D5_1M','5d3'=>'D5_3M','5d5'=>'D5_5M','5d10'=>'D5_10M',
    'ktrx'=>'TrxWinGo_1M','ktrx3'=>'TrxWinGo_3M','ktrx5'=>'TrxWinGo_5M','ktrx10'=>'TrxWinGo_10M',
    'motoracing'=>'MotoRace_1M'
);
$draw = (int)($_GET['draw'] ?? 1);
$start = max(0, (int)($_GET['start'] ?? 0));
$length = max(1, min(100, (int)($_GET['length'] ?? 50)));
$out = array('draw'=>$draw,'recordsTotal'=>0,'recordsFiltered'=>0,'data'=>array());

if (!isset($gameMap[$type]) || !preg_match('/^[0-9]{17}$/', $period)) {
    echo json_encode($out);
    exit;
}
if ($filter !== '' && $filter !== 'big' && $filter !== 'small') {
    $filter = '';
}

try {
    $betContent = $filter ? 'BigSmall_' . $filter : '';

    if ($filter) {
        $count = $conn->prepare('SELECT COUNT(*) FROM saas_lottery_bets b LEFT JOIN demo d ON d.balakedara=b.user_id WHERE b.game_code=? AND b.issue_number=? AND LOWER(b.bet_content)=LOWER(?) AND d.balakedara IS NULL');
        $count->bind_param('sss', $gameMap[$type], $period, $betContent);
    } else {
        $count = $conn->prepare('SELECT COUNT(*) FROM saas_lottery_bets b LEFT JOIN demo d ON d.balakedara=b.user_id WHERE b.game_code=? AND b.issue_number=? AND d.balakedara IS NULL');
        $count->bind_param('ss', $gameMap[$type], $period);
    }
    $count->execute();
    $count->bind_result($total);
    $count->fetch();
    $count->close();
    $out['recordsTotal'] = $out['recordsFiltered'] = (int)$total;

    if ($filter) {
        $stmt = $conn->prepare("SELECT b.user_id,b.bet_content,b.stake,COALESCE(s.mobile,''),COALESCE(w.motta,0) FROM saas_lottery_bets b LEFT JOIN shonu_subjects s ON s.id=b.user_id LEFT JOIN shonu_kaichila w ON w.balakedara=b.user_id LEFT JOIN demo d ON d.balakedara=b.user_id WHERE b.game_code=? AND b.issue_number=? AND LOWER(b.bet_content)=LOWER(?) AND d.balakedara IS NULL ORDER BY b.id DESC LIMIT ? OFFSET ?");
        $stmt->bind_param('sssii', $gameMap[$type], $period, $betContent, $length, $start);
    } else {
        $stmt = $conn->prepare("SELECT b.user_id,b.bet_content,b.stake,COALESCE(s.mobile,''),COALESCE(w.motta,0) FROM saas_lottery_bets b LEFT JOIN shonu_subjects s ON s.id=b.user_id LEFT JOIN shonu_kaichila w ON w.balakedara=b.user_id LEFT JOIN demo d ON d.balakedara=b.user_id WHERE b.game_code=? AND b.issue_number=? AND d.balakedara IS NULL ORDER BY b.id DESC LIMIT ? OFFSET ?");
        $stmt->bind_param('ssii', $gameMap[$type], $period, $length, $start);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($res && ($r = $res->fetch_row())) {
        $out['data'][] = $r;
    }
    $stmt->close();
} catch (Throwable $e) {
    error_log('[admin live bets] ' . $e->getMessage());
}

echo json_encode($out, JSON_UNESCAPED_SLASHES);
