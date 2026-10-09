#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
FIX #19 — WIN / LOSS POPUP timer 0 se 3 SECOND PEHLE call ho
============================================================
Target host : maan1win.club9.eu.cc      (maanwin ko BILKUL nahi chhoona)

USER KA BOLA :
   "maan1win me ek chiz kr do timer zero hone se pehle 3 sec pehle
    popup ko call kr de"

KYUN ZAROORI THA
----------------
assets/js/useWinGo3-*.js  ke andar timer har second `processSound(e)` chalta
hai (e = screen par dikh raha countdown).  Usme popup ki chain yahan se
shuru hoti thi:

    Z = async e => {
        if (m.value=="1" && ( e<=5&&e>0 ? U(1) : e==0&&U(2) ),
            e==5 && T(),
            e==1) {                       // <-- sirf countdown 1 par
              await $e(800);              // 800 ms  (sleep)
              const a = await we(p.value);// history fetch
              setTimeout(...historyIssues..., 800);   // + 800 ms
              setTimeout(async()=>{ await Te() }, 2200);  // + 2200 ms -> POPUP
        }
    }

Chain ki apni delay = 800 + 2200 = 3000 ms = 3 second.
Chain countdown 1 par shuru hoti thi  =>  popup screen timer 0 ke ~2 second
BAAD aata tha.

FIX:  chain ko countdown 3 par shuru karo  =>  popup screen timer 0 par.
(Yahi number user ne bola tha: 3 second.)

3 BADLAV (sirf frontend, koi paisa / settlement nahi chhera)
------------------------------------------------------------
1) `function Ke(){` ke turant baad  `const PLD=3;`  aur trigger
   `e==1`  ->  `e==PLD`.
   Isse popup ki poora chain 3 second pehle chalu hoti hai.

2) popup kholne se THEEK PAHLE history dobara mangwao:
   `setTimeout(async()=>{await Te()},2200)`
   ->
   `setTimeout(async()=>{try{await N()}catch{}await Te()},2200)`
   Kyun: popup `result:i` historyIssues se dhoondhta hai.  Purane time par
   (countdown 1) fetch countdown ~0 par hoti thi, jab server current period
   ki row de deta tha (LE_REVEAL_AT_COUNTDOWN=1).  Ab chain 3 second pehle
   shuru hai, isliye agar purani list use hoti to popup ko result ka row
   NAHI milta.  Te() se pehle `N()` (getHistoryIssues) chalane se list fresh
   aa jati hai aur popup ko uska result row mil jata hai.

3) Te() me RETRY:  agar `status === null` (result abhi ready nahi) to turant
   `c.delete(a)` kar ke popup HAMESHA KE LIYE drop kar diya jata tha.
   Ab 3 baar try karta hai (1.2 second ke gap se).  Isse popup kabhi
   chhut-ta nahi, chahe result thoda late mile.

SERVER SIDE KOI BADLAV NAHI:
  * koi naya settlement nahi, koi result shift nahi, DB unchanged.
  * LE_REVEAL_AT_COUNTDOWN = 1 hi rehta hai => trend list me result 3 second
    pehle nahi dikhta, sirf POPUP 3 second pehle call hota hai.

EXPECTED (live):
  maandiag.php  [16]  PLD = 3 second , trigger = countdown == PLD (NAYA),
                      refresh = YES, retry = YES, THEEK HAI? = YES
