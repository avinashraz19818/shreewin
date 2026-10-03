# RaxiWin update - 2026-10-03, v3 (bet stays on the displayed period, settles only after its timer)

Ready-to-deploy package for the **RaxiWin** site (`raxiwin` repo).

This supersedes `raxiwin-update-20261003` (v2). v2 gave the bet the *running*
period while the screen showed `current - 1`; the user asked for the bet to stay
on the period that is actually displayed ("91 dikha to bet bhi 91 par"). v3 does
exactly that and holds the result until that period's own timer ends.

## Deliverable

| file | note |
| --- | --- |
| `raxiwin-update-20261003-v3.zip` | the uploadable package (56,222 B, md5 `a8d55059da3681c02f57ce5a626ece77`) |
| `PADHO-RAXIWIN-UPDATE-20261003-v3.txt` | the same instructions in Hinglish (also inside the ZIP) |
| `saas_lottery/bootstrap.php` | live engine for `/api/Lottery/*` and `draw/` |
| `saas_lottery/bootstrap_live_v4.php` | live engine for `/api-live-v4/*` and `/draw-live-v4/*` |
| `draw-live-v4/index.php` | public draw / result-list endpoint (`/WinGo/<game>/...json`) - unchanged since v2 |
| `assets/js/useLottery.hook-_2rEnQ4S.js` | client: running-issue label + countdown - unchanged since v2 |
| `assets/js/useWinGo3-5zV6kUzs.js` | client: bet, win/loss poll, history list - unchanged since v2 |
| `changes.diff` | unified diff of the five shipped files against the repo origin (`git diff`) |
| `v2-to-v3.diff` | only the delta v2 -> v3 (the two engine files) |
| `CLIENT-JS-CHANGES.txt` | the exact one-line client-bundle edits (minified, so not in `changes.diff`) |

## What v3 changes (vs the already-published v2)

1. **The bet is booked on the period the screen shows, never pushed forward.**
   In `sl_place_bet()`:

   ```php
   $appRound = sl_issue_number_for_start($gameCode, $currentStart - $interval);
   if (app_setting_bool('accept_previous_period_bets', true)) {
       if ($issue !== $appRound && $issue !== $currentIssue) {
           sl_fail(404, 'Betting has stopped for the current period', 404, 200);
       }
       $issue = $appRound;          // v2 used to force $currentIssue here
       $settleAfter = $endTime;     // hold the result until this period ends
   } elseif ($issue !== $currentIssue) {
       sl_fail(404, 'Betting has stopped for the current period', 404, 200);
   }
   ```

   `accept_previous_period_bets=0` (setting) restores the strict
   running-period-only mode; the bet-lock window (`bet_lock_seconds`) is
   unchanged and still returns 404.

2. **No result before the player's own countdown ends.** New column
   `saas_lottery_bets.settle_after BIGINT NOT NULL DEFAULT 0` (auto-migrated in
   both engines, `AFTER tax_fee`; the older `tax_fee` migration block is
   untouched). `sl_place_bet()` stores that period's `endTime` in it and
   `sl_settle_issue()` skips the row while `sl_now_ms() < settle_after`, so
   `GetWinLossResult` reports `pending` until the timer of the booked period has
   finished. Legacy rows (`settle_after = 0`) keep the old behaviour.

3. **A cached client polling a lagging label still gets its result.**
   `sl_win_loss_follow_issue()` now looks the bet up in a decimal-safe
   `issue_number BETWEEN issue-1 AND issue+1` window (new `sl_issue_shift()`
   helper: digit-string add/sub, no floats/BigInt), instead of only
   `issue_number > ?`.

4. **Insert path fixed and hardened.** The new `settle_after` INSERT is
   `... (?,?,?,?,?,?,?,?,?,?,?,?,'pending',NOW())` with
   `bind_param('isssdiiddiss', ...)` (13 columns / 12 placeholders / 12 types -
   verified), and it throws a clear `RuntimeException` if the statement cannot
   be prepared instead of silently skipping the bet. The `tax_fee` and legacy
   variants are untouched (`isssdiiddss`, `isssdiidss`).

Server-side only: the fix works with the old cached bundle **and** with the
un-shifted client shipped in the ZIP. History/`draw-live-v4` and the client
bundles are byte-identical to v2.

## Verification done before packaging

* `php-parser` (PHP 8.2 AST) lint: both engine files **OK**.
* PHP 8.4.1 (wasm) `token_get_all(..., TOKEN_PARSE)`: both **OK**; tax math
  unchanged (`fee(100)=2`, `net(200)=196`, clamp 100).
* Prepared-statement audit over every `$conn->prepare()` with placeholders in
  both engines: **10/10 consistent** (columns / placeholders / bind types) -
  this audit caught and fixed the malformed v3 INSERT before publishing.
* Runtime simulation of the new accept block + defer guard (PHP 8.4.1 wasm,
  the real extracted source text): **12/12 checks pass**
  - books on the displayed (lagging) round, defers to `$endTime`;
  - accepts both the displayed and the running label, rejects an older one;
  - bet-lock window still returns 404;
  - `accept_previous_period_bets=0` keeps the running period with no deferral
    and rejects the lagging label;
  - defer guard: `pending` before `settle_after`, `settled` at/after it,
    legacy rows (`0`/missing) settle as before.
* `sl_issue_shift()` unit cases (borrow `...50000 -> ...49999`, carry
  `...59999 -> ...60000`): 7/7 pass.
* ZIP: `unzip -t` clean, 10 entries, extract-and-compare md5 identical to the
  served copy.

Note about the test sandbox: the wasm PHP build is 32-bit (`PHP_INT_SIZE=4`),
so millisecond timestamps overflow `(int)` casts there; the simulations use
scaled-down epoch values. Production PHP is 64-bit and already cast
`endTime`/`startTime` this way before this patch.

## Deploy (cPanel)

Site root -> upload the ZIP -> right click -> **Extract** -> Overwrite/Replace
-> hard refresh (Ctrl+F5; clear the PWA/app cache on mobile). No SQL, cron or
setting change is needed - the two new columns (`tax_fee`, `settle_after`) are
added automatically by the engine on the next request.

## Links

* raw: `https://github.com/avinashraz19818/shreewin/raw/raxiwin-update-20261003-v3/raxiwin-update-20261003-v3/raxiwin-update-20261003-v3.zip`
* blob: `https://github.com/avinashraz19818/shreewin/blob/raxiwin-update-20261003-v3/raxiwin-update-20261003-v3/raxiwin-update-20261003-v3.zip`
* branch raw: `https://github.com/avinashraz19818/shreewin/raw/arena/01a09f38-shreewin/raxiwin-update-20261003-v3/raxiwin-update-20261003-v3.zip`
* tag archive (whole repo, ~285 MB): `https://github.com/avinashraz19818/shreewin/archive/refs/tags/raxiwin-update-20261003-v3.zip`

v2 stays available at `raxiwin-update-20261003/` (tag `raxiwin-update-20261003`).

## Rollback

Restore these five files from a backup (or from the v2 folder / the repo
origin); the two extra columns are inert for the old code:

```
saas_lottery/bootstrap.php
saas_lottery/bootstrap_live_v4.php
draw-live-v4/index.php
assets/js/useLottery.hook-_2rEnQ4S.js
assets/js/useWinGo3-5zV6kUzs.js
```
