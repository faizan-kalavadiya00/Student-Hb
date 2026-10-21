<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
sh_start_session();
if (isset($_SESSION['user']['user_id'])) {
    $stmt = $pdo->prepare('DELETE FROM remember_tokens WHERE user_id = :uid');
    $stmt->execute([':uid' => (int)$_SESSION['user']['user_id']]);
}
sh_clear_remember_cookie();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool)$params['secure'], (bool)$params['httponly']);
}
session_destroy();
header('Location: login.php?loggedout=1');
exit;
