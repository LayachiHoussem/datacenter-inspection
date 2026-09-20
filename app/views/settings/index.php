<?php
view('layouts.header', ['title' => 'System Settings & Statuses']);
view('layouts.sidebar');
view('layouts.navbar', ['title' => 'System Configuration & Status Management']);

$totalConstants = count($constants);
$categoriesCount = count($categories);

function humanizeConstantName(string $name): string {
    $clean = preg_replace('/^(STATUS_|RESULT_|EQUIPMENT_|SEVERITY_|ROLE_|APP_)/', '', $name);
    return ucwords(strtolower(str_replace('_', ' ', $clean)));
}

$platformModules = \App\Config\Constants::$platformModules;
?>

<div class="settings-layout">
    <!-- LEFT COLUMN: SETTINGS SIDENAV -->
    <aside class="settings-sidenav-card card">
        <div class="settings-sidenav-header">
            <span class="settings-sidenav-title">Configuration</span>
            <?php if (can_write('settings')): ?>
            <button type="button" class="btn btn-primary btn-sm" style="padding: 4px 8px; font-size: 0.78rem;" onclick="openAddStatusModal('Custom', '')" title="Add New Status">
                <i class="fa-solid fa-plus"></i> New
            </button>
            <?php endif; ?>
        </div>

        <div class="settings-sidenav-group-label">Status & Role Domains</div>
        <nav class="settings-sidenav-menu">
            <?php 
            $i = 0;
            foreach ($groupedConstants as $catName => $group): 
                $itemCount = count($group['items']);
                $paneId = 'pane_' . preg_replace('/[^a-z0-9]/', '_', strtolower($catName));
                $isActive = ($i === 0);
                $i++;
            ?>
                <button type="button" class="settings-sidenav-item <?= $isActive ? 'active' : '' ?>" onclick="switchSettingsPane('<?= $paneId ?>', this)">
                    <i class="fa-solid <?= $group['meta']['icon'] ?? 'fa-tag' ?>"></i>
                    <span class="sidenav-label"><?= sanitize($catName) ?></span>
                    <span class="badge badge-secondary"><?= $itemCount ?></span>
                </button>
            <?php endforeach; ?>

            <div class="settings-sidenav-divider"></div>

            <div class="settings-sidenav-group-label">Enterprise &amp; Branding</div>
            <button type="button" class="settings-sidenav-item" onclick="switchSettingsPane('enterprise-pane', this)">
                <i class="fa-solid fa-building"></i>
                <span class="sidenav-label">Enterprise Profile</span>
                <span class="badge <?= !empty($enterpriseSettings['is_custom_logo']) ? 'badge-primary' : 'badge-secondary' ?>" style="font-size:0.68rem;">
                    <?= !empty($enterpriseSettings['is_custom_logo']) ? 'Custom' : 'Active' ?>
                </span>
            </button>

            <div class="settings-sidenav-divider"></div>

            <div class="settings-sidenav-group-label">System & Security</div>
            <button type="button" class="settings-sidenav-item" onclick="switchSettingsPane('system-pane', this)">
                <i class="fa-solid fa-sliders"></i>
                <span class="sidenav-label">System Parameters</span>
            </button>
            <button type="button" class="settings-sidenav-item" onclick="switchSettingsPane('audit-pane', this)">
                <i class="fa-solid fa-shield-halved"></i>
                <span class="sidenav-label">Audit Log History</span>
                <span class="badge badge-secondary"><?= count($logs) ?></span>
            </button>

            <div class="settings-sidenav-divider"></div>

            <div class="settings-sidenav-group-label">Alerts & Dispatch</div>
            <button type="button" class="settings-sidenav-item" onclick="switchSettingsPane('mail-pane', this)">
                <i class="fa-solid fa-envelope"></i>
                <span class="sidenav-label">SMTP Mail Settings</span>
                <span class="badge <?= !empty($mailSettings['smtp_enabled']) ? 'badge-success' : 'badge-secondary' ?>" style="font-size:0.68rem;">
                    <?= !empty($mailSettings['smtp_enabled']) ? 'Active' : 'Off' ?>
                </span>
            </button>
        </nav>
    </aside>

    <!-- RIGHT COLUMN: MAIN CONTENT AREA -->
    <div class="settings-content-area">

        <!-- DOMAIN PANES -->
        <?php 
        $j = 0;
        foreach ($groupedConstants as $catName => $group): 
            $items = $group['items'];
            $itemCount = count($items);
            $paneId = 'pane_' . preg_replace('/[^a-z0-9]/', '_', strtolower($catName));
            $catMeta = $group['meta'] ?? [];
            $suggestedPrefix = $catMeta['prefix'] ?? '';
            $desc = $catMeta['description'] ?? "Manage statuses and configuration for {$catName}";
            $isRoleDomain = ($catName === 'User Roles');
            $isActive = ($j === 0);
            $j++;
        ?>
        <div id="<?= $paneId ?>" class="settings-pane <?= $isActive ? 'active' : '' ?>">
            <div class="card">
                <!-- Pane Header -->
                <div class="domain-pane-header">
                    <div>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <h2 class="domain-pane-title"><?= sanitize($catName) ?></h2>
                            <span class="badge badge-secondary" style="font-size:0.75rem;">
                                <?= $itemCount ?> <?= $itemCount === 1 ? ($isRoleDomain ? 'Role' : 'Status') : ($isRoleDomain ? 'Roles' : 'Statuses') ?>
                            </span>
                        </div>
                        <div class="domain-pane-desc"><?= sanitize($desc) ?></div>
                    </div>

                    <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                        <div style="position:relative; min-width:200px;">
                            <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:10px; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:0.8rem;"></i>
                            <input type="text" class="form-control pane-search-input" placeholder="Search in <?= sanitize($catName) ?>..." style="padding-left:30px; font-size:0.82rem;" oninput="filterPaneTable(this, '<?= $paneId ?>')">
                        </div>
                        <?php if (can_write('settings')): ?>
                        <button type="button" class="btn btn-primary btn-sm" onclick="openAddStatusModal('<?= sanitize($catName) ?>', '<?= sanitize($suggestedPrefix) ?>')">
                            <i class="fa-solid fa-plus"></i> <?= $isRoleDomain ? 'Add Role' : 'Add Status' ?>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Domain Items Table -->
                <div class="table-responsive">
                    <table class="table domain-table">
                        <thead>
                            <tr>
                                <th style="width:25%;"><?= $isRoleDomain ? 'Role Title' : 'Status Name' ?></th>
                                <?php if ($isRoleDomain): ?>
                                    <th style="width:45%;">Platform Access Permissions</th>
                                <?php endif; ?>
                                <th style="width:<?= $isRoleDomain ? '20%' : '60%' ?>;">Description / Usage</th>
                                <th style="width:10%; text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <tr class="domain-empty-row"><td colspan="<?= $isRoleDomain ? 4 : 3 ?>" style="text-align:center; color:var(--text-muted); padding:24px;">No records in this domain.</td></tr>
                            <?php else: ?>
                                <?php foreach ($items as $name => $item): 
                                    $isCore = !empty($item['is_core']);
                                    $comment = $item['comment'] ?? '';
                                    $friendlyTitle = humanizeConstantName($name);
                                    $roleValue = (string)($item['value'] ?? '');
                                    $itemColor = !empty($item['color']) ? $item['color'] : \App\Config\Constants::getStatusColor($roleValue ?: $name, $catName);
                                    $permsMap = \App\Config\Constants::getRolePermissions($roleValue ?: $name);
                                ?>
                                <tr class="status-row" data-name="<?= strtolower(sanitize($name)) ?>" data-label="<?= strtolower(sanitize($friendlyTitle)) ?>" data-comment="<?= strtolower(sanitize($comment)) ?>">
                                    <td>
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <span style="display:inline-block; width:12px; height:12px; border-radius:50%; background:<?= $itemColor ?>; box-shadow: 0 0 0 2px <?= $itemColor ?>33; flex-shrink:0;" title="<?= $itemColor ?>"></span>
                                            <strong><?= sanitize($friendlyTitle) ?></strong>
                                            <?php if (!$isCore): ?>
                                                <span class="badge" style="font-size:0.65rem; padding:1px 6px; background:<?= $itemColor ?>18; color:<?= $itemColor ?>; border:1px solid <?= $itemColor ?>44;">Custom</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <?php if ($isRoleDomain): ?>
                                    <td>
                                        <div style="display:flex; flex-wrap:wrap; gap:4px;">
                                            <?php if ($name === 'ROLE_ADMIN' || $roleValue === 'admin'): ?>
                                                <span class="badge badge-secondary" style="font-weight:600;"><i class="fa-solid fa-lock"></i> Full Read & Write Access</span>
                                            <?php else: ?>
                                                <?php 
                                                $hasAny = false;
                                                foreach ($platformModules as $modKey => $modInfo): 
                                                    $level = $permsMap[$modKey] ?? 'none';
                                                    if ($level !== 'none'):
                                                        $hasAny = true;
                                                ?>
                                                    <span class="badge badge-secondary" style="font-size:0.75rem; font-weight:500;">
                                                        <i class="fa-solid <?= $modInfo['icon'] ?>"></i> <?= sanitize($modInfo['label']) ?>: 
                                                        <strong style="color:<?= $level === 'write' ? 'var(--primary)' : 'var(--text-secondary)' ?>;"><?= $level === 'write' ? 'Write' : 'Read-Only' ?></strong>
                                                    </span>
                                                <?php 
                                                    endif;
                                                endforeach; 
                                                if (!$hasAny):
                                                ?>
                                                    <span style="color:var(--text-muted); font-size:0.75rem;">No module access granted</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <?php endif; ?>

                                    <td>
                                        <span style="font-size:0.83rem; color:var(--text-muted);" title="<?= sanitize($comment) ?>">
                                            <?= !empty($comment) ? sanitize($comment) : '<em style="color:var(--text-muted); opacity:0.6;">-</em>' ?>
                                        </span>
                                    </td>
                                    <td style="text-align:right;">
                                        <?php if (can_write('settings')): ?>
                                        <div style="display:inline-flex; gap:6px;">
                                            <button type="button" class="btn btn-secondary btn-sm" title="Edit"
                                                onclick="openEditStatusModal(
                                                    '<?= sanitize($name) ?>',
                                                    '<?= sanitize($friendlyTitle) ?>',
                                                    '<?= sanitize($catName) ?>',
                                                    '<?= htmlspecialchars($comment, ENT_QUOTES, 'UTF-8') ?>',
                                                    '<?= htmlspecialchars(json_encode($permsMap), ENT_QUOTES, 'UTF-8') ?>',
                                                    '<?= htmlspecialchars($itemColor, ENT_QUOTES, 'UTF-8') ?>'
                                                )">
                                                <i class="fa-solid fa-pen-to-square"></i> Edit
                                            </button>

                                            <form action="/settings/constants/delete" method="POST" data-confirm="Delete '<?= sanitize($friendlyTitle) ?>'?" style="display:inline-flex; margin:0;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="name" value="<?= sanitize($name) ?>">
                                                <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--color-danger);" title="Delete">
                                                    <i class="fa-solid fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                        <?php else: ?>
                                            <span class="badge badge-secondary" style="font-size:0.75rem;"><i class="fa-solid fa-lock"></i> Read-Only</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- ENTERPRISE & BRANDING PANE -->
        <div id="enterprise-pane" class="settings-pane">
            <div class="card" style="margin-bottom:20px;">
                <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                    <div>
                        <div class="card-title" style="font-size:1.15rem;">
                            <i class="fa-solid fa-building" style="color:var(--primary); margin-right:8px;"></i> Enterprise Profile &amp; Visual Identity
                        </div>
                        <p style="color:var(--text-muted); font-size:0.85rem; margin-top:4px; margin-bottom:0;">
                            Configure dynamic company name, primary datacenter site, official logo and contact details displayed across reports, layouts and notifications.
                        </p>
                    </div>
                    <?php if (!empty($enterpriseSettings['updated_at'])): ?>
                    <span class="badge badge-secondary" style="font-size:0.75rem;">
                        <i class="fa-regular fa-clock"></i> Last updated: <?= sanitize($enterpriseSettings['updated_at']) ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <form action="/settings/enterprise/update" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>

                <div style="display:grid; grid-template-columns: 340px 1fr; gap:20px; align-items:start;">
                    <!-- LEFT COLUMN: LOGO & VISUAL BRAND -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title" style="font-size:0.95rem;">
                                <i class="fa-solid fa-image" style="color:var(--accent-cyan);"></i> Enterprise Logo
                            </div>
                            <span class="badge <?= !empty($enterpriseSettings['is_custom_logo']) ? 'badge-primary' : 'badge-secondary' ?>" style="font-size:0.7rem;">
                                <?= !empty($enterpriseSettings['is_custom_logo']) ? 'Custom Logo' : 'Default Logo' ?>
                            </span>
                        </div>

                        <!-- LOGO DISPLAY BOX -->
                        <div style="padding:16px 0; text-align:center;">
                            <div style="background:#ffffff; border:1px dashed var(--border-color); border-radius:var(--radius-md); padding:24px 16px; min-height:140px; display:flex; align-items:center; justify-content:center; position:relative;">
                                <img id="enterprise_logo_preview" src="<?= enterprise_logo_url() ?>?v=<?= time() ?>" alt="Enterprise Logo" style="max-height:85px; max-width:90%; object-fit:contain;">
                            </div>
                            <div id="enterprise_logo_filename" style="color:var(--text-muted); font-size:0.78rem; margin-top:8px;">
                                <?= sanitize(basename($enterpriseSettings['logo_url'] ?? 'logo.png')) ?>
                            </div>
                        </div>

                        <?php if (can_write('settings')): ?>
                        <div class="form-group" style="margin-top:8px;">
                            <label class="form-label" style="font-size:0.85rem; font-weight:600;">
                                <i class="fa-solid fa-cloud-arrow-up"></i> Upload New Logo
                            </label>
                            <input type="file" name="logo" id="enterprise_logo_input" class="form-control" accept="image/png,image/jpeg,image/webp,image/svg+xml" onchange="previewEnterpriseLogo(this)">
                            <small style="color:var(--text-muted); font-size:0.75rem; display:block; margin-top:4px;">
                                Supported: PNG, JPG, SVG, WebP. Max size: 5MB. Transparent PNG recommended.
                            </small>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($enterpriseSettings['is_custom_logo']) && can_write('settings')): ?>
                        <div style="margin-top:16px; padding-top:16px; border-top:1px solid var(--border-color); text-align:center;">
                            <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('resetLogoForm').submit();">
                                <i class="fa-solid fa-rotate-left"></i> Reset to Default Logo
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- RIGHT COLUMN: ORGANIZATION DETAILS -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title" style="font-size:0.95rem;">
                                <i class="fa-solid fa-id-card" style="color:var(--primary);"></i> Organization &amp; Site Details
                            </div>
                        </div>

                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                            <div class="form-group" style="grid-column: span 2;">
                                <label class="form-label">Enterprise / Company Name <span style="color:var(--color-danger);">*</span></label>
                                <input type="text" name="name" class="form-control" value="<?= sanitize($enterpriseSettings['name'] ?? '') ?>" placeholder="e.g. PCR Datacenter or CloudOps Facility" required <?= !can_write('settings') ? 'readonly' : '' ?>>
                                <small style="color:var(--text-muted); font-size:0.75rem;">
                                    Displayed in application title, sidebar branding, login screen and report headers.
                                </small>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Primary Site / Facility Name <span style="color:var(--color-danger);">*</span></label>
                                <input type="text" name="site" class="form-control" value="<?= sanitize($enterpriseSettings['site'] ?? 'PCR Datacenter Facility') ?>" placeholder="e.g. PCR Datacenter Facility" required <?= !can_write('settings') ? 'readonly' : '' ?>>
                                <small style="color:var(--text-muted); font-size:0.75rem;">
                                    Used as the primary facility location on inspection PDF reports.
                                </small>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Website URL</label>
                                <input type="url" name="website" class="form-control" value="<?= sanitize($enterpriseSettings['website'] ?? '') ?>" placeholder="https://datacenter.local" <?= !can_write('settings') ? 'readonly' : '' ?>>
                                <small style="color:var(--text-muted); font-size:0.75rem;">
                                    Enterprise website or intranet portal address.
                                </small>
                            </div>

                            <div class="form-group" style="grid-column: span 2;">
                                <label class="form-label">Portal Tagline / Subtitle</label>
                                <input type="text" name="tagline" class="form-control" value="<?= sanitize($enterpriseSettings['tagline'] ?? '') ?>" placeholder="e.g. Facilities Inspection &amp; Maintenance Management Portal" <?= !can_write('settings') ? 'readonly' : '' ?>>
                                <small style="color:var(--text-muted); font-size:0.75rem;">
                                    Appears beneath the enterprise logo on the login page and email summaries.
                                </small>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Contact Email</label>
                                <input type="email" name="email" class="form-control" value="<?= sanitize($enterpriseSettings['email'] ?? '') ?>" placeholder="ops@datacenter.local" <?= !can_write('settings') ? 'readonly' : '' ?>>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Contact Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?= sanitize($enterpriseSettings['phone'] ?? '') ?>" placeholder="+1 (555) 019-2834" <?= !can_write('settings') ? 'readonly' : '' ?>>
                            </div>

                            <div class="form-group" style="grid-column: span 2;">
                                <label class="form-label">Physical Address / Facility Location</label>
                                <textarea name="address" class="form-control" rows="2" placeholder="Building 4, Technology Park, Datacenter Way" <?= !can_write('settings') ? 'readonly' : '' ?>><?= sanitize($enterpriseSettings['address'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <?php if (can_write('settings')): ?>
                        <div style="margin-top:20px; display:flex; justify-content:flex-end;">
                            <button type="submit" class="btn btn-primary" style="padding:10px 24px;">
                                <i class="fa-solid fa-floppy-disk"></i> Save Enterprise Profile
                            </button>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info" style="margin-top:16px; margin-bottom:0;">
                            <i class="fa-solid fa-lock"></i> Read-only mode: You do not have permissions to modify enterprise settings.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </form>

            <?php if (!empty($enterpriseSettings['is_custom_logo']) && can_write('settings')): ?>
            <form id="resetLogoForm" action="/settings/enterprise/logo-reset" method="POST" style="display:none;" data-confirm="Are you sure you want to reset the company logo to the system default?">
                <?= csrf_field() ?>
            </form>
            <?php endif; ?>
        </div>

        <!-- SYSTEM PARAMETERS PANE -->
        <div id="system-pane" class="settings-pane">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="fa-solid fa-sliders"></i> Application Environment</div>
                    </div>

                    <form action="/settings/update" method="POST">
                        <?= csrf_field() ?>

                        <div class="form-group">
                            <label class="form-label">Application Title</label>
                            <input type="text" class="form-control" value="<?= sanitize($config['app_name']) ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Environment Mode</label>
                            <input type="text" class="form-control" value="<?= sanitize($config['env']) ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label class="form-label">System Timezone</label>
                            <input type="text" class="form-control" value="<?= sanitize($config['timezone']) ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Upload Storage Path</label>
                            <input type="text" class="form-control code-text" value="<?= sanitize($config['upload']['path']) ?>" readonly>
                        </div>

                        <?php if (can_write('settings')): ?>
                        <button type="submit" class="btn btn-primary" style="width:100%;">
                            <i class="fa-solid fa-floppy-disk"></i> Save System Config
                        </button>
                        <?php else: ?>
                        <div class="alert alert-info" style="margin-top:12px; margin-bottom:0;">
                            <i class="fa-solid fa-lock"></i> Read-only mode: System configuration cannot be modified.
                        </div>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="fa-solid fa-database"></i> Storage & Cache Directories</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Reports Directory</label>
                        <input type="text" class="form-control code-text" value="<?= sanitize($config['storage']['reports']) ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label class="form-label">System Logs Directory</label>
                        <input type="text" class="form-control code-text" value="<?= sanitize($config['storage']['logs']) ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Cache Directory</label>
                        <input type="text" class="form-control code-text" value="<?= sanitize($config['storage']['cache']) ?>" readonly>
                    </div>

                    <div class="alert alert-info" style="margin-top:16px;">
                        <i class="fa-solid fa-circle-info"></i> Storage paths are configured in system configuration.
                    </div>
                </div>
            </div>
        </div>

        <!-- AUDIT LOG HISTORY PANE -->
        <div id="audit-pane" class="settings-pane">
            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title"><i class="fa-solid fa-shield-halved"></i> System Audit Log History</div>
                        <div style="font-size:0.83rem; color:var(--text-muted); margin-top:3px;">
                            Immutable record of recent security events and administrative actions.
                        </div>
                    </div>
                    <a href="/settings/download" class="btn btn-secondary btn-sm">
                        <i class="fa-solid fa-file-pdf"></i> Download PDF
                    </a>
                </div>

                <div class="table-responsive" style="max-height: 520px; overflow-y: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Action</th>
                                <th>Entity</th>
                                <th>User</th>
                                <th>Details</th>
                                <th>IP Address</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr><td colspan="6" style="text-align:center; color:var(--text-muted); padding:20px;">No audit logs recorded yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><strong><?= sanitize($log['action']) ?></strong></td>
                                    <td><span class="badge badge-secondary"><?= sanitize($log['entity_type']) ?></span></td>
                                    <td><?= sanitize($log['user_name'] ?? 'System') ?></td>
                                    <td style="font-size:0.84rem; color:var(--text-primary); max-width:300px;"><?= sanitize($log['details'] ?? '-') ?></td>
                                    <td><span class="code-text" style="color:var(--text-muted);"><?= sanitize($log['ip_address']) ?></span></td>
                                    <td style="font-size:0.8rem; color:var(--text-muted); white-space:nowrap;"><?= sanitize($log['created_at']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- SMTP MAIL SETTINGS PANE -->
        <div id="mail-pane" class="settings-pane">
            <div class="card">
                <div class="card-header" style="flex-wrap:wrap; gap:12px;">
                    <div>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <h2 class="domain-pane-title"><i class="fa-solid fa-envelope" style="color:var(--primary);"></i> SMTP Mail Server Configuration</h2>
                            <span class="badge <?= !empty($mailSettings['smtp_enabled']) ? 'badge-success' : 'badge-secondary' ?>">
                                <?= !empty($mailSettings['smtp_enabled']) ? 'SMTP Active' : 'SMTP Disabled' ?>
                            </span>
                        </div>
                        <div class="domain-pane-desc">
                            Configure outgoing SMTP mail delivery for sending official PDF inspection compliance reports and alert notifications.
                        </div>
                    </div>

                    <?php if (can_write('settings')): ?>
                    <div style="display:flex; gap:8px;">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="openTestEmailModal(event)">
                            <i class="fa-solid fa-paper-plane"></i> Send Test Email
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <form action="/settings/mail/update" method="POST">
                    <?= csrf_field() ?>
                    <div style="padding: 24px; display:flex; flex-direction:column; gap:24px;">

                        <!-- SMTP CONFIGURATION CARD -->
                        <div style="background:var(--bg-page); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:20px;">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:1px solid var(--border-color); padding-bottom:12px;">
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <div style="width:36px; height:36px; border-radius:8px; background:rgba(59,130,246,0.12); color:#3b82f6; display:flex; align-items:center; justify-content:center; font-size:1.1rem;">
                                        <i class="fa-solid fa-server"></i>
                                    </div>
                                    <div>
                                        <h3 style="font-size:1.02rem; font-weight:700; color:var(--text-primary); margin:0;">SMTP Connection Parameters</h3>
                                        <p style="font-size:0.78rem; color:var(--text-secondary); margin:2px 0 0;">Outbound email transport credentials and server configuration</p>
                                    </div>
                                </div>
                                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:0.85rem; font-weight:600; color:var(--text-primary);">
                                    <input type="checkbox" name="smtp_enabled" value="1" <?= !empty($mailSettings['smtp_enabled']) ? 'checked' : '' ?>>
                                    Enable SMTP
                                </label>
                            </div>

                            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:16px;">
                                <div class="form-group">
                                    <label class="form-label">SMTP Host / Server <span style="color:var(--color-danger);">*</span></label>
                                    <input type="text" name="smtp_host" class="form-control" placeholder="e.g. smtp.gmail.com or mail.company.com" value="<?= sanitize($mailSettings['smtp_host'] ?? '') ?>" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">SMTP Port <span style="color:var(--color-danger);">*</span></label>
                                    <input type="number" name="smtp_port" class="form-control" placeholder="587" value="<?= (int)($mailSettings['smtp_port'] ?? 587) ?>" required>
                                    <small style="font-size:0.72rem; color:var(--text-secondary);">Standard: 587 (TLS), 465 (SSL), 25 (Plain)</small>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Encryption Protocol</label>
                                    <select name="smtp_encryption" class="form-control">
                                        <option value="tls" <?= ($mailSettings['smtp_encryption'] ?? '') === 'tls' ? 'selected' : '' ?>>TLS (STARTTLS - Recommended)</option>
                                        <option value="ssl" <?= ($mailSettings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL (SMTPS / Port 465)</option>
                                        <option value="none" <?= ($mailSettings['smtp_encryption'] ?? '') === 'none' ? 'selected' : '' ?>>None (Unencrypted)</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">SMTP Username / Account</label>
                                    <input type="text" name="smtp_user" class="form-control" placeholder="user@example.com" value="<?= sanitize($mailSettings['smtp_user'] ?? '') ?>">
                                </div>

                                <div class="form-group">
                                    <label class="form-label">SMTP Password</label>
                                    <div style="display:flex; gap:6px;">
                                        <input type="password" name="smtp_pass" id="smtp_pass_input" class="form-control" placeholder="<?= !empty($mailSettings['smtp_pass']) ? '••••••••••••' : 'Enter SMTP password' ?>">
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="togglePasswordVisibility('smtp_pass_input', this)" title="Show/Hide Password" style="padding:0 12px;">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                    </div>
                                    <small style="font-size:0.72rem; color:var(--text-secondary);">Leave blank to keep existing password.</small>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Sender Email (From)</label>
                                    <input type="email" name="from_email" class="form-control" placeholder="noreply@datacenter.local" value="<?= sanitize($mailSettings['from_email'] ?? '') ?>">
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Sender Display Name</label>
                                    <input type="text" name="from_name" class="form-control" placeholder="Datacenter Inspection System" value="<?= sanitize($mailSettings['from_name'] ?? '') ?>">
                                </div>

                                <div class="form-group" style="grid-column: 1 / -1;">
                                    <label class="form-label">Default Report Notification Recipients</label>
                                    <input type="text" name="default_recipients" class="form-control" placeholder="admin@datacenter.local, ops-manager@company.com" value="<?= sanitize($mailSettings['default_recipients'] ?? '') ?>">
                                    <small style="font-size:0.72rem; color:var(--text-secondary);">Separate multiple recipient email addresses with commas.</small>
                                </div>
                            </div>
                        </div>

                    </div>

                    <?php if (can_write('settings')): ?>
                    <div style="padding: 16px 24px; border-top:1px solid var(--border-color); display:flex; justify-content:flex-end; gap:12px; background:var(--bg-card); border-bottom-left-radius:var(--radius-md); border-bottom-right-radius:var(--radius-md);">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-floppy-disk"></i> Save SMTP Settings
                        </button>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if (can_write('settings')): ?>
<!-- ========================================================================= -->
<!-- MODAL: ADD STATUS / ROLE -->
<!-- ========================================================================= -->
<div id="addStatusModal" class="modal-backdrop" style="display:none;">
    <div class="modal-dialog" style="max-width: 620px;">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title" id="addModalTitle"><i class="fa-solid fa-circle-plus" style="color:var(--primary);"></i> Add Status / Role</div>
                <button type="button" class="modal-close-btn" onclick="closeAddStatusModal()">&times;</button>
            </div>
            <form action="/settings/constants/store" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Domain Category <span style="color:var(--color-danger);">*</span></label>
                        <select name="category" id="add_modal_category" class="form-control" onchange="onAddCategoryChange(this.value)">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= sanitize($cat) ?>"><?= sanitize($cat) ?></option>
                            <?php endforeach; ?>
                            <option value="Custom">Custom Domain...</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" id="add_modal_name_label">Name / Title <span style="color:var(--color-danger);">*</span></label>
                        <input type="text" name="name" id="add_modal_name" class="form-control" placeholder="e.g. Supervisor, Under Review, or Standby" required>
                    </div>

                    <!-- Platform Read / Write Permissions Matrix (Only shown for User Roles) -->
                    <div class="form-group" id="add_permissions_group" style="display:none;">
                        <label class="form-label">Platform Module Access Permissions <span style="color:var(--color-danger);">*</span></label>
                        <div class="perm-table-container">
                            <table class="table perm-matrix-table">
                                <thead>
                                    <tr>
                                        <th style="width:52%;">Platform Module</th>
                                        <th style="width:16%; text-align:center;">No Access</th>
                                        <th style="width:16%; text-align:center;">Read-Only</th>
                                        <th style="width:16%; text-align:center;">Write</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($platformModules as $modKey => $modInfo): ?>
                                    <tr>
                                        <td>
                                            <div class="perm-mod-title"><i class="fa-solid <?= $modInfo['icon'] ?>"></i> <?= sanitize($modInfo['label']) ?></div>
                                            <div class="perm-mod-desc"><?= sanitize($modInfo['description']) ?></div>
                                        </td>
                                        <td style="text-align:center;">
                                            <input type="radio" name="permissions[<?= $modKey ?>]" value="none">
                                        </td>
                                        <td style="text-align:center;">
                                            <input type="radio" name="permissions[<?= $modKey ?>]" value="read" <?= in_array($modKey, ['dashboard', 'reports', 'equipment']) ? 'checked' : '' ?>>
                                        </td>
                                        <td style="text-align:center;">
                                            <input type="radio" name="permissions[<?= $modKey ?>]" value="write" <?= in_array($modKey, ['inspections']) ? 'checked' : '' ?>>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Status / Result Theme Color -->
                    <div class="form-group" id="add_color_group">
                        <label class="form-label">Status / Result Theme Color</label>
                        <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                            <div class="color-palette-presets" style="display:flex; gap:8px; align-items:center;">
                                <button type="button" class="color-preset-btn" data-color="#03c95a" style="background:#03c95a; width:26px; height:26px; border-radius:50%; border:2px solid #fff; box-shadow:0 0 0 1px #cbd5e1; cursor:pointer;" title="Emerald Green" onclick="selectAddColor('#03c95a')"></button>
                                <button type="button" class="color-preset-btn" data-color="#f6b100" style="background:#f6b100; width:26px; height:26px; border-radius:50%; border:2px solid #fff; box-shadow:0 0 0 1px #cbd5e1; cursor:pointer;" title="Amber Warning" onclick="selectAddColor('#f6b100')"></button>
                                <button type="button" class="color-preset-btn" data-color="#ef4444" style="background:#ef4444; width:26px; height:26px; border-radius:50%; border:2px solid #fff; box-shadow:0 0 0 1px #cbd5e1; cursor:pointer;" title="Crimson Danger" onclick="selectAddColor('#ef4444')"></button>
                                <button type="button" class="color-preset-btn" data-color="#3b82f6" style="background:#3b82f6; width:26px; height:26px; border-radius:50%; border:2px solid #fff; box-shadow:0 0 0 1px #cbd5e1; cursor:pointer;" title="Royal Blue" onclick="selectAddColor('#3b82f6')"></button>
                                <button type="button" class="color-preset-btn" data-color="#8b5cf6" style="background:#8b5cf6; width:26px; height:26px; border-radius:50%; border:2px solid #fff; box-shadow:0 0 0 1px #cbd5e1; cursor:pointer;" title="Violet Purple" onclick="selectAddColor('#8b5cf6')"></button>
                                <button type="button" class="color-preset-btn" data-color="#06b6d4" style="background:#06b6d4; width:26px; height:26px; border-radius:50%; border:2px solid #fff; box-shadow:0 0 0 1px #cbd5e1; cursor:pointer;" title="Azure Cyan" onclick="selectAddColor('#06b6d4')"></button>
                                <button type="button" class="color-preset-btn" data-color="#f97316" style="background:#f97316; width:26px; height:26px; border-radius:50%; border:2px solid #fff; box-shadow:0 0 0 1px #cbd5e1; cursor:pointer;" title="Flame Orange" onclick="selectAddColor('#f97316')"></button>
                                <button type="button" class="color-preset-btn" data-color="#64748b" style="background:#64748b; width:26px; height:26px; border-radius:50%; border:2px solid #fff; box-shadow:0 0 0 1px #cbd5e1; cursor:pointer;" title="Slate Neutral" onclick="selectAddColor('#64748b')"></button>
                            </div>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <input type="color" name="color" id="add_modal_color" value="#03c95a" style="width:34px; height:34px; padding:2px; border-radius:6px; border:1px solid var(--border-color); cursor:pointer;" onchange="updateAddColorPreview(this.value)">
                                <span id="add_color_hex" style="font-family:monospace; font-size:0.85rem; color:var(--text-secondary); font-weight:600;">#03C95A</span>
                            </div>
                            <span id="add_badge_preview" class="badge" style="background:rgba(3,201,90,0.15); color:#03c95a; border:1px solid rgba(3,201,90,0.3); font-weight:700;">
                                PREVIEW BADGE
                            </span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description / Note</label>
                        <textarea name="comment" id="add_modal_comment" class="form-control" rows="2" placeholder="Brief description of what this status or role represents..."></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeAddStatusModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: EDIT STATUS / ROLE -->
<!-- ========================================================================= -->
<div id="editStatusModal" class="modal-backdrop" style="display:none;">
    <div class="modal-dialog" style="max-width: 620px;">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title" id="editModalTitle"><i class="fa-solid fa-pen-to-square" style="color:var(--primary);"></i> Edit Status / Role</div>
                <button type="button" class="modal-close-btn" onclick="closeEditStatusModal()">&times;</button>
            </div>
            <form action="/settings/constants/update" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="original_name" id="edit_modal_original_name">

                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="category" id="edit_modal_category" class="form-control" onchange="onEditCategoryChange(this.value)">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= sanitize($cat) ?>"><?= sanitize($cat) ?></option>
                            <?php endforeach; ?>
                            <option value="Custom">Custom Domain</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Name / Title <span style="color:var(--color-danger);">*</span></label>
                        <input type="text" name="new_name" id="edit_modal_name" class="form-control" required>
                    </div>

                    <!-- Platform Read / Write Permissions Matrix (Only shown for User Roles) -->
                    <div class="form-group" id="edit_permissions_group" style="display:none;">
                        <label class="form-label">Platform Module Access Permissions <span style="color:var(--color-danger);">*</span></label>
                        <div class="perm-table-container">
                            <table class="table perm-matrix-table">
                                <thead>
                                    <tr>
                                        <th style="width:52%;">Platform Module</th>
                                        <th style="width:16%; text-align:center;">No Access</th>
                                        <th style="width:16%; text-align:center;">Read-Only</th>
                                        <th style="width:16%; text-align:center;">Write</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($platformModules as $modKey => $modInfo): ?>
                                    <tr>
                                        <td>
                                            <div class="perm-mod-title"><i class="fa-solid <?= $modInfo['icon'] ?>"></i> <?= sanitize($modInfo['label']) ?></div>
                                            <div class="perm-mod-desc"><?= sanitize($modInfo['description']) ?></div>
                                        </td>
                                        <td style="text-align:center;">
                                            <input type="radio" name="permissions[<?= $modKey ?>]" id="edit_perm_none_<?= $modKey ?>" value="none">
                                        </td>
                                        <td style="text-align:center;">
                                            <input type="radio" name="permissions[<?= $modKey ?>]" id="edit_perm_read_<?= $modKey ?>" value="read">
                                        </td>
                                        <td style="text-align:center;">
                                            <input type="radio" name="permissions[<?= $modKey ?>]" id="edit_perm_write_<?= $modKey ?>" value="write">
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Status / Result Theme Color -->
                    <div class="form-group" id="edit_color_group">
                        <label class="form-label">Status / Result Theme Color</label>
                        <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                            <div class="color-palette-presets" style="display:flex; gap:8px; align-items:center;">
                                <button type="button" class="color-preset-btn" data-color="#03c95a" style="background:#03c95a; width:26px; height:26px; border-radius:50%; border:2px solid #fff; box-shadow:0 0 0 1px #cbd5e1; cursor:pointer;" title="Emerald Green" onclick="selectEditColor('#03c95a')"></button>
                                <button type="button" class="color-preset-btn" data-color="#f6b100" style="background:#f6b100; width:26px; height:26px; border-radius:50%; border:2px solid #fff; box-shadow:0 0 0 1px #cbd5e1; cursor:pointer;" title="Amber Warning" onclick="selectEditColor('#f6b100')"></button>
                                <button type="button" class="color-preset-btn" data-color="#ef4444" style="background:#ef4444; width:26px; height:26px; border-radius:50%; border:2px solid #fff; box-shadow:0 0 0 1px #cbd5e1; cursor:pointer;" title="Crimson Danger" onclick="selectEditColor('#ef4444')"></button>
                                <button type="button" class="color-preset-btn" data-color="#3b82f6" style="background:#3b82f6; width:26px; height:26px; border-radius:50%; border:2px solid #fff; box-shadow:0 0 0 1px #cbd5e1; cursor:pointer;" title="Royal Blue" onclick="selectEditColor('#3b82f6')"></button>
                                <button type="button" class="color-preset-btn" data-color="#8b5cf6" style="background:#8b5cf6; width:26px; height:26px; border-radius:50%; border:2px solid #fff; box-shadow:0 0 0 1px #cbd5e1; cursor:pointer;" title="Violet Purple" onclick="selectEditColor('#8b5cf6')"></button>
                                <button type="button" class="color-preset-btn" data-color="#06b6d4" style="background:#06b6d4; width:26px; height:26px; border-radius:50%; border:2px solid #fff; box-shadow:0 0 0 1px #cbd5e1; cursor:pointer;" title="Azure Cyan" onclick="selectEditColor('#06b6d4')"></button>
                                <button type="button" class="color-preset-btn" data-color="#f97316" style="background:#f97316; width:26px; height:26px; border-radius:50%; border:2px solid #fff; box-shadow:0 0 0 1px #cbd5e1; cursor:pointer;" title="Flame Orange" onclick="selectEditColor('#f97316')"></button>
                                <button type="button" class="color-preset-btn" data-color="#64748b" style="background:#64748b; width:26px; height:26px; border-radius:50%; border:2px solid #fff; box-shadow:0 0 0 1px #cbd5e1; cursor:pointer;" title="Slate Neutral" onclick="selectEditColor('#64748b')"></button>
                            </div>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <input type="color" name="color" id="edit_modal_color" value="#03c95a" style="width:34px; height:34px; padding:2px; border-radius:6px; border:1px solid var(--border-color); cursor:pointer;" onchange="updateEditColorPreview(this.value)">
                                <span id="edit_color_hex" style="font-family:monospace; font-size:0.85rem; color:var(--text-secondary); font-weight:600;">#03C95A</span>
                            </div>
                            <span id="edit_badge_preview" class="badge" style="background:rgba(3,201,90,0.15); color:#03c95a; border:1px solid rgba(3,201,90,0.3); font-weight:700;">
                                PREVIEW BADGE
                            </span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description / Note</label>
                        <textarea name="comment" id="edit_modal_comment" class="form-control" rows="2"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditStatusModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i> Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: SEND TEST EMAIL -->
<!-- ========================================================================= -->
<div id="testEmailModal" class="modal-backdrop" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(15, 23, 42, 0.7); backdrop-filter:blur(4px); z-index:99999; align-items:center; justify-content:center;">
    <div class="modal-dialog" style="max-width: 480px; width:92%; margin:auto; background:#ffffff !important; border-radius:var(--radius-lg); overflow:hidden; box-shadow:0 25px 50px -12px rgba(0,0,0,0.5); border:1px solid #cbd5e1; z-index:100000;">
        <div class="modal-content" style="background:#ffffff !important;">
            <div class="modal-header" style="background:#ffffff !important; padding:18px 24px; border-bottom:1px solid var(--border-color);">
                <div class="modal-title"><i class="fa-solid fa-paper-plane" style="color:var(--primary);"></i> Send Test Email</div>
                <button type="button" class="modal-close-btn" onclick="closeTestEmailModal()">&times;</button>
            </div>
            <form action="/settings/mail/test" method="POST" onsubmit="return handleEmailSend(this, 'btnSubmitTestEmail')">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <p style="font-size:0.86rem; color:var(--text-secondary); margin-bottom:16px;">
                        Send an immediate verification email to confirm that your SMTP server host, port, credentials, and TLS/SSL encryption handshake are properly working.
                    </p>
                    <div class="form-group">
                        <label class="form-label">Recipient Email Address <span style="color:var(--color-danger);">*</span></label>
                        <input type="email" name="test_email" class="form-control" placeholder="your-email@example.com" value="<?= sanitize(auth_user()['email'] ?? '') ?>" required>
                        <small style="font-size:0.75rem; color:var(--text-secondary); margin-top:4px;">A test message with diagnostics will be delivered to this address.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeTestEmailModal()">Cancel</button>
                    <button type="submit" id="btnSubmitTestEmail" class="btn btn-primary">
                        <i class="fa-solid fa-paper-plane"></i> Send Test Now
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ========================================================================= -->
<!-- STYLES & INTERACTIVE SCRIPT -->
<!-- ========================================================================= -->
<style>
/* 2-Column Settings Layout with Sidenav */
.settings-layout {
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 20px;
    align-items: start;
}

@media (max-width: 960px) {
    .settings-layout {
        grid-template-columns: 1fr;
    }
}

/* Left Sidenav Card */
.settings-sidenav-card {
    padding: 16px 12px;
    position: sticky;
    top: calc(var(--header-height) + 20px);
}

.settings-sidenav-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 8px 12px;
    border-bottom: 1px solid var(--border-color);
    margin-bottom: 10px;
}

.settings-sidenav-title {
    font-size: 0.92rem;
    font-weight: 700;
    color: var(--text-primary);
}

.settings-sidenav-group-label {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: var(--text-muted);
    padding: 10px 8px 4px;
}

.settings-sidenav-menu {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.settings-sidenav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 9px 12px;
    background: transparent;
    border: none;
    border-radius: var(--radius-sm);
    font-size: 0.85rem;
    font-weight: 500;
    color: #5c6287;
    cursor: pointer;
    text-align: left;
    transition: all 0.15s ease;
}

.settings-sidenav-item i {
    width: 16px;
    font-size: 0.95rem;
    color: var(--text-secondary);
    transition: color 0.15s ease;
}

.settings-sidenav-item .sidenav-label {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.settings-sidenav-item:hover {
    background: var(--primary-light);
    color: var(--primary);
}

.settings-sidenav-item:hover i {
    color: var(--primary);
}

.settings-sidenav-item.active {
    background: var(--primary-light);
    color: var(--primary);
    font-weight: 600;
}

.settings-sidenav-item.active i {
    color: var(--primary);
}

.settings-sidenav-divider {
    height: 1px;
    background: var(--border-color);
    margin: 10px 4px;
}

/* Right Content Area & Panes */
.settings-pane {
    display: none;
}

.settings-pane.active {
    display: block;
}

.domain-pane-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 14px;
    margin-bottom: 14px;
    border-bottom: 1px solid var(--border-color);
    flex-wrap: wrap;
    gap: 12px;
}

.domain-pane-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
}

.domain-pane-desc {
    font-size: 0.82rem;
    color: var(--text-muted);
    margin-top: 2px;
}

/* Domain Table */
.domain-table {
    margin-bottom: 0;
}

.domain-table th {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-secondary);
    background: #fafbfe;
    border-bottom: 1px solid var(--border-color);
}

.domain-table td {
    vertical-align: middle;
    padding: 10px 12px;
}

/* Permission Matrix Table Container */
.perm-table-container {
    background: #ffffff;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-sm);
    max-height: 240px;
    overflow-y: auto;
}

.perm-matrix-table {
    margin-bottom: 0;
    font-size: 0.82rem;
}

.perm-matrix-table th {
    font-size: 0.72rem;
    text-transform: uppercase;
    color: var(--text-secondary);
    background: #f8fafc;
    padding: 6px 10px;
}

.perm-matrix-table td {
    padding: 8px 10px;
    vertical-align: middle;
}

.perm-mod-title {
    font-weight: 600;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 6px;
}

.perm-mod-desc {
    font-size: 0.72rem;
    color: var(--text-muted);
}

.perm-matrix-table input[type="radio"] {
    cursor: pointer;
    accent-color: var(--primary);
}

/* Modals */
.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(18, 22, 38, 0.5);
    backdrop-filter: blur(3px);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal-dialog {
    width: 100%;
}

.modal-content {
    background: var(--bg-surface);
    border-radius: var(--radius-md);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
    border: 1px solid var(--border-color);
    overflow: hidden;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid var(--border-color);
}

.modal-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 8px;
}

