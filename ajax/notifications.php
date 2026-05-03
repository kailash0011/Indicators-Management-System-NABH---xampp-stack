<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/notifications.php';
requireRole(['admin','quality_officer']);
header('Content-Type: application/json');

function jsonOut($success, $message, $data = null) {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

$action = $_REQUEST['action'] ?? '';

if ($action === 'unread_count') {
    jsonOut(true, 'OK', ['count' => getUnreadNotificationCount()]);
}

if ($action === 'list') {
    $onlyUnread = ($_GET['filter'] ?? 'all') === 'unread';
    $items      = getNotifications($onlyUnread, 100);
    jsonOut(true, 'OK', $items);
}

if ($action === 'mark_read') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
    markNotificationsRead($id);
    jsonOut(true, 'Marked as read.', ['unread' => getUnreadNotificationCount()]);
}

if ($action === 'mark_all_read') {
    markNotificationsRead();
    jsonOut(true, 'All notifications marked as read.', ['unread' => 0]);
}

if ($action === 'run_check') {
    $month = (int)($_POST['month'] ?? date('n'));
    $year  = (int)($_POST['year']  ?? date('Y'));
    $result = runComplianceCheck($month, $year);
    jsonOut(true, "Check complete. {$result['generated']} alert(s) active, {$result['resolved']} resolved.", [
        'generated' => $result['generated'],
        'resolved'  => $result['resolved'],
        'unread'    => getUnreadNotificationCount(),
    ]);
}

if ($action === 'save_settings') {
    $threshold = (float)($_POST['threshold'] ?? 70);
    $enabled   = (int)($_POST['enabled'] ?? 1);
    if ($threshold < 1 || $threshold > 100) $threshold = 70;
    saveSetting('compliance_threshold', (string)$threshold);
    saveSetting('alerts_enabled', $enabled ? '1' : '0');
    jsonOut(true, 'Settings saved.');
}

if ($action === 'get_settings') {
    jsonOut(true, 'OK', [
        'threshold' => (float)getSetting('compliance_threshold', '70'),
        'enabled'   => getSetting('alerts_enabled', '1') === '1',
    ]);
}

jsonOut(false, 'Invalid action.');
