<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
sh_require_login($pdo);
$role = (string)($_SESSION['user']['role'] ?? 'student');
header('Location: ' . ($role === 'admin' ? 'admin_dashboard.php' : 'student_dashboard.php'));
exit;
