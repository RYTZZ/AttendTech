<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

// BASE_URL and BASE_PATH are set by config/app.php before this file is included.
// Fallback guards (should not be needed in normal flow):
if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__));
if (!defined('BASE_URL')) {
    $projectRoot = str_replace('\\', '/', realpath(BASE_PATH));
    $docRoot     = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
    define('BASE_URL', rtrim(str_replace($docRoot, '', $projectRoot), '/'));
}

$settings   = getAllSettings();
$schoolName = $settings['school_name'] ?? 'AttendTech';
$unreadCount = getUnreadNotificationCount();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Dashboard') ?> | <?= e($schoolName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <script>var BASE_URL = '<?= BASE_URL ?>';</script>
</head>
<body>
