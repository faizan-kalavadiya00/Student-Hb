<?php
declare(strict_types=1);

/* Practical 13: JSON API for asynchronous Student Hub CRUD. */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/audit.php';

function api_json(int $status, array $payload): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function api_body(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') return [];
    $data = json_decode($raw, true);
    if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) api_json(400, ['success'=>false,'error'=>'Request body must be valid JSON.']);
    return $data;
}
function api_text(array $data, string $key, int $max): string {
    $value = trim((string)($data[$key] ?? ''));
    if ($value === '' || mb_strlen($value) > $max) api_json(422, ['success'=>false,'error'=>"The {$key} field is required and must be at most {$max} characters."]);
    return $value;
}
try {
    sh_start_session();
    if (!isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin') {
        api_json(isset($_SESSION['user']) ? 403 : 401, ['success'=>false,'error'=>isset($_SESSION['user']) ? 'Administrator access is required.' : 'Please sign in as an administrator.']);
    }
    if (empty($_SESSION['pr13_csrf'])) $_SESSION['pr13_csrf'] = bin2hex(random_bytes(32));
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $resource = (string)($_GET['resource'] ?? 'students');
    if (!in_array($resource, ['students','events'], true)) api_json(400, ['success'=>false,'error'=>'Resource must be students or events.']);
    $table = $resource === 'students' ? 'managed_students' : 'pr12_events';

    if ($method === 'GET') {
        $search = trim((string)($_GET['search'] ?? ''));
        if (mb_strlen($search) > 100) api_json(422, ['success'=>false,'error'=>'Search must be 100 characters or fewer.']);
        if ($resource === 'students') {
            $sql = "SELECT student_id AS id,enrollment_no,full_name,email,course,study_year,status,created_at FROM managed_students WHERE status='active'";
            $params = [];
            if ($search !== '') { $sql .= ' AND (enrollment_no LIKE ? OR full_name LIKE ? OR email LIKE ? OR course LIKE ?)'; $like='%'.$search.'%'; $params=[$like,$like,$like,$like]; }
            $sql .= ' ORDER BY student_id DESC LIMIT 200';
        } else {
            $sql = 'SELECT event_id AS id,title,description,category,venue,event_date,status,poster_filename,created_at FROM pr12_events';
            $params = [];
            if ($search !== '') { $sql .= ' WHERE title LIKE ? OR category LIKE ? OR venue LIKE ? OR description LIKE ?'; $like='%'.$search.'%'; $params=[$like,$like,$like,$like]; }
            $sql .= ' ORDER BY event_date DESC LIMIT 200';
        }
        $stmt=$pdo->prepare($sql); $stmt->execute($params);
        api_json(200, ['success'=>true,'resource'=>$resource,'csrf'=>$_SESSION['pr13_csrf'],'data'=>$stmt->fetchAll()]);
    }

    if (!in_array($method, ['POST','PUT','PATCH','DELETE'], true)) {
        header('Allow: GET, POST, PUT, PATCH, DELETE');
        api_json(405, ['success'=>false,'error'=>'HTTP method not supported.']);
    }
    $token = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if ($token === '' || !hash_equals($_SESSION['pr13_csrf'], $token)) api_json(403, ['success'=>false,'error'=>'Security token invalid or expired. Refresh the page and try again.']);
    $data = api_body();
    $action = (string)($data['action'] ?? ($method === 'DELETE' ? 'delete' : ($method === 'PUT' || $method === 'PATCH' ? 'update' : 'create')));
    $id = filter_var($data['id'] ?? $_GET['id'] ?? null, FILTER_VALIDATE_INT);
    if ($action === 'create' && $method === 'POST') {
        if ($resource === 'students') {
            $enrollment=api_text($data,'enrollment_no',20); $name=api_text($data,'full_name',100);
            $email=api_text($data,'email',150); $course=api_text($data,'course',100);
            if (!filter_var($email,FILTER_VALIDATE_EMAIL)) api_json(422,['success'=>false,'error'=>'Enter a valid email address.']);
            $year=filter_var($data['study_year']??null,FILTER_VALIDATE_INT);
            if (!$year || $year<1 || $year>6) api_json(422,['success'=>false,'error'=>'Study year must be between 1 and 6.']);
            $stmt=$pdo->prepare("INSERT INTO managed_students(enrollment_no,full_name,email,course,study_year,status) VALUES(?,?,?,?,?,'active')");
            $stmt->execute([$enrollment,$name,$email,$course,$year]);
            sh_audit($pdo, 'API_CREATE_STUDENT', 'Created student '.$enrollment.' through Practical 13 API.');
            api_json(201,['success'=>true,'message'=>'Student created successfully.','id'=>(int)$pdo->lastInsertId()]);
        }
        $title=api_text($data,'title',150); $description=trim((string)($data['description']??'')); $category=api_text($data,'category',80); $venue=api_text($data,'venue',180);
        if (mb_strlen($description)>5000) api_json(422,['success'=>false,'error'=>'Description must be 5000 characters or fewer.']);
        $date=trim((string)($data['event_date']??'')); $dt=DateTime::createFromFormat('Y-m-d\TH:i', $date) ?: DateTime::createFromFormat('Y-m-d H:i:s',$date);
        if (!$dt || $dt->format('Y-m-d H:i:s') === false) api_json(422,['success'=>false,'error'=>'Enter a valid event date and time.']);
        $status=(string)($data['status']??'open'); if(!in_array($status,['open','closed'],true)) api_json(422,['success'=>false,'error'=>'Status must be open or closed.']);
        $stmt=$pdo->prepare('INSERT INTO pr12_events(title,description,category,venue,event_date,status) VALUES(?,?,?,?,?,?)');
        $stmt->execute([$title,$description,$category,$venue,$dt->format('Y-m-d H:i:s'),$status]);
        sh_audit($pdo, 'API_CREATE_EVENT', 'Created event '.$title.' through Practical 13 API.');
        api_json(201,['success'=>true,'message'=>'Event created successfully.','id'=>(int)$pdo->lastInsertId()]);
    }
    if (($action === 'update' && in_array($method,['POST','PUT','PATCH'],true)) || ($action === 'delete' && in_array($method,['POST','DELETE'],true))) {
        if (!$id || $id<1) api_json(422,['success'=>false,'error'=>'A valid record id is required.']);
        if ($action === 'delete') {
            if ($resource==='students') {
                $stmt=$pdo->prepare("UPDATE managed_students SET status='deleted' WHERE student_id=? AND status='active'"); $stmt->execute([$id]);
            } else {
                $stmt=$pdo->prepare('SELECT poster_filename FROM pr12_events WHERE event_id=?'); $stmt->execute([$id]); $poster=$stmt->fetchColumn();
                $del=$pdo->prepare('DELETE FROM pr12_events WHERE event_id=?'); $del->execute([$id]);
                if ($poster && is_string($poster) && preg_match('/^[a-f0-9]{32}\.(jpg|jpeg|png|webp)$/i',$poster)) { $path=__DIR__.'/uploads/event_posters/'.$poster; if(is_file($path)) @unlink($path); }
                $stmt=$del;
            }
            if (!$stmt->rowCount()) api_json(404,['success'=>false,'error'=>'Record not found or already deleted.']);
            sh_audit($pdo, 'API_DELETE_'.strtoupper(rtrim($resource,'s')), 'Deleted '.$resource.' record ID '.$id.' through Practical 13 API.');
            api_json(200,['success'=>true,'message'=>ucfirst($resource).' record deleted successfully.']);
        }
        if ($resource==='students') {
            $enrollment=api_text($data,'enrollment_no',20); $name=api_text($data,'full_name',100); $email=api_text($data,'email',150); $course=api_text($data,'course',100);
            if (!filter_var($email,FILTER_VALIDATE_EMAIL)) api_json(422,['success'=>false,'error'=>'Enter a valid email address.']);
            $year=filter_var($data['study_year']??null,FILTER_VALIDATE_INT); if(!$year||$year<1||$year>6) api_json(422,['success'=>false,'error'=>'Study year must be between 1 and 6.']);
            $stmt=$pdo->prepare("UPDATE managed_students SET enrollment_no=?,full_name=?,email=?,course=?,study_year=? WHERE student_id=? AND status='active'"); $stmt->execute([$enrollment,$name,$email,$course,$year,$id]);
        } else {
            $title=api_text($data,'title',150); $description=trim((string)($data['description']??'')); $category=api_text($data,'category',80); $venue=api_text($data,'venue',180);
            if(mb_strlen($description)>5000) api_json(422,['success'=>false,'error'=>'Description must be 5000 characters or fewer.']);
            $date=trim((string)($data['event_date']??'')); $dt=DateTime::createFromFormat('Y-m-d\TH:i',$date) ?: DateTime::createFromFormat('Y-m-d H:i:s',$date);
            if(!$dt) api_json(422,['success'=>false,'error'=>'Enter a valid event date and time.']);
            $status=(string)($data['status']??'open'); if(!in_array($status,['open','closed'],true)) api_json(422,['success'=>false,'error'=>'Status must be open or closed.']);
            $stmt=$pdo->prepare('UPDATE pr12_events SET title=?,description=?,category=?,venue=?,event_date=?,status=? WHERE event_id=?'); $stmt->execute([$title,$description,$category,$venue,$dt->format('Y-m-d H:i:s'),$status,$id]);
        }
        if ($stmt->rowCount()===0) {
            $check=$pdo->prepare($resource==='students'?"SELECT 1 FROM managed_students WHERE student_id=? AND status='active'":"SELECT 1 FROM pr12_events WHERE event_id=?");$check->execute([$id]);
            if(!$check->fetchColumn()) api_json(404,['success'=>false,'error'=>'Record not found.']);
        }
        sh_audit($pdo, 'API_UPDATE_'.strtoupper(rtrim($resource,'s')), 'Updated '.$resource.' record ID '.$id.' through Practical 13 API.');
        api_json(200,['success'=>true,'message'=>ucfirst($resource).' record updated successfully.']);
    }
    api_json(400,['success'=>false,'error'=>'Action must be create, update, or delete.']);
} catch (PDOException $e) {
    error_log('Student Hub PR13 database error: '.$e->getMessage());
    if ((string)$e->getCode()==='23000') api_json(409,['success'=>false,'error'=>'A record with this enrollment number or email already exists.']);
    api_json(500,['success'=>false,'error'=>'A database error occurred. Check the database setup and try again.']);
} catch (Throwable $e) {
    error_log('Student Hub PR13 API error: '.$e->getMessage());
    api_json(500,['success'=>false,'error'=>'An unexpected server error occurred.']);
}
