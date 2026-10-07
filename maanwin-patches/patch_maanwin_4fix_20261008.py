#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
maanwin ROUND 48 — 4 fixes
  1) Bet lagte hi My History me Win / Loss
  2) Bet wala timer 1 second KAM dikhaye (11:55 -> 11:54)
  3) Timer khatam hone par Win / Loss POPUP aaye
  4) TAX (2%) + 30-char ORDER NUMBER  (My History detail me)
Backend-only patch. Re-runnable from a fresh copy of the repo files.
"""
import os, sys, io

BASE = '/home/user/maanfix2'
CHANGED = []

def rd(p):
    with io.open(p, 'r', encoding='utf-8', newline='') as f:
        return f.read()

def wr(p, s):
    with io.open(p, 'w', encoding='utf-8', newline='') as f:
        f.write(s)

def rep(p, old, new, count=1):
    path = os.path.join(BASE, p)
    s = rd(path)
    n = s.count(old)
    if n != count:
        print('  !! MISS (%d/%d) in %s :: %r' % (n, count, p, old[:90]))
        sys.exit(1)
    s = s.replace(old, new, count)
    wr(path, s)
    CHANGED.append(os.path.basename(p))

# ---------------------------------------------------------------- 1) CONFIG
rep('api/_core/config.php',
"""@date_default_timezone_set(APP_TIMEZONE);""",
"""// =========================================================================
// LOTTERY ENGINE — 4 FIXES  (2026-10-08)
// -------------------------------------------------------------------------
// 1) Bet lagte hi My History me Win / Loss aa jaye
//      true  = bet lagte hi result settle ho jata hai (My History me turant
//              "Success / Fail" + amount dikhega)
//      false = purana behaviour (period band hone par settle)
//    (Sirf My History / Win-Loss popup jaldi dikhta hai. Game screen, Trend
//     aur Period history me result period band hone ke BAAD hi dikhega.)
if (!defined('LE_INSTANT_SETTLEMENT'))  define('LE_INSTANT_SETTLEMENT', true);
//
// 2) Bet wale timer me 1 second KAM dikhaye
//      Asli bacha hua time 11:55  ->  screen par 11:54
//      1000 = 1 second kam   |   2000 = 2 second kam   |   0 = asli time
//    (Issue number, settlement aur DB par KOI asar nahi — sirf dikhne wala
//     time badalta hai.)
if (!defined('LE_TIMER_LEAD_MS'))       define('LE_TIMER_LEAD_MS', 1000);
//
// 3) Timer khatam hone par WIN / LOSS POPUP aaye
//      Screen wala countdown is value ke barabar ya chhota hone par current
//      period ka result "Game history / Period history" ki pehli row me aa
//      jata hai — popup ko wahi row chahiye hoti hai, isliye popup tabhi
//      dikhta hai.  Betting 5 second pehle hi band ho jati hai, isliye
//      isse koi result jaldi "dekh kar" bet nahi laga sakta.
//      1 = default (recommended)   |   99 = popup ka ye fix band
if (!defined('LE_REVEAL_AT_COUNTDOWN')) define('LE_REVEAL_AT_COUNTDOWN', 1);
//
// 4) TAX + ORDER NUMBER (My History detail panel)
//      Tax  = bet amount ka 2%  (1000 par 20 rs) — har bet par.
//             Ye sirf record me DIKHAYA jata hai; wallet se alag se nahi
//             kata jata (payout par ka 2% tax alag se lagta hai).
//      Order no = 30 char:  WG + Ymd + His + 7-digit user + 7-digit bet id
//             jaise  WG202610080443175103202703489
//             Prefix: WinGo=WG  TrxWinGo=TW  K3=K3  5D/D5=5D  MotoRace=MR
if (!defined('LE_TAX_PERCENT'))         define('LE_TAX_PERCENT', 2.0);
if (!defined('LE_ORDER_NO'))            define('LE_ORDER_NO', true);
// =========================================================================

@date_default_timezone_set(APP_TIMEZONE);""")

# ------------------------------------------------- 2) TIMER (bootstrap.php)
rep('api/_core/bootstrap.php',
"""    $start = $slot * $interval;
    $end = $start + $interval;
    // Original-style issue: YYYYMMDD1000xxxxx (WinGo screenshots use this shape).""",
