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

$pdo = getPDO();

$date = $_GET['date'] ?? date('Y-m-d');
$grade = $_GET['grade'] ?? '';
$section = $_GET['section'] ?? '';
$status = $_GET['status'] ?? '';

$where = ['al.log_date = ?'];
$params = [$date];

if ($grade) { $where[] = 's.grade_level_id = ?'; $params[] = $grade; }
if ($section) { $where[] = 's.section_id = ?'; $params[] = $section; }
if ($status) { $where[] = 'al.status = ?'; $params[] = $status; }

$whereSQL = implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT al.*, s.full_name, s.lrn, s.photo, gl.grade_name, sec.section_name
    FROM attendance_logs al
    JOIN students s ON al.student_id = s.id
    JOIN grade_levels gl ON s.grade_level_id = gl.id
    JOIN sections sec ON s.section_id = sec.id
    WHERE $whereSQL
    ORDER BY al.time_in DESC
");
$stmt->execute($params);
$logs = $stmt->fetchAll();

$result = array_map(function ($log) {
    return [
        'id' => $log['id'],
        'full_name' => $log['full_name'],
        'lrn' => $log['lrn'],
        'photo' => $log['photo'],
        'grade_name' => $log['grade_name'],
        'section_name' => $log['section_name'],
        'log_date' => $log['log_date'],
        'time_in' => $log['time_in'] ? date('h:i:s A', strtotime($log['time_in'])) : null,
        'time_out' => $log['time_out'] ? date('h:i:s A', strtotime($log['time_out'])) : null,
        'status' => $log['status'],
        'remarks' => $log['remarks'],
    ];
}, $logs);

echo json_encode(['success' => true, 'logs' => $result, 'total' => count($result)]);
