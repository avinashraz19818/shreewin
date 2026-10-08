#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
maanwin FIX #6 — do tarah ke bets ka sahi hisaab (paisa wala fix)
=================================================================
Live diagnostic se pata chala:

    * maan1win.club9.eu.cc  (naya, patched build)
        fee / TAX sirf record me DIKHAYA jata hai (display-only).
        real_amount = POORA bet amount,   debit = stake
        order no = 30 char (WG20261008....)

    * maanwin.club9.eu.cc   (purana build, ISI DB par)
        fee wallet se ALAG se KATA jata tha (asli katoti).
        real_amount = debit - fee,         debit = stake + fee
        order no = 18 char (LT261008....)

Dono ek hi `lottery_bets` table share karte hain. Isliye jab naya code
purane build ke PENDING bet ko settle karta tha to debit = stake ho jata
tha (stake + fee ke bajaye) -> player ko 2% ZYADA mil jate the.

FIX:
    le_is_display_only_tax($orderNo, $stake, $fee)
        -> true  : naya model (fee display-only, debit = stake)
        -> false : purana model (fee asli katoti, debit = stake + fee)

    le_settle_debit(...)      -> settlement me use hota hai
    le_response_row()         -> My History me realAmount / tax sahi dikhata hai

Detection:
    1) order no >= 28 char            => naya model (30-char WG...)
    2) fee == round((stake+fee) * pct / 100)  => purana model
    3) fee == round(stake * pct / 100)        => naya model
    4) fee == 0                              => dono me debit = stake
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
        print('  !! MISS (%d/%d) in %s :: %r' % (n, count, p, old[:110]))
        sys.exit(1)
    s = s.replace(old, new, count)
    wr(path, s)
    CHANGED.append(os.path.basename(p))

# ------------------------------------------------- 1) helpers in engine
rep('api/_core/lottery_engine.php',
"""function le_order_prefix(string $gameCode): string""",
"""// =======================================================================
// FIX #6 — DO TARAH KE BETS KA HISAAB  (paisa wala fix)
//
//   Ek hi database par do build chal rahe hain:
//
//     A) NAYA build (30-char order no, jaise WG2026100810195403193280000098)
//        fee / TAX sirf record me DIKHAYA jata hai — wallet se alag se
//        nahi kata jata.
//            real_amount = POORA bet amount
//            debit       = stake
//
//     B) PURANA build (18-char order no, jaise LT2610081038103803)
//        fee wallet se ALAG se KATA jata tha (asli katoti).
//            real_amount = debit - fee
//            debit       = stake + fee
//
//   Agar naya code purane build ke PENDING bet ko `debit = stake` se
//   settle kare to player ko 2% ZYADA mil jayenge. Yahi fix rokta hai.
// =======================================================================
function le_is_display_only_tax(string $orderNo, float $stake, float $fee): bool
{
    $orderNo = trim((string)$orderNo);
    if ($fee <= 0.0) return true;                 // koi fee nahi => dono model barabar
    if (strlen($orderNo) >= 28) return true;      // naya 30-char order no
    $pct = le_tax_percent();
    if ($pct <= 0.0) return true;
    // Purane model me fee = DEBIT ka 2% hota tha (debit = stake + fee).
    if (abs($fee - round(($stake + $fee) * $pct / 100.0, 2)) < 0.011) return false;
    // Naye model me fee = STAKE ka 2% hota hai.
    if (abs($fee - round($stake * $pct / 100.0, 2)) < 0.011) return true;
    return false;
}

/** Settlement me wallet se kitna kat / joda gaya — dono model ke liye sahi. */
function le_settle_debit(string $orderNo, float $stake, float $fee): float
{
    if (le_is_display_only_tax($orderNo, $stake, $fee)) return round($stake, 2);
    return round($stake + $fee, 2);
}

function le_order_prefix(string $gameCode): string""")

# ------------------------------------------------- 2) settlement debit
rep('api/_core/lottery_engine.php',
"""        $stake = (float)$r['real_amount'];
        $fee = (float)$r['fee'];
        // fee ab display-only TAX hai (2%), wallet se alag se nahi kata jata.
        $debit = round($stake, 2);""",
"""        $stake = (float)$r['real_amount'];
        $fee = (float)$r['fee'];
        // FIX #6 — naye bets me fee (TAX) sirf display hai (debit = stake),
        // purane build ke bets me fee alag se kata gaya tha (debit = stake+fee).
        $debit = le_settle_debit((string)($r['order_no'] ?? ''), $stake, $fee);""")

# ------------------------------------------------- 3) response row display
rep('api/_core/lottery_engine.php',
"""    $rowAmount = (float)($r['amount'] ?? 0);
    $rowStake  = (float)($r['real_amount'] ?? 0);
    $rowFee    = (float)($r['fee'] ?? 0);
    if ($rowFee <= 0.0 && $rowStake > 0.0) {
        $rowFee = le_stake_tax($rowStake); // purane bets me fee 0 tha
    }""",
"""    $rowAmount = (float)($r['amount'] ?? 0);
    $rowStake  = (float)($r['real_amount'] ?? 0);
    $rowFee    = (float)($r['fee'] ?? 0);
    $rowOrder  = (string)($r['order_no'] ?? '');
    // Naye (30-char order no) bets me fee hamesha 2% hota hai.
    // Purane bets me jo fee store hai wahi dikhao (do-baar mat ghatao).
    if ($rowFee <= 0.0 && $rowStake > 0.0 && strlen(trim($rowOrder)) >= 28) {
        $rowFee = le_stake_tax($rowStake);
    }
    // FIX #6 — purane model me real_amount me tax pehle hi ghata hota hai.
    $rowReal  = le_is_display_only_tax($rowOrder, $rowStake, $rowFee)
        ? round(max(0.0, $rowStake - $rowFee), 2)
        : round(max(0.0, $rowStake), 2);""")

rep('api/_core/lottery_engine.php',
"""        'realAmount' => round(max(0.0, $rowStake - $rowFee), 2),""",
"""        'realAmount' => $rowReal,""")

print('OK — patched:', ', '.join(CHANGED))
for p in ['api/_core/lottery_engine.php']:
    print('   %-32s %d bytes' % (p, os.path.getsize(os.path.join(BASE, p))))
