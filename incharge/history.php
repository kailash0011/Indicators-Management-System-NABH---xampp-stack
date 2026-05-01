<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['department_incharge']);
$user    = getCurrentUser();
$curPage = 'history';
$deptId  = $user['department_id'];
$selYear = (int)($_GET['year'] ?? date('Y'));
$indicators = getDepartmentIndicators($deptId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History - NABH</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        .nav-link { color: #99f6e4; }
        .nav-link:hover, .nav-link.active { background:rgba(255,255,255,0.15); color:#fff; font-weight:600; }
    </style>
</head>
<body class="bg-gray-100">
<div class="flex h-screen overflow-hidden">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <main class="flex-1 ml-64 overflow-y-auto">
        <header class="bg-white shadow-sm px-6 py-4 flex items-center justify-between sticky top-0 z-5 no-print">
            <div>
                <h1 class="text-xl font-bold text-gray-800">History</h1>
                <p class="text-xs text-gray-500"><?= htmlspecialchars($user['department_name']) ?></p>
            </div>
            <div class="flex items-center gap-3">
                <form method="GET" class="flex items-center gap-2">
                    <select name="year" class="form-select text-sm" onchange="this.form.submit()">
                        <?php for ($y=date('Y');$y>=date('Y')-5;$y--): ?>
                        <option value="<?= $y ?>" <?= $y==$selYear?'selected':'' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </form>
                <button onclick="window.print()" class="btn btn-secondary btn-sm no-print">Print</button>
            </div>
        </header>
        <div class="p-6">
            <div class="bg-white rounded-xl shadow overflow-hidden print-full">
                <div class="p-4 border-b border-gray-100 text-center print-full">
                    <h2 class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($user['department_name']) ?> — Annual Report <?= $selYear ?></h2>
                    <p class="text-xs text-gray-400"><?= htmlspecialchars(HOSPITAL_NAME) ?></p>
                </div>
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="min-width:60px">Code</th>
                                <th style="min-width:200px">Indicator</th>
                                <th>Unit</th>
                                <th>Benchmark</th>
                                <?php for ($m=1;$m<=12;$m++): ?>
                                <th style="min-width:55px"><?= substr(getMonthName($m),0,3) ?></th>
                                <?php endfor; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($indicators)): ?>
                            <tr><td colspan="16" class="text-center py-8 text-gray-400">No indicators assigned.</td></tr>
                            <?php else: ?>
                            <?php foreach ($indicators as $ind): ?>
                            <?php $monthly = getMonthlyData($deptId, $ind['id'], $selYear); ?>
                            <tr>
                                <td class="font-mono text-xs font-semibold text-blue-700"><?= htmlspecialchars($ind['indicator_code']) ?></td>
                                <td class="text-sm font-medium"><?= htmlspecialchars($ind['name']) ?></td>
                                <td class="text-xs text-gray-500"><?= htmlspecialchars($ind['unit']) ?></td>
                                <td class="text-xs text-gray-500"><?= htmlspecialchars($ind['benchmark'] ?? '—') ?></td>
                                <?php for ($m=1;$m<=12;$m++): ?>
                                <td class="text-center text-sm">
                                    <?php if (isset($monthly[$m])): ?>
                                    <span class="font-semibold text-teal-700"><?= formatValue($monthly[$m]['value'], $ind['unit']) ?></span>
                                    <?php else: ?>
                                    <span class="text-gray-200">—</span>
                                    <?php endif; ?>
                                </td>
                                <?php endfor; ?>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>
