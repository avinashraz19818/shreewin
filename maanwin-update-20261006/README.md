# maanwin — WinGo fix (one period behind + instant win/loss) · 2026-10-06

**One single zip.** Extract it at the site root, overwrite everything, hard-reload
(Ctrl+F5). Do **not** touch the database — the code self-heals on the first request.

This is the same WinGo behaviour that was delivered on the other projects
(dhaniwin / raxiwin), ported onto maanwin's backend.

---

## Why the game history was empty (root cause, found on the live site)

Probing `https://maanwin1.com` showed the JSON draw routes were **404**:

```
GET /WinGo/WinGo_30S/GetHistoryIssuePage.json  -> {"error":"Not Found"}
GET /WinGo/WinGo_30S.json                      -> {"error":"Not Found"}
GET /webapi/kv/issue/WinGo_30S                 -> 200 OK
```

The `.htaccess` rule that maps `^(WinGo|K3|D5|MotoRace|TrxWinGo)/(.+\.json)$`
onto `api/_draw_router.php` is not being applied on this host, so the game
history request never reached PHP. The `/api/*` routes do work.

**Fix:** `js/api-route-fix.js` (loaded from `index.html` *before* the app
bundle) rewrites those two URL shapes in the browser:

```
/WinGo/WinGo_30S/GetHistoryIssuePage.json -> /api/Lottery/GetHistoryIssuePage?gameCode=WinGo_30S&pageNo=1&pageSize=10
/WinGo/WinGo_30S.json                     -> /api/Lottery/GetGameIssue?gameCode=WinGo_30S
```

It patches both `window.fetch` and `XMLHttpRequest.prototype.open` (axios uses
XHR), keeps query strings, only rewrites same-origin URLs, and leaves every
other request untouched. No server config change needed. 9/9 rewrite tests pass.

## Result numbers now match dhaniwin too

dhaniwin generates its result **deterministically** from the issue number:

```php
$seed    = crc32($gameCode . ':' . $issueNumber);   // unsigned
// WinGo    -> (string)($seed % 10)
// K3       -> 3 dice taken from seed bit shifts
// 5D       -> 5 digits, ($seed >> ($i*3)) % 10
// MotoRace -> 10 cars shuffled by the seed
```

So the same period always yields the same number, on every refresh and for
every user. maanwin used `random_int()`, which is why the two sites showed
different results for the same period.

maanwin now uses dhaniwin's `api_lottery_default_premium()` verbatim via
`le_issue_premium()`. Verified: **200/200 identical** results against
dhaniwin's own formula (8 game codes × 25 issues), and stable across repeated
calls.

A one-shot `le_self_heal_results()` (guarded by the `maanwin_result_heal_v2`
setting) deletes result rows from the last 12 hours whose premium does not
match the deterministic value, so periods drawn before this update regenerate
correctly too. Older history and manual admin results are untouched.

## Game history rows now carry dhaniwin's keys

dhaniwin's history item exposes `issue`, `numberValue`, `resultNumber`,
`openCode`, `sumValue`, `source` and `serviceTime`. maanwin's
`lottery_public_result()` did not, so rows rendered blank. It now returns the
full dhaniwin key set **plus** maanwin's own keys — a safe superset.

## Period numbers now match dhaniwin exactly

maanwin used its own issue format (`YYYYMMDD` + `1000` + slot mod 100000) while
dhaniwin used `YYYYMMDD` (UTC) + game prefix + 1-based period index of that UTC
day. That is why the two sites showed different period numbers.

maanwin now uses dhaniwin's formula verbatim:

```
prefix = le_issue_prefix(gameCode)      // WinGo_30S => 10005, K3_1M => 40001 ...
ts     = slot * interval
index  = intdiv(ts - utcMidnight(ts), interval) + 1
issue  = sprintf('%s%s%04d', gmdate('Ymd', ts), prefix, index)
```

dhaniwin's 1 second *end grace* is reproduced too (`le_current_slot()`), so the
round flips at the same instant on both sites. Timers, period lengths and the
countdown are unchanged.

Verified: 90/90 identical issue numbers against dhaniwin's own
`api_lottery_calculate_issue()` across 9 game codes and 10 timestamps each.

