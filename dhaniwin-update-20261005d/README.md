# Dhani.win — Update D (admin dashboard + agent commission)

Date: 2026-10-05 · cumulative bundle (rounds 9–13). Extract into the **site
root** and overwrite. Do not touch the database — the first request repairs the
old rows by itself.

## Why this build exists

After Update-C shipped, the whole admin panel was re-tested action by action and
page by page (the user's standing rule: every button/page must work). Two real
back-end bugs showed up that had nothing to do with the balance work but broke
visible panel features on **both** database drivers:

| Feature | Broken query | Effect |
|---|---|---|
| Admin → Dashboard tiles | `WHERE created_at >= DATE('now','start of day') OR created_at >= CURDATE()` | MySQL rejects `DATE('now','start of day')`, SQLite rejects `CURDATE()` → the try/catch returned `{"error": …}` and every tile (users, today's recharge, today's withdraw, pending, today's profit) showed nothing |
| Admin → Agents → agent detail, "Today's commission" | same mixed dialect `(created_at >= DATE('now','start of day') OR created_at >= CURDATE())` | today's commission threw, so the agent screen reported no commission for today |

### Fix (driver-aware "today")

`admin/controllers/DashboardController.php` and
`admin/controllers/AgentController.php` now build the condition from the live
driver, exactly the way `getChartsData()` already did it:

```php
$driver = api_db_driver($pdo);
$todayStart = $driver === 'mysql'
    ? "created_at >= CURDATE() AND created_at < DATE_ADD(CURDATE(), INTERVAL 1 DAY)"
    : "date(created_at) = date('now')";
```

`getStats()` uses it for today's users / recharge / withdraw; `getAgentSummary()`
uses it for today's commission. The live host (`api/config.php` → MySQL
`club532583_cobra`) and a SQLite fallback both work now.

Nothing else changed: no game, bet, period, wager or result code was touched.

## Test run (wasm PHP 8.2 against a copy of the shipped SQLite)

| Suite | Result |
|---|---|
| `r12_admin_sweep.mjs` — every one of the 60 POST actions of `admin/api.php` called in its own process | **60/60 clean** (users, demo user, balance adjust, ban, admins, password change, settings, payment/USDT + gateway image upload, gift codes, recharge/withdraw approvals + bulk, tickets create/reply/close, game queue, CSV export) |
| `r12_pages.mjs` — all 46 panel tabs rendered through `admin/index.php` | **46/46 rendered clean**, no PHP warning/fatal, and `DashboardController::getStats()` returns real numbers instead of the SQL error |
| `r13_agent_test.mjs` — agent summary with one commission today + one 3 days old | today = 12.5, total = 111.5, no error |
| `r12_sim.mjs` — end-to-end balance/demo-user flow | **54/54** |
| `r12_frontend_test.cjs` — the real client gate expressions | **6/6** (deposit/withdraw 5200 vs old 4.45/0) |
| `r11_interceptor_test.cjs` — WinGo chunk token | **4/4** |
| `r12_probe.mjs` — money endpoints against the shipped DB | PlatForm 5000, amountCoding 0, snapshots `enabled=0` |

## Files in this bundle

* new in this round: `admin/controllers/DashboardController.php`,
  `admin/controllers/AgentController.php`
* everything from Update-C: `api/_bootstrap.php`, `api/_router.php`,
  `api/Recharge/GetRechargeBasicInfo.php`, `api/User/GetUserInfo.php`,
  `api/User/GetUserFinancialList.php`, `api/Withdraw/GetWithdrawBasicInfo.php`,
  `admin/api.php`, `admin/assets/js/admin-ajax.js`,
  `js/WingoSkeleton…CZJCWYiN.js`, plus the earlier rounds' 44 files
* docs: `README.md`, `PADHO-DHANIWIN-UPDATE.txt`, `changes.diff`

53 files total. `changes.diff` applies cleanly to the fresh clone `a33a6d8`:

```bash
git apply -R changes.diff     # back to the original build
git apply changes.diff        # forward again
```
