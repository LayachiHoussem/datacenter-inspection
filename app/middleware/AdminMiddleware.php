<?php
namespace App\Middleware;

class AdminMiddleware {
    public static function handle(string $module = 'settings', string $level = 'read'): void {
        AuthMiddleware::handle();
        $user = auth_user();
        $hasPermission = ($level === 'write') ? can_write($module) : can_read($module);
        if (!$user || !$hasPermission) {
            set_flash('danger', 'Access denied. You do not have permission to access this section.');
            redirect('/dashboard');
        }
    }
}
