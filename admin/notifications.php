<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/notifications.php';
requireRole(['admin','quality_officer']);

$user    = getCurrentUser();
$curPage = 'notifications';
$curMonth = (int)date('n');
$curYear  = (int)date('Y');
$threshold = (float)getSetting('compliance_threshold', '70');
$enabled   = getSetting('alerts_enabled', '1') === '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerts &amp; Notifications - NABH Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="bg-gray-100">
<div id="toast-container"></div>
<div class="flex h-screen overflow-hidden">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="flex-1 ml-64 overflow-y-auto">
        <header class="bg-white shadow-sm px-6 py-4 flex items-center justify-between sticky top-0 z-10">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Alerts &amp; Notifications</h1>
                <p class="text-xs text-gray-500">Compliance threshold alerts for all departments</p>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="markAllRead()" class="btn btn-secondary btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Mark all read
                </button>
                <button onclick="openSettings()" class="btn btn-outline btn-outline-primary btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg>
                    Settings
                </button>
            </div>
        </header>

        <div class="p-6 space-y-5">

            <!-- Run Check Panel -->
            <div class="bg-white rounded-xl shadow p-5">
                <div class="flex flex-wrap items-center gap-4">
                    <div>
                        <h2 class="font-semibold text-gray-800">Run Compliance Check</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Scan all departments for the selected month and generate alerts for those below <span id="thresholdDisplay"><?= $threshold ?>%</span></p>
                    </div>
                    <div class="flex items-center gap-2 ml-auto">
                        <select id="chkMonth" class="form-select w-36 text-sm">
                            <?php
                            $mnames = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
                            for ($m=1;$m<=12;$m++): ?>
                            <option value="<?= $m ?>" <?= $m==$curMonth?'selected':'' ?>><?= $mnames[$m] ?></option>
                            <?php endfor; ?>
                        </select>
                        <select id="chkYear" class="form-select w-24 text-sm">
                            <?php for ($y=$curYear;$y>=$curYear-4;$y--): ?>
                            <option value="<?= $y ?>"><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                        <button onclick="runCheck()" id="runCheckBtn" class="btn btn-primary btn-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Check Now
                        </button>
                    </div>
                </div>
                <div id="checkResult" class="hidden mt-3 p-3 rounded-lg text-sm"></div>
            </div>

            <!-- Filter Bar -->
            <div class="flex items-center gap-3">
                <button onclick="loadNotifications('all')"    id="fAll"    class="btn btn-sm btn-primary">All</button>
                <button onclick="loadNotifications('unread')" id="fUnread" class="btn btn-sm btn-outline btn-outline-primary">Unread</button>
                <span class="text-xs text-gray-400 ml-auto" id="notifMeta"></span>
            </div>

            <!-- Notification List -->
            <div id="notifList" class="space-y-3">
                <div class="text-center py-10 text-gray-400">Loading…</div>
            </div>

        </div>
    </main>
</div>

<!-- Settings Modal -->
<div id="settingsModal" class="modal-overlay hidden">
    <div class="modal-box max-w-md">
        <div class="modal-header">
            <h3 class="font-semibold text-gray-800">Alert Settings</h3>
            <button onclick="closeSettings()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="modal-body space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Alerts Enabled</label>
                <div class="flex items-center gap-3">
                    <input type="checkbox" id="settEnabled" class="w-4 h-4 accent-blue-600" <?= $enabled ? 'checked' : '' ?>>
                    <span class="text-sm text-gray-600">Generate alerts when compliance drops below threshold</span>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Compliance Threshold: <span id="thresholdVal" class="text-blue-600 font-bold"><?= $threshold ?>%</span>
                </label>
                <input type="range" id="settThreshold" min="10" max="100" step="5" value="<?= $threshold ?>"
                    oninput="document.getElementById('thresholdVal').textContent=this.value+'%'"
                    class="w-full accent-blue-600">
                <div class="flex justify-between text-xs text-gray-400 mt-1"><span>10%</span><span>100%</span></div>
                <p class="text-xs text-gray-400 mt-2">An alert is created when a department's percentage of met-target indicators falls below this value for a given month.</p>
            </div>
        </div>
        <div class="modal-footer">
            <button onclick="closeSettings()" class="btn btn-secondary btn-sm">Cancel</button>
            <button onclick="saveSettings()" class="btn btn-primary btn-sm">Save Settings</button>
        </div>
    </div>
</div>

<script>
const BASE = '<?= BASE_URL ?>';
const MONTHS = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
let currentFilter = 'all';

function loadNotifications(filter) {
    currentFilter = filter || 'all';
    document.getElementById('fAll').className    = 'btn btn-sm ' + (currentFilter==='all'    ? 'btn-primary' : 'btn-outline btn-outline-primary');
    document.getElementById('fUnread').className = 'btn btn-sm ' + (currentFilter==='unread' ? 'btn-primary' : 'btn-outline btn-outline-primary');

    const list = document.getElementById('notifList');
    list.innerHTML = '<div class="text-center py-8 text-gray-400">Loading…</div>';

    fetch(`${BASE}/ajax/notifications.php?action=list&filter=${currentFilter}`)
        .then(r => r.json())
        .then(d => {
            if (!d.success) { list.innerHTML = '<div class="text-center py-8 text-red-400">Failed to load.</div>'; return; }
            renderNotifications(d.data);
        });
}

