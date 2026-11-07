<?php
/** @var string $content */
/** @var string|null $title */
$user = auth_user();
$path = current_path();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="light">
    <meta name="description" content="SWON — temporary workforce marketplace for Coimbatore and Tiruppur. Find opportunities or build your crew.">
    <title><?= e($title ?? 'SWON') ?> · SWON</title>
    <link rel="icon" type="image/png" sizes="48x48" href="<?= asset('img/brand/favicon.png') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= asset('img/brand/apple-touch-icon.png') ?>">
    <link rel="manifest" href="<?= url('/manifest.webmanifest') ?>">
    <script>
        window.addEventListener('beforeinstallprompt', function (event) {
            event.preventDefault();
            window.__swonInstallPrompt = event;
        });
    </script>
    <meta name="application-name" content="SWON">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="SWON">
    <meta name="swon-pwa" content="<?= \App\Env::bool('PWA_ENABLED', true) ? '1' : '0' ?>">
    <meta name="swon-base" content="<?= e(BASE_URL) ?>">
    <meta property="og:title" content="<?= e($title ?? 'SWON') ?> · SWON">
    <meta property="og:image" content="<?= asset('img/brand/icon-512.png') ?>">
    <meta name="theme-color" content="#0F766E">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('vendor/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="app-body">
<a class="skip-link" href="#main-content">Skip to content</a>
<header class="topbar">
    <div class="container-app topbar-inner">
        <a class="brand" href="<?= url($user ? \App\Auth::homeFor((string) $user['role']) : '/') ?>" aria-label="SWON home">
            <img src="<?= asset('img/brand/swon-wordmark.png') ?>" alt="SWON" width="559" height="161">
        </a>

        <nav class="nav-links" id="main-nav" data-nav-links aria-label="Main navigation">
            <?php if ($user && $user['role'] === 'worker'): ?>
                <a class="nav-link <?= $path === '/worker' ? 'active' : '' ?>" href="<?= url('/worker') ?>" <?= $path === '/worker' ? 'aria-current="page"' : '' ?>>Dashboard</a>
                <a class="nav-link <?= str_starts_with($path, '/jobs') ? 'active' : '' ?>" href="<?= url('/jobs') ?>" <?= str_starts_with($path, '/jobs') ? 'aria-current="page"' : '' ?>>Find work</a>
                <a class="nav-link <?= $path === '/applications' ? 'active' : '' ?>" href="<?= url('/applications') ?>" <?= $path === '/applications' ? 'aria-current="page"' : '' ?>>My Flight Requests</a>
            <?php elseif ($user && $user['role'] === 'employer'): ?>
                <a class="nav-link <?= $path === '/employer' ? 'active' : '' ?>" href="<?= url('/employer') ?>" <?= $path === '/employer' ? 'aria-current="page"' : '' ?>>Dashboard</a>
                <a class="nav-link <?= $path === '/employer/jobs' ? 'active' : '' ?>" href="<?= url('/employer/jobs') ?>" <?= $path === '/employer/jobs' ? 'aria-current="page"' : '' ?>>My Opportunities</a>
                <a class="nav-link <?= $path === '/employer/jobs/new' ? 'active' : '' ?>" href="<?= url('/employer/jobs/new') ?>" <?= $path === '/employer/jobs/new' ? 'aria-current="page"' : '' ?>>Create Opportunity</a>
            <?php else: ?>
                <a class="nav-link" href="<?= url('/') ?>">Home</a>
                <a class="nav-link" href="<?= url('/login') ?>">Sign in</a>
            <?php endif; ?>
            <?php if ($user): ?>
                <a class="nav-link <?= $path === '/profile' ? 'active' : '' ?>" href="<?= url('/profile') ?>" <?= $path === '/profile' ? 'aria-current="page"' : '' ?>>Profile</a>
            <?php endif; ?>
        </nav>

        <div class="topbar-right">
            <?php if ($user): ?>
                <a class="user-chip" href="<?= url('/profile') ?>" title="Your profile">
                    <span class="avatar"><?= e(initials((string) $user['display_name'])) ?></span>
                    <span class="user-name"><?= e($user['display_name']) ?></span>
                </a>
                <form method="post" action="<?= url('/logout') ?>" class="d-inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn-sw btn-ghost btn-sm" aria-label="Sign out">
                        <?= icon('logout') ?> <span class="d-none d-sm-inline">Sign out</span>
                    </button>
                </form>
            <?php else: ?>
                <a class="btn-sw btn-sm" href="<?= url('/login') ?>">Sign in <?= icon('arrow') ?></a>
            <?php endif; ?>
            <button type="button" class="nav-toggle" data-nav-toggle aria-label="Toggle menu" aria-controls="main-nav" aria-expanded="false">
                <?= icon('menu') ?>
            </button>
        </div>
    </div>
</header>

<main class="page" id="main-content" tabindex="-1">
    <div class="container-app">
        <?php \App\View::partial('partials/flash'); ?>
        <?= $content ?>
    </div>
</main>

<footer class="app-footer">
    <div class="container-app">
        <span class="foot-brand">
            <span class="brand-mark"><?= icon('sparkles') ?></span>
            © <?= date('Y') ?> SWON · Temporary workforce marketplace
        </span>
        <span class="foot-links">
            <?php if ($user && $user['role'] === 'worker'): ?>
                <a href="<?= url('/jobs') ?>">Find work</a>
                <a href="<?= url('/applications') ?>">My Flight Requests</a>
                <a href="<?= url('/profile') ?>">Profile</a>
            <?php elseif ($user && $user['role'] === 'employer'): ?>
                <a href="<?= url('/employer') ?>">Dashboard</a>
                <a href="<?= url('/employer/jobs') ?>">My Opportunities</a>
                <a href="<?= url('/employer/jobs/new') ?>">Create Opportunity</a>
            <?php else: ?>
                <a href="<?= url('/jobs') ?>">Find work</a>
                <a href="<?= url('/employer') ?>">Hire a crew</a>
                <a href="<?= url('/login') ?>">Sign in</a>
            <?php endif; ?>
        </span>
    </div>
</footer>

<?php \App\View::partial('partials/pwa'); ?>
<script src="<?= asset('js/app.js') ?>"></script>
<script src="<?= asset('js/pwa.js') ?>"></script>
</body>
</html>