.modal-close-btn {
    background: none;
    border: none;
    font-size: 1.4rem;
    color: var(--text-secondary);
    cursor: pointer;
    line-height: 1;
}

.modal-close-btn:hover {
    color: var(--text-primary);
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 14px 20px;
    border-top: 1px solid var(--border-color);
    background: var(--bg-page);
}
</style>

<script>
function switchSettingsPane(paneId, btn) {
    document.querySelectorAll('.settings-pane').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.settings-sidenav-item').forEach(el => el.classList.remove('active'));
    
    const target = document.getElementById(paneId);
    if (target) target.classList.add('active');
    btn.classList.add('active');
}

function filterPaneTable(input, paneId) {
    const searchVal = (input.value || '').toLowerCase().trim();
    const pane = document.getElementById(paneId);
    if (!pane) return;

    const rows = pane.querySelectorAll('.status-row');
    rows.forEach(row => {
        const name = row.getAttribute('data-name') || '';
        const label = row.getAttribute('data-label') || '';
        const comment = row.getAttribute('data-comment') || '';

        const matches = !searchVal || 
            label.includes(searchVal) ||
            name.includes(searchVal) || 
            comment.includes(searchVal);

        row.style.display = matches ? '' : 'none';
    });
}

function onAddCategoryChange(cat) {
    const permGroup = document.getElementById('add_permissions_group');
    const nameLabel = document.getElementById('add_modal_name_label');
    const nameInput = document.getElementById('add_modal_name');

    if (!permGroup || !nameLabel || !nameInput) return;

    if (cat === 'User Roles') {
        permGroup.style.display = 'block';
        nameLabel.innerHTML = 'Role Title <span style="color:var(--color-danger);">*</span>';
        nameInput.placeholder = 'e.g. Supervisor or Lead Auditor';
    } else {
        permGroup.style.display = 'none';
        nameLabel.innerHTML = 'Status Name <span style="color:var(--color-danger);">*</span>';
        nameInput.placeholder = 'e.g. Under Review or Standby';
    }
}

