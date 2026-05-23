<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';
logoutAdmin();
header('Location: ' . BASE_URL . '/login.php');
exit;
