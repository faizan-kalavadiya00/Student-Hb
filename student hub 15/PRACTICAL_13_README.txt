STUDENT HUB — PRACTICAL 13
PHP JSON API and Fetch-Based CRUD without Page Reload
======================================================

FILES
- pr13.php: administrator-only CRUD interface.
- pr13_api.php: JSON API supporting GET, POST, PUT/PATCH, and DELETE-style actions.
- js/pr13.js: Fetch API client, asynchronous CRUD, debounced search, loading/success/error messages.
- studenthub_pr13.sql: optional safe schema setup and sample data.
- Uses existing managed_students (Practical 11) and pr12_events (Practical 12) tables.

SETUP (XAMPP / WINDOWS)
1. Extract the complete project into C:\xampp\htdocs\ (keep your project folder name).
2. Start Apache and MySQL.
3. Import the SQL files required by the existing project in order: studenthub_pr8.sql, studenthub_pr9.sql, studenthub_pr10.sql, studenthub_pr11.sql, studenthub_pr12.sql. Do not re-import PR8 if it would reset existing tables/data; for an already configured project, import only any missing PR11/PR12 schema.
4. Import studenthub_pr13.sql if needed; it creates the required tables if they do not exist and seeds demo data only when tables are empty.
5. Register/log in and ensure the account's users.role is admin.
6. Open http://localhost/student%20hub%208/pr13.php (replace folder name if renamed).
7. Switch between Students and Events. Create, edit, search, and delete records. The interface uses Fetch and does not reload the page for CRUD actions.

API CONTRACT
- GET pr13_api.php?resource=students|events[&search=term] returns JSON {success, resource, csrf, data}.
- POST with JSON {action:create,...fields} creates a record (HTTP 201).
- POST with JSON {action:update,id,...fields} updates a record (HTTP 200).
- POST with JSON {action:delete,id} deletes a record (HTTP 200).
- PUT/PATCH can be used for update and DELETE can be used for delete with a JSON id/action.
- Send the CSRF token from the GET response in the X-CSRF-Token header for every write.
- Error responses use appropriate HTTP status codes (400, 401, 403, 404, 405, 409, 422, 500) and JSON {success:false,error}.
- Student delete is a soft delete (status becomes deleted). Event delete removes the event and its associated valid poster file.
- Prepared statements, server-side validation, output-safe JSON, debounced search, and centralized error handling are used.

TEST CASES
1. Logged-out access returns JSON HTTP 401; logged-in non-admin access returns HTTP 403.
2. GET each resource returns valid JSON with success, data, and CSRF token.
3. Create a student with valid data; row appears without full page reload.
4. Duplicate enrollment/email returns HTTP 409 with a useful message.
5. Invalid email, empty fields, or study year outside 1–6 returns HTTP 422 and creates no record.
6. Create, edit, search, and delete an event; confirm UI updates asynchronously.
7. Invalid event status/date or excessive text returns HTTP 422.
8. Missing/invalid CSRF token on a write returns HTTP 403.
9. Search waits briefly after typing (300 ms debounce) and returns matching records.
10. Unsupported HTTP method returns HTTP 405 with an Allow header.
11. Database failures return JSON HTTP 500 without exposing SQL details to the browser.
12. Use browser DevTools > Network to capture GET and POST requests and inspect JSON response/status codes.

POST-LAB SUBMISSION
Submit pr13.php, pr13_api.php, js/pr13.js, studenthub_pr13.sql, and screenshots showing student/event CRUD, Network tab JSON responses, validation/error handling, and search debounce.
