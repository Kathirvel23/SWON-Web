<?php
/** @var list<array<string, mixed>> $jobs */
/** @var array<string, int> $stats */
?>
<div class="row-between mb-3">
    <div>
        <h1>My Opportunities</h1>
        <p class="muted mb-0"><?= (int) $stats['open_jobs'] ?> live · <?= (int) $stats['pending_applicants'] ?> Flight Requests waiting</p>
    </div>
    <a class="btn-sw" href="<?= url('/employer/jobs/new') ?>"><?= icon('plus') ?> Create Opportunity</a>
</div>

<?php if ($jobs === []): ?>
    <?php \App\View::partial('partials/empty', [
        'heading'    => 'No opportunities yet',
        'text'       => 'Create an opportunity with pay, dates and headcount. Flight Requests start arriving immediately.',
        'iconName'   => 'file',
        'actionHref' => '/employer/jobs/new',
        'actionLabel' => 'Create your first opportunity',
    ]); ?>
<?php else: ?>
    <div class="stack-sm">
        <?php foreach ($jobs as $job): ?>
            <?php
            $seatsLeft = max(0, (int) $job['headcount'] - (int) $job['hired_count']);
            $canCancel = in_array($job['status'], ['DRAFT', 'OPEN', 'FILLED'], true);
            ?>
            <div class="card-sw card-pad">
                <div class="row-between">
                    <div class="w-0">
                        <div class="row-gap mb-2">
                            <span class="chip <?= e(status_class((string) $job['status'])) ?>"><?= e(status_label((string) $job['status'])) ?></span>
                            <span class="chip chip-cat chip-cat-<?= e((string) $job['category_slug']) ?>"><?= e($job['category_name']) ?></span>
                            <span class="tiny muted"><?= e(format_range($job['starts_on'], $job['ends_on'])) ?></span>
                        </div>
                        <h3 class="mb-1">
                            <a href="<?= url('/jobs/' . rawurlencode((string) $job['public_id'])) ?>"><?= e($job['title']) ?></a>
                        </h3>
                        <div class="tiny muted">
                            <?= e($job['area_name']) ?> · <?= e(pay_label($job['pay_amount_paise'], $job['pay_type'])) ?>
                            · <?= (int) $job['hired_count'] ?> of <?= (int) $job['headcount'] ?> hired
                            · <?= (int) $job['pending_count'] ?> waiting
                        </div>
                    </div>

                    <div class="end row-gap">
                        <a class="btn-sw btn-sm btn-soft" href="<?= url('/employer/jobs/' . rawurlencode((string) $job['public_id']) . '/queue') ?>">
                            <?= (int) $job['pending_count'] ?> request<?= (int) $job['pending_count'] === 1 ? '' : 's' ?>
                        </a>
                        <?php if ($job['status'] === 'DRAFT'): ?>
                            <form method="post" action="<?= url('/employer/jobs/' . rawurlencode((string) $job['public_id']) . '/publish') ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn-sw btn-sm">Publish</button>
                            </form>
                        <?php endif; ?>
                        <?php if ($canCancel): ?>
                            <form method="post" action="<?= url('/employer/jobs/' . rawurlencode((string) $job['public_id']) . '/cancel') ?>"
                                  data-confirm="Cancel this opportunity? Workers will no longer be able to send Flight Requests.">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn-sw btn-sm btn-danger-soft">Cancel</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
