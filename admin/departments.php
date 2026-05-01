<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['admin','quality_officer']);
$user    = getCurrentUser();
$curPage = 'departments';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departments - NABH Admin</title>
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
                <h1 class="text-xl font-bold text-gray-800">Departments</h1>
                <p class="text-xs text-gray-500">Manage hospital departments</p>
            </div>
            <?php if (isAdmin()): ?>
            <button onclick="openModal()" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Department
            </button>
            <?php endif; ?>
        </header>
        <div class="p-6">
            <div class="bg-white rounded-xl shadow">
                <div class="p-4 border-b border-gray-100 flex items-center gap-3">
                    <input type="text" id="searchInput" placeholder="Search departments..." class="form-input max-w-xs" oninput="filterTable()">
                </div>
                <div class="overflow-x-auto">
                    <table class="data-table" id="deptTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Code</th>
                                <th>Department Name</th>
                                <th>In-charge</th>
                                <th>Contact</th>
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
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="font-semibold text-gray-800" id="modalTitle">Add Department</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
        </div>
        <form id="deptForm">
            <div class="modal-body space-y-4">
                <input type="hidden" id="dept_id" name="id">
                <input type="hidden" name="action" value="save">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Department Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="dept_name" class="form-input" required placeholder="e.g. Cardiology">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Department Code <span class="text-red-500">*</span></label>
                    <input type="text" name="code" id="dept_code" class="form-input" required placeholder="e.g. CARD" maxlength="20">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">In-charge Name</label>
                    <input type="text" name="incharge_name" id="dept_incharge" class="form-input" placeholder="Dr. John Doe">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact</label>
                    <input type="text" name="contact" id="dept_contact" class="form-input" placeholder="Phone / Extension">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="dept_status" class="form-select">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Department</button>
            </div>
        </form>
    </div>
</div>

<script>
const BASE = '<?= BASE_URL ?>';

function loadDepartments() {
    fetch(BASE + '/ajax/departments.php?action=list')
        .then(r => r.json())
        .then(data => {
            const tbody = document.getElementById('tableBody');
            if (!data.success || !data.data.length) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-8 text-gray-400">No departments found.</td></tr>';
                return;
            }
            tbody.innerHTML = data.data.map((d, i) => `
                <tr data-name="${d.name.toLowerCase()} ${d.code.toLowerCase()}">
                    <td class="text-gray-400 text-xs">${i+1}</td>
                    <td><span class="font-mono text-sm font-semibold text-blue-700">${esc(d.code)}</span></td>
                    <td class="font-medium">${esc(d.name)}</td>
                    <td>${esc(d.incharge_name||'—')}</td>
                    <td>${esc(d.contact||'—')}</td>
                    <td><span class="badge badge-${d.status}">${d.status}</span></td>
                    <?php if (isAdmin()): ?>
                    <td>
                        <div class="flex items-center gap-2">
                            <button onclick="editDept(${d.id},'${esc(d.name)}','${esc(d.code)}','${esc(d.incharge_name||'')}','${esc(d.contact||'')}','${d.status}')" class="btn btn-sm btn-outline btn-outline-primary">Edit</button>
                            <button onclick="toggleStatus(${d.id},'${d.status}')" class="btn btn-sm ${d.status==='active'?'btn-warning':'btn-success'}">${d.status==='active'?'Deactivate':'Activate'}</button>
                        </div>
                    </td>
                    <?php endif; ?>
                </tr>
            `).join('');
        });
}

function esc(str) { 
    const d = document.createElement('div'); 
    d.textContent = str; 
    return d.innerHTML; 
}

function filterTable() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('#tableBody tr[data-name]').forEach(r => {
        r.style.display = r.dataset.name.includes(q) ? '' : 'none';
    });
}

function openModal(reset=true) {
    if (reset) {
        document.getElementById('deptForm').reset();
        document.getElementById('dept_id').value = '';
        document.getElementById('modalTitle').textContent = 'Add Department';
    }
    document.getElementById('modal').classList.remove('hidden');
}

function closeModal() { document.getElementById('modal').classList.add('hidden'); }

function editDept(id, name, code, incharge, contact, status) {
    document.getElementById('dept_id').value       = id;
    document.getElementById('dept_name').value     = name;
    document.getElementById('dept_code').value     = code;
    document.getElementById('dept_incharge').value = incharge;
    document.getElementById('dept_contact').value  = contact;
    document.getElementById('dept_status').value   = status;
    document.getElementById('modalTitle').textContent = 'Edit Department';
    openModal(false);
}

function toggleStatus(id, current) {
    if (!confirm(`Are you sure you want to ${current==='active'?'deactivate':'activate'} this department?`)) return;
    fetch(BASE + '/ajax/departments.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=toggle_status&id=${id}`
    }).then(r => r.json()).then(d => {
        showToast(d.message, d.success ? 'success' : 'error');
        if (d.success) loadDepartments();
    });
}

document.getElementById('deptForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    fetch(BASE + '/ajax/departments.php', {
        method: 'POST',
        body: new URLSearchParams(fd)
    }).then(r => r.json()).then(d => {
        showToast(d.message, d.success ? 'success' : 'error');
        if (d.success) { closeModal(); loadDepartments(); }
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

loadDepartments();
</script>
</body>
</html>
