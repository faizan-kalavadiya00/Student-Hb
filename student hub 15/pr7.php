<?php
declare(strict_types=1);
session_start();

/*
 * Student Hub - Practical 7
 * Server-side validation, sanitization, CSRF protection, CSV and JSON storage.
 * Never store passwords or other unnecessary sensitive information in these files.
 */
$dataDir = __DIR__ . DIRECTORY_SEPARATOR . 'data';
$csvFile = $dataDir . DIRECTORY_SEPARATOR . 'pr7_submissions.csv';
$jsonFile = $dataDir . DIRECTORY_SEPARATOR . 'pr7_submissions.json';

if (!is_dir($dataDir)) {
    @mkdir($dataDir, 0755, true);
}
if (!isset($_SESSION['pr7_csrf'])) {
    $_SESSION['pr7_csrf'] = bin2hex(random_bytes(32));
}

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function clean_text(string $value): string {
    $value = trim($value);
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
    return $value;
}

$errors = [];
$success = '';
$old = ['name' => '', 'email' => '', 'phone' => '', 'course' => '', 'year' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $key => $_) {
        $old[$key] = isset($_POST[$key]) && is_string($_POST[$key]) ? clean_text($_POST[$key]) : '';
    }
    $token = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!hash_equals((string)$_SESSION['pr7_csrf'], $token)) {
        $errors[] = 'Your form session expired or could not be verified. Please reload the page and try again.';
    }

    if ($old['name'] === '' || mb_strlen($old['name']) < 2 || mb_strlen($old['name']) > 80) {
        $errors[] = 'Name is required and must be between 2 and 80 characters.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || strlen($old['email']) > 254) {
        $errors[] = 'Enter a valid email address.';
    }
    $phoneDigits = preg_replace('/\D+/', '', $old['phone']) ?? '';
    if (!preg_match('/^(?:\+?91[\s-]?)?[6-9]\d{9}$/', preg_replace('/[\s().-]+/', '', $old['phone']) ?? '')) {
        $errors[] = 'Enter a valid 10-digit Indian mobile number (optional +91).';
    }
    if (!in_array($old['course'], ['Computer Science', 'Information Technology', 'Computer Engineering', 'Other'], true)) {
        $errors[] = 'Select a valid course.';
    }
    if (!in_array($old['year'], ['1', '2', '3', '4'], true)) {
        $errors[] = 'Select a valid year of study.';
    }
    if (mb_strlen($old['message']) > 1000) {
        $errors[] = 'Message must be 1000 characters or fewer.';
    }

    if (!$errors) {
        $record = [
            'id' => bin2hex(random_bytes(6)),
            'submitted_at' => date('c'),
            'name' => $old['name'],
            'email' => $old['email'],
            'phone' => $phoneDigits,
            'course' => $old['course'],
            'year' => $old['year'],
            'message' => $old['message']
        ];

        // Append to CSV with an exclusive lock; escape spreadsheet formula prefixes.
        $csvOk = false;
        $handle = @fopen($csvFile, 'c+');
        if ($handle !== false) {
            if (flock($handle, LOCK_EX)) {
                $stat = fstat($handle);
                if ($stat !== false && $stat['size'] === 0) {
                    fputcsv($handle, array_keys($record));
                }
                fseek($handle, 0, SEEK_END);
                $csvRecord = $record;
                foreach (['name', 'email', 'phone', 'course', 'year', 'message'] as $field) {
                    if (isset($csvRecord[$field]) && preg_match('/^[\s]*[=+\-@]/u', (string)$csvRecord[$field])) {
                        $csvRecord[$field] = "'" . $csvRecord[$field];
                    }
                }
                $csvOk = fputcsv($handle, array_values($csvRecord)) !== false && fflush($handle);
                flock($handle, LOCK_UN);
            }
            fclose($handle);
        }

        // Read, append and safely replace JSON under an exclusive lock.
        $jsonOk = false;
        $lockFile = $jsonFile . '.lock';
        $lockHandle = @fopen($lockFile, 'c');
        if ($lockHandle !== false && flock($lockHandle, LOCK_EX)) {
            $records = [];
            if (is_file($jsonFile)) {
                $existing = file_get_contents($jsonFile);
                if ($existing !== false && trim($existing) !== '') {
                    $decoded = json_decode($existing, true);
                    if (is_array($decoded)) {
                        $records = $decoded;
                    }
                }
            }
            $records[] = $record;
            $encoded = json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($encoded !== false) {
                $tmpFile = $jsonFile . '.tmp';
                $jsonOk = file_put_contents($tmpFile, $encoded . PHP_EOL, LOCK_EX) !== false
                    && @rename($tmpFile, $jsonFile);
                if (!$jsonOk && is_file($tmpFile)) {
                    @unlink($tmpFile);
                }
            }
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
        } elseif ($lockHandle !== false) {
            fclose($lockHandle);
        }

        if ($csvOk && $jsonOk) {
            $success = 'Form submitted successfully. The record was saved in both CSV and JSON files.';
            $old = array_fill_keys(array_keys($old), '');
            $_SESSION['pr7_csrf'] = bin2hex(random_bytes(32));
        } else {
            $errors[] = 'The form was validated, but storage could not be completed. Check that the data folder is writable and try again.';
        }
    }
}

