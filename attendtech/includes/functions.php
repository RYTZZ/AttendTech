<?php
require_once __DIR__ . '/../config/database.php';

function getSystemSetting($key) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT setting_value FROM system_settings WHERE setting_key = ?');
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['setting_value'] : '';
}

function getAllSettings() {
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT setting_key, setting_value FROM system_settings');
    $settings = [];
    foreach ($stmt->fetchAll() as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}

function generateQRCode($lrn) {
    $dir = BASE_PATH . '/assets/qr_codes/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $filename = 'qr_' . $lrn . '.png';
    $filepath = $dir . $filename;
    $url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($lrn) . '&format=png';
    $imageData = @file_get_contents($url);
    if ($imageData !== false) {
        file_put_contents($filepath, $imageData);
        return 'assets/qr_codes/' . $filename;
    }
    return '';
}

function uploadStudentPhoto($file) {
    $dir = BASE_PATH . '/assets/uploads/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed)) return ['success' => false, 'message' => 'Invalid image type.'];
    if ($file['size'] > 5 * 1024 * 1024) return ['success' => false, 'message' => 'File too large.'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'student_' . uniqid() . '.' . strtolower($ext);
    $destination = $dir . $filename;
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => true, 'filename' => 'assets/uploads/' . $filename];
    }
    return ['success' => false, 'message' => 'Upload failed.'];
}

function getTodayStats() {
    $pdo = getPDO();
    $today = date('Y-m-d');
    $settings = getAllSettings();
    $lateTime = $settings['late_time_threshold'] ?? '07:30:00';

    $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE status = 'Active'");
    $totalStmt->execute();
    $total = (int)$totalStmt->fetchColumn();

    $presentStmt = $pdo->prepare("SELECT COUNT(*) FROM attendance_logs WHERE log_date = ? AND status IN ('Present','Late')");
    $presentStmt->execute([$today]);
    $present = (int)$presentStmt->fetchColumn();

    $lateStmt = $pdo->prepare("SELECT COUNT(*) FROM attendance_logs WHERE log_date = ? AND status = 'Late'");
    $lateStmt->execute([$today]);
    $late = (int)$lateStmt->fetchColumn();

    $absent = $total - $present;
    $percentage = $total > 0 ? round(($present / $total) * 100, 1) : 0;

    return compact('total', 'present', 'absent', 'late', 'percentage');
}

function getWeeklyChartData() {
    $pdo = getPDO();
    $data = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $label = date('D, M j', strtotime("-$i days"));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance_logs WHERE log_date = ? AND status IN ('Present','Late')");
        $stmt->execute([$date]);
        $count = (int)$stmt->fetchColumn();
        $data[] = ['date' => $label, 'count' => $count];
    }
    return $data;
}

function getRecentLogs($limit = 10) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("
        SELECT al.*, s.full_name, s.lrn, s.photo,
               gl.grade_name, sec.section_name
        FROM attendance_logs al
        JOIN students s ON al.student_id = s.id
        JOIN grade_levels gl ON s.grade_level_id = gl.id
        JOIN sections sec ON s.section_id = sec.id
        WHERE al.log_date = ?
        ORDER BY al.updated_at DESC
        LIMIT ?
    ");
    $stmt->execute([date('Y-m-d'), $limit]);
    return $stmt->fetchAll();
}

function getUnreadNotifications() {
    $pdo = getPDO();
    $stmt = $pdo->prepare("
        SELECT n.*, s.full_name, s.photo
        FROM notifications n
        JOIN students s ON n.student_id = s.id
        WHERE n.is_read = 0
        ORDER BY n.created_at DESC
        LIMIT 20
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

function getUnreadNotificationCount() {
    $pdo = getPDO();
    $stmt = $pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0");
    return (int)$stmt->fetchColumn();
}

function markAllNotificationsRead() {
    $pdo = getPDO();
    $pdo->exec("UPDATE notifications SET is_read = 1");
}

function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}
