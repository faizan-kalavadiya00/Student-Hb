<?php
declare(strict_types=1);

/*
 * Student Hub PR8: PDO connection.
 * For a local XAMPP setup, the defaults below work with the usual root/no-password setup.
 * For other environments, set STUDENTHUB_DB_HOST, STUDENTHUB_DB_NAME,
 * STUDENTHUB_DB_USER, and STUDENTHUB_DB_PASS environment variables.
 */
$host = getenv('STUDENTHUB_DB_HOST') ?: '127.0.0.1';
$dbname = getenv('STUDENTHUB_DB_NAME') ?: 'studenthub';
$username = getenv('STUDENTHUB_DB_USER') ?: 'root';
$password = getenv('STUDENTHUB_DB_PASS') ?: '';

$dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $exception) {
    // Log technical details on the server; show a safe message in the browser.
    error_log('Student Hub database connection failed: ' . $exception->getMessage());
    http_response_code(500);
    exit(
        '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">' .
        '<title>Database connection</title><body style="font-family:Arial,sans-serif;padding:2rem;max-width:760px;margin:auto">' .
        '<h1>Database connection unavailable</h1>' .
        '<p>Student Hub could not connect to MySQL. Start MySQL in XAMPP and import <code>studenthub_pr8.sql</code> using phpMyAdmin.</p>' .
        '<p>Confirm the database is named <code>studenthub</code> and check the settings at the top of <code>db.php</code>.</p>' .
        '</body></html>'
    );
}
