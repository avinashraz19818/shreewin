#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
FIX #17 — "period aur result apne aap badal jata hai" KI ASLI WAJAH

User: "jis time fix karte ho sahi rahata hai, FIR bad mein apne aap
       change ho jata hai"

DO JAGAH THI (dono FIX #16 ke 15-second cache se paida hui):

 (1) DRIFT — le_issue_drift() "sabse nayi upstream row" CACHE se padhta
     tha. Cache 15 second purana hone par wo PICHLE period ki row mil
     jati thi:
         upTail = N-1,  locTail = N   ->  drift = -1
     Site ka period number apne aap EK PICHE chala jata tha, aur DRIFT
     CACHE 120 SECOND ka hai — to poore 2 minute tak galat number atka
     rehta tha. Yahi "period apne aap badal gaya".

 (2) RESULT — naya period shuru hote hi uski row cached rows me nahi
     hoti (cache 15 second purana). Tab le_result_for_issue() RANDOM
     result bana kar DB me likh deta tha. Baad me jab asli API result
     aata to FIX #13 ka "repair" use badal deta tha ->
     "result apne aap badal gaya".

FIX:
 * le_upstream_get() me naya $force flag: cache ko PADHNA skip karo,
   par LIKHNA (refresh) zaroor. Isse ek hi HTTP call me data bhi milta
   hai aur disk cache bhi fresh ho jata hai (sab requests ke liye).
 * le_upstream_page1_fresh() — page 1 force fetch, ek request me ek baar.
 * le_upstream_result_for() — cached rows me na mile aur period "naya"
   ho (newest-2 .. newest+40) to page 1 FRESH mangwao. Ab random result
   lagbhag kabhi nahi banega.
 * le_issue_drift() — hamesha FRESH data use karo (har 2 minute me ek
   HTTP call). Slot ko fetch se PAHLE aur BAAD dono tarah se milao;
   beech me period badal gaya ho to 0 hi lo.
"""
import io, os, sys

ROOT = "/home/user/maanfix2"
ENG = os.path.join(ROOT, "api/_core/lottery_engine.php")
DIAG = os.path.join(ROOT, "maandiag.php")

ok = 0
fail = []


def rep(path, old, new, count=1, tag=""):
    global ok
    with io.open(path, "r", encoding="utf-8") as f:
        s = f.read()
    n = s.count(old)
    if n != count:
        fail.append("%s  (%s): expected %d, found %d" % (tag, os.path.basename(path), count, n))
        return
    s = s.replace(old, new, count)
    with io.open(path, "w", encoding="utf-8") as f:
        f.write(s)
    ok += 1
    print("  OK  %-58s  (%s)" % (tag, os.path.basename(path)))


# ---------------------------------------------------------------- 1
rep(ENG,
"function le_upstream_get(string $path, int $ttl = 0, bool $raw = false): ?array",
"function le_upstream_get(string $path, int $ttl = 0, bool $raw = false, bool $force = false): ?array",
    1, "engine: le_upstream_get() me $force")

rep(ENG, "    if (!$raw && isset($mem[$url])) {",
         "    if (!$raw && !$force && isset($mem[$url])) {", 1, "engine: force -> mem cache skip")
rep(ENG, "    if (!$raw && is_file($file)) {",
         "    if (!$raw && !$force && is_file($file)) {", 1, "engine: force -> file cache skip")
rep(ENG, "    if (!$raw && is_file($ffile)) {",
         "    if (!$raw && !$force && is_file($ffile)) {", 1, "engine: force -> fail marker skip")

# ---------------------------------------------------------------- 2
rep(ENG,
"""function le_upstream_result_for(string $gameCode, string $issueNumber): string
{
    if (!le_upstream_enabled()) return '';
    $issueNumber = trim((string)$issueNumber);
    if ($issueNumber === '') return '';
    if (strlen($issueNumber) < 5) return '';
    $tail = substr($issueNumber, -5);
    foreach (le_upstream_rows($gameCode) as $row) {
        if (!is_array($row)) continue;
        $num = trim((string)($row['issueNumber'] ?? $row['issueNo'] ?? $row['period'] ?? ''));
        if ($num === '') continue;
        if ($num === $issueNumber || substr($num, -5) === $tail) {
            $p = (string)($row['premium'] ?? $row['number'] ?? $row['result'] ?? '');
            if ($p !== '') return $p;
        }
    }
    return '';
}""",
"""/**
 * page 1 BINA CACHE PADHE lao (naya period turant mil jaye).
 * Disk cache bhi refresh ho jata hai, isliye BAAKI sab requests ko bhi
 * taza data milta hai. Ek request me ek game ke liye SIRF EK BAAR.
 */
function le_upstream_page1_fresh(string $gameCode): array
{
    static $cache = [];
    if (!le_upstream_enabled()) return [];
    if (array_key_exists($gameCode, $cache)) return $cache[$gameCode];
    $cache[$gameCode] = [];
    $fam = le_upstream_family($gameCode);
    $j = le_upstream_get('/' . $fam . '/' . rawurlencode($gameCode) . '/GetHistoryIssuePage.json?pageNo=1&pageSize=10', 0, false, true);
    $list = (is_array($j) && isset($j['data']['list']) && is_array($j['data']['list'])) ? $j['data']['list'] : [];
    $cache[$gameCode] = $list;
    return $list;
}

function le_upstream_result_for(string $gameCode, string $issueNumber): string
{
    if (!le_upstream_enabled()) return '';
    $issueNumber = trim((string)$issueNumber);
    if ($issueNumber === '' || strlen($issueNumber) < 5) return '';
    $tail = substr($issueNumber, -5);

    $find = function (array $rows) use ($issueNumber, $tail) {
        foreach ($rows as $row) {
            if (!is_array($row)) continue;
            $num = trim((string)($row['issueNumber'] ?? $row['issueNo'] ?? $row['period'] ?? ''));
            if ($num === '') continue;
            if ($num === $issueNumber || substr($num, -5) === $tail) {
                $p = (string)($row['premium'] ?? $row['number'] ?? $row['result'] ?? '');
                if ($p !== '') return $p;
            }
        }
        return null;
    };

    $rows = le_upstream_rows($gameCode);
    $p = $find($rows);
    if ($p !== null) return $p;

    // FIX #17 — CACHE PURANA HONE PAR NAYA PERIOD NAHI MILTA THA.
    //   Tab RANDOM result ban kar DB me pakka ho jata tha, aur baad me
    //   asli result aane par DB badal jata tha ("result apne aap badal
    //   gaya"). Ab page 1 EK BAAR FRESH mangwate hain -> naya period
    //   turant mil jata hai -> random kabhi nahi banta.
    //   (bahut purane period ke liye bekar ka HTTP call nahi karte)
    $newest = isset($rows[0]) && is_array($rows[0]) ? trim((string)($rows[0]['issueNumber'] ?? '')) : '';
    if ($newest !== '') {
        $dist = ((int)substr($newest, -5)) - ((int)$tail);
        if ($dist > 50000) $dist -= 100000;
        if ($dist < -50000) $dist += 100000;
        if ($dist < -2 || $dist > 40) return '';   // purana period: DB se hi milega
    }
    if (function_exists('le_upstream_page1_fresh')) {
        $fresh = le_upstream_page1_fresh($gameCode);
        if ($fresh) {
            $p = $find($fresh);
            if ($p !== null) return $p;
        }
    }
    return '';
}""",
    1, "engine: result lookup me FRESH retry")

# ---------------------------------------------------------------- 3
rep(ENG,
"""    $row = le_upstream_newest($gameCode);
    if (!is_array($row)) return 0;
    $up = trim((string)($row['issueNumber'] ?? $row['issueNo'] ?? $row['period'] ?? ''));
    if ($up === '') return 0;

    $interval = le_game_interval($gameCode);
    $slot     = intdiv(time(), $interval);
    $n        = ($slot + le_issue_offset($gameCode)) % 100000;
    if ($n < 0) $n += 100000;
    $locTail  = (int)substr(str_pad((string)$n, 5, '0', STR_PAD_LEFT), -5);
    $upTail   = (int)substr($up, -5);

    $d = ($upTail - $locTail) % 100000;
    if ($d < 0) $d += 100000;
    if ($d > 50000) $d -= 100000;
    if ($d > 5 || $d < -5) $d = 0;          // pagal value => formula hi theek hai""",
"""    // FIX #17 — DRIFT KE LIYE HAMESHA TAZA (bina cache) DATA.
    //   Cache purana hone par "sabse nayi row" PICHLE period ki mil jati
    //   thi -> drift = -1 -> site ka period number apne aap EK PICHE
    //   chala jata tha aur DRIFT CACHE 120 SECOND tak wahin atka rehta
    //   tha. YAHI "period apne aap badal jata hai" KI WAJAH THI.
    //   Har 2 minute me ek HTTP call — isse zyada nahi.
    $interval   = le_game_interval($gameCode);
    $slotBefore = intdiv(time(), $interval);
    $fresh = function_exists('le_upstream_page1_fresh') ? le_upstream_page1_fresh($gameCode) : [];
    $row = isset($fresh[0]) && is_array($fresh[0]) ? $fresh[0] : le_upstream_newest($gameCode);
    if (!is_array($row)) return 0;
    $up = trim((string)($row['issueNumber'] ?? $row['issueNo'] ?? $row['period'] ?? ''));
    if ($up === '') return 0;

    $tailOf = function (int $slot) use ($gameCode): int {
        $n = ($slot + le_issue_offset($gameCode)) % 100000;
        if ($n < 0) $n += 100000;
        return (int)substr(str_pad((string)$n, 5, '0', STR_PAD_LEFT), -5);
    };
    $upTail = (int)substr($up, -5);
    $d1 = $upTail - $tailOf($slotBefore);                 // fetch se PAHLE ka slot
    $d2 = $upTail - $tailOf(intdiv(time(), $interval));   // fetch ke BAAD ka slot
    if ($d1 > 50000) $d1 -= 100000;
    if ($d1 < -50000) $d1 += 100000;
    if ($d2 > 50000) $d2 -= 100000;
    if ($d2 < -50000) $d2 += 100000;
    // fetch aur hisaab ke beech period badal gaya ho to 0 hi sahi hai
    $d = ($d1 === 0 || $d2 === 0) ? 0 : $d2;
    if ($d > 5 || $d < -5) $d = 0;          // pagal value => formula hi theek hai""",
    1, "engine: drift ab hamesha FRESH data se")

