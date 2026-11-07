<?php
/** @var list<array<string, mixed>> $applications */
$canWithdraw = ['APPLIED', 'SHORTLISTED', 'WAITLISTED'];
?>
<div class="row-between mb-3">
    <div>
        <h1>My Flight Requests</h1>
        <p class="muted mb-0"><?= count($applications) ?> Flight Request<?= count($applications) === 1 ? '' : 's' ?> in total.</p>
    </div>
    <a class="btn-sw btn-sm" href="<?= url('/jobs') ?>">Find more work <?= icon('arrow') ?></a>
</div>

<?php if ($applications === []): ?>
    <?php \App\View::partial('partials/empty', [
        'heading'    => 'No Flight Requests yet',
        'text'       => 'Browse open opportunities and send a Flight Request — hosts usually respond the same day.',
        'iconName'   => 'file',
        'actionHref' => '/jobs',
        'actionLabel' => 'Browse opportunities',
    ]); ?>
<?php else: ?>
    <div class="stack-sm">
        <?php foreach ($applications as $application): ?>
            <div class="card-sw card-pad">
                <div class="row-between">
                    <div class="w-0">
                        <div class="row-gap mb-2">
                            <span class="chip <?= e(status_class((string) $application['status'])) ?>">
                                <?= e(status_label((string) $application['status'])) ?>
                            </span>
                            <span class="tiny muted">Sent <?= e(format_date($application['created_at'])) ?></span>
                        </div>
                        <h3 class="mb-1">
                            <a href="<?= url('/jobs/' . rawurlencode((string) $application['job_public_id'])) ?>">
                                <?= e($application['title']) ?>
                            </a>
                        </h3>
                        <div class="tiny muted">
                            <?= e($application['org_name'] ?: 'Host') ?> · <?= e($application['area_name']) ?>
                            · <?= e(pay_label($application['pay_amount_paise'], $application['pay_type'])) ?>
                            · <?= e(format_range($application['starts_on'], $application['ends_on'])) ?>
                        </div>
                    </div>

                    <div class="end row-gap">
                        <a class="btn-sw btn-sm btn-ghost" href="<?= url('/jobs/' . rawurlencode((string) $application['job_public_id'])) ?>">View opportunity</a>
                        <?php if (in_array($application['status'], $canWithdraw, true)): ?>
                            <form method="post" action="<?= url('/applications/' . rawurlencode((string) $application['public_id']) . '/withdraw') ?>"
                                  data-confirm="Withdraw this Flight Request? You cannot re-apply to the same opportunity.">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn-sw btn-sm btn-danger-soft">Withdraw</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (trim((string) $application['note']) !== ''): ?>
                    <div class="note-box"><?= e($application['note']) ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