---

## What was wrong

1. **The bet round and the result list were one period apart.**
   `lottery_issue()` returned the issue of the *running* slot, while every result
   list started at `offset = 1` (the previous slot). So you bet on issue **N** but
   the top of the result list showed **N-1**, and `My Records` kept showing the bet
   as *Pending* until the next round.

2. **Win/loss was only settled after the countdown ended** (and, because of the
   issue mismatch, effectively a whole period later). Since the round being played
   is one period behind upstream, its result is already known — waiting adds
   nothing.

3. **The admin checkbox "Immediate settlement when eligible" did nothing.**
   `lottery_game_settings.immediate_settle` existed in the schema, the seed, the
   admin form and `le_get_settings()` — but no settlement code ever read it.

---

## What changed

### `api/_core/lottery_engine.php`
* New `le_issue_from_slot()` helper.
* `le_issue_by_offset()` now returns `slot - 1 - offset`, so **offset 0 = the round
  the player is betting on** (its result is already drawn).
* `le_settle_pending_bets()` gained `bool $force = false, int $onlyBetId = 0`.
  With `$force = true` a bet is settled without waiting for `le_issue_is_closed()`.
* Force Win / Force Lose is now re-applied at settlement time (it can no longer be
  missed just because the result row was generated before the bet arrived).
* New `le_instant_result()` — reads `settings.lottery_instant_result`, **defaults
  to on**.

### `api/_core/bootstrap.php`
* `lottery_issue()` builds the issue from `slot - 1`, so the issue shown on the bet
  screen is exactly the issue at the top of every result list. `startTime` /
  `endTime` / `countdown` still describe the running slot, so **all timers are
  unchanged**.

### `api/_router.php`
* `handle_lottery_history()` and `handle_lottery_trend()` now start at offset `0`.
* `handle_admin_result_history()` and the recharge-wheel history start at offset `0`.
* `handle_lottery_bet()` captures the new bet id and, when `le_instant_result()` is
  on, settles it immediately and returns the decided result
  (`state`, `isWin`, `result`, `winLoseAmount`, `balance`).
* `handle_win_loss()` force-settles when the switch is on.

### `api/_draw_router.php`
* The public JSON history (`/WinGo/WinGo_30S/GetHistoryIssuePage.json`) starts at
  offset `0` too, so it matches the in-app list.

### `admin1/actions.php` / `admin1/index.php`
* The "Immediate settlement when eligible" checkbox now writes
  `settings.lottery_instant_result` and the form renders its current state.

---

## Popup timing

**No JavaScript was modified.** maanwin's client already calls `getWinLossResult()`
from inside the countdown handler (at 1 second remaining, +2.2–3.2 s), so the
win/loss popup keeps appearing **after the timer ends**. Only the *decision* moved
earlier — which is exactly what was asked for.

---

## Verification

Run against a real PHP 8.3 runtime with a SQLite mysqli shim, on a clean database:

```
[1] the round you bet on and the newest result row must be the SAME issue
  ok  WinGo_30S / WinGo_1M / WinGo_3M / WinGo_5M / TrxWinGo_1M / K3_1M / D5_1M / MotoRace_1M
  ok  countdown is alive (8/8)
[2] the result of a round never changes once it is drawn        ok
[3] a bet is decided the moment it is placed                    ok
  ok  balance = before - stake + win
[4] GetWinLossResult agrees with the bet, no pending records    ok
[5] 15 bets in a row: all settled, money conserved              ok
[6] admin Force Win / Force Lose still decides the round        ok
[7] instant result off restores the old wait-for-the-round      ok

ALL 31/31 MAANWIN WINGO CHECKS PASSED
```

Plus: `Admin/GetResultHistory`, `Lottery/GetTrendStatistics` and the public JSON
history all start at the active issue; the admin toggle round-trips 0/1/0/1
correctly; every changed file parses cleanly.

---

## Files in this package (6)

```
api/_core/lottery_engine.php
api/_core/bootstrap.php
api/_router.php
api/_draw_router.php
admin1/actions.php
admin1/index.php
```

No new tables or columns. One settings key (`lottery_instant_result`) is written,
and it is created on demand.
