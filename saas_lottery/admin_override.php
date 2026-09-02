<?php
/**
 * Period-bound WinGo admin overrides.
 *
 * The legacy admin tables only store the selected number.  This helper keeps
 * the selected number tied to one exact SaaS issue so an old, newly-synced
 * history row can never consume an override intended for the current round.
 */

function sl_admin_override_game_config($gameCode)
{
    static $games = array(
        'WinGo_30S' => array(
            'family_code' => 100,
            'interval_seconds' => 30,
            'interval_code' => 5,
            'manual_table' => 'hastacalita_phalitansa_zehn',
            'queue_table' => 'gelluonduhogu_zehn_zehn',
            'period_table' => 'gelluonduhogu_zehn',
        ),
        'WinGo_1M' => array(
            'family_code' => 100,
            'interval_seconds' => 60,
            'interval_code' => 1,
            'manual_table' => 'hastacalita_phalitansa',
            'queue_table' => 'gelluonduhogu_zehn_zehn_1',
            'period_table' => 'gelluonduhogu',
        ),
        'WinGo_3M' => array(
            'family_code' => 100,
            'interval_seconds' => 180,
            'interval_code' => 2,
            'manual_table' => 'hastacalita_phalitansa_drei',
            'queue_table' => 'gelluonduhogu_zehn_zehn_2',
            'period_table' => 'gelluonduhogu_drei',
        ),
        'WinGo_5M' => array(
            'family_code' => 100,
            'interval_seconds' => 300,
            'interval_code' => 3,
            'manual_table' => 'hastacalita_phalitansa_funf',
            'queue_table' => 'gelluonduhogu_zehn_zehn_3',
            'period_table' => 'gelluonduhogu_funf',
        ),
        'TrxWinGo_1M' => array('family_code'=>103,'interval_seconds'=>60,'interval_code'=>1,'legacy_type'=>'ktrx'),
        'TrxWinGo_3M' => array('family_code'=>103,'interval_seconds'=>180,'interval_code'=>2,'legacy_type'=>'ktrx3'),
        'TrxWinGo_5M' => array('family_code'=>103,'interval_seconds'=>300,'interval_code'=>3,'legacy_type'=>'ktrx5'),
        'TrxWinGo_10M' => array('family_code'=>103,'interval_seconds'=>600,'interval_code'=>4,'legacy_type'=>'ktrx10'),
        'K3_1M' => array('family_code'=>101,'interval_seconds'=>60,'interval_code'=>1,'manual_table'=>'hastacalita_phalitansa_kemeru','legacy_type'=>'k31'),
        'K3_3M' => array('family_code'=>101,'interval_seconds'=>180,'interval_code'=>2,'manual_table'=>'hastacalita_phalitansa_kemeru_drei','legacy_type'=>'k33'),
        'K3_5M' => array('family_code'=>101,'interval_seconds'=>300,'interval_code'=>3,'manual_table'=>'hastacalita_phalitansa_kemeru_funf','legacy_type'=>'k35'),
        'K3_10M' => array('family_code'=>101,'interval_seconds'=>600,'interval_code'=>4,'manual_table'=>'hastacalita_phalitansa_kemeru_zehn','legacy_type'=>'k310'),
        'D5_1M' => array('family_code'=>102,'interval_seconds'=>60,'interval_code'=>1,'manual_table'=>'hastacalita_phalitansa_aidudi','legacy_type'=>'5d1'),
        'D5_3M' => array('family_code'=>102,'interval_seconds'=>180,'interval_code'=>2,'manual_table'=>'hastacalita_phalitansa_aidudi_drei','legacy_type'=>'5d3'),
        'D5_5M' => array('family_code'=>102,'interval_seconds'=>300,'interval_code'=>3,'manual_table'=>'hastacalita_phalitansa_aidudi_funf','legacy_type'=>'5d5'),
        'D5_10M' => array('family_code'=>102,'interval_seconds'=>600,'interval_code'=>4,'manual_table'=>'hastacalita_phalitansa_aidudi_zehn','legacy_type'=>'5d10'),
        'MotoRace_1M' => array('family_code'=>105,'interval_seconds'=>60,'interval_code'=>1),
    );

    return isset($games[$gameCode]) ? $games[$gameCode] : null;
}

