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
    $stmt = $pdo->query("SELECT * FROM indicators ORDER BY indicator_code");
    jsonOut(true, 'OK', $stmt->fetchAll());
}

if ($action === 'save') {
    requireRole(['admin']);
    $id      = (int)($_POST['id'] ?? 0);
    $code    = strtoupper(trim($_POST['indicator_code'] ?? ''));
    $name    = trim($_POST['name'] ?? '');
    $desc    = trim($_POST['description'] ?? '');
    $numDesc = trim($_POST['numerator_description'] ?? '');
    $denDesc = trim($_POST['denominator_description'] ?? '');
    $unit    = $_POST['unit'] ?? 'percentage';
    $cat     = $_POST['category'] ?? 'general';
    $bench   = trim($_POST['benchmark'] ?? '');
    $status  = $_POST['status'] === 'inactive' ? 'inactive' : 'active';
    $validUnits = ['percentage','per_1000','ratio','minutes','number'];
    $validCats  = ['general','department_specific'];
    if (empty($code) || empty($name)) jsonOut(false, 'Code and name are required.');
    if (!in_array($unit, $validUnits)) jsonOut(false, 'Invalid unit.');
    if (!in_array($cat, $validCats))  jsonOut(false, 'Invalid category.');

    $pdo = getDB();
    try {
        if ($id > 0) {
            $old = $pdo->prepare("SELECT * FROM indicators WHERE id=?"); $old->execute([$id]);
            $oldData = json_encode($old->fetch());
            $stmt = $pdo->prepare("UPDATE indicators SET indicator_code=?,name=?,description=?,numerator_description=?,denominator_description=?,unit=?,category=?,benchmark=?,status=? WHERE id=?");
            $stmt->execute([$code,$name,$desc,$numDesc,$denDesc,$unit,$cat,$bench,$status,$id]);
            logAudit('update_indicator','indicators',$id,$oldData,json_encode(compact('code','name','unit','cat','status')));
            jsonOut(true, 'Indicator updated successfully.');
        } else {
            $stmt = $pdo->prepare("INSERT INTO indicators (indicator_code,name,description,numerator_description,denominator_description,unit,category,benchmark,status) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$code,$name,$desc,$numDesc,$denDesc,$unit,$cat,$bench,$status]);
            $newId = $pdo->lastInsertId();
            logAudit('create_indicator','indicators',$newId,null,json_encode(compact('code','name','unit','cat','status')));
            jsonOut(true, 'Indicator created successfully.');
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) jsonOut(false, 'Indicator code already exists.');
        jsonOut(false, 'Database error: '.$e->getMessage());
    }
}

if ($action === 'toggle_status') {
    requireRole(['admin']);
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) jsonOut(false, 'Invalid ID.');
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT status FROM indicators WHERE id=?");
    $stmt->execute([$id]);
    $ind = $stmt->fetch();
    if (!$ind) jsonOut(false, 'Indicator not found.');
    $newStatus = $ind['status'] === 'active' ? 'inactive' : 'active';
    $pdo->prepare("UPDATE indicators SET status=? WHERE id=?")->execute([$newStatus,$id]);
    logAudit('toggle_indicator_status','indicators',$id,$ind['status'],$newStatus);
    jsonOut(true, 'Status changed to '.$newStatus.'.');
}

jsonOut(false, 'Invalid action.');
