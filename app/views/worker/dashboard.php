<?php
/** @var array<string, mixed> $user */
/** @var list<array<string, mixed>> $jobs */
/** @var list<array<string, mixed>> $applications */
/** @var array<string, int> $stats */
$firstName = explode(' ', trim((string) $user['display_name']))[0];

// Next confirmed opportunity — the one thing a worker wants to see first.
$nextShift = null;
foreach ($applications as $application) {
    if (strtoupper((string) $application['status']) === 'HIRED') {
        $nextShift = $application;
        break;
    }
}
?>
<section class="hero">
    <div class="hero-split">
        <div class="hero-inner">
            <span class="eyebrow"><?= icon('sparkles') ?> Worker dashboard</span>
            <h1>Good to see you, <?= e($firstName) ?>.</h1>
            <p>Pick an opportunity that fits your week. Pay, dates and headcount are agreed before you start — no phone tag.</p>
            <div class="hero-actions">
                <a class="btn-sw" href="<?= url('/jobs') ?>">Find work <?= icon('arrow') ?></a>
                <a class="btn-sw btn-ghost" href="<?= url('/applications') ?>">My Flight Requests</a>
            </div>
        </div>

        <div class="hero-side">
            <?php if ($nextShift !== null): ?>
                <div class="hero-glass">
                    <div class="hg-label">Next confirmed opportunity</div>
                    <div class="hg-value"><?= e(format_date($nextShift['starts_on'])) ?></div>
                    <div class="hg-note"><?= e($nextShift['title']) ?> · <?= e(money($nextShift['pay_amount_paise'])) ?> <?= e(pay_unit((string) $nextShift['pay_type'])) ?></div>
                </div>
            <?php else: ?>
                <div class="hero-glass">
                    <div class="hg-label">No opportunity hired yet</div>
                    <div class="hg-value"><?= (int) $stats['open_jobs'] ?></div>
                    <div class="hg-note">opportunities are open right now</div>
                </div>
            <?php endif; ?>
            <div class="hero-glass">
                <div class="hg-label">Awaiting a reply</div>
                <div class="hg-value"><?= max(0, (int) $stats['applications'] - (int) $stats['hired']) ?></div>
                <div class="hg-note">of <?= (int) $stats['applications'] ?> Flight Requests so far</div>
                <a class="btn-sw btn-sm mt-2" href="<?= url('/applications') ?>">Open <?= icon('arrow') ?></a>
            </div>
        </div>
    </div>
</section>

<form class="searchbar" method="get" action="<?= url('/jobs') ?>" role="search">
    <span class="s-ic" aria-hidden="true"><?= icon('search') ?></span>
    <input type="search" name="q" placeholder="Search opportunities or skills" aria-label="Search opportunities">
    <button class="btn-sw btn-sm s-go" type="submit">Search</button>
</form>

<?php if ($nextShift !== null): ?>
<?php endif; ?>

<div class="stat-grid cols-3 mt-3">
    <div class="stat">
        <div class="stat-icon"><?= icon('briefcase') ?></div>
        <div class="stat-label">Open opportunities</div>
        <div class="stat-value"><?= (int) $stats['open_jobs'] ?></div>
        <div class="stat-note">Available to request today</div>
    </div>
    <div class="stat accent-ink">
        <div class="stat-icon"><?= icon('file') ?></div>
        <div class="stat-label">Flight Requests</div>
        <div class="stat-value"><?= (int) $stats['applications'] ?></div>
        <div class="stat-note">All time</div>
    </div>
    <div class="stat accent-money">
        <div class="stat-icon"><?= icon('check') ?></div>
        <div class="stat-label">Hired</div>
        <div class="stat-value"><?= (int) $stats['hired'] ?></div>
        <div class="stat-note">Confirmed opportunities</div>
    </div>
</div>

<div class="section-title">
    <h2>Fresh opportunities</h2>
    <a href="<?= url('/jobs') ?>">See all opportunities <?= icon('arrow') ?></a>
</div>

<?php if ($jobs === []): ?>
    <?php \App\View::partial('partials/empty', [
        'heading'    => 'No open opportunities yet',
        'text'       => 'New opportunities are posted every day. Check back shortly.',
        'iconName'   => 'briefcase',
        'actionHref' => '/jobs',
        'actionLabel' => 'Browse opportunities',
    ]); ?>
<?php else: ?>
    <div class="job-grid">
        <?php foreach ($jobs as $job): ?>
            <?php \App\View::partial('partials/job-card', ['job' => $job]); ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="section-title">
    <h2>Your Flight Requests</h2>
    <a href="<?= url('/applications') ?>">View all <?= icon('arrow') ?></a>
</div>

<?php if ($applications === []): ?>
    <?php \App\View::partial('partials/empty', [
        'heading'    => 'You have not sent a Flight Request yet',
        'text'       => 'When you send one, its status will appear here.',
        'iconName'   => 'file',
        'actionHref' => '/jobs',
        'actionLabel' => 'Find your first opportunity',
    ]); ?>
<?php else: ?>
    <div class="list">
        <?php foreach ($applications as $application): ?>
            <div class="list-row">
                <div class="grow">
                    <div class="title">
                        <a href="<?= url('/jobs/' . rawurlencode((string) $application['job_public_id'])) ?>">
                            <?= e($application['title']) ?>
                        </a>
                    </div>
                    <div class="sub">
                        <?= e($application['org_name'] ?: 'Host') ?> · <?= e($application['area_name']) ?>
                        · <?= e(pay_label($application['pay_amount_paise'], $application['pay_type'])) ?>
                    </div>
                </div>
                <div class="end">
                    <span class="chip <?= e(status_class((string) $application['status'])) ?>">
                        <?= e(status_label((string) $application['status'])) ?>
                    </span>
                    <span class="tiny muted"><?= e(format_range($application['starts_on'], $application['ends_on'])) ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
