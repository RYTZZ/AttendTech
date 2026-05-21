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
        $pdo = getPDO();
        $stmt = $pdo->prepare('SELECT id, username, password, full_name FROM admins WHERE username = ?');
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        if ($admin && password_verify($password, $admin['password'])) {
            loginAdmin($admin['id'], $admin['username'], $admin['full_name']);
            header('Location: ' . BASE_URL . '/admin/dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Please enter username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | AttendTech</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="login-page">
    <div class="login-card">
        <div class="login-logo"><i class="bi bi-qr-code-scan"></i></div>
        <h1 class="login-title">AttendTech</h1>
        <p class="login-sub">Bicol University Gubat Campus<br>Automated Attendance System</p>
        <?php if ($error): ?>
            <div class="login-error"><i class="bi bi-exclamation-circle-fill me-2"></i><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" autocomplete="off">
            <div style="margin-bottom:16px;">
                <label class="login-label">Username</label>
                <input type="text" name="username" class="login-input" placeholder="Enter username"
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
            </div>
            <div style="margin-bottom:20px;">
                <label class="login-label">Password</label>
                <input type="password" name="password" class="login-input" placeholder="Enter password" required>
            </div>
            <button type="submit" class="login-btn">
                <i class="bi bi-shield-lock-fill me-2"></i>Sign In
            </button>
        </form>
        <p class="login-footer-note">Academic Year 2025–2026 &nbsp;·&nbsp; AttendTech v1.0</p>
    </div>
</div>
</body>
</html>