"""    $start = $slot * $interval;
    $end = $start + $interval;
    // --- FIX #2: timer 1 second KAM dikhana -------------------------------
    // Asli band hone ka time $end hai. Screen par dikhane ke liye usme se
    // LE_TIMER_LEAD_MS ghata dete hain (11:55 bache -> 11:54 dikhega).
    // Issue number / settlement / DB sab $start aur $end se hi chalte hain,
    // isliye kuch bhi badalta nahi — sirf dikhne wala time badalta hai.
    $timerEnd = $end;
    if (defined('LE_TIMER_LEAD_MS')) {
        $leadSec = (int)floor(((float)LE_TIMER_LEAD_MS) / 1000.0);
        if ($leadSec < 0)  $leadSec = 0;
        if ($leadSec > 10) $leadSec = 10;
        $timerEnd = $end - $leadSec;
        if ($timerEnd < $start) $timerEnd = $start;
    }
    // ----------------------------------------------------------------------
    // Original-style issue: YYYYMMDD1000xxxxx (WinGo screenshots use this shape).""")

rep('api/_core/bootstrap.php',
"""        'startTime' => $start * 1000,
        'endTime' => $end * 1000,""",
"""        'startTime' => $start * 1000,
        'endTime' => $timerEnd * 1000,""")

rep('api/_core/bootstrap.php',
"""        'diif' => 0,
        'countdown' => max(0, $end - $now),
        'remainTime' => max(0, $end - $now),
        'timeRemaining' => max(0, $end - $now),""",
"""        'diif' => 0,
        'countdown' => max(0, $timerEnd - $now),
        'remainTime' => max(0, $timerEnd - $now),
        'timeRemaining' => max(0, $timerEnd - $now),""")

# ------------------------------------- 3) ENGINE: tax / order no / reveal
rep('api/_core/lottery_engine.php',
"""function le_game_interval(string $gameCode): int""",
"""// =======================================================================
// FIX #4 — TAX + ORDER NUMBER helpers
// =======================================================================
function le_tax_percent(): float
{
    if (!defined('LE_TAX_PERCENT')) return 2.0;
    return (float)LE_TAX_PERCENT;
}

/** Bet amount par 2% tax — sirf record me dikhane ke liye. */
function le_stake_tax(float $stake): float
{
    return round(max(0.0, $stake) * le_tax_percent() / 100.0, 2);
}

function le_order_prefix(string $gameCode): string
{
    $family = strtolower(trim((string)explode('_', (string)$gameCode)[0]));
    $map = [
        'wingo' => 'WG', 'trxwingo' => 'TW', 'k3' => 'K3',
        'd5' => '5D', '5d' => '5D', 'motorace' => 'MR', 'moto' => 'MR',
        'videowingo' => 'VW',
    ];
    return $map[$family] ?? 'LT';
}

/** 30 char order number: WG + Ymd + His + 7-digit user + 7-digit bet id. */
function le_order_number(string $gameCode, int $userId, int $betId, int $ts = 0): string
{
    if (defined('LE_ORDER_NO') && !LE_ORDER_NO) return '';
    if ($ts <= 0) $ts = time();
    return le_order_prefix($gameCode)
        . date('Ymd', $ts)
        . date('His', $ts)
        . str_pad((string)max(0, $userId), 7, '0', STR_PAD_LEFT)
        . str_pad((string)max(0, $betId), 7, '0', STR_PAD_LEFT);
}

// =======================================================================
// FIX #3 — WIN / LOSS POPUP
//   Popup tabhi dikhta hai jab bet wala period "Game history" ki PEHLI row
//   me aa jaye (frontend ko popup dikhane ke liye wahi row chahiye hoti hai).
//   Isliye screen wala countdown <= LE_REVEAL_AT_COUNTDOWN hone par current
//   period ko history list me (row 0 par) dikhana shuru kar deta hain.
//   Betting us waqt tak band ho chuki hoti hai (countdown <= 5), isliye
//   koi result dekh kar bet nahi lagaya ja sakta.
// =======================================================================
function le_should_reveal_current(string $gameCode): bool
{
    if (!defined('LE_REVEAL_AT_COUNTDOWN')) return false;
    $threshold = (int)LE_REVEAL_AT_COUNTDOWN;
    if ($threshold < 0) return false;
    $iss = function_exists('lottery_issue') ? lottery_issue($gameCode) : null;
    if (!is_array($iss)) return false;
    return (int)($iss['countdown'] ?? 99) <= $threshold;
}

/** History/period list ka pehla offset: 0 = current period bhi dikhao. */
function le_history_start_offset(string $gameCode): int
{
    return le_should_reveal_current($gameCode) ? 0 : 1;
}

function le_game_interval(string $gameCode): int""")

