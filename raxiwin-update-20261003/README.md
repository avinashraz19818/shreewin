# RaxiWin update - 2026-10-03 (tax + win/loss "1 period piche")

Ready-to-deploy package for the **RaxiWin** site (`raxiwin` repo). Only two
shipped engine files change; both are drop-in replacements.

## Deliverable

| file | note |
| --- | --- |
| `raxiwin-update-20261003.zip` | the uploadable package (44,060 B, md5 `df85b15aaf258ac50348cc1413fa261a`) |
| `PADHO-RAXIWIN-UPDATE-20261003.txt` | the same instructions in Hinglish (also inside the ZIP) |
| `saas_lottery/bootstrap.php` | patched live engine for `/api/Lottery/*` and `draw/` |
| `saas_lottery/bootstrap_live_v4.php` | patched live engine for `/api-live-v4/*` and `/draw-live-v4/*` |
| `changes.diff` | unified diff of both engines against the live code |

The SPA talks to `<site>/api/Lottery/*` (root `.htaccess` -> `api/Lottery/index.php`
-> `saas_lottery/bootstrap.php`) and to `/WinGo/<game>/...json`
(-> `draw-live-v4/index.php` -> `saas_lottery/bootstrap_live_v4.php`), so both
engines are patched and share the same database tables.

## What it fixes

1. **Bet tax / service charge** - stake tax (default 2%, setting
   `lottery_payout_tax_percent`) is stored per bet, the one-time backfill runs
   automatically, and the record API returns
   `realAmount, realBettingAmount, fee, serviceCharge, tax, taxAmount, taxRate`.
   Previously every record showed `fee = 0.00`.
2. **Win/loss running one period behind** - a bet that arrives with the
   lagging issue label (the app's existing convention) is accepted and settled
   from the already-stored draw, so the win/loss and the wallet credit are
   ready immediately; `GetWinLossResult` never answers `null` for a settled
   period any more (it settles pending rows and waits a short bounded time for
   the closing draw).
3. Nothing else changes - result display/labels, rates, wallet math, and the
   payout tax itself keep the previous behaviour (Settings: `accept_previous_period_bets`,
   `lottery_payout_tax_percent`).

## Deploy

cPanel -> File Manager -> site root -> upload the ZIP -> *Extract* + *Overwrite*.
No SQL and no cron changes; the new `saas_lottery_bets.tax_fee` column is added
by the engine on the first request. Rollback = restore the two backed-up files.
