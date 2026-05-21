<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/includes/session.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/functions.php';
requireLogin();

$pdo = getPDO();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . BASE_URL . '/admin/students.php'); exit; }

$stmt = $pdo->prepare("
    SELECT s.*, gl.grade_name, sec.section_name
    FROM students s
    JOIN grade_levels gl ON s.grade_level_id = gl.id
    JOIN sections sec ON s.section_id = sec.id
    WHERE s.id = ?
");
$stmt->execute([$id]);
$student = $stmt->fetch();
if (!$student) { header('Location: /admin/students.php'); exit; }

$settings = getAllSettings();
$schoolName = $settings['school_name'] ?? 'Bicol University Gubat Campus';
$academicYear = $settings['academic_year'] ?? '2025-2026';

if (!$student['qr_code']) {
    $qrPath = generateQRCode($student['lrn']);
    if ($qrPath) {
        $upd = $pdo->prepare('UPDATE students SET qr_code = ? WHERE id = ?');
        $upd->execute([$qrPath, $id]);
        $student['qr_code'] = $qrPath;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student ID — <?= e($student['full_name']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        body { background: #f0f4f8; display: flex; flex-direction: column; align-items: center; padding: 40px 20px; font-family: 'DM Sans', sans-serif; }
        .controls { margin-bottom: 24px; display: flex; gap: 12px; }
        @media print {
            body { background: #fff; padding: 0; }
            .controls { display: none; }
            .id-card { box-shadow: none; page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="controls no-print">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="bi bi-printer me-1"></i> Print ID
        </button>
        <a href="<?= BASE_URL ?>/admin/students.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="id-card">
        <div class="id-header">
            <?php if (!empty($settings['system_logo'])): ?>
                <img src="<?= BASE_URL ?>/<?= e($settings['system_logo']) ?>" alt="Logo"
                    style="height:32px;object-fit:contain;display:block;margin:0 auto 6px;">
            <?php endif; ?>
            <div class="school"><?= e($schoolName) ?></div>
            <div class="title">STUDENT QR ID — <?= e($academicYear) ?></div>
        </div>

        <div class="id-body">
            <div class="id-photo">
                <?php if ($student['photo']): ?>
                    <img src="<?= BASE_URL ?>/<?= e($student['photo']) ?>" alt="Photo">
                <?php else: ?>
                    <i class="bi bi-person-fill"></i>
                <?php endif; ?>
            </div>

            <div class="id-name"><?= e($student['full_name']) ?></div>
            <div class="id-lrn">LRN: <?= e($student['lrn']) ?></div>
            <div class="id-grade"><?= e($student['grade_name']) ?> — <?= e($student['section_name']) ?></div>
            <div class="id-grade" style="font-size:12px;color:#718096;margin-top:2px;"><?= e($student['gender']) ?></div>

            <?php if ($student['qr_code']): ?>
                <div class="id-qr">
                    <img src="<?= BASE_URL ?>/<?= e($student['qr_code']) ?>" alt="QR Code">
                </div>
            <?php endif; ?>

            <?php if ($student['parent_name']): ?>
                <div style="margin-top:10px;padding:8px;background:#f7f9fc;border-radius:6px;font-size:11px;text-align:left;">
                    <div style="color:#718096;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:2px;">Parent/Guardian</div>
                    <div style="font-weight:600;"><?= e($student['parent_name']) ?></div>
                    <?php if ($student['parent_contact']): ?>
                        <div style="color:#718096;"><?= e($student['parent_contact']) ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="id-footer">
            AttendTech · Automated QR Attendance System
        </div>
    </div>

    <div class="no-print mt-4" style="max-width:340px;text-align:center;color:#718096;font-size:13px;">
        <i class="bi bi-info-circle me-1"></i>
        Use Ctrl+P (or Cmd+P) to print this ID card. Set paper size to A6 or card for best results.
    </div>
</body>
</html>
