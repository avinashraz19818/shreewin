#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
FIX #20 — WIN / LOSS POPUP THEEK TIMER 0 PAR  (maan1win only)
=============================================================
User: "abhi v delay se aa rha h jabki result to pehle se declare ho rha h
       ... 0 timer hone pe dikha hi skte h ... 0 ke 4-5 sec baad show krta h"

LIVE MEASUREMENT (maandiag.php, 11:25 IST)
------------------------------------------
  [16] THEEK HAI? : YES        -> 19fix sahi laga hai, chain screen timer 3
                                  par shuru hoti hai.
  [6]  id=247 ... state=2      -> bet ABHI BHI PENDING.  Period band hone ke
                                  baad hi settle hota hai.

ISLIYE POPUP LATE:
  Te() (popup)  /Lottery/GetWinLossResult  poochhta hai. Period abhi chal
  raha hai to bet settle nahi hota -> `status: null` -> Te retry karta hai
  (1.2s, 2.4s) -> popup timer 0 ke ~4-5 second BAAD.

ASLI BAAT (user ne khud kahi: "result to pehle se declare ho rha h")
  draw.ar-lottery01.com apni row0 me ABHI CHAL RAHE period ka result pehle
  se publish kar deta hai. maandiag [13] me saaf dikh raha hai:
      API row0 (abhi chal raha) : 20261009100050711   result=6
  Matlab period band hone se KAYI SECOND pehle hi ASLI result hamare paas
  hota hai. To bet wahin settle kiya ja sakta hai.

FIX (#20) — 2 file, sirf server side
------------------------------------
1) api/_core/lottery_engine.php  -> naya function le_upstream_result_ready()
       "is period ka ASLI API result abhi available hai?"  (DB / random ko
       kabhi 'ready' nahi maante)

2) api/_router.php -> handle_win_loss() me:
       agar bet pending (state=2) ho AUR period abhi chal raha ho (ya ek
       pichla) AUR API ne us period ka asli result de diya ho
       ->  le_settle_pending_bets(..., includeOpenIssue = true)
       ->  row dobara padho -> ab status: null nahi, turant win/loss.

   SURAKSHA:
     * sirf CURRENT / PREVIOUS period  (purane bet normal tarike se)
     * sirf ASLI API result aane par  (andaze ka result KABHI nahi)
     * result wahi hai jo period band hone par lagta -> paisa/result same

NATEJA:  GetWinLossResult timer 0 se pehle hi status bhej deta hai.
         19fix ki chain (800+2200 = 3000 ms) screen timer 3 par shuru hoti
         hai  =>  POPUP THEEK TIMER 0 PAR.

