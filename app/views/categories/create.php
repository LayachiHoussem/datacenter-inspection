<?php
view('layouts.header', ['title' => 'New Category']);
view('layouts.sidebar');
view('layouts.navbar', ['title' => 'Create Inspection Category']);
?>

<div class="card" style="max-width:650px; margin:0 auto;">
    <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-folder-plus"></i> New Category Details</div>
        <a href="/categories" class="btn btn-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i> Cancel</a>
    </div>

    <form action="/categories/store" method="POST">
        <?= csrf_field() ?>

        <div class="form-group">
            <label class="form-label" for="name">Category Name</label>
            <input type="text" id="name" name="name" class="form-control" placeholder="e.g. HVAC & Environmental Cooling" value="<?= sanitize($old['name'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label" for="icon">FontAwesome Icon Class</label>
            <input type="text" id="icon" name="icon" class="form-control" placeholder="e.g. fa-snowflake or fa-bolt" value="<?= sanitize($old['icon'] ?? 'fa-list-check') ?>">
        </div>

        <div class="form-group">
            <label class="form-label" for="description">Description</label>
            <textarea id="description" name="description" class="form-control" rows="3" placeholder="Category scope and details..."><?= sanitize($old['description'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;">
            <i class="fa-solid fa-floppy-disk"></i> Create Category
        </button>
    </form>
</div>

<?php view('layouts.footer'); ?>