# ---------------------------------------------- 4) response row: tax fields
rep('api/_core/lottery_engine.php',
"""    return [
        'orderNo' => (string)$r['order_no'],
        'issueNumber' => (string)$r['issue_number'],
        'gameCode' => (string)$r['game_code'],
        'betContent' => (string)$r['bet_content'],
        'amount' => (float)$r['amount'],
        'betMultiple' => (int)$r['bet_multiple'],
        'realAmount' => (float)$r['real_amount'],
        'fee' => (float)$r['fee'],""",
"""    // FIX #4 — My History ki "Tax" row yahi se bharti hai.
    //   real_amount = poora bet amount, fee = us par 2% tax (sirf display).
    //   "After tax amount" = real_amount - fee  (dmfirst/shreewin jaisa).
    $rowAmount = (float)($r['amount'] ?? 0);
    $rowStake  = (float)($r['real_amount'] ?? 0);
    $rowFee    = (float)($r['fee'] ?? 0);
    if ($rowFee <= 0.0 && $rowStake > 0.0) {
        $rowFee = le_stake_tax($rowStake); // purane bets me fee 0 tha
    }
    return [
        'orderNo' => (string)$r['order_no'],
        'orderNumber' => (string)$r['order_no'],
        'issueNumber' => (string)$r['issue_number'],
        'gameCode' => (string)$r['game_code'],
        'betContent' => (string)$r['bet_content'],
        'amount' => $rowAmount,
        'betMultiple' => (int)$r['bet_multiple'],
        'realAmount' => round(max(0.0, $rowStake - $rowFee), 2),
        'fee' => $rowFee,
        'tax' => $rowFee,
        'taxAmount' => $rowFee,
        'taxRate' => le_tax_percent(),
        'serviceCharge' => $rowFee,""")

# ------------------------------------- 5) settlement: open issue + debit fix
rep('api/_core/lottery_engine.php',
"""function le_settle_pending_bets(string $gameCode = '', string $issueNumber = '', int $userId = 0, string $orderNo = ''): int""",
"""function le_settle_pending_bets(string $gameCode = '', string $issueNumber = '', int $userId = 0, string $orderNo = '', bool $includeOpenIssue = false): int""")

rep('api/_core/lottery_engine.php',
"""        if (!le_issue_is_closed($g, $issue)) continue;""",
"""        // FIX #1 — $includeOpenIssue = true hone par ABHI CHAL RAHE period ke
        // bets bhi settle ho jate hain (bet lagte hi Win / Loss).
        // false (default) par purana behaviour: period band hone ke baad hi.
        if (!$includeOpenIssue && !le_issue_is_closed($g, $issue)) continue;""")

rep('api/_core/lottery_engine.php',
"""        $stake = (float)$r['real_amount'];
        $fee = (float)$r['fee'];
        $debit = round($stake + $fee, 2);""",
"""        $stake = (float)$r['real_amount'];
        $fee = (float)$r['fee'];
        // fee ab display-only TAX hai (2%), wallet se alag se nahi kata jata.
        $debit = round($stake, 2);""")

# ---------------------------------------------- 6) history: reveal row 0
rep('api/_router.php',
"""    $list = [];
    for ($i = 1; $i <= $pageSize; $i++) {
        $offset = (($pageNo - 1) * $pageSize) + $i;
        $issue = le_issue_by_offset($code, $offset);""",
"""    $list = [];
    // FIX #3: popup ke liye current period ko pehli row me dikhana.
    $shift = le_history_start_offset($code);
    for ($i = 1; $i <= $pageSize; $i++) {
        $offset = (($pageNo - 1) * $pageSize) + $i - (1 - $shift);
        if ($offset < 0) $offset = 0;
        $issue = le_issue_by_offset($code, $offset);""")

