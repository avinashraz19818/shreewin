#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
MAANWIN — FIX #9 (2026-10-08)
================================
User (binding): "jo drawer json wala api url https://draw.ar-lottery01.com/
WinGo/WinGo_1M/GetHistoryIssuePage.json hai uska result dekho aur game me jo
aa raha hai uska dekho — DONO ALAG AA".

WHAT WAS FOUND IN THE FRONTEND (js/index-MaanWinRefreshFix-20261006.js):
    requestInterceptors:
      const t = "https://draw.ar-lottery01.com", n = "https://api.ar-lottery01.com";
      r = qs.get(pr.API_URL) || "";
      if (url includes "/kv/")        -> url UNCHANGED  (LOCAL server)
      else if (url includes ".json")  -> url = ( ey("draw", r) || t ) + url
      else                            -> url = ( ey("api",  r) || n ) + url
    ey(e,t) = hostname ke aage subdomain laga deta hai ("draw"/"api").
    API_URL set nahi hai isliye dono fallback chalte hain:
        /WinGo/<game>/GetHistoryIssuePage.json  -> draw.ar-lottery01.com  (DRAWER)
        /webapi/kv/issue/<game>                 -> LOCAL server           (GAME)
    => D DRAWER ka data upstream se, GAME ka period local engine se.
       Do alag source = do alag period number aur do alag result.

FIX #9
    Local backend ab UPAR WALI (draw.ar-lottery01.com) hi ki period/result
    use karega — 2-3 second ka file-cache ke sath (har user par alag HTTP
    call nahi jayegi). Upstream na mile to PURANA local hisaab chalu rahega
    (koi risk nahi: site rukti nahi).

    * lottery_issue()      -> upstream /webapi/kv/issue/<game> ka issueNumber,
                              startTime, endTime  (timer lead waisa hi rahega)
    * le_result_for_issue()-> upstream GetHistoryIssuePage.json se result,
                              aur wo result DB me bhi save ho jata hai
    * config: LE_UPSTREAM_BASE / LE_UPSTREAM_RESULTS / LE_UPSTREAM_TIMEOUT
      LE_UPSTREAM_RESULTS = false kar do to site bilkul purani tarah chalegi.

    Naya page: maanupdiag.php  -> server se upstream reachable hai ya nahi,
    kya status code mila, upstream vs local period/result side by side.
"""
import os
import sys

SRC = sys.argv[1] if len(sys.argv) > 1 else "/home/user/maanfix2"
FAILS = []


def rep(path, old, new, count=1):
    full = os.path.join(SRC, path)
    with open(full, "r", encoding="utf-8") as f:
        s = f.read()
    n = s.count(old)
    if n != count:
        FAILS.append("%s: expected %d match(es), found %d for %r" % (path, count, n, old[:70]))
        return
    with open(full, "w", encoding="utf-8") as f:
        f.write(s.replace(old, new))
    print("  ok  %-34s (%d)" % (path, n))


# ------------------------------------------------------------ 1. config
rep(
    "api/_core/config.php",
    """if (!defined('LE_TAX_PERCENT'))         define('LE_TAX_PERCENT', 2.0);""",
    """// 5) DRAWER (draw.ar-lottery01.com) AUR GAME — EK HI PERIOD / RESULT
