<?php
/**
 * Preview landing page — /preview-home
 * Self-contained: pairs with layouts/preview.php and css/preview.css only.
 *
 * @var list<array<string, mixed>> $featured
 * @var int $openTotal
 */

$lead       = $featured[0] ?? null;
$leadHired  = $lead ? (int) $lead['hired_count'] : 0;
$leadHead   = $lead ? max(1, (int) $lead['headcount']) : 1;
$leadLeft   = max(0, $leadHead - $leadHired);
$openCount  = (int) ($openTotal ?? 0);
?>
<section class="pv-hero" id="home">
    <div class="pv-container pv-hero-inner">
        <div class="pv-hero-copy">
            <span class="pv-eyebrow">Temporary Workforce Platform</span>

            <h1>Trusted People. Real Work.<br><span class="pv-mint">Everywhere</span>.</h1>

            <p class="pv-lede">
                SWON connects event organizers, businesses and service providers with verified
                temporary workers — quickly, safely and efficiently.
            </p>

            <div class="pv-hero-actions">
                <a class="pv-btn pv-btn-primary pv-btn-lg" href="<?= url('/login') ?>">
                    Hire a Crew <?= icon('arrow', 'pv-icon') ?>
                </a>
                <a class="pv-btn pv-btn-ghost pv-btn-lg" href="<?= url('/login') ?>">Find Work</a>
            </div>

            <ul class="pv-trust">
                <li><?= icon('shield', 'pv-icon') ?> Verified Workers</li>
                <li><?= icon('clock', 'pv-icon') ?> Fast &amp; Easy</li>
                <li><?= icon('check', 'pv-icon') ?> Pay Agreed Up Front</li>
            </ul>
        </div>

        <div class="pv-showcase">
            <span class="pv-shape pv-shape-a" aria-hidden="true"></span>
            <span class="pv-shape pv-shape-b" aria-hidden="true"></span>

            <div class="pv-phone">
                <span class="pv-phone-notch" aria-hidden="true"></span>
                <div class="pv-phone-screen">
                    <div class="pv-app-bar">
                        <span class="pv-app-title">Open roles</span>
                        <span class="pv-app-count"><?= $openCount ?> live</span>
                    </div>

                    <div class="pv-app-body">
                        <?php if (empty($featured)): ?>
                            <p class="pv-app-empty">
                                No open opportunities are listed right now. New work is posted daily.
                            </p>
                        <?php else: ?>
                            <?php foreach ($featured as $job): ?>
                                <?php
                                $seatsTotal = max(1, (int) $job['headcount']);
                                $seatsFilled = min($seatsTotal, (int) $job['hired_count']);
                                ?>
                                <article class="pv-app-job">
                                    <div class="pv-app-job-top">
                                        <span class="pv-app-chip"><?= e($job['category_name']) ?></span>
                                        <span class="pv-app-pay">
                                            <?= e(money($job['pay_amount_paise'])) ?>
                                            <small><?= e(pay_unit((string) $job['pay_type'])) ?></small>
                                        </span>
                                    </div>
                                    <p class="pv-app-job-title"><?= e($job['title']) ?></p>
                                    <p class="pv-app-meta">
                                        <span><?= icon('pin', 'pv-icon') ?><?= e($job['area_name']) ?>, <?= e($job['city']) ?></span>
                                        <span><?= icon('calendar', 'pv-icon') ?><?= e(format_date($job['starts_on'])) ?></span>
                                    </p>
                                    <p class="pv-app-seats">
                                        <?= $seatsFilled ?> of <?= $seatsTotal ?> hired
                                        · <?= max(0, $seatsTotal - $seatsFilled) ?> seats left
                                    </p>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($lead): ?>
                <div class="pv-crew-card">
                    <p class="pv-crew-label"><?= icon('users', 'pv-icon') ?> Crew progress</p>
                    <p class="pv-crew-title"><?= e($lead['title']) ?></p>
                    <progress class="pv-crew-bar" value="<?= $leadHired ?>" max="<?= $leadHead ?>">
                        <?= $leadHired ?> of <?= $leadHead ?> hired
                    </progress>
                    <div class="pv-crew-foot">
                        <strong><?= $leadHired ?> / <?= $leadHead ?> hired</strong>
                        <span><?= $leadLeft ?> seat<?= $leadLeft === 1 ? '' : 's' ?> left</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="pv-section pv-section-canvas" id="benefits">
    <div class="pv-container">
        <div class="pv-section-head" data-reveal>
            <p class="pv-kicker">Who it is for</p>
            <h2>One platform, four kinds of workday.</h2>
            <p>
                Hiring a crew and looking for a shift are two sides of the same problem:
                nobody should have to guess the rate, the date or the headcount.
            </p>
        </div>

        <div class="pv-benefits">
            <article class="pv-benefit" data-reveal>
                <span class="pv-icontile"><?= icon('sparkles', 'pv-icon') ?></span>
                <h3>For Event Organizers</h3>
                <p>
                    Staff a wedding, a conference or an expo with the number of people the day
                    actually needs — one post, one queue, no phone tree.
                </p>
            </article>

            <article class="pv-benefit" data-reveal>
                <span class="pv-icontile"><?= icon('factory', 'pv-icon') ?></span>
                <h3>For Businesses</h3>
                <p>
                    Cover the seasonal peak, the dispatch day or the weekend rush without adding
                    permanent payroll. Set the headcount and watch it fill.
                </p>
            </article>

            <article class="pv-benefit" data-reveal>
                <span class="pv-icontile"><?= icon('users', 'pv-icon') ?></span>
                <h3>For Workers</h3>
                <p>
                    See the rate, the area and the dates before you apply. Keep every request in
                    one place and know where you stand without chasing anyone.
                </p>
            </article>

            <article class="pv-benefit" data-reveal>
                <span class="pv-icontile"><?= icon('shield', 'pv-icon') ?></span>
                <h3>Built for Trust</h3>
                <p>
                    Both sides sign in with a one-time code to a real phone number, and every post
                    carries the same facts in the same order.
                </p>
            </article>
        </div>
    </div>
