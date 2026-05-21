<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/includes/session.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'stats':
        echo json_encode(['success' => true, 'stats' => getTodayStats()]);
        break;

    case 'recent_logs':
        $logs = getRecentLogs(20);
        $formatted = array_map(function ($log) {
            return [
                'full_name' => $log['full_name'],
                'lrn' => $log['lrn'],
                'photo' => $log['photo'],
                'grade_name' => $log['grade_name'],
                'section_name' => $log['section_name'],
                'time_in' => $log['time_in'] ? date('h:i:s A', strtotime($log['time_in'])) : null,
                'time_out' => $log['time_out'] ? date('h:i:s A', strtotime($log['time_out'])) : null,
                'status' => $log['status'],
            ];
        }, $logs);
        echo json_encode(['success' => true, 'logs' => $formatted]);
        break;

    case 'weekly_chart':
        echo json_encode(['success' => true, 'data' => getWeeklyChartData()]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
