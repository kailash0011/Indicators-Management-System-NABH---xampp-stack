<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['admin','quality_officer']);
$user    = getCurrentUser();
$curPage = 'assignments';
$depts   = getDepartments('active');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignments - NABH Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="bg-gray-100">
<div id="toast-container"></div>
<div class="flex h-screen overflow-hidden">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <main class="flex-1 ml-64 overflow-y-auto">
        <header class="bg-white shadow-sm px-6 py-4 sticky top-0 z-5">
            <h1 class="text-xl font-bold text-gray-800">Indicator Assignments</h1>
            <p class="text-xs text-gray-500">Manage which indicators are assigned to which departments</p>
        </header>
        <div class="p-6">
            <!-- Department Selector -->
            <div class="bg-white rounded-xl shadow p-5 mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Department</label>
                <select id="deptSelect" class="form-select max-w-sm" onchange="loadAssignments()">
                    <option value="">— Choose a department —</option>
                    <?php foreach ($depts as $d): ?>
                    <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?> (<?= htmlspecialchars($d['code']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="assignmentArea" class="hidden">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Assigned Indicators -->
                    <div class="bg-white rounded-xl shadow">
                        <div class="p-4 border-b border-gray-100 flex items-center justify-between">
                            <h2 class="font-semibold text-gray-800">Assigned Indicators <span id="assignedCount" class="text-sm text-gray-400"></span></h2>
                        </div>
                        <div class="p-3">
                            <input type="text" id="assignedSearch" placeholder="Filter..." class="form-input text-sm mb-3" oninput="filterList('assigned')">
                        </div>
                        <div id="assignedList" class="px-3 pb-3 space-y-1 max-h-96 overflow-y-auto">
                            <p class="text-gray-400 text-sm text-center py-4">Select a department</p>
                        </div>
                    </div>
                    <!-- Unassigned Indicators -->
                    <div class="bg-white rounded-xl shadow">
                        <div class="p-4 border-b border-gray-100">
                            <h2 class="font-semibold text-gray-800">Available Indicators <span id="unassignedCount" class="text-sm text-gray-400"></span></h2>
                        </div>
                        <div class="p-3">
                            <input type="text" id="unassignedSearch" placeholder="Filter..." class="form-input text-sm mb-3" oninput="filterList('unassigned')">
                        </div>
                        <div id="unassignedList" class="px-3 pb-3 space-y-1 max-h-96 overflow-y-auto">
                            <p class="text-gray-400 text-sm text-center py-4">Select a department</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
const BASE = '<?= BASE_URL ?>';

function loadAssignments() {
    const deptId = document.getElementById('deptSelect').value;
    if (!deptId) { document.getElementById('assignmentArea').classList.add('hidden'); return; }
    document.getElementById('assignmentArea').classList.remove('hidden');

    Promise.all([
        fetch(BASE + `/ajax/assignments.php?action=get_dept_indicators&dept_id=${deptId}`).then(r => r.json()),
        fetch(BASE + `/ajax/assignments.php?action=get_unassigned&dept_id=${deptId}`).then(r => r.json())
    ]).then(([assigned, unassigned]) => {
        renderList('assignedList', assigned.data || [], 'remove', deptId);
        renderList('unassignedList', unassigned.data || [], 'assign', deptId);
        document.getElementById('assignedCount').textContent = `(${(assigned.data||[]).length})`;
        document.getElementById('unassignedCount').textContent = `(${(unassigned.data||[]).length})`;
    });
}

function renderList(containerId, items, action, deptId) {
    const container = document.getElementById(containerId);
    if (!items.length) {
        container.innerHTML = '<p class="text-gray-400 text-sm text-center py-4">None</p>';
        return;
    }
    container.innerHTML = items.map(ind => `
        <div class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-50 border border-gray-100" data-name="${ind.name.toLowerCase()} ${ind.indicator_code.toLowerCase()}">
            <div class="flex-1 min-w-0 mr-2">
                <span class="font-mono text-xs text-blue-600 font-semibold">${esc(ind.indicator_code)}</span>
                <p class="text-sm text-gray-700 truncate">${esc(ind.name)}</p>
                <p class="text-xs text-gray-400">${esc(ind.unit)} ${ind.category==='general'?'· General':'· Dept-specific'}</p>
            </div>
            <?php if (canManageAll()): ?>
            <button onclick="${action}Indicator(${deptId},${ind.id})" 
                    class="btn btn-sm ${action==='assign'?'btn-success':'btn-danger'} flex-shrink-0">
                ${action==='assign'?'Assign':'Remove'}
            </button>
            <?php endif; ?>
        </div>
    `).join('');
}

function esc(str) { if (!str) return ''; const d = document.createElement('div'); d.textContent = str; return d.innerHTML; }

function filterList(type) {
    const q = document.getElementById(type==='assigned'?'assignedSearch':'unassignedSearch').value.toLowerCase();
    const listId = type==='assigned'?'assignedList':'unassignedList';
    document.querySelectorAll(`#${listId} [data-name]`).forEach(el => {
        el.style.display = el.dataset.name.includes(q) ? '' : 'none';
    });
}

function assignIndicator(deptId, indicatorId) {
    fetch(BASE + '/ajax/assignments.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=assign&dept_id=${deptId}&indicator_id=${indicatorId}`
    }).then(r => r.json()).then(d => {
        showToast(d.message, d.success ? 'success' : 'error');
        if (d.success) loadAssignments();
    });
}

function removeIndicator(deptId, indicatorId) {
    if (!confirm('Remove this indicator from the department?')) return;
    fetch(BASE + '/ajax/assignments.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=remove&dept_id=${deptId}&indicator_id=${indicatorId}`
    }).then(r => r.json()).then(d => {
        showToast(d.message, d.success ? 'success' : 'error');
        if (d.success) loadAssignments();
    });
}

function showToast(msg, type='info') {
    const c = document.getElementById('toast-container');
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.textContent = msg;
    c.appendChild(t);
    setTimeout(() => { t.style.animation='fadeOut 0.3s forwards'; setTimeout(()=>t.remove(),300); }, 3000);
}
</script>
<script>window.APP_BASE='<?= BASE_URL ?>';</script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
