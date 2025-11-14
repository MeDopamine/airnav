<?php
// Simple auth helper
session_start();
require_once __DIR__ . '/db/db.php';

function is_logged_in() {
    return !empty($_SESSION['user_id']);
}

function current_user() {
    if (!is_logged_in()) return null;
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'name' => $_SESSION['user_name'] ?? null,
        'email' => $_SESSION['user_email'] ?? null,
        'role' => $_SESSION['user_role'] ?? 'user'
    ];
}

function require_login() {
    if (!is_logged_in()) {
        // redirect to login page
        header('Location: /login.php');
        exit;
    }
}

function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function is_admintl() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admintl';
}

function is_admin_or_admintl() {
    $role = $_SESSION['user_role'] ?? null;
    return $role === 'admin' || $role === 'admintl';
}

function require_admin() {
    if (!is_admin()) {
        http_response_code(403);
        echo 'Terbatas: akses admin diperlukan.';
        exit;
    }
}

function require_admin_or_admintl() {
    if (!is_admin_or_admintl()) {
        http_response_code(403);
        echo 'Terbatas: akses admin diperlukan.';
        exit;
    }
}

// Helper to log in a user given DB row
function login_user_from_row($row) {
    // $row should contain id, name, email, role
    $_SESSION['user_id'] = $row['id'];
    $_SESSION['user_name'] = $row['name'];
    $_SESSION['user_email'] = $row['email'];
    $_SESSION['user_role'] = $row['role'];
}

?>
