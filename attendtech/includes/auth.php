<?php
require_once __DIR__ . '/session.php';

function requireLogin() {
    if (empty($_SESSION['admin_id'])) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function isLoggedIn() {
    return !empty($_SESSION['admin_id']);
}

function loginAdmin($id, $username, $full_name) {
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $id;
    $_SESSION['admin_username'] = $username;
    $_SESSION['admin_name'] = $full_name;
}

function logoutAdmin() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}
