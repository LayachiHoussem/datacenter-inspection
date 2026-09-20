<?php
view('layouts.header', ['title' => 'Edit Category']);
view('layouts.sidebar');
view('layouts.navbar', ['title' => (can_write('categories') ? 'Manage' : 'View') . ' Category & Checklist Template: ' . sanitize($category['name'])]);
?>

<div style="display:grid; grid-template-columns: 1fr 1fr; gap:24px;">
    <!-- Edit Category Form -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid <?= can_write('categories') ? 'fa-pen-to-square' : 'fa-folder-open' ?>"></i> Category Parameters
            </div>
            <a href="/categories" class="btn btn-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>

        <form action="/categories/update" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $category['id'] ?>">

            <div class="form-group">
                <label class="form-label" for="name">Category Name</label>
                <input type="text" id="name" name="name" class="form-control" value="<?= sanitize($category['name']) ?>" <?= !can_write('categories') ? 'readonly' : '' ?> required>
            </div>

            <div class="form-group">
                <label class="form-label" for="icon">FontAwesome Icon Class</label>
                <input type="text" id="icon" name="icon" class="form-control" value="<?= sanitize($category['icon']) ?>" <?= !can_write('categories') ? 'readonly' : '' ?>>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="3" <?= !can_write('categories') ? 'readonly' : '' ?>><?= sanitize($category['description']) ?></textarea>
            </div>

            <?php if (can_write('categories')): ?>
            <button type="submit" class="btn btn-primary" style="width:100%;">
                <i class="fa-solid fa-floppy-disk"></i> Update Category Info
            </button>
            <?php else: ?>
            <div class="alert alert-info" style="margin-top:12px; margin-bottom:0;">
                <i class="fa-solid fa-lock"></i> Read-only mode: Category parameters cannot be modified.
            </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- Checklist Template Manager -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="fa-solid fa-list-check" style="color:var(--accent-cyan);"></i> Category Checklist Items</div>
        </div>

        <!-- Existing Items -->
        <div style="margin-bottom:20px; max-height:280px; overflow-y:auto;">
            <?php if (empty($items)): ?>
                <div style="color:var(--text-dim); text-align:center; padding:20px;">No checklist items added yet.</div>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                    <div style="padding:10px; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <strong style="color:var(--text-primary);"><?= sanitize($item['title']) ?></strong>
                            <?php if ($item['is_critical']): ?>
                                <span class="badge badge-danger" style="margin-left:6px;">CRITICAL</span>
                            <?php endif; ?>
                            <div style="font-size:0.8rem; color:var(--text-muted);"><?= sanitize($item['description']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if (can_write('categories')): ?>
        <!-- Add New Checklist Item Form -->
        <h4 style="color:var(--text-primary); font-size:0.95rem; margin-bottom:12px; border-top:1px solid var(--border-color); padding-top:12px;">Add Checklist Inspection Item</h4>
        <form action="/categories/items/store" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="category_id" value="<?= $category['id'] ?>">

            <div class="form-group">
                <input type="text" name="title" class="form-control" placeholder="Item title (e.g. Test Emergency Battery Voltage)" required>
            </div>

            <div class="form-group">
                <input type="text" name="description" class="form-control" placeholder="Brief evaluation criteria...">
            </div>

            <div class="form-group" style="display:flex; align-items:center; gap:8px;">
                <input type="checkbox" id="is_critical" name="is_critical" value="1">
                <label for="is_critical" style="color:var(--text-muted); font-size:0.88rem;">Mark as Critical Failure Parameter</label>
            </div>

            <button type="submit" class="btn btn-success btn-sm" style="width:100%;">
                <i class="fa-solid fa-plus"></i> Add Item to Checklist
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php view('layouts.footer'); ?>
