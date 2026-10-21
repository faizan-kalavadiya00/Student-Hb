<?php
declare(strict_types=1);

/** Record a security-relevant administrator action without breaking the requested workflow. */
function sh_audit(PDO $pdo, string $action, string $details = ''): void {
    try {
        $user = $_SESSION['user'] ?? [];
        $userId = isset($user['user_id']) ? (int)$user['user_id'] : null;
        $stmt = $pdo->prepare('INSERT INTO admin_audit_logs (user_id, username, action, details, ip_address, user_agent) VALUES (:uid, :username, :action, :details, :ip, :ua)');
        $stmt->execute([
            ':uid' => $userId,
            ':username' => substr((string)($user['username'] ?? 'system'), 0, 80),
            ':action' => substr($action, 0, 80),
            ':details' => substr($details, 0, 500),
            ':ip' => substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45) ?: null,
            ':ua' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255) ?: null
        ]);
    } catch (Throwable $e) {
        // Audit logging must never expose database details or break an otherwise valid operation.
        error_log('Student Hub audit logging failed: ' . $e->getMessage());
    }
}
