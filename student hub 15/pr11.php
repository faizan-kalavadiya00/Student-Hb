<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/audit.php';
sh_require_role($pdo, 'admin');
sh_start_session();
if (!isset($_SESSION['student_crud_csrf'])) $_SESSION['student_crud_csrf'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['student_crud_csrf'];
function out($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function redirect_with(string $message, string $type='success'): never {
    header('Location: pr11.php?notice=' . rawurlencode($message) . '&type=' . rawurlencode($type)); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted = (string)($_POST['csrf'] ?? '');
    if (!hash_equals($csrf, $posted)) { http_response_code(403); exit('Security check failed. Refresh and try again.'); }
    $action = (string)($_POST['action'] ?? '');
    try {
        if ($action === 'save') {
            $id = filter_var($_POST['student_id'] ?? '', FILTER_VALIDATE_INT) ?: 0;
            $enrollment = trim((string)($_POST['enrollment_no'] ?? ''));
            $name = trim((string)($_POST['full_name'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $course = trim((string)($_POST['course'] ?? ''));
            $year = filter_var($_POST['study_year'] ?? '', FILTER_VALIDATE_INT);
            if ($enrollment === '' || $name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $course === '' || !$year || $year < 1 || $year > 6) {
                redirect_with('Enter valid values in all required fields.', 'error');
            }
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE managed_students SET enrollment_no=?, full_name=?, email=?, course=?, study_year=? WHERE student_id=? AND status="active"');
                $stmt->execute([$enrollment,$name,$email,$course,$year,$id]);
                if ($stmt->rowCount()) sh_audit($pdo, 'UPDATE_STUDENT', 'Updated managed student ID '.$id.' ('.$enrollment.').');
                redirect_with($stmt->rowCount() ? 'Student record updated successfully.' : 'No changes made, or the record is inactive.', 'info');
            }
            $stmt = $pdo->prepare('INSERT INTO managed_students (enrollment_no,full_name,email,course,study_year,status) VALUES (?,?,?,?,?,"active")');
            $stmt->execute([$enrollment,$name,$email,$course,$year]);
            sh_audit($pdo, 'CREATE_STUDENT', 'Created managed student '.$enrollment.'.');
            redirect_with('Student record added successfully.');
        }
        if ($action === 'delete' || $action === 'restore') {
            $id = filter_var($_POST['student_id'] ?? '', FILTER_VALIDATE_INT);
            if (!$id) redirect_with('Invalid student record.', 'error');
            $status = $action === 'delete' ? 'deleted' : 'active';
            $stmt = $pdo->prepare('UPDATE managed_students SET status=? WHERE student_id=?');
            $stmt->execute([$status,$id]);
            sh_audit($pdo, $action === 'delete' ? 'DELETE_STUDENT' : 'RESTORE_STUDENT', 'Student ID '.$id.' status set to '.$status.'.');
            redirect_with($action === 'delete' ? 'Student moved to Deleted records.' : 'Student record restored successfully.');
        }
    } catch (PDOException $e) {
        error_log('PR11 student CRUD error: '.$e->getMessage());
        $msg = $e->getCode() === '23000' ? 'Enrollment number or email already exists.' : 'Database operation failed. Check the database setup and try again.';
        redirect_with($msg, 'error');
    }
}
$search = trim((string)($_GET['q'] ?? ''));
$statusFilter = (string)($_GET['status'] ?? 'active');
if (!in_array($statusFilter, ['active','deleted','all'], true)) $statusFilter='active';
$page = max(1, (int)($_GET['page'] ?? 1)); $perPage=10; $offset=($page-1)*$perPage;
$where=[]; $params=[];
if ($search !== '') { $where[]='(full_name LIKE ? OR email LIKE ? OR course LIKE ? OR enrollment_no LIKE ?)'; $term='%'.$search.'%'; array_push($params,$term,$term,$term,$term); }
if ($statusFilter !== 'all') { $where[]='status=?'; $params[]=$statusFilter; }
$whereSql = $where ? ' WHERE '.implode(' AND ',$where) : '';
$countStmt=$pdo->prepare('SELECT COUNT(*) FROM managed_students'.$whereSql); $countStmt->execute($params); $total=(int)$countStmt->fetchColumn();
$totalPages=max(1,(int)ceil($total/$perPage)); if($page>$totalPages){$page=$totalPages;$offset=($page-1)*$perPage;}
$list=$pdo->prepare('SELECT * FROM managed_students'.$whereSql.' ORDER BY student_id DESC LIMIT ? OFFSET ?');
$i=1; foreach($params as $value) $list->bindValue($i++,$value,PDO::PARAM_STR); $list->bindValue($i++,$perPage,PDO::PARAM_INT); $list->bindValue($i,$offset,PDO::PARAM_INT); $list->execute(); $students=$list->fetchAll();
$edit=null;
if(isset($_GET['edit'])) { $stmt=$pdo->prepare('SELECT * FROM managed_students WHERE student_id=? AND status="active"');$stmt->execute([(int)$_GET['edit']]);$edit=$stmt->fetch() ?: null; }
$notice=(string)($_GET['notice']??'');$noticeType=(string)($_GET['type']??'success');
if(isset($_GET['export']) && $_GET['export']==='csv') {
    header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="student-hub-students.csv"');
    $export=$pdo->prepare('SELECT enrollment_no,full_name,email,course,study_year,status,created_at FROM managed_students'.$whereSql.' ORDER BY full_name');$export->execute($params);
    $fp=fopen('php://output','w');fputcsv($fp,['Enrollment No','Full Name','Email','Course','Study Year','Status','Created At']);
    while($row=$export->fetch(PDO::FETCH_NUM)) fputcsv($fp,$row);fclose($fp);exit;
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>PR11 Student Management - Student Hub</title><link rel="stylesheet" href="style.css">
<style>
.pr11{max-width:1180px;margin:2rem auto;padding:0 1rem;color:#172033}.pr11 a{color:#174ea6}.pr11-card{background:#fff;border:1px solid #dce3ee;border-radius:12px;padding:1.25rem;margin:1rem 0;box-shadow:0 4px 16px #1720330c}.pr11-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:1rem}.pr11 label{display:block;font-weight:600}.pr11 input,.pr11 select{display:block;width:100%;box-sizing:border-box;margin-top:.35rem;padding:.7rem;border:1px solid #b8c4d6;border-radius:7px}.pr11 button,.pr11 .button{border:0;border-radius:7px;padding:.65rem 1rem;background:#174ea6;color:white;cursor:pointer;text-decoration:none;display:inline-block}.pr11 .danger{background:#a52834}.pr11 .muted{background:#596579}.pr11 .notice{padding:.85rem;border-radius:8px;background:#e4f7e9;margin:1rem 0}.pr11 .error{background:#ffe8e8}.pr11 .info{background:#e8f1ff}.pr11 table{width:100%;border-collapse:collapse}.pr11 th,.pr11 td{padding:.7rem;text-align:left;border-bottom:1px solid #e2e7ef;vertical-align:top}.pr11 .table-wrap{overflow-x:auto}.pr11 .actions{display:flex;gap:.4rem;flex-wrap:wrap}.pr11 form.inline{display:inline}.pr11 .pagination{display:flex;gap:.8rem;align-items:center;justify-content:center;margin:1rem}.pr11 .subtle{color:#5a6678;font-size:.93rem}@media(max-width:600px){.pr11-card{padding:.85rem}}
</style></head><body>
<header class="external-style-22"><img class="external-style-23" src="student.png" alt="Student Hub logo"><h1 class="external-style-24">Student Hub</h1><nav class="external-style-25"><a class="external-style-6" href="home.html">Home</a><a class="external-style-6" href="dashboard.php">Dashboard</a><a class="external-style-6" href="students.html">Student Profiles</a><a class="external-style-6" href="pr11.php">Student Management</a><a class="external-style-6" href="logout.php">Logout</a></nav></header>
<main class="pr11"><h1>Practical 11: Student Management CRUD</h1><p class="subtle">Add, view, edit, search, filter, export, and safely soft-delete student records using PHP, MySQL, and prepared statements.</p>
<?php if($notice!==''): ?><div class="notice <?=out($noticeType)?>" role="status"><?=out($notice)?></div><?php endif; ?>
<section class="pr11-card"><h2><?= $edit ? 'Edit Student' : 'Add New Student' ?></h2><form method="post" action="pr11.php"><input type="hidden" name="csrf" value="<?=out($csrf)?>"><input type="hidden" name="action" value="save"><input type="hidden" name="student_id" value="<?=out($edit['student_id']??'')?>"><div class="pr11-grid">
<label>Enrollment No *<input name="enrollment_no" maxlength="20" required value="<?=out($edit['enrollment_no']??'')?>"></label>
<label>Full Name *<input name="full_name" maxlength="100" required value="<?=out($edit['full_name']??'')?>"></label>
<label>Email *<input type="email" name="email" maxlength="150" required value="<?=out($edit['email']??'')?>"></label>
<label>Course / Department *<input name="course" maxlength="100" required value="<?=out($edit['course']??'')?>"></label>
<label>Study Year *<select name="study_year" required><?php for($y=1;$y<=6;$y++): ?><option value="<?=$y?>" <?=((int)($edit['study_year']??1)===$y?'selected':'')?>>Year <?=$y?></option><?php endfor; ?></select></label>
</div><p><button type="submit"><?= $edit ? 'Update Student' : 'Add Student' ?></button> <?php if($edit): ?><a class="button muted" href="pr11.php">Cancel edit</a><?php endif; ?></p></form></section>
<section class="pr11-card"><h2>Find Student Records</h2><form method="get"><div class="pr11-grid"><label>Search name, email, course or enrollment<input type="search" name="q" value="<?=out($search)?>" placeholder="e.g. Faizan or Computer Science"></label><label>Record status<select name="status"><option value="active" <?=$statusFilter==='active'?'selected':''?>>Active records</option><option value="deleted" <?=$statusFilter==='deleted'?'selected':''?>>Deleted records</option><option value="all" <?=$statusFilter==='all'?'selected':''?>>All records</option></select></label></div><p><button type="submit">Search / Filter</button> <a class="button muted" href="pr11.php">Clear filters</a> <a class="button" href="<?=out('pr11.php?'.http_build_query(array_filter(['q'=>$search,'status'=>$statusFilter,'export'=>'csv'])))?>">Export CSV</a></p></form><p class="subtle"><?= $total ?> matching record(s).</p>
<div class="table-wrap"><table><thead><tr><th>ID</th><th>Enrollment</th><th>Name / Email</th><th>Course</th><th>Year</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php if(!$students): ?><tr><td colspan="7">No student records match your search.</td></tr><?php endif; ?>
<?php foreach($students as $s): ?><tr><td><?=out($s['student_id'])?></td><td><?=out($s['enrollment_no'])?></td><td><strong><?=out($s['full_name'])?></strong><br><?=out($s['email'])?></td><td><?=out($s['course'])?></td><td><?=out($s['study_year'])?></td><td><?=out(ucfirst($s['status']))?></td><td><div class="actions">
<?php if($s['status']==='active'): ?><a class="button" href="pr11.php?edit=<?=(int)$s['student_id']?>">Edit</a><form class="inline" method="post" onsubmit="return confirm('Move this student to Deleted records? You can restore it later.');"><input type="hidden" name="csrf" value="<?=out($csrf)?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="student_id" value="<?=(int)$s['student_id']?>"><button class="danger" type="submit">Delete</button></form>
<?php else: ?><form class="inline" method="post"><input type="hidden" name="csrf" value="<?=out($csrf)?>"><input type="hidden" name="action" value="restore"><input type="hidden" name="student_id" value="<?=(int)$s['student_id']?>"><button type="submit">Restore</button></form><?php endif; ?></div></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php if($totalPages>1): ?><div class="pagination"><?php if($page>1): ?><a class="button muted" href="?<?=out(http_build_query(['q'=>$search,'status'=>$statusFilter,'page'=>$page-1]))?>">Previous</a><?php endif; ?><span>Page <?=$page?> of <?=$totalPages?></span><?php if($page<$totalPages): ?><a class="button muted" href="?<?=out(http_build_query(['q'=>$search,'status'=>$statusFilter,'page'=>$page+1]))?>">Next</a><?php endif; ?></div><?php endif; ?>
</section></main><footer class="site-footer"><p><b>Student Hub</b></p><p>© 2026 Student Hub. All Rights Reserved.</p></footer></body></html>
