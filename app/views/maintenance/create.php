<?php
view('layouts.header', ['title' => 'Plan Preventive Maintenance']);
view('layouts.sidebar');
view('layouts.navbar', ['title' => 'Plan Preventive Maintenance']);

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
            <i class="fa-solid fa-calendar-plus"></i> New Preventive Maintenance Plan
        </div>
        <a href="/maintenance" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Back to Plans
        </a>
    </div>

    <div style="padding: 24px;">
        <form method="POST" action="/maintenance/store" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="return_view" value="<?= sanitize($_GET['view'] ?? 'kanban') ?>">

            <!-- Section 1: General Plan Info -->
            <div style="margin-bottom: 24px;">
                <h4 style="font-size: 1rem; color: var(--text-primary); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                    <i class="fa-solid fa-circle-info" style="color: var(--primary);"></i> 1. Maintenance Scope & Target Equipment
                </h4>

                <div class="form-group" style="margin-bottom: 18px;">
                    <label style="font-weight: 600;">Maintenance Task Title <span style="color: var(--color-danger);">*</span></label>
                    <input type="text" name="title" class="form-control" required placeholder="e.g. Quarterly CRAC Filter Replacement & Airflow Validation" style="font-size: 0.95rem;">
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 18px;">
                    <div class="form-group">
                        <label style="font-weight: 600;">Facility Domain / Category Filter</label>
                        <select name="category_id" class="form-control" id="maint-category-select">
                            <option value="">-- All Categories (Show All Equipment) --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= sanitize($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color: #64748b; font-size: 0.76rem; margin-top: 3px; display: block;">
                            Selecting a domain instantly filters the equipment list below.
                        </small>
                    </div>

                    <div class="form-group">
                        <label style="font-weight: 600;">Assigned Inspector / Engineer</label>
                        <select name="assigned_to" class="form-control">
                            <option value="">-- Unassigned --</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= $u['id'] == auth_user()['id'] ? 'selected' : '' ?>>
                                    <?= sanitize($u['name']) ?> (<?= sanitize($u['email']) ?> - <?= ucfirst($u['role']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label style="font-weight: 600;">Priority Level</label>
                        <select name="priority" class="form-control">
                            <option value="critical">Critical (Immediate SLA impact)</option>
                            <option value="high">High (Key infrastructure)</option>
                            <option value="medium" selected>Medium (Standard preventive routine)</option>
                            <option value="low">Low (Minor upkeep)</option>
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
                            <strong id="equipment-selected-count">0</strong> equipment selected
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
                                $isPreselected = (!empty($preselectedEquipment) && $preselectedEquipment == $eq['id']);
                                $searchText = strtolower($eq['name'] . ' ' . ($eq['serial_number'] ?? '') . ' ' . ($eq['room_location'] ?? '') . ' ' . ($eq['category_name'] ?? ''));
                            ?>
                            <label class="equipment-check-item <?= $isPreselected ? 'is-checked' : '' ?>" 
                                   data-category-id="<?= (int)($eq['category_id'] ?? 0) ?>" 
                                   data-search-text="<?= sanitize($searchText) ?>">
                                <input type="checkbox" name="equipment_ids[]" value="<?= $eq['id'] ?>" <?= $isPreselected ? 'checked' : '' ?>>
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
                                No equipment units match the selected category or search keyword.
                            </div>
                        </div>
                    </div>
                    <small style="color: #64748b; font-size: 0.78rem; margin-top: 6px; display: block;">
                        Choose one or more units to execute this preventive maintenance protocol across.
                    </small>
                </div>
            </div>

            <!-- Section 2: Dynamic Period & Recurrence -->
            <div style="margin-bottom: 24px;">
                <h4 style="font-size: 1rem; color: var(--text-primary); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                    <i class="fa-solid fa-arrows-rotate" style="color: var(--primary);"></i> 2. Dynamic Recurrence Period & Timing
                </h4>

                <label style="font-weight: 600; display: block; margin-bottom: 8px;">Select Recurrence Frequency:</label>
                
                <div class="recurrence-selector-grid">
                    <label class="recurrence-radio-label">
                        <input type="radio" name="recurrence_type" value="one_time">
                        <div class="recurrence-card-content">
                            <i class="fa-solid fa-bolt"></i>
                            <span>One-Time</span>
                        </div>
                    </label>

                    <label class="recurrence-radio-label">
                        <input type="radio" name="recurrence_type" value="daily">
                        <div class="recurrence-card-content">
                            <i class="fa-solid fa-sun"></i>
                            <span>Daily</span>
                        </div>
                    </label>

                    <label class="recurrence-radio-label">
                        <input type="radio" name="recurrence_type" value="weekly">
                        <div class="recurrence-card-content">
                            <i class="fa-solid fa-calendar-week"></i>
                            <span>Weekly</span>
                        </div>
                    </label>

                    <label class="recurrence-radio-label">
                        <input type="radio" name="recurrence_type" value="bi_weekly">
                        <div class="recurrence-card-content">
                            <i class="fa-solid fa-calendar-days"></i>
                            <span>Bi-Weekly</span>
                        </div>
                    </label>

                    <label class="recurrence-radio-label">
                        <input type="radio" name="recurrence_type" value="monthly" checked>
                        <div class="recurrence-card-content">
                            <i class="fa-solid fa-calendar"></i>
                            <span>Monthly</span>
                        </div>
                    </label>

                    <label class="recurrence-radio-label">
                        <input type="radio" name="recurrence_type" value="quarterly">
                        <div class="recurrence-card-content">
                            <i class="fa-solid fa-chart-pie"></i>
                            <span>Quarterly</span>
                        </div>
                    </label>

                    <label class="recurrence-radio-label">
                        <input type="radio" name="recurrence_type" value="semi_annually">
                        <div class="recurrence-card-content">
                            <i class="fa-solid fa-hourglass-half"></i>
                            <span>6 Months</span>
                        </div>
                    </label>

                    <label class="recurrence-radio-label">
                        <input type="radio" name="recurrence_type" value="annually">
                        <div class="recurrence-card-content">
                            <i class="fa-solid fa-award"></i>
                            <span>Annually</span>
                        </div>
                    </label>

                    <label class="recurrence-radio-label">
                        <input type="radio" name="recurrence_type" value="custom">
                        <div class="recurrence-card-content">
                            <i class="fa-solid fa-sliders"></i>
                            <span>Custom Period</span>
                        </div>
                    </label>
                </div>

                <!-- Custom Interval Dynamic Container -->
                <div class="custom-recurrence-box" id="custom-recurrence-options">
                    <span style="font-weight: 600; color: #475569;">Repeat every:</span>
                    <input type="number" name="custom_interval_value" min="1" max="365" value="15" class="form-control" style="width: 90px;">
                    <select name="custom_interval_unit" class="form-control" style="width: 140px;">
                        <option value="days" selected>Days</option>
                        <option value="weeks">Weeks</option>
                        <option value="months">Months</option>
                        <option value="years">Years</option>
                    </select>
                    <span style="color: #64748b; font-size: 0.85rem;">(e.g., Every 45 Days or Every 3 Weeks)</span>
                </div>

                <!-- Date & Time Row -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-top: 20px;">
                    <div class="form-group">
                        <label style="font-weight: 600;">Scheduled Execution Date <span style="color: var(--color-danger);">*</span></label>
                        <input type="date" name="scheduled_date" class="form-control" required value="<?= sanitize($selectedDate) ?>">
                    </div>

                    <div class="form-group">
                        <label style="font-weight: 600;">Scheduled Time</label>
                        <input type="time" name="scheduled_time" class="form-control" value="09:00">
                    </div>

                    <div class="form-group">
                        <label style="font-weight: 600;">Estimated Duration (mins)</label>
                        <input type="number" name="estimated_duration_minutes" class="form-control" value="60" min="15" step="15">
                    </div>

                    <div class="form-group">
                        <label style="font-weight: 600;">Recurrence End Date (Optional)</label>
                        <input type="date" name="end_date" class="form-control" placeholder="Leave blank for ongoing">
                    </div>
                </div>
            </div>

            <!-- Section 3: Procedures & Checklist Scope -->
            <div style="margin-bottom: 24px;">
                <h4 style="font-size: 1rem; color: var(--text-primary); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                    <i class="fa-solid fa-clipboard-list" style="color: var(--primary);"></i> 3. Checklist Procedures & Technical Description
                </h4>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label style="font-weight: 600;">Overview Description</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Brief summary of the maintenance operation..."></textarea>
                </div>

                <div class="form-group">
                    <label style="font-weight: 600;">Step-by-Step Checklist Tasks for Inspector</label>
                    <textarea name="checklist_scope" class="form-control" rows="5" placeholder="- Check voltage levels on phase L1, L2, L3&#10;- Inspect cooling coils and condensate lines&#10;- Verify battery float voltage and torque connections&#10;- Record thermal camera reading on PDU breakers"></textarea>
                    <small style="color: #64748b; font-size: 0.78rem;">Each hyphenated line will appear as a task in the inspection sheet and email notification.</small>
                </div>
            </div>

            <!-- Section 4: Attachments & Technical Documentation (Joinder Fichiers) -->
            <div style="margin-bottom: 24px;">
                <h4 style="font-size: 1rem; color: var(--text-primary); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                    <i class="fa-solid fa-paperclip" style="color: var(--primary);"></i> 4. Technical Documents & File Attachments (Joinder)
                </h4>

                <div class="attachment-drop-box" id="attachment-drop-box">
                    <input type="file" name="attachments[]" id="maint-file-input" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.png,.jpg,.jpeg,.webp,.zip">
                    <div class="attachment-drop-icon">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                    </div>
                    <div class="attachment-drop-title">Drop technical manuals or files here or click to browse</div>
                    <div class="attachment-drop-hint">
                        Attach service manuals, electrical schematics, SOP checklists, or warranty sheets (PDF, Word, Excel, Images, ZIP up to 25MB each). Multiple files supported.
                    </div>
                </div>

                <!-- Live Staged Files Preview Area -->
                <div id="staged-files-list" style="display: none;"></div>
            </div>

            <!-- Section 5: Email Notification Settings -->
            <div class="email-notice-card">
                <div class="email-notice-header">
                    <i class="fa-solid fa-envelope-circle-check"></i>
                    <div>
                        <h4>Automated Email Dispatch</h4>
                        <p style="margin: 2px 0 0; font-size: 0.82rem; color: #64748b;">Notify assigned inspector and datacenter operations team immediately upon scheduling.</p>
                    </div>
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 600;">
                        <input type="checkbox" name="notify_email" value="1" checked style="width: 18px; height: 18px; accent-color: var(--primary);">
                        <span>Send email notification to assigned technician and stakeholders upon planning</span>
                    </label>
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="font-size: 0.82rem; font-weight: 600; color: #475569;">Additional Notification CC Emails (comma separated)</label>
                    <input type="text" name="recipient_emails" class="form-control" placeholder="facilities-lead@company.com, datacenter-ops@company.com">
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label style="font-size: 0.82rem; font-weight: 600; color: #475569;">Custom Notes for Email Body</label>
                    <textarea name="email_notes" class="form-control" rows="2" placeholder="e.g. Please bring calibrated fluke multimeter and safety gloves."></textarea>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div style="margin-top: 28px; display: flex; justify-content: flex-end; gap: 12px;">
                <a href="/maintenance" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-weight: 600;">
                    <i class="fa-solid fa-calendar-check"></i> Schedule Maintenance Plan
                </button>
            </div>
        </form>
    </div>
</div>

<?php view('layouts.footer'); ?>
