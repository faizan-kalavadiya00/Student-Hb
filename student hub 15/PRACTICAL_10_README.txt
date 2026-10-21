STUDENT HUB — PRACTICAL 10
Secure Login/Logout with Sessions, Role-Based Access, Timeout, and Remember-Me Token

WHAT WAS ADDED
- login.php: verifies password hashes with password_verify(), supports username/email login,
  regenerates the session ID after login, uses a CSRF token, and offers Remember Me.
- auth.php: shared session setup, 15-minute inactivity timeout, authentication checks,
  role checks, and secure remember-token validation/rotation.
- logout.php: clears remember tokens and cookies, destroys the session, then redirects.
- dashboard.php: sends authenticated users to the correct role dashboard.
- student_dashboard.php and admin_dashboard.php: role-specific pages; wrong-role access gets HTTP 403.
- dashboard_content.php: existing Student Hub timetable/services, now protected by login.
- dashboard.html: forwards to dashboard.php so the old static dashboard link cannot show the dashboard content.
- studenthub_pr10.sql: adds user roles, last-login timestamp, and hashed remember-token storage.
- PRACTICAL_10_TEST_CASES.txt: verification steps and expected results.

SETUP (WINDOWS / XAMPP)
1. Extract the entire "student hub 8" folder into C:\\xampp\\htdocs\\.
2. Start Apache and MySQL in XAMPP.
3. Open http://localhost/phpmyadmin/.
4. If starting from scratch, import studenthub_pr8.sql, then studenthub_pr9.sql.
   NOTE: studenthub_pr8.sql resets its PR8 tables; do not re-import it into a database
   containing data you need to preserve.
5. Import studenthub_pr10.sql AFTER PR9. This migration adds role and last_login columns
   to the existing users table and creates remember_tokens. Import it only once.
6. Open http://localhost/<project-folder>/pr9.php and register a student account.
7. Open http://localhost/<project-folder>/login.php and sign in using the same username/email
   and password. You should land on student_dashboard.php.
8. To create an admin, register a second account through pr9.php, then run this in phpMyAdmin
   > studenthub > SQL (replace the username):
       UPDATE users SET role = 'admin' WHERE username = 'your_admin_username';
   Sign out and log in as that account. It should land on admin_dashboard.php.
9. Use Logout, then try opening dashboard.php directly. It should return to login.php.
10. To test Remember Me, select it at login, close/reopen the browser, and revisit dashboard.php.
    The token cookie is HttpOnly/SameSite=Lax and only a SHA-256 hash of the validator is stored.
11. To test timeout, log in, wait 15 minutes without activity, then request a protected page.
    It should redirect to login.php?timeout=1.
12. Screenshots for submission: successful login, failed login, student dashboard, admin dashboard,
    denied wrong-role access, logout, timeout redirect, Remember Me login, and unauthenticated access.

SECURITY NOTES
- Passwords are stored as password_hash() values by PR9; login checks with password_verify().
- Session cookies are HttpOnly and SameSite=Lax; Secure is enabled when HTTPS is used.
- Session IDs are regenerated after password login and remember-token authentication.
- Login uses a generic failure message so the page does not disclose whether a username exists.
- Remember-me tokens use a random selector and validator. Only the validator hash is stored;
  successful use rotates the validator. Tokens expire after 30 days.
- Logout deletes this user's remember tokens and destroys the session.
- Session inactivity timeout is 900 seconds (15 minutes).
- Roles are assigned server-side in the database. Never trust a role submitted by the browser.
- Use HTTPS in production. This project is configured for local XAMPP practical demonstration.

TROUBLESHOOTING
- "Unknown column role/last_login": import studenthub_pr10.sql after PR9.
- "remember_tokens doesn't exist": import studenthub_pr10.sql.
- "Database connection unavailable": start MySQL and confirm db.php settings.
- PHP files must be opened through http://localhost/... with Apache, not by double-clicking them.