</section>

<section class="pv-section" id="how-it-works">
    <div class="pv-container">
        <div class="pv-section-head" data-reveal>
            <p class="pv-kicker">How it works</p>
            <h2>Three steps, start to shift.</h2>
            <p>No interviews to schedule and no forms to print — the work is agreed inside the post.</p>
        </div>

        <ol class="pv-steps">
            <li class="pv-step" data-reveal>
                <span class="pv-step-num">01</span>
                <h3>Sign Up</h3>
                <p>
                    Sign in with your phone number and a one-time code, then choose whether you
                    are hiring or looking for work. That sets your dashboard.
                </p>
            </li>

            <li class="pv-step" data-reveal>
                <span class="pv-step-num">02</span>
                <h3>Find or Post</h3>
                <p>
                    Organizers post a requirement with area, dates, shift times, rate and headcount.
                    Workers browse open opportunities and send a request.
                </p>
            </li>

            <li class="pv-step" data-reveal>
                <span class="pv-step-num">03</span>
                <h3>Get to Work</h3>
                <p>
                    Organizers review the queue and shortlist, waitlist or hire. Workers see the
                    decision in their account and the crew count fills up.
                </p>
            </li>
        </ol>
    </div>
</section>

<section class="pv-section" id="for-organizers">
    <div class="pv-container">
        <div class="pv-journeys">
            <article class="pv-journey pv-journey-dark pv-dark" data-reveal>
                <span class="pv-journey-label">For Organizers</span>
                <h3>Build Your Team. Get Work Done.</h3>
                <p>
                    Find reliable temporary workers for your events, operations and projects.
                </p>
                <ul class="pv-journey-list">
                    <li><?= icon('check', 'pv-icon') ?> One post per requirement, with headcount</li>
                    <li><?= icon('check', 'pv-icon') ?> A queue you can shortlist, waitlist and hire from</li>
                    <li><?= icon('check', 'pv-icon') ?> The rate fixed before anyone applies</li>
                </ul>
                <div class="pv-journey-cta">
                    <a class="pv-btn pv-btn-onnavy" href="<?= url('/login') ?>">
                        Post a Requirement <?= icon('arrow', 'pv-icon') ?>
                    </a>
                </div>
            </article>

            <article class="pv-journey pv-journey-tint" id="for-workers" data-reveal>
                <span class="pv-journey-label">For Workers</span>
                <h3>Your Next Opportunity Starts Here.</h3>
                <p>
                    Discover flexible work opportunities and connect with businesses looking for
                    people like you.
                </p>
                <ul class="pv-journey-list">
                    <li><?= icon('check', 'pv-icon') ?> Rate, area and dates before you apply</li>
                    <li><?= icon('check', 'pv-icon') ?> Every request tracked in one place</li>
                    <li><?= icon('check', 'pv-icon') ?> Day work, hourly work or a fixed total</li>
                </ul>
                <div class="pv-journey-cta">
                    <a class="pv-btn pv-btn-primary" href="<?= url('/login') ?>">
                        Explore Jobs <?= icon('arrow', 'pv-icon') ?>
                    </a>
                </div>
            </article>
        </div>
    </div>
