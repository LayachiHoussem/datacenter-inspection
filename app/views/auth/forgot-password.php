<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?= sanitize(enterprise_name()) ?></title>
    <link rel="icon" type="image/png" href="<?= enterprise_logo_url() ?>?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= asset('fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: #f7f8fb;
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        .auth-card {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            border: 1px solid #e2e5ed;
            border-radius: 12px;
            padding: 36px 30px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.04);
            margin: 20px;
        }
        .brand-header {
            text-align: center;
            margin-bottom: 24px;
        }
        .brand-header i {
            font-size: 2.2rem;
            color: #e75113;
            margin-bottom: 8px;
        }
        .brand-header h2 {
            font-size: 1.35rem;
            font-weight: 700;
            color: #2a2f4c;
            margin-top: 6px;
            margin-bottom: 4px;
        }
        .brand-header p {
            color: #8c92b3;
            font-size: 0.85rem;
            margin-top: 2px;
            margin-bottom: 0;
        }
        .instruction-text {
            color: #555b77;
            font-size: 0.88rem;
            line-height: 1.5;
            margin-bottom: 22px;
            text-align: center;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #64748b;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: color 0.15s ease;
        }
        .back-link:hover {
            color: #e75113;
        }
    </style>
</head>
<body>

<div class="auth-card">
    <div class="brand-header">
        <?php 
        $logoUrl = enterprise_logo_url();
        $entName = enterprise_name();
        $entTagline = enterprise_setting('tagline', 'Facilities Inspection & Maintenance Portal');
        ?>
        <?php if (!empty($logoUrl)): ?>
            <div style="margin-bottom:12px;">
                <img src="<?= $logoUrl ?>?v=<?= time() ?>" alt="<?= sanitize($entName) ?>" style="max-height:60px; max-width:180px; object-fit:contain;">
            </div>
        <?php else: ?>
            <i class="fa-solid fa-key" style="color: #e75113;"></i>
        <?php endif; ?>
        <h2>Password Recovery</h2>
        <p><?= sanitize($entName) ?></p>
    </div>

    <p class="instruction-text">
        Enter the email address registered with your account and we will dispatch a secure link to reset your password.
    </p>

    <?php if ($flash = get_flash()): ?>
        <div class="alert alert-<?= sanitize($flash['type']) ?>" style="margin-bottom: 20px; font-size: 0.88rem;">
            <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check' : ($flash['type'] === 'danger' ? 'fa-circle-xmark' : 'fa-circle-info') ?>"></i>
            <div><?= $flash['message'] /* Allow raw HTML for fallback link */ ?></div>
        </div>
    <?php endif; ?>

    <form action="/forgot-password" method="POST">
        <?= csrf_field() ?>
        
        <div class="form-group">
            <label class="form-label" for="email">Registered Email Address</label>
            <div style="position: relative;">
                <input type="email" id="email" name="email" class="form-control" placeholder="user@datacenter.local" value="<?= sanitize($old['email'] ?? '') ?>" required autofocus style="padding-left: 38px;">
                <i class="fa-solid fa-envelope" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.9rem;"></i>
            </div>
            <?php if (isset($errors['email'])): ?>
                <div style="color: #f72b50; font-size: 0.8rem; margin-top: 4px;"><?= $errors['email'][0] ?></div>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 11px; margin-top: 10px; font-weight: 600; font-size: 0.92rem;">
            <i class="fa-solid fa-paper-plane" style="margin-right: 6px;"></i> Send Reset Link
        </button>
    </form>

    <div style="text-align: center; margin-top: 24px; padding-top: 18px; border-top: 1px solid #f1f3f9;">
        <a href="/login" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Back to Sign In
        </a>
    </div>
</div>

</body>
</html>
