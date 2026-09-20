<?php
/**
 * Application Configuration
 */

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? '0.0.0.0:8000';
$appUrl = getenv('APP_URL') ?: "{$protocol}://{$host}";

return [
    'app_name' => 'Datacenter Inspection System',
    'app_url' => $appUrl,
    'env' => getenv('APP_ENV') ?: 'development',
    'debug' => true,
    'timezone' => 'UTC',
    
    // Upload settings
    'upload' => [
        'max_size' => 5 * 1024 * 1024, // 5MB
        'allowed_types' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
        'path' => __DIR__ . '/../../public/uploads/inspections/'
    ],

    // Storage settings
    'storage' => [
        'reports' => __DIR__ . '/../../storage/reports/',
        'logs' => __DIR__ . '/../../storage/logs/',
        'cache' => __DIR__ . '/../../storage/cache/'
    ]
];
