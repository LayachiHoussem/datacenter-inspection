<?php
view('layouts.header', ['title' => $plan['title'] . ' - Maintenance Plan']);
view('layouts.sidebar');
view('layouts.navbar', ['title' => 'Preventive Maintenance Plan Details']);

$flash = get_flash();
?>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] ?>">
    <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
    <?= $flash['message'] ?>
</div>
<?php endif; ?>

<div style="max-width: 1050px; margin: 0 auto; display: flex; flex-direction: column; gap: 24px;">

    <!-- Top Summary Card -->
    <div class="card">
        <div class="card-header" style="flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="code-text" style="color: #0284c7; font-weight: 700; background: #e0f2fe; padding: 4px 10px; border-radius: 6px; font-size: 0.95rem;">
                    <?= sanitize($plan['plan_code']) ?>
                </span>
                <span class="badge badge-priority-<?= $plan['priority'] ?>" style="font-size: 0.82rem; padding: 4px 10px;">
                    PRIORITY: <?= strtoupper($plan['priority']) ?>
                </span>
                <?= status_badge($plan['status'], 'Inspection Statuses') ?>
                <?php if (!empty($plan['is_overdue']) && $plan['status'] === 'scheduled'): ?>
                    <span class="badge badge-fail" style="background: #fee2e2; color: #b91c1c; border: 1px solid #f87171; font-weight: 700; padding: 4px 10px; font-size: 0.82rem;">
                        <i class="fa-solid fa-triangle-exclamation"></i> OVERDUE
                    </span>
                <?php endif; ?>
            </div>

            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <a href="/maintenance" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left"></i> All Plans
                </a>

                <?php if ($canWrite): ?>
                    <?php if ($plan['status'] !== 'completed'): ?>
                    <button type="button" class="btn btn-success btn-sm" onclick="document.getElementById('complete-plan-modal').classList.add('is-open');">
                        <i class="fa-solid fa-check"></i> Mark Complete
                    </button>
                    <?php endif; ?>

                    <button type="button" class="btn btn-info btn-sm" style="color: #ffffff;" onclick="document.getElementById('email-plan-modal').classList.add('is-open');">
                        <i class="fa-solid fa-envelope"></i> Send Email
                    </button>

                    <a href="/maintenance/edit?id=<?= $plan['id'] ?>" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-pen-to-square"></i> Edit
                    </a>

                    <form action="/maintenance/delete" method="POST" onsubmit="return confirm('Delete this maintenance plan?');" style="display: inline-flex; margin: 0;">
                        <input type="hidden" name="id" value="<?= $plan['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="fa-solid fa-trash"></i> Delete
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div style="padding: 24px;">
            <h2 style="font-size: 1.4rem; color: var(--text-primary); margin-bottom: 8px;">
                <?= sanitize($plan['title']) ?>
            </h2>

            <?php if (!empty($plan['description'])): ?>
                <p style="color: #475569; font-size: 0.95rem; line-height: 1.6; margin-bottom: 20px;">
                    <?= nl2br(sanitize($plan['description'])) ?>
                </p>
            <?php endif; ?>

            <!-- Key Specifications Grid -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 18px; margin-top: 16px;">
                <div>
                    <div style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700; margin-bottom: 4px;">Target Unit(s)</div>
                    <div style="font-weight: 600; color: var(--text-primary);">
                        <i class="fa-solid fa-server" style="color: var(--primary); margin-right: 6px;"></i>
                        <?php 
                        $equipments = $plan['equipments'] ?? [];
                        if (count($equipments) > 1): 
                        ?>
                            <span><?= count($equipments) ?> Units Targeted</span>
                        <?php elseif (count($equipments) === 1): ?>
                            <?= sanitize($equipments[0]['name']) ?>
                        <?php else: ?>
                            <?= sanitize($plan['equipment_name'] ?? 'Facility Wide') ?>
                        <?php endif; ?>
                    </div>
                    <?php if (count($equipments) === 1 && !empty($equipments[0]['serial_number'])): ?>
                        <div style="font-size: 0.8rem; color: #8c92b3;">S/N: <?= sanitize($equipments[0]['serial_number']) ?></div>
                    <?php elseif (!empty($plan['serial_number'])): ?>
                        <div style="font-size: 0.8rem; color: #8c92b3;">S/N: <?= sanitize($plan['serial_number']) ?></div>
                    <?php elseif (count($equipments) > 1): ?>
                        <div style="font-size: 0.8rem; color: #8c92b3;"><?= count($equipments) ?> hardware devices</div>
                    <?php endif; ?>
                </div>

                <div>
                    <div style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700; margin-bottom: 4px;">Room / Location</div>
                    <div style="font-weight: 600; color: var(--text-primary);">
                        <i class="fa-solid fa-location-dot" style="color: #0284c7; margin-right: 6px;"></i>
                        <?= sanitize($plan['room_location'] ?? 'Facility Wide') ?>
                    </div>
                    <div style="font-size: 0.8rem; color: #8c92b3;"><?= sanitize($plan['category_name'] ?? 'Facility') ?></div>
                </div>

                <div>
                    <div style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700; margin-bottom: 4px;">Recurrence Period</div>
                    <div style="font-weight: 600; color: var(--text-primary);">
                        <i class="fa-solid fa-arrows-rotate" style="color: #03c95a; margin-right: 6px;"></i>
                        <?= sanitize($plan['recurrence_label']) ?>
                    </div>
                    <?php if (!empty($plan['next_due_date'])): ?>
                        <div style="font-size: 0.8rem; color: #475569;">Next cycle: <strong><?= sanitize($plan['next_due_date']) ?></strong></div>
                    <?php endif; ?>
                </div>

                <div>
                    <div style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700; margin-bottom: 4px;">Assigned Personnel</div>
                    <div style="font-weight: 600; color: var(--text-primary);">
                        <i class="fa-solid fa-user-gear" style="color: #f6b100; margin-right: 6px;"></i>
                        <?= sanitize($plan['assigned_name'] ?? 'Unassigned') ?>
                    </div>
                    <?php if (!empty($plan['assigned_email'])): ?>
                        <div style="font-size: 0.8rem; color: #8c92b3;"><?= sanitize($plan['assigned_email']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Target Equipment Units Card -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid fa-server"></i> Target Equipment Units (<?= count($equipments) ?>)
            </div>
            <?php if ($canWrite): ?>
                <a href="/maintenance/edit?id=<?= $plan['id'] ?>" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-pen"></i> Edit Equipment Units
                </a>
            <?php endif; ?>
        </div>
        <div style="padding: 20px;">
            <?php if (empty($equipments)): ?>
                <p style="color: #94a3b8; font-style: italic; margin: 0;">Facility-wide general infrastructure (no specific equipment targeted).</p>
            <?php else: ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 14px;">
                    <?php foreach ($equipments as $eq): ?>
                    <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px; display: flex; flex-direction: column; gap: 8px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px;">
                            <div style="font-weight: 700; color: var(--text-primary); font-size: 0.95rem;">
                                <a href="/equipment/show?id=<?= $eq['id'] ?>" style="color: inherit; text-decoration: none;">
                                    <i class="fa-solid fa-microchip" style="color: var(--primary); margin-right: 4px;"></i>
                                    <?= sanitize($eq['name']) ?>
                                </a>
                            </div>
                            <?php if (!empty($eq['status'])): ?>
                                <?= status_badge($eq['status'], 'Equipment Statuses') ?>
                            <?php endif; ?>
                        </div>
                        <div style="font-size: 0.8rem; color: #64748b; display: flex; flex-direction: column; gap: 4px;">
                            <?php if (!empty($eq['serial_number'])): ?>
                                <div><i class="fa-solid fa-barcode" style="width: 16px; color: #94a3b8;"></i> S/N: <strong style="color: var(--text-primary);"><?= sanitize($eq['serial_number']) ?></strong></div>
                            <?php endif; ?>
                            <?php if (!empty($eq['room_location'])): ?>
                                <div><i class="fa-solid fa-location-dot" style="width: 16px; color: #0284c7;"></i> Location: <?= sanitize($eq['room_location']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($eq['category_name'])): ?>
                                <div><i class="fa-solid fa-tag" style="width: 16px; color: var(--primary);"></i> Category: <?= sanitize($eq['category_name']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Timing & Schedule Card -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-regular fa-clock"></i> Execution Schedule & Timeline
            </div>
        </div>
        <div style="padding: 24px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div style="border-left: 3px solid #0284c7; padding-left: 14px;">
                    <div style="font-size: 0.8rem; color: #64748b;">Scheduled Date & Time</div>
                    <div style="font-size: 1.15rem; font-weight: 700; color: #0284c7; margin-top: 4px;">
                        <?= sanitize($plan['scheduled_date']) ?> at <?= substr($plan['scheduled_time'] ?? '09:00', 0, 5) ?>
                    </div>
                    <div style="font-size: 0.82rem; color: #8c92b3; margin-top: 2px;">Estimated: <?= (int)$plan['estimated_duration_minutes'] ?> mins</div>
                </div>

                <div style="border-left: 3px solid #03c95a; padding-left: 14px;">
                    <div style="font-size: 0.8rem; color: #64748b;">Next Due Cycle Date</div>
                    <div style="font-size: 1.15rem; font-weight: 700; color: #03c95a; margin-top: 4px;">
                        <?= !empty($plan['next_due_date']) ? sanitize($plan['next_due_date']) : 'N/A (One-time)' ?>
                    </div>
                    <div style="font-size: 0.82rem; color: #8c92b3; margin-top: 2px;">Auto-advances on completion</div>
                </div>

                <div style="border-left: 3px solid #f6b100; padding-left: 14px;">
                    <div style="font-size: 0.8rem; color: #64748b;">Last Executed / Completed</div>
                    <div style="font-size: 1.15rem; font-weight: 700; color: #f6b100; margin-top: 4px;">
                        <?= !empty($plan['last_executed_at']) ? sanitize($plan['last_executed_at']) : 'Not yet executed' ?>
                    </div>
                    <div style="font-size: 0.82rem; color: #8c92b3; margin-top: 2px;">Recorded upon completion</div>
                </div>
            </div>

            <?php if (!empty($plan['completion_notes'])): ?>
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 16px; margin-top: 20px;">
                    <strong style="color: #166534; font-size: 0.88rem; display: block; margin-bottom: 4px;">
                        <i class="fa-solid fa-circle-check"></i> Latest Completion Notes:
                    </strong>
                    <div style="color: #15803d; font-size: 0.92rem; line-height: 1.6;">
                        <?= nl2br(sanitize($plan['completion_notes'])) ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Checklist & Scope of Work -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid fa-list-check"></i> Checklist & Inspection Procedures
            </div>
        </div>
        <div style="padding: 24px;">
            <?php if (empty($plan['checklist_scope'])): ?>
                <p style="color: #94a3b8; font-style: italic;">No specific checklist items documented for this plan.</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <?php 
                    $lines = explode("\n", $plan['checklist_scope']);
                    foreach ($lines as $line): 
                        $trimLine = trim($line);
                        if (empty($trimLine)) continue;
                        $isBullet = str_starts_with($trimLine, '-') || str_starts_with($trimLine, '*');
                        $cleanText = ltrim($trimLine, '-* ');
                    ?>
                    <div style="display: flex; align-items: flex-start; gap: 10px; padding: 10px 14px; background: #f8fafc; border-radius: 6px; border: 1px solid #e2e8f0;">
                        <i class="fa-regular fa-square-check" style="color: var(--primary); margin-top: 3px; font-size: 1rem;"></i>
                        <span style="color: #334155; font-size: 0.92rem; line-height: 1.5;"><?= sanitize($cleanText) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Attached Technical Documents Card -->
    <?php $attachments = $plan['attachments'] ?? []; ?>
    <div class="card">
        <div class="card-header" style="flex-wrap: wrap; gap: 10px;">
            <div class="card-title">
                <i class="fa-solid fa-paperclip"></i> Attached Technical Documents & Manuals (<?= count($attachments) ?>)
            </div>
            <?php if ($canWrite): ?>
            <button type="button" class="btn btn-primary btn-sm" onclick="const s = document.getElementById('quick-upload-section'); s.style.display = (s.style.display === 'none' ? 'block' : 'none');">
                <i class="fa-solid fa-cloud-arrow-up"></i> Attach Files
            </button>
            <?php endif; ?>
        </div>
        <div style="padding: 24px;">
            <?php if ($canWrite): ?>
            <!-- Quick Upload Form -->
            <div id="quick-upload-section" style="display: none; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: var(--radius-sm); padding: 18px; margin-bottom: 20px;">
                <form action="/maintenance/attachments/upload" method="POST" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="plan_id" value="<?= $plan['id'] ?>">
                    <div style="font-weight: 600; font-size: 0.9rem; color: var(--text-primary); margin-bottom: 8px;">
                        <i class="fa-solid fa-plus-circle" style="color: var(--primary);"></i> Add Technical Documents / Service Manuals
                    </div>
                    <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                        <input type="file" name="attachments[]" multiple class="form-control" style="flex: 1; min-width: 260px;" accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.png,.jpg,.jpeg,.webp,.zip" required>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-upload"></i> Upload Files
                        </button>
                    </div>
                    <small style="color: #64748b; font-size: 0.76rem; margin-top: 6px; display: block;">
                        Supported: PDF, Word, Excel, CSV, Images, ZIP up to 25MB each.
                    </small>
                </form>
            </div>
            <?php endif; ?>

            <?php if (empty($attachments)): ?>
                <div style="text-align: center; padding: 24px; color: #94a3b8;">
                    <i class="fa-regular fa-folder-open" style="font-size: 2.2rem; margin-bottom: 8px; display: block; color: #cbd5e1;"></i>
                    No technical files or manuals attached to this maintenance plan.
                </div>
            <?php else: ?>
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
                            <?php if ($canWrite): ?>
                            <form action="/maintenance/attachments/delete" method="POST" class="form-delete-attachment" style="display: inline; margin: 0;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $att['id'] ?>">
                                <input type="hidden" name="plan_id" value="<?= $plan['id'] ?>">
                                <button type="button" class="attachment-action-btn btn-delete btn-delete-attachment" title="Delete attachment" data-id="<?= $att['id'] ?>" data-plan-id="<?= $plan['id'] ?>">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Complete Plan Modal -->
<div class="cal-modal-backdrop" id="complete-plan-modal">
    <div class="cal-modal-content">
        <form method="POST" action="/maintenance/complete">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $plan['id'] ?>">

            <div class="cal-modal-header">
                <h3 style="display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-circle-check" style="color: #03c95a;"></i> Mark Maintenance Completed
                </h3>
                <button type="button" class="cal-modal-close" data-modal-dismiss="true">&times;</button>
            </div>

            <div class="cal-modal-body">
                <p style="color: #475569; font-size: 0.92rem; margin-top: 0;">
                    Confirm completion of <strong><?= sanitize($plan['title']) ?></strong>.
                    <?php if ($plan['recurrence_type'] !== 'one_time'): ?>
                        <br><span style="color: #0284c7; font-size: 0.85rem;">Note: This will automatically advance the schedule to the next recurrence cycle (<?= sanitize($plan['recurrence_label']) ?>).</span>
                    <?php endif; ?>
                </p>

                <div class="form-group">
                    <label style="font-weight: 600; font-size: 0.85rem;">Completion Observations & Measurements:</label>
                    <textarea name="completion_notes" class="form-control" rows="4" placeholder="e.g. Completed filter replacement. Differential pressure restored to 0.15 in. w.g. No refrigerant leaks observed."></textarea>
                </div>
            </div>

            <div class="cal-modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-dismiss="true">Cancel</button>
                <button type="submit" class="btn btn-success">
                    <i class="fa-solid fa-check"></i> Confirm Completion
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Email Resend Modal -->
<div class="cal-modal-backdrop" id="email-plan-modal">
    <div class="cal-modal-content">
        <form method="POST" action="/maintenance/email">
            <input type="hidden" name="id" value="<?= $plan['id'] ?>">

            <div class="cal-modal-header">
                <h3 style="display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-envelope" style="color: var(--primary);"></i> Dispatch Maintenance Email
                </h3>
                <button type="button" class="cal-modal-close" data-modal-dismiss="true">&times;</button>
            </div>

            <div class="cal-modal-body">
                <p style="color: #475569; font-size: 0.9rem; margin-top: 0;">
                    Send an automated email notification with the schedule and task scope for <strong>[<?= sanitize($plan['plan_code']) ?>]</strong>.
                </p>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="font-weight: 600; font-size: 0.85rem;">Recipient Email Addresses (comma separated)</label>
                    <input type="text" name="recipients" class="form-control" value="<?= sanitize($plan['assigned_email'] ?? '') ?>" placeholder="tech@company.com, manager@company.com">
                </div>

                <div class="form-group">
                    <label style="font-weight: 600; font-size: 0.85rem;">Additional Notes for Email Body</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="e.g. Urgent reminder: maintenance scheduled for this unit tomorrow morning."></textarea>
                </div>
            </div>

            <div class="cal-modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-dismiss="true">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-paper-plane"></i> Send Email
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('[data-modal-dismiss]').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.cal-modal-backdrop').forEach(m => m.classList.remove('is-open'));
    });
});
</script>

<?php view('layouts.footer'); ?>