</section>

<section class="pv-section pv-section-canvas" id="features">
    <div class="pv-container">
        <div class="pv-section-head" data-reveal>
            <p class="pv-kicker">Platform features</p>
            <h2>Everything the shift needs, in the post.</h2>
            <p>
                SWON is deliberately plain: a structured requirement, a queue of people, and a
                count that updates as you hire.
            </p>
        </div>

        <div class="pv-features">
            <article class="pv-feature" data-reveal>
                <span class="pv-icontile"><?= icon('file', 'pv-icon') ?></span>
                <h3>Structured requirements</h3>
                <p>Category, area, dates, shift times, rate and headcount — one post, the same fields for everyone.</p>
            </article>

            <article class="pv-feature" data-reveal>
                <span class="pv-icontile"><?= icon('inbox', 'pv-icon') ?></span>
                <h3>A visible hiring queue</h3>
                <p>Requests per post with a status both sides can read: sent, shortlisted, waitlisted, hired.</p>
            </article>

            <article class="pv-feature" data-reveal>
                <span class="pv-icontile"><?= icon('users', 'pv-icon') ?></span>
                <h3>Headcount that updates</h3>
                <p>Every hire moves the count on the post, so you always know how many seats are still open.</p>
            </article>

            <article class="pv-feature" data-reveal>
                <span class="pv-icontile"><?= icon('pin', 'pv-icon') ?></span>
                <h3>Named areas, not a vague radius</h3>
                <p>Posts are pinned to a Coimbatore or Tiruppur area, so the commute is known before anyone accepts.</p>
            </article>

            <article class="pv-feature" data-reveal>
                <span class="pv-icontile"><?= icon('calendar', 'pv-icon') ?></span>
                <h3>Dates and shift times</h3>
                <p>Start and end dates plus start and end times are part of the post — not a message you have to send.</p>
            </article>

            <article class="pv-feature" data-reveal>
                <span class="pv-icontile"><?= icon('download', 'pv-icon') ?></span>
                <h3>Works on the phone you have</h3>
                <p>SWON runs in the browser and can be added to your home screen. No app store download.</p>
            </article>
        </div>
    </div>
</section>

