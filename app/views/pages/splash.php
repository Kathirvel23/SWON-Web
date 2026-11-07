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
                <span class="dot-live" aria-hidden="true"></span>
                <?= (int) ($openTotal ?? 0) ?> live opportunities · Coimbatore &amp; Tiruppur
            </span>

            <h1>Good work,<br>one shift away.</h1>

            <p class="lede">
                Day rates agreed before you start. Real dates, real headcount, no phone tag —
                for workers who show up and hosts who need a crew today.
            </p>

            <div class="hero-actions">
                <a class="btn-sw btn-lg-sw" href="<?= url('/login') ?>">Find work <?= icon('arrow') ?></a>
                <a class="btn-sw btn-ghost btn-lg-sw" href="<?= url('/login') ?>">Hire a crew</a>
            </div>

            <ul class="splash-trust">
                <li><?= icon('shield') ?> Pay agreed up front</li>
                <li><?= icon('check') ?> Verified hosts</li>
                <li><?= icon('clock') ?> Same-day hiring</li>
            </ul>
        </div>

        <div class="splash-panel">
            <div class="splash-panel-head">
                <span>Latest work</span>
                <a href="<?= url('/jobs') ?>">See all <?= icon('arrow') ?></a>
            </div>

            <?php if (empty($featured)): ?>
                <div class="empty-state" style="padding:28px 20px">
                    <span class="empty-icon"><?= icon('briefcase') ?></span>
                    <strong>No open opportunities yet</strong>
                    <span>New work is posted daily — check back shortly.</span>
                </div>
            <?php else: ?>
                <?php foreach ($featured as $job): ?>
                    <a class="job-peek" href="<?= url('/jobs/' . e($job['public_id'])) ?>">
                        <span class="job-peek-mark"><?= icon('briefcase') ?></span>
                        <span class="job-peek-body">
                            <span class="job-peek-title"><?= e($job['title']) ?></span>
                            <span class="job-peek-meta">
                                <?= e($job['category_name']) ?> · <?= e($job['area_name']) ?>
                            </span>
                            <span class="job-peek-foot">
                                <?= (int) $job['hired_count'] ?>/<?= (int) $job['headcount'] ?> filled
                                <?php if (!empty($job['starts_on'])): ?>
                                    · <?= e(format_date($job['starts_on'])) ?>
                                <?php endif; ?>
                            </span>
                        </span>
                        <span class="job-peek-pay">
                            <?= e(money($job['pay_amount_paise'])) ?>
                            <small><?= match ($job['pay_type']) {
                                'HOUR'  => 'per hour',
                                'TOTAL' => 'total',
                                default => 'per day',
                            } ?></small>
                        </span>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <section class="splash-values">
        <div class="splash-value">
            <span class="role-icon"><?= icon('briefcase') ?></span>
            <div>
                <h2>Work that fits your day</h2>
                <p>Filter by area, category and pay. Apply in a couple of taps.</p>
            </div>
        </div>
        <div class="splash-value">
            <span class="role-icon"><?= icon('users') ?></span>
            <div>
                <h2>A crew in minutes</h2>
                <p>Post an opportunity, review applications, hire the people you need.</p>
            </div>
        </div>
        <div class="splash-value">
            <span class="role-icon"><?= icon('shield') ?></span>
            <div>
                <h2>Nothing left vague</h2>
                <p>Pay, dates and headcount are agreed before the shift starts.</p>
            </div>
        </div>
    </section>

    <footer class="splash-footer">
        <span>© <?= date('Y') ?> SWON · Temporary workforce marketplace</span>
        <span>Coimbatore · Tiruppur</span>
    </footer>
</div>
