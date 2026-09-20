<?php
/**
 * Datacenter Inspection System - Authentication & Permissions Helper
 */

if (!function_exists('auth_init')) {
    function auth_init(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}

if (!function_exists('auth_check')) {
    function auth_check(): bool {
        auth_init();
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

if (!function_exists('auth_user')) {
    function auth_user(): ?array {
        auth_init();
        if (auth_check()) {
            return [
                'id' => $_SESSION['user_id'] ?? null,
                'name' => $_SESSION['user_name'] ?? 'User',
                'email' => $_SESSION['user_email'] ?? '',
                'role' => $_SESSION['user_role'] ?? ROLE_INSPECTOR,
                'avatar' => $_SESSION['user_avatar'] ?? null
            ];
        }
        return null;
    }
}

if (!function_exists('auth_login')) {
    function auth_login(array $user): void {
        auth_init();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_avatar'] = $user['avatar'] ?? null;
    }
}

if (!function_exists('auth_logout')) {
    function auth_logout(): void {
        auth_init();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
}

if (!function_exists('has_role')) {
    function has_role(string $role): bool {
        $user = auth_user();
        if (!$user) return false;
        if ($user['role'] === ROLE_ADMIN || $user['role'] === 'admin') return true;
        return $user['role'] === $role;
    }
}

if (!function_exists('is_admin')) {
    function is_admin(): bool {
        $user = auth_user();
        return $user && ($user['role'] === ROLE_ADMIN || $user['role'] === 'admin');
    }
}

if (!function_exists('can_access')) {
    function can_access(string $module): bool {
        return can_read($module);
    }
}

if (!function_exists('can_read')) {
    function can_read(string $module): bool {
        $user = auth_user();
        if (!$user) return false;
        return \App\Config\Constants::roleHasPermission($user['role'] ?? '', $module, 'read');
    }
}

if (!function_exists('can_write')) {
    function can_write(string $module): bool {
        $user = auth_user();
        if (!$user) return false;
        return \App\Config\Constants::roleHasPermission($user['role'] ?? '', $module, 'write');
    }
}