function onEditCategoryChange(cat) {
    const permGroup = document.getElementById('edit_permissions_group');
    if (!permGroup) return;
    if (cat === 'User Roles') {
        permGroup.style.display = 'block';
    } else {
        permGroup.style.display = 'none';
    }
}

function selectAddColor(col) {
    const input = document.getElementById('add_modal_color');
    if (input) {
        input.value = col;
        updateAddColorPreview(col);
    }
}

function updateAddColorPreview(col) {
    const hex = document.getElementById('add_color_hex');
    const badge = document.getElementById('add_badge_preview');
    if (hex) hex.textContent = col.toUpperCase();
    if (badge) {
        badge.style.background = col + '26'; // ~15% opacity
        badge.style.color = col;
        badge.style.borderColor = col + '59'; // ~35% opacity
    }
}

function selectEditColor(col) {
    const input = document.getElementById('edit_modal_color');
    if (input) {
        input.value = col;
        updateEditColorPreview(col);
    }
}

function updateEditColorPreview(col) {
    const hex = document.getElementById('edit_color_hex');
    const badge = document.getElementById('edit_badge_preview');
    if (hex) hex.textContent = col.toUpperCase();
    if (badge) {
        badge.style.background = col + '26';
        badge.style.color = col;
        badge.style.borderColor = col + '59';
    }
}

