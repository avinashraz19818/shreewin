<?php
/**
 * Additive, self-healing schema used by the admin and live game APIs.
 * It never drops user data and every optional operation is isolated so a
 * restricted cPanel database account cannot turn a page into HTTP 500.
 */
if (!isset($conn) || !($conn instanceof mysqli)) return;

$adminSchemaVersion = '20260814-daman-finance-v7';
try {
    $conn->query("CREATE TABLE IF NOT EXISTS admin_schema_meta (schema_key VARCHAR(80) NOT NULL, schema_value VARCHAR(120) NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(schema_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $versionStmt = $conn->prepare("SELECT schema_value FROM admin_schema_meta WHERE schema_key='admin_runtime_version' LIMIT 1");
    $installedVersion = '';
    if ($versionStmt) { $versionStmt->execute(); $versionStmt->bind_result($installedVersion); $versionStmt->fetch(); $versionStmt->close(); }
    if ((string)$installedVersion === $adminSchemaVersion) return;
} catch (Throwable $e) {
    error_log('[admin schema meta] ' . $e->getMessage());
}

$adminSchemaQueries = array(
    "CREATE TABLE IF NOT EXISTS deyya (shonu BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, maulya VARCHAR(190) NOT NULL, sthiti TINYINT(1) NOT NULL DEFAULT 0, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(shonu), UNIQUE KEY uq_deyya_upi(maulya), KEY idx_deyya_active(sthiti)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS upi_withdrawal (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, user_id BIGINT NOT NULL, upi_id VARCHAR(190) NOT NULL, mobile VARCHAR(32) NOT NULL DEFAULT '', name VARCHAR(120) NOT NULL DEFAULT '', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(id), UNIQUE KEY uq_upi_withdrawal_user(user_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS sametrend (id TINYINT NOT NULL DEFAULT 1, status VARCHAR(20) NOT NULL DEFAULT 'active', updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS same_trend_manager (id INT UNSIGNED NOT NULL AUTO_INCREMENT, domain VARCHAR(190) NOT NULL DEFAULT '', server_ip VARCHAR(80) NOT NULL DEFAULT '', api_key VARCHAR(190) NOT NULL DEFAULT '', wingo TINYINT(1) NOT NULL DEFAULT 1, k3 TINYINT(1) NOT NULL DEFAULT 1, `5d` TINYINT(1) NOT NULL DEFAULT 1, trx_wingo TINYINT(1) NOT NULL DEFAULT 1, motoracing TINYINT(1) NOT NULL DEFAULT 1, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(id), UNIQUE KEY uq_same_trend_domain(domain)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS admin_predictions (id INT AUTO_INCREMENT PRIMARY KEY, game_type VARCHAR(20) NOT NULL, prediction_number VARCHAR(64) NOT NULL, prediction_sum INT NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, is_active BOOLEAN DEFAULT TRUE, KEY idx_admin_prediction_game(game_type,is_active)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS auto_payin_gateways (id INT UNSIGNED NOT NULL AUTO_INCREMENT,gateway_name VARCHAR(80) NOT NULL,display_name VARCHAR(120) NOT NULL,api_url VARCHAR(500) NOT NULL DEFAULT '',merchant_id VARCHAR(190) NOT NULL DEFAULT '',secret_key VARCHAR(500) NOT NULL DEFAULT '',channel_code VARCHAR(100) NOT NULL DEFAULT '',is_active TINYINT(1) NOT NULL DEFAULT 0,updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,PRIMARY KEY(id),UNIQUE KEY uq_payin_gateway_name(gateway_name)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS bonus_settings (id TINYINT UNSIGNED NOT NULL DEFAULT 1,is_enabled TINYINT(1) NOT NULL DEFAULT 0,bonus_percent DECIMAL(8,4) NOT NULL DEFAULT 0,updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,PRIMARY KEY(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS bonus_log (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,user_id BIGINT NOT NULL,bonus_amount DECIMAL(18,4) NOT NULL,refnum VARCHAR(120) NOT NULL DEFAULT '',created_at DATETIME NOT NULL,PRIMARY KEY(id),UNIQUE KEY uq_bonus_log_ref(refnum),KEY idx_bonus_log_user(user_id,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS mrcoder_withdrawals (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,user_id BIGINT NOT NULL,amount DECIMAL(18,4) NOT NULL DEFAULT 0,sthithi TINYINT NOT NULL DEFAULT 0,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,updated_at DATETIME NULL,PRIMARY KEY(id),KEY idx_mrcoder_withdraw_state(sthithi,created_at),KEY idx_mrcoder_withdraw_user(user_id,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS auto_payout_gateways (id INT UNSIGNED NOT NULL AUTO_INCREMENT,gateway_name VARCHAR(80) NOT NULL,display_name VARCHAR(120) NOT NULL,api_url VARCHAR(500) NOT NULL DEFAULT '',merchant_id VARCHAR(190) NOT NULL DEFAULT '',secret_key VARCHAR(500) NOT NULL DEFAULT '',notify_url VARCHAR(500) NOT NULL DEFAULT '',currency_code VARCHAR(10) NOT NULL DEFAULT 'INR',is_active TINYINT(1) NOT NULL DEFAULT 0,updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,PRIMARY KEY(id),UNIQUE KEY uq_payout_gateway_name(gateway_name)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS saas_lottery_bets (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,user_id BIGINT NOT NULL,game_code VARCHAR(32) NOT NULL,issue_number VARCHAR(40) NOT NULL,bet_content VARCHAR(190) NOT NULL,amount DECIMAL(18,4) NOT NULL,bet_multiple INT NOT NULL,bet_units INT NOT NULL DEFAULT 1,stake DECIMAL(18,4) NOT NULL,request_group_key CHAR(64) NOT NULL,request_key CHAR(64) NOT NULL,status VARCHAR(16) NOT NULL DEFAULT 'pending',result_premium VARCHAR(64) NULL,payout DECIMAL(18,4) NOT NULL DEFAULT 0,tax_fee DECIMAL(18,4) NOT NULL DEFAULT 0,created_at DATETIME NOT NULL,settled_at DATETIME NULL,vip_exp_applied TINYINT(1) NOT NULL DEFAULT 0,PRIMARY KEY(id),UNIQUE KEY uq_saas_lottery_request(request_key),KEY idx_saas_lottery_group(request_group_key),KEY idx_saas_lottery_user_created(user_id,created_at),KEY idx_saas_lottery_issue_status(game_code,issue_number,status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS saas_lottery_results (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,game_code VARCHAR(32) NOT NULL,issue_number VARCHAR(40) NOT NULL,premium VARCHAR(64) NOT NULL,number VARCHAR(32) NOT NULL DEFAULT '',color VARCHAR(32) NOT NULL DEFAULT '',result_sum INT NOT NULL DEFAULT 0,provider_seen_at DATETIME NOT NULL,created_at DATETIME NOT NULL,PRIMARY KEY(id),UNIQUE KEY uq_saas_lottery_result(game_code,issue_number),KEY idx_saas_lottery_result_created(game_code,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS saas_lottery_overrides (game_code VARCHAR(32) NOT NULL,issue_number VARCHAR(40) NOT NULL,premium VARCHAR(64) NOT NULL,created_by VARCHAR(100) NOT NULL,created_at DATETIME NOT NULL,PRIMARY KEY(game_code,issue_number),KEY idx_saas_override_created(created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ,"CREATE TABLE IF NOT EXISTS app_settings (setting_key VARCHAR(100) NOT NULL,setting_value TEXT NOT NULL,group_name VARCHAR(80) NOT NULL DEFAULT 'General',updated_at DATETIME NOT NULL,PRIMARY KEY(setting_key),KEY idx_app_settings_group(group_name)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ,"CREATE TABLE IF NOT EXISTS app_game_control (game_code VARCHAR(40) NOT NULL,enabled TINYINT(1) NOT NULL DEFAULT 1,result_source VARCHAR(30) NOT NULL DEFAULT 'api',api_url TEXT NULL,api_timeout_seconds INT NOT NULL DEFAULT 5,lock_before_close_seconds INT NOT NULL DEFAULT 2,manual_override_enabled TINYINT(1) NOT NULL DEFAULT 0,updated_at DATETIME NOT NULL,PRIMARY KEY(game_code)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ,"CREATE TABLE IF NOT EXISTS app_site_messages (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,title VARCHAR(190) NOT NULL,message TEXT NOT NULL,enabled TINYINT(1) NOT NULL DEFAULT 1,sort_order INT NOT NULL DEFAULT 0,starts_at DATETIME NULL,ends_at DATETIME NULL,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,PRIMARY KEY(id),KEY idx_site_messages_active(enabled,sort_order),KEY idx_site_messages_window(starts_at,ends_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ,"CREATE TABLE IF NOT EXISTS saas_owner_auth (id TINYINT UNSIGNED NOT NULL DEFAULT 1,password_hash VARCHAR(255) NOT NULL,failed_attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,locked_until DATETIME NULL,last_login_at DATETIME NULL,password_changed_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,PRIMARY KEY(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ,"CREATE TABLE IF NOT EXISTS saas_owner_audit (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,action VARCHAR(100) NOT NULL,details VARCHAR(500) NOT NULL DEFAULT '',ip_hash CHAR(64) NOT NULL,user_agent_hash CHAR(64) NOT NULL,created_at DATETIME NOT NULL,PRIMARY KEY(id),KEY idx_owner_audit_created(created_at),KEY idx_owner_audit_action(action)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ,"CREATE TABLE IF NOT EXISTS saas_project_registry (id TINYINT UNSIGNED NOT NULL DEFAULT 1,domain VARCHAR(190) NOT NULL,project_name VARCHAR(190) NOT NULL DEFAULT 'Daman',site_enabled TINYINT(1) NOT NULL DEFAULT 1,maintenance_mode TINYINT(1) NOT NULL DEFAULT 0,maintenance_message VARCHAR(500) NOT NULL DEFAULT '',primary_color CHAR(7) NOT NULL DEFAULT '#ff5a5f',last_seen_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,PRIMARY KEY(id),UNIQUE KEY uq_saas_project_domain(domain)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);
foreach ($adminSchemaQueries as $adminSchemaSql) {
    try { $conn->query($adminSchemaSql); } catch (Throwable $e) { error_log('[admin schema] ' . $e->getMessage()); }
}

// Additive migrations for databases created by an older package.
foreach (array(
    "ALTER TABLE same_trend_manager ADD COLUMN motoracing TINYINT(1) NOT NULL DEFAULT 1",
    "ALTER TABLE sametrend ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active'",
    "ALTER TABLE sametrend ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
    "ALTER TABLE deyya ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
    "ALTER TABLE upi_withdrawal ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
    "ALTER TABLE hintegedukolli ADD COLUMN out_trade_no VARCHAR(120) NULL",
    "ALTER TABLE hintegedukolli ADD COLUMN pay_order_no VARCHAR(120) NULL",
    "ALTER TABLE hintegedukolli ADD COLUMN remarks VARCHAR(500) NULL",
    "ALTER TABLE hintegedukolli ADD COLUMN updated_at DATETIME NULL",
    "ALTER TABLE saas_lottery_bets ADD COLUMN tax_fee DECIMAL(18,4) NOT NULL DEFAULT 0 AFTER payout"
) as $migration) {
    try { $conn->query($migration); } catch (Throwable $ignored) {}
}
try {
    $conn->query("INSERT INTO bonus_settings(id,is_enabled,bonus_percent,updated_at) VALUES(1,0,0,NOW()) ON DUPLICATE KEY UPDATE id=id");
    $conn->query("UPDATE saas_lottery_bets SET tax_fee=ROUND(stake*0.02,4) WHERE stake>0 AND tax_fee=0");
    $conn->query("INSERT INTO sametrend(id,status,updated_at) VALUES(1,'active',NOW()) ON DUPLICATE KEY UPDATE status='active',updated_at=NOW()");
    $conn->query("UPDATE same_trend_manager SET wingo=1,k3=1,`5d`=1,trx_wingo=1,motoracing=1");
    $upiResult = $conn->query('SELECT shonu FROM deyya ORDER BY sthiti DESC,shonu DESC LIMIT 1');
    $upiRow = $upiResult ? $upiResult->fetch_assoc() : null;
    if ($upiRow) {
        $activeUpiId = (int)$upiRow['shonu'];
        $conn->query('UPDATE deyya SET sthiti=0');
        $activateUpi = $conn->prepare('UPDATE deyya SET sthiti=1 WHERE shonu=?');
        if ($activateUpi) { $activateUpi->bind_param('i', $activeUpiId); $activateUpi->execute(); $activateUpi->close(); }
    }
    $stmt = $conn->prepare("INSERT INTO admin_schema_meta(schema_key,schema_value,updated_at) VALUES('admin_runtime_version',?,NOW()) ON DUPLICATE KEY UPDATE schema_value=VALUES(schema_value),updated_at=NOW()");
    if ($stmt) { $stmt->bind_param('s', $adminSchemaVersion); $stmt->execute(); $stmt->close(); }
} catch (Throwable $e) {
    error_log('[admin schema seed] ' . $e->getMessage());
}
