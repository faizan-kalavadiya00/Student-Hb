STUDENT HUB — PRACTICAL 15
Final Integration, Testing, Documentation, GitHub Submission, and Local Deployment
==================================================================================

OBJECTIVE
Integrate the existing Student Hub modules into one deployable portal, verify navigation and end-to-end behavior, document installation and tests, prepare a clean GitHub repository, and deploy locally with XAMPP/Apache.

INTEGRATION
- The existing homepage links to the PR7–PR14 modules and shared portal pages.
- Shared database connection is configured in db.php.
- Authentication and role checks are handled by auth.php and the existing protected pages.
- PR11 provides student-management operations; PR12 provides event-management operations and poster uploads.
- PR13 provides Fetch/AJAX-based student and event CRUD.
- PR14 provides the role-based admin dashboard and audit log.
- All existing practical files, assets, SQL schemas, and data samples are retained.

LOCAL DEPLOYMENT (XAMPP / WINDOWS)
1. Extract the ZIP.
2. Copy the folder `student hub 8` into `C:\xampp\htdocs\`. You may rename it to `studenthub`; if you do, use that name in the URLs below.
3. Start Apache and MySQL in XAMPP.
4. Open `http://localhost/phpmyadmin`.
5. For a brand-new database only, import in this order:
   studenthub_pr8.sql, studenthub_pr9.sql, studenthub_pr10.sql, studenthub_pr11.sql, studenthub_pr12.sql, studenthub_pr13.sql, studenthub_pr14.sql.
   CAUTION: PR8 SQL may drop/recreate tables. Do not import it into a database containing data you need to preserve. For an existing installation, back up first and import only missing schema files.
6. Check database connection settings in db.php. Typical local defaults are host 127.0.0.1, database studenthub, username root, and empty password. Other environments can use the documented STUDENTHUB_DB_* environment variables.
7. Register an account through pr9.php. To test admin-only features, promote the account in phpMyAdmin:
   UPDATE users SET role='admin' WHERE username='YOUR_REGISTERED_USERNAME';
   Replace the placeholder with the exact username. Keep real credentials private.
8. Open `http://localhost/student%20hub%208/home.html`.
9. Sign in and exercise the relevant pages. Check the test cases in PRACTICAL_15_TEST_CASES.txt and record actual outcomes in PRACTICAL_15_TEST_REPORT_TEMPLATE.txt.

VIRTUAL HOST (OPTIONAL)
See VIRTUAL_HOST_EXAMPLE.txt. Enable the Apache virtual-host configuration only if required, add the local hostname to the Windows hosts file, and restart Apache. Do not expose a local development server directly to the public internet.

GITHUB SUBMISSION
1. Install Git and open PowerShell in the project folder.
2. Review `.gitignore` and inspect every SQL file and data sample for real/private information before publishing.
3. Initialize and commit:
   git init
   git add .
   git status
   git commit -m "Complete Student Hub practical 15 integration"
4. Create an empty repository on GitHub, then connect and push (replace the placeholder URL with your own repository URL):
   git branch -M main
   git remote add origin YOUR_GITHUB_REPOSITORY_URL
   git push -u origin main
5. Add the actual repository URL to your practical submission. Do not invent or submit a placeholder URL.
6. Never commit passwords, API keys, private student records, browser/session data, or production database dumps. Review `git status` and the staged diff before committing.

SECURITY / ACCESSIBILITY REVIEW
Use SECURITY_ACCESSIBILITY_CHECKLIST.txt. At minimum, verify authentication and role restrictions, CSRF protections, validation, prepared statements, output escaping, upload validation, keyboard navigation, visible focus, form labels, and meaningful image alt text. Document any remaining limitations rather than claiming unperformed checks passed.

DEPLOYMENT LIMITATIONS
- The supplied project targets a local Apache/PHP/MySQL environment; public hosting needs a PHP-compatible host and configured database.
- Public deployment must use HTTPS, a strong database credential, appropriate file permissions, backups, and production error handling.
- GitHub Pages cannot run PHP or MySQL; it can host static assets only. PHP modules require a PHP-compatible server.
- No public GitHub repository is created automatically by this ZIP. Create and verify the repository using your own account.

DELIVERABLES
- Complete Student Hub source and assets.
- SQL schema files for the practical modules.
- README.md and practical-specific documentation.
- Test cases and a blank test-report template.
- Security/accessibility checklist.
- Optional local virtual-host example.
