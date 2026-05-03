<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['admin','quality_officer']);

$stats   = getDashboardStats();
$pdo     = getDB();
$user    = getCurrentUser();
$curPage = 'dashboard';

$curMonth = (int)date('n');
$curYear  = (int)date('Y');

// Recent audit log
$recent = $pdo->query("
    SELECT al.*, u.name as user_name
    FROM audit_log al
    LEFT JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC
    LIMIT 8
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - NABH Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="bg-gray-100">
<div id="toast-container"></div>
<div class="flex h-screen overflow-hidden">

    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="flex-1 ml-64 overflow-y-auto">
        <header class="bg-white shadow-sm px-6 py-4 flex items-center justify-between sticky top-0 z-10">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Dashboard</h1>
                <p class="text-xs text-gray-500">Welcome back, <?= htmlspecialchars($user['name']) ?></p>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-600"><?= date('l, d F Y') ?></p>
            </div>
        </header>

        <div class="p-6 space-y-6">

            <!-- ── Summary Stats ── -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
                    <div class="w-11 h-11 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-800"><?= $stats['departments'] ?></p>
                        <p class="text-xs text-gray-500">Departments</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
                    <div class="w-11 h-11 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-800"><?= $stats['indicators'] ?></p>
                        <p class="text-xs text-gray-500">Active Indicators</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
                    <div class="w-11 h-11 bg-emerald-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-800" id="stat-met">—</p>
                        <p class="text-xs text-gray-500">Met Target</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
                    <div class="w-11 h-11 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-800" id="stat-notmet">—</p>
                        <p class="text-xs text-gray-500">Not Met Target</p>
                    </div>
                </div>
            </div>

            <!-- ── Month / Year Selector ── -->
            <div class="bg-white rounded-xl shadow px-5 py-4 flex flex-wrap items-center gap-4">
                <span class="text-sm font-semibold text-gray-700">Benchmark View:</span>
                <div class="flex items-center gap-2">
                    <label class="text-sm text-gray-600">Month</label>
                    <select id="selMonth" class="form-select w-36">
                        <?php
                        $months = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
                        for ($m = 1; $m <= 12; $m++):
                        ?>
                        <option value="<?= $m ?>" <?= $m == $curMonth ? 'selected' : '' ?>><?= $months[$m] ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-sm text-gray-600">Year</label>
                    <select id="selYear" class="form-select w-28">
                        <?php for ($y = $curYear; $y >= $curYear - 4; $y--): ?>
                        <option value="<?= $y ?>"><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <button onclick="loadAll()" class="btn btn-primary btn-sm">Refresh</button>
                <span id="loading-indicator" class="text-xs text-blue-500 hidden">Loading…</span>
            </div>

            <!-- ── Charts Row ── -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Donut: Met vs Not Met -->
                <div class="bg-white rounded-xl shadow p-5 flex flex-col">
                    <h2 class="font-semibold text-gray-800 mb-1">Target Achievement</h2>
                    <p class="text-xs text-gray-400 mb-4" id="donut-subtitle">Loading…</p>
                    <div class="flex-1 flex items-center justify-center" style="min-height:200px">
                        <canvas id="chartDonut"></canvas>
                    </div>
                    <div class="flex justify-center gap-6 mt-3 text-xs">
                        <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded-full bg-emerald-500"></span> Met Target</span>
                        <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded-full bg-red-400"></span> Not Met</span>
                    </div>
                </div>

                <!-- Bar: Dept Compliance -->
                <div class="bg-white rounded-xl shadow p-5 lg:col-span-2 flex flex-col">
                    <h2 class="font-semibold text-gray-800 mb-1">Department-wise Compliance %</h2>
                    <p class="text-xs text-gray-400 mb-4" id="bar-subtitle">Loading…</p>
                    <div class="flex-1" style="min-height:200px">
                        <canvas id="chartBar"></canvas>
                    </div>
                </div>
            </div>

            <!-- Line: 12-month trend -->
            <div class="bg-white rounded-xl shadow p-5">
                <h2 class="font-semibold text-gray-800 mb-1">12-Month Benchmark Trend</h2>
                <p class="text-xs text-gray-400 mb-4">Number of indicators meeting / not meeting target over the past 12 months</p>
                <div style="height:220px">
                    <canvas id="chartLine"></canvas>
                </div>
            </div>

            <!-- ── Benchmark Status Table ── -->
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="font-semibold text-gray-800">Indicator Status — <span id="tbl-period">…</span></h2>
                        <p class="text-xs text-gray-400 mt-0.5">All indicators with a benchmark — colour coded by achievement</p>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="filterTable('all')"     id="btn-all"     class="btn btn-sm btn-primary">All</button>
                        <button onclick="filterTable('met')"     id="btn-met"     class="btn btn-sm btn-outline btn-outline-primary">Met</button>
                        <button onclick="filterTable('not_met')" id="btn-not_met" class="btn btn-sm btn-outline btn-outline-primary">Not Met</button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="data-table w-full" id="benchmarkTable">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Code</th>
                                <th>Indicator</th>
                                <th>Benchmark</th>
                                <th class="text-center">Value</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody id="benchmarkTbody">
                            <tr><td colspan="6" class="text-center text-gray-400 py-8">Loading…</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="px-5 py-3 border-t border-gray-100 text-xs text-gray-400" id="tbl-summary"></div>
            </div>

            <!-- ── Recent Activity ── -->
            <div class="bg-white rounded-xl shadow overflow-hidden">
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

        </div><!-- /p-6 -->
    </main>
</div>

<script>
const BASE = '<?= BASE_URL ?>';
const MONTHS = ['','January','February','March','April','May','June','July','August','September','October','November','December'];

let chartDonut = null;
let chartBar   = null;
let chartLine  = null;
let allRows    = [];   // cached benchmark rows
let activeFilter = 'all';

/* ── Init charts once ── */
function initCharts() {
    const donutCtx = document.getElementById('chartDonut').getContext('2d');
    chartDonut = new Chart(donutCtx, {
        type: 'doughnut',
        data: {
            labels: ['Met Target','Not Met'],
            datasets: [{
                data: [0, 0],
                backgroundColor: ['#10b981','#f87171'],
                borderWidth: 2,
                borderColor: '#fff',
                hoverOffset: 6
            }]
        },
        options: {
            cutout: '65%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ` ${ctx.label}: ${ctx.parsed} indicators`
                    }
                }
            }
        }
    });

    const barCtx = document.getElementById('chartBar').getContext('2d');
    chartBar = new Chart(barCtx, {
        type: 'bar',
        data: { labels: [], datasets: [
            { label: 'Met Target',  data: [], backgroundColor: '#10b981', borderRadius: 4 },
            { label: 'Not Met',     data: [], backgroundColor: '#f87171', borderRadius: 4 }
        ]},
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
            scales: {
                x: { stacked: true, ticks: { font: { size: 10 } } },
                y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });

    const lineCtx = document.getElementById('chartLine').getContext('2d');
    chartLine = new Chart(lineCtx, {
        type: 'line',
        data: { labels: [], datasets: [
            {
                label: 'Met Target',
                data: [],
                borderColor: '#10b981',
                backgroundColor: 'rgba(16,185,129,0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6
            },
            {
                label: 'Not Met',
                data: [],
                borderColor: '#f87171',
                backgroundColor: 'rgba(248,113,113,0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6
            }
        ]},
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });
}

