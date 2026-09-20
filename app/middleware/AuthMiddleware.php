<?php
namespace App\Middleware;

class AuthMiddleware {
    public static function handle(): void {
        auth_init();
        if (!auth_check()) {
            set_flash('warning', 'Please sign in to access the system.');
            redirect('/login');
        }
    }
}
