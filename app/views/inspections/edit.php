<?php
view('layouts.header', ['title' => 'Edit Inspection']);
view('layouts.sidebar');
view('layouts.navbar', ['title' => 'Edit Inspection: ' . sanitize($inspection['reference_code'])]);
?>

<style>
    .inspection-table {
        width: 100%;
        border-collapse: collapse;
    }
    .inspection-table th {
        background: #fbfcfd;
        color: var(--text-secondary);
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 12px 16px;
        border-bottom: 1px solid var(--border-color);
        text-align: left;
    }
    .inspection-table td {
        padding: 14px 16px;
        border-bottom: 1px solid var(--border-color);
        vertical-align: middle;
    }
    .inspection-table tbody tr:hover {
        background: var(--bg-surface-hover);
    }
    .status-check-group {
        display: flex;
        gap: 6px;
    }
    .status-check-group label {
        padding: 5px 10px;
        border-radius: 6px;
        border: 1px solid var(--border-color);
        cursor: pointer;
        font-size: 0.78rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: all 0.2s ease;
        background: #ffffff;
        color: var(--text-secondary);
    }
    .status-check-group input[type="radio"] { display: none; }

    .status-check-group input:checked + label {
        background: var(--primary-light);
        border-color: var(--primary);
        color: var(--primary);
        font-weight: 700;
    }
    .status-check-group label:hover {
        border-color: #94a3b8;
        background: #f8fafc;
    }
    .status-check-group label .status-indicator-dot {
        transition: transform 0.2s ease;
    }
    .status-check-group input:checked + label .status-indicator-dot {
        transform: scale(1.25);
    }
    .current-photo-thumb {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid var(--border-color);
        margin-right: 8px;
        vertical-align: middle;
    }

    .category-group {
        margin-bottom: 28px;
    }
    .category-group:last-child {
        margin-bottom: 0;
    }
    .category-header {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 16px;
        background: var(--bg-surface-hover);
        border-radius: 8px;
        margin-bottom: 4px;
    }
    .category-header strong {
        font-size: 0.9rem;
        color: var(--text-primary);
    }
    .category-header .badge {
        margin-left: auto;
        font-size: 0.7rem;
    }
</style>

