<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['admin','quality_officer']);

$stats = getDashboardStats();
$pdo   = getDB();

// Recent audit log entries
try {
    $recent = $pdo->query("
        SELECT al.*, u.name as user_name
        FROM audit_log al
        LEFT JOIN users u ON al.user_id = u.id
        ORDER BY al.created_at DESC
        LIMIT 10
    ")->fetchAll();
} catch (PDOException $e) {
    $recent = [];
}

// ── Chart data ──────────────────────────────────────────────────────────────
$chartMonth = (int)date('n');
$chartYear  = (int)date('Y');

// Default indicator for charts
$defaultIndicator  = getDefaultChartIndicator();
$trendData         = $defaultIndicator ? getKpiTrend6Months($defaultIndicator['id']) : [];
$deptComparison    = $defaultIndicator ? getDeptComparisonCurrentMonth($defaultIndicator['id']) : [];
$categoryDist      = getIndicatorCategoryDistribution();
$kpisInBreachCount = getKpisInBreach($chartMonth, $chartYear);

// Serialise for JS
$trendLabels = json_encode(array_column($trendData, 'label'));
$trendValues = json_encode(array_map(fn($r) => $r['value'] !== null ? (float)$r['value'] : null, $trendData));

$deptLabels = json_encode(array_column($deptComparison, 'dept_name'));
$deptValues = json_encode(array_map(fn($r) => $r['value'] !== null ? (float)$r['value'] : null, $deptComparison));

$catLabels = json_encode(['General', 'Department-specific']);
$catValues = json_encode([$categoryDist['general'], $categoryDist['department_specific']]);
// ────────────────────────────────────────────────────────────────────────────

$user    = getCurrentUser();
$curPage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - NABH Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="bg-gray-100">
<div id="toast-container"></div>
<div class="flex h-screen overflow-hidden">

    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 ml-64 overflow-y-auto">
        <header class="bg-white shadow-sm px-6 py-4 flex items-center justify-between sticky top-0 z-5">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Dashboard</h1>
                <p class="text-xs text-gray-500">Welcome back, <?= htmlspecialchars($user['name']) ?></p>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-600"><?= date('l, d F Y') ?></p>
            </div>
        </header>

        <div class="p-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow p-6 flex items-center gap-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-800"><?= $stats['departments'] ?></p>
                        <p class="text-sm text-gray-500">Departments</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow p-6 flex items-center gap-4">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-800"><?= $stats['indicators'] ?></p>
                        <p class="text-sm text-gray-500">Active Indicators</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow p-6 flex items-center gap-4">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-800"><?= $stats['users'] ?></p>
                        <p class="text-sm text-gray-500">Active Users</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow p-6 flex items-center gap-4">
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-800"><?= $stats['entries_this_month'] ?></p>
                        <p class="text-sm text-gray-500">Entries This Month</p>
                    </div>
                </div>
            </div>

            <!-- KPI Summary card – breach count -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow p-6 flex items-center gap-4">
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-red-600"><?= $kpisInBreachCount ?></p>
                        <p class="text-sm text-gray-500">KPIs in Breach <span class="text-xs">(<?= date('M Y') ?>)</span></p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow p-6 flex items-center gap-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/></svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-800"><?= $categoryDist['general'] ?></p>
                        <p class="text-sm text-gray-500">General Indicators</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow p-6 flex items-center gap-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-800"><?= $categoryDist['department_specific'] ?></p>
                        <p class="text-sm text-gray-500">Dept-specific Indicators</p>
                    </div>
                </div>
            </div>

            <!-- Charts Row 1: Line + Bar -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

                <!-- Line chart: 6-month KPI trend -->
                <div class="bg-white rounded-xl shadow p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="font-semibold text-gray-800">KPI Trend – Last 6 Months</h2>
                            <?php if ($defaultIndicator): ?>
                            <p class="text-xs text-gray-400 mt-0.5"><?= htmlspecialchars($defaultIndicator['indicator_code'] . ' – ' . $defaultIndicator['name']) ?></p>
                            <?php endif; ?>
                        </div>
                        <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full font-medium">Avg across depts</span>
                    </div>
                    <?php if (!$defaultIndicator): ?>
                    <div class="flex flex-col items-center justify-center h-48 text-gray-400">
                        <svg class="w-10 h-10 mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        <p class="text-sm">No indicators found. <a href="<?= BASE_URL ?>/admin/indicators.php" class="text-blue-600 hover:underline">Add indicators</a> to see the trend.</p>
                    </div>
                    <?php else: ?>
                    <?php $hasLineData = count(array_filter($trendData, fn($r) => $r['value'] !== null)) > 0; ?>
                    <?php if (!$hasLineData): ?>
                    <div class="flex flex-col items-center justify-center h-48 text-gray-400">
                        <svg class="w-10 h-10 mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-sm">No monthly data recorded yet for this indicator.</p>
                    </div>
                    <?php else: ?>
                    <div class="relative" style="height:220px">
                        <canvas id="lineChart"></canvas>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Bar chart: department comparison -->
                <div class="bg-white rounded-xl shadow p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="font-semibold text-gray-800">Department Comparison</h2>
                            <?php if ($defaultIndicator): ?>
                            <p class="text-xs text-gray-400 mt-0.5"><?= htmlspecialchars($defaultIndicator['indicator_code']) ?> &mdash; <?= date('F Y') ?></p>
                            <?php endif; ?>
                        </div>
                        <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full font-medium">Current month</span>
                    </div>
                    <?php if (!$defaultIndicator): ?>
                    <div class="flex flex-col items-center justify-center h-48 text-gray-400">
                        <p class="text-sm">No indicators available.</p>
                    </div>
                    <?php else: ?>
                    <?php $hasBarData = count(array_filter($deptComparison, fn($r) => $r['value'] !== null)) > 0; ?>
                    <?php if (!$hasBarData): ?>
                    <div class="flex flex-col items-center justify-center h-48 text-gray-400">
                        <svg class="w-10 h-10 mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-sm">No data submitted for <?= date('F Y') ?> yet.</p>
                    </div>
                    <?php else: ?>
                    <div class="relative" style="height:220px">
                        <canvas id="barChart"></canvas>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Charts Row 2: Doughnut + Recent Activity -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

                <!-- Doughnut chart: indicator category distribution -->
                <div class="bg-white rounded-xl shadow p-6 flex flex-col">
                    <h2 class="font-semibold text-gray-800 mb-4">Indicator Distribution</h2>
                    <?php if (($categoryDist['general'] + $categoryDist['department_specific']) === 0): ?>
                    <div class="flex flex-col items-center justify-center flex-1 text-gray-400">
                        <svg class="w-10 h-10 mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-sm">No indicators found.</p>
                    </div>
                    <?php else: ?>
                    <div class="relative mx-auto" style="height:200px;width:200px">
                        <canvas id="doughnutChart"></canvas>
                    </div>
                    <div class="flex justify-center gap-4 mt-4 text-xs text-gray-600">
                        <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded-full bg-blue-500"></span>General (<?= $categoryDist['general'] ?>)</span>
                        <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded-full bg-yellow-400"></span>Dept-specific (<?= $categoryDist['department_specific'] ?>)</span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white rounded-xl shadow lg:col-span-2">
                    <div class="p-5 border-b border-gray-100">
                        <h2 class="font-semibold text-gray-800">Recent Activity</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date/Time</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Entity</th>
                                    <th>IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent)): ?>
                                <tr><td colspan="5" class="text-center text-gray-400 py-8">No activity recorded yet.</td></tr>
                                <?php else: ?>
                                <?php foreach ($recent as $log): ?>
                                <tr>
                                    <td class="text-xs text-gray-500"><?= htmlspecialchars($log['created_at']) ?></td>
                                    <td class="font-medium"><?= htmlspecialchars($log['username']) ?></td>
                                    <td><span class="badge badge-active"><?= htmlspecialchars($log['action']) ?></span></td>
                                    <td class="text-sm"><?= htmlspecialchars($log['entity_type'] . ($log['entity_id'] ? ' #'.$log['entity_id'] : '')) ?></td>
                                    <td class="text-xs text-gray-400"><?= htmlspecialchars($log['ip_address']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (!empty($recent)): ?>
                    <div class="p-4 border-t border-gray-100 text-right">
                        <a href="<?= BASE_URL ?>/admin/audit.php" class="text-sm text-blue-600 hover:underline">View full audit log →</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
Chart.defaults.font.family = "'Inter', 'Helvetica Neue', sans-serif";
Chart.defaults.font.size   = 12;

// ── Line chart ──────────────────────────────────────────────────────────────
(function () {
    const canvas = document.getElementById('lineChart');
    if (!canvas) return;
    const labels = <?= $trendLabels ?>;
    const values = <?= $trendValues ?>;
    new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: '<?= addslashes($defaultIndicator ? $defaultIndicator['indicator_code'] : '') ?>',
                data: values,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59,130,246,0.12)',
                tension: 0.35,
                fill: true,
                pointBackgroundColor: '#3b82f6',
                pointRadius: 4,
                spanGaps: true,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } }
            }
        }
    });
})();

// ── Bar chart ───────────────────────────────────────────────────────────────
(function () {
    const canvas = document.getElementById('barChart');
    if (!canvas) return;
    const labels = <?= $deptLabels ?>;
    const values = <?= $deptValues ?>;
    const colors = labels.map((_, i) => `hsl(${(i * 47) % 360},65%,55%)`); // 47 is prime → maximises hue spread
    new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: '<?= addslashes($defaultIndicator ? $defaultIndicator['indicator_code'] : '') ?>',
                data: values,
                backgroundColor: colors,
                borderRadius: 4,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { maxRotation: 30 } },
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } }
            }
        }
    });
})();

// ── Doughnut chart ──────────────────────────────────────────────────────────
(function () {
    const canvas = document.getElementById('doughnutChart');
    if (!canvas) return;
    const labels = <?= $catLabels ?>;
    const values = <?= $catValues ?>;
    new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: ['#3b82f6', '#facc15'],
                borderWidth: 2,
                borderColor: '#fff',
                hoverOffset: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ` ${ctx.label}: ${ctx.parsed}`
                    }
                }
            },
            cutout: '65%',
        }
    });
})();
</script>
</body>
</html>
