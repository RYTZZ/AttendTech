<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/includes/header.php';
requireLogin();

$pageTitle = 'Dashboard';
$stats = getTodayStats();
$weeklyData = getWeeklyChartData();
$recentLogs = getRecentLogs(10);

$chartLabels = json_encode(array_column($weeklyData, 'date'));
$chartCounts = json_encode(array_column($weeklyData, 'count'));

$extraScripts = [
    '<script src="' . BASE_URL . '/assets/js/charts.js"></script>',
    '<script>
        document.addEventListener("DOMContentLoaded", () => {
            initWeeklyChart(' . $chartLabels . ', ' . $chartCounts . ');
            initAttendancePie(' . $stats['present'] . ', ' . $stats['absent'] . ', ' . $stats['late'] . ');
        });
    </script>'
];
?>
<?php include BASE_PATH . '/includes/sidebar.php'; ?>
<div class="main-wrapper">
<?php include BASE_PATH . '/includes/navbar.php'; ?>
<div class="page-content">

    <div class="stat-cards">
        <div class="stat-card primary">
            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Total Students</span>
                <span class="stat-value"><?= $stats['total'] ?></span>
                <span class="stat-sub">Active enrolled</span>
            </div>
        </div>
        <div class="stat-card success">
            <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Present Today</span>
                <span class="stat-value"><?= $stats['present'] ?></span>
                <span class="stat-sub"><?= $stats['percentage'] ?>% attendance rate</span>
            </div>
        </div>
        <div class="stat-card danger">
            <div class="stat-icon"><i class="bi bi-x-circle-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Absent Today</span>
                <span class="stat-value"><?= $stats['absent'] ?></span>
                <span class="stat-sub">Not yet scanned</span>
            </div>
        </div>
        <div class="stat-card warning">
            <div class="stat-icon"><i class="bi bi-clock-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Late Today</span>
                <span class="stat-value"><?= $stats['late'] ?></span>
                <span class="stat-sub">Arrived after threshold</span>
            </div>
        </div>
        <div class="stat-card info">
            <div class="stat-icon"><i class="bi bi-percent"></i></div>
            <div class="stat-info">
                <span class="stat-label">Attendance Rate</span>
                <span class="stat-value"><?= $stats['percentage'] ?>%</span>
                <span class="stat-sub">Today</span>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header-custom">
                    <h5 class="card-title"><i class="bi bi-bar-chart-fill"></i> Weekly Attendance</h5>
                    <span style="font-size:12px;color:var(--text-3);">Last 7 days</span>
                </div>
                <div class="card-body-custom">
                    <div class="chart-container">
                        <canvas id="weeklyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header-custom">
                    <h5 class="card-title"><i class="bi bi-pie-chart-fill"></i> Today's Summary</h5>
                </div>
                <div class="card-body-custom">
                    <div class="chart-container" style="height:220px;">
                        <canvas id="attendancePie"></canvas>
                    </div>
                    <div class="mt-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small style="color:var(--text-3);">Attendance Progress</small>
                            <small style="font-weight:700;"><?= $stats['percentage'] ?>%</small>
                        </div>
                        <div class="attendance-progress">
                            <div class="attendance-progress-bar" style="width:<?= $stats['percentage'] ?>%;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header-custom">
            <h5 class="card-title"><i class="bi bi-clock-history"></i> Today's Attendance Log</h5>
            <a href="<?= BASE_URL ?>/admin/attendance.php" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="card-body-custom p-0">
            <?php if (empty($recentLogs)): ?>
                <div class="empty-state">
                    <i class="bi bi-calendar-x"></i>
                    <p>No attendance records for today yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Grade & Section</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentLogs as $log): ?>
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
                                    <td><?= e($log['grade_name']) ?> — <?= e($log['section_name']) ?></td>
                                    <td><?= $log['time_in'] ? date('h:i A', strtotime($log['time_in'])) : '—' ?></td>
                                    <td><?= $log['time_out'] ? date('h:i A', strtotime($log['time_out'])) : '—' ?></td>
                                    <td>
                                        <span class="badge-status badge-<?= strtolower($log['status']) ?>">
                                            <?= e($log['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>
</div>
<?php include BASE_PATH . '/includes/footer.php'; ?>
