<nav class="topnav">
    <div class="topnav-left">
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        <div class="page-breadcrumb">
            <span class="breadcrumb-page"><?= e($pageTitle ?? 'Dashboard') ?></span>
        </div>
    </div>
    <div class="topnav-right">
        <div class="topnav-clock" id="topnavClock"></div>
        <div class="topnav-date" id="topnavDate"></div>

        <div class="dropdown topnav-notification">
            <button class="topnav-btn" data-bs-toggle="dropdown" id="notifBtn">
                <i class="bi bi-bell-fill"></i>
                <?php if ($unreadCount > 0): ?>
                    <span class="notif-dot"><?= $unreadCount ?></span>
                <?php endif; ?>
            </button>
            <div class="dropdown-menu dropdown-menu-end notif-dropdown" id="notifDropdown">
                <div class="notif-header">
                    <span>Notifications</span>
                    <a href="#" class="notif-mark-all" id="markAllRead">Mark all read</a>
                </div>
                <div class="notif-body" id="notifList">
                    <div class="notif-loading"><i class="bi bi-arrow-repeat spin"></i></div>
                </div>
                <div class="notif-footer">
                    <a href="<?= BASE_URL ?>/admin/notifications.php">View All Notifications</a>
                </div>
            </div>
        </div>

        <div class="topnav-admin">
            <div class="admin-avatar-sm">
                <i class="bi bi-person-fill"></i>
            </div>
            <span><?= e($_SESSION['admin_name'] ?? 'Admin') ?></span>
        </div>
    </div>
</nav>
