<?php

if (!isset($conn) || !($conn instanceof mysqli)) {
    return;
}

$autoSchema = [
    "CREATE TABLE IF NOT EXISTS `mr_telegram` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `support_link` VARCHAR(255) NOT NULL DEFAULT 'https://t.me/zayro_o',
        `agentline_link` VARCHAR(255) NOT NULL DEFAULT 'https://t.me/zayro_o',
        `promotion_bot` VARCHAR(255) NOT NULL DEFAULT 'https://t.me/zayro_o',
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `app_game_settings` (
        `game_code` VARCHAR(40) NOT NULL,
        `enabled` TINYINT NOT NULL DEFAULT 1,
        `manual_result` VARCHAR(100) NULL,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`game_code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `bonus_settings` (
        `id` TINYINT UNSIGNED NOT NULL DEFAULT 1,
        `is_enabled` TINYINT(1) NOT NULL DEFAULT 0,
        `bonus_percent` DECIMAL(8,4) NOT NULL DEFAULT 0,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `bonus_log` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` BIGINT NOT NULL,
        `bonus_amount` DECIMAL(18,4) NOT NULL DEFAULT 0,
        `refnum` VARCHAR(190) NOT NULL DEFAULT '',
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_bonus_log_user_ref` (`user_id`,`refnum`),
        KEY `idx_bonus_log_created` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `rebetrec` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` BIGINT NOT NULL,
        `rebet` DECIMAL(18,4) NOT NULL DEFAULT 0,
        `lvl` VARCHAR(40) NOT NULL DEFAULT 'BETTING',
        `motta` DECIMAL(18,4) NOT NULL DEFAULT 0,
        `rate` DECIMAL(8,4) NOT NULL DEFAULT 0.8500,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_rebetrec_user_created` (`user_id`,`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `upi_withdrawal` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` BIGINT NOT NULL,
        `upi_id` VARCHAR(190) NOT NULL,
        `mobile` VARCHAR(32) NOT NULL DEFAULT '',
        `name` VARCHAR(120) NOT NULL DEFAULT '',
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_upi_withdrawal_user` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

foreach ($autoSchema as $statement) {
    try {
        $conn->query($statement);
    } catch (Throwable $e) {
        $table = 'unknown';
        if (preg_match('/CREATE TABLE IF NOT EXISTS\s+`?([A-Za-z0-9_]+)/i', $statement, $match)) {
            $table = (string)$match[1];
        }
        if (function_exists('app_log_event')) {
            app_log_event('error', 'Automatic database table creation failed', [
                'component' => 'database_auto',
                'table' => $table,
                'exception' => get_class($e),
                'error' => $e->getMessage(),
            ]);
        } else {
            error_log('[database_auto] ' . $table . ': ' . $e->getMessage());
        }
    }
}

try {
    $conn->query("INSERT INTO mr_telegram (id,support_link,agentline_link,promotion_bot) VALUES (1,'https://t.me/zayro_o','https://t.me/zayro_o','https://t.me/zayro_o') ON DUPLICATE KEY UPDATE support_link=VALUES(support_link),agentline_link=VALUES(agentline_link),promotion_bot=VALUES(promotion_bot)");
} catch (Throwable $e) {
    if (function_exists('app_log_event')) {
        app_log_event('error', 'Automatic support-link seed failed', [
            'component' => 'database_auto',
            'table' => 'mr_telegram',
            'exception' => get_class($e),
            'error' => $e->getMessage(),
        ]);
    }
}

try {
    $conn->query("INSERT IGNORE INTO bonus_settings (id,is_enabled,bonus_percent) VALUES (1,0,0)");
} catch (Throwable $e) {
    if (function_exists('app_log_event')) {
        app_log_event('error', 'Automatic bonus-setting seed failed', [
            'component' => 'database_auto',
            'table' => 'bonus_settings',
            'exception' => get_class($e),
            'error' => $e->getMessage(),
        ]);
    }
}
