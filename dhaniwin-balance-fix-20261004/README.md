# Dhaniwin — Balance / Wallet / Deposit Fix (build 20261004)

Fixes the four reported problems:

1. Admin panel se balance add karne par balance show nahi hota tha.
2. Deposit accept (Approve) karne ke baad bhi balance add nahi hota tha.
3. Registration par milne wala free ₹4.45 (game balance) completely hata diya.
4. Registration ke baad wallet me dikhne wala ₹4 game (WinGo) me show nahi hota tha.

Ab **wallet aur game dono me hamesha same live balance** dikhta hai, aur balance
update hone ke baad dobara login karne ki zarurat nahi (server ab snapshots
nahi, seedha database se value deta hai).

## Install (cPanel)

1. `dhaniwin-balance-fix-20261004.zip` download karo.
2. `public_html/` ke andar extract karo — same paths overwrite ho jayenge:
   - `api/_bootstrap.php`
   - `api/_router.php`
   - `api/User/GetUserInfo.php`
   - `api/User/GetUserFinancialList.php`
   - `api/Recharge/GetRechargeBasicInfo.php`
   - `api/Withdraw/GetWithdrawBasicInfo.php`
   - `api/Withdraw/GetArbWalletInfo.php`
   - `admin/controllers/UserController.php`
   - `admin/controllers/FinanceController.php`
   - `admin/controllers/AgentController.php`
3. Bas. Koi database file ZIP me nahi hai — **aapka data safe rehta hai**.
   Pehli request par code khud hi missing table/column bana leta hai
   (`wallet_logs`, `agent_commissions`, `user_control`, `api_users.referrer_id` …).
4. Ek baar koi bhi page kholo (ya admin panel), phir admin se balance add karke
   aur deposit approve karke test karo.

## Root cause (kya galat tha)

| # | Problem | Root cause |
|---|---------|------------|
| 1 | Admin se balance add → kuch nahi hota | Har balance write `wallet_logs` me log insert karta tha, lekin purane database me wo table **hi nahi thi**. Insert fail → transaction rollback → balance update bhi cancel. (Test: `no such table: wallet_logs`, balance 2.25 hi rehta tha.) |
| 2 | Deposit approve → balance nahi | Wahi `wallet_logs` rollback — order "Approved" bhi nahi hota tha. |
| 3 | Free ₹4 registration | `game_balance` ke default me `4.45` hardcoded tha (register, admin create, seed user, settings). Sab 0 kar diya. |
| 4 | Wallet aur game alag | Do alag columns (`wallet_balance` = site wallet, `game_balance` = WinGo) me kabhi sync nahi hote the + balance endpoints saved snapshots (`api_responses`) se purani value dikha rahe the (wallet ₹4.45, game ₹0). |
| 5 | Fresh install dead | `api_ensure_schema()` me `$marker` variable undefined tha → PHP 8 fatal (TypeError). Naya database banne par poori API band ho jaati thi. |
| 6 | Header ka game balance 0 | `ThirdGame/GetARGameBalance` ek number return karta tha, lekin site `data.arGameBalance` padhti hai → hamesha 0. |

## Kya badla (code)

- **`api/_bootstrap.php`**
  - Naye helpers: `api_wallet_balance_of()`, `api_wallet_apply_change()`,
    `api_wallet_mirror_user()`, `api_wallet_log_insert()`,
    `api_schema_columns()`, `api_ensure_columns()`, `api_wallet_migrate_balances()`.
  - Schema self-heal: `wallet_logs`, `agent_commissions` aur chhoote hue
    `api_users` columns (referrer_id, status, vipLevel, password, token,
    token_expire) purane DB par bhi ban jaate hain. Poori schema check hoti hai
    (pehle sirf `api_users` dekha jata tha) aur pura creation block
    try/catch me hai — koi ek statement fail ho to site down nahi hoti.
  - Balance likhne wale **saare** paths ab dono columns mirror karte hain:
    admin adjust, deposit approve (+ first deposit bonus), withdrawal refund,
    withdraw request, lottery bet deduct, settlement credit, wheel reward,
    agent commission, thirdgame transfer.
  - Ek baar chalne wala merge: jis account me wallet aur game alag the, dono ka
    **jod** kar dono columns me same value kar di jaati hai (koi paisa nahi
    katta; setting `wallet_game_unified=1` ke saath sirf ek baar).
  - `wallet_logs` insert best-effort hai — log fail ho to bhi balance change
    commit hota hai (rollback khatam).
  - Free ₹4.45 sab jagah se hataya (DDL defaults, seed user, register,
    admin create, `default_game_balance` setting).
  - `ThirdGame/GetARGameBalance` ab `{arGameBalance, balance, currency}`
    return karta hai.
  - Undefined `$marker` fix (fresh install crash).