/* ── Fetch + render all sections ── */
function loadAll() {
    const month = document.getElementById('selMonth').value;
    const year  = document.getElementById('selYear').value;
    document.getElementById('loading-indicator').classList.remove('hidden');
    Promise.all([
        fetchMonthlyStatus(month, year),
        fetchDeptCompliance(month, year),
        fetchTrend(month, year)
    ]).finally(() => document.getElementById('loading-indicator').classList.add('hidden'));
}

function fetchMonthlyStatus(month, year) {
    return fetch(`${BASE}/ajax/benchmark_status.php?action=monthly_status&month=${month}&year=${year}`)
        .then(r => r.json())
        .then(d => {
            if (!d.success) return;
            const data = d.data;

            // Summary stats
            document.getElementById('stat-met').textContent    = data.met_count;
            document.getElementById('stat-notmet').textContent = data.not_met_count;

            // Donut chart
            chartDonut.data.datasets[0].data = [data.met_count, data.not_met_count];
            chartDonut.update();
            const periodLabel = MONTHS[parseInt(month)] + ' ' + year;
            document.getElementById('donut-subtitle').textContent = periodLabel;
            document.getElementById('tbl-period').textContent     = periodLabel;

            // Build table rows cache
            allRows = [
                ...data.met.map(r     => ({ ...r, status: 'met' })),
                ...data.not_met.map(r => ({ ...r, status: 'not_met' })),
            ];

            renderTable(activeFilter);

            const total = data.met_count + data.not_met_count;
            const pct   = total ? Math.round(data.met_count / total * 100) : 0;
            document.getElementById('tbl-summary').textContent =
                `${data.met_count} of ${total} measurable indicators met their benchmark (${pct}%) · ${data.not_met_count} did not · ${data.no_bm.length} have no benchmark`;
        });
}

