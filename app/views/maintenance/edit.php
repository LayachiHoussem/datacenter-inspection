<?php
view('layouts.header', ['title' => 'Edit Maintenance Plan - ' . $plan['plan_code']]);
view('layouts.sidebar');
view('layouts.navbar', ['title' => 'Edit Preventive Maintenance Plan']);

$flash = get_flash();
?>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] ?>">
    <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
    <?= $flash['message'] ?>
</div>
<?php endif; ?>

<div class="card" style="max-width: 980px; margin: 0 auto;">
    <div class="card-header">
        <div class="card-title">
            <i class="fa-solid fa-pen-to-square"></i> Edit Plan: <?= sanitize($plan['plan_code']) ?>
        </div>
        <a href="/maintenance/show?id=<?= $plan['id'] ?>" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> View Plan
        </a>
    </div>

    <div style="padding: 24px;">
        <form method="POST" action="/maintenance/update" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $plan['id'] ?>">

            <?php
            // Extract assigned equipment IDs
            $assignedIds = !empty($plan['equipments']) 
                ? array_column($plan['equipments'], 'id') 
                : (!empty($plan['equipment_id']) ? [(int)$plan['equipment_id']] : []);
            ?>

            <!-- Section 1: General Info -->
            <div style="margin-bottom: 24px;">
                <h4 style="font-size: 1rem; color: var(--text-primary); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                    <i class="fa-solid fa-circle-info" style="color: var(--primary);"></i> 1. Scope & Target Equipment
                </h4>

                <div class="form-group" style="margin-bottom: 18px;">
                    <label style="font-weight: 600;">Maintenance Task Title <span style="color: var(--color-danger);">*</span></label>
                    <input type="text" name="title" class="form-control" required value="<?= sanitize($plan['title']) ?>" style="font-size: 0.95rem;">
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 18px;">
                    <div class="form-group">
                        <label style="font-weight: 600;">Facility Domain / Category Filter</label>
                        <select name="category_id" class="form-control" id="maint-category-select">
                            <option value="">-- All Categories (Show All Equipment) --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $plan['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                    <?= sanitize($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color: #64748b; font-size: 0.76rem; margin-top: 3px; display: block;">
                            Filter target equipment below by selected facility domain.
                        </small>
                    </div>

                    <div class="form-group">
                        <label style="font-weight: 600;">Assigned Inspector / Engineer</label>
                        <select name="assigned_to" class="form-control">
                            <option value="">-- Unassigned --</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= $plan['assigned_to'] == $u['id'] ? 'selected' : '' ?>>
                                    <?= sanitize($u['name']) ?> (<?= sanitize($u['email']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label style="font-weight: 600;">Priority Level</label>
                        <select name="priority" class="form-control">
                            <option value="critical" <?= $plan['priority'] === 'critical' ? 'selected' : '' ?>>Critical</option>
                            <option value="high" <?= $plan['priority'] === 'high' ? 'selected' : '' ?>>High</option>
                            <option value="medium" <?= $plan['priority'] === 'medium' ? 'selected' : '' ?>>Medium</option>
                            <option value="low" <?= $plan['priority'] === 'low' ? 'selected' : '' ?>>Low</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label style="font-weight: 600;">Status</label>
                        <select name="status" class="form-control">
                            <option value="scheduled" <?= $plan['status'] === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                            <option value="in_progress" <?= $plan['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="completed" <?= $plan['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="overdue" <?= $plan['status'] === 'overdue' ? 'selected' : '' ?>>Overdue</option>
                            <option value="cancelled" <?= $plan['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                </div>

                <!-- Target Equipment Multi-Unit Selector -->
                <div class="form-group" style="margin-bottom: 0;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <label style="font-weight: 600; margin: 0;">
                            <i class="fa-solid fa-server" style="color: var(--primary); margin-right: 4px;"></i> Target Equipment Units (Multi-Select)
                        </label>
                        <span class="equipment-counter-badge">
                            <strong id="equipment-selected-count"><?= count($assignedIds) ?></strong> equipment selected
                        </span>
                    </div>

                    <div class="multi-equipment-box">
                        <div class="equipment-picker-toolbar">
                            <div style="position: relative; flex: 1; max-width: 380px;">
                                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 10px; top: 9px; color: #94a3b8; font-size: 0.85rem;"></i>
                                <input type="text" id="equipment-search-input" class="form-control form-control-sm equipment-search-input" placeholder="Quick search equipment..." style="padding-left: 30px;">
                            </div>
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <button type="button" id="btn-select-all-equipment" class="btn btn-secondary btn-sm" style="font-size: 0.76rem; padding: 4px 10px;">
                                    <i class="fa-solid fa-check-double"></i> Select Visible (<span id="equipment-visible-count"><?= count($equipments) ?></span>)
                                </button>
                                <button type="button" id="btn-deselect-all-equipment" class="btn btn-secondary btn-sm" style="font-size: 0.76rem; padding: 4px 10px;">
                                    <i class="fa-solid fa-xmark"></i> Clear
                                </button>
                            </div>
                        </div>

                        <div class="equipment-check-list">
                            <?php foreach ($equipments as $eq): 
                                $isAssigned = in_array((int)$eq['id'], $assignedIds, true);
                                $searchText = strtolower($eq['name'] . ' ' . ($eq['serial_number'] ?? '') . ' ' . ($eq['room_location'] ?? '') . ' ' . ($eq['category_name'] ?? ''));
                            ?>
                            <label class="equipment-check-item <?= $isAssigned ? 'is-checked' : '' ?>" 
                                   data-category-id="<?= (int)($eq['category_id'] ?? 0) ?>" 
                                   data-search-text="<?= sanitize($searchText) ?>">
                                <input type="checkbox" name="equipment_ids[]" value="<?= $eq['id'] ?>" <?= $isAssigned ? 'checked' : '' ?>>
                                <div class="equipment-check-info">
                                    <div class="equipment-check-name"><?= sanitize($eq['name']) ?></div>
                                    <div class="equipment-check-meta">
                                        <?php if (!empty($eq['serial_number'])): ?>
                                            <span><i class="fa-solid fa-barcode"></i> <?= sanitize($eq['serial_number']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($eq['room_location'])): ?>
                                            <span><i class="fa-solid fa-location-dot"></i> <?= sanitize($eq['room_location']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($eq['category_name'])): ?>
                                            <span style="color: var(--primary); font-weight: 500;"><i class="fa-solid fa-tag"></i> <?= sanitize($eq['category_name']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </label>
                            <?php endforeach; ?>

                            <div id="equipment-empty-filtered" style="display: none; padding: 24px; text-align: center; color: #94a3b8; font-size: 0.88rem; flex-direction: column; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-filter-circle-xmark" style="font-size: 1.5rem; color: #cbd5e1;"></i>
                                No equipment units match the selected filter.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Dynamic Recurrence & Timing -->
            <div style="margin-bottom: 24px;">
                <h4 style="font-size: 1rem; color: var(--text-primary); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                    <i class="fa-solid fa-arrows-rotate" style="color: var(--primary);"></i> 2. Dynamic Period & Scheduling
                </h4>

                <label style="font-weight: 600; display: block; margin-bottom: 8px;">Recurrence Frequency:</label>

                <div class="recurrence-selector-grid">
                    <?php
                    $recTypes = [
                        'one_time' => ['label' => 'One-Time', 'icon' => 'fa-bolt'],
                        'daily' => ['label' => 'Daily', 'icon' => 'fa-sun'],
                        'weekly' => ['label' => 'Weekly', 'icon' => 'fa-calendar-week'],
                        'bi_weekly' => ['label' => 'Bi-Weekly', 'icon' => 'fa-calendar-days'],
                        'monthly' => ['label' => 'Monthly', 'icon' => 'fa-calendar'],
                        'quarterly' => ['label' => 'Quarterly', 'icon' => 'fa-chart-pie'],
                        'semi_annually' => ['label' => '6 Months', 'icon' => 'fa-hourglass-half'],
                        'annually' => ['label' => 'Annually', 'icon' => 'fa-award'],
                        'custom' => ['label' => 'Custom Period', 'icon' => 'fa-sliders'],
                    ];
                    foreach ($recTypes as $rKey => $rMeta):
                    ?>
                    <label class="recurrence-radio-label">
                        <input type="radio" name="recurrence_type" value="<?= $rKey ?>" <?= $plan['recurrence_type'] === $rKey ? 'checked' : '' ?>>
                        <div class="recurrence-card-content">
                            <i class="fa-solid <?= $rMeta['icon'] ?>"></i>
                            <span><?= $rMeta['label'] ?></span>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>

                <div class="custom-recurrence-box <?= $plan['recurrence_type'] === 'custom' ? 'is-active' : '' ?>" id="custom-recurrence-options">
                    <span style="font-weight: 600; color: #475569;">Repeat every:</span>
                    <input type="number" name="custom_interval_value" min="1" max="365" value="<?= (int)($plan['custom_interval_value'] ?? 15) ?>" class="form-control" style="width: 90px;">
                    <select name="custom_interval_unit" class="form-control" style="width: 140px;">
                        <option value="days" <?= ($plan['custom_interval_unit'] ?? '') === 'days' ? 'selected' : '' ?>>Days</option>
                        <option value="weeks" <?= ($plan['custom_interval_unit'] ?? '') === 'weeks' ? 'selected' : '' ?>>Weeks</option>
                        <option value="months" <?= ($plan['custom_interval_unit'] ?? '') === 'months' ? 'selected' : '' ?>>Months</option>
                        <option value="years" <?= ($plan['custom_interval_unit'] ?? '') === 'years' ? 'selected' : '' ?>>Years</option>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-top: 20px;">
                    <div class="form-group">
                        <label style="font-weight: 600;">Scheduled Date <span style="color: var(--color-danger);">*</span></label>
                        <input type="date" name="scheduled_date" class="form-control" required value="<?= sanitize($plan['scheduled_date']) ?>">
                    </div>

                    <div class="form-group">
                        <label style="font-weight: 600;">Scheduled Time</label>
                        <input type="time" name="scheduled_time" class="form-control" value="<?= substr($plan['scheduled_time'] ?? '09:00', 0, 5) ?>">
                    </div>

                    <div class="form-group">
                        <label style="font-weight: 600;">Estimated Duration (mins)</label>
                        <input type="number" name="estimated_duration_minutes" class="form-control" value="<?= (int)($plan['estimated_duration_minutes'] ?? 60) ?>" min="15" step="15">
                    </div>

                    <div class="form-group">
                        <label style="font-weight: 600;">Recurrence End Date</label>
                        <input type="date" name="end_date" class="form-control" value="<?= sanitize($plan['end_date'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- Section 3: Procedures & Scope -->
            <div style="margin-bottom: 24px;">
                <h4 style="font-size: 1rem; color: var(--text-primary); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                    <i class="fa-solid fa-clipboard-list" style="color: var(--primary);"></i> 3. Procedures & Checklist Scope
                </h4>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label style="font-weight: 600;">Description</label>
                    <textarea name="description" class="form-control" rows="2"><?= sanitize($plan['description'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label style="font-weight: 600;">Checklist Scope for Inspector</label>
                    <textarea name="checklist_scope" class="form-control" rows="5"><?= sanitize($plan['checklist_scope'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Section 4: Attachments & Technical Documentation (Joinder & Delet Files) -->
            <div style="margin-bottom: 24px;">
                <h4 style="font-size: 1rem; color: var(--text-primary); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                    <i class="fa-solid fa-paperclip" style="color: var(--primary);"></i> 4. Technical Documents & File Attachments (Joinder & Supprimer)
                </h4>

                <?php 
                $attachments = $plan['attachments'] ?? [];
                if (!empty($attachments)): 
                ?>
                <div style="margin-bottom: 16px;">
                    <label style="font-weight: 600; font-size: 0.88rem; color: #475569; display: block; margin-bottom: 8px;">
                        Currently Attached Documents (<?= count($attachments) ?>)
                    </label>
                    <div class="attachment-files-grid">
                        <?php foreach ($attachments as $att): 
                            $ext = strtolower(pathinfo($att['original_name'], PATHINFO_EXTENSION));
                            $iconData = match($ext) {
                                'pdf' => ['class' => 'icon-pdf', 'icon' => 'fa-solid fa-file-pdf'],
                                'doc', 'docx' => ['class' => 'icon-doc', 'icon' => 'fa-solid fa-file-word'],
                                'xls', 'xlsx', 'csv' => ['class' => 'icon-xls', 'icon' => 'fa-solid fa-file-excel'],
                                'jpg', 'jpeg', 'png', 'webp' => ['class' => 'icon-img', 'icon' => 'fa-solid fa-file-image'],
                                'zip', 'rar', 'tar', 'gz' => ['class' => 'icon-zip', 'icon' => 'fa-solid fa-file-zipper'],
                                default => ['class' => 'icon-other', 'icon' => 'fa-solid fa-file-lines'],
                            };
                            $fileSizeFormatted = $att['file_size'] > 1048576 
                                ? number_format($att['file_size'] / 1048576, 2) . ' MB' 
                                : number_format($att['file_size'] / 1024, 1) . ' KB';
                        ?>
                        <div class="attachment-file-card" id="attachment-card-<?= $att['id'] ?>">
                            <div class="attachment-file-icon <?= $iconData['class'] ?>">
                                <i class="<?= $iconData['icon'] ?>"></i>
                            </div>
                            <div class="attachment-file-details">
                                <div class="attachment-file-name" title="<?= sanitize($att['original_name']) ?>">
                                    <?= sanitize($att['original_name']) ?>
                                </div>
                                <div class="attachment-file-meta">
                                    <span><?= $fileSizeFormatted ?></span> &bull;
                                    <span><?= date('M d, Y', strtotime($att['created_at'])) ?></span>
                                </div>
                            </div>
                            <div class="attachment-file-actions">
                                <a href="/<?= sanitize($att['file_path']) ?>" download="<?= sanitize($att['original_name']) ?>" class="attachment-action-btn" title="Download file" target="_blank">
                                    <i class="fa-solid fa-download"></i>
                                </a>
                                <button type="button" class="attachment-action-btn btn-delete btn-delete-attachment" title="Delete attachment" data-id="<?= $att['id'] ?>" data-plan-id="<?= $plan['id'] ?>">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Upload Additional Files -->
                <div class="attachment-drop-box" id="attachment-drop-box">
                    <input type="file" name="attachments[]" id="maint-file-input" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.png,.jpg,.jpeg,.webp,.zip">
                    <div class="attachment-drop-icon">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                    </div>
                    <div class="attachment-drop-title">Drop additional files here or click to browse</div>
                    <div class="attachment-drop-hint">
                        Add manuals, schematics, or photos (PDF, Word, Excel, Images, ZIP up to 25MB each). Multiple files supported.
                    </div>
                </div>

                <!-- Live Staged Files Preview Area -->
                <div id="staged-files-list" style="display: none;"></div>
            </div>

            <!-- Section 5: Email Notifications -->
            <div class="email-notice-card">
                <div class="email-notice-header">
                    <i class="fa-solid fa-envelope-circle-check"></i>
                    <div>
                        <h4>Email Notifications</h4>
                        <p style="margin: 2px 0 0; font-size: 0.82rem; color: #64748b;">Configure notifications for this maintenance plan.</p>
                    </div>
                </div>

                <div style="margin-bottom: 12px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 600;">
                        <input type="checkbox" name="notify_email" value="1" <?= !empty($plan['notify_email']) ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: var(--primary);">
                        <span>Keep automated notifications enabled for this plan</span>
                    </label>
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; color: var(--text-primary);">
                        <input type="checkbox" name="send_update_notification" value="1" style="width: 18px; height: 18px; accent-color: var(--primary);">
                        <span><strong>Send email update now</strong> to notify assigned tech and CCs of these modifications</span>
                    </label>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label style="font-size: 0.82rem; font-weight: 600; color: #475569;">Additional Notification CC Emails</label>
                    <input type="text" name="recipient_emails" class="form-control" value="<?= sanitize($plan['recipient_emails'] ?? '') ?>" placeholder="facilities-lead@company.com, datacenter-ops@company.com">
                </div>
            </div>

            <!-- Submit -->
            <div style="margin-top: 28px; display: flex; justify-content: flex-end; gap: 12px;">
                <a href="/maintenance/show?id=<?= $plan['id'] ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-weight: 600;">
                    <i class="fa-solid fa-floppy-disk"></i> Update Maintenance Plan
                </button>
            </div>
        </form>
    </div>
</div>

<?php view('layouts.footer'); ?>
