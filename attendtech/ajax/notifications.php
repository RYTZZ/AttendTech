<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/includes/session.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'fetch') {
    $notifications = getUnreadNotifications();
    echo json_encode(['success' => true, 'notifications' => $notifications, 'count' => count($notifications)]);
    exit;
}

if ($action === 'mark_all_read' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    markAllNotificationsRead();
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'count') {
    echo json_encode(['success' => true, 'count' => getUnreadNotificationCount()]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
