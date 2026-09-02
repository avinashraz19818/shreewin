<?php
/**
 * Shared VIP progression helpers.
 *
 * One rupee of accepted stake earns one EXP. Cash rewards remain manual claims
 * through the existing VIP reward endpoints, so progressing a level never
 * credits the same reward twice.
 */

function app_vip_level_from_experience($experience)
{
    $experience = max(0, (int)$experience);
    if ($experience >= 20000000) return 5;
    if ($experience >= 4000000) return 4;
    if ($experience >= 400000) return 3;
    if ($experience >= 30000) return 2;
    if ($experience >= 3000) return 1;
    return 0;
}

function app_vip_experience_from_stake($stake)
{
    return max(0, (int)floor(((float)$stake) + 0.0000001));
}

function app_vip_ensure_saas_tracking(mysqli $db, $runBackfill = true)
{
    static $schemaReady = false;

    $table = $db->query("SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='saas_lottery_bets' LIMIT 1");
    if (!$table || $table->num_rows < 1) {
        return false;
    }

    if (!$schemaReady) {
        $column = $db->query("SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='saas_lottery_bets' AND column_name='vip_exp_applied' LIMIT 1");
        if (!$column || $column->num_rows < 1) {
            if (!$db->query('ALTER TABLE saas_lottery_bets ADD COLUMN vip_exp_applied TINYINT(1) NOT NULL DEFAULT 0 AFTER settled_at') && (int)$db->errno !== 1060) {
                throw new RuntimeException('Unable to enable VIP experience tracking');
            }
        }

        $index = $db->query("SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='saas_lottery_bets' AND index_name='idx_saas_vip_exp' LIMIT 1");
        if (!$index || $index->num_rows < 1) {
            // The index is only a performance optimisation. A restricted
            // shared-hosting database user may be allowed to add the column
            // but not the index; VIP accounting must still remain correct.
            if (!$db->query('ALTER TABLE saas_lottery_bets ADD KEY idx_saas_vip_exp(vip_exp_applied,user_id)') && (int)$db->errno !== 1061) {
                error_log('[vip] Optional SaaS experience index was not created: ' . $db->error);
            }
        }
        $schemaReady = true;
    }

    if ($runBackfill) {
        app_vip_backfill_saas_bets($db);
    }
    return true;
}

function app_vip_read_progress(mysqli $db, $userId)
{
    $userId = (int)$userId;
    $progress = array('experience'=>0, 'level'=>0);
    if ($userId <= 0) {
        return $progress;
    }

    $stmt = $db->prepare('SELECT expe,lvl FROM vip WHERE userid=? LIMIT 1');
    if (!$stmt) throw new RuntimeException('VIP lookup unavailable');
    $stmt->bind_param('i', $userId);
    if (!$stmt->execute()) throw new RuntimeException('VIP lookup failed');
    $experience = 0;
    $storedLevel = 0;
    $stmt->bind_result($experience, $storedLevel);
    $found = $stmt->fetch();
    $stmt->close();

    if ($found) {
        $progress['experience'] = max(0, (int)$experience);
        $progress['level'] = app_vip_level_from_experience($progress['experience']);
        if ((int)$storedLevel !== (int)$progress['level']) {
            // Keep reward eligibility and the VIP screen on the same derived
            // level even for rows created by an older deployment.
            $derivedLevel = (int)$progress['level'];
            $update = $db->prepare('UPDATE vip SET lvl=? WHERE userid=? AND lvl<>?');
            if ($update) {
                $update->bind_param('iii', $derivedLevel, $userId, $derivedLevel);
                $update->execute();
                $update->close();
            }
        }
    }
    return $progress;
}

