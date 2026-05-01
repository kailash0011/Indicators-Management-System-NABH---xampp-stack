<?php
require_once __DIR__ . '/config.php';

function getDepartments($status = 'active') {
    $pdo = getDB();
    if ($status === 'all') {
        $stmt = $pdo->query("SELECT * FROM departments ORDER BY name");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM departments WHERE status = ? ORDER BY name");
        $stmt->execute([$status]);
    }
    return $stmt->fetchAll();
}

function getDepartmentById($id) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM departments WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getIndicators($status = 'active') {
    $pdo = getDB();
    if ($status === 'all') {
        $stmt = $pdo->query("SELECT * FROM indicators ORDER BY indicator_code");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM indicators WHERE status = ? ORDER BY indicator_code");
        $stmt->execute([$status]);
    }
    return $stmt->fetchAll();
}

function getIndicatorById($id) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM indicators WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getDepartmentIndicators($dept_id) {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT i.*, di.id as assignment_id, di.assigned_at, di.status as assignment_status
        FROM department_indicators di
        JOIN indicators i ON di.indicator_id = i.id
        WHERE di.department_id = ? AND di.status = 'active' AND i.status = 'active'
        ORDER BY i.indicator_code
    ");
    $stmt->execute([$dept_id]);
    return $stmt->fetchAll();
}

function getUnassignedIndicators($dept_id) {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT i.*
        FROM indicators i
        WHERE i.status = 'active'
        AND i.id NOT IN (
            SELECT indicator_id FROM department_indicators
            WHERE department_id = ? AND status = 'active'
        )
        ORDER BY i.indicator_code
    ");
    $stmt->execute([$dept_id]);
    return $stmt->fetchAll();
}

