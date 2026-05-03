<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['admin','quality_officer']);
header('Content-Type: application/json');

function jsonOut($success, $message, $data = null) {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

/**
 * Parse a benchmark string and determine if $value meets it.
 * Returns true (met), false (not met), or null (no benchmark / can't parse).
 */
function checkBenchmark($benchmark, $value) {
    if ($benchmark === null || $benchmark === '' || $value === null || $value === '') return null;
    $b   = trim($benchmark);
    $val = (float)$value;

    // Operator-prefixed: >85%, <5%, >=90%, <=2%, <30 min, <1 per 1000, etc.
    if (preg_match('/^([<>]=?)\s*([\d.]+)/', $b, $m)) {
        $target = (float)$m[2];
        switch ($m[1]) {
            case '>':  return $val >  $target;
            case '>=': return $val >= $target;
            case '<':  return $val <  $target;
            case '<=': return $val <= $target;
        }
    }
    // Exact / no-operator (e.g. "100%") — treat as ">= target"
    if (preg_match('/^([\d.]+)/', $b, $m)) {
        return $val >= (float)$m[1];
    }
    return null;
}

$action = $_GET['action'] ?? '';
$pdo    = getDB();

/* ── Monthly benchmark status ── */
if ($action === 'monthly_status') {
    $month = (int)($_GET['month'] ?? date('n'));
    $year  = (int)($_GET['year']  ?? date('Y'));
    if ($month < 1 || $month > 12) $month = (int)date('n');

    $rows = $pdo->prepare("
        SELECT
            d.name  AS dept_name,
            i.indicator_code,
            i.name  AS indicator_name,
            i.unit,
            i.benchmark,
            md.value,
            md.numerator,
            md.denominator
        FROM monthly_data md
        JOIN departments d  ON md.department_id = d.id
        JOIN indicators  i  ON md.indicator_id  = i.id
        WHERE md.month = ? AND md.year = ?
          AND d.status = 'active' AND i.status = 'active'
        ORDER BY d.name, i.indicator_code
    ");
    $rows->execute([$month, $year]);
    $data = $rows->fetchAll();

    $met     = [];
    $not_met = [];
    $no_bm   = [];

    foreach ($data as $row) {
        $status = checkBenchmark($row['benchmark'], $row['value']);
        $item   = [
            'dept_name'       => $row['dept_name'],
            'indicator_code'  => $row['indicator_code'],
            'indicator_name'  => $row['indicator_name'],
            'unit'            => $row['unit'],
            'benchmark'       => $row['benchmark'] ?? '—',
            'value'           => $row['value'] !== null ? formatValue($row['value'], $row['unit']) : '—',
            'raw_value'       => $row['value'],
        ];
        if ($status === true)  $met[]     = $item;
        elseif ($status === false) $not_met[] = $item;
        else                   $no_bm[]   = $item;
    }

    jsonOut(true, 'OK', [
        'month'   => $month,
        'year'    => $year,
        'met'     => $met,
        'not_met' => $not_met,
        'no_bm'   => $no_bm,
        'total'   => count($met) + count($not_met),
        'met_count'     => count($met),
        'not_met_count' => count($not_met),
    ]);
}

/* ── Department-level compliance for a month (for bar chart) ── */
if ($action === 'dept_compliance') {
    $month = (int)($_GET['month'] ?? date('n'));
    $year  = (int)($_GET['year']  ?? date('Y'));

    $rows = $pdo->prepare("
        SELECT
            d.name  AS dept_name,
            i.benchmark,
            md.value
        FROM monthly_data md
        JOIN departments d  ON md.department_id = d.id
        JOIN indicators  i  ON md.indicator_id  = i.id
        WHERE md.month = ? AND md.year = ?
          AND d.status = 'active' AND i.status = 'active'
          AND i.benchmark IS NOT NULL AND i.benchmark != ''
    ");
    $rows->execute([$month, $year]);
    $data = $rows->fetchAll();

    $depts = [];
    foreach ($data as $row) {
        $dn = $row['dept_name'];
        if (!isset($depts[$dn])) $depts[$dn] = ['met' => 0, 'not_met' => 0];
        $s = checkBenchmark($row['benchmark'], $row['value']);
        if ($s === true)  $depts[$dn]['met']++;
        elseif ($s === false) $depts[$dn]['not_met']++;
    }

    $result = [];
    foreach ($depts as $name => $counts) {
        $total = $counts['met'] + $counts['not_met'];
        $result[] = [
            'dept'    => $name,
            'met'     => $counts['met'],
            'not_met' => $counts['not_met'],
            'pct'     => $total > 0 ? round($counts['met'] / $total * 100, 1) : 0,
        ];
    }
    usort($result, fn($a,$b) => $b['pct'] <=> $a['pct']);
    jsonOut(true, 'OK', $result);
}

/* ── 12-month trend: overall met vs not-met counts ── */
if ($action === 'trend') {
    $endYear  = (int)($_GET['year']  ?? date('Y'));
    $endMonth = (int)($_GET['month'] ?? date('n'));

    $months = [];
    $y = $endYear; $m = $endMonth;
    for ($i = 0; $i < 12; $i++) {
        $months[] = ['y' => $y, 'm' => $m];
        $m--;
        if ($m < 1) { $m = 12; $y--; }
    }
    $months = array_reverse($months);

    $trend = [];
    $stmt  = $pdo->prepare("
        SELECT i.benchmark, md.value
        FROM monthly_data md
        JOIN indicators i ON md.indicator_id = i.id
        WHERE md.month = ? AND md.year = ?
          AND i.status = 'active'
          AND i.benchmark IS NOT NULL AND i.benchmark != ''
    ");

    foreach ($months as $mo) {
        $stmt->execute([$mo['m'], $mo['y']]);
        $rows = $stmt->fetchAll();
        $met = $nm = 0;
        foreach ($rows as $r) {
            $s = checkBenchmark($r['benchmark'], $r['value']);
            if ($s === true)  $met++;
            elseif ($s === false) $nm++;
        }
        $trend[] = [
            'label'   => date('M Y', mktime(0,0,0,$mo['m'],1,$mo['y'])),
            'met'     => $met,
            'not_met' => $nm,
        ];
    }
    jsonOut(true, 'OK', $trend);
}

jsonOut(false, 'Invalid action.');