function sl_admin_override_table_exists($conn, $table)
{
    if (!($conn instanceof mysqli) || !preg_match('/^[A-Za-z0-9_]+$/', (string)$table)) {
        return false;
    }
    $escaped = $conn->real_escape_string((string)$table);
    $result = $conn->query("SHOW TABLES LIKE '" . $escaped . "'");
    return $result && $result->num_rows > 0;
}

function sl_admin_override_ensure_schema($conn)
{
    static $installed = false;
    if ($installed) {
        return;
    }
    if (!($conn instanceof mysqli)) {
        throw new RuntimeException('Database connection is not available');
    }

    $sql = "CREATE TABLE IF NOT EXISTS saas_lottery_overrides ("
        . "game_code VARCHAR(32) NOT NULL,"
        . "issue_number VARCHAR(40) NOT NULL,"
        . "premium VARCHAR(64) NOT NULL,"
        . "created_by VARCHAR(100) NOT NULL,"
        . "created_at DATETIME NOT NULL,"
        . "PRIMARY KEY(game_code,issue_number),"
        . "KEY idx_saas_override_created(created_at)"
        . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if (!$conn->query($sql)) {
        throw new RuntimeException('Unable to create the WinGo override table');
    }
    $installed = true;
}

function sl_admin_override_period_id($gameCode, $timestamp = null)
{
    $config = sl_admin_override_game_config($gameCode);
    if (!$config) {
        throw new InvalidArgumentException('Unsupported WinGo game code');
    }

    $now = $timestamp === null ? time() : (int)$timestamp;
    $interval = (int)$config['interval_seconds'];
    $dayStart = (int)strtotime(gmdate('Y-m-d', $now) . ' 00:00:00 UTC');
    $sequence = (int)floor(($now - $dayStart) / $interval) + 1;

    return gmdate('Ymd', $now)
        . sprintf('%03d%02d%04d', (int)$config['family_code'], (int)$config['interval_code'], $sequence);
}

function sl_admin_override_normalize_premium($gameCode, $premium)
{
    $premium = trim((string)$premium);
    if (strpos($gameCode, 'WinGo_') === 0 || strpos($gameCode, 'TrxWinGo_') === 0) {
        return preg_match('/^[0-9]$/', $premium) ? $premium : null;
    }
    if (strpos($gameCode, 'K3_') === 0) {
        return preg_match('/^[1-6]{3}$/', $premium) ? $premium : null;
    }
    if (strpos($gameCode, 'D5_') === 0) {
        return preg_match('/^[0-9]{5}$/', $premium) ? $premium : null;
    }
    if ($gameCode === 'MotoRace_1M') {
        $parts = array_values(array_filter(array_map('trim', explode(',', $premium)), 'strlen'));
        $numbers = array_map('intval', $parts);
        $sorted = $numbers;
        sort($sorted);
        return count($numbers) === 10 && $sorted === range(1, 10) ? implode(',', $numbers) : null;
    }
    return null;
}

function sl_admin_override_result_exists($conn, $gameCode, $issueNumber)
{
    if (!sl_admin_override_table_exists($conn, 'saas_lottery_results')) {
        return false;
    }
    $stmt = $conn->prepare('SELECT 1 FROM saas_lottery_results WHERE game_code=? AND issue_number=? LIMIT 1');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('ss', $gameCode, $issueNumber);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $exists;
}

