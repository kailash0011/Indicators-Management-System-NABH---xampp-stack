<?php
if (!isset($curPage)) $curPage = '';
if (!isset($user)) $user = getCurrentUser();
?>
<aside class="w-64 bg-teal-900 text-white flex flex-col fixed h-full z-10">
    <div class="p-5 border-b border-teal-800">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 bg-teal-600 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <div>
                <p class="font-bold text-sm leading-tight">NABH IMS</p>
                <p class="text-teal-300 text-xs"><?= htmlspecialchars($user['department_name'] ?? 'Department') ?></p>
            </div>
        </div>
    </div>
    <nav class="flex-1 p-4 space-y-1">
        <a href="<?= BASE_URL ?>/incharge/dashboard.php" class="nav-link <?= $curPage==='dashboard'?'active':'' ?>" style="<?= $curPage==='dashboard'?'background:rgba(255,255,255,0.2);color:#fff;font-weight:600':'color:#99f6e4' ?>">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            Dashboard
        </a>
        <a href="<?= BASE_URL ?>/incharge/data_entry.php" class="nav-link <?= $curPage==='data_entry'?'active':'' ?>" style="<?= $curPage==='data_entry'?'background:rgba(255,255,255,0.2);color:#fff;font-weight:600':'color:#99f6e4' ?>">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Data Entry
        </a>
        <a href="<?= BASE_URL ?>/incharge/history.php" class="nav-link <?= $curPage==='history'?'active':'' ?>" style="<?= $curPage==='history'?'background:rgba(255,255,255,0.2);color:#fff;font-weight:600':'color:#99f6e4' ?>">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            History
        </a>
        <a href="<?= BASE_URL ?>/incharge/trends.php" class="nav-link <?= $curPage==='trends'?'active':'' ?>" style="<?= $curPage==='trends'?'background:rgba(255,255,255,0.2);color:#fff;font-weight:600':'color:#99f6e4' ?>">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
            Trends
        </a>
    </nav>
    <div class="p-4 border-t border-teal-800">
        <p class="text-sm font-medium text-white truncate"><?= htmlspecialchars($user['name']) ?></p>
        <p class="text-xs text-teal-300 mb-3">Dept. In-charge</p>
        <a href="<?= BASE_URL ?>/logout.php" class="flex items-center gap-2 text-xs text-teal-300 hover:text-white transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            Logout
        </a>
    </div>
</aside>
