<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['department_incharge']);
$user    = getCurrentUser();
$curPage = 'trends';
$deptId  = $user['department_id'];
$selYear = (int)($_GET['year'] ?? date('Y'));
$indicators = getDepartmentIndicators($deptId);

// Build chart data
$chartData = [];
foreach ($indicators as $ind) {
    $monthly = getMonthlyData($deptId, $ind['id'], $selYear);
    $values = [];
    for ($m=1;$m<=12;$m++) {
        $values[] = isset($monthly[$m]) && $monthly[$m]['value'] !== null ? (float)$monthly[$m]['value'] : null;
    }
    $hasData = array_filter($values, fn($v) => $v !== null);
    if ($hasData) {
        $chartData[] = [
            'id'          => $ind['id'],
            'code'        => $ind['indicator_code'],
            'name'        => $ind['name'],
            'unit'        => $ind['unit'],
            'benchmark'   => $ind['benchmark'],
            'values'      => $values,
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trends - NABH</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        .nav-link { color: #99f6e4; }
        .nav-link:hover, .nav-link.active { background:rgba(255,255,255,0.15); color:#fff; font-weight:600; }
    </style>
</head>
<body class="bg-gray-100">
<div class="flex h-screen overflow-hidden">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <main class="flex-1 ml-64 overflow-y-auto">
        <header class="bg-white shadow-sm px-6 py-4 flex items-center justify-between sticky top-0 z-5">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Trends</h1>
                <p class="text-xs text-gray-500"><?= htmlspecialchars($user['department_name']) ?></p>
            </div>
            <form method="GET" class="flex items-center gap-2">
                <select name="year" class="form-select text-sm" onchange="this.form.submit()">
                    <?php for ($y=date('Y');$y>=date('Y')-5;$y--): ?>
                    <option value="<?= $y ?>" <?= $y==$selYear?'selected':'' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </form>
        </header>
        <div class="p-6">
            <?php if (empty($chartData)): ?>
            <div class="bg-white rounded-xl shadow p-12 text-center text-gray-400">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M7 12l3-3 3 3 4-4"/></svg>
                <p class="text-lg font-medium">No data available for <?= $selYear ?></p>
                <p class="text-sm mt-2">Enter monthly data to see trend charts.</p>
                <a href="<?= BASE_URL ?>/incharge/data_entry.php" class="btn btn-primary mt-4 inline-flex">Enter Data</a>
            </div>
            <?php else: ?>
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <?php foreach ($chartData as $cd): ?>
                <div class="bg-white rounded-xl shadow p-5">
                    <div class="mb-3">
                        <span class="font-mono text-xs font-semibold text-blue-700"><?= htmlspecialchars($cd['code']) ?></span>
                        <?php if ($cd['benchmark']): ?>
                        <span class="ml-2 text-xs text-gray-400">Benchmark: <?= htmlspecialchars($cd['benchmark']) ?></span>
                        <?php endif; ?>
                        <h3 class="font-semibold text-gray-800 text-sm mt-1"><?= htmlspecialchars($cd['name']) ?></h3>
                    </div>
                    <div style="position:relative;height:200px;">
                        <canvas id="chart_<?= $cd['id'] ?>"></canvas>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
const chartData = <?= json_encode($chartData) ?>;
const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
const colors = ['#0ea5e9','#10b981','#8b5cf6','#f59e0b','#ef4444','#06b6d4','#f97316','#84cc16','#ec4899','#6366f1'];

chartData.forEach((cd, idx) => {
    const ctx = document.getElementById('chart_' + cd.id);
    if (!ctx) return;
    const color = colors[idx % colors.length];
    const datasets = [{
        label: cd.name,
        data: cd.values,
        borderColor: color,
        backgroundColor: color + '20',
        borderWidth: 2,
        pointBackgroundColor: color,
        pointRadius: 4,
        pointHoverRadius: 6,
        tension: 0.3,
        fill: true,
        spanGaps: true,
    }];
    new Chart(ctx, {
        type: 'line',
        data: { labels: months, datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (c) => {
                            const v = c.parsed.y;
                            if (v === null) return 'No data';
                            const unit = cd.unit;
                            if (unit==='percentage') return v.toFixed(2)+'%';
                            if (unit==='per_1000') return v.toFixed(2)+' /1000';
                            if (unit==='minutes') return v.toFixed(1)+' min';
                            if (unit==='number') return Math.round(v).toString();
                            return v.toFixed(4);
                        }
                    }
                }
            },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, grid: { color: '#f1f5f9' } }
            }
        }
    });
});
</script>
</body>
</html>
