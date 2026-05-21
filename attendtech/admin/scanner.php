<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/includes/header.php';
requireLogin();
$pageTitle = 'QR Scanner';

$settings   = getAllSettings();
$lateTime   = $settings['late_time_threshold'] ?? '07:30:00';

$extraScripts = [
    '<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>',
    '<script src="' . BASE_URL . '/assets/js/scanner.js"></script>'
];
?>
<?php include BASE_PATH . '/includes/sidebar.php'; ?>
<div class="main-wrapper">
<?php include BASE_PATH . '/includes/navbar.php'; ?>
<div class="page-content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="font-head fw-700 mb-1">QR Code Scanner</h4>
            <p style="color:var(--text-3);font-size:13px;margin:0;">
                Late threshold: <strong><?= date('h:i A', strtotime($lateTime)) ?></strong>
                &nbsp;·&nbsp; Today: <strong><?= date('l, F j, Y') ?></strong>
            </p>
        </div>
        <div id="scannerStatus" class="text-muted fw-600" style="font-size:14px;">Camera inactive</div>
    </div>

    <?php
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
               || (int)$_SERVER['SERVER_PORT'] === 443;
    $isLocal = in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1', '::1']);
    $cameraOk = $isHttps || $isLocal;
    ?>

    <?php if (!$cameraOk): ?>
    <div class="alert alert-warning d-flex align-items-center gap-3 mb-4">
        <i class="bi bi-exclamation-triangle-fill" style="font-size:20px;flex-shrink:0;"></i>
        <div>
            <strong>Camera requires HTTPS or localhost.</strong>
            You are accessing the system over <code>http://</code> from a non-local address.
            Camera features will not work. Please enable HTTPS on your server or access via <code>localhost</code>.
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="scanner-wrapper">
                <div class="scanner-box">
                    <div class="scanner-header">
                        <h4><i class="bi bi-qr-code-scan me-2"></i>Scan Attendance</h4>
                        <p>Point camera at student QR code to record attendance</p>
                    </div>

                    <div id="qr-reader"></div>

                    <div class="scanner-controls">
                        <button class="btn btn-success" id="startBtn" <?= !$cameraOk ? 'disabled title="HTTPS required"' : '' ?>>
                            <i class="bi bi-camera-video-fill me-2"></i>Start Camera
                        </button>
                        <button class="btn btn-danger" id="stopBtn" disabled>
                            <i class="bi bi-stop-circle-fill me-2"></i>Stop Camera
                        </button>
                    </div>

                    <div id="scanResultArea"></div>
                </div>
            </div>

            <?php if (!$cameraOk): ?>
            <div class="mt-3 p-3" style="background:#1e293b;border-radius:12px;color:rgba(255,255,255,0.6);font-size:13px;">
                <i class="bi bi-info-circle me-2"></i>
                To enable camera: access via <strong style="color:#fff;">http://localhost/<?= basename(BASE_PATH) ?>/admin/scanner.php</strong>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header-custom">
                    <h5 class="card-title"><i class="bi bi-clock-history"></i> Today's Scan Log</h5>
                    <button class="btn btn-sm btn-outline-secondary" onclick="refreshLog()">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                </div>
                <div id="scanLogBody" style="max-height:420px;overflow-y:auto;">
                    <div class="empty-state">
                        <i class="bi bi-qr-code"></i>
                        <p>Scanned records will appear here.</p>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header-custom">
                    <h5 class="card-title"><i class="bi bi-info-circle"></i> How It Works</h5>
                </div>
                <div class="card-body-custom">
                    <div class="d-flex flex-column gap-2">
                        <div class="d-flex align-items-center gap-3 p-2" style="border-radius:8px;background:var(--success-light);">
                            <span style="font-size:18px;width:28px;text-align:center;">1️⃣</span>
                            <div><strong style="color:var(--success);">First Scan</strong> — Records <strong>Time In</strong></div>
                        </div>
                        <div class="d-flex align-items-center gap-3 p-2" style="border-radius:8px;background:var(--info-light);">
                            <span style="font-size:18px;width:28px;text-align:center;">2️⃣</span>
                            <div><strong style="color:var(--info);">Second Scan</strong> — Records <strong>Time Out</strong></div>
                        </div>
                        <div class="d-flex align-items-center gap-3 p-2" style="border-radius:8px;background:var(--danger-light);">
                            <span style="font-size:18px;width:28px;text-align:center;">🚫</span>
                            <div><strong style="color:var(--danger);">Third Scan</strong> — Rejected (already complete)</div>
                        </div>
                        <div class="d-flex align-items-center gap-3 p-2" style="border-radius:8px;background:var(--warning-light);">
                            <span style="font-size:18px;width:28px;text-align:center;">⏰</span>
                            <div><strong style="color:var(--warning);">Late</strong> — Scanned after <?= date('h:i A', strtotime($lateTime)) ?></div>
                        </div>
                    </div>
                    <div class="mt-3 p-2" style="background:var(--surface2);border-radius:8px;font-size:12px;color:var(--text-3);">
                        <i class="bi bi-shield-check me-1 text-success"></i>
                        Camera permission must be <strong>Allowed</strong> in your browser.
                        Chrome users: click the <i class="bi bi-camera"></i> icon in the address bar.
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
</div>
<script>
function refreshLog() {
    var body = document.getElementById('scanLogBody');
    if (!body) return;
    body.innerHTML = '<div class="text-center p-3" style="color:var(--text-3);"><i class="bi bi-arrow-repeat spin"></i></div>';
    fetch(BASE_URL + '/ajax/fetch_dashboard.php?action=recent_logs')
        .then(function(r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(function(data) {
            if (!data.success || !data.logs || data.logs.length === 0) {
                body.innerHTML =
                    '<div class="empty-state"><i class="bi bi-qr-code"></i><p>No scans recorded today yet.</p></div>';
                return;
            }
            var rows = data.logs.map(function(log) {
                var photoHtml = log.photo
                    ? '<img src="' + BASE_URL + '/' + escHtml(log.photo) + '" alt="" style="width:100%;height:100%;object-fit:cover;">'
                    : '<i class="bi bi-person-fill"></i>';
                return '<tr>' +
                    '<td>' +
                        '<div class="student-info">' +
                            '<div class="student-avatar">' + photoHtml + '</div>' +
                            '<div>' +
                                '<span class="student-name">' + escHtml(log.full_name) + '</span>' +
                                '<span class="student-lrn">'  + escHtml(log.lrn)       + '</span>' +
                            '</div>' +
                        '</div>' +
                    '</td>' +
                    '<td>' + escHtml(log.time_in  || '—') + '</td>' +
                    '<td>' + escHtml(log.time_out || '—') + '</td>' +
                    '<td><span class="badge-status badge-' + log.status.toLowerCase() + '">' + escHtml(log.status) + '</span></td>' +
                '</tr>';
            }).join('');
            body.innerHTML =
                '<table class="table table-hover mb-0" style="font-size:13px;">' +
                    '<thead><tr><th>Student</th><th>In</th><th>Out</th><th>Status</th></tr></thead>' +
                    '<tbody>' + rows + '</tbody>' +
                '</table>';
        })
        .catch(function() {
            body.innerHTML =
                '<div class="empty-state"><i class="bi bi-exclamation-circle"></i><p>Could not load scan log.</p></div>';
        });
}

function escHtml(str) {
    var d = document.createElement('div');
    d.textContent = String(str == null ? '' : str);
    return d.innerHTML;
}

document.addEventListener('DOMContentLoaded', function() {
    refreshLog();
    setInterval(refreshLog, 15000);
});
</script>
<?php include BASE_PATH . '/includes/footer.php'; ?>