- **`api/_router.php`** — balance endpoints ke liye saved snapshot bypass:
  `User/GetUserInfo`, `User/GetUserFinancialList`, `Home/CheckCanBet`,
  `ThirdGame/GetARGameBalance`, `ThirdGame/GetARGameAndPlatWallets`,
  `ThirdGame/RecoverSaasBalance`, `ThirdGame/NotifyARGameRecover`,
  `ThirdGame/Transfer`, `Recharge/GetRechargeBasicInfo`,
  `Withdraw/GetWithdrawBasicInfo`, `Withdraw/GetArbWalletInfo`.
  (Baaki 52 endpoints ke recorded responses waise hi chalte hain.)
- **Physical endpoint files** — `User/GetUserInfo.php`,
  `Recharge/GetRechargeBasicInfo.php`, `User/GetUserFinancialList.php`,
  `Withdraw/GetWithdrawBasicInfo.php`, `Withdraw/GetArbWalletInfo.php`: apna
  override lookup hataya, live payload (DB se) return karte hain. Transaction
  list ab `api_user_financial_payload()` (recharges + withdrawals + bets) se
  banti hai, hardcoded 4.45 rows hata di gayi.
- **`admin/controllers/UserController.php`** — `adjustBalance()` ab shared
  helper use karta hai; naya user 0/0 balance ke saath banta hai.
- **`admin/controllers/FinanceController.php`** — recharge approve aur
  withdrawal refund shared helper se (mirror + best-effort log).
- **`admin/controllers/AgentController.php`** — commission credit dono columns
  me.
- **`api/storage/dhaniwin.sqlite`** (sirf repo me, ZIP me nahi) — repaired:
  `wallet_logs` + `agent_commissions` + missing columns add, 10 purane balance
  snapshots disable, `default_game_balance=0`.

## Tests (php-wasm + real PDO/SQLite, shipped DB copy + fresh DB)

| Test | Before | After |
|------|--------|-------|
| Admin: wallet +100 | `false` — *no such table: wallet_logs*, balance 2.25 | ✅ success, wallet=game=102.25 |
| Admin: game +100 | same failure | ✅ success, wallet=game=102.25 |
| Deposit ₹500 approve (first deposit, +10 % bonus ₹50) | fail, order Pending, balance 2.25 | ✅ order Approved, wallet=game=552.25, wallet_logs row |
| Wallet vs game | wallet 2.25 / game 0 | ✅ dono 2.25 |
| `GET /api/User/GetUserInfo` (router) | snapshot: `walletBalance: 0` | ✅ live `2.25` |
| `POST /api/ThirdGame/GetARGameAndPlatWallets` | snapshot: ARGame 4.45 / PlatForm 0 | ✅ live dono 2.25 |
| `POST /api/ThirdGame/GetARGameBalance` | bare `0` (site padhti hai `data.arGameBalance` → 0) | ✅ `{arGameBalance: 2.25, balance: 2.25}` |
| Bet ₹1 (WinGo 1M) | game 2.25 → (game only) | ✅ wallet=game=1.25 (dono se) |
| Settle winning bet | credit sirf game me | ✅ wallet=game=11.07 (dono me 8.82 credit) |
| Transfer ₹1 / recover ₹0.50 | wallets alag ho jaate the | ✅ dono same rehte hain, paisa nahi katta |
| Register | `game_balance = 4.45` (aur purane DB par `no column referrer_id` se fail) | ✅ 0/0, register chal gaya |
| Fresh DB bootstrap | Fatal TypeError (`$marker` null) | ✅ saari tables ban gayi (wallet_logs ke saath) |
| Purani partial DB repair | `user_control`/`admin_users` missing → admin list khaali | ✅ tables auto-create, admin list + adjust balance chal gaya |
| PHP syntax (php-parser) | — | ✅ 10/10 files OK |

`changes.diff` = `git diff` (a33a6d8 → fix commit), 1033 lines (binary sqlite
excluded).

## Not touched

Game/period/wager/issue/result/settlement ki logic bilkul waise hi hai
(sirf balance mirror add hua hai, amounts same hain — test me bet ₹1 aur
win rate 9× (−2 % fee = 8.82) pehle jaisa hi nikla).

## Chhota note

Purane accounts me agar free ₹4.45 (jo registration par mila tha) hataana ho,
to bolo — ek chhoti script bana dunga (sirf un accounts se jinka balance
exactly 4.45 ho, jinhone recharge/bet nahi kiya).

## Download / delivery links

- GitHub (repo `shreewin`, branch `arena/01a09f38-shreewin`, tag `dhaniwin-balance-fix-20261004`):
  `https://github.com/avinashraz19818/shreewin/blob/arena/01a09f38-shreewin/dhaniwin-balance-fix-20261004/dhaniwin-balance-fix-20261004.zip`
- Direct raw: `https://github.com/avinashraz19818/shreewin/raw/arena/01a09f38-shreewin/dhaniwin-balance-fix-20261004/dhaniwin-balance-fix-20261004.zip`
- Live download page (sandbox preview): `https://8080-ilsl464ttnng96hhkr3iq.e2b.app/`
- Note: `dhaniwin` repo par push 403 (read-only token) — isliye patch `shreewin` repo me publish hui hai.

Package: `dhaniwin-balance-fix-20261004.zip` (PHP files + README + PADHO notes + changes.diff).
