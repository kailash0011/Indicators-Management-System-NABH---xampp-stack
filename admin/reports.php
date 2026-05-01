<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['admin','quality_officer']);
$user    = getCurrentUser();
$curPage = 'reports';
$depts   = getDepartments('active');
$curYear = date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - NABH Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="bg-gray-100">
<div id="toast-container"></div>
<div class="flex h-screen overflow-hidden">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <main class="flex-1 ml-64 overflow-y-auto">
        <header class="bg-white shadow-sm px-6 py-4 flex items-center justify-between sticky top-0 z-5 no-print">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Reports</h1>
                <p class="text-xs text-gray-500">Generate monthly indicator reports</p>
            </div>
            <button onclick="window.print()" class="btn btn-secondary no-print">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print
            </button>
        </header>
        <div class="p-6">
            <!-- Filter Form -->
            <div class="bg-white rounded-xl shadow p-5 mb-6 no-print">
                <h2 class="font-semibold text-gray-700 mb-4">Report Filters</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                        <select id="rDept" class="form-select">
                            <option value="">All Departments</option>
                            <?php foreach ($depts as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                        <select id="rYear" class="form-select">
                            <?php for ($y = $curYear; $y >= $curYear - 5; $y--): ?>
                            <option value="<?= $y ?>"><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Month From</label>
                        <select id="rMonthFrom" class="form-select">
                            <?php for ($m=1;$m<=12;$m++): ?>
                            <option value="<?= $m ?>" <?= $m==1?'selected':'' ?>><?= getMonthName($m) ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Month To</label>
                        <select id="rMonthTo" class="form-select">
                            <?php for ($m=1;$m<=12;$m++): ?>
                            <option value="<?= $m ?>" <?= $m==12?'selected':'' ?>><?= getMonthName($m) ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <button onclick="generateReport()" class="btn btn-primary mt-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Generate Report
                </button>
            </div>

            <!-- Report Output -->
            <div id="reportOutput"></div>
        </div>
    </main>
</div>

<script>
const BASE = '<?= BASE_URL ?>';

function generateReport() {
    const dept      = document.getElementById('rDept').value;
    const year      = document.getElementById('rYear').value;
    const monthFrom = document.getElementById('rMonthFrom').value;
    const monthTo   = document.getElementById('rMonthTo').value;

    document.getElementById('reportOutput').innerHTML = '<div class="text-center py-8 text-gray-400">Generating report...</div>';

    fetch(`${BASE}/ajax/reports.php?action=get_report&dept_id=${dept}&year=${year}&month_from=${monthFrom}&month_to=${monthTo}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success || !data.data || !data.data.length) {
                document.getElementById('reportOutput').innerHTML = '<div class="bg-white rounded-xl shadow p-8 text-center text-gray-400">No data found for the selected filters.</div>';
                return;
            }
            renderReport(data.data, year, monthFrom, monthTo);
        });
}

function renderReport(groups, year, mFrom, mTo) {
    const months = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
    const mRange = [];
    for (let m = parseInt(mFrom); m <= parseInt(mTo); m++) mRange.push(m);

    let html = `<div class="print-full">`;
    html += `<div class="text-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">NABH Indicator Report - ${year}</h2>
        <p class="text-gray-500">${months[mFrom]} to ${months[mTo]} ${year}</p>
    </div>`;

    groups.forEach(g => {
        html += `<div class="bg-white rounded-xl shadow mb-6 overflow-hidden">
            <div class="bg-blue-900 text-white p-4">
                <h3 class="font-semibold">${esc(g.department_name)}</h3>
            </div>
            <div class="overflow-x-auto">
            <table class="data-table w-full">
                <thead>
                    <tr>
                        <th class="text-left">Code</th>
                        <th class="text-left">Indicator</th>
                        <th>Unit</th>
                        <th>Benchmark</th>
                        ${mRange.map(m=>`<th>${months[m].substring(0,3)}</th>`).join('')}
                    </tr>
                </thead>
                <tbody>`;

        g.indicators.forEach(ind => {
            html += `<tr>
                <td class="font-mono text-xs font-semibold text-blue-700">${esc(ind.indicator_code)}</td>
                <td class="text-sm font-medium">${esc(ind.name)}</td>
                <td class="text-xs text-gray-500">${esc(ind.unit)}</td>
                <td class="text-xs text-gray-500">${esc(ind.benchmark||'—')}</td>
                ${mRange.map(m => {
                    const val = ind.monthly_data && ind.monthly_data[m] !== undefined ? ind.monthly_data[m] : null;
                    return `<td class="text-center text-sm ${val !== null ? 'font-medium text-blue-800' : 'text-gray-300'}">${val !== null ? val : '—'}</td>`;
                }).join('')}
            </tr>`;
        });
        html += `</tbody></table></div></div>`;
    });

    html += `</div>`;
    document.getElementById('reportOutput').innerHTML = html;
}

function esc(str) { if (!str) return ''; const d = document.createElement('div'); d.textContent = str; return d.innerHTML; }

function showToast(msg, type='info') {
    const c = document.getElementById('toast-container');
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.textContent = msg;
    c.appendChild(t);
    setTimeout(() => { t.style.animation='fadeOut 0.3s forwards'; setTimeout(()=>t.remove(),300); }, 3000);
}
</script>
</body>
</html>
