#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
FIX #13 — GAME KA RESULT == API (draw.ar-lottery01.com) KA RESULT  (asli wajah)

JO MEASURE HUA (09-Oct-2026 02:21 IST, maan1win.club9.eu.cc/maanupdiag.php):

    upstream row0 : 20261008100011251   result=8      <- API (drawer JSON)
    local    row0 : 20261009100011250   result=0      <- game
    upstream row1 : 20261008100011250   result=4

DO ASLI WAJAH (dono alag the):

  WAJAH 1 — DATE ka timezone.
    API (draw.ar-lottery01.com) period number ki date UTC me banati hai,
    site apni timezone (Asia/Kolkata) me. Subah 00:00 se 05:30 IST ke beech
    dono ki date EK DIN ka farq ho jati hai:
        API : 20261008....      site : 20261009....
    Isliye (a) period number alag dikha AUR (b) result ki lookup hi miss ho
    gayi — API me "20261009100011250" naam ka koi period tha hi nahi.

  WAJAH 2 — galat result DB me Pakka ho jana.
    le_result_for_issue() PEHLE DB padhta tha, upstream BAAD me. Ek bar
    period ka result nahi mila to RANDOM result generate hua aur
    lottery_results me save ho gaya — phir hamesha wahi galat number dikha.

FIX:
  1) LE_ISSUE_DATE_TZ = 'utc'  -> period number ki date API jaisi (gmdate).
     ('local' = purana behaviour, switch hai.)
  2) le_upstream_result_for()  -> result ki lookup PURE issue number se NAHI,
     last 5 digit (tail) se bhi milti hai. Ek din ka farq ho to bhi match.
  3) le_result_for_issue() me upstream SABSE UPAR. API ka result mile to wahi
     dikhega AUR DB me bhi theek (repair) kar diya jayega.
  4) le_issue_prev()/next() ab TZ-free (gmmktime).
"""
import io, os, sys

ROOT = "/home/user/maanfix2"
ENG = os.path.join(ROOT, "api/_core/lottery_engine.php")
CFG = os.path.join(ROOT, "api/_core/config.php")
DIAG = os.path.join(ROOT, "maandiag.php")
UPDIAG = os.path.join(ROOT, "maanupdiag.php")

ok = 0
fail = []


def rep(path, old, new, count=1, tag=""):
    global ok
    with io.open(path, "r", encoding="utf-8") as f:
        s = f.read()
    n = s.count(old)
    if n != count:
        fail.append("%s  (%s): expected %d occurrence, found %d" % (tag, os.path.basename(path), count, n))
        return
    s = s.replace(old, new, count)
    with io.open(path, "w", encoding="utf-8") as f:
        f.write(s)
    ok += 1
    print("  OK  %-58s  (%s)" % (tag, os.path.basename(path)))


# =====================================================================
# 1) config.php — naye switch
# =====================================================================
rep(CFG,
"""if (!defined('LE_RESULT_SHIFT'))       define('LE_RESULT_SHIFT', 0);
//
// 5) DRAWER""",
"""if (!defined('LE_RESULT_SHIFT'))       define('LE_RESULT_SHIFT', 0);
//
//    PERIOD NUMBER ki DATE kis hisaab se banegi  (FIX #13 — ASLI WAJAH)
//      'utc'   = draw.ar-lottery01.com jaisa  (DEFAULT)
//                API apna period number UTC date se banati hai. Site apni
//                timezone (Asia/Kolkata) se banati thi, isliye subah
//                00:00 se 05:30 IST ke beech period number EK DIN aage ho
//                jata tha  (20261009....  vs  API 20261008....) — period
//                bhi alag dikha AUR result ki lookup bhi miss ho gayi.
//      'local' = site ki apni timezone (purana behaviour)
if (!defined('LE_ISSUE_DATE_TZ'))      define('LE_ISSUE_DATE_TZ', 'utc');
//
//    Upstream se kitne page (har page = 10 period) laye jayen.
//    3 page = 30 period: history ke 3 page tak API wala hi result milega.
if (!defined('LE_UPSTREAM_PAGES'))     define('LE_UPSTREAM_PAGES', 3);
//
// 5) DRAWER""",
    1, "config: LE_ISSUE_DATE_TZ + LE_UPSTREAM_PAGES")

