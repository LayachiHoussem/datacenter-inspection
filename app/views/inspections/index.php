<?php
view('layouts.header', ['title' => 'Facility Inspections']);
view('layouts.sidebar');
view('layouts.navbar', ['title' => 'Datacenter Inspection Logs']);
?>

<div class="card">
    <div class="card-header">
        <div class="card-title">
            <i class="fa-solid fa-clipboard-check"></i> Inspection Records
        </div>
        <?php if (can_write('inspections')): ?>
        <a href="/inspections/create" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Initiate Inspection
        </a>
        <?php endif; ?>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Ref Code</th>
                    <th>Inspection Title</th>
                    <th>Category</th>
                    <th>Equipment</th>
                    <th>Inspector</th>
                    <th>Status</th>
                    <th>Started At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($inspections)): ?>
                    <tr><td colspan="8" style="text-align: center; color: var(--text-dim);">No inspections found.</td></tr>
                <?php else: ?>
                    <?php foreach ($inspections as $i): ?>
                    <tr>
                        <td><span class="code-text" style="color: var(--accent-cyan); font-weight:600;"><?= sanitize($i['reference_code']) ?></span></td>
                        <td><strong><?= sanitize($i['title']) ?></strong></td>
                        <td><?= sanitize($i['category_name']) ?></td>
                        <td><?= sanitize($i['equipment_name'] ?? 'General Hall') ?></td>
                        <td><?= sanitize($i['inspector_name']) ?></td>
                        <td><?= status_badge($i['status'], 'Inspection Statuses') ?></td>
                        <td><?= sanitize($i['started_at']) ?></td>
                        <td>
                            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                <a href="/inspections/show?id=<?= $i['id'] ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-eye"></i> Details</a>
                                <?php if ($i['status'] === 'completed' || $i['status'] === STATUS_COMPLETED): ?>
                                    <a href="/inspections/result?id=<?= $i['id'] ?>" class="btn btn-success btn-sm"><i class="fa-solid fa-chart-pie"></i> Result</a>
                                <?php endif; ?>
                                <?php if (can_write('inspections')): ?>
                                    <a href="/inspections/edit?id=<?= $i['id'] ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                                    <form action="/inspections/delete" method="POST" onsubmit="return confirm('Delete this inspection? This cannot be undone.');" style="display:inline-flex; margin:0;">
                                        <input type="hidden" name="id" value="<?= $i['id'] ?>">
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
