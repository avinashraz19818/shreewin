<?php
if (!defined('VA_ADMIN')) { http_response_code(404); exit; }
// Executed ONLY by authenticated one-time setup, never on page load.
function va_install(): void {
    $sql=[
    "CREATE TABLE IF NOT EXISTS veegame_admin_accounts (id BIGINT UNSIGNED NOT NULL PRIMARY KEY, username VARCHAR(64) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, role VARCHAR(16) NOT NULL, active TINYINT NOT NULL DEFAULT 1, session_version INT NOT NULL DEFAULT 1, created_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS veegame_admin_audit (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, admin_id BIGINT UNSIGNED NOT NULL, action VARCHAR(64) NOT NULL, target VARCHAR(128) NOT NULL, detail TEXT NOT NULL, created_at DATETIME NOT NULL, INDEX(created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS veegame_admin_rate (bucket CHAR(64) NOT NULL PRIMARY KEY, window_start BIGINT NOT NULL, hits INT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];
    foreach($sql as $q) va_db()->query($q);
    $n=va_one("SELECT COUNT(*) AS n FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('veegame_admin_accounts','veegame_admin_audit','veegame_admin_rate') AND ENGINE='InnoDB'");
    if((int)$n['n']!==3) throw new RuntimeException('Admin schema must be InnoDB');
}