$storedRecords = [];
if (is_file($jsonFile)) {
    $contents = @file_get_contents($jsonFile);
    $decoded = $contents !== false ? json_decode($contents, true) : null;
    if (is_array($decoded)) {
        $storedRecords = array_slice(array_reverse($decoded), 0, 10);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practical 7 — PHP Form Processing | Student Hub</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .pr7-wrap{max-width:980px;margin:28px auto;padding:0 18px}
        .pr7-card{background:var(--card-bg,#fff);border:1px solid #d8dee8;border-radius:14px;padding:24px;margin:18px 0;box-shadow:0 5px 18px rgba(0,0,0,.06)}
        .pr7-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
        .pr7-field{display:flex;flex-direction:column;gap:7px}
        .pr7-field.full{grid-column:1/-1}
        .pr7-field label{font-weight:600}
        .pr7-field input,.pr7-field select,.pr7-field textarea{width:100%;box-sizing:border-box;padding:11px 12px;border:1px solid #aeb8c7;border-radius:7px;font:inherit;background:transparent}
        .pr7-field textarea{min-height:100px;resize:vertical}
        .pr7-submit{grid-column:1/-1;justify-self:start;padding:12px 22px;border:0;border-radius:8px;background:#1769aa;color:#fff;font-weight:700;cursor:pointer}
        .pr7-alert{padding:13px 16px;border-radius:8px;margin:15px 0}
        .pr7-success{background:#e8f7ed;color:#14532d;border:1px solid #86d6a1}
        .pr7-error{background:#fff0f0;color:#8a1c1c;border:1px solid #efaaaa}
        .pr7-table-wrap{overflow-x:auto}.pr7-table{width:100%;border-collapse:collapse}.pr7-table th,.pr7-table td{padding:10px;border:1px solid #d5dce5;text-align:left;overflow-wrap:anywhere}.pr7-table th{background:#eef4fb}
        .pr7-note{font-size:.94rem;color:#586579}
        @media(max-width:620px){.pr7-form{grid-template-columns:1fr}.pr7-field.full,.pr7-submit{grid-column:auto}.pr7-card{padding:16px}}
    </style>
</head>
<body>
<header class="external-style-22">
    <img class="external-style-23" src="student.png" alt="Student Hub logo">
    <h1 class="external-style-24">Student Hub</h1>
    <nav class="external-style-25" aria-label="Main navigation">
        <a class="external-style-6" href="home.html">Home</a>
        <a class="external-style-6" href="dashboard.php">Dashboard</a>
        <a class="external-style-6" href="profile.html">Profile</a>
        <a class="external-style-6" href="faq.html">FAQs</a>
        <a class="external-style-6" href="about.html">About</a>
        <a class="external-style-6" href="login.php">Login</a>
        <a class="external-style-6" href="events.html">Events</a>
        <a class="external-style-6" href="students.html">Student Profiles</a>
        <a class="external-style-6" href="pr7.php" aria-current="page">PHP Form</a>
    <a class="external-style-6" href="pr8.php">Database</a>
    <a class="external-style-6" href="pr9.php">Secure Registration</a>
        <a class="external-style-6" href="login.php">Secure Login</a>
    </nav>
</header>
<main class="pr7-wrap">
    <h1>Practical 7: PHP Form Processing</h1>
    <p>Submit a registration/contact form with server-side validation, input sanitization, CSRF protection, and CSV/JSON file storage.</p>
    <?php if ($success !== ''): ?>
        <div class="pr7-alert pr7-success" role="status"><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($errors): ?>
        <div class="pr7-alert pr7-error" role="alert">
            <strong>Please correct the following:</strong>
            <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>
    <section class="pr7-card">
        <h2>Registration / Contact Form</h2>
        <form method="POST" action="pr7.php" class="pr7-form">
            <input type="hidden" name="csrf_token" value="<?= e((string)$_SESSION['pr7_csrf']) ?>">
            <div class="pr7-field">
                <label for="name">Full name *</label>
                <input id="name" name="name" type="text" maxlength="80" autocomplete="name" required value="<?= e($old['name']) ?>">
            </div>
            <div class="pr7-field">
                <label for="email">Email address *</label>
                <input id="email" name="email" type="email" maxlength="254" autocomplete="email" required value="<?= e($old['email']) ?>">
            </div>
            <div class="pr7-field">
                <label for="phone">Mobile number *</label>
                <input id="phone" name="phone" type="tel" maxlength="20" autocomplete="tel" placeholder="9876543210 or +91 9876543210" required value="<?= e($old['phone']) ?>">
            </div>
            <div class="pr7-field">
                <label for="course">Course / Department *</label>
                <select id="course" name="course" required>
                    <option value="">Select course</option>
                    <?php foreach (['Computer Science','Information Technology','Computer Engineering','Other'] as $course): ?>
                    <option value="<?= e($course) ?>"<?= $old['course'] === $course ? ' selected' : '' ?>><?= e($course) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="pr7-field">
                <label for="year">Year of study *</label>
                <select id="year" name="year" required>
                    <option value="">Select year</option>
                    <?php foreach (['1'=>'First Year','2'=>'Second Year','3'=>'Third Year','4'=>'Fourth Year'] as $value=>$label): ?>
                    <option value="<?= e($value) ?>"<?= $old['year'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="pr7-field full">
                <label for="message">Message (optional, max 1000 characters)</label>
                <textarea id="message" name="message" maxlength="1000"><?= e($old['message']) ?></textarea>
            </div>
            <button class="pr7-submit" type="submit">Submit Form</button>
        </form>
        <p class="pr7-note">Fields marked * are required. Validation is performed again on the server; browser validation alone is not trusted.</p>
    </section>
    <section class="pr7-card">
        <h2>Recently Stored Records</h2>
        <p class="pr7-note">Shows up to the 10 most recent records from the JSON file. For classroom demonstration only; in a production system this section should require administrator authentication.</p>
        <?php if (!$storedRecords): ?>
            <p>No records stored yet. Submit the form above to create the CSV and JSON records.</p>
        <?php else: ?>
        <div class="pr7-table-wrap"><table class="pr7-table">
            <thead><tr><th>Submitted At</th><th>Name</th><th>Email</th><th>Course</th><th>Year</th></tr></thead>
            <tbody>
            <?php foreach ($storedRecords as $record): ?>
                <tr>
                    <td><?= e((string)($record['submitted_at'] ?? '')) ?></td>
                    <td><?= e((string)($record['name'] ?? '')) ?></td>
                    <td><?= e((string)($record['email'] ?? '')) ?></td>
                    <td><?= e((string)($record['course'] ?? '')) ?></td>
                    <td><?= e((string)($record['year'] ?? '')) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
