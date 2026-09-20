<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize(enterprise_name()) ?><?= !empty($title) ? ' - ' . sanitize($title) : '' ?></title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/images/favicon.ico">

    <!-- FontAwesome Icons (Self-hosted for offline/LAN access) -->
    <link rel="stylesheet" href="<?= asset('fontawesome/css/all.min.css') ?>">
    <!-- App CSS -->
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>?v=<?= filemtime(__DIR__ . '/../../../public/assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/dashboard.css') ?>?v=<?= filemtime(__DIR__ . '/../../../public/assets/css/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/maintenance.css') ?>?v=<?= file_exists(__DIR__ . '/../../../public/assets/css/maintenance.css') ? filemtime(__DIR__ . '/../../../public/assets/css/maintenance.css') : '1.0' ?>">
    <!-- Dynamic Status, Role & Checklist Colors -->
    <style>
<?= \App\Config\Constants::getDynamicStyles() ?>
    </style>
</head>
<body>
<div class="app-wrapper">
