<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/audit.php';
sh_require_role($pdo, 'admin');
sh_start_session();


// Practical 12 uses its own table so the existing public events/registrations tables remain unchanged.
$pdo->exec("CREATE TABLE IF NOT EXISTS pr12_events (
    event_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(80) NOT NULL,
    venue VARCHAR(180) NOT NULL,
    event_date DATETIME NOT NULL,
    status ENUM('open','closed') NOT NULL DEFAULT 'open',
    poster_filename VARCHAR(80) NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pr12_events_date (event_date),
    INDEX idx_pr12_events_category (category),
    INDEX idx_pr12_events_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Add demonstration records only when this Practical 12 table is empty.
$seedCount = (int)$pdo->query('SELECT COUNT(*) FROM pr12_events')->fetchColumn();
if ($seedCount === 0) {
    $seed = $pdo->prepare('INSERT INTO pr12_events (title, description, category, venue, event_date, status) VALUES (?, ?, ?, ?, ?, ?)');
    $seedRows = [
        ['Student Orientation 2026', 'Welcome session with campus guidance and an introduction to Student Hub.', 'Academic', 'Main Auditorium', '2026-11-05 10:00:00', 'open'],
        ['Tech Innovation Meetup', 'Student presentations, technology talks, and project networking.', 'Technology', 'DEPSTAR Seminar Hall', '2026-11-12 14:00:00', 'open'],
        ['Annual Cultural Evening', 'Student performances, art, music, and campus culture.', 'Cultural', 'University Ground', '2026-11-20 17:00:00', 'closed']
    ];
    foreach ($seedRows as $row) $seed->execute($row);
}

if (empty($_SESSION['pr12_csrf'])) $_SESSION['pr12_csrf'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['pr12_csrf'];
const PR12_MAX_FILE = 2_097_152; // 2 MiB
const PR12_UPLOAD_DIR = __DIR__ . '/uploads/event_posters/';
const PR12_UPLOAD_URL = 'uploads/event_posters/';

function pr12_e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function pr12_flash(string $message, string $type='success'): never {
    header('Location: pr12.php?notice='.rawurlencode($message).'&type='.rawurlencode($type)); exit;
}
function pr12_remove_poster(?string $name): void {
    if ($name && preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $name)) {
        $path = PR12_UPLOAD_DIR . $name;
        if (is_file($path)) @unlink($path);
    }
}
function pr12_upload_poster(array $file): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) throw new RuntimeException('Poster upload failed. Please select the file again.');
    if (($file['size'] ?? 0) < 1 || $file['size'] > PR12_MAX_FILE) throw new RuntimeException('Poster must be smaller than or equal to 2 MB.');
    if (!is_uploaded_file($file['tmp_name'] ?? '')) throw new RuntimeException('Invalid uploaded file.');
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp'];
    if (!isset($allowed[$mime])) throw new RuntimeException('Invalid poster type. Upload JPG, PNG, or WEBP only.');
    $image = @getimagesize($file['tmp_name']);
    if ($image === false || !in_array($image[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
        throw new RuntimeException('The selected file is not a valid image.');
    }
    if (!is_dir(PR12_UPLOAD_DIR) && !mkdir(PR12_UPLOAD_DIR, 0750, true) && !is_dir(PR12_UPLOAD_DIR)) {
        throw new RuntimeException('Poster upload directory is not available.');
    }
    $name = bin2hex(random_bytes(16)).'.'.$allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], PR12_UPLOAD_DIR.$name)) throw new RuntimeException('Could not save the poster. Check folder permissions.');
    return $name;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) { http_response_code(403); exit('Security check failed. Refresh the page and try again.'); }
    $action = (string)($_POST['action'] ?? '');
    try {
        if ($action === 'save') {
            $id = filter_var($_POST['event_id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
            $title = trim((string)($_POST['title'] ?? ''));
            $description = trim((string)($_POST['description'] ?? ''));
            $category = trim((string)($_POST['category'] ?? ''));
            $venue = trim((string)($_POST['venue'] ?? ''));
            $eventDate = trim((string)($_POST['event_date'] ?? ''));
            $status = (string)($_POST['status'] ?? 'open');
            if ($title === '' || mb_strlen($title) > 150 || $description === '' || mb_strlen($description) > 5000 ||
                $category === '' || mb_strlen($category) > 80 || $venue === '' || mb_strlen($venue) > 180 ||
                !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $eventDate) ||
                !in_array($status, ['open','closed'], true)) {
                throw new RuntimeException('Please complete all fields with valid values.');
            }
            $date = DateTime::createFromFormat('Y-m-d\TH:i', $eventDate);
            if (!$date || $date->format('Y-m-d\TH:i') !== $eventDate) throw new RuntimeException('Please enter a valid event date and time.');
            $existing = null;
            if ($id > 0) {
                $q = $pdo->prepare('SELECT poster_filename FROM pr12_events WHERE event_id=?');
                $q->execute([$id]); $existing = $q->fetch();
                if (!$existing) throw new RuntimeException('Event not found.');
            }
            $newPoster = pr12_upload_poster($_FILES['poster'] ?? []);
            $poster = $newPoster ?? ($existing['poster_filename'] ?? null);
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE pr12_events SET title=?, description=?, category=?, venue=?, event_date=?, status=?, poster_filename=? WHERE event_id=?');
                $stmt->execute([$title,$description,$category,$venue,$date->format('Y-m-d H:i:s'),$status,$poster,$id]);
                if ($newPoster && !empty($existing['poster_filename'])) pr12_remove_poster($existing['poster_filename']);
                sh_audit($pdo, 'UPDATE_EVENT', 'Updated event ID '.$id.': '.$title.'.');
                pr12_flash('Event updated successfully.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO pr12_events (title,description,category,venue,event_date,status,poster_filename,created_by) VALUES (?,?,?,?,?,?,?,?)');
                $stmt->execute([$title,$description,$category,$venue,$date->format('Y-m-d H:i:s'),$status,$poster,(int)$_SESSION['user']['user_id']]);
                sh_audit($pdo, 'CREATE_EVENT', 'Created event: '.$title.'.');
                pr12_flash('Event added successfully.');
            }
        }
        if ($action === 'delete') {
            $id = filter_var($_POST['event_id'] ?? 0, FILTER_VALIDATE_INT);
            if (!$id) throw new RuntimeException('Invalid event selected.');
            $q = $pdo->prepare('SELECT poster_filename FROM pr12_events WHERE event_id=?');
            $q->execute([$id]); $event = $q->fetch();
            if (!$event) throw new RuntimeException('Event not found.');
            $del = $pdo->prepare('DELETE FROM pr12_events WHERE event_id=?'); $del->execute([$id]);
            sh_audit($pdo, 'DELETE_EVENT', 'Deleted event ID '.$id.' ('.$event['title'].').');
            pr12_remove_poster($event['poster_filename'] ?? null);
            pr12_flash('Event deleted successfully.');
        }
        throw new RuntimeException('Unknown action.');
    } catch (Throwable $e) {
        if ($e instanceof PDOException) {
            error_log('PR12 event CRUD error: '.$e->getMessage());
            $message = 'Database operation failed. Import studenthub_pr12.sql and try again.';
        } else $message = $e->getMessage();
        pr12_flash($message, 'error');
    }
}

