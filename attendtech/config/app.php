<?php
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

if (!defined('BASE_URL')) {
    $projectRoot = str_replace('\\', '/', realpath(BASE_PATH));
    $docRoot     = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
    $baseUrl     = str_replace($docRoot, '', $projectRoot);
    define('BASE_URL', rtrim($baseUrl, '/'));
}
