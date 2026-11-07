<?php
/** @var array<string, mixed> $job */
/** @var list<array<string, mixed>> $applicants */
$availabilityLabel = static fn (string $key): string => match ($key) {
    'FULL_TIME' => 'Full time',
    'PART_TIME' => 'Part time',
    'WEEKENDS'  => 'Weekends',
    default     => 'Flexible',
};
$decidable = ['APPLIED', 'SHORTLISTED', 'WAITLISTED'];
?>
<a class="tiny" href="<?= url('/employer/jobs') ?>">← Back to my opportunities</a>

<div class="card-sw card-pad mt-2 mb-3">
    <div class="row-between">
        <div>
            <div class="row-gap mb-2">
                <span class="chip <?= e(status_class((string) $job['status'])) ?>"><?= e(status_label((string) $job['status'])) ?></span>
                <span class="chip chip-cat"><?= e($job['category_name']) ?></span>
            </div>
            <h1 class="h-lg mb-1"><?= e($job['title']) ?></h1>
            <div class="tiny muted">
                <?= e($job['area_name']) ?> · <?= e(pay_label($job['pay_amount_paise'], $job['pay_type'])) ?>
                · <?= e(format_range($job['starts_on'], $job['ends_on'])) ?>
                · <?= e(format_time($job['start_time'])) ?>–<?= e(format_time($job['end_time'])) ?>
            </div>
        </div>
        <div class="end">
            <div class="stat minw-150 text-right">
                <div class="stat-label">Crew hired</div>
                <div class="stat-value compact"><?= (int) $job['hired_count'] ?> / <?= (int) $job['headcount'] ?></div>
            </div>
            <a class="btn-sw btn-sm btn-ghost" href="<?= url('/jobs/' . rawurlencode((string) $job['public_id'])) ?>">View opportunity</a>
        </div>
    </div>
</div>

<div class="row-between mb-2">
    <h2 class="h-md">Flight Requests (<?= count($applicants) ?>)</h2>
    <span class="tiny muted">Hiring is seat-safe. Status changes update the opportunity automatically.</span>
</div>

<?php if ($applicants === []): ?>
    <?php \App\View::partial('partials/empty', [
        'heading'    => 'No Flight Requests yet',
        'text'       => 'Share the opportunity or check back later. New Flight Requests appear here immediately.',
        'iconName'   => 'inbox',
        'actionHref' => '/employer/jobs',
        'actionLabel' => 'Back to my opportunities',
    ]); ?>
<?php else: ?>
    <div class="stack-sm">
        <?php foreach ($applicants as $applicant): ?>
            <?php
            $status = (string) $applicant['status'];
            $actions = [];
            if ($status === 'HIRED') {
                $actions[] = ['action' => 'release', 'label' => 'Release', 'style' => 'btn-danger-soft'];
            } else {
                if (in_array($status, ['APPLIED', 'WAITLISTED'], true)) {
                    $actions[] = ['action' => 'shortlist', 'label' => 'Shortlist', 'style' => 'btn-ghost'];
                }
                if (in_array($status, ['APPLIED', 'SHORTLISTED'], true)) {
                    $actions[] = ['action' => 'waitlist', 'label' => 'Waitlist', 'style' => 'btn-ghost'];
                }
                if (in_array($status, $decidable, true)) {
                    $actions[] = ['action' => 'hire', 'label' => 'Hire', 'style' => ''];
                    $actions[] = ['action' => 'reject', 'label' => 'Reject', 'style' => 'btn-danger-soft'];
                }
            }
            $skills = array_filter(array_map('trim', explode(',', (string) ($applicant['skills'] ?? ''))));
            $applicantName = (string) ($applicant['full_name'] ?: 'Worker');
            ?>
            <article class="applicant">
                <span class="avatar avatar-lg" aria-hidden="true"><?= e(initials($applicantName)) ?></span>

                <div class="body">
                    <div class="row-gap">
                        <strong><?= e($applicantName) ?></strong>
                        <span class="chip <?= e(status_class($status)) ?>"><?= e(status_label($status)) ?></span>
                        <span class="tiny muted">Sent <?= e(format_date((string) $applicant['created_at'])) ?></span>
                    </div>
                    <div class="tiny muted mt-1">
                        <?= e($applicant['area_name'] ?: 'Area not set') ?>
                        · <?= e($availabilityLabel((string) ($applicant['availability'] ?? 'FLEXIBLE'))) ?>
                        · <?= e(mask_phone((string) ($applicant['phone'] ?? ''))) ?>
                    </div>

                    <?php if ($skills !== []): ?>
                        <div class="skills">
                            <?php foreach ($skills as $skill): ?>
                                <span class="skill-pill"><?= e($skill) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (trim((string) ($applicant['note'] ?? '')) !== ''): ?>
                        <div class="note-box">“<?= e($applicant['note']) ?>”</div>
                    <?php endif; ?>
                </div>

                <div class="actions" role="group" aria-label="Decision for <?= e($applicantName) ?>">
                    <?php foreach ($actions as $action): ?>
                        <form method="post" action="<?= url('/applications/' . rawurlencode((string) $applicant['public_id']) . '/decision') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="<?= e($action['action']) ?>">
                            <button type="submit" class="btn-sw btn-sm <?= e($action['style']) ?>"><?= e($action['label']) ?></button>
                        </form>
                    <?php endforeach; ?>

                    <?php if ($status === 'RELEASED' || $status === 'WITHDRAWN' || $status === 'REJECTED'): ?>
                        <span class="tiny muted">No further actions</span>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
