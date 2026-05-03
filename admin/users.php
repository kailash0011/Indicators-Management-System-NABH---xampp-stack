<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['admin']);
$user    = getCurrentUser();
$curPage = 'users';
$depts   = getDepartments('all');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - NABH Admin</title>
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
                <h1 class="text-xl font-bold text-gray-800">Users</h1>
                <p class="text-xs text-gray-500">Manage system users</p>
            </div>
            <button onclick="openModal()" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add User
            </button>
        </header>
        <div class="p-6">
            <div class="bg-white rounded-xl shadow">
                <div class="p-4 border-b border-gray-100">
                    <input type="text" id="searchInput" placeholder="Search users..." class="form-input max-w-xs" oninput="filterTable()">
                </div>
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Department</th>
                                <th>Status</th>
                                <th>Actions</th>
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
            <h3 class="font-semibold text-gray-800" id="modalTitle">Add User</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
        </div>
        <form id="userForm">
            <div class="modal-body space-y-4">
                <input type="hidden" id="user_id" name="id">
                <input type="hidden" name="action" value="save">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" id="u_name" class="form-input" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Username <span class="text-red-500">*</span></label>
                        <input type="text" name="username" id="u_username" class="form-input" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" id="u_email" class="form-input">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Role <span class="text-red-500">*</span></label>
                        <select name="role" id="u_role" class="form-select" onchange="toggleDeptField()" required>
                            <option value="admin">Administrator</option>
                            <option value="quality_officer">Quality Officer</option>
                            <option value="department_incharge">Dept. In-charge</option>
                        </select>
                    </div>
                    <div id="deptField">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                        <select name="department_id" id="u_dept" class="form-select">
                            <option value="">— None —</option>
                            <?php foreach ($depts as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" id="u_status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div id="passwordSection">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password <span id="pwdRequired" class="text-red-500">*</span></label>
                    <input type="password" name="password" id="u_password" class="form-input" placeholder="Leave blank to keep existing (on edit)">
                    <p class="text-xs text-gray-400 mt-1" id="pwdHint"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Save User</button>
            </div>
        </form>
    </div>
</div>

<script>
const BASE = '<?= BASE_URL ?>';

function loadUsers() {
    fetch(BASE + '/ajax/users.php?action=list')
        .then(r => r.json())
        .then(data => {
            const tbody = document.getElementById('tableBody');
            if (!data.success || !data.data.length) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-8 text-gray-400">No users found.</td></tr>';
                return;
            }
            const roleBadge = {admin:'badge-admin', quality_officer:'badge-quality', department_incharge:'badge-incharge'};
            const roleLabel = {admin:'Admin', quality_officer:'Quality Officer', department_incharge:'Dept. Incharge'};
            tbody.innerHTML = data.data.map(d => `
                <tr data-name="${(d.name+' '+d.username).toLowerCase()}">
                    <td class="font-medium">${esc(d.name)}</td>
                    <td class="font-mono text-sm text-gray-600">${esc(d.username)}</td>
                    <td class="text-sm">${esc(d.email||'—')}</td>
                    <td><span class="badge ${roleBadge[d.role]||''}">${roleLabel[d.role]||d.role}</span></td>
                    <td class="text-sm">${esc(d.department_name||'—')}</td>
                    <td><span class="badge badge-${d.status}">${d.status}</span></td>
                    <td>
                        <div class="flex items-center gap-2">
                            <button onclick="editUser(${JSON.stringify(d).replace(/"/g,'&quot;')})" class="btn btn-sm btn-outline btn-outline-primary">Edit</button>
                            <button onclick="toggleStatus(${d.id},'${d.status}')" class="btn btn-sm ${d.status==='active'?'btn-warning':'btn-success'}">${d.status==='active'?'Deactivate':'Activate'}</button>
                        </div>
                    </td>
                </tr>
            `).join('');
        });
}

function esc(str) { if (!str) return ''; const d = document.createElement('div'); d.textContent = str; return d.innerHTML; }

function filterTable() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('#tableBody tr[data-name]').forEach(r => {
        r.style.display = r.dataset.name.includes(q) ? '' : 'none';
    });
}

function toggleDeptField() {
    const role = document.getElementById('u_role').value;
    document.getElementById('deptField').style.display = role === 'department_incharge' ? '' : 'none';
}

let isEdit = false;
function openModal(reset=true) {
    if (reset) {
        isEdit = false;
        document.getElementById('userForm').reset();
        document.getElementById('user_id').value = '';
        document.getElementById('modalTitle').textContent = 'Add User';
        document.getElementById('pwdRequired').textContent = '*';
        document.getElementById('pwdHint').textContent = '';
        document.getElementById('u_password').required = true;
    }
    toggleDeptField();
    document.getElementById('modal').classList.remove('hidden');
}

function closeModal() { document.getElementById('modal').classList.add('hidden'); }

function editUser(d) {
    isEdit = true;
    document.getElementById('user_id').value  = d.id;
    document.getElementById('u_name').value   = d.name;
    document.getElementById('u_username').value = d.username;
    document.getElementById('u_email').value  = d.email || '';
    document.getElementById('u_role').value   = d.role;
    document.getElementById('u_dept').value   = d.department_id || '';
    document.getElementById('u_status').value = d.status;
    document.getElementById('u_password').value = '';
    document.getElementById('u_password').required = false;
    document.getElementById('pwdRequired').textContent = '';
    document.getElementById('pwdHint').textContent = 'Leave blank to keep current password.';
    document.getElementById('modalTitle').textContent = 'Edit User';
    openModal(false);
}

function toggleStatus(id, current) {
    if (!confirm('Toggle status for this user?')) return;
    fetch(BASE + '/ajax/users.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=toggle_status&id=${id}`
    }).then(r => r.json()).then(d => {
        showToast(d.message, d.success ? 'success' : 'error');
        if (d.success) loadUsers();
    });
}

document.getElementById('userForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    fetch(BASE + '/ajax/users.php', {
        method: 'POST', body: new URLSearchParams(fd)
    }).then(r => r.json()).then(d => {
        showToast(d.message, d.success ? 'success' : 'error');
        if (d.success) { closeModal(); loadUsers(); }
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

loadUsers();
</script>
<script>window.APP_BASE='<?= BASE_URL ?>';</script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
