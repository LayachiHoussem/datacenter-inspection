<?php
/**
 * Datacenter Inspection System - Front Controller
 */
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// PSR-4 class autoloader for App namespace (with Linux/Unix case-sensitive directory resolution)
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relativeClass = substr($class, $len);
    
    // 1. Direct path check
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require_once $file;
        return;
    }

    // 2. Linux Case-Insensitive / Lowercase directory and filename fallback
    $parts = explode('\\', $relativeClass);
    if (count($parts) > 1) {
        $rawClass = array_pop($parts);
        $subDir = strtolower(implode('/', $parts));
        
        // Try e.g. app/controllers/DashboardController.php
        $try1 = $baseDir . $subDir . '/' . $rawClass . '.php';
        if (file_exists($try1)) {
            require_once $try1;
            return;
        }

        // Try lowercase file: e.g. app/config/database.php
        $try2 = $baseDir . $subDir . '/' . strtolower($rawClass) . '.php';
        if (file_exists($try2)) {
            require_once $try2;
            return;
        }
    }
});

// Load core config and helper function files
require_once __DIR__ . '/../app/config/constants.php';
if (file_exists(__DIR__ . '/../app/config/database.php')) {
    require_once __DIR__ . '/../app/config/database.php';
} elseif (file_exists(__DIR__ . '/../app/config/Database.php')) {
    require_once __DIR__ . '/../app/config/Database.php';
}
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/csrf.php';
require_once __DIR__ . '/../app/helpers/validation.php';

// Load Composer / Vendor Autoloader (for Dompdf, etc.)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}
if (file_exists(__DIR__ . '/../vendor/dompdf/autoload.inc.php')) {
    require_once __DIR__ . '/../vendor/dompdf/autoload.inc.php';
}

// Parse and normalize request URI
$rawUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

// Strip /public/index.php, /public, or /index.php prefixes if present (common on shared hosts like InfinityFree)
if (strpos($rawUri, '/public/index.php') === 0) {
    $rawUri = substr($rawUri, 17);
} elseif (strpos($rawUri, '/public') === 0) {
    $rawUri = substr($rawUri, 7);
}
if (strpos($rawUri, '/index.php') === 0) {
    $rawUri = substr($rawUri, 10);
}

$uri = '/' . ltrim($rawUri, '/');
$uri = rtrim($uri, '/');
if (empty($uri)) {
    $uri = '/';
}
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// Simple MVC Router Mapping
$routes = [
    'GET' => [
        '/' => ['App\Controllers\DashboardController', 'index'],
        '/index.php' => ['App\Controllers\DashboardController', 'index'],
        '/login' => ['App\Controllers\AuthController', 'login'],
        '/logout' => ['App\Controllers\AuthController', 'logout'],
        '/forgot-password' => ['App\Controllers\AuthController', 'forgotPassword'],
        '/reset-password' => ['App\Controllers\AuthController', 'resetPassword'],
        '/dashboard' => ['App\Controllers\DashboardController', 'index'],
        
        '/inspections' => ['App\Controllers\InspectionController', 'index'],
        '/inspections/create' => ['App\Controllers\InspectionController', 'create'],
        '/inspections/show' => ['App\Controllers\InspectionController', 'show'],
        '/inspections/edit' => ['App\Controllers\InspectionController', 'edit'],
        '/inspections/result' => ['App\Controllers\InspectionController', 'result'],
        
        '/maintenance' => ['App\Controllers\MaintenanceController', 'index'],
        '/maintenance/create' => ['App\Controllers\MaintenanceController', 'create'],
        '/maintenance/show' => ['App\Controllers\MaintenanceController', 'show'],
        '/maintenance/edit' => ['App\Controllers\MaintenanceController', 'edit'],
        '/maintenance/events' => ['App\Controllers\MaintenanceController', 'calendarEvents'],
        
        '/reports' => ['App\Controllers\ReportController', 'index'],
        '/reports/show' => ['App\Controllers\ReportController', 'show'],
        
        '/equipment' => ['App\Controllers\EquipmentController', 'index'],
        '/equipment/create' => ['App\Controllers\EquipmentController', 'create'],
        '/equipment/edit' => ['App\Controllers\EquipmentController', 'edit'],
        
        '/categories' => ['App\Controllers\CategoryController', 'index'],
        '/categories/create' => ['App\Controllers\CategoryController', 'create'],
        '/categories/edit' => ['App\Controllers\CategoryController', 'edit'],
        
        '/users' => ['App\Controllers\UserController', 'index'],
        '/users/create' => ['App\Controllers\UserController', 'create'],
        '/users/edit' => ['App\Controllers\UserController', 'edit'],
        
        '/settings' => ['App\Controllers\SettingsController', 'index'],
        '/settings/download' => ['App\Controllers\SettingsController', 'download'],
    ],
    'POST' => [
        '/login' => ['App\Controllers\AuthController', 'authenticate'],
        '/forgot-password' => ['App\Controllers\AuthController', 'sendResetLink'],
        '/reset-password' => ['App\Controllers\AuthController', 'updatePassword'],
        
        '/inspections/store' => ['App\Controllers\InspectionController', 'store'],
        '/inspections/update' => ['App\Controllers\InspectionController', 'updateResults'],
        '/inspections/delete' => ['App\Controllers\InspectionController', 'delete'],
        
        '/maintenance/store' => ['App\Controllers\MaintenanceController', 'store'],
        '/maintenance/update' => ['App\Controllers\MaintenanceController', 'update'],
        '/maintenance/delete' => ['App\Controllers\MaintenanceController', 'delete'],
        '/maintenance/status' => ['App\Controllers\MaintenanceController', 'updateStatus'],
        '/maintenance/complete' => ['App\Controllers\MaintenanceController', 'markComplete'],
        '/maintenance/email' => ['App\Controllers\MaintenanceController', 'sendNotificationEmail'],
        '/maintenance/attachments/delete' => ['App\Controllers\MaintenanceController', 'deleteAttachment'],
        '/maintenance/attachments/upload' => ['App\Controllers\MaintenanceController', 'uploadAttachments'],
        
        '/equipment/store' => ['App\Controllers\EquipmentController', 'store'],
        '/equipment/update' => ['App\Controllers\EquipmentController', 'update'],
        '/equipment/delete' => ['App\Controllers\EquipmentController', 'delete'],
        
        '/categories/store' => ['App\Controllers\CategoryController', 'store'],
        '/categories/update' => ['App\Controllers\CategoryController', 'update'],
        '/categories/delete' => ['App\Controllers\CategoryController', 'delete'],
        '/categories/items/store' => ['App\Controllers\CategoryController', 'storeItem'],
        
        '/users/store' => ['App\Controllers\UserController', 'store'],
        '/users/update' => ['App\Controllers\UserController', 'update'],
        '/users/delete' => ['App\Controllers\UserController', 'delete'],
        
        '/inspections/email' => ['App\Controllers\InspectionController', 'sendReportEmail'],
        '/reports/email' => ['App\Controllers\ReportController', 'sendReportEmail'],
        
        '/settings/update' => ['App\Controllers\SettingsController', 'update'],
        '/settings/enterprise/update' => ['App\Controllers\SettingsController', 'updateEnterprise'],
        '/settings/enterprise/logo-reset' => ['App\Controllers\SettingsController', 'resetEnterpriseLogo'],
        '/settings/mail/update' => ['App\Controllers\SettingsController', 'updateMail'],
        '/settings/mail/test' => ['App\Controllers\SettingsController', 'testMail'],
        '/settings/constants/store' => ['App\Controllers\SettingsController', 'storeConstant'],
        '/settings/constants/update' => ['App\Controllers\SettingsController', 'updateConstant'],
        '/settings/constants/delete' => ['App\Controllers\SettingsController', 'deleteConstant'],
    ]
];

