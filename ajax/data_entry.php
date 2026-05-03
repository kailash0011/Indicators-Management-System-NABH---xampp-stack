<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/notifications.php';
requireLogin();
header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

function jsonOut($success, $message, $data=null) {
    echo json_encode(['success'=>$success,'message'=>$message,'data'=>$data]);
    exit;
}

if ($action === 'get') {
    $deptId = (int)($_GET['dept_id'] ?? 0);
    $month  = (int)($_GET['month'] ?? 0);
    $year   = (int)($_GET['year'] ?? 0);

    // Verify access
    $user = getCurrentUser();
    if (!canManageAll() && $user['department_id'] != $deptId) {
        jsonOut(false, 'Access denied.');
    }

    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT md.*, i.unit
        FROM monthly_data md
        JOIN indicators i ON md.indicator_id = i.id
        WHERE md.department_id=? AND md.month=? AND md.year=?
    ");
    $stmt->execute([$deptId, $month, $year]);
    jsonOut(true, 'OK', $stmt->fetchAll());
}

if ($action === 'save') {
    $deptId  = (int)($_POST['dept_id'] ?? 0);
    $month   = (int)($_POST['month'] ?? 0);
    $year    = (int)($_POST['year'] ?? 0);
    $entries = json_decode($_POST['entries'] ?? '[]', true);

    $user = getCurrentUser();
    if (!canManageAll() && $user['department_id'] != $deptId) {
        jsonOut(false, 'Access denied.');
    }
    if (!$deptId || !$month || !$year || empty($entries)) {
        jsonOut(false, 'Missing required parameters.');
    }
    if ($month < 1 || $month > 12) jsonOut(false, 'Invalid month.');

    $pdo     = getDB();
    $userId  = $user['id'];
    $saved   = 0;
    $errors  = [];

    foreach ($entries as $entry) {
        $indicatorId = (int)($entry['indicator_id'] ?? 0);
        $numerator   = $entry['numerator'] !== '' ? $entry['numerator'] : null;
        $denominator = $entry['denominator'] !== '' ? $entry['denominator'] : null;
        $remarks     = trim($entry['remarks'] ?? '');

        if (!$indicatorId || $numerator === null) continue;

        // Get indicator unit for value calculation
        $indStmt = $pdo->prepare("SELECT unit FROM indicators WHERE id=?");
        $indStmt->execute([$indicatorId]);
        $ind = $indStmt->fetch();
        if (!$ind) continue;

        $value = calculateValue($numerator, $denominator, $ind['unit']);

        try {
            $stmt = $pdo->prepare("
                INSERT INTO monthly_data (department_id, indicator_id, month, year, numerator, denominator, value, remarks, entered_by)
                VALUES (?,?,?,?,?,?,?,?,?)
                ON DUPLICATE KEY UPDATE
                    numerator=VALUES(numerator), denominator=VALUES(denominator),
                    value=VALUES(value), remarks=VALUES(remarks),
                    entered_by=VALUES(entered_by), updated_at=CURRENT_TIMESTAMP
            ");
            $stmt->execute([$deptId, $indicatorId, $month, $year, $numerator, $denominator, $value, $remarks, $userId]);
            $saved++;
        } catch (PDOException $e) {
            $errors[] = "Indicator $indicatorId: " . $e->getMessage();
        }
    }

    if ($saved > 0) {
        logAudit('save_monthly_data','monthly_data',$deptId,null,"month=$month,year=$year,count=$saved");
        // Run compliance check silently so alerts stay current
        try { runComplianceCheck($month, $year); } catch (Exception $e) { /* non-fatal */ }
    }

    if ($saved > 0) {
        jsonOut(true, "$saved indicator(s) saved successfully." . (!empty($errors) ? ' Some errors: '.implode('; ',$errors) : ''));
    } else {
        jsonOut(false, 'No data saved.' . (!empty($errors) ? ' Errors: '.implode('; ',$errors) : ''));
    }
}

jsonOut(false, 'Invalid action.');
