-- Student Hub Practical 14: admin dashboard, role-based menu, and audit trail.
-- Import after PR9/PR10 so the users table and roles exist.
CREATE DATABASE IF NOT EXISTS studenthub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE studenthub;

CREATE TABLE IF NOT EXISTS admin_audit_logs (
    audit_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    username VARCHAR(80) NOT NULL DEFAULT 'system',
    action VARCHAR(80) NOT NULL,
    details VARCHAR(500) NOT NULL DEFAULT '',
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin_audit_time (created_at),
    INDEX idx_admin_audit_user (user_id),
    INDEX idx_admin_audit_action (action),
    CONSTRAINT fk_admin_audit_user FOREIGN KEY (user_id)
      REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
