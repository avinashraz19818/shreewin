# Dhani.win — WinGo / game page balance fix (2026-10-05, update-b)

Ye bundle **cumulative** hai: round-10 ka pura balance + admin panel fix
(48 files) + **WinGo game page ka balance fix** (1 naya file).
Extract karne par purana kuch nahi tootta — game / period / wager / result
code bilkul waisa hi hai.

---

## 1. Kya problem thi (round-11 report)

| Screen | Pehle | Ab |
|---|---|---|
| Profile | ₹5,200.00 ✔ | ₹5,200.00 |
| Home header | ₹5,200 ✔ | ₹5,200 |
| **WinGo / game page (Wallet card)** | **₹0.00** ✘ | **₹5,200** |

Game / period / wager / result sab chal raha tha — sirf game page ka
balance card galat tha.

## 2. Asli wajah (root cause)

WinGo aur baki game pages ka **apna alag API client** hai:
`js/WingoSkeleton.vue_vue_type_style_index_0_scoped_cd4f34e3_lang-CZJCWYiN.js`
(usme WingoSkeleton, wingo, trxWingo, D5, BettingPopup, Record, Trend — sab
import karte hain).

Woh client apna token **`ar_g_token`** naam ki storage key me dhoondhta hai:

```js
e.headers.Authorization = `Bearer ${h.get(m.TOKEN)}`   // m.TOKEN = "ar_g_token"
```

`ar_g_token` **is build me kabhi likha hi nahi jaata** — poori site ke `js/`
folder me ye naam sirf **1 baar** aata hai (us key ke naam ki definition me,
kisi write me nahi). Baki app `ar_token` use karta hai (`ar_lang`,
`ar_refresh_token` …), jo login par likha jaata hai.

Isliye game page ki har request jaati thi:

```
Authorization: Bearer null
```

Server ke liye "Bearer null" = **koi session nahi** → balance 0.00.

> Round 10 se pehle isi wajah se game page par **33,46,295.47** (database ka
> pehla user, id=1) dikh raha tha — bina token wali request ko server chupke
> pehla user maan leta tha. Ye fallback round-10 me hata diya gaya, isliye
> ab wahi request 0.00 dikhati hai. **Fix token dene wala hai, strictness
> revert karne wala nahi.**

## 3. Fix (1 file, 3 lines)

`js/WingoSkeleton.vue_vue_type_style_index_0_scoped_cd4f34e3_lang-CZJCWYiN.js`

1. **Request interceptor** — token ab game namespace ya app namespace, jo bhi
   mile, use hota hai:
   ```js
   e.headers.Authorization = `Bearer ${h.get(m.TOKEN) || localStorage.getItem("ar_token") || ""}`
   ```
2. **Exposed `token`** computed (live socket / game URLs ke liye) — same fallback.
3. **`getUserInfo` gate** — pehle `ar_g_token` ke bina user info fetch hi
   skip ho jaata tha; ab app token se bhi hota hai.

Agar bhavishya me kisi build me `ar_g_token` set hota hai (game sub-site
login), to pehle wahi use hoga — fallback sirf tab chalta hai jab woh na ho.
Game / bet / period / result logic ko chhua nahi gaya.

## 4. Is bundle me kya-kya hai

* `js/WingoSkeleton…-CZJCWYiN.js` — WinGo balance fix (**naya**)
* Round-10 ke 48 files (balance ek jagah + admin panel ke saare fixes)
  — `api/_bootstrap.php`, `api/_router.php`, `api/ThirdGame/*`,
  `api/Lottery/*`, `api/Recharge/*`, `api/Withdraw/*`, `admin/*` (wahi files
  jo pichhle bundle me thi)
* `README.md`, `PADHO-DHANIWIN-UPDATE.txt`, `changes.diff`

## 5. Test report (sandbox me actually chalaya gaya)

**A. Client test** — shipped chunk ko nikal kar chala kar dekha gaya ki browser
kaun sa Authorization header bhejega:

| Case | Pehle (purana chunk) | Ab (fixed chunk) |
|---|---|---|
| Login kiya hua (localStorage me `ar_token`) | `Bearer null` ✘ | `Bearer local_1085…028` ✔ |
| `ar_g_token` bhi maujood | `Bearer games_xyz` | `Bearer games_xyz` (wahi) |
| `ar_g_token` expired | `Bearer null` ✘ | `Bearer local_1085…028` ✔ |
| Bilkul logged out | `Bearer null` | `Bearer ` (server guest maanta hai) ✔ |

**B. Server test** — admin se naya ID banaya, Adjust Balance se **₹5,000**,
aur wahi header bhej kar saare balance endpoints:

| Screen | Endpoint | Value |
|---|---|---|
| Game page | `Lottery/GetBalance` | 5000.00 |
| Game page | `Lottery/GetUserInfo` | 5000.00 |
| Home header | `ThirdGame/GetARGameAndPlatWallets` | ARGame 5000 + PlatForm 0 = **5000.00** |
| Profile | `ThirdGame/RecoverSaasBalance` | 5000.00 |
| Wallet | `ThirdGame/GetARGameBalance` | 5000.00 |
| Site profile | `User/GetUserInfo` | 5000.00 |
| Withdraw | `Withdraw/GetWithdrawBasicInfo` | 5000.00 |
| Deposit | `Recharge/GetRechargeBasicInfo` | 5000.00 |

* Broken/empty token aur anonymous par har jagah **0.00** — kisi doosre user ka
  paisa kahin nahi (id=1 ka 33,46,295.47 test me rakha gaya tha, ek baar bhi
  nahi aaya).
* Login kiya hua bet 10 → dono column 4990 (ek hi pot), anonymous bet refuse.
* Row id=1 ke column bilkul untouched.

**C. Regression** — round-9 (admin panel, gift code, demo user, 46 tabs) aur
round-10 (balance) ke saare sims dobara chalaye gaye — sab pehle jaise pass.

Total: **35/35 round-11 checks pass**, purane sims bhi green.

## 6. Install

1. ZIP ko site ke **root** folder me extract karo (overwrite = HAAN).
2. **Database ko na chhedo** — na delete, na import. Purana DB khud repair
   ho jaata hai.
3. Site kholo, `Ctrl + F5` (ya phone par browser cache clear) kar do —
   `.htaccess` already `js/css` par `no-cache` bhejta hai, isliye naya file
   turant load hoga.

## 7. Check (1 minute)

1. Login karo → **WinGo** page kholo → Wallet card par wahi balance jo
   profile/header par hai.
2. Home header, profile, deposit, withdraw — sab par **same** number.
3. Ek game/period chalne do — sab pehle jaisa (kuch nahi badla).
4. `admin/` → Users → Adjust Balance +1000 → sab screens par +1000.

Koi dikkat ho to screenshot bhej dena — turant dekh lenge.
