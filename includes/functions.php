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
