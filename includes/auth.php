<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

function requireRole($roles) {
    requireLogin();
    if (!is_array($roles)) $roles = [$roles];
    if (!in_array($_SESSION['role'], $roles)) {
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit;
    }
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isQualityOfficer() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'quality_officer';
}

function isDepartmentIncharge() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'department_incharge';
}

function canManageAll() {
    return isAdmin() || isQualityOfficer();
}

function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) return null;
    return [
        'id'              => $_SESSION['user_id'],
        'username'        => $_SESSION['username'],
        'name'            => $_SESSION['name'],
        'role'            => $_SESSION['role'],
        'department_id'   => $_SESSION['department_id'] ?? null,
        'department_name' => $_SESSION['department_name'] ?? null,
    ];
}

function login($username, $password) {
    $pdo = getDB();
    $stmt = $pdo->prepare(
        "SELECT u.*, d.name as department_name
         FROM users u
         LEFT JOIN departments d ON u.department_id = d.id
         WHERE u.username = ? AND u.status = 'active'"
    );
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']        = $user['id'];
        $_SESSION['username']       = $user['username'];
        $_SESSION['name']           = $user['name'];
        $_SESSION['role']           = $user['role'];
        $_SESSION['department_id']  = $user['department_id'];
        $_SESSION['department_name']= $user['department_name'];
        $_SESSION['csrf_token']     = bin2hex(random_bytes(32));
        return true;
    }
    return false;
}

function logout() {
    session_destroy();
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

function verifyCsrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function getCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
