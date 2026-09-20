<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sign In - <?= sanitize(enterprise_name()) ?></title>

    <link rel="icon" type="image/png" href="/assets/images/favicon.ico">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?= asset('fontawesome/css/all.min.css') ?>">

    <!-- Main CSS -->
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">

    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: #f7f8fb;
        }

        .login-card {
            width: 100%;
            max-width: 400px;
            background: #ffffff;
            border: 1px solid #e2e5ed;
            border-radius: 12px;
            padding: 36px 28px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
        }

        .brand-header {
            text-align: center;
            margin-bottom: 28px;
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
        }

        .brand-header p {
            color: #8c92b3;
            font-size: 0.85rem;
            margin-top: 2px;
        }

        .demo-box {
            background: rgba(231, 81, 19, 0.06);
            border: 1px dashed rgba(231, 81, 19, 0.2);
            border-radius: 8px;
            padding: 12px 14px;
            margin-bottom: 22px;
            font-size: 0.82rem;
            color: #2a2f4c;
        }

        /* Password field */
        .password-wrapper {
            position: relative;
            width: 100%;
        }

        .password-wrapper .form-control {
            width: 100%;
            padding-right: 45px;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            cursor: pointer;
            color: #777;
            font-size: 1rem;
            padding: 5px;
            z-index: 2;
            transition: color 0.2s ease;
        }

        .toggle-password:hover {
            color: #e75113;
        }

        .toggle-password:focus {
            outline: none;
            color: #e75113;
        }
    </style>
</head>

<body>

<div class="login-card">

    <!-- Brand -->
    <div class="brand-header">

        <?php
        $logoUrl = enterprise_logo_url();
        $entName = enterprise_name();
        $entTagline = enterprise_setting(
            'tagline',
            'Facilities Inspection & Maintenance Portal'
        );
        ?>

        <?php if (!empty($logoUrl)): ?>

            <div style="margin-bottom:12px;">
                <img
                    src="<?= $logoUrl ?>?v=<?= time() ?>"
                    alt="<?= sanitize($entName) ?>"
                    style="
                        max-height:60px;
                        max-width:180px;
                        object-fit:contain;
                    "
                >
            </div>

        <?php else: ?>

            <i class="fa-solid fa-server"></i>

        <?php endif; ?>

        <h2>
            <?= sanitize($entName) ?>
        </h2>

        <p>
            <?= sanitize($entTagline) ?>
        </p>

    </div>


    <!-- Flash Message -->
    <?php if ($flash = get_flash()): ?>

        <div class="alert alert-<?= sanitize($flash['type']) ?>">

            <i class="fa-solid fa-circle-info"></i>

            <span>
                <?= sanitize($flash['message']) ?>
            </span>

        </div>

    <?php endif; ?>


    <!-- Demo Credentials -->
    
    <div class="demo-box">
        <strong style="color: #e75113;">
            Demo Login Credentials:
        </strong>
        <br>

        &bull;
        Admin:
        <code>admin@datacenter.local</code>
        /
        <code>Admin123</code>

        <br>

        &bull;
        Inspector:
        <code>inspector@datacenter.local</code>
        /
        <code>password123</code>
    </div>
    

    <!-- Login Form -->
    <form action="/login" method="POST">

        <?= csrf_field() ?>


        <!-- Email -->
        <div class="form-group">

            <label
                class="form-label"
                for="email"
            >
                Email Address
            </label>

            <input
                type="email"
                id="email"
                name="email"
                class="form-control"
                placeholder="user@datacenter.local"
                value="<?= sanitize($old['email'] ?? '') ?>"
                required
                autofocus
            >

            <?php if (isset($errors['email'])): ?>

                <div
                    style="
                        color:#f72b50;
                        font-size:0.8rem;
                        margin-top:4px;
                    "
                >
                    <?= $errors['email'][0] ?>
                </div>

            <?php endif; ?>

        </div>


        <!-- Password -->
        <div class="form-group">

            <div
                style="
                    display:flex;
                    justify-content:space-between;
                    align-items:center;
                    margin-bottom:6px;
                "
            >

                <label
                    class="form-label"
                    for="password"
                    style="margin-bottom:0;"
                >
                    Password
                </label>

                <a
                    href="/forgot-password"
                    style="
                        font-size:0.8rem;
                        color:#e75113;
                        text-decoration:none;
                        font-weight:500;
                    "
                >
                    Forgot password?
                </a>

            </div>


            <!-- Password + Eye Button -->
            <div class="password-wrapper">

                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control"
                    placeholder="••••••••"
                    required
                    autocomplete="current-password"
                >

                <button
                    type="button"
                    id="togglePassword"
                    class="toggle-password"
                    aria-label="Show password"
                    title="Show password"
                >
                    <i class="fa-solid fa-eye"></i>
                </button>

            </div>


            <!-- Password Error -->
            <?php if (isset($errors['password'])): ?>

                <div
                    style="
                        color:#f72b50;
                        font-size:0.8rem;
                        margin-top:4px;
                    "
                >
                    <?= $errors['password'][0] ?>
                </div>

            <?php endif; ?>

        </div>


        <!-- Submit -->
        <button
            type="submit"
            class="btn btn-primary"
            style="
                width:100%;
                padding:11px;
                margin-top:8px;
                font-weight:600;
            "
        >

            <i class="fa-solid fa-right-to-bracket"></i>

            Sign In to Portal

        </button>

    </form>

</div>


<!-- Password Show/Hide -->
<script>
document.addEventListener('DOMContentLoaded', function () {

    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');

    if (!togglePassword || !password) {
        return;
    }

    togglePassword.addEventListener('click', function () {

        // Change password input type
        if (password.type === 'password') {

            password.type = 'text';

            // Change icon
            this.innerHTML = '<i class="fa-solid fa-eye-slash"></i>';

            // Accessibility
            this.setAttribute('aria-label', 'Hide password');
            this.setAttribute('title', 'Hide password');

        } else {

            password.type = 'password';

            // Change icon
            this.innerHTML = '<i class="fa-solid fa-eye"></i>';

            // Accessibility
            this.setAttribute('aria-label', 'Show password');
            this.setAttribute('title', 'Show password');

        }

    });

});
</script>

</body>
</html>