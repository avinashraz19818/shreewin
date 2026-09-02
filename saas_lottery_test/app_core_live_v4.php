<?php
/**
 * Application control-center helpers shared by admin and API endpoints.
 */
if (!isset($conn) || !($conn instanceof mysqli)) {
    require_once __DIR__ . '/conn.php';
}
require_once dirname(__DIR__) . '/saas_lottery/admin_override.php';

function app_default_settings(): array
{
    return [
        'General' => [
            'site_enabled' => '1', 'maintenance_mode' => '0', 'registration_enabled' => '1',
            'maintenance_message' => 'We are improving the platform. Please check back shortly.',
            'login_enabled' => '1', 'profile_edit_enabled' => '1', 'referral_enabled' => '1',
            'announcement_enabled' => '1', 'pwa_enabled' => '1', 'api_status_enabled' => '1',
            'customer_service_live_chat_enabled' => '1', 'customer_service_external_fallback' => '0',
            'site_messages_enabled' => '1', 'notifications_enabled' => '1', 'spin_enabled' => '1',
            'daily_spin_limit' => '1', 'timezone' => 'Asia/Kolkata', 'site_title' => 'Shree Win',
            'support_display_name' => 'Shree Win Support',
        ],
        'Wallet & Finance' => [
            'wallet_enabled' => '1', 'wallet_auto_refresh' => '1', 'wallet_ledger_enabled' => '1',
            'deposit_enabled' => '1', 'withdraw_enabled' => '1', 'bonus_enabled' => '1',
            'manual_deposit_enabled' => '1', 'deposit_receipt_required' => '1',
            'deposit_admin_approval' => '1', 'withdraw_admin_approval' => '1',
            'wallet_negative_balance_block' => '1', 'wallet_transaction_locking' => '1',
            'duplicate_reference_block' => '1', 'finance_audit_trail' => '1',
            'registration_starting_balance' => '0', 'registration_bonus' => '0',
            'demo_starting_balance' => '5000', 'demo_withdraw_auto_complete' => '1',
            'min_demo_deposit' => '100', 'max_demo_deposit' => '50000',
            'min_demo_withdraw' => '100', 'max_demo_withdraw' => '50000',
            'lottery_payout_tax_percent' => '2.00', 'usdt_inr_rate' => '103',
            'withdraw_min_amount' => '110', 'withdraw_max_amount' => '50000',
            'withdraw_turnover_multiplier' => '1',
        ],
        'Lottery & Game' => [
            'game_WinGo_30S_enabled' => '1', 'game_WinGo_1M_enabled' => '1',
            'game_WinGo_3M_enabled' => '1', 'game_WinGo_5M_enabled' => '1',
            'game_TrxWinGo_1M_enabled' => '1', 'game_TrxWinGo_3M_enabled' => '1',
            'game_TrxWinGo_5M_enabled' => '1', 'game_TrxWinGo_10M_enabled' => '1',
            'game_K3_1M_enabled' => '1', 'game_K3_3M_enabled' => '1',
            'game_K3_5M_enabled' => '1', 'game_K3_10M_enabled' => '1',
            'game_D5_1M_enabled' => '1', 'game_D5_3M_enabled' => '1',
            'game_D5_5M_enabled' => '1', 'game_D5_10M_enabled' => '1',
            'game_MotoRace_1M_enabled' => '1',
            'betting_enabled' => '1', 'result_sync_enabled' => '1', 'auto_settlement_enabled' => '1',
            'winner_popup_enabled' => '1', 'game_history_enabled' => '1', 'my_history_enabled' => '1',
            'history_page_size' => '10', 'result_retry_enabled' => '1', 'result_retry_seconds' => '2',
            'settlement_idempotency_enabled' => '1', 'duplicate_bet_guard' => '1',
            'round_lock_before_close_seconds' => '2', 'result_feed_timeout_seconds' => '8',
            'test_result_override_enabled' => '0', 'fair_result_feed_lock' => '1',
            'wingo30_result_source' => 'api', 'wingo30_result_api_url' => '',
            'wingo30_result_api_timeout_seconds' => '5', 'wingo30_manual_override_priority' => '0',
            'wingo30_admin_control_enabled' => '1', 'game_api_fallback_to_random' => '0',
        ],
        'Payments' => [
            'payment_manual_upi_enabled' => '1', 'payment_qr_enabled' => '1',
            'payment_bank_enabled' => '1', 'payment_usdt_demo_enabled' => '1',
            'payment_method_sorting_enabled' => '1', 'payment_limits_enabled' => '1',
            'payment_instruction_enabled' => '1', 'payment_audit_enabled' => '1',
            'payment_reference_unique_check' => '1', 'payment_receipt_preview' => '1',
            'payment_admin_notes_enabled' => '1', 'payment_status_notifications' => '1',
        ],
        'Activity & Promotion' => [
            'daily_award_tier1_target' => '500', 'daily_award_tier1_reward' => '3',
            'daily_award_tier2_target' => '5000', 'daily_award_tier2_reward' => '20',
            'daily_award_tier3_target' => '50000', 'daily_award_tier3_reward' => '200',
            'daily_award_tier4_target' => '100000', 'daily_award_tier4_reward' => '500',
            'invite_wheel_target' => '500', 'invite_wheel_free_spins' => '2',
            'follow_strategy_enabled' => '1',
            'team_commission_level1_percent' => '0.85', 'team_commission_level2_percent' => '0.75',
            'team_commission_level3_percent' => '0.50', 'team_commission_level4_percent' => '0.35',
            'team_commission_level5_percent' => '0.20', 'team_commission_level6_percent' => '0.15',
        ],
        'Live Chat' => [
            'live_chat_enabled' => '1', 'chat_png_upload_enabled' => '1', 'chat_max_upload_mb' => '2',
            'chat_order_context_enabled' => '1', 'chat_deposit_context_enabled' => '1',
            'chat_withdraw_context_enabled' => '1', 'chat_bonus_context_enabled' => '1',
            'chat_admin_close_enabled' => '1', 'chat_user_close_enabled' => '1',
            'chat_auto_close_hours' => '48', 'chat_unread_badge_enabled' => '1',
            'chat_show_user_id' => '1', 'chat_show_mobile' => '1', 'chat_show_wallet_balance' => '1',
            'chat_show_session_id' => '1', 'chat_poll_seconds' => '3', 'chat_max_message_length' => '2000',
        ],
        'Security' => [
            'security_headers_enabled' => '1', 'csrf_enabled' => '1', 'admin_session_timeout_minutes' => '60',
            'admin_audit_log_enabled' => '1', 'admin_login_log_enabled' => '1', 'api_auth_required' => '1',
            'upload_mime_validation' => '1', 'upload_random_names' => '1', 'directory_listing_disabled' => '1',
            'sensitive_file_protection' => '1', 'heartbeat_ip_hash_enabled' => '1', 'rate_limit_enabled' => '1',
            'rate_limit_per_minute' => '120', 'error_details_public' => '0',
            'admin_idle_warning_enabled' => '1', 'admin_single_session_mode' => '0',
            'login_bruteforce_guard' => '1', 'suspicious_ip_event_logging' => '1',
            'api_signature_logging' => '0', 'security_event_retention_days' => '90',
        ],
        'Dashboard & UI' => [
            'dashboard_charts_enabled' => '1', 'dashboard_realtime_cards' => '1',
            'dashboard_system_health_enabled' => '1', 'dashboard_audit_widget_enabled' => '1',
            'admin_compact_sidebar' => '0', 'admin_dark_theme' => '1', 'responsive_tables_enabled' => '1',
            'mobile_admin_nav_enabled' => '1', 'toast_notifications_enabled' => '1',
            'dashboard_finance_widget_enabled' => '1', 'dashboard_chat_widget_enabled' => '1',
            'dashboard_feature_counter_enabled' => '1', 'admin_sidebar_search_enabled' => '1',
        ],
        'Operations' => [
            'auto_schema_install_enabled' => '1', 'system_event_log_enabled' => '1',
            'presence_tracking_enabled' => '1', 'stale_session_cleanup_enabled' => '1',
            'health_check_enabled' => '1', 'database_health_check_enabled' => '1',
            'api_health_check_enabled' => '1', 'settlement_health_check_enabled' => '1',
            'background_cleanup_enabled' => '1', 'failed_job_retry_enabled' => '1',
            'queue_dashboard_enabled' => '1', 'maintenance_banner_enabled' => '1',
        ],
        'User Controls' => [
            'user_search_enabled' => '1', 'user_status_control_enabled' => '1', 'user_wallet_admin_control' => '1',
            'user_password_reset_enabled' => '1', 'user_profile_audit_enabled' => '1', 'user_notes_enabled' => '1',
            'user_activity_view_enabled' => '1', 'user_transaction_view_enabled' => '1', 'user_device_view_enabled' => '1',
            'user_export_enabled' => '1', 'user_bulk_action_enabled' => '0', 'user_soft_block_enabled' => '1',
        ],
        'Risk & Abuse' => [
            'same_ip_detection_enabled' => '1', 'multi_account_alert_enabled' => '1', 'rapid_bet_alert_enabled' => '1',
            'rapid_deposit_alert_enabled' => '1', 'rapid_withdraw_alert_enabled' => '1', 'duplicate_device_alert_enabled' => '1',
            'high_value_transaction_alert_enabled' => '1', 'risk_event_logging_enabled' => '1',
            'auto_block_high_risk_enabled' => '0', 'risk_review_queue_enabled' => '1',
            'ip_velocity_window_minutes' => '30', 'high_value_alert_amount' => '50000',
        ],
        'Finance Automation' => [
            'deposit_pending_alert_enabled' => '1', 'withdraw_pending_alert_enabled' => '1',
            'deposit_duplicate_guard_enabled' => '1', 'withdraw_duplicate_guard_enabled' => '1',
            'wallet_reconciliation_enabled' => '1', 'daily_finance_summary_enabled' => '1',
            'finance_csv_export_enabled' => '1', 'manual_review_queue_enabled' => '1',
            'large_withdraw_review_enabled' => '1', 'large_withdraw_review_amount' => '25000',
            'finance_snapshot_enabled' => '1', 'gateway_health_widget_enabled' => '1',
        ],
        'Support Automation' => [
            'support_user_context_enabled' => '1', 'support_auto_assign_enabled' => '0',
            'support_priority_enabled' => '1', 'support_internal_notes_enabled' => '1',
            'support_quick_replies_enabled' => '1', 'support_session_search_enabled' => '1',
            'support_closed_session_filter_enabled' => '1', 'support_attachment_preview_enabled' => '1',
            'support_typing_indicator_enabled' => '0', 'support_presence_indicator_enabled' => '1',
            'support_chat_export_enabled' => '1', 'support_session_reopen_enabled' => '1', 'support_center_enabled' => '1',
            'support_ticket_enabled' => '1', 'support_progress_query_enabled' => '1',
            'support_self_service_enabled' => '1', 'support_external_links_enabled' => '1',
        ],
        'Reports & Data' => [
            'report_7_day_enabled' => '1', 'report_30_day_enabled' => '1', 'report_90_day_enabled' => '1',
            'report_csv_export_enabled' => '1', 'report_user_growth_enabled' => '1', 'report_cash_flow_enabled' => '1',
            'report_wallet_float_enabled' => '1', 'report_pending_queue_enabled' => '1',
            'report_audit_activity_enabled' => '1', 'report_top_balance_enabled' => '1',
            'report_timezone_normalization' => '1', 'report_max_export_rows' => '10000',
        ],
        'Content & PWA' => [
            'home_banner_enabled' => '1', 'local_asset_fallback_enabled' => '1', 'broken_image_fallback_enabled' => '1',
            'pwa_install_prompt_enabled' => '1', 'pwa_service_worker_enabled' => '1', 'firebase_notifications_enabled' => '1',
            'announcement_modal_enabled' => '1', 'maintenance_page_enabled' => '1',
            'customer_service_icon_enabled' => '1', 'customer_service_open_new_tab' => '0',
            'asset_cache_busting_enabled' => '1', 'frontend_debug_mode' => '0',
        ],
        'Notifications' => [
            'notify_deposit_status_enabled' => '1', 'notify_withdraw_status_enabled' => '1',
            'notify_bonus_enabled' => '1', 'notify_support_reply_enabled' => '1',
            'notify_admin_pending_deposit_enabled' => '1', 'notify_admin_pending_withdraw_enabled' => '1',
            'notify_admin_new_chat_enabled' => '1', 'notify_security_event_enabled' => '1',
            'notification_sound_enabled' => '1', 'notification_badge_enabled' => '1',
        ],
        'Observability' => [
            'php_error_log_monitor_enabled' => '1', 'http_500_event_log_enabled' => '1',
            'missing_table_event_log_enabled' => '1', 'missing_asset_event_log_enabled' => '1',
            'api_latency_tracking_enabled' => '1', 'slow_query_event_log_enabled' => '0',
            'frontend_error_reporting_enabled' => '0', 'service_worker_health_enabled' => '1',
            'cron_health_enabled' => '1', 'disk_usage_health_enabled' => '1',
            'health_history_days' => '30', 'slow_api_threshold_ms' => '2500',
        ],
    ];
}