function openAddStatusModal(category, prefix) {
    const catSelect = document.getElementById('add_modal_category');
    if (catSelect && category) {
        catSelect.value = category;
    }
    
    const nameInput = document.getElementById('add_modal_name');
    const commentInput = document.getElementById('add_modal_comment');
    if (nameInput) nameInput.value = '';
    if (commentInput) commentInput.value = '';
    onAddCategoryChange(category || 'Custom');

    // Default color palette choice
    const defaultColor = (category === 'Checklist Results') ? '#03c95a' : '#3b82f6';
    selectAddColor(defaultColor);

    const modal = document.getElementById('addStatusModal');
    if (modal) {
        modal.style.display = 'flex';
        if (nameInput) nameInput.focus();
    }
}

function closeAddStatusModal() {
    const modal = document.getElementById('addStatusModal');
    if (modal) modal.style.display = 'none';
}

function openEditStatusModal(name, label, category, comment, jsonPermissions, color) {
    const origInput = document.getElementById('edit_modal_original_name');
    const nameInput = document.getElementById('edit_modal_name');
    const catSelect = document.getElementById('edit_modal_category');
    const commentInput = document.getElementById('edit_modal_comment');

    if (origInput) origInput.value = name;
    if (nameInput) nameInput.value = label;
    if (catSelect) catSelect.value = category;
    if (commentInput) commentInput.value = comment;

    onEditCategoryChange(category);

    // Set and preview current color
    const activeColor = color || '#03c95a';
    selectEditColor(activeColor);

    let perms = {};
    try {
        perms = JSON.parse(jsonPermissions) || {};
    } catch(e) {
        perms = {};
    }

    const modules = ['dashboard', 'inspections', 'reports', 'equipment', 'categories', 'users', 'settings'];
    modules.forEach(mod => {
        const level = perms[mod] || 'none';
        const radio = document.getElementById(`edit_perm_${level}_${mod}`);
        if (radio) {
            radio.checked = true;
        } else {
            const noneRadio = document.getElementById(`edit_perm_none_${mod}`);
            if (noneRadio) noneRadio.checked = true;
        }
    });

    const modal = document.getElementById('editStatusModal');
    if (modal) modal.style.display = 'flex';
}

