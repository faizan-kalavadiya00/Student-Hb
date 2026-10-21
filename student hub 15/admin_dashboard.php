<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/audit.php';
sh_require_role($pdo, 'admin');
sh_start_session();
$user = $_SESSION['user'] ?? [];
sh_audit($pdo, 'VIEW_ADMIN_DASHBOARD', 'Administrator opened the dashboard.');

function pr14_table_exists(PDO $pdo, string $table): bool {
    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
        $stmt->execute([$table]);
        return (int)$stmt->fetchColumn() > 0;
    } catch (Throwable $e) { return false; }
}
function pr14_count(PDO $pdo, string $sql): int {
    try { return (int)$pdo->query($sql)->fetchColumn(); } catch (Throwable $e) { return 0; }
}
function pr14e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

$userCount = pr14_table_exists($pdo, 'users') ? pr14_count($pdo, 'SELECT COUNT(*) FROM users') : 0;
if (pr14_table_exists($pdo, 'managed_students')) {
    $studentCount = pr14_count($pdo, "SELECT COUNT(*) FROM managed_students WHERE status='active'");
} else {
    $studentCount = pr14_table_exists($pdo, 'students') ? pr14_count($pdo, 'SELECT COUNT(*) FROM students') : 0;
}
if (pr14_table_exists($pdo, 'pr12_events')) {
    $eventCount = pr14_count($pdo, 'SELECT COUNT(*) FROM pr12_events');
} else {
    $eventCount = pr14_table_exists($pdo, 'events') ? pr14_count($pdo, 'SELECT COUNT(*) FROM events') : 0;
}
$registrationCount = pr14_table_exists($pdo, 'registrations') ? pr14_count($pdo, 'SELECT COUNT(*) FROM registrations') : 0;
$latestUsers = [];
if (pr14_table_exists($pdo, 'users')) {
    try { $latestUsers = $pdo->query('SELECT user_id, full_name, username, email, created_at, role FROM users ORDER BY created_at DESC, user_id DESC LIMIT 8')->fetchAll(); } catch (Throwable $e) {}
}
$latestAudit = [];
if (pr14_table_exists($pdo, 'admin_audit_logs')) {
    try { $latestAudit = $pdo->query('SELECT username, action, details, created_at FROM admin_audit_logs ORDER BY audit_id DESC LIMIT 6')->fetchAll(); } catch (Throwable $e) {}
}
$role = (string)($user['role'] ?? 'student');
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Administrator Dashboard - Student Hub</title><link rel="stylesheet" href="style.css"><script src="script.js" defer></script>
<style>
.p14-wrap{max-width:1180px;margin:1.5rem auto;padding:1rem}.p14-welcome{padding:1.2rem 1.4rem;background:#eaf2ff;border-left:5px solid #174ea6;border-radius:10px;color:#17365d;margin-bottom:1.2rem}.p14-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:1rem;margin:1.2rem 0}.p14-stat{background:var(--card-bg,#fff);border:1px solid #d8dee8;border-radius:12px;padding:1.2rem;box-shadow:0 4px 14px #00000008}.p14-stat span{display:block;color:#526071;font-size:.95rem}.p14-stat strong{display:block;font-size:2rem;margin-top:.4rem}.p14-layout{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:1rem}.p14-card{background:var(--card-bg,#fff);border:1px solid #d8dee8;border-radius:12px;padding:1rem;overflow-x:auto}.p14-links{display:flex;gap:.7rem;flex-wrap:wrap}.p14-links a{display:inline-block;background:#174ea6;color:#fff;padding:.65rem .85rem;border-radius:7px;text-decoration:none;font-weight:600}.p14-table{width:100%;border-collapse:collapse}.p14-table th,.p14-table td{padding:.65rem;text-align:left;border-bottom:1px solid #dce1e8;vertical-align:top}.p14-table th{background:#f0f4fa}.p14-badge{display:inline-block;border-radius:999px;background:#e7f7ed;color:#14532d;padding:.2rem .55rem;font-size:.85rem}.p14-muted{color:#526071}@media(prefers-color-scheme:dark){.p14-welcome{background:#1b3150;color:#eaf2ff}.p14-card,.p14-stat{background:#202733;color:#eef2f8}.p14-table th{background:#303b4b}.p14-table td,.p14-table th{border-color:#485363}.p14-muted,.p14-stat span{color:#b9c4d3}}
</style></head><body>
<header class="external-style-22"><img class="external-style-23" src="student.png" alt="Student Hub logo"><h1 class="external-style-24">Student Hub</h1>
<nav class="external-style-25">
<a class="external-style-6" href="home.html">Home</a>
<a class="external-style-6" href="admin_dashboard.php" aria-current="page">Admin Dashboard</a>
<a class="external-style-6" href="dashboard_content.php">Timetable & Services</a>
<a class="external-style-6" href="pr11.php">Student Management</a>
<a class="external-style-6" href="pr12.php">Event Management</a>
<a class="external-style-6" href="pr13.php">API CRUD</a>
<a class="external-style-6" href="pr14_audit.php">Audit Log</a>
<a class="external-style-6" href="logout.php">Logout</a>
</nav></header>
<main class="p14-wrap">
<section class="p14-welcome"><h2>Welcome, <?= pr14e($user['full_name'] ?? 'Administrator') ?>!</h2><p>You are signed in with the <span class="p14-badge"><?= pr14e($role) ?></span> role. This dashboard is restricted to administrators.</p><p class="p14-muted">Statistics are queried from the database when this page loads.</p></section>
<h2>Dashboard Overview</h2>
<section class="p14-grid" aria-label="Live dashboard statistics">
<div class="p14-stat"><span>Total registered accounts</span><strong><?= $userCount ?></strong></div>
<div class="p14-stat"><span>Active student records</span><strong><?= $studentCount ?></strong></div>
<div class="p14-stat"><span>Total events</span><strong><?= $eventCount ?></strong></div>
<div class="p14-stat"><span>Event registrations</span><strong><?= $registrationCount ?></strong></div>
</section>
<section class="p14-card"><h2>Administrative Menu</h2><p class="p14-muted">Menu options are selected according to the signed-in user's role. Admin-only routes also verify the role on the server.</p><div class="p14-links">
<a href="pr11.php">Manage Students</a><a href="pr12.php">Manage Events</a><a href="pr13.php">JSON API CRUD</a><a href="pr14_audit.php">View Audit Log</a><a href="dashboard_content.php">Timetable & Services</a><a href="logout.php">Secure Logout</a>
</div></section>
<div class="p14-layout" style="margin-top:1rem">
<section class="p14-card"><h2>Latest Account Registrations</h2><div style="overflow-x:auto"><table class="p14-table"><thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Registered</th></tr></thead><tbody>
<?php if (!$latestUsers): ?><tr><td colspan="4">No user registrations found.</td></tr><?php else: foreach ($latestUsers as $r): ?><tr><td><?= pr14e($r['full_name']) ?></td><td><?= pr14e($r['username']) ?></td><td><?= pr14e($r['role'] ?? 'student') ?></td><td><?= pr14e($r['created_at']) ?></td></tr><?php endforeach; endif; ?>
</tbody></table></div></section>
<section class="p14-card"><h2>Recent Audit Activity</h2><div style="overflow-x:auto"><table class="p14-table"><thead><tr><th>Date / Time</th><th>Admin</th><th>Action</th></tr></thead><tbody>
<?php if (!$latestAudit): ?><tr><td colspan="3">No audit activity yet. Import studenthub_pr14.sql to enable the audit log.</td></tr><?php else: foreach ($latestAudit as $a): ?><tr><td><?= pr14e($a['created_at']) ?></td><td><?= pr14e($a['username']) ?></td><td><strong><?= pr14e($a['action']) ?></strong><br><span class="p14-muted"><?= pr14e($a['details']) ?></span></td></tr><?php endforeach; endif; ?>
</tbody></table></div><p><a href="pr14_audit.php">Open full audit log →</a></p></section>
</div>
</main><footer class="site-footer"><p><b>Student Hub</b></p><p>© 2026 Student Hub. All Rights Reserved.</p></footer></body></html>
