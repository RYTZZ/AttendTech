<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $pdo  = getPDO();
        $stmt = $pdo->prepare('SELECT id, username, password, full_name FROM admins WHERE username = ?');
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            loginAdmin($admin['id'], $admin['username'], $admin['full_name']);
            header('Location: ' . BASE_URL . '/admin/dashboard.php');
            exit;
        }
        $error = 'Invalid username or password.';
    } else {
        $error = 'Please enter your username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | AttendTech</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        html, body { height: 100%; margin: 0; padding: 0; }
        body { min-height: 100vh; overflow-x: hidden; }
    </style>
</head>
<body>
<div class="login-page">

    <div class="login-panel-left">
        <div class="lp-brand">
            <div class="lp-brand-icon">
                <i class="bi bi-qr-code-scan"></i>
            </div>
            <span class="lp-brand-name">AttendTech</span>
        </div>

        <div class="lp-hero">
            <div class="lp-hero-icon">
                <i class="bi bi-qr-code-scan"></i>
            </div>
            <h1 class="lp-hero-title">Automated QR Attendance Monitoring</h1>
            <p class="lp-hero-sub">
                A digital attendance system for secondary high school using QR code technology.
                Fast, accurate, and paperless.
            </p>

            <div class="lp-features">
                <div class="lp-feature">
                    <div class="lp-feature-icon"><i class="bi bi-qr-code"></i></div>
                    <span class="lp-feature-text">Instant QR Code scanning for time in & out</span>
                </div>
                <div class="lp-feature">
                    <div class="lp-feature-icon"><i class="bi bi-bar-chart-fill"></i></div>
                    <span class="lp-feature-text">Real-time dashboard analytics and reports</span>
                </div>
                <div class="lp-feature">
                    <div class="lp-feature-icon"><i class="bi bi-file-earmark-excel-fill"></i></div>
                    <span class="lp-feature-text">Export weekly & monthly attendance reports</span>
                </div>
                <div class="lp-feature">
                    <div class="lp-feature-icon"><i class="bi bi-shield-check-fill"></i></div>
                    <span class="lp-feature-text">Duplicate scan prevention & late detection</span>
                </div>
            </div>
        </div>

        <div class="lp-footer">
            Bicol University Gubat Campus &nbsp;·&nbsp; Academic Year 2025–2026
        </div>
    </div>

    <div class="login-panel-right">
        <div class="login-form-wrap">

            <div class="login-form-header">
                <div class="login-school-badge">
                    <i class="bi bi-building"></i>
                    Bicol University Gubat Campus
                </div>
                <h2 class="login-form-title">Welcome back</h2>
                <p class="login-form-sub">Sign in to your administrator account to continue.</p>
            </div>

            <?php if ($error): ?>
                <div class="login-error">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" autocomplete="off" id="loginForm">
                <div class="login-field">
                    <label class="login-label" for="username">Username</label>
                    <div class="login-input-wrap">
                        <i class="bi bi-person-fill login-input-icon"></i>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="login-input"
                            placeholder="Enter your username"
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                            required
                            autofocus
                            autocomplete="username">
                    </div>
                </div>

                <div class="login-field">
                    <label class="login-label" for="password">Password</label>
                    <div class="login-input-wrap">
                        <i class="bi bi-lock-fill login-input-icon"></i>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="login-input"
                            placeholder="Enter your password"
                            required
                            autocomplete="current-password">
                    </div>
                </div>

                <button type="submit" class="login-btn" id="loginBtn">
                    <i class="bi bi-shield-lock-fill"></i>
                    Sign In
                </button>
            </form>

            <p class="login-footer-note">
                AttendTech v1.0 &nbsp;·&nbsp; PHP <?= PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ?> &nbsp;·&nbsp; <?= date('Y') ?>
            </p>
        </div>
    </div>

</div>
<script>
document.getElementById('loginForm').addEventListener('submit', function () {
    var btn = document.getElementById('loginBtn');
    btn.classList.add('loading');
    btn.innerHTML = '<i class="bi bi-arrow-repeat" style="animation:spin 1s linear infinite;"></i> Signing in…';
});
</script>
</body>
</html>