function renderNotifications(items) {
    const list = document.getElementById('notifList');
    const unread = items.filter(i => !parseInt(i.is_read)).length;
    document.getElementById('notifMeta').textContent =
        `${items.length} notification${items.length!==1?'s':''} · ${unread} unread`;

    if (!items.length) {
        list.innerHTML = `<div class="bg-white rounded-xl shadow p-10 text-center">
            <div class="w-16 h-16 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-3">
                <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <p class="text-gray-500 font-medium">All clear — no alerts</p>
            <p class="text-gray-400 text-sm mt-1">Run a compliance check to scan for departments below the threshold.</p>
        </div>`;
        return;
    }

    list.innerHTML = items.map(item => {
        const isUnread = !parseInt(item.is_read);
        const pct      = item.compliance_pct !== null ? parseFloat(item.compliance_pct) : null;
        const pctColor = pct === null ? 'text-gray-500' : pct >= 70 ? 'text-emerald-600' : pct >= 50 ? 'text-amber-600' : 'text-red-600';
        const pctBg    = pct === null ? 'bg-gray-100' : pct >= 70 ? 'bg-emerald-50' : pct >= 50 ? 'bg-amber-50' : 'bg-red-50';

        return `<div class="bg-white rounded-xl shadow ${isUnread ? 'border-l-4 border-red-400' : 'border-l-4 border-gray-200'} p-5 flex items-start gap-4" id="notif-${item.id}">
            <div class="w-10 h-10 rounded-full ${pctBg} flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg class="w-5 h-5 ${pctColor}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-2">
                    <p class="font-semibold text-gray-800 ${isUnread ? '' : 'text-gray-600'}">${esc(item.title)}</p>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        ${pct !== null ? `<span class="text-sm font-bold ${pctColor}">${pct}%</span>` : ''}
                        ${isUnread ? `<span class="badge" style="background:#fee2e2;color:#b91c1c;font-size:0.65rem">UNREAD</span>` : ''}
                    </div>
                </div>
                <p class="text-sm text-gray-500 mt-1">${esc(item.message)}</p>
                <div class="flex items-center gap-4 mt-2">
                    <span class="text-xs text-gray-400">${MONTHS[parseInt(item.month)] || ''} ${item.year} · ${formatDate(item.created_at)}</span>
                    ${isUnread ? `<button onclick="markRead(${item.id})" class="text-xs text-blue-600 hover:underline">Mark as read</button>` : ''}
                </div>
            </div>
        </div>`;
    }).join('');
}

function markRead(id) {
    fetch(`${BASE}/ajax/notifications.php?action=mark_read`, {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `id=${id}`
    }).then(r=>r.json()).then(d => {
        if (d.success) {
            loadNotifications(currentFilter);
            updateBell(d.data.unread);
        }
    });
}

function markAllRead() {
    fetch(`${BASE}/ajax/notifications.php?action=mark_all_read`, { method:'POST' })
        .then(r=>r.json()).then(d => {
            if (d.success) { loadNotifications(currentFilter); updateBell(0); showToast('All notifications marked as read','success'); }
        });
}

function runCheck() {
    const month  = document.getElementById('chkMonth').value;
    const year   = document.getElementById('chkYear').value;
    const btn    = document.getElementById('runCheckBtn');
    const result = document.getElementById('checkResult');
    btn.disabled = true;
    btn.textContent = 'Checking…';
    result.className = 'hidden mt-3 p-3 rounded-lg text-sm';

    fetch(`${BASE}/ajax/notifications.php?action=run_check`, {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `month=${month}&year=${year}`
    }).then(r=>r.json()).then(d => {
        btn.disabled = false;
        btn.innerHTML = `<svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Check Now`;
        if (d.success) {
            result.className = `mt-3 p-3 rounded-lg text-sm ${d.data.generated > 0 ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-green-50 text-green-700 border border-green-200'}`;
            result.textContent = d.message;
            result.classList.remove('hidden');
            updateBell(d.data.unread);
            loadNotifications(currentFilter);
        }
    });
}

function openSettings()  { document.getElementById('settingsModal').classList.remove('hidden'); }
function closeSettings() { document.getElementById('settingsModal').classList.add('hidden'); }

function saveSettings() {
    const threshold = document.getElementById('settThreshold').value;
    const enabled   = document.getElementById('settEnabled').checked ? 1 : 0;
    fetch(`${BASE}/ajax/notifications.php?action=save_settings`, {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `threshold=${threshold}&enabled=${enabled}`
    }).then(r=>r.json()).then(d => {
        if (d.success) {
            closeSettings();
            document.getElementById('thresholdDisplay').textContent = threshold + '%';
            showToast('Settings saved', 'success');
        }
    });
}

function updateBell(count) {
    const badge = document.getElementById('notif-badge');
    if (!badge) return;
    badge.textContent = count;
    badge.style.display = count > 0 ? 'flex' : 'none';
}

function formatDate(dt) {
    if (!dt) return '';
    const d = new Date(dt.replace(' ','T'));
    return d.toLocaleDateString('en-IN', {day:'numeric',month:'short',hour:'2-digit',minute:'2-digit'});
}

function esc(s) {
    if (!s) return '';
    const d = document.createElement('div'); d.textContent = String(s); return d.innerHTML;
}

function showToast(msg, type='info') {
    const c = document.getElementById('toast-container');
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.textContent = msg;
    c.appendChild(t);
    setTimeout(() => { t.style.animation='fadeOut 0.3s forwards'; setTimeout(()=>t.remove(),300); }, 3000);
}

document.addEventListener('DOMContentLoaded', () => loadNotifications('all'));
</script>
<script>window.APP_BASE='<?= BASE_URL ?>';</script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