# =====================================================================
# 2) engine — date helper + TZ-free prev/next
# =====================================================================
rep(ENG,
"""/** bina drift ke (drift function ke andar use hota hai — recursion se bachne ke liye) */
function le_issue_slot_number(string $gameCode, int $slot): string""",
"""// =======================================================================
// FIX #13 — PERIOD NUMBER KI DATE  (API = UTC, site = apni timezone)
//
//   draw.ar-lottery01.com apna period number UTC date se banata hai.
//   Site IST date se banati thi. Subah 00:00-05:30 IST ke beech dono me
//   EK DIN ka farq pad jata tha:
//        API : 20261008100011251      site : 20261009100011251
//   Natija: period number alag + API me result ki lookup miss (result random).
//   Ab date bhi API jaisi (gmdate) banegi, to number AUR result DONO same.
// =======================================================================
function le_issue_date_tz(): string
{
    if (!defined('LE_ISSUE_DATE_TZ')) return 'utc';
    $v = strtolower(trim((string)LE_ISSUE_DATE_TZ));
    return ($v === 'local' || $v === 'site') ? 'local' : 'utc';
}

/** period number ki 8-digit date — 'utc' ho to API jaisi (gmdate) */
function le_issue_date(int $ts): string
{
    return le_issue_date_tz() === 'local' ? date('Ymd', $ts) : gmdate('Ymd', $ts);
}

/** date me $delta din jodo/ghatao — server ki timezone se bilkul azaad */
function le_issue_shift_day(string $date, int $delta): string
{
    if (!preg_match('/^(\\d{4})(\\d{2})(\\d{2})$/', $date, $m)) return $date;
    $ts = gmmktime(0, 0, 0, (int)$m[2], (int)$m[3], (int)$m[1]) + ($delta * 86400);
    return gmdate('Ymd', $ts);
}

/** bina drift ke (drift function ke andar use hota hai — recursion se bachne ke liye) */
function le_issue_slot_number(string $gameCode, int $slot): string""",
    1, "engine: le_issue_date_tz/date/shift_day")

# dono jagah (slot_number + for_time) date('Ymd', $slot * $interval) -> le_issue_date()
rep(ENG,
"    return date('Ymd', $slot * $interval) . le_issue_mid($gameCode) . str_pad((string)$n, 5, '0', STR_PAD_LEFT);",
"    return le_issue_date($slot * $interval) . le_issue_mid($gameCode) . str_pad((string)$n, 5, '0', STR_PAD_LEFT);",
    2, "engine: date() -> le_issue_date()  x2")

# prev / next ab TZ-free
rep(ENG,
"""    $tail = (int)$m[3] - 1;
    if ($tail < 0) {
        $tail = 99999;
        $ts = strtotime($date . ' 00:00:00');
        if ($ts !== false) $date = date('Ymd', $ts - 86400);
    }""",
"""    $tail = (int)$m[3] - 1;
    if ($tail < 0) {
        $tail = 99999;
        $date = le_issue_shift_day($date, -1);
    }""",
    1, "engine: le_issue_prev() TZ-free")

rep(ENG,
"""    $tail = (int)$m[3] + 1;
    if ($tail > 99999) {
        $tail = 0;
        $ts = strtotime($date . ' 00:00:00');
        if ($ts !== false) $date = date('Ymd', $ts + 86400);
    }""",
"""    $tail = (int)$m[3] + 1;
    if ($tail > 99999) {
        $tail = 0;
        $date = le_issue_shift_day($date, 1);
    }""",
    1, "engine: le_issue_next() TZ-free")

# =====================================================================
# 3) engine — upstream se zyada page
# =====================================================================
rep(ENG,
"""    $fam = le_upstream_family($gameCode);
    for ($p = 1; $p <= 2; $p++) {""",
"""    $fam = le_upstream_family($gameCode);
    $pages = (int)(defined('LE_UPSTREAM_PAGES') ? LE_UPSTREAM_PAGES : 3);
    if ($pages < 1) $pages = 1;
    if ($pages > 5) $pages = 5;
    for ($p = 1; $p <= $pages; $p++) {""",
    1, "engine: LE_UPSTREAM_PAGES (3 page = 30 period)")

# =====================================================================
# 4) engine — tail-se result dhoondhne wala helper
# =====================================================================
rep(ENG,
"""// =======================================================================
// FIX #11 — RESULT KO EK PERIOD PICHE KARNA""",
"""/**
 * FIX #13 — API (drawer) se EK period ka result dhoondo.
 *
 *   Sirf PURE issue number se matlab rakha to date ka ek din ka farq
 *   (ya koi bhi chhota farq) poora match gira deta hai — tab result
 *   RANDOM ban jata tha. Ab LAST 5 DIGIT (tail) se bhi match hota hai,
 *   isliye 20261009100011250 aur 20261008100011250 EK HI period hain.
 *
 * @return string  premium ('' = API me ye period hai hi nahi)
 */
function le_upstream_result_for(string $gameCode, string $issueNumber): string
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
}

// =======================================================================
// FIX #11 — RESULT KO EK PERIOD PICHE KARNA""",
    1, "engine: le_upstream_result_for() (tail match)")

