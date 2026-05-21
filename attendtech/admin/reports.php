<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/includes/header.php';
requireLogin();

$pageTitle = 'Reports';
$pdo = getPDO();

$reportType   = $_GET['type']       ?? 'weekly';
$filter_grade = $_GET['grade']      ?? '';
$filter_section = $_GET['section']  ?? '';
$week_start   = $_GET['week_start'] ?? date('Y-m-d', strtotime('monday this week'));
$month        = $_GET['month']      ?? date('Y-m');

$grades   = $pdo->query('SELECT * FROM grade_levels ORDER BY id')->fetchAll();
$sections = $pdo->query('SELECT s.*, gl.grade_name FROM sections s JOIN grade_levels gl ON s.grade_level_id = gl.id ORDER BY gl.id, s.section_name')->fetchAll();

$studentMap = [];
$dates      = [];

if ($reportType === 'weekly') {
    $start = date('Y-m-d', strtotime($week_start));
    $end   = date('Y-m-d', strtotime($week_start . ' +6 days'));
} else {
    $start = $month . '-01';
    $end   = date('Y-m-t', strtotime($start));
}

for ($d = strtotime($start); $d <= strtotime($end); $d += 86400) {
    $dates[] = date('Y-m-d', $d);
}

$where  = ['s.status = \'Active\''];
$params = [$start, $end];
if ($filter_grade)   { $where[] = 's.grade_level_id = ?'; $params[] = $filter_grade; }
if ($filter_section) { $where[] = 's.section_id = ?';     $params[] = $filter_section; }
$whereSQL = implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT s.id, s.full_name, s.lrn, s.photo, gl.grade_name, sec.section_name,
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

foreach ($rows as $row) {
    if (!isset($studentMap[$row['id']])) {
        $studentMap[$row['id']] = [
            'full_name'    => $row['full_name'],
            'lrn'          => $row['lrn'],
            'photo'        => $row['photo'],
            'grade_name'   => $row['grade_name'],
            'section_name' => $row['section_name'],
            'days'         => []
        ];
    }
    if ($row['log_date']) {
        $studentMap[$row['id']]['days'][$row['log_date']] = $row['status'];
    }
}

$totalCols = 3 + count($dates) + 4;

