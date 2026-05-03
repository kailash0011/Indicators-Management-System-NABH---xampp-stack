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

if ($action === 'get_report') {
    $deptId    = (int)($_GET['dept_id'] ?? 0);
    $year      = (int)($_GET['year'] ?? date('Y'));
    $monthFrom = (int)($_GET['month_from'] ?? 1);
    $monthTo   = (int)($_GET['month_to'] ?? 12);

    if ($monthFrom < 1 || $monthFrom > 12) $monthFrom = 1;
    if ($monthTo < 1 || $monthTo > 12) $monthTo = 12;
    if ($monthFrom > $monthTo) [$monthFrom, $monthTo] = [$monthTo, $monthFrom];

    $pdo = getDB();

    // Get departments to report on
    if ($deptId > 0) {
        $deptStmt = $pdo->prepare("SELECT id, name FROM departments WHERE id=? AND status='active'");
        $deptStmt->execute([$deptId]);
        $departments = $deptStmt->fetchAll();
    } else {
        $departments = $pdo->query("SELECT id, name FROM departments WHERE status='active' ORDER BY name")->fetchAll();
    }

    $result = [];

    foreach ($departments as $dept) {
        // Get assigned indicators for this department
        $indStmt = $pdo->prepare("
            SELECT i.*
            FROM department_indicators di
            JOIN indicators i ON di.indicator_id = i.id
            WHERE di.department_id=? AND di.status='active' AND i.status='active'
            ORDER BY i.indicator_code
        ");
        $indStmt->execute([$dept['id']]);
        $indicators = $indStmt->fetchAll();

        if (empty($indicators)) continue;

        $deptGroup = [
            'department_id'   => $dept['id'],
            'department_name' => $dept['name'],
            'indicators'      => [],
        ];

        foreach ($indicators as $ind) {
            // Get monthly data for the range
            $dataStmt = $pdo->prepare("
                SELECT month, value FROM monthly_data
                WHERE department_id=? AND indicator_id=? AND year=? AND month BETWEEN ? AND ?
            ");
            $dataStmt->execute([$dept['id'], $ind['id'], $year, $monthFrom, $monthTo]);
            $rows = $dataStmt->fetchAll();

            $hasAnyData = !empty($rows);
            if (!$hasAnyData) continue; // Skip indicators with no data in range

            $monthlyData    = [];
            $rawMonthlyData = [];
            foreach ($rows as $row) {
                $val = $row['value'];
                $monthlyData[$row['month']]    = $val !== null ? formatValue($val, $ind['unit']) : null;
                $rawMonthlyData[$row['month']] = $val;
            }

            $deptGroup['indicators'][] = [
                'indicator_code'    => $ind['indicator_code'],
                'name'              => $ind['name'],
                'unit'              => $ind['unit'],
                'benchmark'         => $ind['benchmark'],
                'monthly_data'      => $monthlyData,
                'raw_monthly_data'  => $rawMonthlyData,
            ];
        }

        if (!empty($deptGroup['indicators'])) {
            $result[] = $deptGroup;
        }
    }

    jsonOut(true, 'Report generated.', $result);
}

jsonOut(false, 'Invalid action.');
