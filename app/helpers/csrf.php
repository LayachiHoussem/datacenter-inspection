<?php
/**
 * Datacenter Inspection System - CSRF Helper
 */

if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        $token = csrf_token();
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
}

if (!function_exists('csrf_verify')) {
    function csrf_verify(): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }
}