function app_install_schema(?mysqli $db = null): void
{
    global $conn;
    $db = $db ?: $conn;
    static $done = false;
    if ($done || !($db instanceof mysqli)) {
        return;
    }

    // Do not trust the version marker on its own. A cPanel restore or manual
    // cleanup can leave the marker behind while one of the application tables
    // is missing. Check the whole required table set in one information_schema
    // query so normal polls stay cheap and incomplete installs self-heal.
    $requiredSchema = [
        'app_settings' => ['setting_key', 'setting_value'],
        'app_payment_methods' => ['id', 'code'],
        'app_deposit_requests' => ['id', 'user_id'],
        'app_chat_sessions' => ['id', 'user_id'],
        'app_chat_messages' => ['id', 'session_id'],
        'app_audit_logs' => ['id', 'created_at'],
        'app_admin_permissions' => ['admin_id'],
        'app_user_presence' => ['user_id'],
        'app_user_preferences' => ['user_id', 'user_photo'],
        'app_system_events' => ['id', 'event_type'],
        'app_support_tickets' => ['id', 'user_id'],
        'app_game_control' => ['game_code', 'enabled'],
        'app_bet_requests' => ['request_key'],
        'app_turntable_draws' => ['id', 'user_id'],
        'app_provider_transactions' => ['tx_key'],
        'app_site_messages' => ['id', 'enabled'],
        'app_notifications' => ['id', 'user_id'],
        'app_notification_reads' => ['notification_id', 'user_id'],
        'app_spin_prizes' => ['id', 'enabled'],
        'app_channel_bonus_rules' => ['id', 'method_code'],
        'vip' => ['userid', 'expe'],
        'tb_agent' => ['id', 'userid'],
        'shonu_turntable' => ['id', 'user_id', 'created_at', 'updated_at'],
        'shonu_turntable_spins' => ['id', 'user_id', 'user_name', 'is_credited'],
        'app_spin_daily_grants' => ['user_id', 'grant_date'],
        'app_spin_transfers' => ['id', 'user_id'],
        'app_daily_award_claims' => ['id', 'user_id'],
        'upi_withdrawal' => ['id', 'user_id', 'created_at'],
        'app_follow_records' => ['id', 'user_id'],
        'app_saas_commissions' => ['id', 'request_group_key', 'credited_at'],
        'app_invite_spin_grants' => ['owner_user_id', 'invited_user_id'],
    ];

    // Normal API polls perform one schema-shape lookup and one version lookup.
    // Full CREATE/seed work is reserved for a missing table or upgraded package.
    try {
        if (app_schema_columns_exist($requiredSchema, $db)) {
            $versionResult = $db->query("SELECT setting_value FROM app_settings WHERE setting_key='control_center_schema_version' LIMIT 1");
            $versionRow = $versionResult ? $versionResult->fetch_row() : null;
            if ($versionRow && (string)$versionRow[0] === '20260826-v12.1') {
                $done = true;
                return;
            }
        }
    } catch (Throwable $ignored) {
        // Continue into the self-install pass below.
    }

    // Never let an optional control-center table take the public/admin site down.
    // cPanel/PHP 8 can throw mysqli_sql_exception automatically, so every schema
    // operation is isolated and logged instead of becoming an HTTP 500.
    $queries = [
        "CREATE TABLE IF NOT EXISTS app_settings (setting_key VARCHAR(100) NOT NULL, setting_value TEXT NOT NULL, group_name VARCHAR(80) NOT NULL DEFAULT 'General', updated_at DATETIME NOT NULL, PRIMARY KEY(setting_key), KEY idx_app_settings_group(group_name)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS app_payment_methods (id INT UNSIGNED NOT NULL AUTO_INCREMENT, code VARCHAR(40) NOT NULL, name VARCHAR(100) NOT NULL, method_type VARCHAR(40) NOT NULL, enabled TINYINT(1) NOT NULL DEFAULT 1, sort_order INT NOT NULL DEFAULT 0, min_amount DECIMAL(18,2) NOT NULL DEFAULT 100, max_amount DECIMAL(18,2) NOT NULL DEFAULT 50000, instructions TEXT NULL, config_json LONGTEXT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id), UNIQUE KEY uq_app_payment_code(code), KEY idx_app_payment_enabled(enabled,sort_order)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS app_deposit_requests (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, user_id BIGINT NOT NULL, method_code VARCHAR(40) NOT NULL, amount DECIMAL(18,2) NOT NULL, reference_no VARCHAR(120) NOT NULL, receipt_path VARCHAR(255) NULL, status VARCHAR(20) NOT NULL DEFAULT 'pending', admin_note VARCHAR(255) NULL, credited_at DATETIME NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id), UNIQUE KEY uq_app_deposit_reference(reference_no), KEY idx_app_deposit_user(user_id,created_at), KEY idx_app_deposit_status(status,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS app_chat_sessions (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, user_id BIGINT NOT NULL, subject VARCHAR(190) NOT NULL DEFAULT 'Support', status VARCHAR(20) NOT NULL DEFAULT 'open', last_message_at DATETIME NOT NULL, closed_by VARCHAR(80) NULL, created_at DATETIME NOT NULL, closed_at DATETIME NULL, PRIMARY KEY(id), KEY idx_app_chat_user(user_id,status), KEY idx_app_chat_status(status,last_message_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS app_chat_messages (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, session_id BIGINT UNSIGNED NOT NULL, sender_type VARCHAR(20) NOT NULL, sender_id VARCHAR(80) NOT NULL, message_type VARCHAR(20) NOT NULL DEFAULT 'text', message_text TEXT NULL, attachment_path VARCHAR(255) NULL, context_type VARCHAR(30) NULL, context_id VARCHAR(100) NULL, metadata_json LONGTEXT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id), KEY idx_app_chat_messages_session(session_id,id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS app_audit_logs (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, admin_name VARCHAR(100) NOT NULL, action VARCHAR(100) NOT NULL, entity_type VARCHAR(80) NULL, entity_id VARCHAR(100) NULL, metadata_json LONGTEXT NULL, ip_hash CHAR(64) NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id), KEY idx_app_audit_created(created_at), KEY idx_app_audit_admin(admin_name)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS app_admin_permissions (admin_id BIGINT NOT NULL, dashboard TINYINT(1) NOT NULL DEFAULT 0, users TINYINT(1) NOT NULL DEFAULT 0, wingomanager TINYINT(1) NOT NULL DEFAULT 0, trxwingomanager TINYINT(1) NOT NULL DEFAULT 0, k3manager TINYINT(1) NOT NULL DEFAULT 0, `5dmanager` TINYINT(1) NOT NULL DEFAULT 0, motoracingmanager TINYINT(1) NOT NULL DEFAULT 0, finance TINYINT(1) NOT NULL DEFAULT 0, support TINYINT(1) NOT NULL DEFAULT 0, managegame TINYINT(1) NOT NULL DEFAULT 0, updated_at DATETIME NOT NULL, PRIMARY KEY(admin_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS app_user_presence (user_id BIGINT NOT NULL, last_seen DATETIME NOT NULL, ip_hash CHAR(64) NULL, user_agent_hash CHAR(64) NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(user_id), KEY idx_app_presence_last_seen(last_seen)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS app_user_preferences (user_id BIGINT NOT NULL, language VARCHAR(20) NULL, user_photo VARCHAR(20) NULL, guidelines_finished TINYINT(1) NOT NULL DEFAULT 0, updated_at DATETIME NOT NULL, PRIMARY KEY(user_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS app_system_events (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, event_type VARCHAR(80) NOT NULL, severity VARCHAR(20) NOT NULL DEFAULT 'info', message VARCHAR(255) NOT NULL, metadata_json LONGTEXT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id), KEY idx_app_events_created(created_at), KEY idx_app_events_type(event_type)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS app_support_tickets (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, user_id BIGINT NOT NULL, category VARCHAR(80) NOT NULL, subject VARCHAR(190) NOT NULL, message TEXT NULL, reference_no VARCHAR(120) NULL, amount DECIMAL(18,2) NULL, status VARCHAR(20) NOT NULL DEFAULT 'pending', priority VARCHAR(20) NOT NULL DEFAULT 'normal', admin_note TEXT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, closed_at DATETIME NULL, PRIMARY KEY(id), KEY idx_app_support_user(user_id,created_at), KEY idx_app_support_status(status,updated_at), KEY idx_app_support_category(category,status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS app_game_control (game_code VARCHAR(40) NOT NULL, enabled TINYINT(1) NOT NULL DEFAULT 1, result_source VARCHAR(30) NOT NULL DEFAULT 'api', api_url TEXT NULL, api_timeout_seconds INT NOT NULL DEFAULT 5, lock_before_close_seconds INT NOT NULL DEFAULT 2, manual_override_enabled TINYINT(1) NOT NULL DEFAULT 0, updated_at DATETIME NOT NULL, PRIMARY KEY(game_code)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS app_bet_requests (request_key CHAR(64) NOT NULL, user_id BIGINT NOT NULL, game_type_id INT NOT NULL, issue_number VARCHAR(80) NOT NULL, bet_table VARCHAR(80) NOT NULL, bet_row_id BIGINT NULL, status VARCHAR(20) NOT NULL DEFAULT 'processing', created_at DATETIME NOT NULL, completed_at DATETIME NULL, PRIMARY KEY(request_key), KEY idx_app_bet_user(user_id,created_at), KEY idx_app_bet_issue(game_type_id,issue_number,status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS app_turntable_draws (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, user_id BIGINT NOT NULL, prize_id INT NULL, prize_label VARCHAR(100) NOT NULL, prize_amount DECIMAL(18,2) NOT NULL DEFAULT 0, created_at DATETIME NOT NULL, PRIMARY KEY(id), KEY idx_app_turntable_user(user_id,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS app_provider_transactions (tx_key CHAR(64) NOT NULL, provider_tx_id VARCHAR(120) NOT NULL, user_id BIGINT NOT NULL, operation VARCHAR(40) NOT NULL, amount DECIMAL(18,2) NOT NULL DEFAULT 0, status VARCHAR(20) NOT NULL DEFAULT 'processing', response_json LONGTEXT NULL, created_at DATETIME NOT NULL, completed_at DATETIME NULL, PRIMARY KEY(tx_key), KEY idx_provider_user(user_id,created_at), KEY idx_provider_reference(provider_tx_id,operation)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS app_site_messages (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, title VARCHAR(190) NOT NULL, message TEXT NOT NULL, enabled TINYINT(1) NOT NULL DEFAULT 1, sort_order INT NOT NULL DEFAULT 0, starts_at DATETIME NULL, ends_at DATETIME NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id), KEY idx_site_messages_active(enabled,sort_order), KEY idx_site_messages_window(starts_at,ends_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS app_notifications (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, user_id BIGINT NULL, title VARCHAR(190) NOT NULL, message TEXT NOT NULL, type VARCHAR(30) NOT NULL DEFAULT 'general', enabled TINYINT(1) NOT NULL DEFAULT 1, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id), KEY idx_notifications_target(user_id,enabled,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS app_notification_reads (notification_id BIGINT UNSIGNED NOT NULL, user_id BIGINT NOT NULL, state TINYINT(1) NOT NULL DEFAULT 1, read_at DATETIME NOT NULL, PRIMARY KEY(notification_id,user_id), KEY idx_notification_reads_user(user_id,state)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS app_spin_prizes (id INT UNSIGNED NOT NULL AUTO_INCREMENT, label VARCHAR(100) NOT NULL, amount DECIMAL(18,2) NOT NULL DEFAULT 0, weight INT UNSIGNED NOT NULL DEFAULT 1, first_spin TINYINT(1) NOT NULL DEFAULT 0, enabled TINYINT(1) NOT NULL DEFAULT 1, sort_order INT NOT NULL DEFAULT 0, updated_at DATETIME NOT NULL, PRIMARY KEY(id), KEY idx_spin_prizes_pool(enabled,first_spin,sort_order)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS app_channel_bonus_rules (id INT UNSIGNED NOT NULL AUTO_INCREMENT, method_code VARCHAR(40) NOT NULL DEFAULT '*', title VARCHAR(120) NOT NULL, min_amount DECIMAL(18,2) NOT NULL DEFAULT 0, max_amount DECIMAL(18,2) NOT NULL DEFAULT 0, bonus_percent DECIMAL(8,4) NOT NULL DEFAULT 0, bonus_fixed DECIMAL(18,2) NOT NULL DEFAULT 0, max_bonus DECIMAL(18,2) NOT NULL DEFAULT 0, enabled TINYINT(1) NOT NULL DEFAULT 1, sort_order INT NOT NULL DEFAULT 0, updated_at DATETIME NOT NULL, PRIMARY KEY(id), KEY idx_channel_bonus_match(enabled,method_code,min_amount,max_amount,sort_order)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS vip (userid BIGINT NOT NULL, expe BIGINT NOT NULL DEFAULT 0, lvl INT NOT NULL DEFAULT 0, createdate DATETIME NULL, PRIMARY KEY(userid), KEY idx_vip_level(lvl,expe)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS tb_agent (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, userid BIGINT NOT NULL, mobile VARCHAR(32) NOT NULL, createdate DATETIME NOT NULL, status TINYINT NOT NULL DEFAULT 1, type VARCHAR(20) NOT NULL DEFAULT 'month', salary DECIMAL(18,2) NOT NULL DEFAULT 0, PRIMARY KEY(id), UNIQUE KEY uq_agent_user(userid), KEY idx_agent_status(status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS shonu_turntable (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, user_id BIGINT NOT NULL, invited_wheel_amount DECIMAL(18,2) NOT NULL DEFAULT 0, total_spins INT NOT NULL DEFAULT 0, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(id), UNIQUE KEY uq_turntable_user(user_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS shonu_turntable_spins (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, user_id BIGINT NOT NULL, prize_amount DECIMAL(18,2) NOT NULL DEFAULT 0, spin_time DATETIME NOT NULL, user_name VARCHAR(100) NOT NULL DEFAULT 'User', prize_id INT NULL, is_credited TINYINT(1) NOT NULL DEFAULT 0, PRIMARY KEY(id), KEY idx_turntable_spins_user(user_id,spin_time)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS app_spin_daily_grants (user_id BIGINT NOT NULL, grant_date DATE NOT NULL, amount INT NOT NULL DEFAULT 0, created_at DATETIME NOT NULL, PRIMARY KEY(user_id,grant_date)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS app_spin_transfers (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, user_id BIGINT NOT NULL, amount DECIMAL(18,2) NOT NULL, balance_before DECIMAL(18,2) NOT NULL, balance_after DECIMAL(18,2) NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id), KEY idx_spin_transfers_user(user_id,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        ,"CREATE TABLE IF NOT EXISTS app_daily_award_claims (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, user_id BIGINT NOT NULL, claim_date DATE NOT NULL, config_id INT NOT NULL, task_target DECIMAL(18,2) NOT NULL, award_amount DECIMAL(18,2) NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id), UNIQUE KEY uq_daily_award_claim(user_id,claim_date,config_id), KEY idx_daily_award_user(user_id,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        ,"CREATE TABLE IF NOT EXISTS upi_withdrawal (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, user_id BIGINT NOT NULL, upi_id VARCHAR(190) NOT NULL, mobile VARCHAR(32) NOT NULL DEFAULT '', name VARCHAR(120) NOT NULL DEFAULT '', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(id), UNIQUE KEY uq_upi_withdrawal_user(user_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        ,"CREATE TABLE IF NOT EXISTS app_follow_records (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, user_id BIGINT NOT NULL, game_code VARCHAR(40) NOT NULL, strategy_code VARCHAR(40) NOT NULL, strategy_name VARCHAR(100) NOT NULL, amount DECIMAL(18,2) NOT NULL DEFAULT 1, status TINYINT NOT NULL DEFAULT 1, created_at DATETIME NOT NULL, stopped_at DATETIME NULL, PRIMARY KEY(id), KEY idx_follow_user(user_id,status,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        ,"CREATE TABLE IF NOT EXISTS app_saas_commissions (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, request_group_key VARCHAR(96) NOT NULL, source_user_id BIGINT NOT NULL, beneficiary_user_id BIGINT NOT NULL, level_no TINYINT NOT NULL, turnover DECIMAL(18,2) NOT NULL, commission_amount DECIMAL(18,4) NOT NULL, created_at DATETIME NOT NULL, credited_at DATETIME NULL, PRIMARY KEY(id), UNIQUE KEY uq_saas_commission_group_level(request_group_key,level_no), KEY idx_saas_commission_beneficiary(beneficiary_user_id,created_at), KEY idx_saas_commission_pending(credited_at,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        ,"CREATE TABLE IF NOT EXISTS app_invite_spin_grants (owner_user_id BIGINT NOT NULL, invited_user_id BIGINT NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(owner_user_id,invited_user_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];

    foreach ($queries as $sql) {
        try {
            $db->query($sql);
        } catch (Throwable $e) {
            error_log('[app_schema] ' . $e->getMessage());
        }
    }

    // Additive migration for installations that created preferences before V11.3.
    try {
        if (app_table_exists('app_user_preferences') && !app_column_exists('app_user_preferences', 'user_photo')) {
            $db->query('ALTER TABLE app_user_preferences ADD COLUMN user_photo VARCHAR(20) NULL AFTER language');
        }
    } catch (Throwable $e) {
        error_log('[app_schema_preferences] ' . $e->getMessage());
    }

    try {
        if (app_table_exists('shonu_turntable') && !app_column_exists('shonu_turntable', 'created_at')) {
            $db->query('ALTER TABLE shonu_turntable ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        }
        if (app_table_exists('shonu_turntable') && !app_column_exists('shonu_turntable', 'updated_at')) {
            $db->query('ALTER TABLE shonu_turntable ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        }
        if (app_table_exists('upi_withdrawal') && !app_column_exists('upi_withdrawal', 'created_at')) {
            $db->query('ALTER TABLE upi_withdrawal ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        }
        if (app_table_exists('shonu_turntable_spins') && !app_column_exists('shonu_turntable_spins', 'is_credited')) {
            $db->query('ALTER TABLE shonu_turntable_spins ADD COLUMN is_credited TINYINT(1) NOT NULL DEFAULT 0');
        }
        if (app_table_exists('shonu_turntable_spins') && !app_column_exists('shonu_turntable_spins', 'user_name')) {
            $db->query("ALTER TABLE shonu_turntable_spins ADD COLUMN user_name VARCHAR(100) NOT NULL DEFAULT 'User'");
        }
        if (app_table_exists('app_saas_commissions') && !app_column_exists('app_saas_commissions', 'credited_at')) {
            $db->query('ALTER TABLE app_saas_commissions ADD COLUMN credited_at DATETIME NULL');
            try { $db->query('ALTER TABLE app_saas_commissions ADD KEY idx_saas_commission_pending(credited_at,created_at)'); } catch (Throwable $ignored) {}
        }
    } catch (Throwable $e) {
        error_log('[app_schema_activity_wallet] ' . $e->getMessage());
    }

    // Mark the pass complete before seeding so a failed optional seed cannot recurse.
    $done = true;

    try {
        if (app_table_exists('app_settings')) {
            $defaults = app_default_settings();
            $schemaVersion = '20260826-v12.1';
            $installedVersion = '';
            $versionResult = $db->query("SELECT setting_value FROM app_settings WHERE setting_key='control_center_schema_version' LIMIT 1");
            if ($versionResult && ($versionRow = $versionResult->fetch_row())) {
                $installedVersion = (string)$versionRow[0];
            }
            // Seed the full control catalog only when this package introduces a
            // newer settings schema. Normal requests perform a single version read.
            if ($installedVersion !== $schemaVersion) {
                $insert = $db->prepare('INSERT IGNORE INTO app_settings (setting_key,setting_value,group_name,updated_at) VALUES (?,?,?,NOW())');
                if ($insert) {
                    foreach ($defaults as $group => $settings) {
                        foreach ($settings as $key => $value) {
                            $v = (string)$value;
                            $insert->bind_param('sss', $key, $v, $group);
                            $insert->execute();
                        }
                    }
                    $insert->close();
                }
                $versionGroup = 'Internal';
                $versionStmt = $db->prepare("INSERT INTO app_settings(setting_key,setting_value,group_name,updated_at) VALUES ('control_center_schema_version',?,?,NOW()) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),group_name=VALUES(group_name),updated_at=NOW()");
                if ($versionStmt) {
                    $versionStmt->bind_param('ss', $schemaVersion, $versionGroup);
                    $versionStmt->execute();
                    $versionStmt->close();
                }
            }
        }
    } catch (Throwable $e) {
        error_log('[app_schema_settings] ' . $e->getMessage());
    }

    // Keep the optional manual-payment control records for admin compatibility only.
    // The public recharge APIs continue to use the project's original gateways/channels.
    try {
        if (app_table_exists('app_payment_methods')) {
            $methods = [
                ['manual_upi', 'Manual UPI', 'upi', 1, 10, 100, 50000, 'Submit a payment request with UTR/reference and PNG receipt. Admin approval is required.'],
                ['qr_deposit', 'QR Deposit', 'qr', 1, 20, 100, 50000, 'Scan the configured QR, then submit reference details for admin review.'],
                ['bank_transfer', 'Bank Transfer', 'bank', 1, 30, 500, 100000, 'Use the configured bank instructions and submit transaction reference for verification.'],
                ['usdt_demo', 'USDT', 'usdt', 0, 40, 10, 10000, 'Optional admin-managed USDT request flow.'],
            ];
            $pm = $db->prepare('INSERT IGNORE INTO app_payment_methods (code,name,method_type,enabled,sort_order,min_amount,max_amount,instructions,config_json,updated_at) VALUES (?,?,?,?,?,?,?,?,?,NOW())');
            if ($pm) {
                $empty = '{}';
                foreach ($methods as $m) {
                    [$code,$name,$type,$enabled,$sort,$min,$max,$instructions] = $m;
                    $pm->bind_param('sssiiddss', $code, $name, $type, $enabled, $sort, $min, $max, $instructions, $empty);
                    $pm->execute();
                }
                $pm->close();
            }
        }
    } catch (Throwable $e) {
        error_log('[app_schema_payments] ' . $e->getMessage());
    }

    try {
        if (app_table_exists('app_game_control')) {
            $gameRows = [
                ['WinGo_30S',1,'api','',5,2,0],
                ['WinGo_1M',1,'api','',5,3,0],
                ['WinGo_3M',1,'api','',5,5,0],
                ['WinGo_5M',1,'api','',5,5,0],
                ['TrxWinGo_1M',1,'api','',5,3,0],
                ['TrxWinGo_3M',1,'api','',5,5,0],
                ['TrxWinGo_5M',1,'api','',5,5,0],
                ['TrxWinGo_10M',1,'api','',5,5,0],
                ['K3_1M',1,'api','',5,3,0],
                ['K3_3M',1,'api','',5,5,0],
                ['K3_5M',1,'api','',5,5,0],
                ['K3_10M',1,'api','',5,5,0],
                ['D5_1M',1,'api','',5,3,0],
                ['D5_3M',1,'api','',5,5,0],
                ['D5_5M',1,'api','',5,5,0],
                ['D5_10M',1,'api','',5,5,0],
                ['MotoRace_1M',1,'api','',5,3,0],
            ];
            $stmt = $db->prepare('INSERT IGNORE INTO app_game_control(game_code,enabled,result_source,api_url,api_timeout_seconds,lock_before_close_seconds,manual_override_enabled,updated_at) VALUES (?,?,?,?,?,?,?,NOW())');
            if ($stmt) {
                foreach ($gameRows as $g) {
                    [$gameCode,$enabled,$source,$apiUrl,$timeout,$lock,$manual] = $g;
                    $stmt->bind_param('sissiii',$gameCode,$enabled,$source,$apiUrl,$timeout,$lock,$manual);
                    $stmt->execute();
                }
                $stmt->close();
            }
        }
    } catch (Throwable $e) {
        error_log('[app_schema_game_control] ' . $e->getMessage());
    }

    try {
        if (app_table_exists('app_site_messages')) {
            $count = $db->query('SELECT COUNT(*) FROM app_site_messages');
            $countRow = $count ? $count->fetch_row() : [0];
            if ((int)($countRow[0] ?? 0) === 0) {
                $stmt = $db->prepare('INSERT INTO app_site_messages(title,message,enabled,sort_order,created_at,updated_at) VALUES (?,?,1,?,NOW(),NOW())');
                if ($stmt) {
                    $seedMessages = [
                        ['Recharge help', 'Use the payment details currently shown in the app and submit the correct reference number for verification.', 10],
                        ['Withdrawal help', 'Keep your bank details accurate. Pending withdrawals are reviewed from the administrator queue.', 20],
                    ];
                    foreach ($seedMessages as [$title,$message,$sort]) {
                        $stmt->bind_param('ssi', $title, $message, $sort);
                        $stmt->execute();
                    }
                    $stmt->close();
                }
            }
        }
        if (app_table_exists('app_spin_prizes')) {
            $count = $db->query('SELECT COUNT(*) FROM app_spin_prizes');
            $countRow = $count ? $count->fetch_row() : [0];
            if ((int)($countRow[0] ?? 0) === 0) {
                $stmt = $db->prepare('INSERT INTO app_spin_prizes(label,amount,weight,first_spin,enabled,sort_order,updated_at) VALUES (?,?,?,?,1,?,NOW())');
                if ($stmt) {
                    $seedPrizes = [
                        ['Small reward',1.00,35,0,10], ['Reward',2.00,25,0,20],
                        ['Bonus reward',3.00,18,0,30], ['Lucky reward',5.00,7,0,40],
                        ['Try again',0.00,15,0,50], ['Welcome reward',5.00,100,1,10],
                    ];
                    foreach ($seedPrizes as [$label,$amount,$weight,$first,$sort]) {
                        $stmt->bind_param('sdiii', $label, $amount, $weight, $first, $sort);
                        $stmt->execute();
                    }
                    $stmt->close();
                }
            }
        }
    } catch (Throwable $e) {
        error_log('[app_schema_v9_seed] ' . $e->getMessage());
    }
}

function app_setting(string $key, $default = null)
{
    global $conn;
    app_install_schema($conn);
    try {
        if (!app_table_exists('app_settings')) {
            return $default;
        }
        $stmt = $conn->prepare('SELECT setting_value FROM app_settings WHERE setting_key=? LIMIT 1');
        if (!$stmt) {
            return $default;
        }
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $settingValue = null;
        $stmt->bind_result($settingValue);
        $found = $stmt->fetch();
        $stmt->close();
        return $found ? $settingValue : $default;
    } catch (Throwable $e) {
        error_log('[app_setting] ' . $e->getMessage());
        return $default;
    }
}

/**
 * Central password compatibility layer. Legacy installations may still have
 * 32-character MD5 columns; wider columns receive password_hash values.
 */
function app_password_column_length(mysqli $db, string $table = 'shonu_subjects', string $column = 'password'): int
{
    static $cache = [];
    $allowed = [
        'shonu_subjects.password' => true,
        'nirvahaka_shonu.guptapada' => true,
    ];
    $key = $table . '.' . $column;
    if (!isset($allowed[$key])) throw new InvalidArgumentException('Unsupported password column.');
    if (isset($cache[$key])) return $cache[$key];
    $length = 32;
    try {
        $stmt = $db->prepare('SELECT CHARACTER_MAXIMUM_LENGTH FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('ss', $table, $column);
            $stmt->execute();
            $storedLength = null;
            $stmt->bind_result($storedLength);
            if ($stmt->fetch() && (int)$storedLength > 0) $length = (int)$storedLength;
            $stmt->close();
        }
    } catch (Throwable $ignored) {
    }
    return $cache[$key] = $length;
}

function app_password_is_modern(string $stored): bool
{
    if ($stored === '') return false;
    $info = password_get_info($stored);
    return !empty($info['algo']);
}

function app_password_matches(string $plain, string $stored): bool
{
    if ($plain === '' || $stored === '') return false;
    if (app_password_is_modern($stored)) return password_verify($plain, $stored);
    return preg_match('/^[a-f0-9]{32}$/i', $stored) === 1 && hash_equals(strtolower($stored), md5($plain));
}

function app_password_value(mysqli $db, string $plain, string $table = 'shonu_subjects', string $column = 'password'): string
{
    return app_password_column_length($db, $table, $column) >= 60
        ? password_hash($plain, PASSWORD_DEFAULT)
        : md5($plain);
}

function app_password_needs_upgrade(mysqli $db, string $stored, string $table = 'shonu_subjects', string $column = 'password'): bool
{
    if (app_password_column_length($db, $table, $column) < 60) return false;
    return !app_password_is_modern($stored) || password_needs_rehash($stored, PASSWORD_DEFAULT);
}

/**
 * Resolve one enabled channel bonus rule for a deposit. A max value of zero
 * means unlimited. Exact channel rules take priority over the wildcard rule.
 */
function app_channel_bonus(string $methodCode, float $amount): array
{
    global $conn;
    $empty = ['rule_id' => 0, 'title' => '', 'amount' => 0.0];
    if ($amount <= 0 || !app_table_exists('app_channel_bonus_rules')) {
        return $empty;
    }

    $methodCode = strtolower(trim($methodCode));
    try {
        $sql = "SELECT id,title,bonus_percent,bonus_fixed,max_bonus
                FROM app_channel_bonus_rules
                WHERE enabled=1 AND (LOWER(method_code)=? OR method_code='*')
                  AND min_amount<=? AND (max_amount=0 OR max_amount>=?)
                ORDER BY (LOWER(method_code)=?) DESC, min_amount DESC, sort_order ASC, id ASC
                LIMIT 1";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return $empty;
        }
        $stmt->bind_param('sdds', $methodCode, $amount, $amount, $methodCode);
        $stmt->execute();
        $result = $stmt->get_result();
        $rule = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        if (!$rule) {
            return $empty;
        }

        $bonus = round(($amount * (float)$rule['bonus_percent'] / 100) + (float)$rule['bonus_fixed'], 4);
        $cap = (float)$rule['max_bonus'];
        if ($cap > 0) {
            $bonus = min($bonus, $cap);
        }
        return [
            'rule_id' => (int)$rule['id'],
            'title' => (string)$rule['title'],
            'amount' => max(0.0, $bonus),
        ];
    } catch (Throwable $e) {
        error_log('[app_channel_bonus] ' . $e->getMessage());
        return $empty;
    }
}

/** Grant the configured daily spins once without overwriting admin-granted spins. */
function app_spin_grant_daily(int $userId): int
{
    global $conn;
    $daily = max(0, min(100, (int)app_setting('daily_spin_limit', '1')));
    if ($userId < 1 || !app_table_exists('app_spin_daily_grants') || !app_table_exists('shonu_turntable')) {
        return 0;
    }
    $todayDeposit = 0.0;
    if (app_table_exists('thevani')) {
        $stmt = $conn->prepare('SELECT COALESCE(SUM(motta),0) FROM thevani WHERE balakedara=? AND sthiti=1 AND dinankavannuracisi>=CURDATE()');
        if ($stmt) {
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $stmt->bind_result($todayDeposit);
            $stmt->fetch();
            $stmt->close();
        }
    }
    $depositSpins = 0;
    foreach ([[100000,11],[50000,8],[10000,6],[5000,4],[2000,3],[1000,2],[500,1]] as $tier) {
        if ((float)$todayDeposit >= $tier[0]) { $depositSpins = $tier[1]; break; }
    }
    $target = max($daily, $depositSpins);
    if ($target < 1) return 0;

    $date = date('Y-m-d');
    $stmt = $conn->prepare('SELECT amount FROM app_spin_daily_grants WHERE user_id=? AND grant_date=? LIMIT 1 FOR UPDATE');
    if (!$stmt) return 0;
    $stmt->bind_param('is', $userId, $date);
    $stmt->execute();
    $alreadyGranted = 0;
    $stmt->bind_result($alreadyGranted);
    $found = $stmt->fetch();
    $stmt->close();
    $alreadyGranted = $found ? max(0, (int)$alreadyGranted) : 0;
    $grant = max(0, $target - $alreadyGranted);
    if ($grant < 1) return 0;

    if ($found) {
        $stmt = $conn->prepare('UPDATE app_spin_daily_grants SET amount=? WHERE user_id=? AND grant_date=?');
        if (!$stmt) return 0;
        $stmt->bind_param('iis', $target, $userId, $date);
    } else {
        $stmt = $conn->prepare('INSERT INTO app_spin_daily_grants(user_id,grant_date,amount,created_at) VALUES (?,?,?,NOW())');
        if (!$stmt) return 0;
        $stmt->bind_param('isi', $userId, $date, $target);
    }
    if (!$stmt->execute()) { $stmt->close(); return 0; }
    $stmt->close();
    $stmt = $conn->prepare('INSERT INTO shonu_turntable(user_id,invited_wheel_amount,total_spins) VALUES (?,0,?) ON DUPLICATE KEY UPDATE total_spins=total_spins+VALUES(total_spins)');
    if (!$stmt) return 0;
    $stmt->bind_param('ii', $userId, $grant);
    $stmt->execute();
    $stmt->close();
    return $grant;
}

function app_setting_bool(string $key, bool $default = false): bool
{
    $value = app_setting($key, $default ? '1' : '0');
    return in_array(strtolower((string)$value), ['1','true','yes','on'], true);
}

function app_set_setting(string $key, string $value, string $group = 'General'): void
{
    global $conn;
    app_install_schema($conn);
    try {
        if (!app_table_exists('app_settings')) {
            return;
        }
        $stmt = $conn->prepare('INSERT INTO app_settings(setting_key,setting_value,group_name,updated_at) VALUES (?,?,?,NOW()) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), group_name=VALUES(group_name), updated_at=NOW()');
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('sss', $key, $value, $group);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $e) {
        error_log('[app_set_setting] ' . $e->getMessage());
    }
}

function app_table_exists(string $table): bool
{
    global $conn;
    try {
        $stmt = $conn->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=? LIMIT 1');
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('s', $table);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
        return $exists;
    } catch (Throwable $e) {
        error_log('[app_table_exists] ' . $e->getMessage());
        return false;
    }
}

/**
 * Check several required tables/columns with a single metadata query.
 *
 * The identifiers are validated before being used in the SQL IN clause. The
 * helper is read-only and returns false on metadata errors so the caller can
 * enter its idempotent repair pass.
 */
function app_schema_columns_exist(array $requirements, ?mysqli $db = null): bool
{
    global $conn;
    $db = $db ?: $conn;
    if (!($db instanceof mysqli) || !$requirements) {
        return false;
    }

    $tables = [];
    foreach ($requirements as $table => $columns) {
        if (!is_string($table) || !preg_match('/^[A-Za-z0-9_]+$/', $table) || !is_array($columns) || !$columns) {
            return false;
        }
        foreach ($columns as $column) {
            if (!is_string($column) || !preg_match('/^[A-Za-z0-9_]+$/', $column)) {
                return false;
            }
        }
        $tables[] = "'" . $db->real_escape_string($table) . "'";
    }

    try {
        $sql = 'SELECT table_name,column_name FROM information_schema.columns '
            . 'WHERE table_schema=DATABASE() AND table_name IN (' . implode(',', $tables) . ')';
        $result = $db->query($sql);
        if (!$result) {
            return false;
        }
        $seen = [];
        while ($row = $result->fetch_assoc()) {
            $seen[(string)$row['table_name']][(string)$row['column_name']] = true;
        }
        $result->free();

        foreach ($requirements as $table => $columns) {
            foreach ($columns as $column) {
                if (empty($seen[$table][$column])) {
                    return false;
                }
            }
        }
        return true;
    } catch (Throwable $e) {
        error_log('[app_schema_columns_exist] ' . $e->getMessage());
        return false;
    }
}

function app_column_exists(string $table, string $column): bool
{
    global $conn;
    try {
        $stmt = $conn->prepare('SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=? LIMIT 1');
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('ss', $table, $column);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
        return $exists;
    } catch (Throwable $e) {
        error_log('[app_column_exists] ' . $e->getMessage());
        return false;
    }
}

function app_audit(string $admin, string $action, ?string $entityType = null, ?string $entityId = null, array $metadata = []): void
{
    global $conn;
    if (!app_setting_bool('admin_audit_log_enabled', true)) {
        return;
    }
    try {
        if (!app_table_exists('app_audit_logs')) {
            return;
        }
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ipHash = $ip !== '' ? hash('sha256', $ip . '|' . (getenv('APP_HASH_SALT') ?: 'local-admin')) : null;
        $json = $metadata ? json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;
        $stmt = $conn->prepare('INSERT INTO app_audit_logs(admin_name,action,entity_type,entity_id,metadata_json,ip_hash,created_at) VALUES (?,?,?,?,?,?,NOW())');
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('ssssss', $admin, $action, $entityType, $entityId, $json, $ipHash);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $e) {
        error_log('[app_audit] ' . $e->getMessage());
    }
}

function app_security_headers(): void
{
    if (!headers_sent()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: same-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header('Cache-Control: no-store');
    }
}


function app_feature_enabled(string $key, bool $default = true): bool
{
    return app_setting_bool($key, $default);
}

/** Normalize the mobile value used by the bundled payment-link contract. */
function app_payment_display_mobile(string $mobile): string
{
    $digits = preg_replace('/\D+/', '', $mobile);
    $digits = is_string($digits) ? $digits : '';
    if (strlen($digits) === 10) {
        return '91' . $digits;
    }
    if (strlen($digits) === 12 && strpos($digits, '91') === 0) {
        return $digits;
    }
    return $digits;
}

/**
 * One canonical payment-link signature for GetUserInfo, checkout, UTR submit
 * and status polling. The value is an opaque hand-off token for the frontend.
 */
function app_payment_user_signature(int $userId, string $mobile, string $nickname, string $createdAt): string
{
    $payload = json_encode([
        'userId' => $userId,
        'userPhoto' => '1',
        'userName' => app_payment_display_mobile($mobile),
        'nickName' => $nickname,
        'createdate' => $createdAt,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if (!is_string($payload)) {
        throw new RuntimeException('Payment signature payload could not be encoded.');
    }
    return strtoupper(hash('sha256', $payload));
}

/** Return one control-center payment method with normalized scalar values. */
function app_payment_method_record(string $code, bool $enabledOnly = true): ?array
{
    global $conn;
    if (!app_table_exists('app_payment_methods')) {
        return null;
    }
    $sql = 'SELECT id,code,name,method_type,enabled,sort_order,min_amount,max_amount,instructions,config_json FROM app_payment_methods WHERE code=?';
    if ($enabledOnly) {
        $sql .= ' AND enabled=1';
    }
    $sql .= ' LIMIT 1';
    try {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        if (!$row) {
            return null;
        }
        $row['id'] = (int)$row['id'];
        $row['enabled'] = (int)$row['enabled'];
        $row['sort_order'] = (int)$row['sort_order'];
        $row['min_amount'] = (float)$row['min_amount'];
        $row['max_amount'] = (float)$row['max_amount'];
        $row['config'] = json_decode((string)($row['config_json'] ?: '{}'), true) ?: [];
        unset($row['config_json']);
        return $row;
    } catch (Throwable $e) {
        error_log('[app_payment_method_record] ' . $e->getMessage());
        return null;
    }
}

function app_json_error(string $message, int $code = 7, int $msgCode = 6, int $httpStatus = 200): void
{
    http_response_code($httpStatus);
    $timezone = (string) app_setting('timezone', 'Asia/Kolkata');
    @date_default_timezone_set($timezone ?: 'Asia/Kolkata');
    echo json_encode([
        'code' => $code,
        'msg' => $message,
        'msgCode' => $msgCode,
        'serviceNowTime' => date('Y-m-d H:i:s'),
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

function app_game_feature_key($typeId): string
{
    $id = (int) $typeId;
    if ($id === 4 || $id === 30) return 'game_WinGo_30S_enabled';
    if ($id === 1) return 'game_WinGo_1M_enabled';
    if ($id === 2) return 'game_WinGo_3M_enabled';
    if ($id === 3) return 'game_WinGo_5M_enabled';
    if ($id >= 5 && $id <= 8) return 'game_D5_' . [5=>'1M',6=>'3M',7=>'5M',8=>'10M'][$id] . '_enabled';
    if ($id >= 9 && $id <= 12) return 'game_K3_' . [9=>'1M',10=>'3M',11=>'5M',12=>'10M'][$id] . '_enabled';
    if ($id >= 13 && $id <= 16) return 'game_TrxWinGo_' . [13=>'1M',14=>'3M',15=>'5M',16=>'10M'][$id] . '_enabled';
    return 'betting_enabled';
}

function app_game_interval_seconds($typeId): int
{
    $id = (int) $typeId;
    if ($id === 4 || $id === 30) return 30;
    if ($id === 1) return 60;
    if ($id === 2) return 180;
    if ($id === 3) return 300;
    if (in_array($id, [5,9,13], true)) return 60;
    if (in_array($id, [6,10,14], true)) return 180;
    if (in_array($id, [7,11,15], true)) return 300;
    if (in_array($id, [8,12,16], true)) return 600;
    return 60;
}

function app_game_control(string $gameCode): array
{
    global $conn;
    $fallback = [
        'game_code' => $gameCode,
        'enabled' => 1,
        'result_source' => 'api',
        'api_url' => '',
        'api_timeout_seconds' => 5,
        'lock_before_close_seconds' => (int)app_setting('round_lock_before_close_seconds', '2'),
        'manual_override_enabled' => 0,
    ];
    try {
        if (!app_table_exists('app_game_control')) return $fallback;
        $stmt = $conn->prepare('SELECT game_code,enabled,result_source,api_url,api_timeout_seconds,lock_before_close_seconds,manual_override_enabled FROM app_game_control WHERE game_code=? LIMIT 1');
        if (!$stmt) return $fallback;
        $stmt->bind_param('s', $gameCode);
        $stmt->execute();
        $stmt->bind_result($gc,$enabled,$source,$url,$timeout,$lock,$manual);
        $found = $stmt->fetch();
        $stmt->close();
        return $found ? [
            'game_code'=>(string)$gc,
            'enabled'=>(int)$enabled,
            // SaaS lottery results are permanently locked to the real feed.
            // Retain the enabled/timeout/lock values, but never expose a
            // database-stored local/manual source at runtime.
            'result_source'=>'api',
            'api_url'=>'',
            'api_timeout_seconds'=>(int)$timeout,
            'lock_before_close_seconds'=>(int)$lock,
            'manual_override_enabled'=>0,
        ] : $fallback;
    } catch (Throwable $e) {
        error_log('[app_game_control] '.$e->getMessage());
        return $fallback;
    }
}

function app_update_game_control(string $gameCode, array $data): bool
{
    global $conn;
    app_install_schema($conn);
    try {
        if (!app_table_exists('app_game_control')) return false;
        $enabled = !empty($data['enabled']) ? 1 : 0;
        $source = 'api';
        $url = '';
        $timeout = max(1, min(20, (int)($data['api_timeout_seconds'] ?? 5)));
        $lock = max(0, min(20, (int)($data['lock_before_close_seconds'] ?? 2)));
        $manual = 0;
        $stmt = $conn->prepare('INSERT INTO app_game_control(game_code,enabled,result_source,api_url,api_timeout_seconds,lock_before_close_seconds,manual_override_enabled,updated_at) VALUES (?,?,?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE enabled=VALUES(enabled),result_source=VALUES(result_source),api_url=VALUES(api_url),api_timeout_seconds=VALUES(api_timeout_seconds),lock_before_close_seconds=VALUES(lock_before_close_seconds),manual_override_enabled=VALUES(manual_override_enabled),updated_at=NOW()');
        if (!$stmt) return false;
        $stmt->bind_param('sissiii',$gameCode,$enabled,$source,$url,$timeout,$lock,$manual);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    } catch (Throwable $e) {
        error_log('[app_update_game_control] '.$e->getMessage());
        return false;
    }
}


/**
 * Resolve a WinGo result. Admin panel manual override takes priority over
 * the real feed. If admin has set a number via the prediction form, that
 * number is used only when the legacy open issue matches the exact issue saved
 * by the admin. Subsequent periods resume normal real-feed mode automatically.
 */
function app_resolve_game_result(string $gameCode, string $manualTable): array
{
    global $conn;
    $control = app_game_control($gameCode);
    $source = 'real_feed';
    $timeout = max(1, min(20, (int)($control['api_timeout_seconds'] ?? 5)));

    // --- STEP 1: Check the override for this exact open issue FIRST ---
    try {
        $legacyIssue = sl_admin_override_legacy_issue($conn, $gameCode);
        if ($legacyIssue !== '') {
            $override = sl_admin_override_get($conn, $gameCode, $legacyIssue);
            if ($override) {
                return [
                    'number'         => (int)$override['premium'],
                    'source'         => 'manual_admin',
                    'control'        => $control,
                    'manual_allowed' => false,
                    'issue_number'   => $legacyIssue,
                ];
            }
        }
    } catch (Throwable $e) {
        error_log('[app_resolve_game_result override check] ' . $e->getMessage());
    }

    // --- STEP 2: No override — fetch from authoritative real feed ---
    $number = null;
    $base = 'https://draw.ar-lottery01.com';
    $url = $base . '/WinGo/' . rawurlencode($gameCode) . '/GetHistoryIssuePage.json?pageNo=1&pageSize=10&ts=' . (string)round(microtime(true) * 1000);

    try {
        $response = false;
        $httpCode = 0;
        if (function_exists('curl_init')) {
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CONNECTTIMEOUT => $timeout,
                    CURLOPT_TIMEOUT => $timeout,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_MAXREDIRS => 2,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_HTTPHEADER => [
                        'Accept: application/json, text/plain, */*',
                        'Referer: https://www.lottery7uuu.com/',
                    ],
                    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/150.0.0.0 Safari/537.36',
                ]);
                $response = curl_exec($ch);
                $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
        } else {
            $context = stream_context_create(['http'=>[
                'timeout'=>$timeout,
                'follow_location'=>1,
                'max_redirects'=>2,
                'ignore_errors'=>true,
                'header'=>"Accept: application/json, text/plain, */*\r\nReferer: https://www.lottery7uuu.com/\r\nUser-Agent: Mozilla/5.0 Chrome/150.0.0.0 Safari/537.36\r\n",
            ]]);
            $response = @file_get_contents($url, false, $context);
            $httpCode = is_string($response) && $response !== '' ? 200 : 0;
        }
        if ($response !== false && $httpCode >= 200 && $httpCode < 300) {
            $decoded = json_decode((string)$response, true);
            $candidate = $decoded['data']['list'][0]['premium'] ?? ($decoded['data']['list'][0]['number'] ?? null);
            if (is_numeric($candidate)) {
                $candidate = (int)$candidate;
                if ($candidate >= 0 && $candidate <= 9) {
                    $number = $candidate;
                }
            }
        }
    } catch (Throwable $e) {
        error_log('[app_resolve_game_result real feed] ' . $e->getMessage());
    }

    if ($number === null) {
        throw new RuntimeException('Authoritative WinGo result is unavailable; settlement stopped to prevent a mismatched result.');
    }

    return ['number'=>$number, 'source'=>$source, 'control'=>$control, 'manual_allowed'=>false];
}
