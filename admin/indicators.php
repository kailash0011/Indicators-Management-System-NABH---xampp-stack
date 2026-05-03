<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['admin','quality_officer']);
$user    = getCurrentUser();
$curPage = 'indicators';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Indicators - NABH Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="bg-gray-100">
<div id="toast-container"></div>
<div class="flex h-screen overflow-hidden">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <main class="flex-1 ml-64 overflow-y-auto">
        <header class="bg-white shadow-sm px-6 py-4 flex items-center justify-between sticky top-0 z-5">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Indicators</h1>
                <p class="text-xs text-gray-500">Manage quality indicators</p>
            </div>
            <?php if (isAdmin()): ?>
            <button onclick="openModal()" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Indicator
            </button>
            <?php endif; ?>
        </header>
        <div class="p-6">
            <div class="bg-white rounded-xl shadow">
                <div class="p-4 border-b border-gray-100 flex flex-wrap items-center gap-3">
                    <input type="text" id="searchInput" placeholder="Search indicators..." class="form-input max-w-xs" oninput="filterTable()">
                    <div class="flex gap-2">
                        <button onclick="filterCat('all')" id="btn-all" class="btn btn-sm btn-primary">All</button>
                        <button onclick="filterCat('general')" id="btn-general" class="btn btn-sm btn-secondary">General</button>
                        <button onclick="filterCat('department_specific')" id="btn-dept" class="btn btn-sm btn-secondary">Dept-Specific</button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="data-table" id="indTable">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Unit</th>
                                <th>Category</th>
                                <th>Benchmark</th>
                                <th>Status</th>
                                <?php if (isAdmin()): ?><th>Actions</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <tr><td colspan="7" class="text-center py-8 text-gray-400">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Modal -->
<div id="modal" class="modal-overlay hidden">
    <div class="modal-box" style="max-width:640px">
        <div class="modal-header">
            <h3 class="font-semibold text-gray-800" id="modalTitle">Add Indicator</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
        </div>
        <form id="indForm">
            <div class="modal-body grid grid-cols-2 gap-4">
                <input type="hidden" id="ind_id" name="id">
                <input type="hidden" name="action" value="save">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Code <span class="text-red-500">*</span></label>
                    <input type="text" name="indicator_code" id="ind_code" class="form-input" required maxlength="20">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Unit <span class="text-red-500">*</span></label>
                    <select name="unit" id="ind_unit" class="form-select" required>
                        <option value="percentage">Percentage</option>
                        <option value="per_1000">Per 1000</option>
                        <option value="ratio">Ratio</option>
                        <option value="minutes">Minutes</option>
                        <option value="number">Number</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="ind_name" class="form-input" required>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" id="ind_desc" class="form-textarea" rows="2"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Numerator Description</label>
                    <input type="text" name="numerator_description" id="ind_num_desc" class="form-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Denominator Description</label>
                    <input type="text" name="denominator_description" id="ind_den_desc" class="form-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select name="category" id="ind_cat" class="form-select">
                        <option value="general">General</option>
                        <option value="department_specific">Department Specific</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Benchmark</label>
                    <input type="text" name="benchmark" id="ind_bench" class="form-input" placeholder="e.g. >80%">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="ind_status" class="form-select">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Indicator</button>
            </div>
        </form>
    </div>
</div>

<script>
const BASE = '<?= BASE_URL ?>';
let currentCat = 'all';

