VEERGAME — INSTALL / UPLOAD CHECKLIST
=====================================
ZIP: veergame-cpanel-fix-20260914.zip


1) BACKUP (2 minute ka kaam, skip mat karo)
   cPanel > File Manager > apne site folder me jao > purane files ka backup
   banao (ya cPanel Backup Wizard se full backup).


2) UPLOAD
   cPanel > File Manager > site ke document root me jao
   (jahan index.html / index.php hai).
   ZIP upload karo -> right click -> Extract -> Overwrite/Replace = haan.

   Command line se karna ho to:
       cd ~/public_html
       unzip -o veergame-cpanel-fix-20260914.zip


3) PERMISSIONS (sirf agar 500 error aaye)
   PHP files 644, folders 755. cPanel me hote hi default yahi hote hain.


4) ORIGINAL VIDEOS/VIDEO CHUNKS (optional, secondary pages)
   missing-frontend-files.md me wo files listed hain jo is ZIP me nahi hain
   (WinGoRecord type "history" pages). Ye tumhare original build me hongi —
   unhe assets/js/ aur assets/css/ me upload kar dena. Main lottery game
   (WinGo/K3/D5/TrxWinGo/MotoRace) in files ke bina bhi chalega.


5) FINAL STEP — check karo
   Browser me kholo:   https://<your-domain>/veegame_check.php

   Dikhna chahiye: zyada tar "OK". Sirf ye warn normal hain:
       WARN mysqlnd driver ...        (patch handle karta hai)
       WARN mysqli_stmt::get_result   (patch handle karta hai)
       WARN table saas_lottery_*      (pehle bet par ban jaati hai)
   FAIL dikhe to wo line bhej do.

   Uske baad:
       - Ctrl+Shift+R (hard refresh) karo
       - login -> WinGo 30 sec kholo -> countdown + last result dikhna chahiye
       - ₹1 ka bet lagao -> balance kam ho aur betting record me bet aaye

   Sab chal jaye to veegame_check.php delete kar dena.


ZIP ME KYA-KYA HAI
------------------
  index.html  (patched: API bridge + relative VITE_API_URL)
  same-origin-lottery.js          (NAYI - saare API/draw calls ko sahi
                                   endpoints par le jaati hai)
  mysqli_compat.php               (NAYI - get_result() ka portable fix,
                                   PHP 7.4 support, mysqlnd ke bina chalega)
  developer-maruf/error_logger.php (RESTORE - iske missing hone se PHP fatal
                                   error aata tha aur lottery API 500 deta tha)
  veegame_check.php               (NAYI - deployment diagnostic)
  web/config, web/config.js, web/config.html, web/.htaccess  (RESTORE)
  favico.ico, icon-192x192.png, icon-512x512.png             (RESTORE)
  VEERGAME_FIX_REPORT.md         (kya problem thi / kya change hua)
  missing-frontend-files.md      (jo files original build se chahiye)
  assets/        (saare frontend chunks + patched bundles)
  api-live-v4/, draw-live-v4/, saas_lottery/   (lottery backend)
  developer-maruf/, evenvessis/, pay/, digitaladmin/ (PHP code)
  digitaladmin/ ke static theme assets ZIP me nahi hain (55 MB, kabhi badle
  nahi jaate, server par already hain) — unhe touch karne ki zaroorat nahi.
  Purane error logs ZIP me nahi hain (server par waise hi rehne do).


USKE BAAD BHI KAAM NA KARE TO
-----------------------------
1) veegame_check.php ka poora output bhej dena
2) browser: F12 -> Console + Network tab me jo red lines aaye wo bhej dena
3) server par error log: public_html/developer-maruf/error_log
   (ye ZIP ke baad ban jaaye to uska last 50 lines bhej dena)
