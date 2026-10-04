# Dhani.win — Admin Panel + Balance fix (2026-10-05)

Final bundle. Isme **do cheezein** hain:

1. **Admin Panel ka pura fix** (har menu option apna page + apna backend).
2. **Balance ka asli fix** — game page, home header, profile, deposit aur withdraw
   page, sab jagah **ek hi number** dikhega.

Site ka game / period / wager / result code chhua nahi gaya hai.

---

## A. Balance issue (jo abhi report hua)

### Asli problem (3 alag-alag source)

| Screen | Endpoint | Pehle kya dikha raha tha |
|---|---|---|
| Game page (WinGo wallet card) | `Lottery/GetBalance` | game wallet |
| Home header | `ThirdGame/GetARGameAndPlatWallets` ka **sum** | dono wallet jodkar = **2× paisa** (₹5,000 add karo → ₹10,000) |
| Profile / wallet | `ThirdGame/RecoverSaasBalance` | response me `balance` key hi nahi thi → **₹0** |
| Deposit page | `Recharge/GetRechargeBasicInfo` → `gameSaasBalance[PlatForm]` | duplicate row |
| Withdraw page | `Withdraw/GetWithdrawBasicInfo` | purane column se value |

Aur ek bada bug: jis request ke saath **valid token nahi hota** (purana / expired /
`Bearer undefined`), server chupke se **database ka pehla user (id=1)** maan leta
tha — isliye ek account ka balance (jaise ₹33,46,295.47) doosre user ki screen par
dikhta tha.

### Ab kya hota hai

* `api_wallet_balance_of()` = wallet + game ka **ek** source; purane bikhre rows
  khud merge ho jaate hain (wallet == game).
* Header ka sum = asli balance (ARGame row me paisa, PlatForm row 0 — jaisa
  original site ke reference data me tha).
* `RecoverSaasBalance` / `Transfer` / `NotifyARGameRecover` / `GetARGameBalance`
  sab me `balance` wahi ek value.
* `Lottery/GetBalance` (game page) bhi wahi value.
* Deposit page `gameSaasBalance` ka PlatForm row = wahi value (ARGame row 0).
* Withdraw page balance = wahi value.
* **Koi session nahi / token expired** → per-user endpoints 0 ya "Please login"
  dete hain; kisi doosre user ka paisa **kabhi** nahi dikhega.
* Bet / withdraw / deposit / gift claim bina valid session ke refuse ho jaate hain
  (pehle wo chupke user id=1 par likh dete the).
* Admin panel ka **Adjust Balance** dono columns (wallet + game) ek saath update
  karta hai, aur usi request me aage padhne wale code ko fresh value milti hai.

---

## B. Admin Panel fix (round 9)

1. **Dead tabs fix**: `add_upi`, `usdt_rate`, `add_usdt`, `add_upi_image`,
   `add_usdt_image`, `upi_withdraw`, `withdraw_sent`, `withdraw_reject`,
   `support_deposit/withdraw/ifsc/bank/game`, `bonus_manage`, `admin_password`,
   `banned_users`, `demo_user`, `agent_user` — pehle sirf "go to Settings"
   placeholder tha, ab pura page + form + backend (46 tabs, 0 placeholder).
2. **Demo user**: `/admin/?tab=demo_user` — create / reset balance / delete, aur
   site par normal login.
3. **Agent user**: `/admin/?tab=agent_user` — create, rate, enable/disable.
4. **Banned users**: alag tab + user list me Ban/Unban; banned login blocked.
5. **Gift code**: create → user claim (case-insensitive) → wallet credit →
   redemption log; duplicate aur expired code reject.
6. **Finance**: deposit approve (+ first deposit bonus), withdraw approve/reject
   with refund + remarks, UPI/USDT gateway CRUD, gateway image upload
   (`/uploads/gateways/`), USDT rate converter.
7. **Support**: 5 category queues, admin reply ↔ user comment
   (`WorkOrder/*` ↔ `ticket_replies`), close ticket.
8. **Schema self-heal** (`api/_bootstrap.php`, marker `2c`): purana database
   pehli request par khud repair ho jaata hai — 33 tables + naye columns
   (is_demo, remarks, qr_image, bonus_amount, ...). Manual SQL import nahi chahiye.
9. **USDT wallet add** ka SQL error (9 values for 8 columns) fix.
10. Multi-value forms (bonus percent + max, USDT min + max) alag-alag single-key
    forms me — ab dono values save hoti hain.

---

## Install (cPanel)

1. Ye ZIP site ke **root folder** me extract karo (overwrite = yes).
2. **Database ko delete / import nahi karna.** Purana DB khud repair hota hai.
3. `/admin/` kholo. Panel ka cache (browser) clear kar do (Ctrl+F5).

Zip me sirf badle hue files hain: `admin/**` aur `api/**` — koi DB file nahi.
