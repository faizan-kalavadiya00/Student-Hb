<?php
declare(strict_types=1);

/* Shared authentication helpers for Student Hub Practical 10. */
const SH_SESSION_TIMEOUT = 900; // 15 minutes of inactivity

function sh_start_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'secure' => $secure,
        'httponly' => true, 'samesite' => 'Lax'
    ]);
    session_start();
}

function sh_clear_remember_cookie(): void {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    setcookie('studenthub_remember', '', [
        'expires' => time() - 3600, 'path' => '/', 'secure' => $secure,
        'httponly' => true, 'samesite' => 'Lax'
    ]);
}

function sh_remember_login(PDO $pdo): bool {
    $cookie = $_COOKIE['studenthub_remember'] ?? '';
    if (!is_string($cookie) || !preg_match('/^([a-f0-9]{32}):([a-f0-9]{64})$/', $cookie, $m)) return false;
    $stmt = $pdo->prepare('SELECT selector, user_id, validator_hash, expires_at FROM remember_tokens WHERE selector = :selector LIMIT 1');
    $stmt->execute([':selector' => $m[1]]);
    $token = $stmt->fetch();
    if (!$token) { sh_clear_remember_cookie(); return false; }
    if (strtotime((string)$token['expires_at']) <= time() || !hash_equals((string)$token['validator_hash'], hash('sha256', $m[2]))) {
        $del = $pdo->prepare('DELETE FROM remember_tokens WHERE selector = :selector');
        $del->execute([':selector' => $m[1]]);
        sh_clear_remember_cookie();
        return false;
    }
    $userStmt = $pdo->prepare('SELECT user_id, full_name, username, role FROM users WHERE user_id = :id LIMIT 1');
    $userStmt->execute([':id' => (int)$token['user_id']]);
    $user = $userStmt->fetch();
    if (!$user) { sh_clear_remember_cookie(); return false; }

    // Rotate the validator after each successful use to reduce replay risk.
    $validator = bin2hex(random_bytes(32));
    $hash = hash('sha256', $validator);
    $expires = date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 30);
    $update = $pdo->prepare('UPDATE remember_tokens SET validator_hash = :hash, expires_at = :expires WHERE selector = :selector');
    $update->execute([':hash' => $hash, ':expires' => $expires, ':selector' => $m[1]]);
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    setcookie('studenthub_remember', $m[1] . ':' . $validator, [
        'expires' => time() + 60 * 60 * 24 * 30, 'path' => '/', 'secure' => $secure,
        'httponly' => true, 'samesite' => 'Lax'
    ]);
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'user_id' => (int)$user['user_id'], 'full_name' => (string)$user['full_name'],
        'username' => (string)$user['username'], 'role' => (string)$user['role']
    ];
    $_SESSION['last_activity'] = time();
    return true;
}

function sh_require_login(PDO $pdo): void {
    sh_start_session();
    if (isset($_SESSION['user']['user_id'])) {
        $last = (int)($_SESSION['last_activity'] ?? 0);
        if ($last > 0 && (time() - $last) > SH_SESSION_TIMEOUT) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool)$params['secure'], (bool)$params['httponly']);
            }
            session_destroy();
            sh_clear_remember_cookie();
            header('Location: login.php?timeout=1');
            exit;
        }
        $_SESSION['last_activity'] = time();
        return;
    }
    if (sh_remember_login($pdo)) return;
    header('Location: login.php?required=1');
    exit;
}

function sh_require_role(PDO $pdo, string $role): void {
    sh_require_login($pdo);
    if (($_SESSION['user']['role'] ?? '') !== $role) {
        http_response_code(403);
        echo '<!doctype html><html lang="en"><meta charset="utf-8"><title>Access denied</title><body><h1>403 — Access denied</h1><p>You do not have permission to view this page.</p><p><a href="dashboard.php">Return to your dashboard</a></p></body></html>';
        exit;
    }
}

function sh_e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
