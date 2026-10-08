#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
maanwin FIX #7 — settlement ko "period number" se AZAAD banana
===============================================================
Do alag build ek hi database share kar rahe hain, aur dono ka PERIOD
NUMBER alag hai:

    maanwin.club9.eu.cc  -> WinGo_1M = 20261008100010352
    maan1win.club9.eu.cc -> WinGo_1M = 20261008100057312

Jab purane build ke pending bets naye build ke code se settle honge to
le_issue_is_closed() (jo sirf issue number compare karta hai) unhe
"abhi bhi chal raha hai" samajh sakta hai — khaas kar WinGo_30S par,
jahan purana number (50585) naye number (14617) se BADA hota hai.
Aise bets kabhi settle hi nahi honge -> player ka paisa atka rahega.

FIX:
    le_bet_period_is_closed($gameCode, $createdAt)
        Agar bet ka created_at CURRENT PERIOD KI SHURUAAT se pehle ka hai
        to uska period PAKKA band ho chuka hai — chahe period number kisi
        bhi scheme ka ho.

    Settlement loop me:
        if (!$includeOpenIssue
            && !le_issue_is_closed($g, $issue)
            && !le_bet_period_is_closed($g, $createdAt)) continue;

Ye 100% safe hai: current period me laga hua bet kabhi jaldi settle nahi
hoga (uska created_at >= current period start hota hai).
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

# ------------------------------------------------- 1) new helper in engine
rep('api/_core/lottery_engine.php',
"""function le_settle_pending_bets(string $gameCode = '', string $issueNumber = '', int $userId = 0, string $orderNo = '', bool $includeOpenIssue = false): int""",
"""// =======================================================================
// FIX #7 — settlement ko PERIOD NUMBER se AZAAD banana
//
//   Do alag build ek hi `lottery_bets` table use kar rahe hain aur dono
//   ka period number ALAG hai (jaise WinGo_1M: 10352 vs 57312).
//   le_issue_is_closed() sirf issue NUMBER compare karta hai, isliye
//   doosre build ke bet ko "abhi chal raha hai" samajh sakta hai aur wo
//   bet kabhi settle na ho (player ka paisa atka rahe).
//
//   YE FUNCTION period number ko dekhta hi nahi:
//   bet ka created_at agar CURRENT PERIOD KI SHURUAAT se pehle ka hai to
//   uska period PAKKA band ho chuka hai.
// =======================================================================
function le_bet_period_is_closed(string $gameCode, string $createdAt): bool
{
    $createdAt = trim((string)$createdAt);
    if ($createdAt === '' || $createdAt === '0000-00-00 00:00:00') return false;
    $ts = strtotime($createdAt);
    if ($ts === false || $ts <= 0) return false;
    $interval = le_game_interval($gameCode);
    if ($interval <= 0) return false;
    $currentStart = intdiv(time(), $interval) * $interval;  // current period ki shuruaat
    return $ts < $currentStart;
}

function le_settle_pending_bets(string $gameCode = '', string $issueNumber = '', int $userId = 0, string $orderNo = '', bool $includeOpenIssue = false): int""")

# ------------------------------------------------- 2) settlement loop gate
rep('api/_core/lottery_engine.php',
"""        if (!$includeOpenIssue && !le_issue_is_closed($g, $issue)) continue;""",
"""        // FIX #7 — period number ka koi bhi scheme ho (do alag build ek hi DB
        // share karte hain), created_at current period se pehle ka hai to band.
        if (!$includeOpenIssue
            && !le_issue_is_closed($g, $issue)
            && !le_bet_period_is_closed($g, (string)($r['created_at'] ?? ''))) continue;""")

print('OK — patched:', ', '.join(CHANGED))
for p in ['api/_core/lottery_engine.php']:
    print('   %-32s %d bytes' % (p, os.path.getsize(os.path.join(BASE, p))))
