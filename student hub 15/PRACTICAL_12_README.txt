STUDENT HUB — PRACTICAL 12
Event Management CRUD with Poster Upload and File Validation
============================================================

FEATURES IMPLEMENTED
- Admin-only event management page (pr12.php), protected by the shared role-based login.
- Add, view, update and delete event records in MySQL.
- Prepared SQL statements for create, read, update and delete operations.
- Server-side validation for required fields, lengths, date/time and open/closed status.
- Poster uploads accept JPG/JPEG, PNG and WEBP only, maximum size 2 MB.
- Validates upload error, actual MIME type with PHP Fileinfo, and image data with getimagesize().
- Random server-generated poster filenames; user-supplied filenames are never trusted.
- Replacing a poster removes the old poster; deleting an event removes its poster.
- CSRF token for all POST operations, output escaping, and admin role restriction.
- Search, category filter, open/closed status filter, responsive table, and success/failure messages.
- Upload folder disables directory listing and PHP/script execution using .htaccess.

SETUP (XAMPP / WINDOWS)
1. Extract the complete ZIP into C:\xampp\htdocs\ (keep the folder name "student hub 8", or rename it if desired).
2. Start Apache and MySQL in XAMPP.
3. Open http://localhost/phpmyadmin/
4. Import the existing database SQL files in the order required by your current project:
   studenthub_pr8.sql, studenthub_pr9.sql, studenthub_pr10.sql, then studenthub_pr12.sql.
   (If your existing database is already set up through Practical 11, import only studenthub_pr12.sql.)
5. Confirm db.php uses the correct database credentials. Defaults are database studenthub, user root, blank password.
6. Ensure Apache can write to uploads/event_posters/ (XAMPP normally allows this for the project folder).
7. Sign in with an account whose users.role value is admin.
8. Open http://localhost/student%20hub%208/pr12.php
   If you renamed the folder, replace "student%20hub%208" with the new folder name.
9. The admin dashboard now includes a link to Practical 12 event management.

ADMIN ACCOUNT
Practical 10's SQL instructions explain how to promote an existing registered user:
UPDATE users SET role = 'admin' WHERE username = 'YOUR_REGISTERED_USERNAME';
Run this only after replacing the username with your actual account username. Do not share your password.

POSTER VALIDATION
Allowed: JPG/JPEG, PNG, WEBP. Maximum size: 2,097,152 bytes (2 MiB).
GIF, SVG, PDF, PHP, HTML and other formats are rejected. Validation is performed on the server;
changing the browser's file picker accept attribute does not bypass it.

TEST CASES
1. Visit pr12.php while logged out: redirected to login.
2. Sign in as a student and visit pr12.php: access denied (HTTP 403).
3. Sign in as admin: event form and existing events list display.
4. Add an event with all valid fields and no poster: success message and row appear.
5. Add an event with a valid JPG/PNG/WEBP under 2 MB: upload succeeds and poster displays.
6. Upload a .php, .svg, .gif, renamed text file, or invalid image: upload rejected.
7. Upload a valid image larger than 2 MB: upload rejected with a size message.
8. Leave required fields empty or enter an invalid date: validation error appears; no row is saved.
9. Edit an event's title/category/date/status: changes appear after saving.
10. Edit an event without choosing a new poster: existing poster remains.
11. Replace an event poster: new poster appears and the old image is removed.
12. Filter by category, status (open/closed), and search terms: only matching events appear.
13. Delete an event: event row and its associated poster are removed.
14. Submit a modified/missing CSRF token: request is rejected.
15. Directly visit pr12.php with a non-admin account: access is denied.

POST-LAB SUBMISSION
Capture screenshots of: event list, adding an event, editing an event, poster upload success,
invalid file type/oversized file validation, category/status filtering, and delete confirmation/result.
Submit studenthub_pr12.sql as the SQL dump. Never submit real passwords.
