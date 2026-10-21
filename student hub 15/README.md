# Student Hub

Student Hub is a PHP, MySQL, HTML, CSS, and JavaScript student portal intended to run locally with XAMPP/Apache.

## Included modules
- Public portal pages, FAQs, student information, events, profile, fees, scholarship, hall ticket, and medical leave pages.
- PR7–PR10 PHP forms, database integration, registration, and secure login.
- PR11 student management CRUD.
- PR12 event management CRUD and validated event-poster uploads.
- PR13 Fetch/AJAX JSON API and asynchronous CRUD.
- PR14 role-based administrator dashboard and audit log.
- PR15 integration, testing, documentation, GitHub submission, and local deployment guides.

## Requirements
- Windows with XAMPP (Apache, PHP, and MySQL/MariaDB) or an equivalent PHP-compatible environment.
- PHP PDO MySQL extension; PHP `mbstring` is used by the PR13 API.
- A modern browser.

## Local setup (XAMPP)
1. Copy the extracted `student hub 8` folder to `C:\xampp\htdocs\` (or rename it to `studenthub`).
2. Open XAMPP Control Panel and start **Apache** and **MySQL**.
3. Open phpMyAdmin at `http://localhost/phpmyadmin`.
4. For a fresh install only, import SQL files in this order: `studenthub_pr8.sql`, `studenthub_pr9.sql`, `studenthub_pr10.sql`, `studenthub_pr11.sql`, `studenthub_pr12.sql`, `studenthub_pr13.sql`, `studenthub_pr14.sql`.
   - **Warning:** PR8 SQL may drop/recreate its own tables. Never import it into a database with data you need to keep.
   - If an existing Student Hub database is already working, back it up first and import only the missing schema files. PR14 alone can be added by importing `studenthub_pr14.sql`.
5. Confirm `db.php` uses the right database connection. Defaults are database `studenthub`, user `root`, and an empty password, which match a typical local XAMPP setup. For other environments, use the `STUDENTHUB_DB_HOST`, `STUDENTHUB_DB_NAME`, `STUDENTHUB_DB_USER`, and `STUDENTHUB_DB_PASS` environment variables.
6. Register an account using `pr9.php` if needed. To grant administrator access, run the following in phpMyAdmin after replacing the username:
   ```sql
   UPDATE users SET role = 'admin' WHERE username = 'YOUR_REGISTERED_USERNAME';
   ```
   Do not use a plaintext password or share credentials in the repository.
7. Visit `http://localhost/student%20hub%208/home.html` (or use your renamed folder). Sign in through `login.php`. The dashboard redirects by role.
8. Test administrator-only pages while signed in as an admin, then sign out and verify access is denied/redirected. Also test with a normal student account.

## Important entry points
- `home.html` — portal homepage
- `login.php` / `logout.php` — authentication
- `dashboard.php` — role-based dashboard redirect
- `student_dashboard.php` — student dashboard
- `admin_dashboard.php` — administrator dashboard
- `pr11.php` — student management
- `pr12.php` — event management
- `pr13.php` — asynchronous CRUD
- `pr14_audit.php` — administrator audit log

## Security notes
- Use prepared statements and server-side validation for database operations.
- Keep PHP errors in server logs rather than exposing stack traces to users.
- Use a unique, strong database password outside local development.
- Do not commit real credentials, private student data, session cookies, or production database dumps to GitHub.
- HTTPS is required for a public deployment. Configure secure cookies and least-privilege database credentials on the host.
- This project is a student practical and should be reviewed and security-tested before handling real student information.

## Documentation and verification
- `PRACTICAL_15_README.txt` — practical requirements and deployment steps.
- `PRACTICAL_15_TEST_CASES.txt` — end-to-end test cases.
- `PRACTICAL_15_TEST_REPORT_TEMPLATE.txt` — blank report for recording actual test results.
- `SECURITY_ACCESSIBILITY_CHECKLIST.txt` — review checklist.
- `VIRTUAL_HOST_EXAMPLE.txt` — optional local Apache virtual-host example.
- `PRACTICAL_14_README.txt` and earlier practical readmes describe module-specific behavior.

## GitHub submission
See the GitHub section in `PRACTICAL_15_README.txt`. Commit source code and documentation; avoid committing secrets, personal data, or runtime logs. Do not publish a public repository until you have checked the included SQL files for any data you do not intend to share.
