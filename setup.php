<?php
/**
 * NABH Indicators Management System - Setup / Installer
 * Run this once to create database, tables, seed data, and users.
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'nabh_indicators');
define('BASE_URL', '/nabh');

$steps   = [];
$success = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_setup'])) {

    /* ── Step 1: Connect without DB to create it ── */
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";charset=utf8mb4",
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $steps[] = ['ok', 'Connected to MySQL server.'];
    } catch (PDOException $e) {
        $steps[] = ['fail', 'MySQL connection failed: ' . $e->getMessage()];
        $success = false;
        goto render;
    }

    /* ── Step 2: Execute schema.sql ── */
    try {
        $schema = file_get_contents(__DIR__ . '/sql/schema.sql');
        if ($schema === false) throw new Exception('Cannot read sql/schema.sql');
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `" . DB_NAME . "`");
        // Split on semicolons but skip empty
        $queries = array_filter(array_map('trim', explode(';', $schema)));
        foreach ($queries as $q) {
            if ($q !== '') $pdo->exec($q);
        }
        $steps[] = ['ok', 'Database and tables created successfully.'];
    } catch (Exception $e) {
        $steps[] = ['fail', 'Schema error: ' . $e->getMessage()];
        $success = false;
        goto render;
    }

    /* ── Step 3: Execute seed.sql ── */
    try {
        $seed = file_get_contents(__DIR__ . '/sql/seed.sql');
        if ($seed === false) throw new Exception('Cannot read sql/seed.sql');
        // Remove the USE statement (already done) and split
        $seed = preg_replace('/^USE\s+\w+;/mi', '', $seed);
        $queries = array_filter(array_map('trim', explode(';', $seed)));
        foreach ($queries as $q) {
            if ($q !== '') $pdo->exec($q);
        }
        $steps[] = ['ok', 'Departments, indicators, and assignments seeded successfully.'];
    } catch (Exception $e) {
        $steps[] = ['fail', 'Seed error: ' . $e->getMessage()];
        $success = false;
        goto render;
    }

    /* ── Step 4: Create users ── */
    try {
        $users = [
            ['admin',          'admin123',  'Admin User',           'admin',               null],
            ['quality',        'quality123','Quality Officer',       'quality_officer',     null],
            ['med_incharge',   'dept123',   'Dr. Medicine Head',    'department_incharge', 1],
            ['surg_incharge',  'dept123',   'Dr. Surgery Head',     'department_incharge', 2],
            ['obg_incharge',   'dept123',   'Dr. OBG Head',         'department_incharge', 3],
            ['paed_incharge',  'dept123',   'Dr. Paediatrics Head', 'department_incharge', 4],
            ['emrg_incharge',  'dept123',   'Dr. Emergency Head',   'department_incharge', 5],
            ['icu_incharge',   'dept123',   'Dr. ICU Head',         'department_incharge', 6],
            ['ot_incharge',    'dept123',   'Dr. OT Head',          'department_incharge', 7],
            ['lab_incharge',   'dept123',   'Lab Head',             'department_incharge', 8],
            ['rad_incharge',   'dept123',   'Radiology Head',       'department_incharge', 9],
            ['bb_incharge',    'dept123',   'Blood Bank Head',      'department_incharge', 10],
            ['pharm_incharge', 'dept123',   'Pharmacy Head',        'department_incharge', 11],
            ['cssd_incharge',  'dept123',   'CSSD Head',            'department_incharge', 12],
            ['diet_incharge',  'dept123',   'Dietetics Head',       'department_incharge', 13],
            ['dial_incharge',  'dept123',   'Dialysis Head',        'department_incharge', 14],
            ['nicu_incharge',  'dept123',   'NICU Head',            'department_incharge', 15],
        ];

        $stmt = $pdo->prepare("
            INSERT INTO users (username, password, name, role, department_id, status)
            VALUES (?, ?, ?, ?, ?, 'active')
            ON DUPLICATE KEY UPDATE
                password=VALUES(password), name=VALUES(name),
                role=VALUES(role), department_id=VALUES(department_id)
        ");

        foreach ($users as [$uname, $pass, $name, $role, $dept]) {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $stmt->execute([$uname, $hash, $name, $role, $dept]);
        }
        $steps[] = ['ok', count($users) . ' users created/updated successfully.'];
    } catch (Exception $e) {
        $steps[] = ['fail', 'User creation error: ' . $e->getMessage()];
        $success = false;
        goto render;
    }

    $steps[] = ['ok', 'Setup completed successfully! You can now <a href="' . BASE_URL . '/index.php" class="text-blue-600 underline font-medium">login here</a>.'];
}

render:
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - NABH Indicators Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-4">
    <div class="w-full max-w-2xl">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-600 rounded-full mb-3 shadow-lg">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">NABH Indicators Management System</h1>
            <p class="text-gray-500 text-sm mt-1">Database Setup &amp; Installation</p>
        </div>

        <!-- Card -->
        <div class="bg-white rounded-2xl shadow-xl p-8">

            <?php if (!empty($steps)): ?>
            <!-- Results -->
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Setup Results</h2>
            <div class="space-y-3 mb-6">
                <?php foreach ($steps as [$status, $msg]): ?>
                <div class="flex items-start gap-3 p-3 rounded-lg <?= $status === 'ok' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' ?>">
                    <?php if ($status === 'ok'): ?>
                    <svg class="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm text-green-800"><?= $msg ?></span>
                    <?php else: ?>
                    <svg class="w-5 h-5 text-red-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm text-red-800"><?= htmlspecialchars($msg) ?></span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($success): ?>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <h3 class="font-semibold text-blue-800 mb-2">Default Login Credentials</h3>
                <div class="grid grid-cols-1 gap-1 text-sm text-blue-700">
                    <p><strong>Admin:</strong> admin / admin123</p>
                    <p><strong>Quality Officer:</strong> quality / quality123</p>
                    <p><strong>Department Incharges:</strong> [dept]_incharge / dept123 (e.g., med_incharge)</p>
                </div>
            </div>
            <a href="<?= BASE_URL ?>/index.php"
               class="block w-full text-center bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 rounded-lg transition">
                Proceed to Login
            </a>
            <?php else: ?>
            <form method="POST">
                <button type="submit" name="run_setup"
                        class="block w-full text-center bg-orange-600 hover:bg-orange-700 text-white font-medium py-3 rounded-lg transition">
                    Retry Setup
                </button>
            </form>
            <?php endif; ?>

            <?php else: ?>
            <!-- Initial screen -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-700 mb-3">Pre-Installation Checklist</h2>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        MySQL server running (XAMPP)
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        PHP PDO MySQL extension enabled
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Project placed in <code class="bg-gray-100 px-1 rounded">htdocs/nabh/</code>
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        MySQL root password is empty (default XAMPP)
                    </li>
                </ul>
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-yellow-800">
                    <strong>Warning:</strong> Running setup will create the database <code>nabh_indicators</code> and
                    insert all seed data. If run again, existing users will be updated with reset passwords.
                    Other data (departments, indicators, monthly entries) will not be duplicated due to unique constraints.
                </p>
            </div>

            <form method="POST">
                <button type="submit" name="run_setup"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition shadow-md hover:shadow-lg text-base">
                    Run Setup Now
                </button>
            </form>
            <?php endif; ?>
        </div>

        <p class="text-center text-xs text-gray-400 mt-4">
            &copy; <?= date('Y') ?> NABH Indicators Management System
        </p>
    </div>
</body>
</html>
