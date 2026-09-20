<?php
view('layouts.header', ['title' => 'Edit Equipment']);
view('layouts.sidebar');
view('layouts.navbar', ['title' => 'Edit Equipment Unit: ' . sanitize($equipment['name'])]);
?>

<div class="card" style="max-width:700px; margin:0 auto;">
    <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-pen-to-square"></i> Modify Hardware Details</div>
        <a href="/equipment" class="btn btn-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i> Cancel</a>
    </div>

    <form action="/equipment/update" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $equipment['id'] ?>">

        <div class="form-group">
            <label class="form-label" for="name">Equipment Unit Name</label>
            <input type="text" id="name" name="name" class="form-control" value="<?= sanitize($equipment['name']) ?>" required>
            <?php if (isset($errors['name'])): ?>
                <div style="color: #f43f5e; font-size: 0.8rem; margin-top: 4px;"><?= $errors['name'][0] ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label" for="serial_number">Serial Number / Asset Tag</label>
            <input type="text" id="serial_number" name="serial_number" class="form-control" value="<?= sanitize($equipment['serial_number']) ?>" required>
            <?php if (isset($errors['serial_number'])): ?>
                <div style="color: #f43f5e; font-size: 0.8rem; margin-top: 4px;"><?= $errors['serial_number'][0] ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label" for="category_id">Domain Category</label>
            <select id="category_id" name="category_id" class="form-control" required>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $equipment['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= sanitize($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['category_id'])): ?>
                <div style="color: #f43f5e; font-size: 0.8rem; margin-top: 4px;"><?= $errors['category_id'][0] ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label" for="room_location">Datacenter Room / Aisle Location</label>
            <input type="text" id="room_location" name="room_location" class="form-control" value="<?= sanitize($equipment['room_location']) ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label" for="status">Operational Status</label>
            <select id="status" name="status" class="form-control">
                <?php 
                $eqStatuses = \App\Config\Constants::getStatusOptions('Equipment Statuses');
                foreach ($eqStatuses as $stVal => $stLabel): 
                ?>
                    <option value="<?= sanitize($stVal) ?>" <?= ($equipment['status'] ?? 'active') === $stVal ? 'selected' : '' ?>>
                        <?= sanitize($stLabel) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;">
            <i class="fa-solid fa-floppy-disk"></i> Update Equipment Unit
        </button>
    </form>
</div>

<?php view('layouts.footer'); ?>