rep('api/_draw_router.php',
"""    $list = [];
    $pageNo = max(1, (int)($_GET['pageNo'] ?? $_GET['page'] ?? 1));
    $set = site_settings();
    $pageSize = max(1, min(10, (int)($set['game_history_page_size'] ?? 10)));
    for ($i = 1; $i <= $pageSize; $i++) {
        $offset = (($pageNo - 1) * $pageSize) + $i;
        $issueNum = le_issue_by_offset($gameCode, $offset);""",
"""    $list = [];
    $pageNo = max(1, (int)($_GET['pageNo'] ?? $_GET['page'] ?? 1));
    $set = site_settings();
    $pageSize = max(1, min(10, (int)($set['game_history_page_size'] ?? 10)));
    // FIX #3: popup ke liye current period ko pehli row me dikhana.
    $shift = le_history_start_offset($gameCode);
    for ($i = 1; $i <= $pageSize; $i++) {
        $offset = (($pageNo - 1) * $pageSize) + $i - (1 - $shift);
        if ($offset < 0) $offset = 0;
        $issueNum = le_issue_by_offset($gameCode, $offset);""")

# -------------------------------------------- 7) win/loss popup amount fix
rep('api/_router.php',
"""$row=bet_row_response($r); $state=(int)$r['state']; $isPending=$state===2; $isWin=$state===1; $winAmount=$isWin?max(0,(float)$r['win_lose_amount']+(float)$r['real_amount']+(float)$r['fee']):0.0;""",
"""$row=bet_row_response($r); $state=(int)$r['state']; $isPending=$state===2; $isWin=$state===1; $winAmount=$isWin?max(0,(float)$r['win_lose_amount']+(float)$r['real_amount']):0.0;""")

# ------------------------------------------------- 8) BET handler: tax+order
rep('api/_router.php',
"""    $debit=round($requested_stake,2); // Total amount to deduct from balance
    $fee=round($debit*((float)$settings['fee_percent'])/100,2);
    $stake=round($debit-$fee,2); // Real amount used for winning calculation
    $state=2; // 2 = pending, result ke baad settle hoga
    $premium='';
    $winLose=0.0;
    $order='LT'.date('ymdHis').random_int(1000,9999);
    $newBalance=(float)$u['balance'] - $debit;""",
"""    $uid=(int)$u['id'];
    $betId=0;
    $debit=round($requested_stake,2); // Total amount to deduct from balance
    // FIX #4 — TAX: bet amount ka 2%. Sirf My History me DIKHAYA jata hai;
    // wallet se alag se nahi kata jata (payout ka 2% tax settlement me lagta hai).
    $fee=le_stake_tax($debit);
    $stake=round($debit,2); // Real amount used for winning calculation
    $state=2; // 2 = pending; niche instant settlement se turant 0/1 ho jayega
    $premium='';
    $winLose=0.0;
    $order='LT'.date('ymdHis').random_int(1000,9999); // placeholder
    $newBalance=(float)$u['balance'] - $debit;""")

rep('api/_router.php',
"""        $uid=(int)$u['id'];
        @$conn->begin_transaction();""",
"""        @$conn->begin_transaction();""")

