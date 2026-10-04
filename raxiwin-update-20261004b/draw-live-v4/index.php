<?php
/**
 * Uncached authoritative SaaS draw adapter.
 *
 * Public animation polling must stay independent from schema migrations and
 * wallet settlement. Authenticated API requests still persist and settle the
 * same provider results, while this route returns the real feed immediately.
 */
require_once dirname(__DIR__) . '/saas_lottery/bootstrap_live_v4.php';

/**
 * Near a timer boundary the browser may ask for history a few milliseconds
 * before the provider publishes the closing result. Hold that one request for
 * a short bounded window so its response already contains the period that just
 * ended; normal history requests still return immediately.
 */
/**
 * RaxiWin public presentation: the whole screen runs one period behind the
 * provider feed. The draw the provider labels X is shown, and bet on, as the
 * player's label X-1. Only the label moves - premium/number/color stay exactly
 * as drawn - and a period that is still running (provider issue >= current) is
 * never exposed, so a closing draw can never be revealed before its timer.
 */
function raxiwin_display_previous_periods($gameCode, $list)
{
    if (!is_array($list) || !$list || strpos((string)$gameCode, 'WinGo_') !== 0) {
        return $list;
    }
    $currentIssue = sl_wingo_current_issue($gameCode);
    $visible = array();
    foreach ($list as $item) {
        $issue = isset($item['issueNumber']) ? (string)$item['issueNumber'] : '';
        if (preg_match('/^\d{17}$/', $issue)) {
            if ($currentIssue !== '' && strcmp($issue, $currentIssue) >= 0) {
                // Current (or future) period: never expose it before it ends.
                continue;
            }
            $offset = sl_display_label_offset();
            if ($offset !== 0) {
                $item['issueNumber'] = sl_issue_shift($issue, $offset);
            }
        }
        $visible[] = $item;
    }
    return $visible ? array_values($visible) : $list;
}

/**
 * Same one-period-behind presentation for the running-period payload the client
 * uses as its header label and as the bet issue. The countdown times stay
 * canonical, so the label the player sees ends exactly when its draw arrives.
 */
function raxiwin_public_current($gameCode, $payload)
{
    if (!is_array($payload) || strpos((string)$gameCode, 'WinGo_') !== 0) {
        return $payload;
    }
    foreach (array('previous', 'current', 'next') as $key) {
        if (isset($payload[$key]['issueNumber']) && preg_match('/^\d{17}$/', (string)$payload[$key]['issueNumber'])) {
            $offset = sl_display_label_offset();
            if ($offset !== 0) {
                $payload[$key]['issueNumber'] = sl_issue_shift((string)$payload[$key]['issueNumber'], $offset);
            }
        }
    }
    return $payload;
}

function dlv4_history_with_boundary_wait($gameCode, $input)
{
    $expectedIssue = '';
    $pageNo = max(1, (int)($input['pageNo'] ?? 1));
    if ($pageNo === 1) {
        $current = sl_provider_current($gameCode);
        if (is_array($current) && isset($current['previous']['issueNumber'])) {
            $expectedIssue = (string)$current['previous']['issueNumber'];
        }
        if ($expectedIssue === '') {
            $local = sl_local_current($gameCode);
            $expectedIssue = (string)($local['previous']['issueNumber'] ?? '');
        }
    }

    $deadline = microtime(true) + 3.0;
    $data = array('list'=>array(),'pageNo'=>$pageNo,'totalPage'=>0,'totalCount'=>0);
    do {
        $data = sl_history_page($gameCode, $input);
        $data['list'] = sl_rebind_wingo_history_periods($gameCode, $data['list'] ?? array());
        $latestIssue = isset($data['list'][0]['issueNumber']) ? (string)$data['list'][0]['issueNumber'] : '';
        if ($expectedIssue === '' || ($latestIssue !== '' && strcmp($latestIssue, $expectedIssue) >= 0)) {
            break;
        }
        usleep(50000);
    } while (microtime(true) < $deadline);

    return $data;
}

try {
    $gameCode = isset($_GET['gameCode']) ? preg_replace('/[^A-Za-z0-9_-]/', '', (string) $_GET['gameCode']) : '';
    $lottery = isset($_GET['lottery']) ? preg_replace('/[^A-Za-z0-9_-]/', '', (string) $_GET['lottery']) : '';

    if (strcasecmp($gameCode, 'WinGo_30S') === 0) $gameCode = 'WinGo_30S';
    elseif (strcasecmp($gameCode, 'WinGo_1M') === 0) $gameCode = 'WinGo_1M';
    elseif (strcasecmp($gameCode, 'WinGo_3M') === 0) $gameCode = 'WinGo_3M';
    elseif (strcasecmp($gameCode, 'WinGo_5M') === 0) $gameCode = 'WinGo_5M';

    $game = sl_game_config($gameCode);
    if (!$game || strcasecmp((string) $game['lottery'], $lottery) !== 0) {
        sl_fail(7, 'Unsupported game code', 7, 404);
    }

    if (!empty($_GET['history'])) {
        $input = $_GET;
        $input['pageNo'] = isset($_GET['pageNo']) ? max(1, (int) $_GET['pageNo']) : 1;
        $input['pageSize'] = 10;
        // Preserve the exact-period admin overlay and settlement, then apply
        // one final period normalisation at the public response boundary.
        $data = dlv4_history_with_boundary_wait($gameCode, $input);
        // Keep database settlement canonical; shift only the public result label.
        if (isset($data['list']) && is_array($data['list'])) {
            $data['list'] = raxiwin_display_previous_periods($gameCode, $data['list']);
        }
        $data['totalPage'] = 50;
        $data['totalCount'] = 500;
        sl_send(array('code' => 0, 'msg' => 'success', 'data' => $data), 200);
    }

    $payload = sl_provider_current($gameCode);
    if (!$payload) {
        sl_fail(503, 'Real current draw is temporarily unavailable', 503, 503);
    }
    // Present the running period under the one-behind label the player sees.
    sl_send(raxiwin_public_current($gameCode, $payload), 200);
} catch (Throwable $e) {
    error_log('[draw-live-v4] ' . $e->getMessage());
    sl_fail(500, 'Real draw service is temporarily unavailable', 500, 500);
}
