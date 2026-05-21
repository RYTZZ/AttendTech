<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/includes/session.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$lrn = trim($_POST['lrn'] ?? '');
if (!$lrn) {
    echo json_encode(['success' => false, 'message' => 'No LRN received.', 'lrn' => '']);
    exit;
}

$pdo = getPDO();
$settings = getAllSettings();
$lateThreshold = $settings['late_time_threshold'] ?? '07:30:00';

$stmt = $pdo->prepare("
    SELECT s.*, gl.grade_name, sec.section_name
    FROM students s
    JOIN grade_levels gl ON s.grade_level_id = gl.id
    JOIN sections sec ON s.section_id = sec.id
    WHERE s.lrn = ? AND s.status = 'Active'
");
$stmt->execute([$lrn]);
$student = $stmt->fetch();

if (!$student) {
    echo json_encode(['success' => false, 'message' => 'Student not found or inactive.', 'lrn' => $lrn]);
    exit;
}

$today = date('Y-m-d');
$nowTime = date('H:i:s');
$nowFormatted = date('h:i:s A');

$logStmt = $pdo->prepare("SELECT * FROM attendance_logs WHERE student_id = ? AND log_date = ?");
$logStmt->execute([$student['id'], $today]);
$existingLog = $logStmt->fetch();

if (!$existingLog) {
    $status = (strtotime($nowTime) > strtotime($lateThreshold)) ? 'Late' : 'Present';
    $remarks = $status === 'Late' ? 'Arrived late' : '';

    $insertStmt = $pdo->prepare("
        INSERT INTO attendance_logs (student_id, log_date, time_in, status, remarks)
        VALUES (?, ?, ?, ?, ?)
    ");
    $insertStmt->execute([$student['id'], $today, $nowTime, $status, $remarks]);

    $notifType = $status === 'Late' ? 'late' : 'time_in';
    $notifMsg = $student['full_name'] . ' scanned TIME IN at ' . $nowFormatted;
    if ($status === 'Late') $notifMsg .= ' (Late)';

    $notifStmt = $pdo->prepare("INSERT INTO notifications (student_id, message, type) VALUES (?, ?, ?)");
    $notifStmt->execute([$student['id'], $notifMsg, $notifType]);

    echo json_encode([
        'success' => true,
        'type' => 'time_in',
        'status' => $status,
        'time' => $nowFormatted,
        'student' => [
            'full_name' => $student['full_name'],
            'lrn' => $student['lrn'],
            'photo' => $student['photo'],
            'grade_name' => $student['grade_name'],
            'section_name' => $student['section_name'],
        ]
    ]);
    exit;
}

if ($existingLog['time_in'] && !$existingLog['time_out']) {
    $updateStmt = $pdo->prepare("
        UPDATE attendance_logs SET time_out = ?, updated_at = NOW() WHERE id = ?
    ");
    $updateStmt->execute([$nowTime, $existingLog['id']]);

    $notifMsg = $student['full_name'] . ' scanned TIME OUT at ' . $nowFormatted;
    $notifStmt = $pdo->prepare("INSERT INTO notifications (student_id, message, type) VALUES (?, ?, 'time_out')");
    $notifStmt->execute([$student['id'], $notifMsg]);

    echo json_encode([
        'success' => true,
        'type' => 'time_out',
        'status' => $existingLog['status'],
        'time' => $nowFormatted,
        'student' => [
            'full_name' => $student['full_name'],
            'lrn' => $student['lrn'],
            'photo' => $student['photo'],
            'grade_name' => $student['grade_name'],
            'section_name' => $student['section_name'],
        ]
    ]);
    exit;
}

if ($existingLog['time_in'] && $existingLog['time_out']) {
    $notifMsg = $student['full_name'] . ' attempted duplicate scan at ' . $nowFormatted;
    $notifStmt = $pdo->prepare("INSERT INTO notifications (student_id, message, type) VALUES (?, ?, 'duplicate')");
    $notifStmt->execute([$student['id'], $notifMsg]);

    echo json_encode([
        'success' => false,
        'message' => 'Already timed in and out today. Duplicate scan rejected.',
        'lrn' => $lrn
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unexpected error. Please try again.', 'lrn' => $lrn]);
