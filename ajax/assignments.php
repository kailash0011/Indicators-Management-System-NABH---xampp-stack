<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['admin','quality_officer']);
header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

function jsonOut($success, $message, $data=null) {
    echo json_encode(['success'=>$success,'message'=>$message,'data'=>$data]);
    exit;
}

if ($action === 'get_dept_indicators') {
    $deptId = (int)($_GET['dept_id'] ?? 0);
    if (!$deptId) jsonOut(false, 'Department ID required.');
    jsonOut(true, 'OK', getDepartmentIndicators($deptId));
}

if ($action === 'get_unassigned') {
    $deptId = (int)($_GET['dept_id'] ?? 0);
    if (!$deptId) jsonOut(false, 'Department ID required.');
    jsonOut(true, 'OK', getUnassignedIndicators($deptId));
}

if ($action === 'assign') {
    requireRole(['admin','quality_officer']);
    $deptId      = (int)($_POST['dept_id'] ?? 0);
    $indicatorId = (int)($_POST['indicator_id'] ?? 0);
    if (!$deptId || !$indicatorId) jsonOut(false, 'Department and indicator IDs required.');
    $userId = getCurrentUser()['id'];
    $result = assignIndicatorToDepartment($deptId, $indicatorId, $userId);
    jsonOut($result, $result ? 'Indicator assigned successfully.' : 'Failed to assign indicator.');
}

if ($action === 'remove') {
    requireRole(['admin','quality_officer']);
    $deptId      = (int)($_POST['dept_id'] ?? 0);
    $indicatorId = (int)($_POST['indicator_id'] ?? 0);
    if (!$deptId || !$indicatorId) jsonOut(false, 'Department and indicator IDs required.');
    $result = removeIndicatorFromDepartment($deptId, $indicatorId);
    jsonOut($result, $result ? 'Indicator removed successfully.' : 'Failed to remove indicator.');
}

jsonOut(false, 'Invalid action.');
