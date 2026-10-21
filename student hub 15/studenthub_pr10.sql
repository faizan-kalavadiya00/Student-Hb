-- Student Hub Practical 10 migration.
-- IMPORTANT: import this once AFTER studenthub_pr9.sql. It preserves existing users.
USE studenthub;

ALTER TABLE users
    ADD COLUMN role ENUM('admin','student') NOT NULL DEFAULT 'student' AFTER password_hash,
    ADD COLUMN last_login TIMESTAMP NULL DEFAULT NULL AFTER role;

CREATE TABLE IF NOT EXISTS remember_tokens (
    token_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    selector CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL UNIQUE,
    validator_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_remember_user FOREIGN KEY (user_id)
      REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX idx_remember_user (user_id),
    INDEX idx_remember_expiry (expires_at)
) ENGINE=InnoDB;

-- Optional cleanup job: expired tokens can be removed periodically.
DELETE FROM remember_tokens WHERE expires_at < CURRENT_TIMESTAMP;

-- Create an admin account by registering normally through pr9.php, then run:
-- UPDATE users SET role = 'admin' WHERE username = 'YOUR_REGISTERED_USERNAME';
-- Never place a plaintext password in this SQL file.