function assignIndicatorToDepartment($dept_id, $indicator_id, $user_id) {
    $pdo = getDB();
    try {
        $stmt = $pdo->prepare("
            INSERT INTO department_indicators (department_id, indicator_id, assigned_by, status)
            VALUES (?, ?, ?, 'active')
            ON DUPLICATE KEY UPDATE status = 'active', assigned_by = ?, assigned_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([$dept_id, $indicator_id, $user_id, $user_id]);
        logAudit('assign_indicator', 'department_indicators', $dept_id, null, "indicator_id=$indicator_id");
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function removeIndicatorFromDepartment($dept_id, $indicator_id) {
    $pdo = getDB();
    try {
        $stmt = $pdo->prepare("UPDATE department_indicators SET status = 'inactive' WHERE department_id = ? AND indicator_id = ?");
        $stmt->execute([$dept_id, $indicator_id]);
        logAudit('remove_indicator', 'department_indicators', $dept_id, "indicator_id=$indicator_id", null);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function logAudit($action, $entity_type, $entity_id, $old_value, $new_value) {
    $pdo = getDB();
    $user_id  = $_SESSION['user_id'] ?? null;
    $username = $_SESSION['username'] ?? 'system';
    $ip       = $_SERVER['REMOTE_ADDR'] ?? '';
    $stmt = $pdo->prepare("
        INSERT INTO audit_log (user_id, username, action, entity_type, entity_id, old_value, new_value, ip_address)
        VALUES (?,?,?,?,?,?,?,?)
    ");
    $stmt->execute([$user_id, $username, $action, $entity_type, $entity_id, $old_value, $new_value, $ip]);
}

function calculateValue($numerator, $denominator, $unit) {
    if ($numerator === null || $numerator === '') return null;
    $num = (float)$numerator;
    $den = (float)($denominator ?? 0);
    switch ($unit) {
        case 'percentage': return ($den > 0) ? round(($num / $den) * 100, 4) : null;
        case 'per_1000':   return ($den > 0) ? round(($num / $den) * 1000, 4) : null;
        case 'ratio':      return ($den > 0) ? round($num / $den, 4) : null;
        case 'minutes':
        case 'number':     return round($num, 4);
        default:           return ($den > 0) ? round(($num / $den) * 100, 4) : null;
    }
}

function formatValue($value, $unit) {
    if ($value === null || $value === '') return 'N/A';
    switch ($unit) {
        case 'percentage': return round($value, 2) . '%';
        case 'per_1000':   return round($value, 2) . ' per 1000';
        case 'ratio':      return round($value, 4);
        case 'minutes':    return round($value, 1) . ' min';
        case 'number':     return round($value, 0);
        default:           return round($value, 2);
    }
}

function getMonthName($month_num) {
    $months = ['','January','February','March','April','May','June',
               'July','August','September','October','November','December'];
    return $months[(int)$month_num] ?? '';
}

function getMonthlyData($dept_id, $indicator_id, $year) {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT md.*, u.name as entered_by_name
        FROM monthly_data md
        LEFT JOIN users u ON md.entered_by = u.id
        WHERE md.department_id = ? AND md.indicator_id = ? AND md.year = ?
        ORDER BY md.month
    ");
    $stmt->execute([$dept_id, $indicator_id, $year]);
    $rows = $stmt->fetchAll();
    $data = [];
    foreach ($rows as $row) {
        $data[$row['month']] = $row;
    }
    return $data;
}

function getMonthlyDataForDept($dept_id, $month, $year) {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT md.*, i.name as indicator_name, i.indicator_code, i.unit, i.benchmark
        FROM monthly_data md
        JOIN indicators i ON md.indicator_id = i.id
        WHERE md.department_id = ? AND md.month = ? AND md.year = ?
        ORDER BY i.indicator_code
    ");
    $stmt->execute([$dept_id, $month, $year]);
    return $stmt->fetchAll();
}

function getDashboardStats() {
    $pdo   = getDB();
    $stats = [];
    $stats['departments'] = $pdo->query("SELECT COUNT(*) FROM departments WHERE status='active'")->fetchColumn();
    $stats['indicators']  = $pdo->query("SELECT COUNT(*) FROM indicators WHERE status='active'")->fetchColumn();
    $stats['users']       = $pdo->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn();
    $month = date('n');
    $year  = date('Y');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM monthly_data WHERE month=? AND year=?");
    $stmt->execute([$month, $year]);
    $stats['entries_this_month'] = $stmt->fetchColumn();
    return $stats;
}

// ── Chart / KPI Analytics helpers ────────────────────────────────────────────

/**
 * Return the first active "general" indicator to use as the default chart KPI.
 * Falls back to the first active indicator of any category.
 * Returns false when no indicators exist.
 */
function getDefaultChartIndicator() {
    $pdo = getDB();
    try {
        $stmt = $pdo->query(
            "SELECT id, name, indicator_code, unit
             FROM indicators
             WHERE status = 'active'
             ORDER BY CASE WHEN category = 'general' THEN 0 ELSE 1 END, indicator_code
             LIMIT 1"
        );
        return $stmt->fetch();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Aggregate monthly values across all departments for the given indicator
 * over the last 6 calendar months (including the current month).
 * Returns an array of ['label' => 'Mon YYYY', 'value' => float|null].
 */
function getKpiTrend6Months($indicator_id) {
    $pdo    = getDB();
    $result = [];
    // Build last-6-months list (oldest → newest)
    for ($i = 5; $i >= 0; $i--) {
        $ts    = strtotime("-$i months");
        $m     = (int)date('n', $ts);
        $y     = (int)date('Y', $ts);
        $label = date('M Y', $ts);
        $result[] = ['label' => $label, 'month' => $m, 'year' => $y, 'value' => null];
    }
    try {
        $stmt = $pdo->prepare(
            "SELECT month, year, AVG(value) as avg_value
             FROM monthly_data
             WHERE indicator_id = ?
             GROUP BY year, month"
        );
        $stmt->execute([$indicator_id]);
        $rows = $stmt->fetchAll();
        $map  = [];
        foreach ($rows as $row) {
            $key        = sprintf('%d-%02d', $row['year'], $row['month']);
            $map[$key] = round((float)$row['avg_value'], 4);
        }
        foreach ($result as &$r) {
            $key = sprintf('%d-%02d', $r['year'], $r['month']);
            if (isset($map[$key])) {
                $r['value'] = $map[$key];
            }
        }
    } catch (PDOException $e) {
        // Leave values null – caller will show empty state
    }
    return $result;
}

/**
 * For the given indicator, fetch each department's value for the current month.
 * Returns an array of ['dept_name' => string, 'value' => float|null].
 */
function getDeptComparisonCurrentMonth($indicator_id) {
    $pdo   = getDB();
    $month = (int)date('n');
    $year  = (int)date('Y');
    try {
        $stmt = $pdo->prepare(
            "SELECT d.name AS dept_name, md.value
             FROM departments d
             LEFT JOIN monthly_data md
               ON md.department_id = d.id
               AND md.indicator_id = ?
               AND md.month = ?
               AND md.year  = ?
             WHERE d.status = 'active'
             ORDER BY d.name"
        );
        $stmt->execute([$indicator_id, $month, $year]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Count of active indicators grouped by category.
 * Returns ['general' => int, 'department_specific' => int].
 */
function getIndicatorCategoryDistribution() {
    $pdo  = getDB();
    $dist = ['general' => 0, 'department_specific' => 0];
    try {
        $rows = $pdo->query(
            "SELECT category, COUNT(*) as cnt
             FROM indicators
             WHERE status = 'active'
             GROUP BY category"
        )->fetchAll();
        foreach ($rows as $row) {
            $dist[$row['category']] = (int)$row['cnt'];
        }
    } catch (PDOException $e) { /* return zeros */ }
    return $dist;
}

/**
 * Count KPIs that are in breach (value > benchmark) for the given month/year.
 * "Benchmark" is stored as a varchar; numeric prefix is parsed.
 * Returns integer count.
 */
function getKpisInBreach($month, $year) {
    $pdo = getDB();
    try {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM monthly_data md
             JOIN indicators i ON md.indicator_id = i.id
             WHERE md.month = ? AND md.year = ?
               AND md.value IS NOT NULL
               AND i.benchmark IS NOT NULL AND i.benchmark != ''
               AND i.benchmark REGEXP '^[0-9]+(\\\\.[0-9]+)?$'
               AND CAST(md.value AS DECIMAL(15,4)) > CAST(i.benchmark AS DECIMAL(15,4))"
        );
        $stmt->execute([$month, $year]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

// ─────────────────────────────────────────────────────────────────────────────

function getRoleLabel($role) {
    $labels = [
        'admin'               => 'Administrator',
        'quality_officer'     => 'Quality Officer',
        'department_incharge' => 'Dept. In-charge',
    ];
    return $labels[$role] ?? $role;
}

function getUnitLabel($unit) {
    $labels = [
        'percentage' => 'Percentage (%)',
        'per_1000'   => 'Per 1000',
        'ratio'      => 'Ratio',
        'minutes'    => 'Minutes',
        'number'     => 'Number',
    ];
    return $labels[$unit] ?? $unit;
}