function app_vip_apply_experience(mysqli $db, $userId, $stake, $now = null)
{
    $userId = (int)$userId;
    $gain = app_vip_experience_from_stake($stake);
    if ($userId <= 0 || $gain <= 0) {
        return array('experience_added'=>0, 'experience'=>0, 'level'=>0);
    }

    $stmt = $db->prepare('SELECT expe,lvl FROM vip WHERE userid=? LIMIT 1 FOR UPDATE');
    if (!$stmt) throw new RuntimeException('VIP lock unavailable');
    $stmt->bind_param('i', $userId);
    if (!$stmt->execute()) throw new RuntimeException('VIP lock failed');
    $oldExperience = 0;
    $oldLevel = 0;
    $stmt->bind_result($oldExperience, $oldLevel);
    $found = $stmt->fetch();
    $stmt->close();

    $oldExperience = $found ? max(0, (int)$oldExperience) : 0;
    $newExperience = $oldExperience + $gain;
    $newLevel = app_vip_level_from_experience($newExperience);
    $createdAt = $now === null ? date('Y-m-d H:i:s') : (string)$now;

    if ($found) {
        $update = $db->prepare('UPDATE vip SET expe=?,lvl=?,createdate=? WHERE userid=?');
        if (!$update) throw new RuntimeException('VIP update unavailable');
        $update->bind_param('iisi', $newExperience, $newLevel, $createdAt, $userId);
    } else {
        $update = $db->prepare('INSERT INTO vip(userid,expe,lvl,createdate) VALUES (?,?,?,?)');
        if (!$update) throw new RuntimeException('VIP insert unavailable');
        $update->bind_param('iiis', $userId, $newExperience, $newLevel, $createdAt);
    }
    if (!$update->execute()) {
        $update->close();
        throw new RuntimeException('VIP update failed');
    }
    $update->close();

    return array(
        'experience_added'=>$gain,
        'experience'=>$newExperience,
        'level'=>$newLevel,
    );
}

/**
 * Reconcile one member's accepted SaaS bets exactly once. This is also called
 * by the VIP read endpoints, so a previously accepted WinGo/K3/5D/TRX/Moto
 * bet cannot remain invisible merely because an earlier deployment stopped
 * between its schema migration and the VIP screen refresh.
 */
function app_vip_sync_user_saas_bets(mysqli $db, $userId)
{
    $userId = (int)$userId;
    if ($userId <= 0 || !app_vip_ensure_saas_tracking($db, false)) {
        return app_vip_read_progress($db, $userId);
    }

    if (!$db->begin_transaction()) {
        throw new RuntimeException('VIP reconciliation transaction unavailable');
    }
    try {
        $lookup = $db->prepare('SELECT id,stake FROM saas_lottery_bets WHERE user_id=? AND vip_exp_applied=0 FOR UPDATE');
        if (!$lookup) throw new RuntimeException('VIP reconciliation lookup unavailable');
        $lookup->bind_param('i', $userId);
        if (!$lookup->execute()) throw new RuntimeException('VIP reconciliation lookup failed');
        $result = $lookup->get_result();
        $stake = 0.0;
        while ($result && ($row = $result->fetch_assoc())) {
            $stake += max(0.0, (float)$row['stake']);
        }
        $lookup->close();

        if ($stake > 0) {
            app_vip_apply_experience($db, $userId, $stake);
        }
        $mark = $db->prepare('UPDATE saas_lottery_bets SET vip_exp_applied=1 WHERE user_id=? AND vip_exp_applied=0');
        if (!$mark) throw new RuntimeException('VIP reconciliation marker unavailable');
        $mark->bind_param('i', $userId);
        if (!$mark->execute()) throw new RuntimeException('VIP reconciliation marker failed');
        $mark->close();
        if (!$db->commit()) throw new RuntimeException('VIP reconciliation commit failed');
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }

    return app_vip_read_progress($db, $userId);
}

/**
 * Best-effort sync plus an independent read. A migration/backfill warning must
 * never make an existing VIP row appear as 0 EXP to the client.
 */
function app_vip_get_progress(mysqli $db, $userId)
{
    $userId = (int)$userId;
    try {
        return app_vip_sync_user_saas_bets($db, $userId);
    } catch (Throwable $e) {
        error_log('[vip] SaaS experience sync failed for user ' . $userId . ': ' . $e->getMessage());
    }

    try {
        return app_vip_read_progress($db, $userId);
    } catch (Throwable $e) {
        error_log('[vip] Experience read failed for user ' . $userId . ': ' . $e->getMessage());
        return array('experience'=>0, 'level'=>0);
    }
}

/**
 * Credit historical SaaS bets exactly once after the vip_exp_applied column is
 * introduced. Each user's increment and marker update share one transaction.
 */
function app_vip_backfill_saas_bets(mysqli $db)
{
    static $completed = false;
    if ($completed) return;

    $users = array();
    $result = $db->query('SELECT DISTINCT user_id FROM saas_lottery_bets WHERE vip_exp_applied=0 ORDER BY user_id');
    while ($result && ($row = $result->fetch_assoc())) {
        $userId = (int)($row['user_id'] ?? 0);
        if ($userId > 0) $users[] = $userId;
    }

    foreach ($users as $userId) {
        app_vip_sync_user_saas_bets($db, $userId);
    }

    $completed = true;
}
