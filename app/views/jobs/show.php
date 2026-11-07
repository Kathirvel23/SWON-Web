<?php
/** @var array<string, mixed> $job */
/** @var bool $isOwner */
/** @var array<string, mixed>|null $myApplication */
/** @var array<string, mixed> $user */
$seatsLeft = max(0, (int) $job['headcount'] - (int) $job['hired_count']);
$isOpen    = $job['status'] === 'OPEN';
$paySuffix = match ($job['pay_type']) {
    'HOUR'  => 'per hour',
    'TOTAL' => 'for the opportunity',
    default => 'per day',
};
$applyable = $user['role'] === 'worker' && $myApplication === null && $isOpen && $seatsLeft > 0;
?>
<a class="tiny" href="<?= url($user['role'] === 'employer' ? '/employer/jobs' : '/jobs') ?>">← Back to opportunities</a>

<?php $photo = category_photo((string) $job['category_slug']); ?>
<div class="card-media detail-media tile-<?= e((string) $job['category_slug']) ?>">
    <?php if ($photo !== null): ?>
        <img src="<?= asset($photo . '.webp') ?>" alt="<?= e($job['category_name']) ?> opportunity" width="880" height="587" decoding="async">
    <?php else: ?>
        <span class="tile-glyph hero" aria-hidden="true"><?= icon(category_icon((string) $job['category_slug']), 'icon') ?></span>
    <?php endif; ?>
    <div class="media-badges">
        <span class="chip chip-cat chip-cat-<?= e((string) $job['category_slug']) ?>"><?= e($job['category_name']) ?></span>
        <span class="chip <?= e(status_class((string) $job['status'])) ?>"><?= e(status_label((string) $job['status'])) ?></span>
        <?php if ($seatsLeft > 0 && $isOpen): ?>
            <span class="seats-pill <?= $seatsLeft <= 2 ? 'low' : '' ?>"><?= icon('users') ?> <?= $seatsLeft ?> seat<?= $seatsLeft === 1 ? '' : 's' ?> left</span>
        <?php endif; ?>
    </div>
</div>

<h1><?= e($job['title']) ?></h1>
<p class="muted mt-1">
    <?= e($job['org_name'] ?: 'Host') ?> · <?= e($job['area_name']) ?>, <?= e($job['city']) ?>
</p>

<div class="detail-grid mt-3">
    <div class="stack">
        <section class="card-sw card-pad">
            <h2 class="h-sm mb-2">About this opportunity</h2>
            <p class="mb-0 pre-line"><?= e($job['description']) ?></p>
        </section>

        <section class="card-sw card-pad">
            <h2 class="h-sm mb-3">Opportunity details</h2>
            <div class="detail-list">
                <div class="detail-row">
                    <?= icon('calendar') ?>
                    <div>
                        <div class="k">Dates</div>
                        <div class="v"><?= e(format_range($job['starts_on'], $job['ends_on'])) ?> · <?= days_between($job['starts_on'], $job['ends_on']) ?> day<?= days_between($job['starts_on'], $job['ends_on']) === 1 ? '' : 's' ?></div>
                    </div>
                </div>
                <div class="detail-row">
                    <?= icon('clock') ?>
                    <div>
                        <div class="k">Timing</div>
                        <div class="v"><?= e(format_time($job['start_time'])) ?> – <?= e(format_time($job['end_time'])) ?></div>
                    </div>
                </div>
                <div class="detail-row">
                    <?= icon('pin') ?>
                    <div>
                        <div class="k">Location</div>
                        <div class="v"><?= e($job['address'] !== '' ? $job['address'] : $job['area_name'] . ', ' . $job['city']) ?></div>
                    </div>
                </div>
                <div class="detail-row">
                    <?= icon('users') ?>
                    <div>
                        <div class="k">Seats</div>
                        <div class="v"><?= (int) $job['headcount'] ?> total · <?= (int) $job['hired_count'] ?> hired</div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <aside class="card-sw card-pad pay-card">
        <div class="pay-type">Pay</div>
        <div class="pay-hero"><?= e(money($job['pay_amount_paise'])) ?></div>
        <div class="pay-type"><?= e($paySuffix) ?></div>

        <div class="divider"></div>

        <?php if ($isOwner): ?>
            <a class="btn-sw btn-block" href="<?= url('/employer/jobs/' . rawurlencode((string) $job['public_id']) . '/queue') ?>">
                Review Flight Requests <?= icon('arrow') ?>
            </a>
            <?php if ($job['status'] === 'DRAFT'): ?>
                <form method="post" action="<?= url('/employer/jobs/' . rawurlencode((string) $job['public_id']) . '/publish') ?>" class="mt-2">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn-sw btn-soft btn-block">Publish this opportunity</button>
                </form>
            <?php endif; ?>
            <?php if (in_array($job['status'], ['DRAFT', 'OPEN', 'FILLED'], true)): ?>
                <form method="post" action="<?= url('/employer/jobs/' . rawurlencode((string) $job['public_id']) . '/cancel') ?>"
                      class="mt-2" data-confirm="Cancel this opportunity? Workers will see it as closed.">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn-sw btn-danger-soft btn-block">Cancel opportunity</button>
                </form>
            <?php endif; ?>
        <?php elseif ($user['role'] === 'worker'): ?>
            <?php if ($myApplication !== null): ?>
                <div class="text-center">
                    <span class="chip <?= e(status_class((string) $myApplication['status'])) ?>">
                        <?= e(status_label((string) $myApplication['status'])) ?>
                    </span>
                    <p class="tiny muted mt-2 mb-0">You have already sent a Flight Request for this opportunity.</p>
                    <a class="btn-sw btn-ghost btn-block mt-2" href="<?= url('/applications') ?>">Track Flight Request</a>
                </div>
            <?php elseif ($applyable): ?>
                <form method="post" action="<?= url('/applications') ?>" data-single-submit>
                    <?= csrf_field() ?>
                    <input type="hidden" name="job_public_id" value="<?= e($job['public_id']) ?>">
                    <div class="field">
                        <label class="label" for="note">Message to host <span class="tiny muted">(optional)</span></label>
                        <textarea class="textarea textarea-sm" id="note" name="note" maxlength="500"
                                  placeholder="Tell them why you are a good fit…"></textarea>
                    </div>
                    <button type="submit" class="btn-sw btn-block btn-lg-sw mt-2" data-busy="Sending…">
                        Send Flight Request <?= icon('arrow') ?>
                    </button>
                </form>
            <?php elseif (!$isOpen): ?>
                <p class="tiny muted text-center mb-0">This opportunity is no longer accepting Flight Requests.</p>
                <a class="btn-sw btn-ghost btn-block mt-2" href="<?= url('/jobs') ?>">Find similar work</a>
            <?php else: ?>
                <p class="tiny muted text-center mb-0">All seats for this opportunity are currently filled.</p>
                <a class="btn-sw btn-ghost btn-block mt-2" href="<?= url('/jobs') ?>">Find similar work</a>
            <?php endif; ?>
        <?php else: ?>
            <p class="tiny muted mb-0">Only worker accounts can send a Flight Request. You are signed in as a host.</p>
        <?php endif; ?>

        <p class="tiny muted mt-3 mb-0">
            Flight Requests are reviewed by the host. You will see status changes under My Flight Requests.
        </p>
    </aside>
</div>