rep('api/_router.php',
"""        $stmt->bind_param('issssdiddsid',$uid,$order,$game,$issue,$content,$amount,$multiple,$stake,$fee,$premium,$state,$winLose);
        if(!$stmt->execute()){ $err = $stmt->error ?: $conn->error; @$conn->rollback(); api_error('Bet insert failed: '.$err,500,500); }
        $stmt->close();
        """,
"""        $stmt->bind_param('issssdiddsid',$uid,$order,$game,$issue,$content,$amount,$multiple,$stake,$fee,$premium,$state,$winLose);
        if(!$stmt->execute()){ $err = $stmt->error ?: $conn->error; @$conn->rollback(); api_error('Bet insert failed: '.$err,500,500); }
        $stmt->close();
        // FIX #4 — ORDER NUMBER (30 char): WG + Ymd + His + 7-digit user + 7-digit bet id
        $betId=(int)$conn->insert_id;
        $finalOrder=le_order_number($game,$uid,$betId);
        if($finalOrder!=='' && $finalOrder!==$order){
            $stmtO=$conn->prepare('UPDATE lottery_bets SET order_no=? WHERE id=?');
            if($stmtO){
                $stmtO->bind_param('si',$finalOrder,$betId);
                if($stmtO->execute()) $order=$finalOrder;
                $stmtO->close();
            }
        }
        """)

# ------------------------------------------- 9) BET handler: instant settle
rep('api/_router.php',
"""        @$conn->commit();
    }
    api_success(['orderNo'=>$order,'issueNumber'=>$issue,'gameCode'=>$game,'state'=>2,'isPending'=>true,'isWin'=>false,'amount'=>0,'winAmount'=>0,'winLoseAmount'=>0,'balance'=>$newBalance,'msg'=>'Bet accepted. Result ke baad settle hoga.'], 'Success', ['serviceTime'=>now_ms()]);
}""",
"""        @$conn->commit();
    }

    // ------------------------------------------------------------------
    // FIX #1 — BET LAGTE HI WIN / LOSS
    //   Abhi chal rahe period ke is bet ko turant settle kar do, taaki
    //   "My History" kholte hi Success / Fail + amount dikhe.
    //   Amount ka hisaab (stake, payout rate, payout par 2% tax) BILKUL wahi
    //   hai jo period band hone par hota — bas time pehle aa gaya.
    //   Game screen / Trend / Period history par result tabhi dikhega jab
    //   period band hoga (wo sab offsets >= 1 use karte hain).
    // ------------------------------------------------------------------
    $settleError='';
    if(defined('LE_INSTANT_SETTLEMENT') && LE_INSTANT_SETTLEMENT && $conn && $order!==''){
        try{
            le_settle_pending_bets($game,$issue,$uid,$order,true);
        }catch(Throwable $e){
            $settleError=$e->getMessage();
        }
    }

    $state=2; $winLose=0.0;
    if($conn && $betId>0){
        $stmtS=$conn->prepare('SELECT state,win_lose_amount,premium FROM lottery_bets WHERE id=? LIMIT 1');
        if($stmtS){
            $stmtS->bind_param('i',$betId);
            $stmtS->execute();
            $sr=$stmtS->get_result()->fetch_assoc();
            $stmtS->close();
            if($sr){ $state=(int)$sr['state']; $winLose=(float)$sr['win_lose_amount']; }
        }
        $stmtB=$conn->prepare('SELECT balance FROM users WHERE id=? LIMIT 1');
        if($stmtB){
            $stmtB->bind_param('i',$uid);
            $stmtB->execute();
            $br=$stmtB->get_result()->fetch_assoc();
            $stmtB->close();
            if($br) $newBalance=(float)$br['balance'];
        }
    }
    $isWin=$state===1; $isPending=$state===2;
    api_success([
        'orderNo'=>$order,
        'issueNumber'=>$issue,
        'gameCode'=>$game,
        'betId'=>$betId,
        'state'=>$state,
        'isPending'=>$isPending,
        'isWin'=>$isWin,
        'amount'=>$stake,
        'winAmount'=>$isWin?max(0.0,$winLose+$stake):0.0,
        'winLoseAmount'=>$winLose,
        'balance'=>$newBalance,
        'instantSettled'=>!$isPending,
        'settleError'=>$settleError,
        'msg'=>$isPending?'Bet accepted. Result ke baad settle hoga.':($isWin?'Win':'Loss'),
    ], 'Success', ['serviceTime'=>now_ms()]);
}""")

print('OK — patched:', ', '.join(CHANGED))
for p in ['api/_core/config.php','api/_core/bootstrap.php','api/_core/lottery_engine.php','api/_router.php','api/_draw_router.php']:
    print('   %-32s %d bytes' % (p, os.path.getsize(os.path.join(BASE, p))))
