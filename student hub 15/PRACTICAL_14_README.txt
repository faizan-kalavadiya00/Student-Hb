STUDENT HUB — PRACTICAL 14
Admin Dashboard with Role-Based Access Control, Dynamic Menu, and Audit Log
============================================================================

IMPLEMENTATION
- admin_dashboard.php: administrator-only dashboard with live database statistics and latest account registrations.
- audit.php: shared, failure-safe audit logging helper.
- pr14_audit.php: administrator-only audit log viewer (latest 200 entries).
- studenthub_pr14.sql: creates the audit log table without dropping existing data.
- Existing student and event management actions in PR11, PR12, and PR13 now write audit entries.
- PR11 student-management routes are restricted to administrators.
- student_dashboard.php remains a student-only dashboard; admin-only links are not shown to students.
- Existing PR8–PR13 features and files are retained.

SETUP (XAMPP / WINDOWS)
1. Extract the entire ZIP into C:\xampp\htdocs\ (keep the extracted folder name).
2. Start Apache and MySQL in XAMPP.
3. Ensure the existing Student Hub database is set up. For a fresh database, import the project SQL files in this order:
   studenthub_pr8.sql, studenthub_pr9.sql, studenthub_pr10.sql, studenthub_pr11.sql, studenthub_pr12.sql, studenthub_pr13.sql, studenthub_pr14.sql.
   Important: studenthub_pr8.sql drops/recreates its PR8 tables. Do NOT re-import it into a database with data you need to preserve.
4. For an already working Student Hub, import only studenthub_pr14.sql in phpMyAdmin. It adds admin_audit_logs and does not reset other tables.
5. Register a user using pr9.php. In phpMyAdmin, promote that account to administrator:
   UPDATE users SET role='admin' WHERE username='YOUR_REGISTERED_USERNAME';
   Replace the username with the exact account username. Do not store a plaintext password in SQL.
6. Sign in through login.php. The role-based redirect opens admin_dashboard.php for administrators and student_dashboard.php for students.
7. Open the admin dashboard using your local project folder path, for example:
   http://localhost/student%20hub%208/admin_dashboard.php
   Replace "student hub 8" if you renamed the project folder.
8. Open Audit Log from the admin menu to view recent actions.

FUNCTIONALITY
- Statistics (accounts, active managed student records, events, and event registrations) are queried from MySQL at page load.
- Latest account registrations show the newest user accounts.
- Role-specific dashboards and server-side role guards restrict administrator pages.
- Dashboard visits, audit-log views, and successful student/event create, update, delete, restore, and PR13 API CRUD actions are logged with user, action, details, time, and IP address.
- Audit log view shows the newest 200 records. The dashboard shows the latest six audit entries.
- If a table from an earlier practical is missing, the dashboard avoids a fatal error and displays zero for that statistic. Import the corresponding earlier practical SQL to populate it.

TEST CASES
1. Sign in as admin: admin_dashboard.php loads and displays database-backed counts.
2. Create a new account, then reload the dashboard: the account count/latest registration list updates.
3. Sign in as a student and visit admin_dashboard.php, pr11.php, pr12.php, pr13.php, or pr14_audit.php directly: access is denied or the request is redirected to login.
4. As admin, open PR11 and create/update/delete/restore a student. Confirm corresponding audit entries.
5. As admin, open PR12 and create/update/delete an event. Confirm corresponding audit entries.
6. In PR13, create/update/delete student and event records; confirm API actions appear in the audit log.
7. Open the audit log page: latest entries appear first with timestamp, user, action, details, and IP.
8. Verify student menu/dashboard does not show administrator-only links.
9. Verify existing PR8–PR13 pages still exist and their SQL files remain included.
10. Confirm the SQL import does not drop or reset existing project data.

POST-LAB SUBMISSION
Submit the full Student Hub project ZIP, studenthub_pr14.sql, and screenshots of:
- Admin dashboard with dynamic counts and latest account registrations.
- Student account access restriction when attempting an admin-only route.
- Audit log entries after at least one student-management action and one event-management action.
- Role-specific menu/dashboard for a student account.

SECURITY NOTES
- Dashboard and audit log pages use server-side administrator role checks.
- Audit details are HTML-escaped before display.
- SQL statements use prepared statements for user-supplied data.
- Import studenthub_pr14.sql before testing audit logging.
