<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/includes/header.php';
requireLogin();

$pageTitle = 'Notifications';
$pdo = getPDO();

if (isset($_GET['mark_all'])) {
    markAllNotificationsRead();
    header('Location: /admin/notifications.php');
    exit;
}

$filter = $_GET['filter'] ?? 'all';
$where = $filter === 'unread' ? 'WHERE n.is_read = 0' : '';

$stmt = $pdo->prepare("
    SELECT n.*, s.full_name, s.lrn, s.photo, s.grade_level_id,
           gl.grade_name, sec.section_name
    FROM notifications n
    JOIN students s ON n.student_id = s.id
    JOIN grade_levels gl ON s.grade_level_id = gl.id
    JOIN sections sec ON s.section_id = sec.id
    $where
    ORDER BY n.created_at DESC
    LIMIT 200
");
$stmt->execute();
$notifications = $stmt->fetchAll();

$unread = array_filter($notifications, fn($n) => !$n['is_read']);

$extraScripts = ['<script>
$(document).ready(function() {
    $("#notifTable").DataTable({
        pageLength: 25,
        order: [[4, "desc"]],
        language: {
            emptyTable: "No notifications found.",
            zeroRecords: "No matching notifications found."
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
            <h4 class="font-head fw-700 mb-1">Notifications</h4>
            <p style="color:var(--text-3);font-size:13px;margin:0;">
                <?= count($unread) ?> unread · <?= count($notifications) ?> total shown
            </p>
        </div>
        <div class="d-flex gap-2">
            <div class="btn-group btn-group-sm">
                <a href="?filter=all" class="btn btn-<?= $filter === 'all' ? 'primary' : 'outline-secondary' ?>">All</a>
                <a href="?filter=unread" class="btn btn-<?= $filter === 'unread' ? 'primary' : 'outline-secondary' ?>">Unread</a>
            </div>
            <a href="?mark_all=1" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-check2-all me-1"></i> Mark All Read
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body-custom p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="notifTable">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Grade & Section</th>
                            <th>Type</th>
                            <th>Message</th>
                            <th>Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notifications as $n):
                            $typeClass = match($n['type']) {
                                'time_in' => 'success',
                                'time_out' => 'info',
                                'late' => 'warning',
                                'duplicate' => 'danger',
                                default => 'secondary'
                            };
                            $typeLabel = match($n['type']) {
                                'time_in' => 'Time In',
                                'time_out' => 'Time Out',
                                'late' => 'Late',
                                'duplicate' => 'Duplicate',
                                default => ucfirst($n['type'])
                            };
                        ?>
                            <tr class="<?= !$n['is_read'] ? 'table-active' : '' ?>">
                                <td>
                                    <div class="student-info">
                                        <div class="student-avatar">
                                            <?php if ($n['photo']): ?>
                                                <img src="<?= BASE_URL ?>/<?= e($n['photo']) ?>" alt="">
                                            <?php else: ?>
                                                <i class="bi bi-person-fill"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <span class="student-name"><?= e($n['full_name']) ?></span>
                                            <span class="student-lrn"><?= e($n['lrn']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><?= e($n['grade_name']) ?> — <?= e($n['section_name']) ?></td>
                                <td>
                                    <span class="badge text-bg-<?= $typeClass ?>"><?= $typeLabel ?></span>
                                </td>
                                <td style="font-size:13px;"><?= e($n['message']) ?></td>
                                <td style="font-size:12px;white-space:nowrap;color:var(--text-3);">
                                    <?= date('M j, Y h:i A', strtotime($n['created_at'])) ?>
                                </td>
                                <td>
                                    <?php if (!$n['is_read']): ?>
                                        <span class="badge-status badge-present" style="background:var(--primary-light);color:var(--primary);">New</span>
                                    <?php else: ?>
                                        <span style="font-size:12px;color:var(--text-3);">Read</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($notifications)): ?>

                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
</div>
<?php include BASE_PATH . '/includes/footer.php'; ?>
