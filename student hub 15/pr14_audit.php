<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/audit.php';
sh_require_role($pdo, 'admin');
sh_start_session();
sh_audit($pdo, 'VIEW_AUDIT_LOG', 'Administrator opened audit log.');
$logs = $pdo->query('SELECT audit_id, user_id, username, action, details, ip_address, created_at FROM admin_audit_logs ORDER BY audit_id DESC LIMIT 200')->fetchAll();
function pr14e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Audit Log - Student Hub</title><link rel="stylesheet" href="style.css">
<style>.p14{max-width:1200px;margin:2rem auto;padding:1rem}.card14{background:#fff;border:1px solid #d8dee8;border-radius:12px;padding:1rem;overflow-x:auto}.table14{width:100%;border-collapse:collapse}.table14 th,.table14 td{padding:.7rem;text-align:left;border-bottom:1px solid #e1e6ed;vertical-align:top}.table14 th{background:#f0f4fa}.muted14{color:#526071}@media(prefers-color-scheme:dark){.card14{background:#202733;color:#eef2f8}.table14 th{background:#303b4b}.table14 td,.table14 th{border-color:#485363}}</style></head><body>
<header class="external-style-22"><img class="external-style-23" src="student.png" alt="Student Hub logo"><h1 class="external-style-24">Student Hub</h1><nav class="external-style-25"><a class="external-style-6" href="home.html">Home</a><a class="external-style-6" href="admin_dashboard.php">Admin Dashboard</a><a class="external-style-6" href="pr14_audit.php" aria-current="page">Audit Log</a><a class="external-style-6" href="pr11.php">Students</a><a class="external-style-6" href="pr12.php">Events</a><a class="external-style-6" href="logout.php">Logout</a></nav></header>
<main class="p14"><h2>Administrator Audit Log</h2><p class="muted14">Latest 200 administrator actions. Entries are newest first.</p><section class="card14"><table class="table14"><thead><tr><th>ID</th><th>Date / Time</th><th>Administrator</th><th>Action</th><th>Details</th><th>IP Address</th></tr></thead><tbody>
<?php if (!$logs): ?><tr><td colspan="6">No audit entries have been recorded yet.</td></tr><?php else: foreach ($logs as $log): ?><tr><td><?= (int)$log['audit_id'] ?></td><td><?= pr14e($log['created_at']) ?></td><td><?= pr14e($log['username']) ?><?= $log['user_id'] ? ' (#'.(int)$log['user_id'].')' : '' ?></td><td><?= pr14e($log['action']) ?></td><td><?= pr14e($log['details']) ?></td><td><?= pr14e($log['ip_address'] ?? '') ?></td></tr><?php endforeach; endif; ?>
</tbody></table></section></main></body></html>
