<?php
view('layouts.header', ['title' => 'Preventive Maintenance Plan']);
view('layouts.sidebar');
view('layouts.navbar', ['title' => 'Preventive Maintenance Management']);

$flash = get_flash();
?>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] ?>">
    <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
    <?= $flash['message'] ?>
</div>
<?php endif; ?>

<!-- Top Metric KPI Cards -->
<div class="maint-kpi-grid">
    <div class="maint-kpi-card kpi-total">
        <div class="maint-kpi-info">
            <h4>Total Plans</h4>
            <div class="kpi-num"><?= (int)($stats['total'] ?? 0) ?></div>
        </div>
        <div class="maint-kpi-icon">
            <i class="fa-solid fa-list-check"></i>
        </div>
    </div>

    <div class="maint-kpi-card kpi-scheduled">
        <div class="maint-kpi-info">
            <h4>Scheduled</h4>
            <div class="kpi-num"><?= (int)($stats['scheduled'] ?? 0) ?></div>
        </div>
        <div class="maint-kpi-icon">
            <i class="fa-solid fa-calendar-day"></i>
        </div>
    </div>

    <div class="maint-kpi-card kpi-in-progress">
        <div class="maint-kpi-info">
            <h4>In Progress</h4>
            <div class="kpi-num"><?= (int)($stats['in_progress'] ?? 0) ?></div>
        </div>
        <div class="maint-kpi-icon">
            <i class="fa-solid fa-clock-rotate-left"></i>
        </div>
    </div>

    <div class="maint-kpi-card kpi-overdue">
        <div class="maint-kpi-info">
            <h4>Overdue / Alert</h4>
            <div class="kpi-num"><?= (int)($stats['overdue'] ?? 0) ?></div>
        </div>
        <div class="maint-kpi-icon">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
    </div>

    <div class="maint-kpi-card kpi-completed">
        <div class="maint-kpi-info">
            <h4>Completed</h4>
            <div class="kpi-num"><?= (int)($stats['completed'] ?? 0) ?></div>
        </div>
        <div class="maint-kpi-icon">
            <i class="fa-solid fa-circle-check"></i>
        </div>
    </div>
</div>

