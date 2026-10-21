-- Student Hub Practical 9: secure user registration.
-- Import this file in phpMyAdmin AFTER (or alongside) the PR8 schema.
-- It does not drop or modify the existing PR8 tables.
CREATE DATABASE IF NOT EXISTS studenthub
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE studenthub;

CREATE TABLE IF NOT EXISTS users (
    user_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(30) NOT NULL,
    email VARCHAR(254) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_users_username UNIQUE (username),
    CONSTRAINT uq_users_email UNIQUE (email),
    INDEX idx_users_created_at (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS registration_audit_logs (
    audit_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(40) NOT NULL,
    details VARCHAR(255) NOT NULL DEFAULT '',
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id)
      REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_audit_action_time (action, created_at),
    INDEX idx_audit_user (user_id)
) ENGINE=InnoDB;

-- No sample password is inserted. Create test accounts through pr9.php.
