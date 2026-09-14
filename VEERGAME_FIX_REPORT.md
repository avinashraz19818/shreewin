VEERGAME — WinGo / LOTTERY FIX REPORT
====================================
Date       : 2026-09-14
Reference  : working install of the same codebase ("shreewin") + VeerGame logs


KYA PROBLEM THI (WHAT WAS BROKEN)
---------------------------------
WinGo (aur K3 / 5D / TrxWinGo / MotoRace) kholne par page load nahi hota tha /
blank ya error screen aata tha, kyunki neeche diye gaye 5 defects ek saath the:


[1] FRONTEND GALAT BACKEND HOST PAR REQUEST BHEJ RAHA THA
    File   : assets/js/index-CBcbycSk.js (entry bundle), assets/js/main.vue_*.js
    Bundle `api.<domain>` aur `draw.<domain>` hosts use karta hai jo is server
    par exist hi nahi karte (bundle kisi purane deployment par build hua tha).
    Isliye GetGameInfo / GetHistoryIssuePage / WinGoBet aur draw feed
    (WinGo_30S.json) — sab unreachable hosts par jaate the.

[2] SERVER PAR FATAL PHP ERROR — error_logger.php MISSING THI
    File   : developer-maruf/conn.php  (line 2)  ->  error_logger.php
    Production log (api-live-v4/Lottery/error_log) me har request par:
        PHP Fatal error: require_once(.../developer-maruf/error_logger.php):
        failed to open stream: No such file or directory
    Matlab database se pehle hi request mar jaati thi => lottery API HTML 500
    deta tha, JSON nahi. Yahi "kuch load hi nahi hota" ka main server-side
    reason hai.

[3] ACTIVE SETTINGS ENDPOINT NE LOTTERY BAND KAR RAKHI THI
    File   : evenvessis/api/webapi/GetHomeSettings.php
    Ye file live API ka purana capture thi jisme:
        "isOpenArLottery": false   -> client lottery tile ko EXTERNAL AR vendor
                                      game maan kar purane api/draw hosts par
                                      bhej raha tha (wahi blank/stuck screen)
        "isSwitchSaasBalance": false -> wallet balance API se set nahi ho raha tha
        "isV3Mode": true           -> v3 files is build me nahi hain
    Note: veegame ke apne developer-maruf/api/webapi/GetHomeSettings.php me ye
    flags already sahi (true/true/false) the — sirf ACTIVE copy galat thi.

[4] mysqli_stmt::get_result() NON-MYSQLND PHP PAR EXIST NAHI KARTA
    File   : 69 files, 152 call sites (saas_lottery/bootstrap_live_v4.php,
             api-live-v4/Lottery/index.php, wallet/webapi files, admin pages)
    cPanel ke kai PHP builds bina mysqlnd ke hote hain. Wahan
        Call to undefined method mysqli_stmt::get_result()
    aata hai. Lottery history me ye error live log me bhi mila.

[5] HARDCODED DOMAIN (domain change karne par sab toot jaata hai)
    Files  : evenvessis/api/webapi/GetGameUrl.php  -> $origin = 'https://veergame.club9.eu.cc'
             evenvessis/api/webapi/GetRechargeTypes.php -> $sites = 'veergame.club9.eu.cc'
             index.html -> VITE_API_URL absolute URL tha
    Client game URL ke origin se api/draw hosts nikalta hai, is liye hardcoded
    domain hone par naye domain/deployment par lottery + recharge URLs galat
    host par jaate hain.

Saath hi:
   - web/config (client boot par /web/config?_key=ar102) repo me nahi thi.
   - favico.ico / icon-192x192.png / icon-512x512.png missing the.
   - index.html ka VITE_API_URL /evenvessis par tha jabki app ka baaki saas
     backend developer-maruf tree use karta hai (working shreewin site
     "/developer-maruf" use karti hai).