# ---------------------------------------------------------------- 4
rep(DIAG,
"""echo "\\n" . $line . "\\n";
echo "END. Is file ko ab delete kar dena.\\n";""",
"""// ------------------------------------------------------------------ 15
echo "[15] STABILITY — period / result apne aap to nahi badalte?\\n";
echo '    LE_UPSTREAM_CACHE_SEC : ' . (defined('LE_UPSTREAM_CACHE_SEC') ? (string)LE_UPSTREAM_CACHE_SEC . ' second' : '<< NOT DEFINED >>') . "\\n";
echo "    (drift ab hamesha FRESH data se banta hai, result miss hone par\\n";
echo "     page 1 force-fetch hota hai — isliye random result nahi banta)\\n";
foreach (['WinGo_30S', 'WinGo_1M', 'WinGo_3M', 'WinGo_5M'] as $g) {
    $iss = function_exists('lottery_issue') ? lottery_issue($g) : null;
    if (!is_array($iss)) continue;
    $cur = (string)$iss['issueNumber'];
    $a = le_result_for_issue($g, $cur, false);
    $b = le_result_for_issue($g, $cur, false);
    $c = le_result_for_issue($g, $cur, false);
    $same = ((string)$a['number'] === (string)$b['number'] && (string)$b['number'] === (string)$c['number']);
    $dbnum = '-';
    if (function_exists('db')) {
        $cn = db();
        if ($cn) {
            $st = @$cn->prepare('SELECT premium FROM lottery_results WHERE game_code=? AND issue_number=? LIMIT 1');
            if ($st) { $st->bind_param('ss', $g, $cur); if ($st->execute()) { $rr = $st->get_result()->fetch_assoc(); if ($rr) $dbnum = (string)$rr['premium']; } }
        }
    }
    $age = -1;
    if (function_exists('le_upstream_cache_file') && function_exists('le_upstream_family') && function_exists('le_upstream_base')) {
        $u = le_upstream_base() . '/' . le_upstream_family($g) . '/' . rawurlencode($g) . '/GetHistoryIssuePage.json?pageNo=1&pageSize=10';
        $f = le_upstream_cache_file(md5($u));
        if (is_file($f)) $age = time() - (int)@filemtime($f);
    }
    $drift = function_exists('le_issue_drift') ? (int)le_issue_drift($g) : 0;
    echo "  --- $g ---\\n";
    echo '      site ka period       : ' . $cur . "\\n";
    echo '      API cache ki umar   : ' . ($age < 0 ? '-' : $age . ' second') . "\\n";
    echo '      result 3 baar manga : ' . (string)$a['number'] . ' / ' . (string)$b['number'] . ' / ' . (string)$c['number'] . "\\n";
    echo '      DB me pada hua      : ' . $dbnum . "\\n";
    echo '      drift               : ' . (string)$drift . "\\n";
    echo '      STABLE? : ' . ($same && $dbnum === (string)$a['number'] && $drift === 0
        ? 'YES  (period + result DONO pakke)' : 'NO  <<< yahi "apne aap badal jata hai"') . "\\n";
}
echo "\\n";

echo "\\n" . $line . "\\n";
echo "END. Is file ko ab delete kar dena.\\n";""",
    1, "maandiag: naya section [15] STABILITY")

print("")
print("replacements OK : %d" % ok)
if fail:
    print("FAILED          : %d" % len(fail))
    for f in fail:
        print("   - " + f)
    sys.exit(1)
print("SAB THEEK")