<form action="/inspections/update" method="POST" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="inspection_id" value="<?= $inspection['id'] ?>">

    <!-- Inspection Metadata Header -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid fa-clipboard-list" style="color: var(--primary);"></i> Inspection Header Parameters
            </div>
            <div style="display: flex; gap: 8px;">
                <a href="/inspections/show?id=<?= $inspection['id'] ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i> Cancel</a>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 16px;">
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="title">Inspection Title</label>
                <input type="text" id="title" name="title" class="form-control" value="<?= sanitize($inspection['title']) ?>" required>
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">Reference Code</label>
                <input type="text" class="form-control" value="<?= sanitize($inspection['reference_code']) ?>" disabled style="background: #f0f2f5; color: var(--text-secondary);">
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="status">Inspection Status</label>
                <select id="status" name="status" class="form-control">
                    <?php 
                    $inspStatuses = \App\Config\Constants::getStatusOptions('Inspection Statuses');
                    foreach ($inspStatuses as $stVal => $stLabel): 
                    ?>
                        <option value="<?= sanitize($stVal) ?>" <?= ($inspection['status'] ?? '') === $stVal ? 'selected' : '' ?>>
                            <?= sanitize($stLabel) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- Equipment Devices Inspection Table (grouped by category) -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid fa-server" style="color: var(--primary);"></i> Hardware Devices Inspection Checklist
            </div>
            <span style="font-size: 0.85rem; color: var(--text-secondary);">Editing existing inspection results</span>
        </div>

        <?php if (empty($devices)): ?>
            <div style="text-align: center; color: var(--text-secondary); padding: 30px;">
                No equipment devices registered yet. Add equipment in the Equipment manager.
            </div>
        <?php else: ?>
            <?php
            // Group devices by category name (fallback to "Facility Hardware" if none set)
            $groupedDevices = [];
            foreach ($devices as $device) {
                $catName = $device['category_name'] ?? 'Facility Hardware';
                $catIcon = $device['category_icon'] ?? 'fa-server';
                if (!isset($groupedDevices[$catName])) {
                    $groupedDevices[$catName] = [
                        'icon'    => $catIcon,
                        'devices' => [],
                    ];
                }
                $groupedDevices[$catName]['devices'][] = $device;
            }
            ksort($groupedDevices); // alphabetical category order; swap for usort() for custom ordering
            ?>

            <?php foreach ($groupedDevices as $categoryName => $group): ?>
                <div class="category-group">
                    <div class="category-header">
                        <i class="fa-solid <?= sanitize($group['icon']) ?>" style="color: var(--primary);"></i>
                        <strong><?= sanitize($categoryName) ?></strong>
                        <span class="badge badge-info">
                            <?= count($group['devices']) ?> device<?= count($group['devices']) > 1 ? 's' : '' ?>
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="inspection-table">
                            <thead>
                                <tr>
                                    <th style="width: 25%;">Device Name & Location</th>
                                    <th style="width: 25%;">Status Rating</th>
                                    <th style="width: 25%;">Remarques / Observations</th>
                                    <th style="width: 25%;">Device Photo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($group['devices'] as $device):
                                    $prevStatus = $results[$device['id']]['result_status'] ?? 'pass';
                                    $prevNotes = $results[$device['id']]['notes'] ?? '';
                                    $existingPhoto = $photos[$device['id']]['file_path'] ?? null;
                                ?>
                                    <tr>
                                        <td>
                                            <strong style="color: var(--text-primary); font-size: 0.92rem;"><?= sanitize($device['name']) ?></strong>
                                            <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 2px;">
                                                SN: <span class="code-text" style="color:var(--primary); font-weight:600;"><?= sanitize($device['serial_number']) ?></span> &bull;
                                                Location: <?= sanitize($device['room_location']) ?>
                                            </div>
                                        </td>

                                        <td>
                                            <div class="status-check-group">
                                                <?php 
                                                $checklistResults = \App\Config\Constants::getStatusOptionsWithMeta('Checklist Results');
                                                foreach ($checklistResults as $resKey => $meta): 
                                                    $resLabel = $meta['label'];
                                                    $resColor = $meta['color'];
                                                    $iconClass = match($resKey) {
                                                        'pass', 'good' => 'fa-circle-check',
                                                        'warning' => 'fa-triangle-exclamation',
                                                        'fail', 'critical' => 'fa-circle-xmark',
                                                        'na' => 'fa-minus',
                                                        default => 'fa-circle-dot'
                                                    };
                                                ?>
                                                    <input type="radio" id="<?= $resKey ?>_<?= $device['id'] ?>" name="items[<?= $device['id'] ?>][status]" value="<?= sanitize($resKey) ?>" <?= $prevStatus === $resKey ? 'checked' : '' ?>>
                                                    <label for="<?= $resKey ?>_<?= $device['id'] ?>" title="<?= sanitize($resLabel) ?>: <?= sanitize($resColor) ?>">
                                                        <span class="status-indicator-dot" style="display:inline-block; width:8px; height:8px; border-radius:50%; background:<?= $resColor ?>; box-shadow: 0 0 0 1px rgba(0,0,0,0.12); flex-shrink:0;"></span>
                                                        <i class="fa-solid <?= $iconClass ?>"></i>
                                                        <span><?= sanitize($resLabel) ?></span>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </td>

                                        <td>
                                            <input type="text" name="items[<?= $device['id'] ?>][notes]" class="form-control" placeholder="Add remarque notes..." style="padding: 6px 10px; font-size: 0.84rem;" value="<?= sanitize($prevNotes) ?>">
                                        </td>

                                        <td>
                                            <?php if ($existingPhoto): ?>
                                                <div style="margin-bottom: 6px; display: flex; align-items: center; gap: 6px;">
                                                    <a href="/<?= sanitize($existingPhoto) ?>" target="_blank">
                                                        <img src="/<?= sanitize($existingPhoto) ?>" alt="Current" class="current-photo-thumb" style="width: 32px; height: 32px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border-color);">
                                                    </a>
                                                    <span style="font-size: 0.72rem; color: var(--text-secondary);">Current</span>
                                                </div>
                                            <?php endif; ?>
                                            <div class="photo-upload-wrapper">
                                                <label for="photo_<?= $device['id'] ?>" class="btn-upload-photo" title="Upload or replace photo">
                                                    <i class="fa-solid fa-camera"></i>
                                                    <span><?= $existingPhoto ? 'Change Photo' : 'Upload Photo' ?></span>
                                                </label>
                                                <input type="file" id="photo_<?= $device['id'] ?>" name="photos[<?= $device['id'] ?>]" accept="image/*" class="photo-file-input" onchange="previewInspectionPhoto(this, 'preview_<?= $device['id'] ?>', 'name_<?= $device['id'] ?>')">
                                                <img id="preview_<?= $device['id'] ?>" class="photo-preview-mini" style="display:none;" alt="New Preview">
                                                <span class="photo-filename" id="name_<?= $device['id'] ?>"></span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Final Submission Card -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid fa-flag-checkered" style="color: var(--color-success);"></i> General Remarks & Submission
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="overall_notes">Executive Remarque / Overall Summary</label>
            <textarea id="overall_notes" name="overall_notes" class="form-control" rows="2" placeholder="Enter general datacenter facility remarks..."><?= sanitize($inspection['notes']) ?></textarea>
        </div>

        <button type="submit" class="btn btn-success" style="width: 100%; padding: 12px; font-size: 0.95rem; font-weight: 600;">
            <i class="fa-solid fa-floppy-disk"></i> Save Changes & Update Inspection
        </button>
    </div>
</form>

<?php view('layouts.footer'); ?>