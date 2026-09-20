<?php
view('layouts.header', ['title' => 'Create User']);
view('layouts.sidebar');
view('layouts.navbar', ['title' => 'Create System User Account']);
?>

<div class="card" style="max-width:650px; margin:0 auto;">
    <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-user-plus"></i> User Details</div>
        <a href="/users" class="btn btn-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i> Cancel</a>
    </div>

    <form action="/users/store" method="POST">
        <?= csrf_field() ?>

        <div class="form-group">
            <label class="form-label" for="name">Full Name</label>
            <input type="text" id="name" name="name" class="form-control" placeholder="e.g. John Doe" value="<?= sanitize($old['name'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label" for="email">Email Address</label>
            <input type="email" id="email" name="email" class="form-control" placeholder="user@datacenter.local" value="<?= sanitize($old['email'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
        </div>

        <div class="form-group">
            <label class="form-label" for="role">Role Permission</label>
            <select id="role" name="role" class="form-control" required>
                <?php 
                $rolesList = $roles ?? \App\Config\Constants::getRoleOptions();
                foreach ($rolesList as $roleValue => $roleLabel): 
                ?>
                    <option value="<?= sanitize($roleValue) ?>" <?= ($old['role'] ?? 'inspector') === $roleValue ? 'selected' : '' ?>>
                        <?= sanitize($roleLabel) ?> (<?= sanitize($roleValue) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label" for="status">Account Status</label>
            <select id="status" name="status" class="form-control">
                <option value="active">ACTIVE</option>
                <option value="inactive">INACTIVE</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;">
            <i class="fa-solid fa-user-check"></i> Create User Account
        </button>
    </form>
</div>

<?php view('layouts.footer'); ?>