function loadIndicators() {
    fetch(BASE + '/ajax/indicators.php?action=list')
        .then(r => r.json())
        .then(data => {
            const tbody = document.getElementById('tableBody');
            if (!data.success || !data.data.length) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-8 text-gray-400">No indicators found.</td></tr>';
                return;
            }
            tbody.innerHTML = data.data.map(d => `
                <tr data-name="${d.name.toLowerCase()} ${d.indicator_code.toLowerCase()}" data-cat="${d.category}">
                    <td><span class="font-mono text-xs font-semibold text-blue-700">${esc(d.indicator_code)}</span></td>
                    <td class="font-medium max-w-xs truncate" title="${esc(d.name)}">${esc(d.name)}</td>
                    <td class="text-sm text-gray-600">${esc(d.unit)}</td>
                    <td><span class="badge ${d.category==='general'?'badge-general':'badge-dept'}">${d.category==='general'?'General':'Dept'}</span></td>
                    <td class="text-sm">${esc(d.benchmark||'—')}</td>
                    <td><span class="badge badge-${d.status}">${d.status}</span></td>
                    <?php if (isAdmin()): ?>
                    <td>
                        <div class="flex items-center gap-2">
                            <button onclick="editInd(${JSON.stringify(d).replace(/"/g,'&quot;')})" class="btn btn-sm btn-outline btn-outline-primary">Edit</button>
                            <button onclick="toggleStatus(${d.id},'${d.status}')" class="btn btn-sm ${d.status==='active'?'btn-warning':'btn-success'}">${d.status==='active'?'Deactivate':'Activate'}</button>
                        </div>
                    </td>
                    <?php endif; ?>
                </tr>
            `).join('');
            applyFilter();
        });
}

function esc(str) { if (!str) return ''; const d = document.createElement('div'); d.textContent = str; return d.innerHTML; }

function filterCat(cat) {
    currentCat = cat;
    ['all','general','dept'].forEach(c => {
        const key = c === 'dept' ? 'department_specific' : c;
        const btn = document.getElementById('btn-' + c);
        btn.className = 'btn btn-sm ' + (cat === key || (cat === 'all' && c === 'all') ? 'btn-primary' : 'btn-secondary');
    });
    applyFilter();
}

function applyFilter() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('#tableBody tr[data-name]').forEach(r => {
        const matchQ   = !q || r.dataset.name.includes(q);
        const matchCat = currentCat === 'all' || r.dataset.cat === currentCat;
        r.style.display = matchQ && matchCat ? '' : 'none';
    });
}

function filterTable() { applyFilter(); }

function openModal(reset=true) {
    if (reset) {
        document.getElementById('indForm').reset();
        document.getElementById('ind_id').value = '';
        document.getElementById('modalTitle').textContent = 'Add Indicator';
    }
    document.getElementById('modal').classList.remove('hidden');
}

function closeModal() { document.getElementById('modal').classList.add('hidden'); }

function editInd(d) {
    document.getElementById('ind_id').value       = d.id;
    document.getElementById('ind_code').value     = d.indicator_code;
    document.getElementById('ind_name').value     = d.name;
    document.getElementById('ind_desc').value     = d.description || '';
    document.getElementById('ind_num_desc').value = d.numerator_description || '';
    document.getElementById('ind_den_desc').value = d.denominator_description || '';
    document.getElementById('ind_unit').value     = d.unit;
    document.getElementById('ind_cat').value      = d.category;
    document.getElementById('ind_bench').value    = d.benchmark || '';
    document.getElementById('ind_status').value   = d.status;
    document.getElementById('modalTitle').textContent = 'Edit Indicator';
    openModal(false);
}

function toggleStatus(id, current) {
    if (!confirm('Toggle status for this indicator?')) return;
    fetch(BASE + '/ajax/indicators.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=toggle_status&id=${id}`
    }).then(r => r.json()).then(d => {
        showToast(d.message, d.success ? 'success' : 'error');
        if (d.success) loadIndicators();
    });
}

document.getElementById('indForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    fetch(BASE + '/ajax/indicators.php', {
        method: 'POST', body: new URLSearchParams(fd)
    }).then(r => r.json()).then(d => {
        showToast(d.message, d.success ? 'success' : 'error');
        if (d.success) { closeModal(); loadIndicators(); }
    });
});

function showToast(msg, type='info') {
    const c = document.getElementById('toast-container');
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.textContent = msg;
    c.appendChild(t);
    setTimeout(() => { t.style.animation='fadeOut 0.3s forwards'; setTimeout(()=>t.remove(),300); }, 3000);
}

loadIndicators();
</script>
<script>window.APP_BASE='<?= BASE_URL ?>';</script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