<!-- View Mode Switcher and Primary Action -->
<div class="view-tabs-container">
    <div class="view-pills">
        <button type="button" class="view-pill-btn <?= $activeView === 'kanban' ? 'active' : '' ?>" data-view="kanban">
            <i class="fa-solid fa-table-columns"></i> Kanban Board
        </button>
        <button type="button" class="view-pill-btn <?= $activeView === 'calendar' ? 'active' : '' ?>" data-view="calendar">
            <i class="fa-solid fa-calendar-days"></i> Calendar View
        </button>
        <button type="button" class="view-pill-btn <?= $activeView === 'table' ? 'active' : '' ?>" data-view="table">
            <i class="fa-solid fa-table-list"></i> Table View
        </button>
    </div>

    <div style="display: flex; gap: 10px;">
        <?php if (can_write('maintenance')): ?>
        <a href="/maintenance/create" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-calendar-plus"></i> Plan Maintenance
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Filter Bar -->
<form method="GET" action="/maintenance" class="maint-filter-bar" id="maint-filter-form">
    <input type="hidden" name="view" value="<?= sanitize($activeView) ?>">
    
    <div class="maint-filter-item" style="flex: 1 1 240px; position: relative;">
        <label for="maint-search-input">Search Keyword</label>
        <div style="position: relative; display: flex; align-items: center;">
            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; color: #94a3b8; pointer-events: none; font-size: 0.85rem;"></i>
            <input type="text" id="maint-search-input" name="search" class="form-control" style="padding-left: 32px; padding-right: <?= !empty($filters['search']) ? '32px' : '12px' ?>;" placeholder="Search title, code, equipment, technician..." value="<?= sanitize($filters['search'] ?? '') ?>" autocomplete="off">
            <?php if (!empty($filters['search'])): ?>
                <a href="/maintenance?<?= http_build_query(array_merge($filters, ['search' => '', 'view' => $activeView])) ?>" style="position: absolute; right: 10px; color: #94a3b8; text-decoration: none; cursor: pointer;" title="Clear search keyword">
                    <i class="fa-solid fa-circle-xmark"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="maint-filter-item">
        <label>Equipment Unit</label>
        <select name="equipment_id" class="form-control" onchange="this.form.submit()">
            <option value="">All Equipment Units</option>
            <?php foreach ($equipments as $eq): ?>
                <option value="<?= $eq['id'] ?>" <?= (!empty($filters['equipment_id']) && $filters['equipment_id'] == $eq['id']) ? 'selected' : '' ?>>
                    <?= sanitize($eq['name']) ?> (<?= sanitize($eq['room_location']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="maint-filter-item">
        <label>Priority</label>
        <select name="priority" class="form-control" onchange="this.form.submit()">
            <option value="">All Priorities</option>
            <option value="critical" <?= ($filters['priority'] ?? '') === 'critical' ? 'selected' : '' ?>>Critical</option>
            <option value="high" <?= ($filters['priority'] ?? '') === 'high' ? 'selected' : '' ?>>High</option>
            <option value="medium" <?= ($filters['priority'] ?? '') === 'medium' ? 'selected' : '' ?>>Medium</option>
            <option value="low" <?= ($filters['priority'] ?? '') === 'low' ? 'selected' : '' ?>>Low</option>
        </select>
    </div>

    <div class="maint-filter-item">
        <label>Status</label>
        <select name="status" class="form-control" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <option value="scheduled" <?= ($filters['status'] ?? '') === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
            <option value="in_progress" <?= ($filters['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
            <option value="completed" <?= ($filters['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
            <option value="overdue" <?= ($filters['status'] ?? '') === 'overdue' ? 'selected' : '' ?>>Overdue</option>
            <option value="cancelled" <?= ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
        </select>
    </div>

    <div style="display: flex; gap: 8px;">
        <button type="submit" class="btn btn-secondary" title="Execute Search & Filters">
            <i class="fa-solid fa-filter"></i> Filter
        </button>
        <?php if (!empty(array_filter($filters))): ?>
            <a href="/maintenance?view=<?= sanitize($activeView) ?>" class="btn btn-outline" style="border: 1px solid var(--border-color);" title="Clear all filters">
                <i class="fa-solid fa-xmark"></i> Reset
            </a>
        <?php endif; ?>
    </div>
</form>

<!-- =========================================================
     VIEW 1: KANBAN BOARD
     ========================================================= -->
<div id="maint-view-kanban" style="display: <?= $activeView === 'kanban' ? 'block' : 'none' ?>;">
    <?php
    $columns = [
        'scheduled'   => ['title' => 'Scheduled Plans', 'icon' => 'fa-calendar-day', 'color' => '#3b82f6'],
        'in_progress' => ['title' => 'In Progress', 'icon' => 'fa-clock-rotate-left', 'color' => '#f6b100'],
        'completed'   => ['title' => 'Completed', 'icon' => 'fa-circle-check', 'color' => '#03c95a'],
        'overdue'     => ['title' => 'Overdue / Alerts', 'icon' => 'fa-triangle-exclamation', 'color' => '#ef4444'],
    ];

    $groupedPlans = [
        'scheduled'   => [],
        'in_progress' => [],
        'completed'   => [],
        'overdue'     => []
    ];

    foreach ($plans as $p) {
        if ($p['status'] === 'completed') {
            $groupedPlans['completed'][] = $p;
        } elseif ($p['status'] === 'in_progress') {
            $groupedPlans['in_progress'][] = $p;
        } elseif ($p['status'] === 'overdue') {
            $groupedPlans['overdue'][] = $p;
        } else {
            $groupedPlans['scheduled'][] = $p;
        }
    }
    ?>

    <div class="kanban-board-container">
        <?php foreach ($columns as $statusKey => $col): ?>
        <div class="kanban-column kanban-col-<?= $statusKey ?>" data-status="<?= $statusKey ?>">
            <div class="kanban-col-header">
                <div class="kanban-col-title">
                    <i class="fa-solid <?= $col['icon'] ?>" style="color: <?= $col['color'] ?>;"></i>
                    <span><?= $col['title'] ?></span>
                </div>
                <span class="kanban-col-count"><?= count($groupedPlans[$statusKey]) ?></span>
            </div>

            <div class="kanban-cards-wrapper" data-status="<?= $statusKey ?>">
                <?php if (empty($groupedPlans[$statusKey])): ?>
                    <div style="text-align: center; padding: 30px 10px; color: #94a3b8; font-size: 0.85rem;">
                        <i class="fa-regular fa-folder-open" style="font-size: 1.5rem; margin-bottom: 6px; display: block; opacity: 0.5;"></i>
                        No <?= strtolower($col['title']) ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($groupedPlans[$statusKey] as $card): ?>
                    <div class="kanban-card" draggable="true" data-plan-id="<?= $card['id'] ?>">
                        <div class="kanban-card-top">
                            <span class="kanban-code"><?= sanitize($card['plan_code']) ?></span>
                            <span class="badge badge-priority-<?= $card['priority'] ?>">
                                <?= strtoupper($card['priority']) ?>
                            </span>
                        </div>

                        <div class="kanban-card-title">
                            <a href="/maintenance/show?id=<?= $card['id'] ?>">
                                <?= sanitize($card['title']) ?>
                            </a>
                        </div>

                        <div class="kanban-card-meta">
                            <div class="kanban-card-meta-item" title="Equipment: <?= sanitize($card['equipment_name'] ?? 'Facility') ?>">
                                <i class="fa-solid fa-server"></i>
                                <span><?= sanitize($card['equipment_name'] ?? 'Facility General') ?></span>
                                <?php if (!empty($card['room_location'])): ?>
                                    <span style="color: #94a3b8;">(<?= sanitize($card['room_location']) ?>)</span>
                                <?php endif; ?>
                            </div>

                            <div class="kanban-card-meta-item">
                                <i class="fa-solid fa-user-gear"></i>
                                <span><?= sanitize($card['assigned_name'] ?? 'Unassigned') ?></span>
                            </div>

                            <div style="margin-top: 4px;">
                                <span class="recurrence-chip" title="Dynamic period">
                                    <i class="fa-solid fa-arrows-rotate"></i> <?= sanitize($card['recurrence_label']) ?>
                                </span>
                            </div>
                        </div>

                        <div class="kanban-card-bottom">
                            <div class="kanban-due-date <?= !empty($card['is_overdue']) ? 'is-overdue' : '' ?>">
                                <i class="fa-regular fa-clock"></i>
                                <span><?= sanitize($card['scheduled_date']) ?> <?= substr($card['scheduled_time'] ?? '09:00', 0, 5) ?></span>
                            </div>

                            <div style="display: flex; gap: 4px;">
                                <a href="/maintenance/show?id=<?= $card['id'] ?>" class="kanban-actions-btn" title="View details">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <?php if (can_write('maintenance')): ?>
                                <a href="/maintenance/edit?id=<?= $card['id'] ?>" class="kanban-actions-btn" title="Edit plan">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- =========================================================
     VIEW 2: INTERACTIVE CALENDAR VIEW
     ========================================================= -->
<div id="maint-view-calendar" style="display: <?= $activeView === 'calendar' ? 'block' : 'none' ?>;">
    <div class="calendar-wrapper">
        <div class="calendar-header">
            <div class="calendar-title-nav">
                <button type="button" class="calendar-nav-btn" id="cal-prev-btn">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                <div class="calendar-month-title" id="cal-month-title">Current Month</div>
                <button type="button" class="calendar-nav-btn" id="cal-next-btn">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
                <button type="button" class="calendar-nav-btn" id="cal-today-btn">
                    Today
                </button>
            </div>

            <div style="display: flex; align-items: center; gap: 14px; font-size: 0.8rem; color: #64748b; flex-wrap: wrap;">
                <span style="display: inline-flex; align-items: center; gap: 5px;"><span style="width: 10px; height: 10px; border-radius: 50%; background: #ef4444;"></span> Critical</span>
                <span style="display: inline-flex; align-items: center; gap: 5px;"><span style="width: 10px; height: 10px; border-radius: 50%; background: #f97316;"></span> High</span>
                <span style="display: inline-flex; align-items: center; gap: 5px;"><span style="width: 10px; height: 10px; border-radius: 50%; background: #3b82f6;"></span> Medium</span>
                <span style="display: inline-flex; align-items: center; gap: 5px;"><span style="width: 10px; height: 10px; border-radius: 50%; background: #64748b;"></span> Low</span>
            </div>
        </div>

        <div class="calendar-grid">
            <div class="calendar-weekday-header">Mon</div>
            <div class="calendar-weekday-header">Tue</div>
            <div class="calendar-weekday-header">Wed</div>
            <div class="calendar-weekday-header">Thu</div>
            <div class="calendar-weekday-header">Fri</div>
            <div class="calendar-weekday-header">Sat</div>
            <div class="calendar-weekday-header">Sun</div>
        </div>

        <div class="calendar-grid" id="calendar-grid-body">
            <!-- Rendered by maintenance.js -->
        </div>
    </div>
</div>

<!-- =========================================================
     VIEW 3: TABLE / LIST VIEW
     ========================================================= -->
<div id="maint-view-table" style="display: <?= $activeView === 'table' ? 'block' : 'none' ?>;">
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid fa-list-check"></i> Preventive Maintenance Plans (<?= count($plans) ?>)
            </div>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Plan Title</th>
                        <th>Equipment & Room</th>
                        <th>Dynamic Recurrence</th>
                        <th>Priority</th>
                        <th>Scheduled Date</th>
                        <th>Assigned Tech</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($plans)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                                <i class="fa-solid fa-calendar-xmark" style="font-size: 2rem; opacity: 0.4; margin-bottom: 8px; display: block;"></i>
                                No preventive maintenance plans matching current criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($plans as $p): ?>
                        <tr>
                            <td>
                                <span class="code-text" style="color: #0284c7; font-weight: 700;">
                                    <?= sanitize($p['plan_code']) ?>
                                </span>
                            </td>
                            <td>
                                <strong><a href="/maintenance/show?id=<?= $p['id'] ?>" style="color: var(--text-primary);"><?= sanitize($p['title']) ?></a></strong>
                                <?php if (!empty($p['category_name'])): ?>
                                    <div style="font-size: 0.78rem; color: #64748b;"><?= sanitize($p['category_name']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div><strong><?= sanitize($p['equipment_name'] ?? 'Facility General') ?></strong></div>
                                <?php if (!empty($p['room_location'])): ?>
                                    <div style="font-size: 0.78rem; color: #8c92b3;"><i class="fa-solid fa-location-dot"></i> <?= sanitize($p['room_location']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="recurrence-chip">
                                    <i class="fa-solid fa-arrows-rotate"></i> <?= sanitize($p['recurrence_label']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-priority-<?= $p['priority'] ?>">
                                    <?= strtoupper($p['priority']) ?>
                                </span>
                            </td>
                            <td>
                                <div><strong><?= sanitize($p['scheduled_date']) ?></strong></div>
                                <div style="font-size: 0.78rem; color: #64748b;"><?= substr($p['scheduled_time'] ?? '09:00', 0, 5) ?> (<?= $p['estimated_duration_minutes'] ?>m)</div>
                            </td>
                            <td>
                                <?= sanitize($p['assigned_name'] ?? 'Unassigned') ?>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                    <?= status_badge($p['status'], 'Inspection Statuses') ?>
                                    <?php if (!empty($p['is_overdue']) && $p['status'] === 'scheduled'): ?>
                                        <span class="badge badge-fail" style="background: #fee2e2; color: #b91c1c; border: 1px solid #f87171; font-weight: 700; font-size: 0.72rem; padding: 2px 6px;" title="Scheduled date is in the past">
                                            <i class="fa-solid fa-triangle-exclamation"></i> OVERDUE
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px; flex-wrap: wrap; align-items: center;">
                                    <a href="/maintenance/show?id=<?= $p['id'] ?>" class="btn btn-secondary btn-sm" title="View details">
                                        <i class="fa-solid fa-eye"></i> Details
                                    </a>
                                    <?php if (can_write('maintenance')): ?>
                                        <a href="/maintenance/edit?id=<?= $p['id'] ?>" class="btn btn-primary btn-sm" title="Edit plan">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit
                                        </a>
                                        <form action="/maintenance/delete" method="POST" onsubmit="return confirm('Delete this preventive maintenance plan?');" style="display: inline-flex; margin: 0;">
                                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Delete plan">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
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
</div>

<!-- =========================================================
     MODAL: CALENDAR EVENT DETAILS
     ========================================================= -->
<div class="cal-modal-backdrop" id="cal-event-modal">
    <div class="cal-modal-content">
        <div class="cal-modal-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="code-text" id="modal-event-code" style="color: #0284c7; font-weight: 700; background: #e0f2fe; padding: 2px 8px; border-radius: 4px;"></span>
                <span id="modal-event-priority"></span>
            </div>
            <button type="button" class="cal-modal-close">&times;</button>
        </div>
        <div class="cal-modal-body">
            <h3 id="modal-event-title" style="margin: 0 0 16px; font-size: 1.15rem; color: var(--text-primary);"></h3>

            <table style="width: 100%; font-size: 0.88rem; line-height: 2;">
                <tr>
                    <td style="width: 140px; color: #64748b;"><strong>Target Unit:</strong></td>
                    <td id="modal-event-equipment" style="font-weight: 600; color: var(--text-primary);"></td>
                </tr>
                <tr>
                    <td style="color: #64748b;"><strong>Schedule:</strong></td>
                    <td id="modal-event-datetime" style="font-weight: 600; color: #0284c7;"></td>
                </tr>
                <tr>
                    <td style="color: #64748b;"><strong>Recurrence:</strong></td>
                    <td id="modal-event-recurrence"></td>
                </tr>
                <tr>
                    <td style="color: #64748b;"><strong>Assigned To:</strong></td>
                    <td id="modal-event-assigned" style="font-weight: 600;"></td>
                </tr>
                <tr>
                    <td style="color: #64748b;"><strong>Current Status:</strong></td>
                    <td id="modal-event-status"></td>
                </tr>
            </table>
        </div>
        <div class="cal-modal-footer">
            <button type="button" class="btn btn-secondary" data-modal-dismiss="true">Close</button>
            <a href="#" id="modal-view-plan-btn" class="btn btn-primary">Open Full Plan &rarr;</a>
        </div>
    </div>
</div>

<!-- Embed Calendar Data for maintenance.js -->
<?php
$calendarEvents = [];
foreach ($plans as $p) {
    $priorityColors = [
        'low' => '#64748b',
        'medium' => '#3b82f6',
        'high' => '#f97316',
        'critical' => '#ef4444'
    ];
    $calendarEvents[] = [
        'id' => $p['id'],
        'code' => $p['plan_code'],
        'title' => $p['title'],
        'date' => $p['scheduled_date'],
        'time' => substr($p['scheduled_time'] ?? '09:00', 0, 5),
        'equipment' => $p['equipment_name'] ?? 'Facility General',
        'room' => $p['room_location'] ?? '',
        'assigned' => $p['assigned_name'] ?? 'Unassigned',
        'status' => $p['status'],
        'priority' => $p['priority'],
        'recurrence' => $p['recurrence_label'],
        'color' => $priorityColors[$p['priority']] ?? '#3b82f6',
        'duration' => $p['estimated_duration_minutes'] . 'm'
    ];
}
?>
<script>
window.maintCalendarEvents = <?= json_encode($calendarEvents, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>

<?php view('layouts.footer'); ?>
