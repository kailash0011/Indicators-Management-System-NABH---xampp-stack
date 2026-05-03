<?php
/**
 * CLI script to create default users for the NABH system.
 * Run via: php run_setup.php
 */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'nabh_indicators');

$socket = '/home/runner/mysql_run/mysql.sock';

try {
    $pdo = new PDO(
        "mysql:unix_socket=$socket;dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("DB connection failed: " . $e->getMessage() . "\n");
}

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
    echo "Created/updated user: $uname\n";
}

echo "All users ready.\n";
