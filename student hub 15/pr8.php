<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/db.php';

function h(mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (!isset($_SESSION['pr8_csrf'])) {
    $_SESSION['pr8_csrf'] = bin2hex(random_bytes(32));
}
$success = '';
$error = '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
        if (!hash_equals($_SESSION['pr8_csrf'], $token)) {
            throw new RuntimeException('The form session expired. Refresh the page and try again.');
        }

        $studentId = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
        $eventId = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
        if (!$studentId || !$eventId) {
            throw new RuntimeException('Please select both a student and an event.');
        }

        // PDO prepared statement: values are bound, not concatenated into SQL.
        $insert = $pdo->prepare(
            "INSERT INTO registrations (student_id, event_id) VALUES (:student_id, :event_id)"
        );
        $insert->execute([
            ':student_id' => $studentId,
            ':event_id' => $eventId,
        ]);
        $success = 'Registration saved successfully.';
        $_SESSION['pr8_csrf'] = bin2hex(random_bytes(32));
    }
} catch (PDOException $exception) {
    if ($exception->getCode() === '23000') {
        $error = 'This student may already be registered for that event, or the selected record is not available.';
    } else {
        error_log('Student Hub PR8 operation failed: ' . $exception->getMessage());
        $error = 'The registration could not be saved. Check the database setup and try again.';
    }
} catch (RuntimeException $exception) {
    $error = $exception->getMessage();
}