<section class="pv-section" id="trust">
    <div class="pv-container pv-split">
        <div data-reveal>
            <p class="pv-kicker">Verification &amp; trust</p>
            <h2>Trust is built from what both sides can see.</h2>
            <p>
                SWON does not ask you to take a stranger's word for it. Every account is tied to a
                phone number, every post is structured the same way, and organizers and workers
                read the same status.
            </p>

            <ul class="pv-checks">
                <li>
                    <span class="pv-check-mark"><?= icon('check', 'pv-icon') ?></span>
                    <span><strong>Phone-verified sign-in.</strong> A one-time code goes to the number on the account, every time.</span>
                </li>
                <li>
                    <span class="pv-check-mark"><?= icon('check', 'pv-icon') ?></span>
                    <span><strong>A named organization.</strong> Organizer accounts post under an organization name, not a blank profile.</span>
                </li>
                <li>
                    <span class="pv-check-mark"><?= icon('check', 'pv-icon') ?></span>
                    <span><strong>Masked contact details.</strong> The hiring queue shows applicants with masked phone numbers, not an open contact list.</span>
                </li>
                <li>
                    <span class="pv-check-mark"><?= icon('check', 'pv-icon') ?></span>
                    <span><strong>One set of facts.</strong> Rate, unit, dates, times, area and headcount live in the post itself.</span>
                </li>
            </ul>
        </div>

        <div data-reveal>
            <?php if ($lead): ?>
                <div class="pv-card">
                    <div class="pv-card-head">
                        <span>Anatomy of a live post</span>
                        <span><?= e(status_label((string) $lead['status'])) ?></span>
                    </div>
                    <div class="pv-anatomy">
                        <div class="pv-anatomy-row">
                            <span class="pv-anatomy-key"><?= icon('briefcase', 'pv-icon') ?> Role</span>
                            <span class="pv-anatomy-val"><?= e($lead['title']) ?></span>
                        </div>
                        <div class="pv-anatomy-row">
                            <span class="pv-anatomy-key"><?= icon('grid', 'pv-icon') ?> Category</span>
                            <span class="pv-anatomy-val"><?= e($lead['category_name']) ?></span>
                        </div>
                        <div class="pv-anatomy-row">
                            <span class="pv-anatomy-key"><?= icon('pin', 'pv-icon') ?> Area</span>
                            <span class="pv-anatomy-val"><?= e($lead['area_name']) ?>, <?= e($lead['city']) ?></span>
                        </div>
                        <div class="pv-anatomy-row">
                            <span class="pv-anatomy-key"><?= icon('calendar', 'pv-icon') ?> Dates</span>
                            <span class="pv-anatomy-val"><?= e(format_range($lead['starts_on'], $lead['ends_on'])) ?></span>
                        </div>
                        <div class="pv-anatomy-row">
                            <span class="pv-anatomy-key"><?= icon('clock', 'pv-icon') ?> Shift</span>
                            <span class="pv-anatomy-val"><?= e(format_time($lead['start_time'])) ?> – <?= e(format_time($lead['end_time'])) ?></span>
                        </div>
                        <div class="pv-anatomy-row">
                            <span class="pv-anatomy-key"><?= icon('tag', 'pv-icon') ?> Rate</span>
                            <span class="pv-anatomy-val"><?= e(pay_label($lead['pay_amount_paise'], (string) $lead['pay_type'])) ?></span>
                        </div>
                        <div class="pv-anatomy-row">
                            <span class="pv-anatomy-key"><?= icon('users', 'pv-icon') ?> Crew</span>
                            <span class="pv-anatomy-val"><?= $leadHired ?> of <?= $leadHead ?> hired</span>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="pv-card">
                    <div class="pv-card-head"><span>Anatomy of a post</span></div>
                    <p class="pv-pay-source" style="color:var(--muted)">
                        Every post carries a role, category, area, dates, shift times, rate and headcount.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="pv-section pv-section-mint" id="pay">
    <div class="pv-container pv-split">
        <div data-reveal>
            <p class="pv-kicker">Pay</p>
            <h2>Pay agreed before anyone shows up.</h2>
            <p>
                The rate is part of the post, not a conversation at the gate. Organizers pick an
                amount and a unit — per day, per hour or a fixed total — and every worker sees the
                same figure before sending a request.
            </p>
            <p>
                SWON does not hold or process the payment itself. The amount agreed in the post is
                settled directly between the organizer and the worker; the platform's job is to make
                sure nobody arrives without knowing it.
            </p>

            <ul class="pv-checks">
                <li>
                    <span class="pv-check-mark"><?= icon('check', 'pv-icon') ?></span>
                    <span>Rate and unit shown on the opportunity card, in rupees.</span>
                </li>
                <li>
                    <span class="pv-check-mark"><?= icon('check', 'pv-icon') ?></span>
                    <span>The same rate is visible to every applicant — no per-person haggling.</span>
                </li>
                <li>
                    <span class="pv-check-mark"><?= icon('check', 'pv-icon') ?></span>
                    <span>Paise are preserved when a rate has them: ₹450, ₹450.50, ₹1,250.75.</span>
                </li>
            </ul>
        </div>

        <div data-reveal>
            <div class="pv-card pv-card-dark pv-dark">
                <div class="pv-card-head">
                    <span>Rate in a live post</span>
                    <span><?= e(format_date($lead['starts_on'] ?? null)) ?></span>
                </div>

                <?php if ($lead): ?>
                    <p class="pv-pay-figure">
                        <?= e(money($lead['pay_amount_paise'])) ?>
                        <small><?= e(pay_unit((string) $lead['pay_type'])) ?></small>
                    </p>
                    <p class="pv-pay-source"><?= e($lead['title']) ?> · <?= e($lead['area_name']) ?>, <?= e($lead['city']) ?></p>

                    <div class="pv-pay-divider"></div>

                    <ul class="pv-pay-list">
                        <li><?= icon('check', 'pv-icon') ?> Fixed in the post before the first request</li>
                        <li><?= icon('check', 'pv-icon') ?> Shift <?= e(format_time($lead['start_time'])) ?> – <?= e(format_time($lead['end_time'])) ?></li>
                        <li><?= icon('check', 'pv-icon') ?> Settled between organizer and worker</li>
                    </ul>
                <?php else: ?>
                    <p class="pv-pay-figure">Rate<small>set by the organizer</small></p>
                    <p class="pv-pay-source">No open post right now — new work is posted daily.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="pv-section" id="faq">
    <div class="pv-container">
        <div class="pv-faq-wrap">
            <div class="pv-section-head pv-section-head-center" data-reveal>
                <p class="pv-kicker">FAQ</p>
                <h2>Questions worth asking.</h2>
                <p>Straight answers about what SWON does today — and what it deliberately leaves to you.</p>
            </div>

            <div class="pv-faq">
                <details>
                    <summary>Who can post work on SWON?</summary>
                    <p>
                        Event organizers, businesses and service providers with an organizer account.
                        You set the role, area, dates, shift times, rate and the number of people you
                        need, then publish the opportunity.
                    </p>
                </details>

                <details>
                    <summary>How does hiring actually work?</summary>
                    <p>
                        Requests land in a queue for each post. You can shortlist, waitlist or hire
                        each person, and the crew count on the post updates as hires are confirmed.
                        Workers see the same status in their own account.
                    </p>
                </details>

                <details>
                    <summary>What does it cost?</summary>
                    <p>
                        Creating an account and sending a request are free. Organizer pricing is not
                        published on this page, so it isn't quoted here.
                    </p>
                </details>

                <details>
                    <summary>Does SWON process payments?</summary>
                    <p>
                        No. SWON is where the work is agreed and the crew is confirmed. The rate in
                        the post is settled directly between the organizer and the worker — the
                        platform's contribution is that the amount is visible and fixed up front.
                    </p>
                </details>

                <details>
                    <summary>Where does SWON operate?</summary>
                    <p>
                        Coimbatore and Tiruppur. Every post is pinned to a named area in one of those
                        cities, so the commute is known before anyone accepts a shift.
                    </p>
                </details>

                <details>
                    <summary>Can I use it on my phone?</summary>
                    <p>
                        Yes. SWON runs in the browser on a phone and can be added to your home screen,
                        so there's no app store download and no update to install.
                    </p>
                </details>

                <details>
                    <summary>How do I back out of a shift?</summary>
                    <p>
                        Workers can withdraw a request from their applications page, and organizers
                        can cancel a post. Either way, the other side sees the change rather than
                        finding out on the day.
                    </p>
                </details>
            </div>
        </div>
    </div>
</section>

<section class="pv-cta pv-dark">
    <div class="pv-container pv-cta-inner">
        <div>
            <h2>Ready when you are.</h2>
            <p>
                Post a requirement in a couple of minutes, or find work that fits the days you can
                give. Same platform, same facts on both sides.
            </p>
        </div>
        <div class="pv-cta-actions">
            <a class="pv-btn pv-btn-mint" href="<?= url('/login') ?>">
                Hire a Crew <?= icon('arrow', 'pv-icon') ?>
            </a>
            <a class="pv-btn pv-btn-outline-light" href="<?= url('/login') ?>">Find Work</a>
        </div>
    </div>
</section>

<section class="pv-contact" id="contact">
    <div class="pv-container pv-contact-inner">
        <div>
            <h2>Contact</h2>
            <p>
                There's no contact form here. SWON runs on phone sign-in: start with your number and
                you're in the right place, whether you're hiring or looking for work.
            </p>
        </div>
        <div class="pv-cta-actions">
            <a class="pv-btn pv-btn-ghost pv-btn-sm" href="<?= url('/login') ?>">Sign In</a>
            <a class="pv-btn pv-btn-primary pv-btn-sm" href="<?= url('/login') ?>">Create an Account</a>
        </div>
    </div>
</section>
