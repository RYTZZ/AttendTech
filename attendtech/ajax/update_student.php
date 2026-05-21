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
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$pdo = getPDO();
$id = (int)($_POST['id'] ?? 0);
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
$stmt->execute([$id]);
$student = $stmt->fetch();
if (!$student) {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit;
}

$lrn = trim($_POST['lrn'] ?? '');
$full_name = trim($_POST['full_name'] ?? '');
$gender = $_POST['gender'] ?? '';
$grade_level_id = (int)($_POST['grade_level_id'] ?? 0);
$section_id = (int)($_POST['section_id'] ?? 0);
$contact_number = trim($_POST['contact_number'] ?? '');
$address = trim($_POST['address'] ?? '');
$parent_name = trim($_POST['parent_name'] ?? '');
$parent_contact = trim($_POST['parent_contact'] ?? '');
$status = $_POST['status'] ?? 'Active';

$errors = [];
if (!$lrn || strlen($lrn) !== 12) $errors[] = 'LRN must be 12 digits.';
if (!$full_name) $errors[] = 'Full name required.';
if (!in_array($gender, ['Male', 'Female'])) $errors[] = 'Gender required.';
if (!$grade_level_id) $errors[] = 'Grade level required.';
if (!$section_id) $errors[] = 'Section required.';

if ($errors) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

$check = $pdo->prepare('SELECT id FROM students WHERE lrn = ? AND id != ?');
$check->execute([$lrn, $id]);
if ($check->fetch()) {
    echo json_encode(['success' => false, 'message' => 'LRN already used by another student.']);
    exit;
}

$photoPath = $student['photo'];
if (!empty($_FILES['photo']['name'])) {
    $upload = uploadStudentPhoto($_FILES['photo']);
    if (!$upload['success']) {
        echo json_encode(['success' => false, 'message' => $upload['message']]);
        exit;
    }
    $photoPath = $upload['filename'];
}

$qrPath = $student['qr_code'];
if ($lrn !== $student['lrn']) {
    $qrPath = generateQRCode($lrn);
}

$upd = $pdo->prepare("
    UPDATE students SET lrn=?, full_name=?, gender=?, grade_level_id=?, section_id=?,
    contact_number=?, address=?, parent_name=?, parent_contact=?, photo=?, qr_code=?, status=?
    WHERE id=?
");
$upd->execute([$lrn, $full_name, $gender, $grade_level_id, $section_id, $contact_number, $address, $parent_name, $parent_contact, $photoPath, $qrPath, $status, $id]);

echo json_encode(['success' => true, 'message' => 'Student updated successfully.']);
