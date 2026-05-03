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
$curMonth = (int)date('n');
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
        <header class="bg-white shadow-sm px-6 py-4 flex items-center justify-between sticky top-0 z-10 no-print">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Reports</h1>
                <p class="text-xs text-gray-500">Generate monthly indicator reports with benchmark status</p>
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
                            <option value="<?= $m ?>" <?= $m==$curMonth?'selected':'' ?>><?= getMonthName($m) ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <button onclick="generateReport()" class="btn btn-primary mt-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Generate Report
                </button>

                <!-- Legend -->
                <div class="flex flex-wrap gap-4 mt-4 pt-4 border-t border-gray-100 text-xs text-gray-600">
                    <span class="font-medium text-gray-500">Legend:</span>
                    <span class="flex items-center gap-1.5"><span class="inline-block w-3 h-3 rounded-full bg-emerald-500"></span> Met Target (benchmark achieved)</span>
                    <span class="flex items-center gap-1.5"><span class="inline-block w-3 h-3 rounded-full bg-red-400"></span> Not Met (below benchmark)</span>
                    <span class="flex items-center gap-1.5"><span class="inline-block w-3 h-3 rounded bg-gray-200"></span> No benchmark / No data</span>
                </div>
            </div>

            <!-- Report Output -->
            <div id="reportOutput"></div>
        </div>
    </main>
</div>

<script>
const BASE = '<?= BASE_URL ?>';
const MONTHS = ['','January','February','March','April','May','June','July','August','September','October','November','December'];

/* Parse benchmark string and compare to numeric value.
   Returns 'met', 'not_met', or null (no benchmark / can't compare). */
function checkBenchmark(benchmark, rawValue) {
    if (!benchmark || rawValue === null || rawValue === undefined || rawValue === '') return null;
    const val = parseFloat(rawValue);
    if (isNaN(val)) return null;
    const b = benchmark.trim();

    const m = b.match(/^([<>]=?)\s*([\d.]+)/);
    if (m) {
        const target = parseFloat(m[2]);
        switch (m[1]) {
            case '>':  return val >  target ? 'met' : 'not_met';
            case '>=': return val >= target ? 'met' : 'not_met';
            case '<':  return val <  target ? 'met' : 'not_met';
            case '<=': return val <= target ? 'met' : 'not_met';
        }
    }
    const exact = b.match(/^([\d.]+)/);
    if (exact) return val >= parseFloat(exact[1]) ? 'met' : 'not_met';
    return null;
}

