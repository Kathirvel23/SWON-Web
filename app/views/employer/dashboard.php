<?php
/** @var array<string, mixed> $user */
/** @var list<array<string, mixed>> $jobs */
/** @var array<string, int> $stats */
$firstName = explode(' ', trim((string) $user['display_name']))[0];

// The opportunity with the most Flight Requests still waiting on a decision.
$needsDecision = null;
foreach ($jobs as $job) {
    if ((int) $job['pending_count'] > 0) {
        $needsDecision = $job;
        break;
    }
}
?>
<section class="hero">
    <div class="hero-split">
        <div class="hero-inner">
            <span class="eyebrow"><?= icon('sparkles') ?> Host dashboard</span>
            <h1>Hiring, without the phone calls.</h1>
            <p>Post an opportunity, review Flight Requests and hire your Formation — the pay, dates and headcount stay agreed.</p>
            <div class="hero-actions">
                <a class="btn-sw" href="<?= url('/employer/jobs/new') ?>">Create Opportunity <?= icon('plus') ?></a>
                <a class="btn-sw btn-ghost" href="<?= url('/employer/jobs') ?>">Manage opportunities</a>
            </div>
        </div>

        <div class="hero-side">
            <div class="hero-glass">
                <?php if ($needsDecision !== null): ?>
                    <div class="hg-label">Needs your decision</div>
                    <div class="hg-value"><?= (int) $needsDecision['pending_count'] ?> Flight Request<?= (int) $needsDecision['pending_count'] === 1 ? '' : 's' ?></div>
                    <div class="hg-note"><?= e($needsDecision['title']) ?></div>
                    <a class="btn-sw btn-sm mt-2" href="<?= url('/employer/jobs/' . rawurlencode((string) $needsDecision['public_id']) . '/queue') ?>">
                        Open queue <?= icon('arrow') ?>
                    </a>
                <?php else: ?>
                    <div class="hg-label">Next step</div>
                    <div class="hg-value">Create an opportunity</div>
                    <div class="hg-note">Publish an opportunity and start receiving Flight Requests the same day.</div>
                    <a class="btn-sw btn-sm mt-2" href="<?= url('/employer/jobs/new') ?>">
                        Create Opportunity <?= icon('plus') ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php if ($needsDecision !== null): ?>
<?php endif; ?>

<div class="stat-grid mt-3">
    <div class="stat">
        <div class="stat-icon"><?= icon('briefcase') ?></div>
        <div class="stat-label">Live opportunities</div>
        <div class="stat-value"><?= (int) $stats['open_jobs'] ?></div>
        <div class="stat-note">Open for Flight Requests</div>
    </div>
    <div class="stat accent-warm">
        <div class="stat-icon"><?= icon('inbox') ?></div>
        <div class="stat-label">Flight Requests</div>
        <div class="stat-value"><?= (int) $stats['pending_applicants'] ?></div>
        <div class="stat-note">Awaiting your decision</div>
    </div>
    <div class="stat accent-money">
        <div class="stat-icon"><?= icon('check') ?></div>
        <div class="stat-label">Hired</div>
        <div class="stat-value"><?= (int) $stats['hired_workers'] ?></div>
        <div class="stat-note">Across all opportunities</div>
    </div>
    <div class="stat accent-ink">
        <div class="stat-icon"><?= icon('grid') ?></div>
        <div class="stat-label">Total opportunities</div>
        <div class="stat-value"><?= (int) $stats['total_jobs'] ?></div>
        <div class="stat-note">Including drafts</div>
    </div>
</div>

<div class="section-title">
    <h2>Your opportunities</h2>
    <a href="<?= url('/employer/jobs') ?>">View all <?= icon('arrow') ?></a>
</div>

<?php if ($jobs === []): ?>
    <?php \App\View::partial('partials/empty', [
        'heading'    => 'No opportunities yet',
        'text'       => 'Create your first opportunity and start receiving Flight Requests the same day.',
        'iconName'   => 'file',
        'actionHref' => '/employer/jobs/new',
        'actionLabel' => 'Create Opportunity',
    ]); ?>
<?php else: ?>
    <div class="list">
        <?php foreach ($jobs as $job): ?>
            <?php
            $seatsLeft = max(0, (int) $job['headcount'] - (int) $job['hired_count']);
            ?>
            <div class="list-row">
                <?php $photo = category_photo((string) $job['category_slug']); ?>
                <?php if ($photo !== null): ?>
                    <img class="thumb" src="<?= asset($photo . '-sm.webp') ?>" alt="" width="440" height="293" loading="lazy" decoding="async">
                <?php else: ?>
                    <span class="mark mark-sm tile-<?= e((string) $job['category_slug']) ?>"><?= icon(category_icon((string) $job['category_slug']), 'icon') ?></span>
                <?php endif; ?>
                <div class="grow">
                    <div class="title">
                        <a href="<?= url('/jobs/' . rawurlencode((string) $job['public_id'])) ?>"><?= e($job['title']) ?></a>
                    </div>
                    <div class="sub">
                        <?= e($job['area_name']) ?> · <?= e(pay_label($job['pay_amount_paise'], $job['pay_type'])) ?>
                        · <?= e(format_range($job['starts_on'], $job['ends_on'])) ?>
                    </div>
                </div>
                <div class="end">
                    <span class="chip <?= e(status_class((string) $job['status'])) ?>"><?= e(status_label((string) $job['status'])) ?></span>
                    <span class="seats-pill <?= $seatsLeft <= 0 ? 'out' : ($seatsLeft <= 2 ? 'low' : '') ?>">
                        <?= icon('users') ?> <?= (int) $job['hired_count'] ?> of <?= (int) $job['headcount'] ?> hired
                    </span>
                    <a class="btn-sw btn-sm btn-soft" href="<?= url('/employer/jobs/' . rawurlencode((string) $job['public_id']) . '/queue') ?>">
                        <?= (int) $job['pending_count'] ?> request<?= (int) $job['pending_count'] === 1 ? '' : 's' ?>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