function sl_admin_override_remove_unsettled_result($conn, $gameCode, $issueNumber)
{
    if (!sl_admin_override_table_exists($conn, 'saas_lottery_results')) {
        return;
    }

    if (sl_admin_override_table_exists($conn, 'saas_lottery_bets')) {
        $stmt = $conn->prepare(
            "DELETE r FROM saas_lottery_results r "
            . "LEFT JOIN saas_lottery_bets b ON b.game_code=r.game_code "
            . "AND b.issue_number=r.issue_number AND b.status<>'pending' "
            . "WHERE r.game_code=? AND r.issue_number=? AND b.id IS NULL"
        );
    } else {
        $stmt = $conn->prepare('DELETE FROM saas_lottery_results WHERE game_code=? AND issue_number=?');
    }
    if (!$stmt) {
        throw new RuntimeException('Unable to verify the active WinGo period');
    }
    $stmt->bind_param('ss', $gameCode, $issueNumber);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException('Unable to verify the active WinGo period');
    }
    $stmt->close();
}

function sl_admin_override_get($conn, $gameCode, $issueNumber = null)
{
    sl_admin_override_ensure_schema($conn);

    if ($issueNumber !== null) {
        $stmt = $conn->prepare('SELECT issue_number,premium,created_by,created_at FROM saas_lottery_overrides WHERE game_code=? AND issue_number=? LIMIT 1');
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('ss', $gameCode, $issueNumber);
    } else {
        $stmt = $conn->prepare('SELECT issue_number,premium,created_by,created_at FROM saas_lottery_overrides WHERE game_code=? ORDER BY created_at DESC,issue_number DESC LIMIT 1');
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $gameCode);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    if (!$row || sl_admin_override_normalize_premium($gameCode, (string)$row['premium']) === null) {
        return null;
    }
    return $row;
}

function sl_admin_override_get_pending($conn, $gameCode)
{
    sl_admin_override_ensure_schema($conn);
    if (!sl_admin_override_table_exists($conn, 'saas_lottery_results')) {
        return sl_admin_override_get($conn, $gameCode);
    }

    $stmt = $conn->prepare(
        'SELECT o.issue_number,o.premium,o.created_by,o.created_at '
        . 'FROM saas_lottery_overrides o '
        . 'LEFT JOIN saas_lottery_results r '
        . 'ON r.game_code=o.game_code AND r.issue_number=o.issue_number '
        . 'WHERE o.game_code=? AND r.id IS NULL '
        . 'ORDER BY o.created_at DESC,o.issue_number DESC LIMIT 1'
    );
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('s', $gameCode);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return $row && sl_admin_override_normalize_premium($gameCode, (string)$row['premium']) !== null ? $row : null;
}

function sl_admin_override_delete_pending($conn, $gameCode)
{
    sl_admin_override_ensure_schema($conn);
    if (sl_admin_override_table_exists($conn, 'saas_lottery_results')) {
        $stmt = $conn->prepare(
            'DELETE o FROM saas_lottery_overrides o '
            . 'LEFT JOIN saas_lottery_results r '
            . 'ON r.game_code=o.game_code AND r.issue_number=o.issue_number '
            . 'WHERE o.game_code=? AND r.id IS NULL'
        );
    } else {
        $stmt = $conn->prepare('DELETE FROM saas_lottery_overrides WHERE game_code=?');
    }
    if (!$stmt) {
        throw new RuntimeException('Unable to clear the pending WinGo override');
    }
    $stmt->bind_param('s', $gameCode);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException('Unable to clear the pending WinGo override');
    }
    $stmt->close();
}

function sl_admin_override_legacy_issue($conn, $gameCode)
{
    $config = sl_admin_override_game_config($gameCode);
    if (!$config || empty($config['period_table']) || !sl_admin_override_table_exists($conn, $config['period_table'])) {
        return '';
    }
    $table = $config['period_table'];
    $result = $conn->query("SELECT atadaaidi FROM `{$table}` ORDER BY kramasankhye DESC LIMIT 1");
    $row = $result ? $result->fetch_assoc() : null;
    $issue = $row ? (string)$row['atadaaidi'] : '';
    return preg_match('/^[0-9]{17}$/', $issue) ? $issue : '';
}