$extraScripts = ['<script>
$(document).ready(function() {
    var colCount = ' . $totalCols . ';
    var orderableOff = [];
    for (var i = 3; i < colCount; i++) { orderableOff.push(i); }

    $("#reportTable").DataTable({
        pageLength: 50,
        scrollX: true,
        columnDefs: [{ orderable: false, targets: orderableOff }],
        language: {
            emptyTable: "No data available for the selected period.",
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
            <h4 class="font-head fw-700 mb-1">Attendance Reports</h4>
            <p style="color:var(--text-3);font-size:13px;margin:0;">Generate and export attendance summaries</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/ajax/export_excel.php?type=<?= urlencode($reportType) ?>&week_start=<?= urlencode($week_start) ?>&month=<?= urlencode($month) ?>&grade=<?= urlencode($filter_grade) ?>&section=<?= urlencode($filter_section) ?>"
                class="btn btn-success btn-sm no-print">
                <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
            </a>
            <button class="btn btn-outline-secondary btn-sm no-print" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print
            </button>
        </div>
    </div>

    <div class="card mb-4 no-print">
        <div class="card-body-custom">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Report Type</label>
                    <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="weekly"  <?= $reportType === 'weekly'  ? 'selected' : '' ?>>Weekly</option>
                        <option value="monthly" <?= $reportType === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                    </select>
                </div>
                <?php if ($reportType === 'weekly'): ?>
                <div class="col-md-3">
                    <label class="form-label">Week Start (Monday)</label>
                    <input type="date" name="week_start" class="form-control form-control-sm" value="<?= e($week_start) ?>">
                </div>
                <?php else: ?>
                <div class="col-md-3">
                    <label class="form-label">Month</label>
                    <input type="month" name="month" class="form-control form-control-sm" value="<?= e($month) ?>">
                </div>
                <?php endif; ?>
                <div class="col-md-2">
                    <label class="form-label">Grade</label>
                    <select name="grade" class="form-select form-select-sm">
                        <option value="">All</option>
                        <?php foreach ($grades as $g): ?>
                            <option value="<?= $g['id'] ?>" <?= $filter_grade == $g['id'] ? 'selected' : '' ?>><?= e($g['grade_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Section</label>
                    <select name="section" class="form-select form-select-sm">
                        <option value="">All</option>
                        <?php foreach ($sections as $sec): ?>
                            <option value="<?= $sec['id'] ?>" <?= $filter_section == $sec['id'] ? 'selected' : '' ?>><?= e($sec['section_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm w-100">Generate</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header-custom">
            <h5 class="card-title">
                <i class="bi bi-table"></i>
                <?php if ($reportType === 'weekly'): ?>
                    Weekly Report: <?= date('M j', strtotime($start)) ?> – <?= date('M j, Y', strtotime($end)) ?>
                <?php else: ?>
                    Monthly Report: <?= date('F Y', strtotime($start)) ?>
                <?php endif; ?>
            </h5>
            <span style="font-size:12px;color:var(--text-3);"><?= count($studentMap) ?> student(s)</span>
        </div>
        <div class="card-body-custom p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="reportTable" style="font-size:12px;">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Grade</th>
                            <th>Section</th>
                            <?php foreach ($dates as $d): ?>
                                <th class="text-center" style="min-width:58px;">
                                    <?= date('M j', strtotime($d)) ?><br>
                                    <small style="font-weight:400;opacity:.7;"><?= date('D', strtotime($d)) ?></small>
                                </th>
                            <?php endforeach; ?>
                            <th class="text-center" style="min-width:60px;">Present</th>
                            <th class="text-center" style="min-width:60px;">Absent</th>
                            <th class="text-center" style="min-width:50px;">Late</th>
                            <th class="text-center" style="min-width:55px;">Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($studentMap as $sid => $s):
                            $presentCount = 0;
                            $lateCount    = 0;
                            $absentCount  = 0;
                            foreach ($dates as $d) {
                                $status = $s['days'][$d] ?? null;
                                if ($status === 'Present') { $presentCount++; }
                                elseif ($status === 'Late') { $lateCount++; $presentCount++; }
                                else { $absentCount++; }
                            }
                            $totalDays = count($dates);
                            $rate = $totalDays > 0 ? round(($presentCount / $totalDays) * 100) : 0;
                        ?>
                        <tr>
                            <td>
                                <div class="student-info">
                                    <div class="student-avatar">
                                        <?php if ($s['photo']): ?>
                                            <img src="<?= BASE_URL ?>/<?= e($s['photo']) ?>" alt="">
                                        <?php else: ?>
                                            <i class="bi bi-person-fill"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <span class="student-name" style="font-size:13px;"><?= e($s['full_name']) ?></span>
                                        <span class="student-lrn"><?= e($s['lrn']) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td><?= e($s['grade_name']) ?></td>
                            <td><?= e($s['section_name']) ?></td>
                            <?php foreach ($dates as $d):
                                $status = $s['days'][$d] ?? null;
                                if (!$status) {
                                    $cell = '<span style="color:var(--text-3);">—</span>';
                                } elseif ($status === 'Present') {
                                    $cell = '<span class="badge-status badge-present">P</span>';
                                } elseif ($status === 'Late') {
                                    $cell = '<span class="badge-status badge-late">L</span>';
                                } else {
                                    $cell = '<span class="badge-status badge-absent">A</span>';
                                }
                            ?>
                                <td class="text-center"><?= $cell ?></td>
                            <?php endforeach; ?>
                            <td class="text-center fw-600 text-success"><?= $presentCount ?></td>
                            <td class="text-center fw-600 text-danger"><?= $absentCount ?></td>
                            <td class="text-center fw-600 text-warning"><?= $lateCount ?></td>
                            <td class="text-center fw-600"><?= $rate ?>%</td>
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
