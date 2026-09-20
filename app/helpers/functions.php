<?php
/**
 * Datacenter Inspection System - Core Functions Helper
 */

if (!function_exists('view')) {
    function view(string $template, array $data = []): void {
        extract($data);
        $file = __DIR__ . '/../views/' . str_replace('.', '/', $template) . '.php';
        if (file_exists($file)) {
            require $file;
        } else {
            echo "View [{$template}] not found.";
        }
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url): void {
        header("Location: {$url}");
        exit;
    }
}

if (!function_exists('sanitize')) {
    function sanitize(?string $value): string {
        return htmlspecialchars(trim($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string {
        $path = ltrim($path, '/');
        return '/assets/' . $path;
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string {
        $path = ltrim($path, '/');
        return '/' . $path;
    }
}

if (!function_exists('set_flash')) {
    function set_flash(string $type, string $message): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['flash'] = [
            'type' => $type, // success, danger, warning, info
            'message' => $message
        ];
    }
}

if (!function_exists('get_flash')) {
    function get_flash(): ?array {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
}

if (!function_exists('json_response')) {
    function json_response(array $data, int $statusCode = 200): void {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
}

if (!function_exists('status_badge')) {
    /**
     * Render a dynamic status badge with custom colors from Constants registry
     */
    function status_badge(?string $status, string $category = '', ?string $label = null): string {
        $st = strtolower(trim($status ?? ''));
        if ($st === '') {
            return '<span class="badge badge-secondary">-</span>';
        }
        $color = \App\Config\Constants::getStatusColor($st, $category);
        $bg = \App\Config\Constants::hexToRgba($color, 0.14);
        $border = \App\Config\Constants::hexToRgba($color, 0.38);
        $cleanStatus = sanitize($st);
        $displayText = $label !== null ? sanitize($label) : strtoupper(str_replace('_', ' ', $cleanStatus));

        return "<span class=\"badge badge-{$cleanStatus}\" style=\"background: {$bg}; color: {$color}; border: 1px solid {$border}; font-weight: 600;\">{$displayText}</span>";
    }
}
if (!function_exists('enterprise_service')) {
    function enterprise_service(): \App\Services\EnterpriseService {
        static $service = null;
        if ($service === null) {
            $service = new \App\Services\EnterpriseService();
        }
        return $service;
    }
}

if (!function_exists('enterprise_setting')) {
    function enterprise_setting(?string $key = null, mixed $default = null): mixed {
        $srv = enterprise_service();
        if ($key === null) {
            return $srv->getSettings();
        }
        return $srv->get($key, $default);
    }
}

if (!function_exists('enterprise_name')) {
    function enterprise_name(): string {
        return (string)enterprise_setting('name', 'Datacenter Inspection System');
    }
}

if (!function_exists('enterprise_site')) {
    function enterprise_site(): string {
        return (string)enterprise_setting('site', 'PCR Datacenter Facility');
    }
}

if (!function_exists('enterprise_logo_url')) {
    function enterprise_logo_url(): string {
        return enterprise_service()->getLogoUrl();
    }
}

if (!function_exists('enterprise_logo_base64')) {
    function enterprise_logo_base64(): string {
        return enterprise_service()->getLogoBase64();
    }
}