function sl_admin_override_set($conn, $gameCode, $number, $requestedIssue, $createdBy)
{
    $config = sl_admin_override_game_config($gameCode);
    $premium = sl_admin_override_normalize_premium($gameCode, $number);
    if (!$config || $premium === null) {
        throw new InvalidArgumentException('Invalid WinGo override');
    }
    sl_admin_override_ensure_schema($conn);

    // The server's active issue is authoritative.  A stale admin tab is never
    // allowed to target an already-finished period.
    $now = time();
    $currentIssue = sl_admin_override_period_id($gameCode, $now);
    $requestedIssue = preg_match('/^[0-9]{17}$/', (string)$requestedIssue)
        ? (string)$requestedIssue
        : '';
    $targetIssue = $requestedIssue === $currentIssue ? $requestedIssue : $currentIssue;

    // Old history mirrors could pre-create this UTC issue several hours too
    // early. Remove only an un-settled row; any period tied to a completed bet
    // remains immutable.
    sl_admin_override_remove_unsettled_result($conn, $gameCode, $targetIssue);
    if (sl_admin_override_result_exists($conn, $gameCode, $targetIssue)) {
        $targetIssue = sl_admin_override_period_id(
            $gameCode,
            $now + (int)$config['interval_seconds']
        );
    }

    $createdBy = trim((string)$createdBy);
    $createdBy = $createdBy === '' ? 'admin' : substr($createdBy, 0, 100);
    $manualTable = isset($config['manual_table']) ? (string)$config['manual_table'] : '';
    $queueTable = isset($config['queue_table']) ? (string)$config['queue_table'] : '';

    $conn->begin_transaction();
    try {
        // Replace only an unresolved selection. Keep settled issue rows so a
        // delayed legacy cron can still resolve the same immutable number.
        sl_admin_override_delete_pending($conn, $gameCode);

        $insert = $conn->prepare('INSERT INTO saas_lottery_overrides(game_code,issue_number,premium,created_by,created_at) VALUES (?,?,?,?,NOW())');
        if (!$insert) {
            throw new RuntimeException('Unable to save the WinGo override');
        }
        $insert->bind_param('ssss', $gameCode, $targetIssue, $premium, $createdBy);
        if (!$insert->execute()) {
            throw new RuntimeException('Unable to save the WinGo override');
        }
        $insert->close();

        if ($manualTable !== '' && sl_admin_override_table_exists($conn, $manualTable)) {
            if (!$conn->query("UPDATE `{$manualTable}` SET sthiti='0'")) {
                throw new RuntimeException('Unable to reset the legacy number state');
            }
            if (strpos($gameCode, 'WinGo_') === 0) {
                $activate = $conn->prepare("UPDATE `{$manualTable}` SET sthiti='1' WHERE sankhye=?");
                $legacyNumber = (int)$premium;
                if ($activate) {
                    $activate->bind_param('i', $legacyNumber);
                }
            } else {
                $activate = $conn->prepare("UPDATE `{$manualTable}` SET sthiti='1',sankhye=? WHERE shonu='1'");
                if ($activate) {
                    $activate->bind_param('s', $premium);
                }
            }
            if (!$activate || !$activate->execute()) {
                throw new RuntimeException('Unable to activate the legacy result');
            }
            $activate->close();
        }

        $legacyType = isset($config['legacy_type']) ? (string)$config['legacy_type'] : '';
        if ($legacyType !== '') {
            $conn->query("CREATE TABLE IF NOT EXISTS admin_predictions (id INT AUTO_INCREMENT PRIMARY KEY,game_type VARCHAR(20) NOT NULL,prediction_number VARCHAR(64) NOT NULL,prediction_sum INT NOT NULL DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,is_active BOOLEAN DEFAULT TRUE,KEY idx_admin_prediction_game(game_type,is_active)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $deactivate = $conn->prepare('UPDATE admin_predictions SET is_active=FALSE WHERE game_type=?');
            if ($deactivate) {
                $deactivate->bind_param('s', $legacyType);
                $deactivate->execute();
                $deactivate->close();
            }
            $predictionSum = array_sum(array_map('intval', str_split(preg_replace('/\D/', '', $premium))));
            $legacy = $conn->prepare('INSERT INTO admin_predictions(game_type,prediction_number,prediction_sum,is_active) VALUES (?,?,?,TRUE)');
            if ($legacy) {
                $legacy->bind_param('ssi', $legacyType, $premium, $predictionSum);
                $legacy->execute();
                $legacy->close();
            }
        }

        // Preserve compatibility with installations that still inspect the
        // one-row legacy queue.  The exact issue remains authoritative above.
        if ($queueTable !== '' && sl_admin_override_table_exists($conn, $queueTable)) {
            $conn->query("DELETE FROM `{$queueTable}`");
            $queue = $conn->prepare("INSERT INTO `{$queueTable}` (id,manual_number) VALUES (1,?) ON DUPLICATE KEY UPDATE manual_number=VALUES(manual_number)");
            if ($queue) {
                $legacyNumber = (int)$premium;
                $queue->bind_param('i', $legacyNumber);
                $queue->execute();
                $queue->close();
            }
        }

        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }

    return array('issue_number' => $targetIssue, 'premium' => $premium);
}

function sl_admin_override_clear($conn, $gameCode)
{
    $config = sl_admin_override_game_config($gameCode);
    if (!$config) {
        throw new InvalidArgumentException('Unsupported WinGo game code');
    }
    sl_admin_override_ensure_schema($conn);

    sl_admin_override_delete_pending($conn, $gameCode);
    $manualTable = isset($config['manual_table']) ? (string)$config['manual_table'] : '';
    if ($manualTable !== '' && sl_admin_override_table_exists($conn, $manualTable)) {
        $conn->query("UPDATE `{$manualTable}` SET sthiti='0'");
    }
    $queueTable = isset($config['queue_table']) ? (string)$config['queue_table'] : '';
    if ($queueTable !== '' && sl_admin_override_table_exists($conn, $queueTable)) {
        $conn->query("DELETE FROM `{$queueTable}`");
    }
    $legacyType = isset($config['legacy_type']) ? (string)$config['legacy_type'] : '';
    if ($legacyType !== '' && sl_admin_override_table_exists($conn, 'admin_predictions')) {
        $stmt = $conn->prepare('UPDATE admin_predictions SET is_active=FALSE WHERE game_type=?');
        if ($stmt) {
            $stmt->bind_param('s', $legacyType);
            $stmt->execute();
            $stmt->close();
        }
    }
}

function sl_admin_override_mark_applied($conn, $gameCode, $issueNumber)
{
    $override = sl_admin_override_get($conn, $gameCode, $issueNumber);
    if (!$override) {
        return;
    }
    $config = sl_admin_override_game_config($gameCode);
    if (!$config) {
        return;
    }

    // Keep the period-bound row as an audit/legacy synchronization record.
    // It can never affect another issue because every consumer matches the
    // exact issue_number.  The next admin selection replaces it.
    $manualTable = isset($config['manual_table']) ? (string)$config['manual_table'] : '';
    if ($manualTable !== '' && sl_admin_override_table_exists($conn, $manualTable)) {
        $conn->query("UPDATE `{$manualTable}` SET sthiti='0'");
    }
    $queueTable = isset($config['queue_table']) ? (string)$config['queue_table'] : '';
    if ($queueTable !== '' && sl_admin_override_table_exists($conn, $queueTable)) {
        $conn->query("DELETE FROM `{$queueTable}`");
    }
    $legacyType = isset($config['legacy_type']) ? (string)$config['legacy_type'] : '';
    if ($legacyType !== '' && sl_admin_override_table_exists($conn, 'admin_predictions')) {
        $stmt = $conn->prepare('UPDATE admin_predictions SET is_active=FALSE WHERE game_type=?');
        if ($stmt) {
            $stmt->bind_param('s', $legacyType);
            $stmt->execute();
            $stmt->close();
        }
    }
}
