-- Student Hub Practical 11: student CRUD module.
-- Import AFTER studenthub_pr8.sql (and PR9/PR10 if using the existing site).
-- This module uses its own table so existing PR8 students and registrations remain unchanged.
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
    CONSTRAINT chk_managed_students_year CHECK (study_year BETWEEN 1 AND 6),
    INDEX idx_managed_students_name (full_name),
    INDEX idx_managed_students_course (course),
    INDEX idx_managed_students_status (status)
) ENGINE=InnoDB;

-- Seed only when table is empty; safe to re-import without duplicate seed rows.
INSERT INTO managed_students (enrollment_no, full_name, email, course, study_year)
SELECT seed.enrollment_no, seed.full_name, seed.email, seed.course, seed.study_year
FROM (
 SELECT '25DCS045' enrollment_no,'Faizan Kalavadiya' full_name,'faizan.pr11@example.com' email,'Computer Science' course,2 study_year
 UNION ALL SELECT '25DCS046','Aarav Patel','aarav.pr11@example.com','Computer Science',2
 UNION ALL SELECT '25DCS047','Diya Shah','diya.pr11@example.com','Information Technology',2
 UNION ALL SELECT '25DCS048','Rehan Khan','rehan.pr11@example.com','Computer Science',2
 UNION ALL SELECT '25DCS049','Mira Desai','mira.pr11@example.com','Information Technology',2
 UNION ALL SELECT '25DCS050','Vivaan Mehta','vivaan.pr11@example.com','Computer Science',2
 UNION ALL SELECT '25DCS051','Anaya Joshi','anaya.pr11@example.com','Information Technology',2
 UNION ALL SELECT '25DCS052','Kabir Trivedi','kabir.pr11@example.com','Computer Science',2
 UNION ALL SELECT '25DCS053','Sara Sheikh','sara.pr11@example.com','Electronics',2
 UNION ALL SELECT '25DCS054','Arjun Rao','arjun.pr11@example.com','Computer Science',2
 UNION ALL SELECT '25DCS055','Ira Shah','ira.pr11@example.com','Information Technology',2
 UNION ALL SELECT '25DCS056','Dev Patel','dev.pr11@example.com','Computer Science',2
 UNION ALL SELECT '25DCS057','Aditi Parmar','aditi.pr11@example.com','Electronics',2
 UNION ALL SELECT '25DCS058','Zayn Merchant','zayn.pr11@example.com','Computer Science',2
 UNION ALL SELECT '25DCS059','Kiara Desai','kiara.pr11@example.com','Information Technology',2
 UNION ALL SELECT '25DCS060','Neil Shah','neil.pr11@example.com','Computer Science',2
 UNION ALL SELECT '25DCS061','Myra Patel','myra.pr11@example.com','Electronics',2
 UNION ALL SELECT '25DCS062','Rohan Dave','rohan.pr11@example.com','Computer Science',2
 UNION ALL SELECT '25DCS063','Navya Shah','navya.pr11@example.com','Information Technology',2
 UNION ALL SELECT '25DCS064','Yusuf Khan','yusuf.pr11@example.com','Computer Science',2
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM managed_students LIMIT 1);