try {
    $students = $pdo->query(
        'SELECT student_id, enrollment_no, full_name FROM students ORDER BY full_name'
    )->fetchAll();
    $events = $pdo->query(
        'SELECT event_id, title, event_date, venue FROM events ORDER BY event_date, title'
    )->fetchAll();

    // Join normalized tables to display readable registration details.
    $report = $pdo->query(
        "SELECT r.registration_id, s.enrollment_no, s.full_name, e.title AS event_title,
                e.event_date, e.venue, r.status, r.registered_at
         FROM registrations r
         INNER JOIN students s ON s.student_id = r.student_id
         INNER JOIN events e ON e.event_id = r.event_id
         ORDER BY r.registered_at DESC, r.registration_id DESC"
    )->fetchAll();
} catch (PDOException $exception) {
    error_log('Student Hub PR8 read failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('Could not load Student Hub records. Check the imported schema and database connection.');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Database &amp; Event Registration - Student Hub PR8</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .pr8-main{max-width:1100px;margin:2rem auto;padding:0 1rem}
        .pr8-card{background:var(--card-bg,#fff);border:1px solid #d8dee8;border-radius:12px;padding:1.25rem;margin:1rem 0;box-shadow:0 3px 12px #0000000b}
        .pr8-form{display:grid;gap:1rem;max-width:620px}
        .pr8-form label{display:grid;gap:.4rem;font-weight:600}
        .pr8-form select,.pr8-form button{font:inherit;padding:.7rem;border:1px solid #aab4c2;border-radius:7px}
        .pr8-form button{background:#174ea6;color:#fff;border:0;cursor:pointer;font-weight:700}
        .pr8-message{padding:.8rem 1rem;border-radius:7px;margin:1rem 0}
        .pr8-success{background:#e8f7ed;color:#14532d}.pr8-error{background:#fff0f0;color:#8b1e1e}
        .pr8-table-wrap{overflow-x:auto}.pr8-table{width:100%;border-collapse:collapse}
        .pr8-table th,.pr8-table td{border:1px solid #d5dbe5;padding:.65rem;text-align:left}
        .pr8-table th{background:#edf2fa}
        .pr8-note{line-height:1.6}
        body.dark-mode .pr8-card{background:#202633;color:#f1f5f9;border-color:#414b5c}
        body.dark-mode .pr8-table th{background:#303b4e;color:#fff}
        body.dark-mode .pr8-table td{border-color:#414b5c}
    </style>
    <script src="script.js" defer></script>
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
        <a class="external-style-6" href="events.html">Events</a>
        <a class="external-style-6" href="students.html">Student Profiles</a>
        <a class="external-style-6" href="pr7.php">Form Processing</a>
        <a class="external-style-6" href="pr8.php" aria-current="page">Database</a>
    <a class="external-style-6" href="pr9.php">Secure Registration</a>
        <a class="external-style-6" href="login.php">Secure Login</a>
    </nav>
</header>

<main class="pr8-main">
    <h1>Practical 8: MySQL Schema, PDO Connectivity &amp; Prepared Statements</h1>
    <p class="pr8-note">This page demonstrates a normalized MySQL database, foreign-key relationships, a secure PDO connection, prepared INSERT statements, and a joined registration report.</p>

    <?php if ($success !== ''): ?><div class="pr8-message pr8-success" role="status"><?= h($success) ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="pr8-message pr8-error" role="alert"><?= h($error) ?></div><?php endif; ?>

    <section class="pr8-card">
        <h2>Register a Student for an Event</h2>
        <?php if (!$students || !$events): ?>
            <p>Seed data is missing. Import <code>studenthub_pr8.sql</code> in phpMyAdmin.</p>
        <?php else: ?>
        <form class="pr8-form" method="post" action="pr8.php">
            <input type="hidden" name="csrf_token" value="<?= h($_SESSION['pr8_csrf']) ?>">
            <label for="student_id">Student
                <select id="student_id" name="student_id" required>
                    <option value="">-- Select student --</option>
                    <?php foreach ($students as $student): ?>
                    <option value="<?= h($student['student_id']) ?>"><?= h($student['enrollment_no'] . ' — ' . $student['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label for="event_id">Event
                <select id="event_id" name="event_id" required>
                    <option value="">-- Select event --</option>
                    <?php foreach ($events as $event): ?>
                    <option value="<?= h($event['event_id']) ?>"><?= h($event['title'] . ' — ' . $event['event_date'] . ' (' . $event['venue'] . ')') ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit">Save Registration</button>
        </form>
        <?php endif; ?>
    </section>

    <section class="pr8-card">
        <h2>Database Relationship / ER Model</h2>
        <ul class="pr8-note">
            <li><strong>students</strong> (PK: student_id) stores each student once.</li>
            <li><strong>events</strong> (PK: event_id) stores each event once.</li>
            <li><strong>registrations</strong> (PK: registration_id) links students and events using foreign keys.</li>
            <li>One student can register for many events, and one event can have many students: a many-to-many relationship resolved by the registrations table.</li>
            <li>The unique constraint on (student_id, event_id) prevents duplicate registrations.</li>
        </ul>
        <p><strong>ER view:</strong> students (1) ─── (many) registrations (many) ─── (1) events</p>
    </section>

    <section class="pr8-card">
        <h2>Saved Registrations</h2>
        <?php if (!$report): ?>
            <p>No registrations found yet. Use the form above to add one.</p>
        <?php else: ?>
        <div class="pr8-table-wrap">
            <table class="pr8-table">
                <thead><tr><th>ID</th><th>Enrollment</th><th>Student</th><th>Event</th><th>Date</th><th>Venue</th><th>Status</th><th>Registered At</th></tr></thead>
                <tbody>
                <?php foreach ($report as $row): ?>
                    <tr>
                        <td><?= h($row['registration_id']) ?></td><td><?= h($row['enrollment_no']) ?></td>
                        <td><?= h($row['full_name']) ?></td><td><?= h($row['event_title']) ?></td>
                        <td><?= h($row['event_date']) ?></td><td><?= h($row['venue']) ?></td>
                        <td><?= h($row['status']) ?></td><td><?= h($row['registered_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>
    <section class="pr8-card">
        <h2>PDO Connection Status</h2>
        <p>Connected to MySQL database <code>studenthub</code>. PDO exception mode and native prepared statements are enabled.</p>
    </section>
</main>
</body>
</html>
