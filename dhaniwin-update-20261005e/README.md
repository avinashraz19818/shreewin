# Dhani.win — FINAL ALL-IN-ONE bundle (Update E)

Date: 2026-10-05 · **one zip with every fix from the beginning** (earlier builds
were cumulative too, but this one also carries the two fixes that had slipped
out: the ARB wallet balance and the deposit-credit safety net).

Extract into the **site root** and overwrite. Do **not** touch the database —
the first request repairs the old rows by itself. Hit `Ctrl+F5` once after
uploading.

## Everything that is fixed (by round)

| # | Reported problem | Root cause | Fix |
|---|---|---|---|
| 1 | Admin credits a balance, nothing shows | every balance write inserted into `wallet_logs`, a table missing on the old DB → insert failed → the whole transaction rolled back | schema self-heal creates the table; the log write is best-effort and never cancels the balance change |
| 2 | Deposit approved, balance not added | same rollback + the credit result was ignored | credit now goes through `api_wallet_apply_change()` and is **verified**; if it cannot be applied the order is **not** marked approved |
| 3 | Free ₹4.45 welcome balance | hard-coded default in registration / seed / admin create | 0 everywhere, and old `4.45` settings rows are repaired |
| 4 | Wallet and game showed different money | two columns were never kept in sync | one pot of money, both columns always mirror; marker-guarded one-time merge |
| 5 | Balance changed but the site kept showing the old value | endpoints answered from saved `api_responses` snapshots | 29 money/admin endpoints are a shared *live* list — `api_get_override()` refuses snapshots for them and the stale rows are disabled once |
| 6 | Fresh install died (blank API) | undefined `$marker` in `api_ensure_schema()` (PHP 8 fatal) | guarded; verified on an empty database |
| 7 | Header game balance 0 | `ThirdGame/GetARGameBalance` returned a bare number while the site reads `data.arGameBalance` | payload now `{arGameBalance, balance, currency}` |
| 8 | WinGo / game page ₹0.00 while profile showed the real balance | game client looked for `ar_g_token` (never written) → `Authorization: Bearer null` → guest | the chunk now falls back to the app session token (`ar_token`); games token still wins when present |
| 9 | Deposit page **Cash Balance ₹4** / Withdrawable ₹0 | the shipped `Recharge/GetRechargeBasicInfo.php` served the saved snapshot (PlatForm **4.45**) | the endpoint is router-backed and reads the DB |
| 10 | Withdraw page **Withdrawable ₹0** + "Wager Required to Withdraw **₹4.11**", button blocked | `amountCoding` 4.11 (legacy hard-code) makes the client show 0 withdrawable | `api_amount_coding()` reads the `wager_required_amount` setting (default 0) and the legacy 4.11 rows are zeroed once |
| 11 | Demo user create → *This site can't be reached* (`/admin/api.php`) | plain form POST redirected the browser to `HTTP_REFERER`, which on the live host is an unreachable `api.php` URL | every panel form submits in the background (`admin-ajax.js`) and reloads the tab with a flash; the server only honours a same-host referer, never redirects to `api.php`, and answers JSON when headers are already sent |
| 12 | Admin Dashboard tiles empty / error | one query mixed SQLite (`DATE('now','start of day')`) and MySQL (`CURDATE()`) syntax → fails on **both** drivers | driver-aware `$todayStart` (like `getChartsData()` already did) |
| 13 | Agents → Today's commission error | same mixed-dialect query | same driver-aware fix |
| 14 | ARB wallet balance always 0 | `Withdraw/GetArbWalletInfo.php` had lost its earlier fix (snapshot override + `balance => 0`) | live balance from the DB; still refused for snapshots |
| 15 | Risk: an unrecognised status spelling (e.g. `1`) marked a deposit approved **without** crediting | status string compared literally | statuses normalised (`1`→Approved, `2`→Rejected, …) for deposits and withdrawals; refund on reject is verified too |
| 16 | Risk: legacy order rows storing the public `user_id` could not be credited | lookup used the row id only | `api_wallet_find_user()` resolves either id |
| 17 | **Bet placed → win/loss declared immediately** | settlement waited for `api_lottery_issue_closed()` even though the issue list deliberately runs one period behind upstream, so the round being bet on has *already* been drawn | `api_lottery_settle_bet()` gained a `force` flag; `api_lottery_place_bet()` settles the new bet right away and returns `resultStatus` / `isWin` / `winAmount` / `result`; `Lottery/GetWinLossResult` answers a decided result instead of "pending". The shared game composable now reacts to `mapBet.set(issue)` (what every game chunk does the instant a bet is accepted) by refreshing history and opening the win/loss popup — no waiting for the countdown |
| 18 | Old "wait for the period" behaviour still reachable | — | set `instant_result = 0` in `api_settings` |

