-- Unified WinGo, TrxWinGo, K3, 5D and Moto Racing tables.
-- bootstrap.php creates these automatically; this file is provided for hosts
-- where the database user cannot run CREATE TABLE during a web request.

CREATE TABLE IF NOT EXISTS `saas_lottery_bets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NOT NULL,
  `game_code` VARCHAR(32) NOT NULL,
  `issue_number` VARCHAR(40) NOT NULL,
  `bet_content` VARCHAR(190) NOT NULL,
  `amount` DECIMAL(18,4) NOT NULL,
  `bet_multiple` INT NOT NULL,
  `bet_units` INT NOT NULL DEFAULT 1,
  `stake` DECIMAL(18,4) NOT NULL,
  `request_group_key` CHAR(64) NOT NULL,
  `request_key` CHAR(64) NOT NULL,
  `status` VARCHAR(16) NOT NULL DEFAULT 'pending',
  `result_premium` VARCHAR(64) NULL,
  `payout` DECIMAL(18,4) NOT NULL DEFAULT 0,
  `tax_fee` DECIMAL(18,4) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  `settled_at` DATETIME NULL,
  `vip_exp_applied` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_saas_lottery_request` (`request_key`),
  KEY `idx_saas_lottery_group` (`request_group_key`),
  KEY `idx_saas_lottery_user_created` (`user_id`,`created_at`),
  KEY `idx_saas_lottery_issue_status` (`game_code`,`issue_number`,`status`),
  KEY `idx_saas_vip_exp` (`vip_exp_applied`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `saas_lottery_requests` (
  `request_group_key` CHAR(64) NOT NULL,
  `user_id` BIGINT NOT NULL,
  `game_code` VARCHAR(32) NOT NULL,
  `issue_number` VARCHAR(40) NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`request_group_key`),
  KEY `idx_saas_request_user_created` (`user_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `saas_lottery_results` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `game_code` VARCHAR(32) NOT NULL,
  `issue_number` VARCHAR(40) NOT NULL,
  `premium` VARCHAR(64) NOT NULL,
  `number` VARCHAR(32) NOT NULL DEFAULT '',
  `color` VARCHAR(32) NOT NULL DEFAULT '',
  `result_sum` INT NOT NULL DEFAULT 0,
  `provider_seen_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_saas_lottery_result` (`game_code`,`issue_number`),
  KEY `idx_saas_lottery_result_created` (`game_code`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `saas_wallet_ledger` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NOT NULL,
  `entry_key` VARCHAR(96) NOT NULL,
  `entry_type` VARCHAR(32) NOT NULL,
  `amount` DECIMAL(18,4) NOT NULL,
  `balance_before` DECIMAL(18,4) NOT NULL,
  `balance_after` DECIMAL(18,4) NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_saas_wallet_entry` (`entry_key`),
  KEY `idx_saas_wallet_user_created` (`user_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `saas_lottery_settings` (
  `setting_key` VARCHAR(80) NOT NULL,
  `setting_value` TEXT NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `saas_lottery_overrides` (
  `game_code` VARCHAR(32) NOT NULL,
  `issue_number` VARCHAR(40) NOT NULL,
  `premium` VARCHAR(64) NOT NULL,
  `created_by` VARCHAR(100) NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`game_code`,`issue_number`),
  KEY `idx_saas_override_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `saas_lottery_settings` (`setting_key`,`setting_value`,`updated_at`) VALUES
('game_WinGo_30S_enabled','1',NOW()),
('game_WinGo_1M_enabled','1',NOW()),
('game_WinGo_3M_enabled','1',NOW()),
('game_WinGo_5M_enabled','1',NOW()),
('game_TrxWinGo_1M_enabled','1',NOW()),
('game_TrxWinGo_3M_enabled','1',NOW()),
('game_TrxWinGo_5M_enabled','1',NOW()),
('game_TrxWinGo_10M_enabled','1',NOW()),
('game_K3_1M_enabled','1',NOW()),
('game_K3_3M_enabled','1',NOW()),
('game_K3_5M_enabled','1',NOW()),
('game_K3_10M_enabled','1',NOW()),
('game_D5_1M_enabled','1',NOW()),
('game_D5_3M_enabled','1',NOW()),
('game_D5_5M_enabled','1',NOW()),
('game_D5_10M_enabled','1',NOW()),
('game_MotoRace_1M_enabled','1',NOW())
ON DUPLICATE KEY UPDATE `setting_key`=VALUES(`setting_key`);