//      Frontend .json requests (GetHistoryIssuePage.json) sidhe
//      https://draw.ar-lottery01.com par jati hain (DRAWER), jabki game ka
//      period local server se aata tha — isliye dono ALAG dikhte the.
//      true  = local backend bhi wahi upstream period + result use kare
//              (2-3 second cache; upstream na mile to purana local hisaab)
//      false = purana behaviour (sab kuch local engine se)
if (!defined('LE_UPSTREAM_RESULTS'))    define('LE_UPSTREAM_RESULTS', true);
if (!defined('LE_UPSTREAM_BASE'))       define('LE_UPSTREAM_BASE', 'https://draw.ar-lottery01.com');
if (!defined('LE_UPSTREAM_TIMEOUT'))    define('LE_UPSTREAM_TIMEOUT', 3);
if (!defined('LE_UPSTREAM_CACHE_SEC'))  define('LE_UPSTREAM_CACHE_SEC', 3);
//
if (!defined('LE_TAX_PERCENT'))         define('LE_TAX_PERCENT', 2.0);""",
)

# ------------------------------------------------------------ 2. engine: helpers
rep(
    "api/_core/lottery_engine.php",
    """function le_color_from_number(int $n): string
{""",
    """// =======================================================================
// FIX #9 — DRAWER (draw.ar-lottery01.com) AUR GAME — EK HI PERIOD / RESULT
//
//   Frontend me axios interceptor .json wali requests ko
//   https://draw.ar-lottery01.com par bhejta hai (DRAWER = wahi data),
//   lekin /webapi/kv/issue LOCAL server se aata hai (GAME = local period).
//   Do alag source => period number aur result DONO ALAG.
//
//   Ab local backend bhi upstream ka data use karega:
//     * lottery_issue()       -> upstream ka issueNumber / startTime / endTime
//     * le_result_for_issue() -> upstream ka result (DB me bhi save)
//   Cache: file based (2-3 sec) — har user ke liye alag HTTP call nahi jati.
//   Upstream na mile / 403 de / time-out ho => PURANA local hisaab (safe).
// =======================================================================
function le_upstream_enabled(): bool
{
    return defined('LE_UPSTREAM_RESULTS') && LE_UPSTREAM_RESULTS
        && defined('LE_UPSTREAM_BASE') && trim((string)LE_UPSTREAM_BASE) !== '';
}

function le_upstream_base(): string
{
    return rtrim((string)(defined('LE_UPSTREAM_BASE') ? LE_UPSTREAM_BASE : ''), '/');
}

/** WinGo_1M -> WinGo, K3_1M -> K3, D5_1M -> D5 ... (upstream URL ka pehla hissa) */
function le_upstream_family(string $gameCode): string
{
    $c = strtolower(trim($gameCode));
    if (strpos($c, 'trxwingo') === 0) return 'TrxWinGo';
    if (strpos($c, 'wingo') === 0)    return 'WinGo';
    if (strpos($c, 'k3') === 0)       return 'K3';
    if (strpos($c, '5d') === 0)       return 'D5';
    if (strpos($c, 'd5') === 0)       return 'D5';
    if (strpos($c, 'motorace') === 0) return 'MotoRace';
    if (strpos($c, 'moto') === 0)     return 'MotoRace';
    return 'WinGo';
}

function le_upstream_cache_file(string $key): string
{
    $dir = '';
    if (function_exists('sys_get_temp_dir')) { $d = @sys_get_temp_dir(); if (is_string($d) && $d !== '') $dir = $d; }
    if ($dir === '') $dir = __DIR__;
    return rtrim($dir, '/') . '/maanup_' . $key . '.json';
}

/**
 * Upstream se JSON lao. Cache file me rakho (LE_UPSTREAM_CACHE_SEC second).
 * Fail hone par thodi der (30 sec) tak dobara call nahi karega.
 * @param bool $raw  true = cached body use mat karo (diagnostic ke liye)
 * @return array|null
 */
function le_upstream_get(string $path, int $ttl = 0, bool $raw = false): ?array
{
    static $mem = [];
    if (!le_upstream_enabled()) return null;
    $path = '/' . ltrim($path, '/');
    $url  = le_upstream_base() . $path;
    if ($ttl <= 0) $ttl = (int)(defined('LE_UPSTREAM_CACHE_SEC') ? LE_UPSTREAM_CACHE_SEC : 3);
    if ($ttl < 1) $ttl = 1;
    $failTtl = 30;

    $file = le_upstream_cache_file(md5($url));
    if (!$raw && isset($mem[$url])) {
        $m = $mem[$url];
        if (is_array($m) && (time() - (int)$m[0]) <= $ttl) return $m[1];
    }
    if (!$raw && is_file($file)) {
        $age = time() - (int)@filemtime($file);
        $txt = @file_get_contents($file);
        if (is_string($txt) && $txt !== '') {
            $c = json_decode($txt, true);
            if (is_array($c) && isset($c['t'])) {
                $isFail = !empty($c['fail']);
                $limit  = $isFail ? $failTtl : $ttl;
                if ($age <= $limit) {
                    $mem[$url] = [time(), $isFail ? null : ($c['data'] ?? null)];
                    return $isFail ? null : ($c['data'] ?? null);
                }
            }
        }
    }

    $timeout = (int)(defined('LE_UPSTREAM_TIMEOUT') ? LE_UPSTREAM_TIMEOUT : 3);
    if ($timeout < 1) $timeout = 1;
    if ($timeout > 10) $timeout = 10;
    $base = le_upstream_base();
    $hdr  = [
        'Accept: application/json, text/plain, */*',
        'Origin: ' . $base,
        'Referer: ' . $base . '/',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
    ];
    $body = null;
    if (function_exists('curl_init')) {
        $ch = @curl_init($url);
        if ($ch) {
            @curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => $timeout,
                CURLOPT_TIMEOUT        => $timeout + 2,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_HTTPHEADER     => $hdr,
            ]);
            $b = @curl_exec($ch);
            @curl_close($ch);
            if (is_string($b)) $body = $b;
        }
    }
    if (($body === null || $body === '') && (int)ini_get('allow_url_fopen') === 1) {
        $ctx = @stream_context_create([
            'http' => ['method' => 'GET', 'timeout' => $timeout, 'header' => implode("\\r\\n", $hdr)],
            'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);
        $b = @file_get_contents($url, false, $ctx);
        if (is_string($b)) $body = $b;
    }

    if (!is_string($body) || trim($body) === '') {
        if (!$raw) @file_put_contents($file, json_encode(['t' => time(), 'fail' => 1]), LOCK_EX);
        $mem[$url] = [time(), null];
        return null;
    }
    $json = json_decode($body, true);
    if (!is_array($json)) {
        if (!$raw) @file_put_contents($file, json_encode(['t' => time(), 'fail' => 1]), LOCK_EX);
        $mem[$url] = [time(), null];
        return null;
    }
    if (!$raw) @file_put_contents($file, json_encode(['t' => time(), 'fail' => 0, 'data' => $json]), LOCK_EX);
    $mem[$url] = [time(), $json];
    return $json;
}

/** upstream ki CURRENT issue: /webapi/kv/issue/<game> */
function le_upstream_issue(string $gameCode): ?array
{
    if (!le_upstream_enabled()) return null;
    $j = le_upstream_get('/webapi/kv/issue/' . rawurlencode($gameCode), 2);
    if (!is_array($j)) return null;
    $d = $j['data'] ?? null;
    if (!is_array($d)) return null;
    $issue = trim((string)($d['issueNumber'] ?? $d['issueNo'] ?? $d['issue'] ?? $d['period'] ?? ''));
    if ($issue === '') return null;
    return $d;
}

/** upstream ki history: issueNumber => row  (2 page = 20 period) */
function le_upstream_results(string $gameCode): array
{
    static $mem = [];
    if (!le_upstream_enabled()) return [];
    if (isset($mem[$gameCode])) return $mem[$gameCode];
    $out = [];
    $fam = le_upstream_family($gameCode);
    for ($p = 1; $p <= 2; $p++) {
        $j = le_upstream_get('/' . $fam . '/' . rawurlencode($gameCode) . '/GetHistoryIssuePage.json?pageNo=' . $p . '&pageSize=10');
        $list = (is_array($j) && isset($j['data']['list']) && is_array($j['data']['list'])) ? $j['data']['list'] : [];
        foreach ($list as $row) {
            if (!is_array($row)) continue;
            $num = trim((string)($row['issueNumber'] ?? $row['issueNo'] ?? $row['period'] ?? ''));
            if ($num !== '') $out[$num] = $row;
        }
    }
    $mem[$gameCode] = $out;
    return $out;
}

/** result row ko lottery_results me save (INSERT IGNORE) */
function le_result_save(string $gameCode, string $issueNumber, array $detail): void
{
    $conn = db();
    if (!$conn || $issueNumber === '') return;
    if (function_exists('le_ensure_lottery_results_table')) le_ensure_lottery_results_table($conn);
    $premium  = (string)$detail['premium'];
    $number   = (string)$detail['number'];
    $color    = (string)$detail['color'];
    $bigSmall = (string)$detail['bigSmall'];
    $sum      = (int)$detail['sum'];
    $stmt = @$conn->prepare('INSERT IGNORE INTO lottery_results(game_code, issue_number, premium, number, color, big_small, sum_value, open_time, created_at) VALUES(?,?,?,?,?,?,?,NOW(),NOW())');
    if (!$stmt) return;
    @$stmt->bind_param('ssssssi', $gameCode, $issueNumber, $premium, $number, $color, $bigSmall, $sum);
    @$stmt->execute();
}

function le_color_from_number(int $n): string
{""",
)

# ------------------------------------------------------------ 3. engine: result hook
rep(
    "api/_core/lottery_engine.php",
    """    $settings = le_get_settings($gameCode);
    $forceResult = trim((string)($settings['force_result'] ?? ''));""",
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

    $settings = le_get_settings($gameCode);
    $forceResult = trim((string)($settings['force_result'] ?? ''));""",
)

# ------------------------------------------------------------ 4. bootstrap: lottery_issue
rep(
    "api/_core/bootstrap.php",
    """    $interval = le_game_interval($gameCode);
    $now = time();
    $slot = intdiv($now, $interval);
    $start = $slot * $interval;
    $end = $start + $interval;
    // --- FIX #2: timer 1 second KAM dikhana -------------------------------""",
    """    $interval = le_game_interval($gameCode);
    $now = time();
    $slot = intdiv($now, $interval);
    $start = $slot * $interval;
    $end = $start + $interval;

    // --- FIX #9: DRAWER (draw.ar-lottery01.com) AUR GAME EK HI PERIOD -----
    //   Drawer ka data upstream se aata hai. Game ka period/result bhi ab
    //   wahin se aayega — tabhi dono match karenge. Upstream na mile to
    //   niche wala local hisaab hi use hoga (site rukti nahi).
    $issue = le_issue_for_time($start, $gameCode);
    if (function_exists('le_upstream_issue')) {
        $up = le_upstream_issue($gameCode);
        if (is_array($up)) {
            $upIssue = trim((string)($up['issueNumber'] ?? $up['issueNo'] ?? $up['issue'] ?? $up['period'] ?? ''));
            if ($upIssue !== '') {
                $issue = $upIssue;
                if (isset($up['startTime']) && (int)$up['startTime'] > 0) $start = (int)floor(((int)$up['startTime']) / 1000);
                if (isset($up['endTime'])   && (int)$up['endTime']   > 0) $end   = (int)floor(((int)$up['endTime'])   / 1000);
            }
        }
    }
    // ----------------------------------------------------------------------
    // --- FIX #2: timer 1 second KAM dikhana -------------------------------""",
)

rep(
    "api/_core/bootstrap.php",
    """    // ----------------------------------------------------------------------
    // Original-style issue: YYYYMMDD1000xxxxx (WinGo screenshots use this shape).
    // FIX #8: ab period number EK HI jagah se banta hai (le_issue_for_time),
    // isliye game screen ka period aur history/drawer ka period hamesha same.
    $issue = le_issue_for_time($start, $gameCode);
    return [""",
    """    // ----------------------------------------------------------------------
    // ($issue, $start, $end upar FIX #9 me set ho chuke hain)
    return [""",
)

# ------------------------------------------------------------ 5. diag [10]
rep(
    "maandiag.php",
    """echo "\\n" . $line . "\\n";
echo "END. Is file ko ab delete kar dena.\\n";""",
    """// ------------------------------------------------------------------ 10
echo "[10] FIX #9 — UPSTREAM (draw.ar-lottery01.com) MIRROR\\n";
echo '    LE_UPSTREAM_RESULTS : ' . (defined('LE_UPSTREAM_RESULTS') ? var_export(LE_UPSTREAM_RESULTS, true) : 'NOT DEFINED') . "\\n";
echo '    LE_UPSTREAM_BASE    : ' . (defined('LE_UPSTREAM_BASE') ? LE_UPSTREAM_BASE : 'NOT DEFINED') . "\\n";
echo '    curl available      : ' . (function_exists('curl_init') ? 'yes' : 'NO') . "\\n";
echo '    allow_url_fopen     : ' . var_export((bool)ini_get('allow_url_fopen'), true) . "\\n";
if (function_exists('le_upstream_get')) {
    foreach (['WinGo_30S', 'WinGo_1M'] as $g) {
        $up = function_exists('le_upstream_issue') ? le_upstream_issue($g) : null;
        $loc = lottery_issue($g);
        $ui = is_array($up) ? trim((string)($up['issueNumber'] ?? '')) : '';
        $li = (string)$loc['issueNumber'];
        echo "  --- $g ---\\n";
        echo '      upstream issue : ' . ($ui !== '' ? $ui : '<< UPSTREAM SE JAWAB NAHI MILA >>') . "\\n";
        echo '      game  issue    : ' . $li . "\\n";
        echo '      MATCH? : ' . (($ui !== '' && $ui === $li) ? 'YES  (drawer = game)' : 'NO   (abhi bhi alag)') . "\\n";
    }
} else {
    echo "    le_upstream_get() MISSING — fix #9 laga nahi hai\\n";
}
echo "    (poori detail ke liye /maanupdiag.php kholo)\\n";
echo "\\n";

echo "\\n" . $line . "\\n";
echo "END. Is file ko ab delete kar dena.\\n";""",
)

if FAILS:
    print("\n\nFAILED:")
    for f in FAILS:
        print("  -", f)
    sys.exit(1)

print("\nfix#9 patch applied OK to", SRC)