VERIFY:  maandiag.php [17]
"""

import io
import os
import sys

DST = '/home/user/maanfix2'

ok = fail = 0


def mark(good, what):
    global ok, fail
    if good:
        ok += 1
        print('  OK   : ' + what)
    else:
        fail += 1
        print('  FAIL : ' + what)
    return good


def sub_once(path, old, new, label):
    global ok, fail
    if not os.path.isfile(path):
        mark(False, 'file nahi mili: ' + path)
        return
    s = io.open(path, 'r', encoding='utf-8').read()
    n = s.count(old)
    if n != 1:
        mark(False, '%s (expected 1, found %d)' % (label, n))
        return
    s = s.replace(old, new, 1)
    io.open(path, 'w', encoding='utf-8').write(s)
    mark(True, label)


print('=' * 74)
print('FIX #20 — popup theek timer 0 par   (maan1win only)')
print('=' * 74)

# ------------------------------------------------- 1) lottery_engine.php
print('\n--- api/_core/lottery_engine.php : le_upstream_result_ready() ---')
LE = os.path.join(DST, 'api', '_core', 'lottery_engine.php')
anchor_le = 'function le_upstream_result_for(string $gameCode, string $issueNumber): string'

new_fn = r'''// =======================================================================
// FIX #20 — WIN / LOSS POPUP THEEK TIMER 0 PAR
//
//   draw.ar-lottery01.com apni pehli row me ABHI CHAL RAHE period ka
//   result PEHLE SE publish kar deta hai (maandiag [13] me dikh raha hai:
//   "API row0 (abhi chal raha) : ... result=6").  Matlab period band hone
//   se kayi second pehle hi ASLI result hamare paas hota hai.
//
//   Phir bhi bet tabhi settle hota tha jab period BAND ho jata tha, isliye
//   /Lottery/GetWinLossResult timer 0 ke 3-5 second baad tak `status:null`
//   bhejta raha -> popup late dikhta tha.
//
//   Ye function bas yahi poochhta hai:
//       "is period ka ASLI API result abhi available hai?"
//   Haan  -> bet wahin settle kar sakte hain -> popup TIMER 0 par.
//   Nahi  -> purana tarika (period band hone par settle).
//
//   SURAKSHA: DB me pada hua ya RANDOM result kabhi 'ready' nahi mana jata.
// =======================================================================
function le_upstream_result_ready(string $gameCode, string $issueNumber): bool
{
    $gameCode = trim((string)$gameCode);
    $issueNumber = trim((string)$issueNumber);
    if ($gameCode === '' || $issueNumber === '') return false;
    if (!function_exists('le_upstream_enabled') || !le_upstream_enabled()) return false;
    if (!function_exists('le_upstream_result_for')) return false;
    $premium = le_upstream_result_for($gameCode, $issueNumber);
    return is_string($premium) && $premium !== '';
}

'''

if os.path.isfile(LE):
    s = io.open(LE, 'r', encoding='utf-8').read()
    if 'function le_upstream_result_ready' in s:
        mark(True, 'le_upstream_result_ready() pehle se maujood (skip)')
    elif s.count(anchor_le) != 1:
        mark(False, 'anchor le_upstream_result_for nahi mila')
    else:
        s = s.replace(anchor_le, new_fn + anchor_le, 1)
        io.open(LE, 'w', encoding='utf-8').write(s)
        mark(True, 'le_upstream_result_ready() add')
else:
    mark(False, 'lottery_engine.php nahi mila')

# ------------------------------------------------------- 2) _router.php
print('\n--- api/_router.php : handle_win_loss() early settle ---')
RT = os.path.join(DST, 'api', '_router.php')
anchor_rt = ("    if(!$r) api_success(['status'=>null,'isWin'=>false,'isPending'=>false,"
             "'amount'=>0], 'Success', ['serviceTime'=>now_ms()]);")

new_rt = r'''    if(!$r) api_success(['status'=>null,'isWin'=>false,'isPending'=>false,'amount'=>0], 'Success', ['serviceTime'=>now_ms()]);

    // ------------------------------------------------------------------
    // FIX #20 — WIN / LOSS POPUP THEEK TIMER 0 PAR AAYE
    //   App popup ki chain screen timer 3 par shuru karta hai (FIX #19) aur
    //   chain ki apni delay 3000 ms hai, to Te() (popup) theek timer 0 par
    //   chalta hai.  Lekin bet tabhi settle hota tha jab period BAND ho jata
    //   tha -> yahan `status: null` milta tha -> popup 4-5 second late.
    //
    //   API result period SHURU hote hi publish kar deti hai, to jaise hi
    //   US period ka ASLI result haath lage, bet wahin settle kar deta hain.
    //   Result BILKUL wahi hai jo period band hone par lagta (API wala),
    //   isliye paisa aur result dono waise hi rahte hain.
    //
    //   SIRF TAB:
    //     * bet pending ho (state = 2)
    //     * period ABHI CHAL RAHA ho ya usse EK PICHLA ho
    //     * API ne us period ka ASLI result de diya ho
    //   Andaze / DB / random result par KABHI settle nahi karte.
    // ------------------------------------------------------------------
    if ((int)$r['state'] === 2 && function_exists('le_upstream_result_ready')) {
        $fxGame  = (string)($r['game_code'] ?? '');
        $fxIssue = (string)($r['issue_number'] ?? '');
        if ($fxGame !== '' && $fxIssue !== '') {
            $fxCur   = function_exists('lottery_issue') ? lottery_issue($fxGame) : null;
            $fxCurN  = is_array($fxCur) ? (string)($fxCur['issueNumber'] ?? '') : '';
            $fxPrevN = ($fxCurN !== '' && function_exists('le_issue_prev_game'))
                ? (string)le_issue_prev_game($fxCurN, $fxGame) : '';
            if ($fxIssue === $fxCurN || ($fxPrevN !== '' && $fxIssue === $fxPrevN)) {
                if (le_upstream_result_ready($fxGame, $fxIssue)) {
                    le_settle_pending_bets($fxGame, $fxIssue, $uid, '', true);
                    $fxStmt = $conn->prepare('SELECT * FROM lottery_bets WHERE id=? LIMIT 1');
                    if ($fxStmt) {
                        $fxId = (int)$r['id'];
                        $fxStmt->bind_param('i', $fxId);
                        $fxStmt->execute();
                        $fxRow = $fxStmt->get_result()->fetch_assoc();
                        if ($fxRow) $r = $fxRow;
                    }
                }
            }
        }
    }
'''

if os.path.isfile(RT):
    s = io.open(RT, 'r', encoding='utf-8').read()
    if 'FIX #20 — WIN / LOSS POPUP THEEK TIMER 0 PAR AAYE' in s:
        mark(True, 'handle_win_loss FIX #20 pehle se maujood (skip)')
    elif s.count(anchor_rt) != 1:
        mark(False, 'anchor handle_win_loss if(!$r) nahi mila (count=%d)' % s.count(anchor_rt))
    else:
        s = s.replace(anchor_rt, new_rt, 1)
        io.open(RT, 'w', encoding='utf-8').write(s)
        mark(True, 'handle_win_loss me early-settle lagaya')
else:
    mark(False, '_router.php nahi mila')

# --------------------------------------------------------- 3) maandiag [17]
print('\n--- maandiag.php [17] ---')
DIAG = os.path.join(DST, 'maandiag.php')
anchor_diag = 'echo "\\n" . $line . "\\n";\necho "END. Is file ko ab delete kar dena.\\n";'

section = r'''
echo "[17] POPUP THEEK TIMER 0 PAR — kya bet period BAND hone se PEHLE settle ho sakta hai?\n";
echo "    (FIX #20: API result period shuru hote hi publish kar deti hai. Jaise\n";
echo "     hi ASLI result haath lage, bet wahin settle ho jata hai — to\n";
echo "     GetWinLossResult timer 0 par hi status bhejta hai aur popup 4-5\n";
echo "     second BAAD nahi, THEEK TIMER 0 PAR dikhta hai.)\n";
echo '    le_upstream_result_ready() : ' . (function_exists('le_upstream_result_ready') ? 'present  (FIX #20 LAGA HAI)' : '<< MISSING — 20fix extract nahi hua >>') . "\n";
echo "    ABHI CHAL RAHE PERIOD ka API result\n";
foreach (['WinGo_30S', 'WinGo_1M', 'WinGo_3M', 'WinGo_5M'] as $g) {
    $i = function_exists('lottery_issue') ? lottery_issue($g) : null;
    $cur = is_array($i) ? (string)($i['issueNumber'] ?? '') : '';
    echo "  --- $g ---\n";
    if ($cur === '') { echo "      period nahi mila\n"; continue; }
    echo '      abhi chal raha period : ' . $cur . "\n";
    $p = function_exists('le_upstream_result_for') ? le_upstream_result_for($g, $cur) : '';
    echo '      API ka ASLI result    : ' . ($p === '' ? '<< ABHI NAHI MILA >>' : $p) . "\n";
    echo '      result ready?         : ' . ($p !== '' ? 'YES' : 'NO') . "\n";
    echo '      => POPUP              : ' . ($p !== ''
        ? 'TIMER 0 PAR  (bet wahin settle hoga)'
        : 'thoda late (result aate hi settle)') . "\n";
}
$cnx = function_exists('db') ? db() : null;
if ($cnx) {
    $rsx = @$cnx->query('SELECT id, game_code, issue_number, created_at FROM lottery_bets WHERE state=2 ORDER BY id DESC LIMIT 3');
    if ($rsx && $rsx->num_rows) {
        echo "    PENDING BET (state=2) — kya ABHI settle ho sakte hain?\n";
        while ($b = $rsx->fetch_assoc()) {
            $bg = (string)($b['game_code'] ?? '');
            $bi = (string)($b['issue_number'] ?? '');
            $bi2 = function_exists('lottery_issue') ? lottery_issue($bg) : null;
            $bc = is_array($bi2) ? (string)($bi2['issueNumber'] ?? '') : '';
            $bp = ($bc !== '' && function_exists('le_issue_prev_game')) ? (string)le_issue_prev_game($bc, $bg) : '';
            $inWin = ($bi === $bc || ($bp !== '' && $bi === $bp));
            $bp2 = function_exists('le_upstream_result_for') ? le_upstream_result_for($bg, $bi) : '';
            echo '      id=' . (int)$b['id'] . '  ' . $bg . '  issue=' . $bi . "\n";
            echo '          period abhi chal raha? : ' . ($inWin ? 'YES (ya ek pichla)' : 'NO (purana — normal tarike se settle hoga)') . "\n";
            echo '          API ka ASLI result     : ' . ($bp2 === '' ? '<< NAHI MILA >>' : $bp2) . "\n";
            echo '          ABHI SETTLE HOGA?      : ' . (($inWin && $bp2 !== '')
                ? 'YES  -> POPUP TIMER 0 PAR'
                : 'NO   (period band hone par settle)') . "\n";
        }
    } else {
        echo "    Koi PENDING (state=2) bet abhi nahi hai — sab settle hain.\n";
    }
}
echo "\n";

'''

if os.path.isfile(DIAG):
    s = io.open(DIAG, 'r', encoding='utf-8').read()
    if '[17] POPUP THEEK TIMER 0 PAR' in s:
        mark(True, '[17] pehle se maujood (skip)')
    elif s.count(anchor_diag) != 1:
        mark(False, 'maandiag END anchor nahi mila')
    else:
        s = s.replace(anchor_diag, section.lstrip('\n') + anchor_diag, 1)
        io.open(DIAG, 'w', encoding='utf-8').write(s)
        mark(True, '[17] POPUP AT TIMER 0 section add')
else:
    mark(False, 'maandiag.php nahi mila')

print('\n' + '=' * 74)
print('FIX #20  RESULT :  OK = %d   FAIL = %d' % (ok, fail))
print('=' * 74)
sys.exit(0 if fail == 0 else 1)
