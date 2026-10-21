<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/db.php';

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function text_length(string $value): int {
    if (function_exists('mb_strlen')) return mb_strlen($value, 'UTF-8');
    $count = preg_match_all('/./us', $value, $matches);
    return $count === false ? strlen($value) : $count;
}
function clean_input(mixed $value): string {
    if (!is_string($value)) return '';
    $value = trim($value);
    return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
}
function audit(PDO $pdo, string $action, ?int $userId, string $details = ''): void {
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO registration_audit_logs (user_id, action, details, ip_address, created_at)
             VALUES (:user_id, :action, :details, :ip, CURRENT_TIMESTAMP)'
        );
        $ip = isset($_SERVER['REMOTE_ADDR']) ? substr((string)$_SERVER['REMOTE_ADDR'], 0, 45) : null;
        $stmt->execute([
            ':user_id' => $userId,
            ':action' => $action,
            ':details' => substr($details, 0, 255),
            ':ip' => $ip,
        ]);
    } catch (PDOException $ex) {
        // Do not expose audit/database details to the visitor.
        error_log('Student Hub PR9 audit write failed: ' . $ex->getMessage());
    }
}

if (!isset($_SESSION['pr9_csrf'])) {
    $_SESSION['pr9_csrf'] = bin2hex(random_bytes(32));
}
$errors = [];
$success = '';
$old = ['full_name' => '', 'username' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['full_name'] = clean_input($_POST['full_name'] ?? null);
    $old['username'] = clean_input($_POST['username'] ?? null);
    $old['email'] = strtolower(clean_input($_POST['email'] ?? null));
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $confirmPassword = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';
    $token = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';

    if (!hash_equals((string)$_SESSION['pr9_csrf'], $token)) {
        $errors[] = 'Security check failed. Reload the page and submit the form again.';
        audit($pdo, 'registration_rejected', null, 'Invalid CSRF token');
    }
    if (text_length($old['full_name']) < 2 || text_length($old['full_name']) > 100) {
        $errors[] = 'Full name must be between 2 and 100 characters.';
    }
    if (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $old['username'])) {
        $errors[] = 'Username must be 3–30 characters and may contain letters, numbers, dot, underscore, or hyphen.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || strlen($old['email']) > 254) {
        $errors[] = 'Enter a valid email address.';
    }
    if (strlen($password) < 8 || strlen($password) > 72) {
        $errors[] = 'Password must be between 8 and 72 bytes long.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Password and confirm password do not match.';
    }

    if (!$errors) {
        try {
            // Prepared statement prevents SQL injection; unique indexes enforce duplicate checks safely.
            $check = $pdo->prepare(
                'SELECT user_id, username, email FROM users WHERE username = :username OR email = :email LIMIT 1'
            );
            $check->execute([':username' => $old['username'], ':email' => $old['email']]);
            $duplicate = $check->fetch();

            if ($duplicate) {
                $errors[] = strcasecmp((string)$duplicate['username'], $old['username']) === 0
                    ? 'That username is already registered. Please choose another.'
                    : 'That email address is already registered. Please use another email.';
                audit($pdo, 'registration_rejected', null, 'Duplicate username or email');
            } else {
                // Never store the plain-text password.
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $insert = $pdo->prepare(
                    'INSERT INTO users (full_name, username, email, password_hash)
                     VALUES (:full_name, :username, :email, :password_hash)'
                );
                $insert->execute([
                    ':full_name' => $old['full_name'],
                    ':username' => $old['username'],
                    ':email' => $old['email'],
                    ':password_hash' => $passwordHash,
                ]);
                $newUserId = (int)$pdo->lastInsertId();
                audit($pdo, 'registration_success', $newUserId, 'New user account created');
                $success = 'Account created successfully! You can now use your registered account.';
                $old = ['full_name' => '', 'username' => '', 'email' => ''];
                $_SESSION['pr9_csrf'] = bin2hex(random_bytes(32));
            }
        } catch (PDOException $ex) {
            // Unique indexes are the final protection against simultaneous duplicate submissions.
            if ($ex->getCode() === '23000') {
                $errors[] = 'That username or email is already registered. Please check your details.';
                audit($pdo, 'registration_rejected', null, 'Duplicate unique-key constraint');
            } else {
                error_log('Student Hub PR9 registration failed: ' . $ex->getMessage());
                $errors[] = 'Registration could not be completed. Please check the database setup and try again.';
                audit($pdo, 'registration_error', null, 'Database insert failed');
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Secure Registration - Student Hub PR9</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
    <style>
        .pr9-main{max-width:900px;margin:2rem auto;padding:0 1rem}
        .pr9-card{max-width:650px;margin:auto;padding:1.5rem;background:var(--card-bg,#fff);border:1px solid #d8dee8;border-radius:14px;box-shadow:0 5px 20px #0000000c}
        .pr9-form{display:grid;gap:1rem}
        .pr9-form label{display:grid;gap:.4rem;font-weight:600}
        .pr9-form input{box-sizing:border-box;width:100%;font:inherit;padding:.75rem;border:1px solid #aab4c2;border-radius:8px}
        .pr9-form button{font:inherit;padding:.8rem 1rem;border:0;border-radius:8px;background:#174ea6;color:#fff;font-weight:700;cursor:pointer}
        .pr9-message{padding:.8rem 1rem;border-radius:8px;margin:1rem 0}
        .pr9-success{background:#e8f7ed;color:#14532d}.pr9-error{background:#fff0f0;color:#8b1e1e}
        .pr9-help{font-size:.9rem;line-height:1.5;color:#526071}
        body.dark-mode .pr9-card{background:#202633;color:#f1f5f9;border-color:#414b5c}
        body.dark-mode .pr9-help{color:#cbd5e1}
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
        <a class="external-style-6" href="events.html">Events</a>
        <a class="external-style-6" href="registration.html">Registration</a>
        <a class="external-style-6" href="pr7.php">PHP Form</a>
        <a class="external-style-6" href="pr8.php">Database</a>
        <a class="external-style-6" href="pr9.php" aria-current="page">Secure Registration</a>
    </nav>
</header>
<main class="pr9-main">
    <section class="pr9-card">
        <h2>Secure User Registration</h2>
        <p>Create a Student Hub account. Required fields are marked with an asterisk.</p>
        <?php if ($success !== ''): ?>
            <div class="pr9-message pr9-success" role="status"><?= e($success) ?></div>
        <?php endif; ?>
        <?php if ($errors): ?>
            <div class="pr9-message pr9-error" role="alert">
                <strong>Please fix the following:</strong>
                <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>
        <form class="pr9-form" method="post" action="pr9.php" autocomplete="on">
            <input type="hidden" name="csrf_token" value="<?= e((string)$_SESSION['pr9_csrf']) ?>">
            <label for="full_name">Full Name *
                <input id="full_name" name="full_name" type="text" minlength="2" maxlength="100" autocomplete="name" required value="<?= e($old['full_name']) ?>">
            </label>
            <label for="username">Username *
                <input id="username" name="username" type="text" minlength="3" maxlength="30" pattern="[A-Za-z0-9_.-]{3,30}" autocomplete="username" required value="<?= e($old['username']) ?>" aria-describedby="username-help">
            </label>
            <small class="pr9-help" id="username-help">Use 3–30 letters, numbers, dots, underscores, or hyphens.</small>
            <label for="email">Email Address *
                <input id="email" name="email" type="email" maxlength="254" autocomplete="email" required value="<?= e($old['email']) ?>">
            </label>
            <label for="password">Password *
                <input id="password" name="password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required>
            </label>
            <label for="confirm_password">Confirm Password *
                <input id="confirm_password" name="confirm_password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required>
            </label>
            <p class="pr9-help">Passwords are hashed with PHP password_hash() before storage. Do not reuse a password you use elsewhere.</p>
            <button type="submit">Create Account</button>
        </form>
    </section>
</main>
<footer class="site-footer"><p><b>Student Hub</b></p><p>© 2026 Student Hub. All Rights Reserved.</p></footer>
</body>
</html>