function generateReport() {
    const dept      = document.getElementById('rDept').value;
    const year      = document.getElementById('rYear').value;
    const monthFrom = document.getElementById('rMonthFrom').value;
    const monthTo   = document.getElementById('rMonthTo').value;

    document.getElementById('reportOutput').innerHTML =
        '<div class="text-center py-8 text-gray-400">Generating report…</div>';

    fetch(`${BASE}/ajax/reports.php?action=get_report&dept_id=${dept}&year=${year}&month_from=${monthFrom}&month_to=${monthTo}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success || !data.data || !data.data.length) {
                document.getElementById('reportOutput').innerHTML =
                    '<div class="bg-white rounded-xl shadow p-8 text-center text-gray-400">No data found for the selected filters.</div>';
                return;
            }
            renderReport(data.data, year, monthFrom, monthTo);
        });
}

function renderReport(groups, year, mFrom, mTo) {
    const mRange = [];
    for (let m = parseInt(mFrom); m <= parseInt(mTo); m++) mRange.push(m);

    // Overall counters
    let totalMet = 0, totalNotMet = 0;

    let html = `<div class="print-full space-y-6">`;
    html += `<div class="text-center mb-2">
        <h2 class="text-2xl font-bold text-gray-800">NABH Indicator Report — ${year}</h2>
        <p class="text-gray-500 text-sm">${MONTHS[mFrom]} to ${MONTHS[mTo]} ${year}</p>
    </div>`;

    groups.forEach(g => {
        let deptMet = 0, deptNotMet = 0;

        let rows = '';
        g.indicators.forEach(ind => {
            let cells = '';
            mRange.forEach(m => {
                const entry = ind.monthly_data && ind.monthly_data[m] !== undefined ? ind.monthly_data[m] : null;
                // raw_monthly_data carries the unformatted value for benchmark comparison
                const rawEntry = ind.raw_monthly_data && ind.raw_monthly_data[m] !== undefined ? ind.raw_monthly_data[m] : null;
                const status   = checkBenchmark(ind.benchmark, rawEntry);
                let cls = 'text-gray-300';
                let dot = '';
                if (entry !== null) {
                    if (status === 'met')     { cls = 'font-semibold text-emerald-700'; deptMet++;    totalMet++;    dot = '<span class="text-emerald-500 mr-0.5">●</span>'; }
                    else if (status === 'not_met') { cls = 'font-semibold text-red-600';     deptNotMet++; totalNotMet++; dot = '<span class="text-red-400 mr-0.5">●</span>'; }
                    else { cls = 'text-blue-800 font-medium'; }
                }
                cells += `<td class="text-center text-sm ${cls}">${entry !== null ? dot + esc(entry) : '—'}</td>`;
            });

            rows += `<tr>
                <td class="font-mono text-xs font-semibold text-blue-700">${esc(ind.indicator_code)}</td>
                <td class="text-sm font-medium">${esc(ind.name)}</td>
                <td class="text-xs text-gray-400">${esc(ind.unit)}</td>
                <td class="text-xs text-gray-500">${esc(ind.benchmark || '—')}</td>
                ${cells}
            </tr>`;
        });

        const deptTotal = deptMet + deptNotMet;
        const deptPct   = deptTotal > 0 ? Math.round(deptMet / deptTotal * 100) : null;
        const pctBadge  = deptPct !== null
            ? `<span class="ml-3 text-xs font-medium px-2 py-0.5 rounded-full ${deptPct >= 70 ? 'bg-emerald-100 text-emerald-700' : deptPct >= 50 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'}">${deptPct}% achieved</span>`
            : '';

        html += `<div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="bg-blue-900 text-white px-5 py-3 flex items-center justify-between">
                <h3 class="font-semibold">${esc(g.department_name)}</h3>
                <div class="text-sm text-blue-200 flex items-center gap-3">
                    <span class="text-emerald-300">✓ ${deptMet} met</span>
                    <span class="text-red-300">✗ ${deptNotMet} not met</span>
                    ${deptPct !== null ? `<span class="text-white font-bold">${deptPct}%</span>` : ''}
                </div>
            </div>
            <div class="overflow-x-auto">
            <table class="data-table w-full">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Indicator</th>
                        <th>Unit</th>
                        <th>Benchmark</th>
                        ${mRange.map(m => `<th class="text-center">${MONTHS[m].substring(0,3)}</th>`).join('')}
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
            </div>
        </div>`;
    });

    // Grand summary bar
    const grandTotal = totalMet + totalNotMet;
    const grandPct   = grandTotal > 0 ? Math.round(totalMet / grandTotal * 100) : 0;
    html = html.replace(
        '<div class="print-full space-y-6">',
        `<div class="print-full space-y-6">
        <div class="bg-white rounded-xl shadow px-6 py-4 flex flex-wrap items-center gap-6 no-print">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span>
                <span class="text-sm text-gray-700"><strong class="text-emerald-700">${totalMet}</strong> indicators met target</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-red-400 inline-block"></span>
                <span class="text-sm text-gray-700"><strong class="text-red-600">${totalNotMet}</strong> did not meet target</span>
            </div>
            <div class="flex-1 bg-gray-100 rounded-full h-3 min-w-32">
                <div class="bg-emerald-500 h-3 rounded-full" style="width:${grandPct}%"></div>
            </div>
            <span class="text-sm font-bold text-gray-700">${grandPct}% overall</span>
        </div>`
    );

    html += `</div>`;
    document.getElementById('reportOutput').innerHTML = html;
}

function esc(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}
</script>
</body>
</html>
