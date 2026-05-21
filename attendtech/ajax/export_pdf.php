<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/includes/session.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/functions.php';

if (!isLoggedIn()) { http_response_code(403); exit; }

$pdo = getPDO();

$date = $_GET['date'] ?? date('Y-m-d');
$grade = $_GET['grade'] ?? '';
$section = $_GET['section'] ?? '';

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
    ORDER BY gl.id, sec.section_name, s.full_name
");
$stmt->execute($params);
$logs = $stmt->fetchAll();

$settings = getAllSettings();
$schoolName = $settings['school_name'] ?? 'AttendTech';
$academicYear = $settings['academic_year'] ?? '2025-2026';

$presentCount = count(array_filter($logs, fn($l) => $l['status'] === 'Present'));
$lateCount = count(array_filter($logs, fn($l) => $l['status'] === 'Late'));
$absentCount = count(array_filter($logs, fn($l) => $l['status'] === 'Absent'));

function eh($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance Report — <?= eh($date) ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #1a202c; background: #fff; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #1a56db; padding-bottom: 12px; }
        .school-name { font-size: 16px; font-weight: 700; color: #1a56db; }
        .report-title { font-size: 13px; font-weight: 600; margin-top: 4px; }
        .report-date { font-size: 11px; color: #718096; margin-top: 2px; }
        .summary { display: flex; gap: 16px; margin-bottom: 16px; }
        .summary-box { flex: 1; padding: 10px; border-radius: 6px; text-align: center; }
        .summary-box.present { background: #e6f4ea; }
        .summary-box.absent { background: #fce8e6; }
        .summary-box.late { background: #fef7e0; }
        .summary-box .num { font-size: 22px; font-weight: 700; }
        .summary-box .lbl { font-size: 10px; text-transform: uppercase; letter-spacing: .5px; margin-top: 2px; }
        .summary-box.present .num { color: #0f9d58; }
        .summary-box.absent .num { color: #d93025; }
        .summary-box.late .num { color: #f4a100; }
        table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        thead th { background: #1a56db; color: #fff; padding: 8px 10px; text-align: left; font-size: 11px; letter-spacing: .5px; text-transform: uppercase; }
        tbody td { padding: 7px 10px; border-bottom: 1px solid #e2e8f0; font-size: 12px; }
        tbody tr:nth-child(even) { background: #f7f9fc; }
        .status-badge { padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: 700; }
        .badge-Present { background: #e6f4ea; color: #0f9d58; }
        .badge-Late { background: #fef7e0; color: #f4a100; }
        .badge-Absent { background: #fce8e6; color: #d93025; }
        .footer { margin-top: 20px; padding-top: 12px; border-top: 1px solid #e2e8f0; font-size: 10px; color: #718096; text-align: center; }
        @media print { body { padding: 0; } .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom:16px;">
        <button onclick="window.print()" style="padding:8px 16px;background:#1a56db;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:13px;font-weight:600;">
            🖨️ Print / Save as PDF
        </button>
        <button onclick="window.close()" style="margin-left:8px;padding:8px 16px;background:#e2e8f0;color:#1a202c;border:none;border-radius:6px;cursor:pointer;font-size:13px;">
            Close
        </button>
    </div>

    <div class="header">
        <div class="school-name"><?= eh($schoolName) ?></div>
        <div class="report-title">Daily Attendance Report — Academic Year <?= eh($academicYear) ?></div>
        <div class="report-date"><?= date('l, F j, Y', strtotime($date)) ?> &nbsp;|&nbsp; Generated: <?= date('Y-m-d H:i:s') ?></div>
    </div>

    <div class="summary">
        <div class="summary-box present">
            <div class="num"><?= $presentCount ?></div>
            <div class="lbl">Present</div>
        </div>
        <div class="summary-box absent">
            <div class="num"><?= $absentCount ?></div>
            <div class="lbl">Absent</div>
        </div>
        <div class="summary-box late">
            <div class="num"><?= $lateCount ?></div>
            <div class="lbl">Late</div>
        </div>
        <div class="summary-box" style="background:#e8f0fe;">
            <div class="num" style="color:#1a56db;"><?= count($logs) ?></div>
            <div class="lbl">Total Scanned</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Student Name</th>
                <th>LRN</th>
                <th>Grade</th>
                <th>Section</th>
                <th>Time In</th>
                <th>Time Out</th>
                <th>Status</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $i => $log): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><strong><?= eh($log['full_name']) ?></strong></td>
                    <td><?= eh($log['lrn']) ?></td>
                    <td><?= eh($log['grade_name']) ?></td>
                    <td><?= eh($log['section_name']) ?></td>
                    <td><?= $log['time_in'] ? date('h:i A', strtotime($log['time_in'])) : '—' ?></td>
                    <td><?= $log['time_out'] ? date('h:i A', strtotime($log['time_out'])) : '—' ?></td>
                    <td><span class="status-badge badge-<?= eh($log['status']) ?>"><?= eh($log['status']) ?></span></td>
                    <td><?= eh($log['remarks'] ?: '—') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($logs)): ?>
                <tr><td colspan="9" style="text-align:center;padding:24px;color:#718096;">No records found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        AttendTech — Automated Online Attendance Monitoring System | <?= eh($schoolName) ?> | <?= eh($academicYear) ?>
    </div>

    <script>
        window.onload = function() {
            if (window.location.search.indexOf('autoprint=1') !== -1) {
                window.print();
            }
        };
    </script>
</body>
</html>
