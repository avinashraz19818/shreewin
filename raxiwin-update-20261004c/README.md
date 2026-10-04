# RaxiWin update - 2026-10-04, build `20261004c` (period **and** result one period behind)

Ready-to-deploy package for the **RaxiWin** site (`raxiwin` repo).

Supersedes `raxiwin-update-20261004b`. In `b` only the *label* was shifted: the
history row `377` carried the draw of provider period `378`, so the number next
to the label looked "one period ahead". `c` delays the whole feed instead: every
row shows **its own** draw, and the newest provider row stays hidden until its
countdown has finished inside the app - so the period *and* the result the
player sees are one period behind, and they always belong together.

## What the player sees

Let `C` be the provider's canonical running period.

| | provider feed | app |
| --- | --- | --- |
| running period (header / bet / record) | `C` | `C - 1` (countdown of window `C`) |
| newest row in history | `(C-1, draw(C-1))` | `(C-2, draw(C-2))` - the `C-1` row is withheld |
| when the app's countdown ends | `draw(C)` published | header -> `C`, history top becomes `(C-1, draw(C-1))` |

Concrete example: provider `341` running, `340` drawn.

```
app header / bet / record : 340
app history top row       : 339  ->  its own draw (of 339)
after the countdown       : header 341, history top 340 -> its own draw (of 340)
```

So a bet placed on the displayed label settles, at the end of that countdown,
with exactly the draw that then appears next to that label in the list - the
list and the win/loss can never disagree.

## Implementation

* `draw-live-v4/index.php`
  * `raxiwin_display_previous_periods()` no longer re-labels rows. It hides
    every row with `issue >= sl_issue_shift($currentIssue, $offset)` - i.e. the
    newest provider row plus anything newer - which is what "publish one period
    later" means. Rows keep their own premium/number/color.
  * `raxiwin_public_current()` keeps the running period's label one period
    behind, with canonical countdown times.
* `saas_lottery/bootstrap.php` / `bootstrap_live_v4.php`
  * `sl_place_bet()` (unchanged since `20261004`): the label the client sent is
    validated (`previous canonical` or `running`, otherwise 404), stored
    **as-is** and gets `settle_after = $endTime` - the end of the countdown the
    player is watching.
  * settlement is canonical again (`label X` settles with draw `X`; the `b`
    build's `label -> X+1` mapping is gone), and the `settle_after` guard keeps
    every outcome hidden until that countdown ends.
  * `sl_win_loss()` gets one addition: when a polled period still has pending
    bets inside their countdown but the draw is already stored,
    `sl_bet_defer_until()` (new helper) returns the earliest `settle_after` and
    the request waits for it (bounded 3 s) so a poll landing a moment early
    still answers won/lost instead of `null`.
* `sl_display_label_offset()` drives everything: `-1` (default, this build) or
  `0` (live/current labels) via `saas_lottery_settings.raxiwin_period_label_offset`.

## Deliverable

| file | note |
| --- | --- |
| `raxiwin-update-20261004c.zip` | the uploadable package (56,354 B, md5 `a8c34cff4f19777031c8eb2e47fa4577`) |
| `PADHO-RAXIWIN-UPDATE-20261004c.txt` | the same instructions in Hinglish (also inside the ZIP) |
| `saas_lottery/bootstrap.php` | engine for `/api/Lottery/*` and `draw/` |
| `saas_lottery/bootstrap_live_v4.php` | engine for `/api-live-v4/*` and `/draw-live-v4/*` |
| `draw-live-v4/index.php` | public feed: running period + history (`/WinGo/<game>/*.json`) |
| `assets/js/useLottery.hook-_2rEnQ4S.js` | client label + countdown (unchanged) |
| `assets/js/useWinGo3-5zV6kUzs.js` | client bet/win-loss/history (unchanged) |
| `changes.diff` | upstream origin (`raxiwin@35718c3`) -> this build, all five files |
| `20261004b-to-20261004c.diff` | only the delta from the previous build |

## Verification before packaging

PHP 8.4.1 (wasm) runtime simulations of the real extracted code:

* delayed history feed: newest row hidden during the countdown, top row = `C-2`
  with its own draw, the `C-1` row appears after the countdown, `offset = 0`
  keeps the live feed - **7/7**;
* running-period payload: header `C-1`, countdown times unchanged,
  `previous`/`next` shifted, non-WinGo untouched - **5/5**;
* `sl_bet_defer_until()`: reads the pending deadline, returns 0 without the
  column - **2/2**;
* bet acceptance + `settle_after` guard: displayed label stored as-is, defers to
  the countdown end, advanced label kept, older label 404, lock window 404,
  strict mode, defer-holds/defer-settles - **9/9**.

Plus: `php-parser` AST lint on the three PHP files **OK**; prepared-statement
audit (every `$conn->prepare()` with placeholders matches its `bind_param`
string, and all three `saas_lottery_bets` inserts match their column list)
**OK**; `changes.diff` reverse-applies to upstream `raxiwin@35718c3`
byte-for-byte; ZIP `unzip -t` clean and extracted files md5-match the sources.

## Deploy (cPanel)

Site root -> upload the ZIP -> Extract -> Overwrite -> hard refresh (Ctrl+F5 /
clear the app/PWA cache). No SQL, cron or setting change needed.

## Note to the operator

In this mode the public feed is deliberately one period late. A player who
compares with another site/feed can therefore know a period's outcome before the
app reveals it. The app itself never reveals anything early; set
`raxiwin_period_label_offset = 0` to return to the live feed at any time.
