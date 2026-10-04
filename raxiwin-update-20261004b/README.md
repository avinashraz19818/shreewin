# RaxiWin update - 2026-10-04, build `20261004b` ("poori app 1 period piche")

Ready-to-deploy package for the **RaxiWin** site (`raxiwin` repo).

Supersedes `raxiwin-update-20261004`: the whole public WinGo surface now runs
**one period behind the provider feed** - header, bet, record, history and
result all use the same lagging label, and a bet still cannot settle before its
own countdown ends. Everything is server-side, so cached/old client bundles get
the same behaviour.

## Label contract

Let `C` be the provider's canonical running period and `N = C - 1` the label the
player sees.

| moment | provider feed | app shows | app history top | deliverable |
| --- | --- | --- | --- | --- |
| during window `C` | `C` running | header `N` (countdown = window `C`) | `N-1` (draw of `C-1`) | bet on `N` accepted, stored as `N` |
| window `C` ends | draw `C` published | header rolls to `N+1 = C` | `N` appears (draw `C`) | bet `N` settles with draw `C` |

* `sl_place_bet()` accepts the label the client sent (`N` = previous canonical,
  or `C` if the client already rolled over), stores it **unchanged** and sets
  `settle_after = $endTime` (the end of the countdown the player is looking at).
* `sl_save_and_settle_results()` keeps settling rows on the canonical label
  (legacy rows) **and** settles rows on the shifted label with the same draw.
* `sl_win_loss()` maps the polled label to its draw: `resultIssue = label + 1`,
  waits for that stored draw (bounded ~3 s) and settles the label's rows.
* `draw-live-v4/index.php` shifts the public surfaces: `raxiwin_public_current()`
  moves the running-period label down (countdown times stay canonical) and
  `raxiwin_display_previous_periods()` relabels history rows (values untouched)
  while never exposing a period that is still running.

All of it is driven by one helper, `sl_display_label_offset()`:

```
saas_lottery_settings.raxiwin_period_label_offset = -1   (default: 1 period behind)
                                                  =  0   (canonical/current labels)
```

Display and settlement read the same value, so the two can never drift apart.

## Deliverable

| file | note |
| --- | --- |
| `raxiwin-update-20261004b.zip` | the uploadable package (56,709 B, md5 `f1c3704551cc966e17ffcf150ab754e1`) |
| `PADHO-RAXIWIN-UPDATE-20261004b.txt` | the same instructions in Hinglish (also inside the ZIP) |
| `saas_lottery/bootstrap.php` | live engine for `/api/Lottery/*` and `draw/` |
| `saas_lottery/bootstrap_live_v4.php` | live engine for `/api-live-v4/*` and `/draw-live-v4/*` |
| `draw-live-v4/index.php` | public feed: running period + history (`/WinGo/<game>/*.json`) |
| `assets/js/useLottery.hook-_2rEnQ4S.js` | client label + countdown (unchanged) |
| `assets/js/useWinGo3-5zV6kUzs.js` | client bet/win-loss/history (unchanged) |
| `changes.diff` | upstream origin (`raxiwin@35718c3`) -> this build, all five files |
| `20261004-to-20261004b.diff` | only the delta from the previous build |
| `CLIENT-JS-CHANGES.txt` | the exact one-line client-bundle edits (minified) |

## Verification before packaging

* `php-parser` AST lint on the three PHP files: **OK**.
* Runtime simulations of the real extracted code (PHP 8.4.1 wasm):
  * history relabel: running period hidden, `X -> X-1`, values untouched - **4/4**;
  * public running payload: header `C-1`, countdown times unchanged,
    `previous`/`next` shifted, non-WinGo untouched - **6/6**;
  * settlement mapping: displayed-label bet settles with the provider draw,
    legacy canonical bet still settles, `settle-from-stored` asks the right
    provider row, `poll label + 1` - **6/6**;
  * offset `0` mode (separate processes, fresh static): history labels stay
    canonical, payload stays canonical, settlement falls back to canonical -
    **3/3**;
  * `sl_place_bet()` accept path reused from the previous build (unchanged):
    displayed label stored as-is, defers to `$endTime`, advanced label kept,
    older label 404, lock window 404, strict mode - **9/9**.
* Prepared-statement audit (columns/placeholders/bind types) both engines: OK.
* `changes.diff` round-trip: reverse-applying it reproduces upstream
  `raxiwin@35718c3` byte-for-byte.
* ZIP: `unzip -t` clean, extracted PHP lints OK, extracted files md5-match the
  staged sources.

Note: the wasm PHP build is 32-bit, so millisecond timestamps overflow there;
simulations use scaled epochs and string labels (`sl_issue_shift()` never uses
floats). Production PHP is 64-bit and already cast these values this way.

## Deploy (cPanel)

Site root -> upload the ZIP -> Extract -> Overwrite -> hard refresh (Ctrl+F5,
clear the app/PWA cache). No SQL/cron/setting change needed. To return to
canonical labels later, set `raxiwin_period_label_offset = 0`.

## Links

* raw: `https://github.com/avinashraz19818/shreewin/raw/raxiwin-update-20261004b/raxiwin-update-20261004b/raxiwin-update-20261004b.zip`
* blob: `https://github.com/avinashraz19818/shreewin/blob/raxiwin-update-20261004b/raxiwin-update-20261004b/raxiwin-update-20261004b.zip`
* branch raw: `https://github.com/avinashraz19818/shreewin/raw/arena/01a09f38-shreewin/raxiwin-update-20261004b/raxiwin-update-20261004b.zip`
* tag archive (whole repo, ~285 MB): `https://github.com/avinashraz19818/shreewin/archive/refs/tags/raxiwin-update-20261004b.zip`

Earlier packages remain: `raxiwin-update-20261003/` (v2),
`raxiwin-update-20261003-v3/` (v3), `raxiwin-update-20261004/` (canonical build).

## Rollback

Restore these five files (the previous build folder or a backup):

```
saas_lottery/bootstrap.php
saas_lottery/bootstrap_live_v4.php
draw-live-v4/index.php
assets/js/useLottery.hook-_2rEnQ4S.js
assets/js/useWinGo3-5zV6kUzs.js
```
