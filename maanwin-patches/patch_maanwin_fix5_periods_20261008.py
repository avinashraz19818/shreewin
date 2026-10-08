#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
maanwin ROUND 48b — FIX #5

  "period aur result dusra-dusra aa raha hai"

Asli wajah: history/period list me har row ka issue number
le_issue_by_offset() se nikalta tha. Bade offset (jaise 10) par wo
GALAT period number de deta hai — offset 10 par CURRENT period ka number
aa jata tha, jisse list me period number aur result aapas me match nahi
karte the (…584, 583, 582, 581, 580, 579, 578, 577, 576, 585).

Fix: pehla number le_issue_by_offset() se lo, uske baad har agli row ko
EK period piche le jao (le_issue_prev()). Isse list hamesha lagataar
(consecutive) rahegi — chahe le_issue_by_offset() mein koi bhi quirk ho.

Ye script /home/user/maanfix2 (already 4-fix patched) par chalti hai.
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
        print('  !! MISS (%d/%d) in %s :: %r' % (n, count, p, old[:100]))
        sys.exit(1)
    s = s.replace(old, new, count)
    wr(path, s)
    CHANGED.append(os.path.basename(p))

# ------------------------------------------- 1) engine: le_issue_prev/sequence
rep('api/_core/lottery_engine.php',
"""function le_color_from_number(int $n): string""",
"""// =======================================================================
// FIX #5 — Period history me PERIOD NUMBER aur RESULT ka MILAN
//
//   PURANI PROBLEM:
//     History/period list me har row ka issue number alag-alag
//     le_issue_by_offset($code, $offset) se nikalta tha. Bade offset par
//     (jaise offset 10) wo GALAT number de deta hai — offset 10 par
//     CURRENT period ka number aa jata tha. Natija:
//        ...584, 583, 582, 581, 580, 579, 578, 577, 576, 585   <-- galti
//     Isse list me period number aur uska result aapas me match nahi
//     karte the ("period aur result dusra-dusra").
//
//   FIX:
//     Sirf PEHLA number le_issue_by_offset() se lo, uske baad har agli
//     row ko EK period piche le jao (le_issue_prev). Isse list hamesha
//     lagataar (consecutive) rahegi.
// =======================================================================
function le_issue_prev(string $issue): string
{
    $issue = trim((string)$issue);
    // Format: YYYYMMDD + '1000' + 5-digit counter   (17 digits)
    if (!preg_match('/^(\\d{8})(\\d{4})(\\d{5})$/', $issue, $m)) return '';
    $date = $m[1];
    $mid  = $m[2];
    $tail = (int)$m[3] - 1;
    if ($tail < 0) {
        $tail = 99999;
        $ts = strtotime($date . ' 00:00:00');
        if ($ts !== false) $date = date('Ymd', $ts - 86400);
    }
    return $date . $mid . str_pad((string)$tail, 5, '0', STR_PAD_LEFT);
}

/**
 * $startOffset se shuru kar ke $count LAGATAAR period numbers.
 * Pehla number le_issue_by_offset() se, baaki usse ek-ek piche.
 */
function le_issue_sequence(string $gameCode, int $startOffset, int $count): array
{
    $out = [];
    if ($count <= 0) return $out;
    if ($startOffset < 0) $startOffset = 0;
    $cur = le_issue_by_offset($gameCode, $startOffset);
    if ($cur === '' || $cur === null) return $out;
    $cur = (string)$cur;
    $out[] = $cur;
    for ($i = 1; $i < $count; $i++) {
        $prev = le_issue_prev($cur);
        if ($prev === '') $prev = (string)le_issue_by_offset($gameCode, $startOffset + $i);
        $cur = $prev;
        $out[] = $cur;
    }
    return $out;
}

function le_color_from_number(int $n): string""")

# ------------------------------------------- 2) _draw_router.php: sequence
rep('api/_draw_router.php',
"""    // FIX #3: popup ke liye current period ko pehli row me dikhana.
    $shift = le_history_start_offset($gameCode);
    for ($i = 1; $i <= $pageSize; $i++) {
        $offset = (($pageNo - 1) * $pageSize) + $i - (1 - $shift);
        if ($offset < 0) $offset = 0;
        $issueNum = le_issue_by_offset($gameCode, $offset);
        $r = le_result_for_issue($gameCode, $issueNum, true);
        $list[] = lottery_public_result($gameCode, (string)$issueNum, $r, now_ms() - $offset * le_game_interval($gameCode) * 1000);
    }""",
"""    // FIX #3: popup ke liye current period ko pehli row me dikhana.
    // FIX #5: period number LAGATAAR (consecutive) rakho, taaki period
    //         aur result hamesha aapas me match karein.
    $shift = le_history_start_offset($gameCode);
    $startOffset = (($pageNo - 1) * $pageSize) + $shift;
    if ($startOffset < 0) $startOffset = 0;
    $issues = le_issue_sequence($gameCode, $startOffset, $pageSize);
    for ($i = 0; $i < $pageSize; $i++) {
        $offset = $startOffset + $i;
        $issueNum = isset($issues[$i]) ? (string)$issues[$i] : (string)le_issue_by_offset($gameCode, $offset);
        $r = le_result_for_issue($gameCode, $issueNum, true);
        $list[] = lottery_public_result($gameCode, $issueNum, $r, now_ms() - $offset * le_game_interval($gameCode) * 1000);
    }""")

# ------------------------------------------- 3) _router.php: sequence
rep('api/_router.php',
"""    $list = [];
    // FIX #3: popup ke liye current period ko pehli row me dikhana.
    $shift = le_history_start_offset($code);
    for ($i = 1; $i <= $pageSize; $i++) {
        $offset = (($pageNo - 1) * $pageSize) + $i - (1 - $shift);
        if ($offset < 0) $offset = 0;
        $issue = le_issue_by_offset($code, $offset);
        $r = le_result_for_issue($code, $issue, true);

        $list[] = lottery_public_result($code, $issue, $r, now_ms() - ($offset * le_game_interval($code) * 1000));
    }""",
"""    $list = [];
    // FIX #3: popup ke liye current period ko pehli row me dikhana.
    // FIX #5: period number LAGATAAR (consecutive) rakho, taaki period
    //         aur result hamesha aapas me match karein.
    $shift = le_history_start_offset($code);
    $startOffset = (($pageNo - 1) * $pageSize) + $shift;
    if ($startOffset < 0) $startOffset = 0;
    $issues = le_issue_sequence($code, $startOffset, $pageSize);
    for ($i = 0; $i < $pageSize; $i++) {
        $offset = $startOffset + $i;
        $issue = isset($issues[$i]) ? (string)$issues[$i] : (string)le_issue_by_offset($code, $offset);
        $r = le_result_for_issue($code, $issue, true);

        $list[] = lottery_public_result($code, $issue, $r, now_ms() - ($offset * le_game_interval($code) * 1000));
    }""")

print('OK — patched:', ', '.join(CHANGED))
for p in ['api/_core/lottery_engine.php', 'api/_router.php', 'api/_draw_router.php']:
    print('   %-32s %d bytes' % (p, os.path.getsize(os.path.join(BASE, p))))
