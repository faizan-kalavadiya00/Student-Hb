STUDENT HUB — PRACTICAL 7
PHP Form Processing with Server-Side Validation and CSV/JSON File Storage
=========================================================================

WHAT IS INCLUDED
- Existing Student Hub Practical 1–6 files and features retained.
- pr7.php: registration/contact form handled using the POST method.
- Server-side validation for name, email, Indian mobile number, course, year and message length.
- Input trimming/control-character cleanup and HTML output escaping.
- CSRF token validation for form submission.
- Safe CSV append with file locking and spreadsheet-formula prefix protection.
- JSON storage using a lock file and temporary-file replacement.
- Clear success/error messages and a table showing the 10 most recent stored records.
- Navigation links to PR7 added to existing HTML pages.
- data/pr7_submissions.csv and data/pr7_submissions.json initialised for demonstration.
- PRACTICAL_7_TEST_CASES.txt with test cases and expected results.

HOW TO RUN (PHP SERVER REQUIRED)
1. Install/start XAMPP or WAMP.
2. Copy the complete "student hub 4" folder into XAMPP's htdocs folder (e.g. C:\xampp\htdocs\student hub 4).
3. Start Apache from the XAMPP Control Panel.
4. Open http://localhost/student%20hub%204/pr7.php in the browser.
   If you rename the folder, update the URL to match.
5. Submit a valid form and check data/pr7_submissions.csv and data/pr7_submissions.json.
6. Use a local PHP server instead if preferred: open a terminal in this folder and run
   php -S localhost:8000
   Then visit http://localhost:8000/pr7.php.

IMPORTANT
- Do not open pr7.php by double-clicking it or using a file:// URL. PHP must run through Apache or a PHP server.
- Ensure the data folder is writable by the PHP/Apache process.
- The record table is intended for a local classroom demonstration. Do not deploy public record viewing without authentication and access controls.
- The data files contain personal form data. Use dummy test information and do not publish the generated CSV/JSON files.
- Existing Practical 6 pages use Fetch API to read JSON; run the site through localhost for those pages too.
