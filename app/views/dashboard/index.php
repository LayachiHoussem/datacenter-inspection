<?php
view('layouts.header', ['title' => '']);
view('layouts.sidebar');
view('layouts.navbar', ['title' => 'Datacenter Overview']);
?>

<!-- Metrics Grid -->
<div class="metrics-grid">
    <div class="metric-card">
        <div class="metric-icon blue">
            <i class="fa-solid fa-clipboard-list"></i>
        </div>
        <div class="metric-data">
            <h3><span class="counter-val" data-target="<?= $totalInspections ?>"><?= $totalInspections ?></span> <span class="trend-badge up">+12.5%</span></h3>
            <p>Total Inspections Logged</p>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon green">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div class="metric-data">
            <h3><span class="counter-val" data-target="<?= $completedInspections ?>"><?= $completedInspections ?></span> <span class="trend-badge up">+8.1%</span></h3>
            <p>Completed Checks</p>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon amber">
            <i class="fa-solid fa-spinner"></i>
        </div>
        <div class="metric-data">
            <h3 class="counter-val" data-target="<?= $pendingInspections ?>"><?= $pendingInspections ?></h3>
            <p>Pending / In-Progress</p>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon primary">
            <i class="fa-solid fa-server"></i>
        </div>
        <div class="metric-data">
            <h3 class="counter-val" data-target="<?= $totalEquipment ?>"><?= $totalEquipment ?></h3>
            <p>Equipment Units Logged</p>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon green">
            <i class="fa-solid fa-shield-halved"></i>
        </div>
        <div class="metric-data">
            <h3><?= $avgScore ?>% <span class="trend-badge up">+2.4%</span></h3>
            <p>Average Compliance Score</p>
        </div>
    </div>
</div>

<!-- Main Row: Recent Inspections & Activity Audit -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
    <!-- Recent Inspections Card -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid fa-clock-rotate-left" style="color: var(--primary);"></i> Recent Facility Inspections
            </div>
            <?php if (can_write('inspections')): ?>
            <a href="/inspections/create" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus"></i> New Inspection
            </a>
            <?php endif; ?>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Ref Code</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Inspector</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentInspections)): ?>
                        <tr><td colspan="6" style="text-align: center; color: var(--text-secondary);">No inspections logged yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentInspections as $i): ?>
                        <tr>
                            <td><span class="code-text" style="color: var(--primary); font-weight:600;"><?= sanitize($i['reference_code']) ?></span></td>
                            <td><strong><?= sanitize($i['title']) ?></strong></td>
                            <td><?= sanitize($i['category_name']) ?></td>
                            <td><span class="badge badge-<?= sanitize($i['status']) ?>"><?= sanitize($i['status']) ?></span></td>
                            <td><?= sanitize($i['inspector_name']) ?></td>
                            <td>
                                <a href="/inspections/show?id=<?= $i['id'] ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-eye"></i> View</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Live Audit Stream Card -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid fa-list-ol" style="color: var(--color-success);"></i> System Activity Feed
            </div>
        </div>
        <ul class="timeline">
            <?php foreach ($recentLogs as $log): ?>
                <li class="timeline-item">
                    <div class="timeline-title"><?= sanitize($log['action']) ?> &bull; <?= sanitize($log['entity_type']) ?></div>
                    <div style="font-size: 0.82rem; color: var(--text-secondary); margin-top:2px;"><?= sanitize($log['details']) ?></div>
                    <div class="timeline-time"><?= sanitize($log['user_name'] ?? 'System') ?> &bull; <?= sanitize($log['created_at']) ?></div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<!-- Categories Grid -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <i class="fa-solid fa-layer-group" style="color: var(--primary);"></i> Datacenter Inspection Domain Categories
        </div>
    </div>
    <div class="category-grid">
        <?php foreach ($categories as $cat): ?>
            <div class="category-card">
                <div class="category-icon">
                    <i class="fa-solid <?= sanitize($cat['icon']) ?>"></i>
                </div>
                <div>
                    <h4 style="font-size: 0.95rem; color: var(--text-primary); font-weight:600;"><?= sanitize($cat['name']) ?></h4>
                    <p style="font-size: 0.82rem; color: var(--text-secondary); margin-top: 4px;"><?= sanitize($cat['description']) ?></p>
                    <div style="font-size: 0.78rem; color: var(--primary); margin-top: 8px; font-weight:600;">
                        <i class="fa-solid fa-server"></i> <?= $cat['equipment_count'] ?> Equipment Items Linked
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php view('layouts.footer'); ?>
