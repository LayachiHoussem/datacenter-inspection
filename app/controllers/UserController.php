<?php
namespace App\Controllers;

use App\Middleware\AdminMiddleware;
use App\Models\User;
use App\Models\AuditLog;

class UserController {
    private User $userModel;
    private AuditLog $auditLog;

    public function __construct() {
        AdminMiddleware::handle('users', 'read');
        $this->userModel = new User();
        $this->auditLog = new AuditLog();
    }

    public function index(): void {
        $users = $this->userModel->all();
        view('users.index', ['users' => $users]);
    }

    public function create(): void {
        AdminMiddleware::handle('users', 'write');
        $roles = \App\Config\Constants::getRoleOptions();
        view('users.create', ['roles' => $roles]);
    }

    public function store(): void {
        AdminMiddleware::handle('users', 'write');
        if (!csrf_verify()) {
            set_flash('danger', 'CSRF verification failed.');
            redirect('/users/create');
        }

        $errors = validate($_POST, [
            'name' => 'required|min:3',
            'email' => 'required|email',
            'password' => 'required|min:6',
            'role' => 'required'
        ]);

        $roles = \App\Config\Constants::getRoleOptions();

        if (!empty($errors)) {
            view('users.create', ['errors' => $errors, 'old' => $_POST, 'roles' => $roles]);
            return;
        }

        if ($this->userModel->findByEmail(trim($_POST['email']))) {
            set_flash('danger', 'A user with this email address already exists.');
            view('users.create', ['old' => $_POST, 'roles' => $roles]);
            return;
        }

        $userId = $this->userModel->create([
            'name' => trim($_POST['name']),
            'email' => trim($_POST['email']),
            'password' => $_POST['password'],
            'role' => $_POST['role'],
            'status' => $_POST['status'] ?? 'active'
        ]);

        if ($userId) {
            $this->auditLog->log('Created User', 'User', $userId, "Created user: {$_POST['email']}");
            set_flash('success', 'User created successfully.');
            redirect('/users');
        } else {
            set_flash('danger', 'Failed to create user account.');
            redirect('/users/create');
        }
    }

    public function edit(): void {
        $id = (int)($_GET['id'] ?? 0);
        $user = $this->userModel->findById($id);
        if (!$user) {
            set_flash('danger', 'User not found.');
            redirect('/users');
        }
        $roles = \App\Config\Constants::getRoleOptions();
        view('users.edit', ['user' => $user, 'roles' => $roles]);
    }

    public function update(): void {
        AdminMiddleware::handle('users', 'write');
        if (!csrf_verify()) {
            set_flash('danger', 'CSRF verification failed.');
            redirect('/users');
        }

        $id = (int)($_POST['id'] ?? 0);
        $errors = validate($_POST, [
            'name' => 'required|min:3',
            'email' => 'required|email',
            'role' => 'required'
        ]);

        if (!empty($errors)) {
            $user = $this->userModel->findById($id);
            $roles = \App\Config\Constants::getRoleOptions();
            view('users.edit', ['errors' => $errors, 'user' => array_merge($user ?? [], $_POST), 'roles' => $roles]);
            return;
        }

        $this->userModel->update($id, [
            'name' => trim($_POST['name']),
            'email' => trim($_POST['email']),
            'password' => $_POST['password'] ?? '',
            'role' => $_POST['role'],
            'status' => $_POST['status'] ?? 'active'
        ]);

        $this->auditLog->log('Updated User', 'User', $id, "Updated user account #{$id}");
        set_flash('success', 'User profile updated.');
        redirect('/users');
    }

    public function delete(): void {
        AdminMiddleware::handle('users', 'write');
        if (!csrf_verify()) {
            set_flash('danger', 'CSRF verification failed.');
            redirect('/users');
        }
        $id = (int)($_POST['id'] ?? 0);
        
        $currentUser = auth_user();
        if ($currentUser['id'] == $id) {
            set_flash('danger', 'You cannot delete your own user account.');
            redirect('/users');
        }

        $this->userModel->delete($id);
        $this->auditLog->log('Deleted User', 'User', $id, "Deleted user #{$id}");
        set_flash('info', 'User deleted successfully.');
        redirect('/users');
    }
}