$search = trim((string)($_GET['q'] ?? ''));
$statusFilter = (string)($_GET['status'] ?? 'all');
if (!in_array($statusFilter, ['all','open','closed'], true)) $statusFilter = 'all';
$categoryFilter = trim((string)($_GET['category'] ?? ''));
$conditions=[]; $params=[];
if ($search !== '') { $conditions[]='(title LIKE ? OR description LIKE ? OR venue LIKE ?)'; $t='%'.$search.'%'; array_push($params,$t,$t,$t); }
if ($statusFilter !== 'all') { $conditions[]='status=?'; $params[]=$statusFilter; }
if ($categoryFilter !== '') { $conditions[]='category=?'; $params[]=$categoryFilter; }
$where = $conditions ? ' WHERE '.implode(' AND ',$conditions) : '';
$events = [];
try {
    $stmt=$pdo->prepare('SELECT * FROM pr12_events'.$where.' ORDER BY event_date DESC, event_id DESC'); $stmt->execute($params); $events=$stmt->fetchAll();
    $categories=$pdo->query('SELECT DISTINCT category FROM pr12_events ORDER BY category')->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log('PR12 event listing error: '.$e->getMessage());
    $dbError = 'Event management database could not be initialized. Check that MySQL is running and the database user has table-creation permission.';
    $categories=[];
}
$edit=null;
if (isset($_GET['edit']) && empty($dbError)) {
    $stmt=$pdo->prepare('SELECT * FROM pr12_events WHERE event_id=?'); $stmt->execute([(int)$_GET['edit']]); $edit=$stmt->fetch() ?: null;
}
$notice=(string)($_GET['notice']??''); $noticeType=(string)($_GET['type']??'success');
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Practical 12 — Event Management | Student Hub</title><link rel="stylesheet" href="style.css">
<style>
.pr12-wrap{max-width:1180px;margin:2rem auto;padding:0 1rem;color:#172033}.pr12-head{display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap}.pr12-panel{background:#fff;border:1px solid #dbe2ea;border-radius:12px;padding:1.25rem;margin:1rem 0;box-shadow:0 4px 16px #0f172a0a}.pr12-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}.pr12-form label{display:flex;flex-direction:column;gap:.4rem;font-weight:600}.pr12-form input,.pr12-form select,.pr12-form textarea,.pr12-filter input,.pr12-filter select{width:100%;box-sizing:border-box;padding:.7rem;border:1px solid #cbd5e1;border-radius:7px;font:inherit}.pr12-form .wide{grid-column:1/-1}.pr12-form textarea{min-height:110px}.pr12-actions{display:flex;gap:.5rem;align-items:center;flex-wrap:wrap}.pr12-btn{display:inline-block;border:0;border-radius:7px;padding:.65rem 1rem;background:#174ea6;color:white;text-decoration:none;cursor:pointer;font:inherit}.pr12-btn.secondary{background:#475569}.pr12-btn.danger{background:#b42318}.pr12-notice{padding:.9rem 1rem;border-radius:8px;margin:1rem 0}.pr12-notice.success{background:#dcfce7;color:#166534}.pr12-notice.error{background:#fee2e2;color:#991b1b}.pr12-notice.info{background:#dbeafe;color:#1e40af}.pr12-filter{display:flex;gap:.7rem;flex-wrap:wrap;align-items:end}.pr12-filter label{min-width:160px;flex:1}.pr12-table-wrap{overflow-x:auto}.pr12-table{width:100%;border-collapse:collapse}.pr12-table th,.pr12-table td{text-align:left;padding:.75rem;border-bottom:1px solid #e2e8f0;vertical-align:top}.pr12-table th{background:#f1f5f9}.pr12-poster{width:76px;height:66px;object-fit:cover;border-radius:6px}.pr12-badge{display:inline-block;border-radius:20px;padding:.2rem .6rem;font-size:.85rem;background:#dcfce7;color:#166534}.pr12-badge.closed{background:#fee2e2;color:#991b1b}.pr12-muted{color:#64748b;font-size:.9rem}@media(max-width:650px){.pr12-form{grid-template-columns:1fr}.pr12-form .wide{grid-column:auto}.pr12-table th,.pr12-table td{padding:.5rem}}
</style></head><body>
<header class="external-style-22"><img class="external-style-23" src="student.png" alt="Student Hub logo"><h1 class="external-style-24">Student Hub</h1>
<nav class="external-style-25"><a class="external-style-6" href="home.html">Home</a><a class="external-style-6" href="dashboard.php">Dashboard</a><a class="external-style-6" href="admin_dashboard.php">Admin</a><a class="external-style-6" href="events.html">Public Events</a><a class="external-style-6" href="logout.php">Logout</a></nav></header>
<main class="pr12-wrap"><div class="pr12-head"><span></span><a class="pr12-btn secondary" href="admin_dashboard.php">← Admin dashboard</a></div>
<?php if ($notice !== ''): ?><div class="pr12-notice <?= pr12_e(in_array($noticeType,['success','error','info'],true)?$noticeType:'success') ?>" role="status"><?= pr12_e($notice) ?></div><?php endif; ?>
<?php if (!empty($dbError)): ?><div class="pr12-notice error"><?= pr12_e($dbError) ?></div><?php endif; ?>
<section class="pr12-panel"><h3><?= $edit ? 'Update event' : 'Add new event' ?></h3>
<form class="pr12-form" method="post" enctype="multipart/form-data">
<input type="hidden" name="csrf" value="<?= pr12_e($csrf) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="event_id" value="<?= (int)($edit['event_id']??0) ?>">
<label>Event title *<input name="title" maxlength="150" required value="<?= pr12_e($edit['title']??'') ?>"></label>
<label>Category *<input name="category" maxlength="80" required list="pr12-categories" value="<?= pr12_e($edit['category']??'') ?>"><datalist id="pr12-categories"><?php foreach(($categories??[]) as $cat): ?><option value="<?= pr12_e($cat) ?>"><?php endforeach; ?></datalist></label>
<label>Venue *<input name="venue" maxlength="180" required value="<?= pr12_e($edit['venue']??'') ?>"></label>
<label>Event date & time *<input type="datetime-local" name="event_date" required value="<?= isset($edit['event_date']) ? pr12_e(date('Y-m-d\TH:i',strtotime($edit['event_date']))) : '' ?>"></label>
<label>Status *<select name="status"><option value="open" <?= ($edit['status']??'open')==='open'?'selected':'' ?>>Open</option><option value="closed" <?= ($edit['status']??'')==='closed'?'selected':'' ?>>Closed</option></select></label>
<label>Poster image (JPG, PNG, WEBP; max 2 MB)<?= $edit ? ' — optional to keep current poster' : '' ?><input type="file" name="poster" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"><span class="pr12-muted">Server checks the actual MIME type and image data, not just the filename.</span></label>
<label class="wide">Description *<textarea name="description" maxlength="5000" required><?= pr12_e($edit['description']??'') ?></textarea></label>
<?php if (!empty($edit['poster_filename'])): ?><div class="wide"><p>Current poster:</p><img class="pr12-poster" src="<?= pr12_e(PR12_UPLOAD_URL.$edit['poster_filename']) ?>" alt="Current event poster"></div><?php endif; ?>
<div class="wide pr12-actions"><button class="pr12-btn" type="submit"><?= $edit ? 'Save changes' : 'Add event' ?></button><?php if($edit): ?><a class="pr12-btn secondary" href="pr12.php">Cancel edit</a><?php endif; ?></div>
</form></section>
<section class="pr12-panel"><h3>Manage events (<?= count($events) ?>)</h3><form class="pr12-filter" method="get">
<label>Search<input type="search" name="q" value="<?= pr12_e($search) ?>" placeholder="Title, venue or description"></label>
<label>Category<select name="category"><option value="">All categories</option><?php foreach(($categories??[]) as $cat): ?><option value="<?= pr12_e($cat) ?>" <?= $categoryFilter===$cat?'selected':'' ?>><?= pr12_e($cat) ?></option><?php endforeach; ?></select></label>
<label>Status<select name="status"><option value="all" <?= $statusFilter==='all'?'selected':'' ?>>All statuses</option><option value="open" <?= $statusFilter==='open'?'selected':'' ?>>Open</option><option value="closed" <?= $statusFilter==='closed'?'selected':'' ?>>Closed</option></select></label>
<button class="pr12-btn" type="submit">Filter</button><a class="pr12-btn secondary" href="pr12.php">Reset</a></form>
<div class="pr12-table-wrap"><table class="pr12-table"><thead><tr><th>Poster</th><th>Event details</th><th>Date & venue</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php if (!$events): ?><tr><td colspan="5">No events found. Add the first event using the form above.</td></tr><?php endif; ?>
<?php foreach($events as $event): ?><tr><td><?php if($event['poster_filename']): ?><img class="pr12-poster" src="<?= pr12_e(PR12_UPLOAD_URL.$event['poster_filename']) ?>" alt="Poster for <?= pr12_e($event['title']) ?>"><?php else: ?><span class="pr12-muted">No poster</span><?php endif; ?></td>
<td><strong><?= pr12_e($event['title']) ?></strong><br><span class="pr12-muted"><?= pr12_e($event['category']) ?></span><p><?= nl2br(pr12_e($event['description'])) ?></p></td><td><?= pr12_e(date('d M Y, h:i A',strtotime($event['event_date']))) ?><br><?= pr12_e($event['venue']) ?></td>
<td><span class="pr12-badge <?= $event['status']==='closed'?'closed':'' ?>"><?= pr12_e(ucfirst($event['status'])) ?></span></td><td><div class="pr12-actions"><a class="pr12-btn secondary" href="pr12.php?edit=<?= (int)$event['event_id'] ?>">Edit</a><form method="post" onsubmit="return confirm('Permanently delete this event and its poster?');"><input type="hidden" name="csrf" value="<?= pr12_e($csrf) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="event_id" value="<?= (int)$event['event_id'] ?>"><button class="pr12-btn danger" type="submit">Delete</button></form></div></td></tr><?php endforeach; ?>
</tbody></table></div></section>
<p class="pr12-muted">Admin-only page. All database changes use prepared statements and POST requests protected by a CSRF token.</p></main>
<footer class="site-footer"><p><b>Student Hub</b></p><p>© 2026 Student Hub. All Rights Reserved.</p></footer></body></html>
