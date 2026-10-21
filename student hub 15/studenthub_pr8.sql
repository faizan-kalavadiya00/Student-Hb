-- Student Hub Practical 8: normalized schema, ER relationships, and seed data.
-- Import this file in phpMyAdmin (or run: mysql -u root -p < studenthub_pr8.sql)
CREATE DATABASE IF NOT EXISTS studenthub
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE studenthub;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS registrations;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS students;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE students (
    student_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    enrollment_no VARCHAR(20) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    department VARCHAR(80) NOT NULL,
    study_year TINYINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_students_year CHECK (study_year BETWEEN 1 AND 6)
) ENGINE=InnoDB;

CREATE TABLE events (
    event_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT NULL,
    venue VARCHAR(120) NOT NULL,
    event_date DATE NOT NULL,
    capacity INT UNSIGNED NOT NULL DEFAULT 100,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_events_capacity CHECK (capacity > 0)
) ENGINE=InnoDB;

CREATE TABLE registrations (
    registration_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    event_id INT UNSIGNED NOT NULL,
    registered_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Registered', 'Cancelled', 'Attended') NOT NULL DEFAULT 'Registered',
    CONSTRAINT fk_registrations_student FOREIGN KEY (student_id)
      REFERENCES students(student_id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_registrations_event FOREIGN KEY (event_id)
      REFERENCES events(event_id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT uq_student_event UNIQUE (student_id, event_id),
    INDEX idx_registrations_event (event_id),
    INDEX idx_registrations_student (student_id)
) ENGINE=InnoDB;

INSERT INTO students (enrollment_no, full_name, email, department, study_year) VALUES
('25DCS045', 'Faizan Kalavadiya', 'faizan.student@example.com', 'Computer Science', 2),
('25DCS046', 'Aarav Patel', 'aarav.patel@example.com', 'Computer Science', 2),
('25DCS047', 'Diya Shah', 'diya.shah@example.com', 'Information Technology', 2),
('25DCS048', 'Rehan Khan', 'rehan.khan@example.com', 'Computer Science', 2),
('25DCS049', 'Mira Desai', 'mira.desai@example.com', 'Information Technology', 2);

INSERT INTO events (title, description, venue, event_date, capacity) VALUES
('Web Development Workshop', 'Hands-on introduction to modern web development.', 'Computer Lab 1', '2026-11-05', 80),
('Coding Competition', 'Solve programming challenges individually or in teams.', 'Seminar Hall', '2026-11-12', 120),
('Tech Talk: AI in Practice', 'A student-focused talk on practical AI applications.', 'Auditorium', '2026-11-20', 150),
('Project Showcase', 'Present student projects and receive feedback.', 'Innovation Centre', '2026-11-28', 100);

-- One sample registration demonstrates the relationship and joined report.
INSERT INTO registrations (student_id, event_id, status) VALUES (1, 1, 'Registered');
