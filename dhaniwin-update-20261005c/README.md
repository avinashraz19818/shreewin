# Dhani.win — Update C (deposit / withdraw balance + demo-user dead page)

Date: 2026-10-05 · cumulative bundle (round 9 + 10 + 11 + 12)

Extract the zip into the **site root** and overwrite. Do **not** touch the
database: the first request repairs the old data by itself.

```
admin/…  api/…  js/…
README.md  PADHO-DHANIWIN-UPDATE.txt  changes.diff   ← docs, upload nahi karna
```

---

## What the user reported (round 12)

| Screen | Shown | Should be |
|---|---|---|
| Deposit page | Cash Balance **₹4**, Withdrawable **₹0** | same balance as header |
| Withdraw page | Cash ₹44,982, Withdrawable **₹0**, "Wager Required to Withdraw **₹4.11**", button blocked | withdrawable = balance, no wager line, button works |
| Admin → create demo user | browser lands on `https://dhaniwin.club9.eu.cc/admin/api.php` → *This site can't be reached* | stays in the panel, green message |

## Cause 1 — a saved snapshot answered for the money endpoints

The shipped build contains `api_responses`: saved reference answers that the
router is allowed to serve when no dynamic handler matches. Four of them were
registered **enabled** for money screens, and they contained a single number
for every player:

* `Recharge/GetRechargeBasicInfo` → `PlatForm` wallet **4.45**, `amountCoding` **4.11**
* `Withdraw/GetWithdrawBasicInfo` → `amountCoding` **4.11**
* `User/GetUserInfo` → `walletBalance` **0.00**

The router already skipped snapshots for the wallet endpoints, but the
**physical endpoint files called `api_get_override()` themselves**, so they
served the snapshot anyway. That is the whole bug: the deposit card read
`PlatForm` = 4.45 (₹4 after `Math.trunc` in the header) and the withdraw screen
read `amountCoding = 4.11 > 0` → it forced *Withdrawable 0* and disabled the
button.

### Fix (files)

| File | Change |
|---|---|
| `api/_bootstrap.php` | new `api_live_endpoints()` (29 money/wallet/history/gift/support/work-order endpoints) + `api_get_override()` returns `null` for them; `api_amount_coding()` reads the `wager_required_amount` setting (default 0); `api_set_default_settings()` zeroes `amount_coding`, one-time `UPDATE api_responses SET enabled = 0` for the live list (`money_snapshots_disabled` marker), seeds `wager_required_amount = '0'`, bounded `.wallet_unified` migration |
| `api/Recharge/GetRechargeBasicInfo.php` | now a router stub (197 B) instead of the snapshot reader |
| `api/User/GetUserInfo.php` | router stub |
| `api/User/GetUserFinancialList.php` | router stub |
| `api/Withdraw/GetWithdrawBasicInfo.php` | live payload: `balance` from `api_wallet_balance_of($user)`, `amountCoding` from `api_amount_coding()` |
| `api/_router.php` | uses the shared live list |

Result (probe on a copy of the shipped SQLite, user seeded with ₹5,000):

```
BEFORE  Recharge PlatForm=4.45 amountCoding=4.11 | Withdraw balance=5000 amountCoding=4.11 | UserInfo 0.00
AFTER   Recharge PlatForm=5000.00 amountCoding=0 | Withdraw balance=5000 amountCoding=0 | UserInfo 5000
```

No manual DB work: the cleanup is marker-guarded and runs on the first request.

## Cause 2 — demo create redirected the browser to `admin/api.php`

`admin/api.php`'s plain-POST branch redirected to `HTTP_REFERER` whenever it
contained `/admin/`. On the live host the referer is the panel page
(`/admin/?tab=…`), but the browser was being sent to `…/admin/api.php`
(unreachable) → dead page.

### Fix (files)

| File | Change |
|---|---|
| `admin/assets/js/admin-ajax.js` | every `form[action$="api.php"]` is submitted in the background (AJAX + `X-Requested-With`), then the current tab is reloaded with the flash message. The browser never navigates to `api.php` again |
| `admin/api.php` | referer accepted only when it is on the same host (otherwise `/admin/?tab=…`), redirect can never point at `api.php`, JSON fallback when `headers_sent()` |

Both paths verified: AJAX post → JSON `success:true`, plain form post →
`{"success":true,"redirect":"https://dhaniwin.club9.eu.cc/admin/?tab=demo_user&flash=…"}`
(never `api.php`), and a foreign referer falls back to the local tab URL.

## Proof run (wasm PHP 8.2 + real chunk code)

* `r12_frontend_test.cjs` — runs the real gate expressions from
  `js/withdraw-6gAiRB6t.js` and `js/recharge-kuO-BoSD.js`:
  before `[4.45, 0]` deposit / withdrawable `0`, button blocked;
  after `[5200, 5200]` and the button allowed. **6/6**
* `r12_sim.mjs` — end-to-end on the shipped DB: 10 screens all ₹5,000 for a
  logged-in user (deposit, withdraw, game, profile, header, wallet card,
  notify, GetUserInfo), anonymous → 0, withdrawal 110 → *Pending* and the
  wallet debited (5000 → 4890), demo user create / reset / list, plain-post
  + AJAX post + foreign referer, demo login sees ₹1,500 everywhere.
  **54/54**
* `r11_interceptor_test.cjs` — the patched WinGo chunk sends
  `Authorization: Bearer <site token>` (games token still wins when present,
  expired → fallback, logged out → empty). **4/4**

## Verify after upload (2 minutes)

1. Deposit page: Cash Balance **and** Withdrawable = header balance, no
   "4.11" wager line.
2. Withdraw page: Withdrawable = balance, a 110 request creates a *Pending*
   order.
3. Admin → Users → Demo User: create one, the panel comes back with a green
   message, and the demo user can log in with its demo balance.
4. WinGo page wallet card = header / profile balance.
5. Games / period / wager / results / records untouched.

## Revert

`changes.diff` applies cleanly to the fresh clone `a33a6d8`:

```bash
git apply -R changes.diff     # back to the original build
git apply changes.diff        # forward again
```