function fetchDeptCompliance(month, year) {
    return fetch(`${BASE}/ajax/benchmark_status.php?action=dept_compliance&month=${month}&year=${year}`)
        .then(r => r.json())
        .then(d => {
            if (!d.success) return;
            const rows = d.data;
            chartBar.data.labels                    = rows.map(r => r.dept.length > 12 ? r.dept.substring(0,12)+'…' : r.dept);
            chartBar.data.datasets[0].data          = rows.map(r => r.met);
            chartBar.data.datasets[1].data          = rows.map(r => r.not_met);
            chartBar.update();
            const periodLabel = MONTHS[parseInt(month)] + ' ' + year;
            document.getElementById('bar-subtitle').textContent = periodLabel + ' — stacked count by department';
        });
}

function fetchTrend(month, year) {
    return fetch(`${BASE}/ajax/benchmark_status.php?action=trend&month=${month}&year=${year}`)
        .then(r => r.json())
        .then(d => {
            if (!d.success) return;
            const rows = d.data;
            chartLine.data.labels                  = rows.map(r => r.label);
            chartLine.data.datasets[0].data        = rows.map(r => r.met);
            chartLine.data.datasets[1].data        = rows.map(r => r.not_met);
            chartLine.update();
        });
}

/* ── Benchmark table rendering ── */
function renderTable(filter) {
    activeFilter = filter;
    const rows = filter === 'all' ? allRows : allRows.filter(r => r.status === filter);
    const tbody = document.getElementById('benchmarkTbody');

    // Update button styles
    ['all','met','not_met'].forEach(f => {
        const btn = document.getElementById('btn-' + f);
        btn.className = 'btn btn-sm ' + (f === filter ? 'btn-primary' : 'btn-outline btn-outline-primary');
    });

    if (!rows.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-gray-400 py-8">No data for this selection.</td></tr>';
        return;
    }

    tbody.innerHTML = rows.map(r => {
        const isMet = r.status === 'met';
        const badge = isMet
            ? '<span class="badge" style="background:#dcfce7;color:#15803d">✓ Met Target</span>'
            : '<span class="badge" style="background:#fee2e2;color:#b91c1c">✗ Not Met</span>';
        const valClass = isMet ? 'value-good' : 'value-bad';
        return `<tr>
            <td class="text-sm">${esc(r.dept_name)}</td>
            <td class="font-mono text-xs font-semibold text-blue-700">${esc(r.indicator_code)}</td>
            <td class="text-sm">${esc(r.indicator_name)}</td>
            <td class="text-xs text-gray-500">${esc(r.benchmark)}</td>
            <td class="text-center ${valClass}">${esc(r.value)}</td>
            <td class="text-center">${badge}</td>
        </tr>`;
    }).join('');
}

function filterTable(f) { renderTable(f); }

function esc(s) {
    if (s == null) return '—';
    const d = document.createElement('div');
    d.textContent = String(s);
    return d.innerHTML;
}

/* ── Boot ── */
document.addEventListener('DOMContentLoaded', () => {
    initCharts();
    loadAll();
});
</script>
</body>
</html>
