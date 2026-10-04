# Dhani.win — Admin Panel fix (2026-10-05)

Round 9 fix bundle. Admin panel ke saare dead tabs ab real pages hain aur unka
backend bhi check karke fix kiya gaya hai. Site ka game/period/wager code chhua
nahi gaya.

## What was broken

1. **Bahut se menu options "Settings" jaisi jagah kholte the ya khaali page** —
   `add_upi`, `usdt_rate`, `add_usdt`, `add_upi_image`, `add_usdt_image`,
   `upi_withdraw`, `withdraw_sent`, `withdraw_reject`, `support_deposit`,
   `support_withdraw`, `support_ifsc`, `support_bank`, `support_game`,
   `bonus_manage`, `admin_password`, `banned_users`, `demo_user`, `agent_user`
   par sirf ek placeholder dikhta tha ("go to Settings").
2. **Demo user create** karne ka koi independent option nahi tha.
3. **Gift code** claim karne par balance credit nahi hota tha (claim endpoint
   saved snapshot se answer kar raha tha).
4. **Ban** kiye gaye user phir bhi site par login kar lete the.
5. **USDT wallet add** karte hi SQL error (`9 values for 8 columns`).
6. **Purane database** par naye columns/tables missing the, isliye admin actions
   `no such column` error dete the.
7. Admin ke chat/reply, withdrawal approve/reject, deposit approve jaise flows
   par data save nahi hota tha ya stale dikhta tha.

## What is fixed

* Har menu option ka apna page + apna form hai (46 tabs, koi placeholder nahi).
* Demo user: `/admin/?tab=demo_user` se seedha create / reset balance / delete.
* Agent user: `/admin/?tab=agent_user` se create, rate change, enable/disable.
* Banned users: alag tab + member list me Ban/Unban button; banned login blocked.
* Gift code: create -> user claim -> wallet credit -> redemption log
  (`/admin/?tab=gift_code` ka "Redemption Log").
* Finance: deposit approve (+first deposit bonus), withdrawal approve/reject
  (refund), UPI/USDT gateway add/edit/delete, gateway image upload
  (`/uploads/gateways/`), USDT rate + converter.
* Support: 5 category queues (deposit/withdraw/IFSC/bank/game), chat reply,
  close ticket; site ke `WorkOrder/*` se do-taraf message sync.
* Patch/multi-value forms theek kiye (bonus percent + max, USDT min + max).
* Schema self-heal (`api_ensure_schema`, marker `2c`): purana database khud
  repair ho jaata hai — koi manual SQL import nahi chahiye.
* Plain HTML form post par ab JSON ki jagah redirect + flash message.
* `api/_router.php` + saare endpoint entry files `require_once` (double include
  par fatal error nahi).
* Login guard: banned (status = 0) account login nahi kar sakta.

## Install (cPanel)

1. Ye ZIP site ke root me extract karein (overwrite karein) — `admin/` aur
   `api/` folders replace honge.
2. **Database ko haath nahi lagana hai** — na delete, na import. Pehli request par
   code khud missing tables/columns add kar leta hai (marker `.schema_v2 = 2c`).
3. `/admin/` kholein aur `admin` / `admin123` (ya jo bhi apka password hai) se
   login karein.
4. `/uploads` folder writable hona chahiye (gateway image upload ke liye), warna
   image URL wala option use karein.

## Quick test checklist

| Test | Expected |
| --- | --- |
| Demo User -> create `demo1` | User banta hai, balance dono jagah (wallet + game) same |
| Demo user se site login | Login chal jaata hai |
| Banned Users -> kisi user ko block | Us user ka login block |
| Gift code create (e.g. `WELCOME500`) | Site par redeem karne par balance credit + Redemption Log me entry |
| Deposit Update -> approve | Balance credit + first deposit bonus |
| UPI Withdraw -> Reject | Amount user ko wapas (refund) |
| Add UPI / Add USDT | Site ke deposit page par method dikhe |
| Add UPI Image -> upload | Deposit page par QR image dikhe |
| Support Deposit -> Chat reply | Site ke ticket me reply dikhe |
| Admin Password -> change | Naye password se login |

Tested: PHP lint (all changed files), fresh DB + purane DB dono par end-to-end
simulate kiya gaya, aur round 8 ke saare regression scenarios dobara pass hue.
