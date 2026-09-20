<?php
$user = auth_user();
$currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
?>
<aside class="sidebar">
    <div class="sidebar-brand" style="gap:10px; overflow:hidden;">
        <?php 
        $entLogo = enterprise_logo_url();
        $entName = enterprise_name();
        ?>
        <?php if (!empty($entLogo)): ?>
            <img src="<?= $entLogo ?>?v=<?= time() ?>" alt="Logo" style="max-height:50px; max-width:80px; object-fit:contain; flex-shrink:0;">
        <?php else: ?>
            <i class="fa-solid fa-server"></i>
        <?php endif; ?>
        <span style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-size:0.95rem;"><?= sanitize($entName) ?></span>
    </div>
    
    <nav class="sidebar-menu">
        <div class="menu-label">Main Ops</div>
        <?php if (can_access('dashboard')): ?>
        <a href="/dashboard" class="nav-link <?= str_starts_with($currentUri, '/dashboard') || $currentUri === '/' ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-line"></i> Dashboard
        </a>
        <?php endif; ?>

        <?php if (can_access('inspections')): ?>
        <a href="/inspections" class="nav-link <?= str_starts_with($currentUri, '/inspections') ? 'active' : '' ?>">
            <i class="fa-solid fa-clipboard-check"></i> Inspections
        </a>
        <?php endif; ?>

        <?php if (can_access('maintenance')): ?>
        <a href="/maintenance" class="nav-link <?= str_starts_with($currentUri, '/maintenance') ? 'active' : '' ?>">
            <i class="fa-solid fa-calendar-check"></i> Maintenance Plan
        </a>
        <?php endif; ?>

        <?php if (can_access('reports')): ?>
        <a href="/reports" class="nav-link <?= str_starts_with($currentUri, '/reports') ? 'active' : '' ?>">
            <i class="fa-solid fa-file-contract"></i> Reports
        </a>
        <?php endif; ?>

        <?php if (can_access('equipment')): ?>
        <a href="/equipment" class="nav-link <?= str_starts_with($currentUri, '/equipment') ? 'active' : '' ?>">
            <i class="fa-solid fa-hard-drive"></i> Equipment Racks
        </a>
        <?php endif; ?>

        <?php if (can_access('categories') || can_access('users') || can_access('settings')): ?>
        <div class="menu-label">Administration</div>
        
        <?php if (can_access('categories')): ?>
        <a href="/categories" class="nav-link <?= str_starts_with($currentUri, '/categories') ? 'active' : '' ?>">
            <i class="fa-solid fa-layer-group"></i> Categories & Checklists
        </a>
        <?php endif; ?>

        <?php if (can_access('users')): ?>
        <a href="/users" class="nav-link <?= str_starts_with($currentUri, '/users') ? 'active' : '' ?>">
            <i class="fa-solid fa-users"></i> User Accounts
        </a>
        <?php endif; ?>

        <?php if (can_access('settings')): ?>
        <a href="/settings" class="nav-link <?= str_starts_with($currentUri, '/settings') ? 'active' : '' ?>">
            <i class="fa-solid fa-gears"></i> System Settings
        </a>
        <?php endif; ?>
        <?php endif; ?>

        <div class="menu-label">Session</div>
        <a href="/logout" class="nav-link">
            <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
        </a>
    </nav>
</aside>
