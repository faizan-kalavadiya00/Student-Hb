<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/audit.php';
sh_start_session();

if (isset($_SESSION['user']['user_id'])) {
    header('Location: dashboard.php'); exit;
}
$errors = [];
$username = '';
$notice = '';
if (isset($_GET['timeout'])) $notice = 'Your session expired after 15 minutes of inactivity. Please log in again.';
elseif (isset($_GET['required'])) $notice = 'Please log in to access the dashboard.';
elseif (isset($_GET['loggedout'])) $notice = 'You have been logged out securely.';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim(is_string($_POST['username'] ?? null) ? $_POST['username'] : '');
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $csrf = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
    if (!isset($_SESSION['login_csrf']) || !hash_equals((string)$_SESSION['login_csrf'], $csrf)) {
        $errors[] = 'Security check failed. Reload the page and try again.';
    } elseif ($username === '' || $password === '') {
        $errors[] = 'Enter both username and password.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT user_id, full_name, username, password_hash, role FROM users WHERE username = :username OR email = :email LIMIT 1');
            $stmt->execute([':username' => $username, ':email' => strtolower($username)]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, (string)$user['password_hash'])) {
                if (password_needs_rehash((string)$user['password_hash'], PASSWORD_DEFAULT)) {
                    $rehash = password_hash($password, PASSWORD_DEFAULT);
                    $up = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE user_id = :id');
                    $up->execute([':hash' => $rehash, ':id' => (int)$user['user_id']]);
                }
                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'user_id' => (int)$user['user_id'], 'full_name' => (string)$user['full_name'],
                    'username' => (string)$user['username'], 'role' => (string)$user['role']
                ];
                $_SESSION['last_activity'] = time();
                sh_audit($pdo, 'LOGIN_SUCCESS', 'User signed in successfully with role '.(string)$user['role'].'.');
                unset($_SESSION['login_csrf']);

                $lastLogin = $pdo->prepare('UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = :id');
                $lastLogin->execute([':id' => (int)$user['user_id']]);

                if (!empty($_POST['remember_me'])) {
                    $selector = bin2hex(random_bytes(16));
                    $validator = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 30);
                    $tokenStmt = $pdo->prepare('INSERT INTO remember_tokens (user_id, selector, validator_hash, expires_at) VALUES (:uid, :selector, :hash, :expires)');
                    $tokenStmt->execute([':uid' => (int)$user['user_id'], ':selector' => $selector, ':hash' => hash('sha256', $validator), ':expires' => $expires]);
                    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
                    setcookie('studenthub_remember', $selector . ':' . $validator, [
                        'expires' => time() + 60 * 60 * 24 * 30, 'path' => '/', 'secure' => $secure,
                        'httponly' => true, 'samesite' => 'Lax'
                    ]);
                }
                header('Location: dashboard.php'); exit;
            }
            // Generic error avoids disclosing whether an account exists.
            $errors[] = 'Invalid username/email or password.';
        } catch (PDOException $ex) {
            error_log('Student Hub PR10 login failed: ' . $ex->getMessage());
            $errors[] = 'Login is temporarily unavailable. Check the Practical 10 database setup.';
        }
    }
}
if (!isset($_SESSION['login_csrf'])) $_SESSION['login_csrf'] = bin2hex(random_bytes(32));
function login_e(string $v): string { return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Secure Login - Student Hub</title><link rel="stylesheet" href="style.css"><script src="script.js" defer></script>
<style>
.auth-wrap{max-width:560px;margin:2rem auto;padding:1rem}.auth-card{background:var(--card-bg,#fff);padding:1.6rem;border:1px solid #d8dee8;border-radius:14px;box-shadow:0 5px 20px #0000000c}
.auth-form{display:grid;gap:1rem}.auth-form label{display:grid;gap:.4rem;font-weight:600}.auth-form input[type=text],.auth-form input[type=password]{width:100%;box-sizing:border-box;padding:.75rem;font:inherit;border:1px solid #aab4c2;border-radius:8px}
.auth-form button{padding:.8rem;border:0;border-radius:8px;background:#174ea6;color:white;font-weight:700;font:inherit;cursor:pointer}.auth-msg{padding:.8rem;border-radius:8px;margin:1rem 0}.auth-error{background:#fff0f0;color:#8b1e1e}.auth-info{background:#eaf2ff;color:#174078}.auth-muted{font-size:.9rem;color:#526071}
</style></head><body>
<header class="external-style-22"><img class="external-style-23" src="student.png" alt="Student Hub logo"><h1 class="external-style-24">Student Hub</h1>
<nav class="external-style-25"><a class="external-style-6" href="home.html">Home</a><a class="external-style-6" href="login.php" aria-current="page">Login</a><a class="external-style-6" href="pr9.php">Register</a></nav></header>
<main class="auth-wrap"><section class="auth-card"><h2>Secure Login</h2><p>Sign in with the account you registered for Student Hub.</p>
<?php if ($notice): ?><div class="auth-msg auth-info" role="status"><?= login_e($notice) ?></div><?php endif; ?>
<?php if ($errors): ?><div class="auth-msg auth-error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= login_e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form class="auth-form" method="post" action="login.php" autocomplete="on">
<input type="hidden" name="csrf_token" value="<?= login_e((string)$_SESSION['login_csrf']) ?>">
<label for="username">Username or Email<input id="username" name="username" type="text" autocomplete="username" required maxlength="254" value="<?= login_e($username) ?>"></label>
<label for="password">Password<input id="password" name="password" type="password" autocomplete="current-password" required></label>
<label style="display:flex;align-items:center;gap:.55rem;font-weight:500"><input type="checkbox" name="remember_me" value="1"> Remember me on this device (30 days)</label>
<button type="submit">Log In Securely</button>
</form><p class="auth-muted">New user? <a href="pr9.php">Create an account</a>. Sessions expire after 15 minutes of inactivity.</p>
</section></main><footer class="site-footer"><p><b>Student Hub</b></p><p>© 2026 Student Hub. All Rights Reserved.</p></footer></body></html>
