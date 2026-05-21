<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/includes/header.php';
requireLogin();

$pageTitle = 'Attendance Records';
$pdo = getPDO();

$grades   = $pdo->query('SELECT * FROM grade_levels ORDER BY id')->fetchAll();
$sections = $pdo->query('SELECT s.*, gl.grade_name FROM sections s JOIN grade_levels gl ON s.grade_level_id = gl.id ORDER BY gl.id, s.section_name')->fetchAll();

$filter_date    = $_GET['date']    ?? date('Y-m-d');
$filter_grade   = $_GET['grade']   ?? '';
$filter_section = $_GET['section'] ?? '';
$filter_status  = $_GET['status']  ?? '';

$where  = ['al.log_date = ?'];
$params = [$filter_date];
if ($filter_grade)   { $where[] = 's.grade_level_id = ?'; $params[] = $filter_grade; }
if ($filter_section) { $where[] = 's.section_id = ?';     $params[] = $filter_section; }
if ($filter_status)  { $where[] = 'al.status = ?';        $params[] = $filter_status; }
$whereSQL = implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT al.*, s.full_name, s.lrn, s.photo, s.gender,
           gl.grade_name, sec.section_name
    FROM attendance_logs al
    JOIN students s ON al.student_id = s.id
    JOIN grade_levels gl ON s.grade_level_id = gl.id
    JOIN sections sec ON s.section_id = sec.id
    WHERE $whereSQL
    ORDER BY al.time_in DESC
");
$stmt->execute($params);
$logs = $stmt->fetchAll();

$countStmt = $pdo->prepare("SELECT status, COUNT(*) as cnt FROM attendance_logs WHERE log_date = ? GROUP BY status");
$countStmt->execute([$filter_date]);
$statusCounts = [];
foreach ($countStmt->fetchAll() as $row) $statusCounts[$row['status']] = $row['cnt'];

$extraScripts = ['<script>
$(document).ready(function() {
    $("#attendanceTable").DataTable({
        pageLength: 25,
        order: [[3, "asc"]],
        language: {
            emptyTable: "No attendance records found for this date.",
            zeroRecords: "No matching records found."
        }
    });
});
</script>'];
?>
<?php include BASE_PATH . '/includes/sidebar.php'; ?>
<div class="main-wrapper">
<?php include BASE_PATH . '/includes/navbar.php'; ?>
<div class="page-content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="font-head fw-700 mb-1">Attendance Records</h4>
            <p style="color:var(--text-3);font-size:13px;margin:0;"><?= date('l, F j, Y', strtotime($filter_date)) ?></p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/ajax/export_excel.php?date=<?= urlencode($filter_date) ?>&grade=<?= urlencode($filter_grade) ?>&section=<?= urlencode($filter_section) ?>"
                class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel me-1"></i> Excel
            </a>
            <a href="<?= BASE_URL ?>/ajax/export_pdf.php?date=<?= urlencode($filter_date) ?>&grade=<?= urlencode($filter_grade) ?>&section=<?= urlencode($filter_section) ?>"
                target="_blank" class="btn btn-danger btn-sm">
                <i class="bi bi-file-earmark-pdf me-1"></i> PDF
            </a>
            <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print
            </button>
        </div>
    </div>

    <div class="stat-cards mb-4">
        <div class="stat-card success">
            <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Present</span>
                <span class="stat-value"><?= $statusCounts['Present'] ?? 0 ?></span>
            </div>
        </div>
        <div class="stat-card danger">
            <div class="stat-icon"><i class="bi bi-x-circle-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Absent</span>
                <span class="stat-value"><?= $statusCounts['Absent'] ?? 0 ?></span>
            </div>
        </div>
        <div class="stat-card warning">
            <div class="stat-icon"><i class="bi bi-clock-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Late</span>
                <span class="stat-value"><?= $statusCounts['Late'] ?? 0 ?></span>
            </div>
        </div>
        <div class="stat-card primary">
            <div class="stat-icon"><i class="bi bi-calendar-check"></i></div>
            <div class="stat-info">
                <span class="stat-label">Total Scanned</span>
                <span class="stat-value"><?= count($logs) ?></span>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body-custom">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Date</label>
                    <input type="date" name="date" class="form-control form-control-sm" value="<?= e($filter_date) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Grade Level</label>
                    <select name="grade" class="form-select form-select-sm">
                        <option value="">All Grades</option>
                        <?php foreach ($grades as $g): ?>
                            <option value="<?= $g['id'] ?>" <?= $filter_grade == $g['id'] ? 'selected' : '' ?>><?= e($g['grade_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Section</label>
                    <select name="section" class="form-select form-select-sm">
                        <option value="">All Sections</option>
                        <?php foreach ($sections as $sec): ?>
                            <option value="<?= $sec['id'] ?>" <?= $filter_section == $sec['id'] ? 'selected' : '' ?>><?= e($sec['grade_name']) ?> — <?= e($sec['section_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="Present" <?= $filter_status === 'Present' ? 'selected' : '' ?>>Present</option>
                        <option value="Late"    <?= $filter_status === 'Late'    ? 'selected' : '' ?>>Late</option>
                        <option value="Absent"  <?= $filter_status === 'Absent'  ? 'selected' : '' ?>>Absent</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <a href="<?= BASE_URL ?>/admin/attendance.php" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body-custom p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="attendanceTable">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Grade</th>
                            <th>Section</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Status</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td>
                                    <div class="student-info">
                                        <div class="student-avatar">
                                            <?php if ($log['photo']): ?>
                                                <img src="<?= BASE_URL ?>/<?= e($log['photo']) ?>" alt="">
                                            <?php else: ?>
                                                <i class="bi bi-person-fill"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <span class="student-name"><?= e($log['full_name']) ?></span>
                                            <span class="student-lrn"><?= e($log['lrn']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><?= e($log['grade_name']) ?></td>
                                <td><?= e($log['section_name']) ?></td>
                                <td><?= $log['time_in']  ? date('h:i:s A', strtotime($log['time_in']))  : '—' ?></td>
                                <td><?= $log['time_out'] ? date('h:i:s A', strtotime($log['time_out'])) : '—' ?></td>
                                <td>
                                    <span class="badge-status badge-<?= strtolower($log['status']) ?>">
                                        <?= e($log['status']) ?>
                                    </span>
                                </td>
                                <td style="font-size:12px;color:var(--text-3);"><?= e($log['remarks'] ?: '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
</div>
<?php include BASE_PATH . '/includes/footer.php'; ?>
