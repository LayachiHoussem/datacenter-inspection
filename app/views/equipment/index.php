<?php
view('layouts.header', ['title' => 'Equipment Inventory']);
view('layouts.sidebar');
view('layouts.navbar', ['title' => 'Datacenter Equipment Racks & Hardware']);
?>

<div class="card">
    <div class="card-header">
        <div class="card-title">
            <i class="fa-solid fa-server"></i> Hardware & Facilities Inventory
        </div>
        <?php if (can_write('equipment')): ?>
        <a href="/equipment/create" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Add Equipment Unit
        </a>
        <?php endif; ?>
    </div>

    <!-- Filter Toolbar -->
    <div style="background: var(--bg-page); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 14px 16px; margin-bottom: 20px;">
        <form action="/equipment" method="GET" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;">
            <div style="flex: 1 1 220px; min-width: 200px;">
                <label class="form-label" for="filter-name" style="font-size: 0.8rem; margin-bottom: 4px; color: var(--text-secondary);">
                    <i class="fa-solid fa-magnifying-glass"></i> Equipment Name / Serial
                </label>
                <input type="text" id="filter-name" name="name" class="form-control" placeholder="Search by name or serial..." value="<?= sanitize($searchName ?? '') ?>">
            </div>

            <div style="flex: 1 1 200px; min-width: 180px;">
                <label class="form-label" for="filter-category" style="font-size: 0.8rem; margin-bottom: 4px; color: var(--text-secondary);">
                    <i class="fa-solid fa-layer-group"></i> Category
                </label>
                <select id="filter-category" name="category_id" class="form-control">
                    <option value="">All Categories</option>
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= (isset($selectedCategory) && (int)$selectedCategory === (int)$cat['id']) ? 'selected' : '' ?>>
                                <?= sanitize($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-filter"></i> Filter
                </button>
                <?php if (!empty($selectedCategory) || !empty($searchName)): ?>
                    <a href="/equipment" class="btn btn-secondary" title="Clear all filters">
                        <i class="fa-solid fa-rotate-left"></i> Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <?php if (!empty($selectedCategory) || !empty($searchName)): ?>
            <div style="margin-top: 10px; font-size: 0.82rem; color: var(--text-secondary); display: flex; align-items: center; gap: 6px;">
                <i class="fa-solid fa-circle-info"></i>
                <span>Showing filtered results (<strong><?= count($equipmentList) ?></strong> found)</span>
            </div>
        <?php endif; ?>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Equipment Name</th>
                    <th>Serial Number</th>
                    <th>Category</th>
                    <th>Location / Room</th>
                    <th>Status</th>
                    <th>Last Inspected</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($equipmentList)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center; color:var(--text-dim); padding: 28px 16px;">
                            <?php if (!empty($selectedCategory) || !empty($searchName)): ?>
                                <i class="fa-solid fa-filter-circle-xmark" style="font-size: 1.6rem; display: block; margin-bottom: 8px; color: var(--text-secondary);"></i>
                                No equipment units matched your filter criteria.<br>
                                <a href="/equipment" class="btn btn-secondary btn-sm" style="margin-top: 10px; display: inline-flex;">
                                    <i class="fa-solid fa-rotate-left"></i> Clear Filters
                                </a>
                            <?php else: ?>
                                No equipment units added yet.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($equipmentList as $eq): ?>
                    <tr>
                        <td><strong><?= sanitize($eq['name']) ?></strong></td>
                        <td><span class="code-text" style="color:var(--accent-cyan);"><?= sanitize($eq['serial_number']) ?></span></td>
                        <td><?= sanitize($eq['category_name']) ?></td>
                        <td><?= sanitize($eq['room_location']) ?></td>
                        <td><span class="badge badge-<?= sanitize($eq['status']) ?>"><?= sanitize($eq['status']) ?></span></td>
                        <td><?= $eq['last_inspected_at'] ? sanitize($eq['last_inspected_at']) : '<span style="color:var(--text-dim);">Never</span>' ?></td>
                        <td>
                            <div style="display:flex; gap:6px;">
                                <?php if (can_write('equipment')): ?>
                                    <a href="/equipment/edit?id=<?= $eq['id'] ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                                    <form action="/equipment/delete" method="POST" data-confirm="Delete this equipment unit?" style="display:inline-flex; margin:0;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= $eq['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i> Delete</button>
                                    </form>
                                <?php else: ?>
                                    <span class="badge badge-secondary" style="font-size:0.75rem;"><i class="fa-solid fa-lock"></i> Read-Only</span>
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