KYA FIX KIYA (WHAT WAS CHANGED)
-------------------------------
1. same-origin-lottery.js  (NAYI FILE)
   index.html me app bundle se pehle load hoti hai aur:
     - localStorage ar_api / ar_api_json ko current domain par pin karti hai,
     - fetch + XMLHttpRequest (axios) dono par kisi bhi api.*/draw.* request ko
       current domain + sahi endpoint par rewrite karti hai,
     - /api/Lottery/<Action> -> /api-live-v4/Lottery/index.php?action=<Action>,
       /WinGo/WinGo_30S.json  -> /draw-live-v4/index.php?lottery=WinGo&gameCode=WinGo_30S,
     - purane /api/webapi/* ko /developer-maruf/api/webapi/* par le jaati hai,
     - purane service-worker/cache hata deti hai (stale JS problem),
     - koi lazy page missing ho to blank screen ki jagah readable message deti hai.
   Isse purane cached bundle ke saath bhi lottery kaam karta hai.

2. developer-maruf/error_logger.php  (RESTORE)
   Missing file wapas add ki (conn.php fresh install par skip karta hai agar
   file na ho, aur app_log_event() ka fallback bhi hai) — fatal error khatam.

3. mysqli_compat.php  (NAYI FILE)
   app_stmt_result($stmt) = mysqli_stmt::get_result() ka portable replacement
   (bind_result + fetch se wahi result object banata hai).
   Saath me mysqlnd-only procedural API ka polyfill:
   mysqli_stmt_get_result(), mysqli_fetch_all().
   Saare 6 conn.php (developer-maruf, evenvessis, evenvessis/api,
   evenvessis/api/webapi, digitaladmin, digitaladmin/api) ise load karte hain.
   Isi file me PHP 8 ke str_contains()/str_starts_with()/str_ends_with() ka
   polyfill bhi hai, taaki PHP 7.4 wale cPanel par bhi code chale.

4. 152 get_result() call sites -> app_stmt_result(...) (69 files)
   Saare conn.php files me mysqli_report(MYSQLI_REPORT_OFF), connect error par
   JSON response, aur utf8mb4 charset bhi set kiya.

5. evenvessis/api/webapi/GetHomeSettings.php  (REWRITE)
   Ab PHP endpoint hai jo fresh JSON deta hai aur lottery flags force karta hai:
       isOpenArLottery = true, isSwitchSaasBalance = true, isV3Mode = false
   Baaki branding/settings (logo, languages, recharge amounts...) same hain.

6. index.html
   - VITE_API_URL -> "/developer-maruf" (relative, domain independent — working
     shreewin site bhi yahi karti hai),
   - same-origin-lottery.js include,
   - API/draw hardcoded hosts nahi rahe.

7. Bundle patch (assets/js/index-CBcbycSk.js, index-BjnPYVvI.js,
   index-CM5ZoCWi.js, main.vue_*.js x3)
   hp("api", origin) / hp("draw", origin)  ->  location.origin
   https://h5.ar-lottery06.com / https://api.veergameapi.com -> location.origin
   (shim ke saath double safety; purane cached copies bhi shim se cover hain.)

8. evenvessis/api/webapi/_common.php
   Naye helper: api_request_origin() / api_request_host() — request ke domain
   se host nikalte hain.
   GetGameUrl.php aur GetRechargeTypes.php ab inhe use karte hain (hardcoded
   domain hata diya).

9. .htaccess
   - api/Lottery/<Action> -> api-live-v4 (pehle se) [END,QSA],
   - draw JSON routes (pehle se),
   - NAYA: index.html/*.html ke liye no-cache + .js revalidate, images 7 din cache.

10. web/config + web/config.js + web/config.html + favico.ico + icons (RESTORE)

11. veegame_check.php (NAYI FILE — diagnostic)
    Browser me kholte hi batata hai: PHP version, mysqli/mysqlnd, missing files,
    DB connection + tables, draw provider reachable hai ya nahi, aur local
    endpoints (draw/api/bridge) 200 de rahe hain ya nahi.
    Kaam hone ke baad ise delete kar dena.


FILES (NEW)
-----------
    same-origin-lottery.js
    mysqli_compat.php
    developer-maruf/error_logger.php
    veegame_check.php
    web/config, web/config.js, web/config.html, web/.htaccess
    favico.ico, icon-192x192.png, icon-512x512.png


FILES (MODIFIED)
----------------
    index.html
    .htaccess
    evenvessis/api/webapi/GetHomeSettings.php
    evenvessis/api/webapi/_common.php
    evenvessis/api/webapi/GetGameUrl.php
    evenvessis/api/webapi/GetRechargeTypes.php
    developer-maruf/conn.php
    evenvessis/conn.php, evenvessis/api/conn.php, evenvessis/api/webapi/conn.php
    digitaladmin/conn.php, digitaladmin/api/conn.php
    assets/js/index-CBcbycSk.js, assets/js/index-BjnPYVvI.js, assets/js/index-CM5ZoCWi.js
    assets/js/main.vue_vue_type_style_index_0_scoped_d3b4a951_lang-{BQvK2MsY,C3gAtki6,Deq63BmN}.js
    + 69 PHP files jahan get_result() -> app_stmt_result() hua


BAKI (IS ZIP KE BAAHAR) — frontend ke kuch lazy chunks
-----------------------------------------------------
`missing-frontend-files.md` me list hai. Sabse zaroori:
    betRecord-BlvKqeog.js + betRecord-lEtumWlD.css  (saasLottery/WinGoRecord)
    betRecord-C7U5PGur.js (K3Record), betRecord-DSxIbPHL.js (D5Record),
    betRecord-DTvv69-Z.js (TrxWinGoRecord), index-CqawRjzG.js (VideoWinGo)

WinGo/K3/D5/TrxWinGo/MotoRace ke MAIN game pages ke saare zaroori chunks
available hain — betting, result, countdown, history sab chalega. Ye missing
files sirf "record/history" jaise secondary pages ke hain. Inhe apne original
build/source se upload karna padega (source build ke bina safe recreate nahi
kiye jaa sakte). Tab tak un pages par blank screen ki jagah ek message dikhta
hai (shim), app crash nahi hota.


TEST KAISE KARNA HAI
--------------------
1. Browser me: https://<your-domain>/veegame_check.php
   Sab OK/aane chahiye. FAIL jo bhi ho wo report bhej dena.
2. Login -> home -> WinGo 30sec kholo. Period ka countdown + last results
   dikhne chahiye.
3. Ek chhota bet (₹1) lagao, balance kam hona chahiye aur bet record me aana
   chahiye.
4. Kaam hone ke baad veegame_check.php delete kar do.

Kuch bhi galat lage to:
   - browser me Ctrl+Shift+R (hard refresh),
   - error.log aur veegame_check.php ka output bhej dena.