Games, periods, wagers, results and records were not touched.

## Verification (wasm PHP 8.2 against copies of the shipped SQLite)

| Suite | Result |
|---|---|
| `r12_admin_sweep.mjs` — all 60 POST actions of `admin/api.php` | **60/60 clean** |
| `r12_pages.mjs` — all 46 panel tabs + dashboard numbers | **46/46 clean** |
| `r14_money_flow.mjs` — approve/reject credit & refund, bonus, numeric + legacy ids | **12/12** |
| `r14_fullfix_test.mjs` — empty DB self-heal, no ₹4.45, deposit credit, ARB wallet | **9/9** |
| `r12_sim.mjs` — 10 screens, anonymous, withdrawal, demo user, plain/AJAX posts | **54/54** |
| `r12_frontend_test.cjs` — the real client gate expressions | **6/6** (5200 vs old 4.45 / 0) |
| `r11_interceptor_test.cjs` — WinGo chunk token | **4/4** |
| `r13_agent_test.mjs` — agent today/total commission | pass |
| `r12_probe.mjs` — money endpoints, snapshots `enabled=0` | pass |
| `r15_instant_result.mjs` — bet → declared result, balance maths, records, force_win, legacy switch, 20 bets | **23/23** |
| `r15_frontend_instant.mjs` — real shipped chunk in a VM: `mapBet.set()` → history → `GetWinLossResult` → popup | **12/12** (pristine build opens nothing, shipped build opens win and loss popups) |

## Files

54 modified + 1 new (`admin/views/support-chat-modal.php`) = 55 source files:
`admin/api.php`, `admin/index.php`, `admin/assets/js/admin-ajax.js`,
`admin/views/sidebar.php`, `admin/views/support-chat-modal.php`,
`admin/controllers/*` (9), `api/_bootstrap.php`, `api/_router.php`,
`api/**` endpoints (37), `js/WingoSkeleton…CZJCWYiN.js`,
`js/BetRule-DyIqbhnc.js` (the shared WinGo/K3/5D/MotoRace game composable).
`changes.diff` applies cleanly to the fresh clone `a33a6d8`
(`git apply -R changes.diff` reverts).

## Instant result — how it works

The issue list is deliberately one period behind upstream
(`api_lottery_issue_data()` subtracts one interval), so the round the player is
betting on has already been drawn. `api_lottery_place_bet()` therefore calls
`api_lottery_settle_bet($row, true)` right after the insert: the result is
fetched from `lottery_results` / the admin queue / the deterministic fallback,
the bet is marked won or lost, the win is credited, agent commissions run, and
the response carries `resultStatus`, `isWin`, `isPending`, `winAmount`,
`profitAmount` and `result` next to the refreshed `balance`.

On the client, every game chunk calls `mapBet.set(issueNumber, 1)` the moment a
bet is accepted. The patched shared composable hooks that call: it refreshes the
result history (so the popup has the drawn row) and then asks
`Lottery/GetWinLossResult`, which now answers a decided result, so the win/loss
popup opens straight away — WinGo, TrxWinGo, K3, 5D and MotoRace all share this
one code path.

## Deploy notes

* The live host uses MySQL (`api/config.php` → `club532583_cobra`) with a SQLite
  fallback; all new code branches on the live driver, so both work.
* The one-time cleanup (zero `amount_coding`, disable stale snapshots, merge the
  two balance columns) runs on the first request and is marker-guarded.
* No database file is shipped; your data is untouched.
