<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/includes/session.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/functions.php';

if (!isLoggedIn()) { http_response_code(403); exit; }

$pdo = getPDO();

$type = $_GET['type'] ?? 'daily';
$date = $_GET['date'] ?? date('Y-m-d');
$grade = $_GET['grade'] ?? '';
$section = $_GET['section'] ?? '';
$week_start = $_GET['week_start'] ?? date('Y-m-d', strtotime('monday this week'));
$month = $_GET['month'] ?? date('Y-m');

$settings = getAllSettings();
$schoolName = $settings['school_name'] ?? 'AttendTech';

if ($type === 'weekly' || $type === 'monthly') {
    if ($type === 'weekly') {
        $start = date('Y-m-d', strtotime($week_start));
        $end = date('Y-m-d', strtotime($week_start . ' +6 days'));
        $filename = 'weekly_report_' . $start . '.csv';
        $title = 'Weekly Attendance Report: ' . date('M j', strtotime($start)) . ' - ' . date('M j, Y', strtotime($end));
    } else {
        $start = $month . '-01';
        $end = date('Y-m-t', strtotime($start));
        $filename = 'monthly_report_' . $month . '.csv';
        $title = 'Monthly Attendance Report: ' . date('F Y', strtotime($start));
    }

    $dates = [];
    for ($d = strtotime($start); $d <= strtotime($end); $d += 86400) {
        $dates[] = date('Y-m-d', $d);
    }

    $where = ['s.status = \'Active\''];
    $params = [$start, $end];
    if ($grade) { $where[] = 's.grade_level_id = ?'; $params[] = $grade; }
    if ($section) { $where[] = 's.section_id = ?'; $params[] = $section; }
    $whereSQL = implode(' AND ', $where);

    $stmt = $pdo->prepare("
        SELECT s.id, s.full_name, s.lrn, gl.grade_name, sec.section_name,
               al.log_date, al.status
        FROM students s
        JOIN grade_levels gl ON s.grade_level_id = gl.id
        JOIN sections sec ON s.section_id = sec.id
        LEFT JOIN attendance_logs al ON al.student_id = s.id AND al.log_date BETWEEN ? AND ?
        WHERE $whereSQL
        ORDER BY s.full_name
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $studentMap = [];
    foreach ($rows as $row) {
        if (!isset($studentMap[$row['id']])) {
            $studentMap[$row['id']] = [
                'full_name' => $row['full_name'],
                'lrn' => $row['lrn'],
                'grade_name' => $row['grade_name'],
                'section_name' => $row['section_name'],
                'days' => []
            ];
        }
        if ($row['log_date']) {
            $studentMap[$row['id']]['days'][$row['log_date']] = $row['status'];
        }
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

    fputcsv($out, [$schoolName]);
    fputcsv($out, [$title]);
    fputcsv($out, ['Generated: ' . date('Y-m-d H:i:s')]);
    fputcsv($out, []);

    $header = ['Student Name', 'LRN', 'Grade', 'Section'];
    foreach ($dates as $d) $header[] = date('M j (D)', strtotime($d));
    $header = array_merge($header, ['Present', 'Absent', 'Late', 'Rate %']);
    fputcsv($out, $header);

    foreach ($studentMap as $s) {
        $row = [$s['full_name'], $s['lrn'], $s['grade_name'], $s['section_name']];
        $presentCount = 0; $absentCount = 0; $lateCount = 0;
        foreach ($dates as $d) {
            $status = $s['days'][$d] ?? '';
            $row[] = $status ?: 'Absent';
            if ($status === 'Present') $presentCount++;
            elseif ($status === 'Late') { $lateCount++; $presentCount++; }
            else $absentCount++;
        }
        $rate = count($dates) > 0 ? round(($presentCount / count($dates)) * 100) : 0;
        $row = array_merge($row, [$presentCount, $absentCount, $lateCount, $rate . '%']);
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

$where = ['al.log_date = ?'];
$params = [$date];
if ($grade) { $where[] = 's.grade_level_id = ?'; $params[] = $grade; }
if ($section) { $where[] = 's.section_id = ?'; $params[] = $section; }
$whereSQL = implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT al.*, s.full_name, s.lrn, s.gender, gl.grade_name, sec.section_name
    FROM attendance_logs al
    JOIN students s ON al.student_id = s.id
    JOIN grade_levels gl ON s.grade_level_id = gl.id
    JOIN sections sec ON s.section_id = sec.id
    WHERE $whereSQL
    ORDER BY al.time_in
");
$stmt->execute($params);
$logs = $stmt->fetchAll();

$filename = 'attendance_' . $date . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
$out = fopen('php://output', 'w');
fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

fputcsv($out, [$schoolName]);
fputcsv($out, ['Daily Attendance Report: ' . date('F j, Y', strtotime($date))]);
fputcsv($out, ['Generated: ' . date('Y-m-d H:i:s')]);
fputcsv($out, []);
fputcsv($out, ['Student Name', 'LRN', 'Gender', 'Grade', 'Section', 'Time In', 'Time Out', 'Status', 'Remarks']);

foreach ($logs as $log) {
    fputcsv($out, [
        $log['full_name'],
        $log['lrn'],
        $log['gender'],
        $log['grade_name'],
        $log['section_name'],
        $log['time_in'] ? date('h:i:s A', strtotime($log['time_in'])) : '',
        $log['time_out'] ? date('h:i:s A', strtotime($log['time_out'])) : '',
        $log['status'],
        $log['remarks'] ?? ''
    ]);
}
fclose($out);
