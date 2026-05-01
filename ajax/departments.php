<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

function jsonOut($success, $message, $data=null) {
    echo json_encode(['success'=>$success,'message'=>$message,'data'=>$data]);
    exit;
}

if ($action === 'list') {
    $pdo  = getDB();
    $stmt = $pdo->query("SELECT * FROM departments ORDER BY name");
    jsonOut(true, 'OK', $stmt->fetchAll());
}

if ($action === 'save') {
    requireRole(['admin']);
    $id          = (int)($_POST['id'] ?? 0);
    $name        = trim($_POST['name'] ?? '');
    $code        = strtoupper(trim($_POST['code'] ?? ''));
    $incharge    = trim($_POST['incharge_name'] ?? '');
    $contact     = trim($_POST['contact'] ?? '');
    $status      = $_POST['status'] === 'inactive' ? 'inactive' : 'active';

    if (empty($name) || empty($code)) jsonOut(false, 'Name and code are required.');

    $pdo = getDB();
    try {
        if ($id > 0) {
            $old = $pdo->prepare("SELECT * FROM departments WHERE id=?"); $old->execute([$id]);
            $oldData = json_encode($old->fetch());
            $stmt = $pdo->prepare("UPDATE departments SET name=?,code=?,incharge_name=?,contact=?,status=? WHERE id=?");
            $stmt->execute([$name,$code,$incharge,$contact,$status,$id]);
            logAudit('update_department','departments',$id,$oldData,json_encode(compact('name','code','incharge','contact','status')));
            jsonOut(true, 'Department updated successfully.');
        } else {
            $stmt = $pdo->prepare("INSERT INTO departments (name,code,incharge_name,contact,status) VALUES (?,?,?,?,?)");
            $stmt->execute([$name,$code,$incharge,$contact,$status]);
            $newId = $pdo->lastInsertId();
            logAudit('create_department','departments',$newId,null,json_encode(compact('name','code','incharge','contact','status')));
            jsonOut(true, 'Department created successfully.');
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) jsonOut(false, 'Department code already exists.');
        jsonOut(false, 'Database error: '.$e->getMessage());
    }
}

if ($action === 'toggle_status') {
    requireRole(['admin']);
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) jsonOut(false, 'Invalid ID.');
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT status FROM departments WHERE id=?");
    $stmt->execute([$id]);
    $dep = $stmt->fetch();
    if (!$dep) jsonOut(false, 'Department not found.');
    $newStatus = $dep['status'] === 'active' ? 'inactive' : 'active';
    $pdo->prepare("UPDATE departments SET status=? WHERE id=?")->execute([$newStatus, $id]);
    logAudit('toggle_dept_status','departments',$id,$dep['status'],$newStatus);
    jsonOut(true, 'Status changed to '.$newStatus.'.');
}

jsonOut(false, 'Invalid action.');
