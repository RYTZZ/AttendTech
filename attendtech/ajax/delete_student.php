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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
    exit;
}

$pdo = getPDO();
$stmt = $pdo->prepare("SELECT id, full_name, photo, qr_code FROM students WHERE id = ?");
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit;
}

if ($student['photo'] && file_exists(BASE_PATH . '/' . $student['photo'])) {
    @unlink(BASE_PATH . '/' . $student['photo']);
}
if ($student['qr_code'] && file_exists(BASE_PATH . '/' . $student['qr_code'])) {
    @unlink(BASE_PATH . '/' . $student['qr_code']);
}

$del = $pdo->prepare("DELETE FROM students WHERE id = ?");
$del->execute([$id]);

echo json_encode(['success' => true, 'message' => $student['full_name'] . ' has been deleted.']);