function closeEditStatusModal() {
    const modal = document.getElementById('editStatusModal');
    if (modal) modal.style.display = 'none';
}

function openTestEmailModal(e) {
    if (e && typeof e.stopPropagation === 'function') e.stopPropagation();
    const modal = document.getElementById('testEmailModal');
    if (modal) modal.style.setProperty('display', 'flex', 'important');
}

function closeTestEmailModal() {
    const modal = document.getElementById('testEmailModal');
    if (modal) modal.style.setProperty('display', 'none', 'important');
}

function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) {
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        }
    } else {
        input.type = 'password';
        if (icon) {
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
}

function previewEnterpriseLogo(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
            const previewImg = document.getElementById('enterprise_logo_preview');
            if (previewImg) {
                previewImg.src = e.target.result;
            }
            const nameEl = document.getElementById('enterprise_logo_filename');
            if (nameEl) {
                nameEl.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB - Preview)';
                nameEl.style.color = 'var(--primary)';
                nameEl.style.fontWeight = '600';
            }
        };
        reader.readAsDataURL(file);
    }
}

// Auto-switch to tab if passed in query string (e.g. ?tab=mail-pane)
window.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab');
    if (tab) {
        const targetBtn = document.querySelector(`[onclick*="${tab}"]`);
        if (targetBtn) {
            switchSettingsPane(tab, targetBtn);
        }
    }
});

window.addEventListener('click', (e) => {
    const addModal = document.getElementById('addStatusModal');
    const editModal = document.getElementById('editStatusModal');
    const testEmailModal = document.getElementById('testEmailModal');
    if (e.target === addModal) closeAddStatusModal();
    if (e.target === editModal) closeEditStatusModal();
    if (e.target === testEmailModal) closeTestEmailModal();
});
</script>

<?php view('layouts.footer'); ?>
