-- Student Hub Practical 13 dependencies. Safe to import after PR11 and PR12.
CREATE DATABASE IF NOT EXISTS studenthub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE studenthub;

CREATE TABLE IF NOT EXISTS managed_students (
    student_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    enrollment_no VARCHAR(20) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    course VARCHAR(100) NOT NULL,
    study_year TINYINT UNSIGNED NOT NULL,
    status ENUM('active','deleted') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_managed_students_name (full_name),
    INDEX idx_managed_students_course (course),
    INDEX idx_managed_students_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pr12_events (
    event_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(80) NOT NULL,
    venue VARCHAR(180) NOT NULL,
    event_date DATETIME NOT NULL,
    status ENUM('open','closed') NOT NULL DEFAULT 'open',
    poster_filename VARCHAR(80) NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pr12_events_date (event_date),
    INDEX idx_pr12_events_category (category),
    INDEX idx_pr12_events_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO managed_students (enrollment_no,full_name,email,course,study_year)
SELECT '25DCS900','Demo Student','demo.pr13@example.com','Computer Science',2
WHERE NOT EXISTS (SELECT 1 FROM managed_students LIMIT 1);

INSERT INTO pr12_events (title,description,category,venue,event_date,status)
SELECT 'Practical 13 Demo Event','Sample event for testing JSON CRUD.','Technology','DEPSTAR Seminar Hall','2026-11-25 10:00:00','open'
WHERE NOT EXISTS (SELECT 1 FROM pr12_events LIMIT 1);
