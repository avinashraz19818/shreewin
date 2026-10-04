# RaxiWin update - 2026-10-04 (build `20261004`) - corrected v3

Ready-to-deploy package for the **RaxiWin** site (`raxiwin` repo).

Supersedes `raxiwin-update-20261003-v3`. Same 5 files, but the bet-booking rule
in `sl_place_bet()` is corrected: **the bet is stored on exactly the label the
client sent** - never pushed forward (v2) and never pulled back (v3).

Why: v3 forced every accepted bet onto `$appRound` (the previous canonical
period) to match a *lagging* client label. That is wrong for an up-to-date
client, which displays and sends the **running** period - v3 would have booked
its bet one period behind the screen. This build removes that one assignment
(`$issue = $appRound;`); the sent label is validated (`appRound` or
`currentIssue`, otherwise 404) and kept as-is, and `settle_after = $endTime`
still holds the result until the player's own countdown ends.

| client | sends | display | booked | settled |
| --- | --- | --- | --- | --- |
| up-to-date bundle | running period (e.g. `...334`) | `...334` | `...334` | after `...334` timer |
| stale cached bundle | last-fetched (often `...333`) | `...333` | `...333` | after the countdown it is showing |

## Deliverable

| file | note |
| --- | --- |
| `raxiwin-update-20261004.zip` | the uploadable package (55,774 B, md5 `248f71b64b68b27403d44b21a784055d`) |
| `PADHO-RAXIWIN-UPDATE-20261004.txt` | the same instructions in Hinglish (also inside the ZIP) |
| `saas_lottery/bootstrap.php` | live engine for `/api/Lottery/*` and `draw/` |
| `saas_lottery/bootstrap_live_v4.php` | live engine for `/api-live-v4/*` and `/draw-live-v4/*` |
| `draw-live-v4/index.php` | public draw / result-list endpoint (unchanged since v2) |
| `assets/js/useLottery.hook-_2rEnQ4S.js` | client: issue label + countdown (unchanged since v2) |
| `assets/js/useWinGo3-5zV6kUzs.js` | client: bet, win/loss poll, history list (unchanged since v2) |
| `changes.diff` | origin (`raxiwin@35718c3`) -> this build, all five files |
| `v3-to-20261004.diff` | only the delta from `raxiwin-update-20261003-v3` |
| `CLIENT-JS-CHANGES.txt` | the exact one-line client-bundle edits (minified, so not in `changes.diff`) |

## Verification done before packaging

* `php-parser` AST lint: `bootstrap.php`, `bootstrap_live_v4.php`,
  `draw-live-v4/index.php` - **OK**.
* Runtime simulation of the real extracted accept block + defer guard
  (PHP 8.4.1 wasm, scaled-down epoch because that build is 32-bit):
  **12/12 pass** - running label kept for the up-to-date client, lagging label
  kept for the stale client, older label 404, lock window 404,
  `accept_previous_period_bets=0` strict mode, defer guard
  (`pending` before `settle_after`, `settled` at/after, legacy rows settle).
* Prepared-statement audit (columns / placeholders / bind types) on both
  engines: **6/6 inserts OK** (`isssdiiddiss`, `isssdiiddss`, `isssdiidss`).
* `changes.diff` round-trip: reverse-applying it to this build reproduces the
  upstream `raxiwin@35718c3` files byte-for-byte.
* ZIP: `unzip -t` clean, extracted PHP lints OK, every extracted file md5-matches
  the staged source.

## Deploy (cPanel)

Site root -> upload the ZIP -> Extract -> Overwrite -> hard refresh (Ctrl+F5 /
clear the app/PWA cache). No SQL, cron or setting change needed; the engine adds
`tax_fee` and `settle_after` on its own.

## Note on the running period vs the history list

The header (running period) is always exactly one ahead of the newest row in
the history list (last completed period) - `334 = 333 + 1` in the 2026-10-04
screenshot. That is the correct shape, not a lag: the running period's result
never appears in the list before its timer ends. The original bug was the other
way round (header showed an older period than the newest history row).

## Links

* raw: `https://github.com/avinashraz19818/shreewin/raw/raxiwin-update-20261004/raxiwin-update-20261004/raxiwin-update-20261004.zip`
* blob: `https://github.com/avinashraz19818/shreewin/blob/raxiwin-update-20261004/raxiwin-update-20261004/raxiwin-update-20261004.zip`
* branch raw: `https://github.com/avinashraz19818/shreewin/raw/arena/01a09f38-shreewin/raxiwin-update-20261004/raxiwin-update-20261004.zip`
* tag archive (whole repo, ~285 MB): `https://github.com/avinashraz19818/shreewin/archive/refs/tags/raxiwin-update-20261004.zip`

Earlier packages stay available: `raxiwin-update-20261003/` (v2) and
`raxiwin-update-20261003-v3/` (v3).

## Rollback

Restore these five files from a backup; the two extra columns are inert for the
old code:

```
saas_lottery/bootstrap.php
saas_lottery/bootstrap_live_v4.php
draw-live-v4/index.php
assets/js/useLottery.hook-_2rEnQ4S.js
assets/js/useWinGo3-5zV6kUzs.js
```