# =====================================================================
# 5) engine — le_result_save() me repair (overwrite)
# =====================================================================
rep(ENG,
"""function le_result_save(string $gameCode, string $issueNumber, array $detail): void
{""",
"""function le_result_save(string $gameCode, string $issueNumber, array $detail, bool $overwrite = false): void
{""",
    1, "engine: le_result_save() overwrite param")

rep(ENG,
"""    $stmt = @$conn->prepare('INSERT IGNORE INTO lottery_results(game_code, issue_number, premium, number, color, big_small, sum_value, open_time, created_at) VALUES(?,?,?,?,?,?,?,NOW(),NOW())');
    if (!$stmt) return;
    @$stmt->bind_param('ssssssi', $gameCode, $issueNumber, $premium, $number, $color, $bigSmall, $sum);
    @$stmt->execute();
}""",
"""    $stmt = @$conn->prepare('INSERT IGNORE INTO lottery_results(game_code, issue_number, premium, number, color, big_small, sum_value, open_time, created_at) VALUES(?,?,?,?,?,?,?,NOW(),NOW())');
    if (!$stmt) return;
    @$stmt->bind_param('ssssssi', $gameCode, $issueNumber, $premium, $number, $color, $bigSmall, $sum);
    @$stmt->execute();

    // FIX #13 — API ka result mil gaya to DB me bhi WAHII likho, chahe pehle
    // koi aur (random) result kyon na likha gaya ho. Isse history me hamesha
    // API wala hi result dikhega.
    if ($overwrite) {
        $u = @$conn->prepare('UPDATE lottery_results SET premium=?, number=?, color=?, big_small=?, sum_value=? WHERE game_code=? AND issue_number=? AND premium<>?');
        if ($u) {
            @$u->bind_param('ssssisss', $premium, $number, $color, $bigSmall, $sum, $gameCode, $issueNumber, $premium);
            @$u->execute();
        }
    }
}""",
    1, "engine: le_result_save() repair UPDATE")

# =====================================================================
# 6) engine — le_result_for_issue(): upstream SABSE UPAR
# =====================================================================
rep(ENG,
"""    $conn = db();
    if ($conn) le_ensure_lottery_results_table($conn);
    if ($conn) {
        $stmt = @$conn->prepare('SELECT id, game_code, issue_number, premium, number, color, big_small, sum_value, open_time, created_at FROM lottery_results WHERE game_code=? AND issue_number=? ORDER BY id ASC LIMIT 1');""",
"""    $conn = db();
    if ($conn) le_ensure_lottery_results_table($conn);

    // FIX #13 — DRAWER (API) KA RESULT SABSE UPAR.
    //   PURANA ORDER (galat): DB pehle, upstream baad me. Ek bar period ka
    //   result nahi mila to RANDOM ban kar DB me pakka ho gaya, aur phir
    //   hamesha wahi galat number dikha — chahe API kuch aur kahe.
    //   NAYA ORDER: API -> DB -> naya random. API ka result mile to DB me
    //   bhi theek (repair) kar diya jata hai.
    if (le_upstream_enabled() && function_exists('le_upstream_result_for')) {
        $upPremium = le_upstream_result_for($gameCode, $issueNumber);
        if ($upPremium !== '') {
            $upDetail = le_result_detail($gameCode, $upPremium, $issueNumber);
            le_result_save($gameCode, $issueNumber, $upDetail, true);
            return $upDetail;
        }
    }

    if ($conn) {
        $stmt = @$conn->prepare('SELECT id, game_code, issue_number, premium, number, color, big_small, sum_value, open_time, created_at FROM lottery_results WHERE game_code=? AND issue_number=? ORDER BY id ASC LIMIT 1');""",
    1, "engine: result order API -> DB -> random")

# purana (ab bekar) upstream block hata do
rep(ENG,
"""    // FIX #9: drawer (draw.ar-lottery01.com) wala RESULT yahan bhi lagao,
    // taaki game screen par bhi wahi period + wahi result dikhe.
    if (le_upstream_enabled()) {
        $upRows = le_upstream_results($gameCode);
        if (isset($upRows[$issueNumber]) && is_array($upRows[$issueNumber])) {
            $upPremium = (string)($upRows[$issueNumber]['premium'] ?? $upRows[$issueNumber]['number'] ?? '');
            if ($upPremium !== '') {
                $upDetail = le_result_detail($gameCode, $upPremium, $issueNumber);
                le_result_save($gameCode, $issueNumber, $upDetail);
                return $upDetail;
            }
        }
    }

""",
"""    // (FIX #9 ka purana block: upar FIX #13 me aa gaya — ab API SABSE UPAR)

""",
    1, "engine: purana FIX #9 block hata")

