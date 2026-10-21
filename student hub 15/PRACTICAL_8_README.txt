STUDENT HUB — PRACTICAL 8
MySQL Schema Design, ER Model, PDO Connectivity and Prepared Statements
=======================================================================

WHAT IS INCLUDED
- The complete existing Student Hub website and earlier practical files are retained.
- studenthub_pr8.sql: creates the normalized `studenthub` database, tables, primary keys, foreign keys, indexes, unique constraints, and demonstration seed records.
- db.php: reusable PDO/MySQL connection with utf8mb4, exception mode, native prepared statements, configurable credentials, and a safe browser-facing connection error.
- pr8.php: event registration demonstration using a PDO prepared INSERT, CSRF token, output escaping, duplicate-registration handling, and an INNER JOIN report.
- PR8 Database navigation links added to the existing HTML pages and PR7 page.
- ER_MODEL.md: relationship explanation and ER diagram.
- PRACTICAL_8_TEST_CASES.txt: manual test cases and expected results.

DATABASE DESIGN
1. students(student_id PK, enrollment_no UNIQUE, full_name, email UNIQUE, department, study_year)
2. events(event_id PK, title, description, venue, event_date, capacity)
3. registrations(registration_id PK, student_id FK, event_id FK, registered_at, status)
The registrations table resolves the many-to-many relationship between students and events. The composite UNIQUE constraint prevents one student from registering for the same event more than once.

HOW TO RUN ON WINDOWS WITH XAMPP
1. Extract the ZIP.
2. Copy the complete `student hub 7` folder into `C:\xampp\htdocs\` (result: `C:\xampp\htdocs\student hub 7`).
3. Open XAMPP Control Panel and start Apache and MySQL.
4. Open phpMyAdmin at `http://localhost/phpmyadmin`.
5. Choose Import, select `studenthub_pr8.sql` from the extracted folder, and click Import/Go. Alternatively, open the SQL file in phpMyAdmin's SQL tab and execute it.
6. Open `http://localhost/student%20hub%207/pr8.php` in your browser.
7. Select a student and event, then click Save Registration. The registration should appear in the table below.
8. To view the rest of the site, use `http://localhost/student%20hub%207/home.html`.

IMPORTANT
- Do not open pr8.php by double-clicking it or using a `file://` URL. PHP needs Apache (or another PHP server).
- The default database credentials in db.php are for a common local XAMPP setup: host 127.0.0.1, database studenthub, username root, blank password. If your MySQL root account has a password, update db.php or set the documented STUDENTHUB_DB_* environment variables.
- If the existing website's Fetch API pages are being tested, use the same Apache/localhost URL rather than opening the HTML file directly.
- The seed names and contact details are fictional demonstration data. Replace them with approved test data if your instructor requires it.
- The SQL script drops and recreates only the three PR8 tables when re-imported; do not re-import if you need to preserve your own registrations.
