<?php
require_once __DIR__ . '/config.php';

/**
 * Parse benchmark string, compare to value.
 * Returns true (met), false (not met), or null (no benchmark / unparseable).
 */
function nbCheckBenchmark($benchmark, $value) {
    if ($benchmark === null || $benchmark === '' || $value === null || $value === '') return null;
    $b   = trim($benchmark);
    $val = (float)$value;
    if (preg_match('/^([<>]=?)\s*([\d.]+)/', $b, $m)) {
        $target = (float)$m[2];
        switch ($m[1]) {
            case '>':  return $val >  $target;
            case '>=': return $val >= $target;
            case '<':  return $val <  $target;
            case '<=': return $val <= $target;
        }
    }
    if (preg_match('/^([\d.]+)/', $b, $m)) {
        return $val >= (float)$m[1];
    }
    return null;
}

/** Get a setting value by key, with optional default. */
function getSetting($key, $default = null) {
    try {
        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = ?");
        $stmt->execute([$key]);
        $row  = $stmt->fetch();
        return $row ? $row['value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

/** Save a setting value. */
function saveSetting($key, $value) {
    $pdo = getDB();
    $stmt = $pdo->prepare("INSERT INTO settings (`key`, value) VALUES (?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)");
    $stmt->execute([$key, $value]);
}

/** Return the count of unread notifications. */
function getUnreadNotificationCount() {
    try {
        $pdo  = getDB();
        return (int)$pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0")->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

/** Return notifications (all or unread only), newest first. */
function getNotifications($onlyUnread = false, $limit = 50) {
    $pdo  = getDB();
    $sql  = "SELECT * FROM notifications" . ($onlyUnread ? " WHERE is_read = 0" : "") . " ORDER BY created_at DESC LIMIT ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/** Mark one or all notifications as read. */
function markNotificationsRead($id = null) {
    $pdo = getDB();
    if ($id) {
        $pdo->prepare("UPDATE notifications SET is_read=1 WHERE id=?")->execute([$id]);
    } else {
        $pdo->exec("UPDATE notifications SET is_read=1");
    }
}

/**
 * Check compliance for a given month+year across all departments.
 * Generates/updates a notification for every dept whose compliance %
 * falls below the configured threshold.
 * Returns ['generated'=>N, 'resolved'=>N] counts.
 */
function runComplianceCheck($month, $year) {
    $alertsEnabled = getSetting('alerts_enabled', '1');
    if ($alertsEnabled !== '1') return ['generated' => 0, 'resolved' => 0];

    $threshold = (float)(getSetting('compliance_threshold', '70'));
    $pdo       = getDB();

    // Fetch departments that have data for this period
    $depts = $pdo->query("SELECT id, name FROM departments WHERE status='active'")->fetchAll();

    $generated = 0;
    $resolved  = 0;

    $monthNames = ['','January','February','March','April','May','June',
                   'July','August','September','October','November','December'];

    foreach ($depts as $dept) {
        $deptId = $dept['id'];

        // Get all indicators with benchmarks that have data this month
        $stmt = $pdo->prepare("
            SELECT i.benchmark, md.value
            FROM monthly_data md
            JOIN indicators i ON md.indicator_id = i.id
            WHERE md.department_id = ? AND md.month = ? AND md.year = ?
              AND i.benchmark IS NOT NULL AND i.benchmark != ''
              AND i.status = 'active'
        ");
        $stmt->execute([$deptId, $month, $year]);
        $rows = $stmt->fetchAll();

        if (empty($rows)) continue; // no measurable data yet

        $met = 0; $notMet = 0;
        foreach ($rows as $r) {
            $s = nbCheckBenchmark($r['benchmark'], $r['value']);
            if ($s === true)  $met++;
            elseif ($s === false) $notMet++;
        }

        $total = $met + $notMet;
        if ($total === 0) continue;

        $compliancePct = round($met / $total * 100, 2);
        $periodLabel   = $monthNames[$month] . ' ' . $year;

        if ($compliancePct < $threshold) {
            // Upsert alert
            $title   = "Low Compliance: {$dept['name']}";
            $message = "{$dept['name']} achieved {$compliancePct}% compliance in {$periodLabel} "
                     . "({$met} of {$total} indicators met target) — below the {$threshold}% threshold.";

            $ins = $pdo->prepare("
                INSERT INTO notifications (type, title, message, dept_id, dept_name, month, year, threshold_used, compliance_pct, is_read)
                VALUES ('below_threshold', ?, ?, ?, ?, ?, ?, ?, ?, 0)
                ON DUPLICATE KEY UPDATE
                    title=VALUES(title), message=VALUES(message),
                    threshold_used=VALUES(threshold_used), compliance_pct=VALUES(compliance_pct),
                    is_read = IF(ABS(compliance_pct - VALUES(compliance_pct)) > 1, 0, is_read),
                    created_at = IF(ABS(compliance_pct - VALUES(compliance_pct)) > 1, CURRENT_TIMESTAMP, created_at)
            ");
            $ins->execute([$title, $message, $deptId, $dept['name'], $month, $year, $threshold, $compliancePct]);
            $generated++;
        } else {
            // Compliance is now OK — remove any existing alert for this dept/month/year
            $del = $pdo->prepare("DELETE FROM notifications WHERE dept_id=? AND month=? AND year=? AND type='below_threshold'");
            $del->execute([$deptId, $month, $year]);
            if ($del->rowCount() > 0) $resolved++;
        }
    }

    return ['generated' => $generated, 'resolved' => $resolved];
}