# =====================================================================
# 7) maandiag.php — [11] tail-match wala + naya [12] API vs game table
# =====================================================================
rep(DIAG,
"""    $upMap = function_exists('le_upstream_results') ? le_upstream_results($g) : [];
    $upRes = isset($upMap[$cur]) ? (string)($upMap[$cur]['premium'] ?? $upMap[$cur]['number'] ?? '-') : '-';""",
"""    $upRes = function_exists('le_upstream_result_for') ? le_upstream_result_for($g, $cur) : '';
    if ($upRes === '') $upRes = '-';""",
    1, "maandiag [11]: tail match")

rep(DIAG,
"""echo "\\n" . $line . "\\n";
echo "END. Is file ko ab delete kar dena.\\n";""",
"""// ------------------------------------------------------------------ 12
echo "[12] FIX #13 — GAME vs API (json wala URL) — PERIOD AUR RESULT DONO\\n";
echo '    LE_ISSUE_DATE_TZ : ' . (defined('LE_ISSUE_DATE_TZ') ? LE_ISSUE_DATE_TZ : '<< NOT DEFINED >>') . "\\n";
echo '    LE_UPSTREAM_PAGES: ' . (defined('LE_UPSTREAM_PAGES') ? (string)LE_UPSTREAM_PAGES : '<< NOT DEFINED >>') . "\\n";
echo '    server timezone  : ' . date('T') . '   abhi ' . date('Y-m-d H:i:s') . '  |  UTC ' . gmdate('Y-m-d H:i:s') . "\\n";
foreach (['WinGo_30S', 'WinGo_1M'] as $g) {
    $iss = function_exists('lottery_issue') ? lottery_issue($g) : null;
    if (!is_array($iss)) continue;
    $cur = (string)$iss['issueNumber'];
    echo "  --- $g ---\\n";
    $upNew = function_exists('le_upstream_newest') ? le_upstream_newest($g) : null;
    $upN   = is_array($upNew) ? trim((string)($upNew['issueNumber'] ?? '')) : '';
    echo '      API ka naya period (row0) : ' . ($upN !== '' ? $upN : '-') . "\\n";
    echo '      site ka period            : ' . $cur . "\\n";
    $same = 0; $tot = 0;
    if (function_exists('le_history_page')) {
        $hp = le_history_page($g, 1, 5);
        foreach ($hp['list'] as $i => $row) {
            $num = (string)$row['issueNumber'];
            $up  = function_exists('le_upstream_result_for') ? le_upstream_result_for($g, $num) : '';
            $tot++;
            $hit = ($up !== '' && $up === (string)$row['number']);
            if ($hit) $same++;
            echo '      row' . $i . ' : site ' . $num . ' = ' . (string)$row['number']
                 . '   |   API ' . ($up !== '' ? $up : '<< nahi mila >>')
                 . '   ' . ($hit ? 'SAME' : 'ALAG <<<') . "\\n";
        }
    }
    echo '      NATIJA : ' . $same . '/' . $tot . ' row API se mile'
         . ($same === $tot && $tot > 0 ? '  => SAB API WALE (theek)' : '  => GADBAD HAI') . "\\n";
}
echo "\\n";

echo "\\n" . $line . "\\n";
echo "END. Is file ko ab delete kar dena.\\n";""",
    1, "maandiag: naya section [12]")

# =====================================================================
# 8) maanupdiag.php — [A] naye switch, [B] shift-aware MATCH, [D] TZ saboot
# =====================================================================
rep(UPDIAG,
"""if (function_exists('le_issue_offset')) {""",
"""echo '    LE_ISSUE_DATE_TZ  : ' . (defined('LE_ISSUE_DATE_TZ') ? LE_ISSUE_DATE_TZ : '<< NOT DEFINED >>') . "   (utc = API jaisa)\\n";
echo '    LE_PERIOD_SHIFT  : ' . (function_exists('le_period_shift') ? (string)le_period_shift() : '<< NOT DEFINED >>') . "\\n";
echo '    LE_UPSTREAM_PAGES: ' . (defined('LE_UPSTREAM_PAGES') ? (string)LE_UPSTREAM_PAGES : '<< NOT DEFINED >>') . "\\n";
echo '    server timezone  : ' . date('T') . '   abhi ' . date('Y-m-d H:i:s') . '  |  UTC ' . gmdate('Y-m-d H:i:s') . "\\n";
if (function_exists('le_issue_offset')) {""",
    1, "maanupdiag [A]: naye switch")

