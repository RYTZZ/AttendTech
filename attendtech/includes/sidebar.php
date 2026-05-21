<?php
$currentPage = basename($_SERVER['PHP_SELF']);
function isActive($pages) {
    $current = basename($_SERVER['PHP_SELF']);
    return in_array($current, (array)$pages) ? 'active' : '';
}
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">
            <i class="bi bi-qr-code-scan"></i>
        </div>
        <div class="brand-text">
            <span class="brand-name">AttendTech</span>
            <span class="brand-sub">Smart Attendance</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">
            <span class="nav-section-label">Main</span>
            <a href="<?= BASE_URL ?>/admin/dashboard.php" class="nav-item <?= isActive('dashboard.php') ?>">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Dashboard</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/scanner.php" class="nav-item <?= isActive('scanner.php') ?>">
                <i class="bi bi-qr-code-scan"></i>
                <span>QR Scanner</span>
                <span class="nav-badge pulse">Live</span>
            </a>
        </div>

        <div class="nav-section">
            <span class="nav-section-label">Management</span>
            <a href="<?= BASE_URL ?>/admin/students.php" class="nav-item <?= isActive(['students.php','add_student.php','edit_student.php']) ?>">
                <i class="bi bi-people-fill"></i>
                <span>Students</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/attendance.php" class="nav-item <?= isActive('attendance.php') ?>">
                <i class="bi bi-calendar-check-fill"></i>
                <span>Attendance</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/reports.php" class="nav-item <?= isActive('reports.php') ?>">
                <i class="bi bi-bar-chart-fill"></i>
                <span>Reports</span>
            </a>
        </div>

        <div class="nav-section">
            <span class="nav-section-label">System</span>
            <a href="<?= BASE_URL ?>/admin/notifications.php" class="nav-item <?= isActive('notifications.php') ?>">
                <i class="bi bi-bell-fill"></i>
                <span>Notifications</span>
                <?php if ($unreadCount > 0): ?>
                    <span class="nav-badge notif-count"><?= $unreadCount ?></span>
                <?php endif; ?>
            </a>
            <a href="<?= BASE_URL ?>/admin/settings.php" class="nav-item <?= isActive('settings.php') ?>">
                <i class="bi bi-gear-fill"></i>
                <span>Settings</span>
            </a>
        </div>
    </nav>

    <div class="sidebar-footer">
        <div class="admin-card">
            <div class="admin-avatar">
                <i class="bi bi-person-circle"></i>
            </div>
            <div class="admin-info">
                <span class="admin-name"><?= e($_SESSION['admin_name'] ?? 'Admin') ?></span>
                <span class="admin-role">Administrator</span>
            </div>
            <a href="<?= BASE_URL ?>/logout.php" class="admin-logout" title="Logout">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>
</aside>
