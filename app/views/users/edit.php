<?php
view('layouts.header', ['title' => (can_write('users') ? 'Edit User' : 'View User')]);
view('layouts.sidebar');
view('layouts.navbar', ['title' => (can_write('users') ? 'Edit User Account: ' : 'User Account Details: ') . sanitize($user['name'])]);
?>

<div class="card" style="max-width:650px; margin:0 auto;">
    <div class="card-header">
        <div class="card-title">
            <i class="fa-solid <?= can_write('users') ? 'fa-user-gear' : 'fa-user' ?>"></i> 
            <?= can_write('users') ? 'Update User Parameters' : 'User Account Details' ?>
        </div>
        <a href="/users" class="btn btn-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
    </div>

    <form action="/users/update" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $user['id'] ?>">

        <div class="form-group">
            <label class="form-label" for="name">Full Name</label>
            <input type="text" id="name" name="name" class="form-control" value="<?= sanitize($user['name']) ?>" <?= !can_write('users') ? 'readonly' : '' ?> required>
        </div>

        <div class="form-group">
            <label class="form-label" for="email">Email Address</label>
            <input type="email" id="email" name="email" class="form-control" value="<?= sanitize($user['email']) ?>" <?= !can_write('users') ? 'readonly' : '' ?> required>
        </div>

        <?php if (can_write('users')): ?>
        <div class="form-group">
            <label class="form-label" for="password">Change Password (Leave empty to keep current password)</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="New password...">
        </div>
        <?php endif; ?>

        <div class="form-group">
            <label class="form-label" for="role">Role Permission</label>
            <?php if (can_write('users')): ?>
            <select id="role" name="role" class="form-control" required>
                <?php 
                $rolesList = $roles ?? \App\Config\Constants::getRoleOptions();
                foreach ($rolesList as $roleValue => $roleLabel): 
                ?>
                    <option value="<?= sanitize($roleValue) ?>" <?= ($user['role'] ?? '') === $roleValue ? 'selected' : '' ?>>
                        <?= sanitize($roleLabel) ?> (<?= sanitize($roleValue) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php else: ?>
                <input type="text" class="form-control" value="<?= strtoupper(sanitize($user['role'])) ?>" readonly>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label" for="status">Account Status</label>
            <?php if (can_write('users')): ?>
            <select id="status" name="status" class="form-control">
                <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>ACTIVE</option>
                <option value="inactive" <?= $user['status'] === 'inactive' ? 'selected' : '' ?>>INACTIVE</option>
            </select>
            <?php else: ?>
                <input type="text" class="form-control" value="<?= strtoupper(sanitize($user['status'])) ?>" readonly>
            <?php endif; ?>
        </div>

        <?php if (can_write('users')): ?>
        <button type="submit" class="btn btn-primary" style="width:100%;">
            <i class="fa-solid fa-floppy-disk"></i> Update User
        </button>
        <?php else: ?>
        <div class="alert alert-info" style="margin-top:12px; margin-bottom:0;">
            <i class="fa-solid fa-lock"></i> Read-only mode: User parameters cannot be modified.
        </div>
        <?php endif; ?>
    </form>
</div>

<?php view('layouts.footer'); ?>