rep(UPDIAG,
"""    echo '      MATCH?   : ' . (($ui !== '' && $ui === $li) ? 'YES  (drawer = game)' : ($ui === '' ? 'UPSTREAM SE JAWAB NAHI MILA' : 'NO   (abhi bhi alag)')) . "\\n\\n";""",
"""    $shift = function_exists('le_period_shift') ? le_period_shift() : 0;
    $exp   = ($ui !== '' && function_exists('le_issue_add')) ? (string)le_issue_add($ui, $shift) : '';
    echo '      expected  -> (API ' . ($shift <= 0 ? (string)$shift : '+' . $shift) . ' period) issueNumber=' . ($exp !== '' ? $exp : '-') . "\\n";
    echo '      MATCH?   : ' . (($exp !== '' && $exp === $li) ? 'YES  (site = API ' . $shift . ' period)' : ($ui === '' ? 'UPSTREAM SE JAWAB NAHI MILA' : 'NO   (abhi bhi alag)')) . "\\n\\n";""",
    1, "maanupdiag [B]: shift-aware MATCH")

rep(UPDIAG,
"""echo $line . "\\n";
echo "END. Is page ka output developer ko bhej do. Kaam ho jane par delete kar dena.\\n";""",
"""// ------------------------------------------------------------------ D
echo "[D] DATE / TIMEZONE — API ki date kis hisaab se banti hai?\\n";
echo '    PHP timezone      : ' . date('T') . "\\n";
echo '    abhi site ka samay: ' . date('Y-m-d H:i:s') . ' ' . date('T') . "\\n";
echo '    abhi UTC samay    : ' . gmdate('Y-m-d H:i:s') . ' UTC' . "\\n";
echo '    LE_ISSUE_DATE_TZ  : ' . (defined('LE_ISSUE_DATE_TZ') ? LE_ISSUE_DATE_TZ : '<< NOT DEFINED >>') . "\\n";
foreach ($games as $g) {
    $fam = function_exists('le_upstream_family') ? le_upstream_family($g) : 'WinGo';
    $url = $base . '/' . $fam . '/' . rawurlencode($g) . '/GetHistoryIssuePage.json?pageNo=1&pageSize=10';
    $r   = up_raw($url);
    $j   = json_decode($r['body'], true);
    $rows = (is_array($j) && isset($j['data']['list']) && is_array($j['data']['list'])) ? $j['data']['list'] : [];
    echo "  --- $g ---\\n";
    if (!isset($rows[0])) { echo "      API se row nahi mili (http=" . $r['code'] . ")\\n"; continue; }
    $row = $rows[0];
    $num = trim((string)($row['issueNumber'] ?? $row['issueNo'] ?? $row['period'] ?? ''));
    $ot  = (int)($row['openTime'] ?? 0);
    $ots = $ot > 1000000000000 ? (int)floor($ot / 1000) : $ot;
    echo '      API row0 issueNumber : ' . $num . "\\n";
    echo '      API row0 openTime    : ' . $ot . '  -> UTC ' . gmdate('Y-m-d H:i:s', $ots)
         . '  |  site TZ ' . date('Y-m-d H:i:s', $ots) . "\\n";
    echo '      API row0 date  UTC   : ' . gmdate('Ymd', $ots) . "\\n";
    echo '      API row0 date  site  : ' . date('Ymd', $ots) . "\\n";
    echo '      API row0 date in no. : ' . substr($num, 0, 8) . "\\n";
    echo '      SAMAN? : ' . (substr($num, 0, 8) === gmdate('Ymd', $ots)
            ? 'UTC   (LE_ISSUE_DATE_TZ = utc SAHI hai)'
            : (substr($num, 0, 8) === date('Ymd', $ots)
                ? 'SITE TZ  (LE_ISSUE_DATE_TZ = local chahiye)' : 'DONO SE ALAG <<<')) . "\\n";
}
echo "\\n";

echo $line . "\\n";
echo "END. Is page ka output developer ko bhej do. Kaam ho jane par delete kar dena.\\n";""",
    1, "maanupdiag: naya section [D] timezone saboot")

print("")
print("replacements OK : %d" % ok)
if fail:
    print("FAILED          : %d" % len(fail))
    for f in fail:
        print("   - " + f)
    sys.exit(1)
print("SAB THEEK")
