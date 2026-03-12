<?php

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

define('LARAVEL_START', microtime(true));

$_SERVER['DOCUMENT_ROOT'] = __DIR__ . '/../public';
$_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SERVER_PORT'] = 443;
$_SERVER['HTTPS'] = 'on';

chdir(__DIR__ . '/../');

// Ensure /tmp directories exist for Vercel serverless
$directories = [
    '/tmp/storage/app/public',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/framework/views',
    '/tmp/storage/logs',
    '/tmp/bootstrap/cache',
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Point Laravel storage to /tmp
$_ENV['APP_STORAGE'] = '/tmp';

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../public/index.php';
