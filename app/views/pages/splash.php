<div class="splash-wrap">
    <header class="splash-top">
        <a class="brand" href="<?= url('/') ?>" aria-label="SWON home">
            <img src="<?= asset('img/brand/swon-wordmark-light.png') ?>" alt="SWON" width="559" height="161">
        </a>
        <div class="row-gap">
            <a class="btn-sw btn-ghost btn-sm" href="<?= url('/login') ?>">Sign in</a>
            <a class="btn-sw btn-sm" href="<?= url('/login') ?>">Get started <?= icon('arrow') ?></a>
        </div>
    </header>

    <main class="splash-main">
        <div class="splash-copy">
        <span class="splash-eyebrow">
            Temporary workforce marketplace
        </span>
        <h1>Find Opportunities. Hire fast. Get paid fairly.</h1>
        <p class="lede">
            SWON connects workers with hosts across Coimbatore and Tiruppur — daily
            opportunities, event crews, warehouse teams and more. Clear pay, real dates,
            no phone tag.
        </p>
        <div class="hero-actions">
            <a class="btn-sw btn-lg-sw" href="<?= url('/login') ?>">Find work <?= icon('arrow') ?></a>
            <a class="btn-sw btn-ghost btn-lg-sw" href="<?= url('/login') ?>">Hire a crew</a>
        </div>
        </div>

        <div class="splash-cards">
        <div class="splash-feature">
            <span class="role-icon"><?= icon('briefcase') ?></span>
            <h2>Opportunities near you</h2>
            <p>Filter by area, category and pay. Send a Flight Request in a couple of taps.</p>
        </div>
        <div class="splash-feature">
            <span class="role-icon"><?= icon('users') ?></span>
            <h2>Formation in minutes</h2>
            <p>Post an opportunity, review Flight Requests and hire the right people for the day.</p>
        </div>
        <div class="splash-feature">
            <span class="role-icon"><?= icon('shield') ?></span>
            <h2>No surprises</h2>
            <p>Pay, dates and headcount are agreed before the shift starts.</p>
        </div>
        </div>
    </main>

    <footer class="splash-footer">
        <span>© <?= date('Y') ?> SWON · Temporary workforce marketplace</span>
        <span>Coimbatore · Tiruppur</span>
    </footer>
</div>
