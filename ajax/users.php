<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['admin']);
header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

function jsonOut($success, $message, $data=null) {
    echo json_encode(['success'=>$success,'message'=>$message,'data'=>$data]);
    exit;
}

if ($action === 'list') {
    $pdo  = getDB();
    $stmt = $pdo->query("
        SELECT u.*, d.name as department_name
        FROM users u LEFT JOIN departments d ON u.department_id = d.id
        ORDER BY u.name
    ");
    jsonOut(true, 'OK', $stmt->fetchAll());
}

if ($action === 'save') {
    $id       = (int)($_POST['id'] ?? 0);
    $name     = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $role     = $_POST['role'] ?? '';
    $deptId   = ($role === 'department_incharge' && !empty($_POST['department_id'])) ? (int)$_POST['department_id'] : null;
    $status   = $_POST['status'] === 'inactive' ? 'inactive' : 'active';
    $password = $_POST['password'] ?? '';
    $validRoles = ['admin','quality_officer','department_incharge'];

    if (empty($name) || empty($username) || !in_array($role, $validRoles)) {
        jsonOut(false, 'Name, username, and valid role are required.');
    }

    $pdo = getDB();
    try {
        if ($id > 0) {
            // Update
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET name=?,username=?,email=?,role=?,department_id=?,status=?,password=? WHERE id=?");
                $stmt->execute([$name,$username,$email,$role,$deptId,$status,$hash,$id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name=?,username=?,email=?,role=?,department_id=?,status=? WHERE id=?");
                $stmt->execute([$name,$username,$email,$role,$deptId,$status,$id]);
            }
            logAudit('update_user','users',$id,null,json_encode(compact('name','username','role','status')));
            jsonOut(true, 'User updated successfully.');
        } else {
            if (empty($password)) jsonOut(false, 'Password is required for new users.');
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (name,username,email,password,role,department_id,status) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute([$name,$username,$email,$hash,$role,$deptId,$status]);
            $newId = $pdo->lastInsertId();
            logAudit('create_user','users',$newId,null,json_encode(compact('name','username','role','status')));
            jsonOut(true, 'User created successfully.');
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) jsonOut(false, 'Username already exists.');
        jsonOut(false, 'Database error: '.$e->getMessage());
    }
}

if ($action === 'toggle_status') {
    $id = (int)($_POST['id'] ?? 0);
    $currentUser = getCurrentUser();
    if ($id === (int)$currentUser['id']) jsonOut(false, 'You cannot deactivate yourself.');
    if (!$id) jsonOut(false, 'Invalid ID.');
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT status FROM users WHERE id=?");
    $stmt->execute([$id]);
    $u = $stmt->fetch();
    if (!$u) jsonOut(false, 'User not found.');
    $newStatus = $u['status'] === 'active' ? 'inactive' : 'active';
    $pdo->prepare("UPDATE users SET status=? WHERE id=?")->execute([$newStatus,$id]);
    logAudit('toggle_user_status','users',$id,$u['status'],$newStatus);
    jsonOut(true, 'Status changed to '.$newStatus.'.');
}

jsonOut(false, 'Invalid action.');
