<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['department_incharge']);
$user    = getCurrentUser();
$curPage = 'data_entry';
$deptId  = $user['department_id'];
$indicators = getDepartmentIndicators($deptId);
$curMonth = (int)date('n');
$curYear  = (int)date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Entry - NABH</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        .nav-link { color: #99f6e4; }
        .nav-link:hover, .nav-link.active { background:rgba(255,255,255,0.15); color:#fff; font-weight:600; }
    </style>
</head>
<body class="bg-gray-100">
<div id="toast-container"></div>
<div class="flex h-screen overflow-hidden">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <main class="flex-1 ml-64 overflow-y-auto">
        <header class="bg-white shadow-sm px-6 py-4 flex items-center justify-between sticky top-0 z-5">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Monthly Data Entry</h1>
                <p class="text-xs text-gray-500"><?= htmlspecialchars($user['department_name']) ?></p>
            </div>
            <div class="flex items-center gap-3">
                <select id="selMonth" class="form-select text-sm" onchange="loadData()">
                    <?php for ($m=1;$m<=12;$m++): ?>
                    <option value="<?= $m ?>" <?= $m==$curMonth?'selected':'' ?>><?= getMonthName($m) ?></option>
                    <?php endfor; ?>
                </select>
                <select id="selYear" class="form-select text-sm" onchange="loadData()">
                    <?php for ($y=date('Y');$y>=date('Y')-5;$y--): ?>
                    <option value="<?= $y ?>"><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </header>
        <div class="p-6">
            <div class="bg-white rounded-xl shadow">
                <div class="p-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="font-semibold text-gray-700" id="periodLabel">Loading...</h2>
                    <button onclick="saveAll()" class="btn btn-primary" id="saveBtn">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                        Save All
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="data-table entry-table" id="entryTable">
                        <thead>
                            <tr>
                                <th style="width:30px">#</th>
                                <th>Indicator</th>
                                <th>Unit</th>
                                <th>Benchmark</th>
                                <th style="width:120px">Numerator</th>
                                <th style="width:120px">Denominator</th>
                                <th style="width:100px">Calculated Value</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody id="entryBody">
                            <tr><td colspan="8" class="text-center py-8 text-gray-400">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-gray-100 flex justify-end">
                    <button onclick="saveAll()" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                        Save All
                    </button>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
const BASE    = '<?= BASE_URL ?>';
const DEPT_ID = <?= $deptId ?>;
const INDICATORS = <?= json_encode($indicators) ?>;
const MONTHS  = ['','January','February','March','April','May','June','July','August','September','October','November','December'];

function getMonth() { return parseInt(document.getElementById('selMonth').value); }
function getYear()  { return parseInt(document.getElementById('selYear').value); }

function calcValue(num, den, unit) {
    if (num === '' || num === null || isNaN(num)) return '';
    num = parseFloat(num); den = parseFloat(den||0);
    switch(unit) {
        case 'percentage': return den > 0 ? ((num/den)*100).toFixed(2)+'%' : '';
        case 'per_1000':   return den > 0 ? ((num/den)*1000).toFixed(2)+' /1000' : '';
        case 'ratio':      return den > 0 ? (num/den).toFixed(4) : '';
        case 'minutes':    return num.toFixed(1)+' min';
        case 'number':     return Math.round(num).toString();
        default:           return den > 0 ? ((num/den)*100).toFixed(2)+'%' : '';
    }
}

function loadData() {
    const month = getMonth(); const year = getYear();
    document.getElementById('periodLabel').textContent = MONTHS[month] + ' ' + year;

    fetch(`${BASE}/ajax/data_entry.php?action=get&dept_id=${DEPT_ID}&month=${month}&year=${year}`)
        .then(r => r.json())
        .then(resp => {
            const existing = {};
            if (resp.success && resp.data) {
                resp.data.forEach(d => existing[d.indicator_id] = d);
            }
            renderTable(existing);
        });
}

function renderTable(existing) {
    if (!INDICATORS.length) {
        document.getElementById('entryBody').innerHTML = '<tr><td colspan="8" class="text-center py-8 text-gray-400">No indicators assigned to your department yet.</td></tr>';
        return;
    }
    document.getElementById('entryBody').innerHTML = INDICATORS.map((ind, i) => {
        const ex = existing[ind.id] || {};
        const numVal = ex.numerator !== undefined && ex.numerator !== null ? ex.numerator : '';
        const denVal = ex.denominator !== undefined && ex.denominator !== null ? ex.denominator : '';
        const calcVal = numVal !== '' ? calcValue(numVal, denVal, ind.unit) : '—';
        const needsDenom = !['minutes','number'].includes(ind.unit);
        return `
        <tr>
            <td class="text-gray-400 text-xs">${i+1}</td>
            <td>
                <span class="font-mono text-xs font-semibold text-blue-700">${esc(ind.indicator_code)}</span>
                <p class="text-sm font-medium text-gray-700">${esc(ind.name)}</p>
                ${ind.numerator_description ? `<p class="text-xs text-gray-400">Num: ${esc(ind.numerator_description)}</p>` : ''}
                ${ind.denominator_description && needsDenom ? `<p class="text-xs text-gray-400">Den: ${esc(ind.denominator_description)}</p>` : ''}
            </td>
            <td class="text-xs text-gray-500">${esc(ind.unit)}</td>
            <td class="text-xs text-gray-500">${esc(ind.benchmark||'—')}</td>
            <td>
                <input type="number" step="any" 
                       id="num_${ind.id}" 
                       value="${numVal}"
                       class="w-full px-2 py-1.5 border border-gray-200 rounded text-sm text-right focus:border-teal-400 focus:outline-none"
                       oninput="updateCalc(${ind.id},'${ind.unit}')">
            </td>
            <td>
                ${needsDenom ? `<input type="number" step="any" 
                       id="den_${ind.id}" 
                       value="${denVal}"
                       class="w-full px-2 py-1.5 border border-gray-200 rounded text-sm text-right focus:border-teal-400 focus:outline-none"
                       oninput="updateCalc(${ind.id},'${ind.unit}')">` : '<span class="text-gray-300 text-sm text-center block">N/A</span>'}
            </td>
            <td class="text-center">
                <span id="calc_${ind.id}" class="calc-value text-sm font-semibold text-teal-700">${calcVal}</span>
            </td>
            <td>
                <input type="text" id="rem_${ind.id}" value="${esc(ex.remarks||'')}"
                       class="w-full px-2 py-1.5 border border-gray-200 rounded text-sm focus:border-teal-400 focus:outline-none"
                       placeholder="Optional remarks">
            </td>
        </tr>`;
    }).join('');
}

function esc(str) { if (!str) return ''; const d = document.createElement('div'); d.textContent = str; return d.innerHTML; }

function updateCalc(indId, unit) {
    const num = document.getElementById('num_'+indId)?.value ?? '';
    const denEl = document.getElementById('den_'+indId);
    const den = denEl ? denEl.value : '';
    const val = calcValue(num, den, unit);
    document.getElementById('calc_'+indId).textContent = val || '—';
}

function saveAll() {
    const month = getMonth(); const year = getYear();
    const entries = INDICATORS.map(ind => {
        const numEl = document.getElementById('num_'+ind.id);
        const denEl = document.getElementById('den_'+ind.id);
        const remEl = document.getElementById('rem_'+ind.id);
        return {
            indicator_id: ind.id,
            numerator:    numEl ? numEl.value : '',
            denominator:  denEl ? denEl.value : '',
            remarks:      remEl ? remEl.value : ''
        };
    }).filter(e => e.numerator !== '');

    if (!entries.length) { showToast('No data to save (fill at least one numerator).', 'warning'); return; }

    document.getElementById('saveBtn').disabled = true;
    document.getElementById('saveBtn').textContent = 'Saving...';

    const params = new URLSearchParams({
        action: 'save',
        dept_id: DEPT_ID,
        month: month,
        year: year,
        entries: JSON.stringify(entries)
    });

    fetch(BASE + '/ajax/data_entry.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: params
    }).then(r => r.json()).then(d => {
        showToast(d.message, d.success ? 'success' : 'error');
        document.getElementById('saveBtn').disabled = false;
        document.getElementById('saveBtn').innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg> Save All';
        if (d.success) loadData();
    });
}

function showToast(msg, type='info') {
    const c = document.getElementById('toast-container');
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.textContent = msg;
    c.appendChild(t);
    setTimeout(() => { t.style.animation='fadeOut 0.3s forwards'; setTimeout(()=>t.remove(),300); }, 3500);
}

loadData();
</script>
</body>
</html>
