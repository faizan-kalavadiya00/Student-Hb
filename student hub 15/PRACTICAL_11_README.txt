STUDENT HUB — PRACTICAL 11
Student Management CRUD with Search, Filter, CSV Export, and Soft Delete

FILES ADDED
- pr11.php: PHP/MySQL student management module (Create, Read, Update, Delete/restore).
- studenthub_pr11.sql: creates a dedicated managed_students table and seeds 20 records.
- PRACTICAL_11_README.txt: setup and submission instructions.
- PRACTICAL_11_TEST_CASES.txt: test cases and expected results.

FEATURES IMPLEMENTED
1. Add student records with enrollment number, name, email, course, and study year.
2. View student records in a table with pagination (10 rows per page).
3. Edit active student records.
4. Soft-delete records (status changes to deleted); restore them later. No permanent delete.
5. Search by name, email, course, or enrollment number.
6. Filter active/deleted/all records.
7. Export the current search/filter results as CSV.
8. Uses PDO prepared statements for all data queries and writes.
9. Validates required fields/email/year; unique enrollment/email conflicts show a clear message.
10. Escapes displayed user content, includes CSRF tokens on write actions, and confirms delete.
11. Keeps PR8's existing students/events/registrations tables unchanged.

SETUP (WINDOWS + XAMPP)
1. Extract the complete ZIP into C:\xampp\htdocs\.
2. Start Apache and MySQL from XAMPP Control Panel.
3. Open http://localhost/phpmyadmin/.
4. Select the SQL tab and import studenthub_pr11.sql. It creates/uses database `studenthub`.
   This PR11 SQL is safe alongside PR8–PR10 and does not drop or modify their tables.
5. Confirm db.php uses the correct MySQL host/database/user/password. Default XAMPP values are:
   host 127.0.0.1, database studenthub, user root, blank password.
6. Open http://localhost/student%20hub%208/pr11.php if your extracted folder is literally named
   `student hub 8`. For easier URLs, rename the folder to `studenthub`, then use
   http://localhost/studenthub/pr11.php.
7. Use Add Student, Search/Filter, Edit, Delete, Deleted records, Restore, and Export CSV.
8. To verify the seed data, run:
   SELECT COUNT(*) FROM managed_students;
   It should show 20 on a new installation.

IMPORTANT
- Run PHP pages through Apache (http://localhost/...), not by opening the PHP file directly.
- The PR11 page currently works as a standalone practical module. It does not require login so
  the evaluator can test CRUD immediately; deploy behind sh_require_role($pdo, 'admin') if required.
- Seed records are inserted only when managed_students is empty. Re-importing the SQL does not
  add duplicate seed rows. Do not delete this table if you need to preserve entered practical data.
- CSV export respects the currently selected search and status filter.
- Soft-deleted rows remain in the database and can be restored.
