<?php
$user = auth_user();
?>
<div class="main-wrapper">
    <header class="topbar">
        <div class="topbar-title" style="display:flex; align-items:center; flex-wrap:wrap; gap:10px;">
            <h1 style="margin:0;"><?= isset($title) ? sanitize($title) : sanitize(enterprise_name()) ?></h1>
            <span class="badge badge-secondary" style="font-size:0.72rem; font-weight:500; opacity:0.85;" title="Primary Facility Site">
                <i class="fa-solid fa-location-dot" style="color:var(--primary);"></i> <?= sanitize(enterprise_site()) ?>
            </span>
        </div>
        <div class="user-profile">
            <div class="avatar-circle">
                <?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?>
            </div>
            <div>
                <div style="font-weight: 600; font-size: 0.88rem; color: var(--text-primary);"><?= sanitize($user['name'] ?? 'User') ?></div>
                <div style="font-size: 0.75rem; color: var(--primary); text-transform: uppercase; font-weight: 600;"><?= sanitize($user['role'] ?? 'Inspector') ?></div>
            </div>
        </div>
    </header>
    <main class="content-body">
        <?php if ($flash = get_flash()): ?>
            <div class="alert alert-<?= sanitize($flash['type']) ?>" data-flash="true" data-type="<?= sanitize($flash['type']) ?>" data-message="<?= sanitize($flash['message']) ?>">
                <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check' : ($flash['type'] === 'danger' ? 'fa-circle-xmark' : ($flash['type'] === 'warning' ? 'fa-triangle-exclamation' : 'fa-circle-info')) ?>"></i>
                <span><?= sanitize($flash['message']) ?></span>
            </div>
        <?php endif; ?>
