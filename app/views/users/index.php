<?php
view('layouts.header', ['title' => 'User Management']);
view('layouts.sidebar');
view('layouts.navbar', ['title' => 'User Accounts & Inspector Access']);
?>

<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-users"></i> System Accounts</div>
        <?php if (can_write('users')): ?>
        <a href="/users/create" class="btn btn-primary"><i class="fa-solid fa-user-plus"></i> Add New User</a>
        <?php endif; ?>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Name</th>
                    <th>Email Address</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Registered At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td>#<?= $u['id'] ?></td>
                    <td><strong><?= sanitize($u['name']) ?></strong></td>
                    <td><?= sanitize($u['email']) ?></td>
                    <td><span class="badge badge-info"><?= strtoupper(sanitize($u['role'])) ?></span></td>
                    <td><span class="badge badge-<?= sanitize($u['status']) ?>"><?= strtoupper(sanitize($u['status'])) ?></span></td>
                    <td><?= sanitize($u['created_at']) ?></td>
                    <td>
                        <div style="display:flex; gap:6px;">
                            <a href="/users/edit?id=<?= $u['id'] ?>" class="btn btn-secondary btn-sm">
                                <i class="fa-solid <?= can_write('users') ? 'fa-pen-to-square' : 'fa-eye' ?>"></i> 
                                <?= can_write('users') ? 'Edit' : 'View' ?>
                            </a>
                            <?php if (can_write('users')): ?>
                            <form action="/users/delete" method="POST" data-confirm="Delete user account?" style="display:inline-flex; margin:0;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i> Delete</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php view('layouts.footer'); ?>
