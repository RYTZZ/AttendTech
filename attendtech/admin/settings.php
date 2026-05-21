<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/includes/header.php';
requireLogin();

$pageTitle = 'System Settings';
$pdo = getPDO();

$success = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'school_name', 'academic_year', 'late_time_threshold',
        'school_address', 'contact_number', 'timezone'
    ];

    foreach ($fields as $field) {
        $value = trim($_POST[$field] ?? '');
        $stmt = $pdo->prepare('UPDATE system_settings SET setting_value = ? WHERE setting_key = ?');
        $stmt->execute([$value, $field]);
        if ($stmt->rowCount() === 0) {
            $ins = $pdo->prepare('INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?');
            $ins->execute([$field, $value, $value]);
        }
    }

    if (!empty($_FILES['system_logo']['name'])) {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        if (in_array($_FILES['system_logo']['type'], $allowed) && $_FILES['system_logo']['size'] <= 2 * 1024 * 1024) {
            $ext = pathinfo($_FILES['system_logo']['name'], PATHINFO_EXTENSION);
            $logoName = 'logo.' . strtolower($ext);
            $logoDest = BASE_PATH . '/assets/images/' . $logoName;
            if (move_uploaded_file($_FILES['system_logo']['tmp_name'], $logoDest)) {
                $logoStmt = $pdo->prepare('UPDATE system_settings SET setting_value = ? WHERE setting_key = ?');
                $logoStmt->execute(['assets/images/' . $logoName, 'system_logo']);
            }
        } else {
            $errors[] = 'Logo must be an image file under 2MB.';
        }
    }

    if (empty($errors)) {
        $success = 'Settings saved successfully.';
    }
}

if (isset($_POST['change_password'])) {
    $currentPw = $_POST['current_password'] ?? '';
    $newPw = $_POST['new_password'] ?? '';
    $confirmPw = $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare('SELECT password FROM admins WHERE id = ?');
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch();

    if (!password_verify($currentPw, $admin['password'])) {
        $errors[] = 'Current password is incorrect.';
    } elseif (strlen($newPw) < 6) {
        $errors[] = 'New password must be at least 6 characters.';
    } elseif ($newPw !== $confirmPw) {
        $errors[] = 'Passwords do not match.';
    } else {
        $hash = password_hash($newPw, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('UPDATE admins SET password = ? WHERE id = ?');
        $stmt->execute([$hash, $_SESSION['admin_id']]);
        $success = 'Password changed successfully.';
    }
}

$settings = getAllSettings();
$timezones = ['Asia/Manila', 'Asia/Singapore', 'UTC', 'Asia/Tokyo', 'Asia/Shanghai'];
?>
<?php include BASE_PATH . '/includes/sidebar.php'; ?>
<div class="main-wrapper">
<?php include BASE_PATH . '/includes/navbar.php'; ?>
<div class="page-content">

    <div class="mb-4">
        <h4 class="font-head fw-700 mb-1">System Settings</h4>
        <p style="color:var(--text-3);font-size:13px;margin:0;">Configure system-wide parameters</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success mb-4">
            <i class="bi bi-check-circle-fill me-2"></i><?= e($success) ?>
        </div>
    <?php endif; ?>
    <?php if ($errors): ?>
        <div class="alert alert-danger mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?php foreach ($errors as $er): ?><div><?= e($er) ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header-custom">
                    <h5 class="card-title"><i class="bi bi-building"></i> School Information</h5>
                </div>
                <div class="card-body-custom">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">School Name</label>
                                <input type="text" name="school_name" class="form-control"
                                    value="<?= e($settings['school_name'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Academic Year</label>
                                <input type="text" name="academic_year" class="form-control"
                                    value="<?= e($settings['academic_year'] ?? '') ?>" placeholder="2025-2026">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Late Time Threshold</label>
                                <input type="time" name="late_time_threshold" class="form-control"
                                    value="<?= e($settings['late_time_threshold'] ?? '07:30') ?>">
                                <div class="form-text">Students who scan after this time are marked Late.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Timezone</label>
                                <select name="timezone" class="form-select">
                                    <?php foreach ($timezones as $tz): ?>
                                        <option value="<?= $tz ?>" <?= ($settings['timezone'] ?? '') === $tz ? 'selected' : '' ?>><?= $tz ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">School Address</label>
                                <input type="text" name="school_address" class="form-control"
                                    value="<?= e($settings['school_address'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contact Number</label>
                                <input type="text" name="contact_number" class="form-control"
                                    value="<?= e($settings['contact_number'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">System Logo</label>
                                <input type="file" name="system_logo" class="form-control" accept="image/*">
                                <div class="form-text">PNG, JPG, SVG. Max 2MB.</div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header-custom">
                    <h5 class="card-title"><i class="bi bi-key-fill"></i> Change Password</h5>
                </div>
                <div class="card-body-custom">
                    <form method="POST">
                        <input type="hidden" name="change_password" value="1">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control" minlength="6" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-key me-1"></i> Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header-custom">
                    <h5 class="card-title"><i class="bi bi-info-circle"></i> Current Settings</h5>
                </div>
                <div class="card-body-custom">
                    <dl style="font-size:13px;">
                        <?php foreach ([
                            'school_name' => 'School Name',
                            'academic_year' => 'Academic Year',
                            'late_time_threshold' => 'Late Threshold',
                            'timezone' => 'Timezone',
                            'school_address' => 'Address',
                            'contact_number' => 'Contact'
                        ] as $key => $label): ?>
                            <dt style="font-weight:600;color:var(--text-3);font-size:11px;text-transform:uppercase;letter-spacing:.5px;margin-bottom:2px;"><?= $label ?></dt>
                            <dd style="color:var(--text);margin-bottom:12px;"><?= e($settings[$key] ?? '—') ?></dd>
                        <?php endforeach; ?>
                    </dl>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header-custom">
                    <h5 class="card-title"><i class="bi bi-image"></i> Current Logo</h5>
                </div>
                <div class="card-body-custom text-center">
                    <?php if (!empty($settings['system_logo'])): ?>
                        <img src="<?= BASE_URL ?>/<?= e($settings['system_logo']) ?>" alt="Logo"
                            style="max-width:100%;max-height:100px;object-fit:contain;">
                    <?php else: ?>
                        <div style="padding:24px;color:var(--text-3);">
                            <i class="bi bi-image" style="font-size:36px;display:block;margin-bottom:8px;opacity:.3;"></i>
                            No logo uploaded
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header-custom">
                    <h5 class="card-title"><i class="bi bi-cpu"></i> System Info</h5>
                </div>
                <div class="card-body-custom">
                    <dl style="font-size:13px;">
                        <dt style="font-weight:600;color:var(--text-3);font-size:11px;text-transform:uppercase;letter-spacing:.5px;margin-bottom:2px;">PHP Version</dt>
                        <dd style="margin-bottom:12px;"><?= PHP_VERSION ?></dd>
                        <dt style="font-weight:600;color:var(--text-3);font-size:11px;text-transform:uppercase;letter-spacing:.5px;margin-bottom:2px;">Server Time</dt>
                        <dd style="margin-bottom:12px;"><?= date('Y-m-d H:i:s') ?></dd>
                        <dt style="font-weight:600;color:var(--text-3);font-size:11px;text-transform:uppercase;letter-spacing:.5px;margin-bottom:2px;">System Version</dt>
                        <dd style="margin-bottom:0;">AttendTech v1.0</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

</div>
</div>
<?php include BASE_PATH . '/includes/footer.php'; ?>
