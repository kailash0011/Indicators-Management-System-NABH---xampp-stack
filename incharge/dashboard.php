<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['department_incharge']);
$user    = getCurrentUser();
$curPage = 'dashboard';
$deptId  = $user['department_id'];

$pdo = getDB();

// Stats
$assignedCount = $pdo->prepare("SELECT COUNT(*) FROM department_indicators WHERE department_id=? AND status='active'");
$assignedCount->execute([$deptId]);
$assigned = $assignedCount->fetchColumn();

$month = date('n'); $year = date('Y');
$entriesStmt = $pdo->prepare("SELECT COUNT(*) FROM monthly_data WHERE department_id=? AND month=? AND year=?");
$entriesStmt->execute([$deptId, $month, $year]);
$entriesThisMonth = $entriesStmt->fetchColumn();
$pending = $assigned - $entriesThisMonth;

// Recent entries
$recent = $pdo->prepare("
    SELECT md.*, i.name as indicator_name, i.indicator_code, i.unit
    FROM monthly_data md
    JOIN indicators i ON md.indicator_id = i.id
    WHERE md.department_id = ?
    ORDER BY md.updated_at DESC
    LIMIT 8
");
$recent->execute([$deptId]);
$recentData = $recent->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= htmlspecialchars($user['department_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        .nav-link { color: #99f6e4; }
        .nav-link:hover, .nav-link.active { background: rgba(255,255,255,0.15); color: #fff; font-weight: 600; }
    </style>
</head>
<body class="bg-gray-100">
<div id="toast-container"></div>
<div class="flex h-screen overflow-hidden">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <main class="flex-1 ml-64 overflow-y-auto">
        <header class="bg-white shadow-sm px-6 py-4 flex items-center justify-between sticky top-0 z-5">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Welcome, <?= htmlspecialchars($user['name']) ?></h1>
                <p class="text-xs text-gray-500"><?= htmlspecialchars($user['department_name']) ?> Department</p>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-600"><?= date('l, d F Y') ?></p>
                <p class="text-xs text-teal-600 font-medium">Current: <?= date('F Y') ?></p>
            </div>
        </header>
        <div class="p-6">
            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow p-6 flex items-center gap-4">
                    <div class="w-12 h-12 bg-teal-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-800"><?= $assigned ?></p>
                        <p class="text-sm text-gray-500">Assigned Indicators</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow p-6 flex items-center gap-4">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-800"><?= $entriesThisMonth ?></p>
                        <p class="text-sm text-gray-500">Entries This Month</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow p-6 flex items-center gap-4">
                    <div class="w-12 h-12 <?= $pending > 0 ? 'bg-red-100' : 'bg-green-100' ?> rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 <?= $pending > 0 ? 'text-red-600' : 'text-green-600' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold <?= $pending > 0 ? 'text-red-600' : 'text-green-600' ?>"><?= max(0,$pending) ?></p>
                        <p class="text-sm text-gray-500">Pending <?= date('M Y') ?></p>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                <a href="<?= BASE_URL ?>/incharge/data_entry.php" class="bg-teal-600 hover:bg-teal-700 text-white rounded-xl p-5 flex items-center gap-3 transition">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    <div>
                        <p class="font-semibold">Enter Monthly Data</p>
                        <p class="text-teal-100 text-xs">Record indicator values</p>
                    </div>
                </a>
                <a href="<?= BASE_URL ?>/incharge/history.php" class="bg-blue-600 hover:bg-blue-700 text-white rounded-xl p-5 flex items-center gap-3 transition">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <div>
                        <p class="font-semibold">View History</p>
                        <p class="text-blue-100 text-xs">Annual data matrix</p>
                    </div>
                </a>
                <a href="<?= BASE_URL ?>/incharge/trends.php" class="bg-purple-600 hover:bg-purple-700 text-white rounded-xl p-5 flex items-center gap-3 transition">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                    <div>
                        <p class="font-semibold">View Trends</p>
                        <p class="text-purple-100 text-xs">Charts &amp; analytics</p>
                    </div>
                </a>
            </div>

            <!-- Recent Data -->
            <div class="bg-white rounded-xl shadow">
                <div class="p-5 border-b border-gray-100">
                    <h2 class="font-semibold text-gray-800">Recent Data Entries</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Indicator</th>
                                <th>Month/Year</th>
                                <th>Value</th>
                                <th>Entered</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentData)): ?>
                            <tr><td colspan="4" class="text-center py-8 text-gray-400">No data entered yet. <a href="<?= BASE_URL ?>/incharge/data_entry.php" class="text-teal-600 underline">Enter data now</a></td></tr>
                            <?php else: ?>
                            <?php foreach ($recentData as $row): ?>
                            <tr>
                                <td>
                                    <span class="font-mono text-xs font-semibold text-blue-700"><?= htmlspecialchars($row['indicator_code']) ?></span>
                                    <p class="text-sm font-medium text-gray-700"><?= htmlspecialchars($row['indicator_name']) ?></p>
                                </td>
                                <td class="text-sm"><?= getMonthName($row['month']) ?> <?= $row['year'] ?></td>
                                <td class="font-semibold text-teal-700"><?= formatValue($row['value'], $row['unit']) ?></td>
                                <td class="text-xs text-gray-400"><?= htmlspecialchars($row['entered_at']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>
<script>window.APP_BASE='<?= BASE_URL ?>';</script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
