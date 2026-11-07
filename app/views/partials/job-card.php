<?php
/** @var array<string, mixed> $job */
$seatsLeft  = max(0, (int) $job['headcount'] - (int) $job['hired_count']);
$seatsClass = $seatsLeft <= 0 ? 'out' : ($seatsLeft <= 2 ? 'low' : '');
$orgName    = (string) ($job['org_name'] ?: 'Host');
$slug       = (string) $job['category_slug'];
$jobUrl     = url('/jobs/' . rawurlencode((string) $job['public_id']));
$photo      = category_photo($slug);
?>
<article class="job-card">
    <div class="card-media tile-<?= e($slug) ?>">
        <?php if ($photo !== null): ?>
            <img src="<?= asset($photo . '-sm.webp') ?>"
                 srcset="<?= asset($photo . '-sm.webp') ?> 440w, <?= asset($photo . '.webp') ?> 880w"
                 sizes="(max-width: 640px) 92vw, (max-width: 1080px) 45vw, 360px"
                 alt="<?= e($job['category_name']) ?> opportunity" width="880" height="587"
                 loading="lazy" decoding="async" onerror="this.style.display='none'">
        <?php else: ?>
            <span class="tile-glyph" aria-hidden="true"><?= icon(category_icon($slug), 'icon') ?></span>
        <?php endif; ?>

        <div class="media-badges">
            <span class="chip chip-cat chip-cat-<?= e($slug) ?>"><?= e($job['category_name']) ?></span>
            <?php if ($seatsLeft > 0 && $seatsLeft <= 2): ?>
                <span class="badge badge-urgent"><?= icon('alert') ?> <?= $seatsLeft ?> seat<?= $seatsLeft === 1 ? '' : 's' ?> left</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="card-body">
        <h3><a href="<?= $jobUrl ?>"><?= e($job['title']) ?></a></h3>

        <div class="job-host">
            <span class="mark"><?= e(initials($orgName)) ?></span>
            <div class="jh-lines">
                <div class="org"><?= e($orgName) ?></div>
                <div class="area"><?= icon('pin') ?> <?= e($job['area_name']) ?></div>
            </div>
        </div>

        <div class="meta">
            <span><?= icon('calendar') ?> <?= e(format_range($job['starts_on'], $job['ends_on'])) ?></span>
            <span><?= icon('clock') ?> <?= e(format_time($job['start_time'])) ?> – <?= e(format_time($job['end_time'])) ?></span>
        </div>

        <div class="job-card-foot">
            <span class="pay">
                <?= e(money($job['pay_amount_paise'])) ?><span class="unit"> <?= e(pay_unit((string) $job['pay_type'])) ?></span>
            </span>
            <span class="seats-pill <?= $seatsClass ?>">
                <?= icon('users') ?>
                <?= $seatsLeft > 0
                    ? $seatsLeft . ' seat' . ($seatsLeft === 1 ? '' : 's') . ' open'
                    : 'Fully staffed' ?>
            </span>
        </div>

        <a class="btn-sw btn-block btn-sm mt-2" href="<?= $jobUrl ?>">
            View opportunity <?= icon('arrow') ?>
        </a>
    </div>
</article>