// Dispatch route with exception handling
try {
    if (isset($routes[$method][$uri])) {
        [$controllerClass, $action] = $routes[$method][$uri];
        if (class_exists($controllerClass)) {
            $controller = new $controllerClass();
            if (method_exists($controller, $action)) {
                $controller->$action();
                exit;
            }
        }
    }

    // 404 Handler
    http_response_code(404);
    if (auth_check()) {
        view('layouts.header', ['title' => '404 - Page Not Found']);
        echo '<div class="main-content" style="padding: 40px; text-align: center;"><h1>404 - Page Not Found</h1><p>The requested URL was not found on this server.</p><a href="/dashboard" class="btn btn-primary">Return to Dashboard</a></div>';
        view('layouts.footer');
    } else {
        // If not authenticated and attempting login or root, render login directly without redirect loop
        if ($uri === '/login' || $uri === '/' || $uri === '/index.php') {
            (new \App\Controllers\AuthController())->login();
        } else {
            view('layouts.header', ['title' => '404 - Page Not Found']);
            echo '<div class="main-content" style="padding: 40px; text-align: center;"><h1>404 - Page Not Found</h1><p>The requested URL was not found on this server.</p><a href="/login" class="btn btn-primary">Sign In</a></div>';
            view('layouts.footer');
        }
    }
} catch (\Throwable $e) {
    http_response_code(500);
    echo '<div style="font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif; padding: 30px; max-width: 680px; margin: 40px auto; border: 1px solid #fca5a5; border-radius: 10px; background: #fff5f5; color: #991b1b; box-shadow: 0 4px 12px rgba(0,0,0,0.06);">';
    echo '<h2 style="margin-top: 0; color: #b91c1c; display: flex; align-items: center; gap: 8px;"><span>⚠️</span> Application Configuration Notice</h2>';
    echo '<p style="margin-bottom: 8px; font-size: 0.95rem;">An issue occurred while running the application:</p>';
    echo '<div style="background: #fee2e2; padding: 12px 14px; border-radius: 6px; font-family: monospace; font-size: 0.88rem; overflow-x: auto; margin-bottom: 14px;">' . htmlspecialchars($e->getMessage()) . '</div>';
    if (str_contains($e->getMessage(), 'on null') || str_contains($e->getMessage(), 'SQLSTATE') || str_contains($e->getMessage(), 'Database')) {
        echo '<p style="font-size: 0.85rem; color: #475569; margin-bottom: 0;"><strong>Tip:</strong> Please verify your database connection credentials in <code>app/config/Database.php</code>. On InfinityFree, the MySQL host is <em>not</em> localhost (e.g. <code>sqlxxx.infinityfree.com</code>).</p>';
    }
    echo '</div>';
    error_log("Datacenter Inspection Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
}