"""

import os
import re
import shutil
import subprocess
import sys

SRC_JS = '/home/user/shreewin/assets/js'          # mirrored live tree
DST    = '/home/user/maanfix2'                    # build dir
DST_JS = os.path.join(DST, 'assets', 'js')

JS_FILES = [
    'useWinGo3-BS04Dx-2.js',   # <- maan1win ki LIVE build (index-_oBi1P6F.js import karti hai)
    'useWinGo3-CMH10SZw.js',   # <- doosri build (ho to bhi theek ho jaye)
]

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


def sub_once(src, old, new, label):
    n = src.count(old)
    if n != 1:
        mark(False, '%s  (expected 1 occurrence, found %d)' % (label, n))
        return src, False
    return src.replace(old, new, 1), True


def node_check(path):
    """ESM syntax check — node --check needs a .mjs extension."""
    tmp = '/tmp/_fix19_check.mjs'
    shutil.copyfile(path, tmp)
    try:
        r = subprocess.run(['node', '--check', tmp],
                           capture_output=True, text=True, timeout=120)
        return r.returncode == 0, (r.stderr or r.stdout or '').strip()
    except Exception as e:                                   # pragma: no cover
        return False, str(e)


# --------------------------------------------------------------------------
print('=' * 74)
print('FIX #19 — popup 3 second pehle   (maan1win only)')
print('=' * 74)

os.makedirs(DST_JS, exist_ok=True)

# ---------------------------------------------------------------- 1. JS files
for name in JS_FILES:
    print('\n--- %s ---' % name)
    src_path = os.path.join(SRC_JS, name)
    dst_path = os.path.join(DST_JS, name)

    if not os.path.isfile(dst_path):
        if os.path.isfile(src_path):
            shutil.copyfile(src_path, dst_path)
            print('  copied from mirror')
        else:
            mark(False, 'source %s nahi mila' % src_path)
            continue

    src = open(dst_path, 'r', encoding='utf-8').read()
    orig = src

    # (a) lead constant
    src, g1 = sub_once(src,
                       'function Ke(){',
                       'function Ke(){const PLD=3;',
                       'PLD constant add')

    # (b) trigger: countdown 1 -> countdown PLD (3)
    src, g2 = sub_once(src,
                       'e==5&&T(),e==1){await',
                       'e==5&&T(),e==PLD){await',
                       'trigger e==1 -> e==PLD')

    # (c) popup kholne se pehle fresh history
    src, g3 = sub_once(src,
                       'setTimeout(async()=>{await Te()},2200)',
                       'setTimeout(async()=>{try{await N()}catch{}await Te()},2200)',
                       'Te() se pehle N() refresh')

    # (d) status null par retry (popup drop na ho)
    old_status = ('const{result:s,data:l}=await ke({issueNumber:a});'
                  'if(!s)return;if(l.status===null){c.delete(a);return}')
    new_status = ('let s=!1,l=null;'
                  'for(let qN=0;qN<3;qN++){'
                  'if(qN)await $e(1200);'
                  'const qR=await ke({issueNumber:a});'
                  'if(!qR.result)return;'
                  's=qR.result,l=qR.data;'
                  'if(l&&l.status!==null)break}'
                  'if(!l||l.status===null){c.delete(a);return}')
    src, g4 = sub_once(src, old_status, new_status, 'status null par 3 retry')

    if not (g1 and g2 and g3 and g4):
        print('  SKIP write (upar koi edit fail hua)')
        continue

    if src == orig:
        mark(False, 'koi badlav hi nahi hua: ' + name)
        continue

    open(dst_path, 'w', encoding='utf-8').write(src)

    good, msg = node_check(dst_path)
    mark(good, 'node --check  (%s)' % (name if good else msg[:200]))

    # quick assertions
    mark('const PLD=3;' in src, 'PLD=3 present')
    mark('e==PLD){await' in src, 'trigger == PLD')
    mark('try{await N()}catch{}await Te()' in src, 'N() refresh present')
    mark('qN<3;qN++' in src, 'retry loop present')
    mark('e==1){await' not in src, 'purana e==1 trigger hata diya')

# ------------------------------------------------------------ 2. maandiag [16]
print('\n--- maandiag.php [16] POPUP LEAD ---')
diag = os.path.join(DST, 'maandiag.php')
if not os.path.isfile(diag):
    mark(False, 'maandiag.php nahi mila')
else:
    d = open(diag, 'r', encoding='utf-8').read()
    anchor = 'echo "\\n" . $line . "\\n";\necho "END. Is file ko ab delete kar dena.\\n";'

    section = r'''
echo "[16] WIN / LOSS POPUP — timer 0 se kitne second PEHLE call hota hai\n";
echo "    (FIX #19: chain 3 second pehle shuru; chain ki apni delay 3000 ms\n";
echo "     hai, isliye popup screen ke timer 0 par dikhta hai)\n";
$jsDir  = __DIR__ . '/assets/js';
$jsList = @glob($jsDir . '/useWinGo3*.js');
if (!$jsList) {
    echo "    KOI assets/js/useWinGo3*.js NAHI MILA — popup wali JS is docroot me nahi hai.\n";
} else {
    foreach ($jsList as $jf) {
        $src  = @file_get_contents($jf);
        $name = basename($jf);
        if ($src === false || $src === '') { echo "  --- $name --- (padha nahi ja saka)\n"; continue; }
        $lead = null;
        if (preg_match('/const\s+PLD\s*=\s*(\d+)\s*;/', $src, $m1)) $lead = (int)$m1[1];
        $usesLead = (strpos($src, 'e==PLD){await') !== false);
        $oldTrig  = (strpos($src, 'e==1){await') !== false);
        $refresh  = (strpos($src, 'try{await N()}catch{}await Te()') !== false);
        $retry    = (strpos($src, 'qN<3;qN++') !== false);
        echo "  --- $name ---\n";
        echo '      PLD (popup lead)       : ' . ($lead === null ? '<< NAHI MILA (purani file) >>' : $lead . ' second') . "\n";
        echo '      trigger                : ' . ($usesLead ? 'countdown == PLD  (NAYA)' : ($oldTrig ? 'countdown == 1  (PURANA)' : 'pehchaan nahi')) . "\n";
        echo '      popup se pehle refresh : ' . ($refresh ? 'YES (result row pakki)' : 'NO') . "\n";
        echo '      result late to retry   : ' . ($retry ? 'YES (3 tries, 1.2 sec gap)' : 'NO') . "\n";
        echo '      POPUP KAB              : ' . ($lead !== null ? ('chain screen timer ' . $lead . ' par shuru -> popup ~timer 0 par') : 'pata nahi') . "\n";
        echo '      THEEK HAI?             : ' . (($lead === 3 && $usesLead && $refresh && $retry)
                ? "YES  (FIX #19 LAGA HUA HAI)" : "NO  <<< 19fix extract nahi hua ya purani file") . "\n";
    }
}
echo "\n";

'''
    if '[16] WIN / LOSS POPUP' in d:
        mark(True, '[16] pehle se maujood (re-apply skip)')
    elif anchor not in d:
        mark(False, 'maandiag.php me END anchor nahi mila')
    else:
        d = d.replace(anchor, section.lstrip('\n') + anchor, 1)
        open(diag, 'w', encoding='utf-8').write(d)
        mark(True, '[16] POPUP LEAD section add')

print('\n' + '=' * 74)
print('FIX #19  RESULT :  OK = %d   FAIL = %d' % (ok, fail))
print('=' * 74)
sys.exit(0 if fail == 0 else 1)
