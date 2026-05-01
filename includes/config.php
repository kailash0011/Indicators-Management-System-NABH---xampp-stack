<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'nabh_indicators');
define('HOSPITAL_NAME', 'City General Hospital');
define('HOSPITAL_ADDRESS', '123 Healthcare Avenue, Medical District');
define('HOSPITAL_PHONE', '+91-0000-000000');
define('HOSPITAL_ACCREDITATION', 'NABH Accredited Hospital');
define('BASE_URL', '/nabh');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    return $pdo;
}

/**
 * Returns true when the database connection succeeds and all required
 * tables already exist; returns false otherwise (DB not created yet or
 * tables missing — prompting the setup wizard).
 *
 * Note: a dedicated connection attempt is used here (rather than getDB())
 * because getDB() calls die() on failure, making it impossible to recover
 * gracefully when the database does not exist yet.
 */
function isDbReady() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $tables = $pdo->query("SHOW TABLES LIKE 'users'")->rowCount()
                + $pdo->query("SHOW TABLES LIKE 'departments'")->rowCount();
        return $tables === 2;
    } catch (PDOException $e) {
        return false;
    }
}
