<?php
/** @var string $content */
/** @var string|null $title */

$previewNav = [
    ['Home', '/preview-home'],
    ['How It Works', '#how-it-works'],
    ['For Organizers', '#for-organizers'],
    ['For Workers', '#for-workers'],
    ['Features', '#features'],
    ['Contact', '#contact'],
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title><?= e($title ?? 'SWON') ?> · SWON</title>
    <meta name="description" content="SWON connects event organizers, businesses and service providers with verified temporary workers in Coimbatore and Tiruppur.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" sizes="48x48" href="<?= asset('img/brand/favicon.png') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= asset('img/brand/apple-touch-icon.png') ?>">
    <link rel="manifest" href="<?= url('/manifest.webmanifest') ?>">
    <meta name="theme-color" content="#0F1E3D">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/preview.css') ?>">
    <noscript><style>[data-reveal]{opacity:1;transform:none}</style></noscript>
</head>
<body>
<a class="pv-skip" href="#main-content">Skip to content</a>

<header class="pv-header">
    <div class="pv-container pv-header-inner">
        <a class="pv-brand" href="<?= url('/preview-home') ?>" aria-label="SWON home">
            <img src="<?= asset('img/brand/swon-wordmark.png') ?>" alt="SWON"
                 width="559" height="161" decoding="async">
        </a>

        <nav class="pv-nav" id="pv-nav" aria-label="Primary">
            <ul class="pv-nav-list">
                <?php foreach ($previewNav as [$label, $href]): ?>
                    <li><a href="<?= e(str_starts_with($href, '#') ? $href : url($href)) ?>"><?= e($label) ?></a></li>
                <?php endforeach; ?>
            </ul>
            <div class="pv-nav-actions">
                <a class="pv-btn pv-btn-ghost pv-btn-sm" href="<?= url('/login') ?>">Sign In</a>
                <a class="pv-btn pv-btn-mint pv-btn-sm" href="<?= url('/login') ?>">Get Started</a>
            </div>
        </nav>

        <button class="pv-burger" id="pv-burger" type="button"
                aria-expanded="false" aria-controls="pv-nav" aria-label="Open menu">
            <span class="pv-burger-box" aria-hidden="true"><span></span><span></span><span></span></span>
        </button>
    </div>
</header>

<main id="main-content">
    <?= $content ?>
</main>

<footer class="pv-footer pv-dark">
    <div class="pv-container">
        <div class="pv-footer-grid">
            <div class="pv-footer-brand-col">
                <div class="pv-footer-brand">SWON<span>.</span></div>
                <p class="pv-footer-blurb">
                    A temporary workforce platform for event organizers, businesses and service
                    providers — and the people who show up to do the work.
                </p>
            </div>

            <div class="pv-footer-col">
                <h3>Platform</h3>
                <ul>
                    <li><a href="#how-it-works">How It Works</a></li>
                    <li><a href="#features">Features</a></li>
                    <li><a href="#trust">Verification &amp; Trust</a></li>
                    <li><a href="#pay">Pay</a></li>
                    <li><a href="#faq">FAQ</a></li>
                </ul>
            </div>

            <div class="pv-footer-col">
                <h3>For Organizers</h3>
                <ul>
                    <li><a href="#for-organizers">Hire a crew</a></li>
                    <li><a href="#benefits">Who it is for</a></li>
                    <li><a href="<?= url('/jobs') ?>">Live opportunities</a></li>
                    <li><a href="<?= url('/login') ?>">Post a requirement</a></li>
                </ul>
            </div>

            <div class="pv-footer-col">
                <h3>For Workers</h3>
                <ul>
                    <li><a href="#for-workers">Find work</a></li>
                    <li><a href="#pay">How pay works</a></li>
                    <li><a href="<?= url('/jobs') ?>">Browse opportunities</a></li>
                    <li><a href="<?= url('/login') ?>">Sign in</a></li>
                </ul>
            </div>
        </div>

        <div class="pv-footer-bottom">
            <span>© <?= date('Y') ?> SWON · Temporary workforce platform</span>
            <span>Coimbatore · Tiruppur</span>
        </div>
    </div>
</footer>

<script>
(function () {
    var burger = document.getElementById('pv-burger');
    var nav = document.getElementById('pv-nav');

    function closeNav(returnFocus) {
        if (!nav.classList.contains('is-open')) { return; }
        nav.classList.remove('is-open');
        burger.setAttribute('aria-expanded', 'false');
        burger.setAttribute('aria-label', 'Open menu');
        if (returnFocus) { burger.focus(); }
    }

    burger.addEventListener('click', function () {
        var open = nav.classList.toggle('is-open');
        burger.setAttribute('aria-expanded', open ? 'true' : 'false');
        burger.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    });

    nav.addEventListener('click', function (event) {
        if (event.target.closest('a')) { closeNav(false); }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') { closeNav(true); }
    });

    document.addEventListener('click', function (event) {
        if (!nav.classList.contains('is-open')) { return; }
        if (!nav.contains(event.target) && !burger.contains(event.target)) { closeNav(false); }
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > 1000) { closeNav(false); }
    });

    var items = document.querySelectorAll('[data-reveal]');
    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (reduced || !('IntersectionObserver' in window)) {
        items.forEach(function (el) { el.classList.add('is-in'); });
        return;
    }

    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-in');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });

    items.forEach(function (el) { observer.observe(el); });
})();
</script>
</body>
</html>
