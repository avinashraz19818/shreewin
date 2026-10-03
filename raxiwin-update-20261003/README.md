# RaxiWin update - 2026-10-03, v2 (issue label = bet period, tax, no early settle)

Ready-to-deploy package for the **RaxiWin** site (`raxiwin` repo).

## Deliverable

| file | note |
| --- | --- |
| `raxiwin-update-20261003.zip` | the uploadable package (54,671 B, md5 `f02599de5dfb52e8a8a279c87674be3e`) |
| `PADHO-RAXIWIN-UPDATE-20261003.txt` | the same instructions in Hinglish (also inside the ZIP) |
| `saas_lottery/bootstrap.php` | live engine for `/api/Lottery/*` and `draw/` |
| `saas_lottery/bootstrap_live_v4.php` | live engine for `/api-live-v4/*` and `/draw-live-v4/*` |
| `draw-live-v4/index.php` | public draw / result-list endpoint (`/WinGo/<game>/...json`) |
| `assets/js/useLottery.hook-_2rEnQ4S.js` | client: running-issue label + countdown |
| `assets/js/useWinGo3-5zV6kUzs.js` | client: bet, win/loss poll, history list |
| `changes.diff` | unified diff of the three PHP files against the repo origin (`git diff`) |
| `CLIENT-JS-CHANGES.txt` | the exact one-line client-bundle edits (minified, so not in changes.diff) |

The SPA places bets on `<site>/api/Lottery/WinGoBet` (root `.htaccess` ->
`api/Lottery/index.php` -> `saas_lottery/bootstrap.php`: bet insert, wallet, tax,
`GetWinLossResult`, `GetRecordPage`) while the result screen polls
`/WinGo/<game>/<game>.json` and `/WinGo/<game>/GetHistoryIssuePage.json`
(-> `draw-live-v4/index.php` -> `saas_lottery/bootstrap_live_v4.php`: result
store, settlement, backfill, history). Both engines are shipped and share the
same tables.

## What it fixes

1. **Displayed issue != bet issue ("61 pe lag rha h 62 pe")** - the client
   label was `current - 1` while the bet went to `current`, and the two
   `shiftPublicResults`/`raxiwin_display_previous_periods` shifts re-labelled
   rows on top of that. Now: label == bet issue == settlement issue == history
   label. A lagging label that still arrives from a cached bundle is
   re-booked on the running period server-side instead of being rejected.
2. **Bet settled immediately ("bet lagate hi win ho jaa rha h")** - removed
   the accept-and-settle-from-stored-draw path (`acceptDrawnPrevious`); a bet
   is only settled by the draw of its own period, and
   `sl_save_and_settle_results()` still refuses rows for the period that is
   still running, so nothing can pay out before its timer ends.
3. **Win/loss popup still arrives** - `GetWinLossResult` settles any pending
   row from the stored draw, waits up to ~3 s for the closing draw, and follows
   a re-booked bet when the polled label has no rows of its own (the
   `sl_win_loss_follow_issue()` fallback for cached old clients).
4. **Bet tax / service charge** (unchanged from v1, still included) - stake tax
   (default 2%, setting `lottery_payout_tax_percent`) stored per bet with an
   automatic one-time backfill; the record API returns
   `realAmount, realBettingAmount, fee, serviceCharge, tax, taxAmount,
   taxRate` instead of `fee = 0.00`.
5. **History panel** - `raxiwin_display_previous_periods()` no longer shifts
   labels; it only hides rows for the period that is still running, so a
   closing draw can never be revealed before its timer ends.

## Settings (optional, table `saas_lottery_settings`)

| key | default | meaning |
| --- | --- | --- |
| `lottery_payout_tax_percent` | `2.00` | tax percent, clamped 0-100 |
| `accept_previous_period_bets` | `1` | `1`: a lagging label is re-booked on the running period; `0`: such a label is rejected (404) |

Either way a bet never settles before the end of its own period.

## Verification done here

* `php-parser` 8.2 parse: OK for `bootstrap.php`, `bootstrap_live_v4.php`,
  `draw-live-v4/index.php`, `api-live-v4/index.php`.
* real PHP 8.4.1 `token_get_all(..., TOKEN_PARSE)`: OK for the same three
  shipped PHP files; tax helpers return `fee(100)=2`, `net(200)=196` at 2 %,
  `fee(100)=5` at 5 %, clamped to 100 % for out-of-range settings.
* `node --check` on both patched client chunks: OK.
* ZIP: `unzip -t` OK, every entry's md5 equals the working tree, and the
  copy in `/home/user/dl/raxiwin-update-20261003.zip` serves byte-identical
  over the sandbox preview URL.
