<?php
view('layouts.header', ['title' => 'Domain Categories']);
view('layouts.sidebar');
view('layouts.navbar', ['title' => 'Inspection Domains & Checklist Setup']);
?>

<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-layer-group"></i> Facility Inspection Categories</div>
        <?php if (can_write('categories')): ?>
        <a href="/categories/create" class="btn btn-primary"><i class="fa-solid fa-plus"></i> New Category</a>
        <?php endif; ?>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Icon</th>
                    <th>Category Name</th>
                    <th>Slug</th>
                    <th>Description</th>
                    <th>Linked Equipment</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr><td colspan="6" style="text-align:center; color:var(--text-dim);">No domain categories created yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td style="font-size:1.4rem; color:var(--accent-cyan);"><i class="fa-solid <?= sanitize($cat['icon']) ?>"></i></td>
                        <td><strong><?= sanitize($cat['name']) ?></strong></td>
                        <td><span class="code-text" style="color:var(--text-muted);"><?= sanitize($cat['slug']) ?></span></td>
                        <td><?= sanitize($cat['description']) ?></td>
                        <td><span class="badge badge-info"><?= $cat['equipment_count'] ?> Units</span></td>
                        <td>
                            <div style="display:flex; gap:6px;">
                                <a href="/categories/edit?id=<?= $cat['id'] ?>" class="btn btn-secondary btn-sm">
                                    <i class="fa-solid <?= can_write('categories') ? 'fa-pen-to-square' : 'fa-eye' ?>"></i> 
                                    <?= can_write('categories') ? 'Checklists & Edit' : 'View Checklists' ?>
                                </a>
                                <?php if (can_write('categories')): ?>
                                <form action="/categories/delete" method="POST" data-confirm="Delete category?" style="display:inline-flex; margin:0;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i> Delete</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php view('layouts.footer'); ?>
