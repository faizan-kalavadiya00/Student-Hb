STUDENT HUB — PRACTICAL 9
Secure User Registration with Database Insert, Duplicate Email Check, and Password Hashing

FILES ADDED
- pr9.php: registration form + server-side validation + prepared statements + duplicate checks + password_hash() + CSRF token + audit log.
- studenthub_pr9.sql: creates the users and registration_audit_logs tables without deleting PR8 data.
- PRACTICAL_9_README.txt: setup and verification instructions.
- PRACTICAL_9_TEST_CASES.txt: valid, invalid, duplicate, and security tests.

SETUP (Windows / XAMPP)
1. Copy the entire extracted "student hub 8" folder into C:\xampp\htdocs\ (or use your existing project folder under htdocs).
2. Open XAMPP Control Panel and start Apache and MySQL.
3. Open http://localhost/phpmyadmin/
4. Select Import and import studenthub_pr8.sql if you have not already imported the PR8 database.
5. Import studenthub_pr9.sql. It creates only the PR9 tables and does not drop PR8 tables.
6. Open http://localhost/student%20hub%208/pr9.php if the folder name contains spaces, or use the actual folder name in the URL. If you renamed the folder to student-hub, use http://localhost/student-hub/pr9.php.
7. Submit a valid registration, then check phpMyAdmin > studenthub > users. The password_hash column must contain a hash, never the original password.
8. Check registration_audit_logs for registration_success / registration_rejected entries.

DATABASE CONFIGURATION
db.php uses database name studenthub, host 127.0.0.1, user root, and an empty password by default, which matches a common local XAMPP installation. If your MySQL credentials differ, update the environment variables documented in db.php or change the local settings there.

IMPLEMENTATION / SECURITY CHECKLIST
- Frontend HTML validation and backend PHP validation are both present.
- Email is normalized to lowercase; email and username are checked for duplicates.
- Unique database indexes are the final safeguard against concurrent duplicate submissions.
- SQL values use PDO prepared statements.
- Passwords are stored with password_hash($password, PASSWORD_DEFAULT), not in plaintext.
- CSRF token is generated in the session and verified on POST.
- Audit records include the event, optional user ID, short details, IP address, and timestamp; passwords are never written to audit logs.
- User-visible errors do not disclose database exception details.
- Password length is capped at 72 bytes to avoid bcrypt truncation surprises when PASSWORD_DEFAULT uses bcrypt.

TROUBLESHOOTING
- "Database connection unavailable": ensure MySQL is running and db.php credentials match your setup.
- "Table doesn't exist": import studenthub_pr9.sql into the studenthub database.
- If the browser downloads/displays PHP source rather than executing it, access the site through http://localhost/... with Apache/PHP, not by double-clicking the PHP file.
- PR9 includes a Unicode-aware name-length fallback if the mbstring extension is not enabled.
