<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['admin','quality_officer']);
$user    = getCurrentUser();
$curPage = 'audit';

$pdo = getDB();
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;
$dateFrom  = $_GET['date_from'] ?? '';
$dateTo    = $_GET['date_to'] ?? '';
$actionFilter = $_GET['action_filter'] ?? '';

$where = [];
$params = [];
if ($dateFrom) { $where[] = 'DATE(al.created_at) >= ?'; $params[] = $dateFrom; }
if ($dateTo)   { $where[] = 'DATE(al.created_at) <= ?'; $params[] = $dateTo; }
if ($actionFilter) { $where[] = 'al.action LIKE ?'; $params[] = '%'.$actionFilter.'%'; }
$whereSQL = $where ? 'WHERE '.implode(' AND ', $where) : '';

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM audit_log al $whereSQL");
$totalStmt->execute($params);
$total = $totalStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

$paginatedParams = array_merge($params, [$perPage, $offset]);
$stmt = $pdo->prepare("
    SELECT al.*, u.name as user_name
    FROM audit_log al LEFT JOIN users u ON al.user_id = u.id
    $whereSQL
    ORDER BY al.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute($paginatedParams);
$logs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Log - NABH Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="bg-gray-100">
<div class="flex h-screen overflow-hidden">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <main class="flex-1 ml-64 overflow-y-auto">
        <header class="bg-white shadow-sm px-6 py-4 sticky top-0 z-5">
            <h1 class="text-xl font-bold text-gray-800">Audit Log</h1>
            <p class="text-xs text-gray-500">System activity trail</p>
        </header>
        <div class="p-6">
            <!-- Filters -->
            <form method="GET" class="bg-white rounded-xl shadow p-5 mb-6">
                <div class="flex flex-wrap gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                        <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>" class="form-input">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                        <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>" class="form-input">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Action</label>
                        <input type="text" name="action_filter" value="<?= htmlspecialchars($actionFilter) ?>" class="form-input" placeholder="e.g. login, save">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="<?= BASE_URL ?>/admin/audit.php" class="btn btn-secondary">Reset</a>
                    </div>
                </div>
            </form>

            <!-- Table -->
            <div class="bg-white rounded-xl shadow">
                <div class="p-4 border-b border-gray-100 flex items-center justify-between">
                    <p class="text-sm text-gray-500">Showing <?= count($logs) ?> of <?= $total ?> records</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date/Time</th>
                                <th>Username</th>
                                <th>Action</th>
                                <th>Entity Type</th>
                                <th>Entity ID</th>
                                <th>Old Value</th>
                                <th>New Value</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                            <tr><td colspan="8" class="text-center py-8 text-gray-400">No audit records found.</td></tr>
                            <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td class="text-xs text-gray-500 whitespace-nowrap"><?= htmlspecialchars($log['created_at']) ?></td>
                                <td class="font-medium text-sm"><?= htmlspecialchars($log['username']) ?></td>
                                <td><span class="badge badge-active text-xs"><?= htmlspecialchars($log['action']) ?></span></td>
                                <td class="text-sm text-gray-600"><?= htmlspecialchars($log['entity_type'] ?? '—') ?></td>
                                <td class="text-sm text-center"><?= htmlspecialchars($log['entity_id'] ?? '—') ?></td>
                                <td class="text-xs text-gray-500 max-w-xs truncate" title="<?= htmlspecialchars($log['old_value'] ?? '') ?>"><?= htmlspecialchars(mb_strimwidth($log['old_value'] ?? '—', 0, 50, '…')) ?></td>
                                <td class="text-xs text-gray-500 max-w-xs truncate" title="<?= htmlspecialchars($log['new_value'] ?? '') ?>"><?= htmlspecialchars(mb_strimwidth($log['new_value'] ?? '—', 0, 50, '…')) ?></td>
                                <td class="text-xs text-gray-400"><?= htmlspecialchars($log['ip_address'] ?? '—') ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="p-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-sm text-gray-500">Page <?= $page ?> of <?= $totalPages ?></p>
                    <div class="flex gap-2">
                        <?php if ($page > 1): ?>
                        <a href="?page=<?= $page-1 ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>&action_filter=<?= urlencode($actionFilter) ?>" class="btn btn-sm btn-secondary">← Prev</a>
                        <?php endif; ?>
                        <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page+1 ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>&action_filter=<?= urlencode($actionFilter) ?>" class="btn btn-sm btn-primary">Next →</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
</body>
</html>
