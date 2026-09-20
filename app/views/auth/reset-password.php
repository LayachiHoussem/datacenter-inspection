<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password - <?= sanitize(enterprise_name()) ?></title>
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
            max-width: 440px;
            background: #ffffff;
            border: 1px solid #e2e5ed;
            border-radius: 12px;
            padding: 36px 30px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.04);
            margin: 20px;
        }
        .brand-header {
            text-align: center;
            margin-bottom: 22px;
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
        .user-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.82rem;
            color: #334155;
            margin-bottom: 20px;
            width: 100%;
            box-sizing: border-box;
            justify-content: center;
        }
        .input-group {
            position: relative;
        }
        .toggle-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 4px;
            font-size: 0.9rem;
        }
        .toggle-btn:hover {
            color: #475569;
        }
        .hint-text {
            color: #94a3b8;
            font-size: 0.78rem;
            margin-top: 4px;
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
        ?>
        <?php if (!empty($logoUrl)): ?>
            <div style="margin-bottom:12px;">
                <img src="<?= $logoUrl ?>?v=<?= time() ?>" alt="<?= sanitize($entName) ?>" style="max-height:60px; max-width:180px; object-fit:contain;">
            </div>
        <?php else: ?>
            <i class="fa-solid fa-lock-open" style="color: #e75113;"></i>
        <?php endif; ?>
        <h2>Set New Password</h2>
        <p><?= sanitize($entName) ?></p>
    </div>

    <div class="user-pill">
        <i class="fa-solid fa-user-check" style="color: #e75113;"></i>
        <span>Resetting password for: <strong><?= sanitize($email ?? '') ?></strong></span>
    </div>

    <?php if ($flash = get_flash()): ?>
        <div class="alert alert-<?= sanitize($flash['type']) ?>" style="margin-bottom: 20px; font-size: 0.88rem;">
            <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check' : ($flash['type'] === 'danger' ? 'fa-circle-xmark' : 'fa-circle-info') ?>"></i>
            <span><?= sanitize($flash['message']) ?></span>
        </div>
    <?php endif; ?>

    <form action="/reset-password" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="email" value="<?= sanitize($email ?? '') ?>">
        <input type="hidden" name="token" value="<?= sanitize($token ?? '') ?>">

        <div class="form-group">
            <label class="form-label" for="password">New Password</label>
            <div class="input-group">
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required autofocus style="padding-right: 40px;">
                <button type="button" class="toggle-btn" onclick="toggleVisibility('password', this)" title="Show/Hide Password">
                    <i class="fa-regular fa-eye"></i>
                </button>
            </div>
            <div class="hint-text">Minimum 6 characters</div>
            <?php if (isset($errors['password'])): ?>
                <div style="color: #f72b50; font-size: 0.8rem; margin-top: 4px;"><?= $errors['password'][0] ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label" for="password_confirm">Confirm New Password</label>
            <div class="input-group">
                <input type="password" id="password_confirm" name="password_confirm" class="form-control" placeholder="••••••••" required style="padding-right: 40px;">
                <button type="button" class="toggle-btn" onclick="toggleVisibility('password_confirm', this)" title="Show/Hide Password">
                    <i class="fa-regular fa-eye"></i>
                </button>
            </div>
            <?php if (isset($errors['password_confirm'])): ?>
                <div style="color: #f72b50; font-size: 0.8rem; margin-top: 4px;"><?= $errors['password_confirm'][0] ?></div>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 11px; margin-top: 12px; font-weight: 600; font-size: 0.92rem;">
            <i class="fa-solid fa-shield-check" style="margin-right: 6px;"></i> Update &amp; Save Password
        </button>
    </form>

    <div style="text-align: center; margin-top: 24px; padding-top: 18px; border-top: 1px solid #f1f3f9;">
        <a href="/login" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Cancel &amp; Back to Sign In
        </a>
    </div>
</div>

<script>
function toggleVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (!input || !icon) return;
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>

</body>
</html>
