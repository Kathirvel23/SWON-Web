<?php
/** @var string $content */
/** @var string|null $title */
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="light">
    <meta name="description" content="SWON — find opportunities and build your crew across Coimbatore and Tiruppur.">
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
    <meta name="theme-color" content="#0F766E">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('vendor/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="splash-body">
    <div class="splash-bg" aria-hidden="true"></div>
    <?= $content ?>
    <?php \App\View::partial('partials/pwa'); ?>
    <script src="<?= asset('js/app.js') ?>"></script>
    <script src="<?= asset('js/pwa.js') ?>"></script>
</body>
</html>
