<?php
/**
 * Landing page — SWON.
 * UI only. Real data comes from DashboardController::splash() ($featured, $openTotal).
 */
$jobs = $featured ?? [];
$total = (int) ($openTotal ?? 0);
?>
<div class="lp">

  <header class="lp-nav">
    <div class="lp-wrap lp-nav-in">
      <a class="lp-logo" href="<?= url('/') ?>" aria-label="SWON home">
        <img src="<?= asset('img/brand/swon-logo.png') ?>" alt="SWON" width="140" height="40">
      </a>
      <nav class="lp-links" aria-label="Main">
        <a class="is-active" href="<?= url('/') ?>">Home</a>
        <a href="#how-it-works">How It Works</a>
        <a href="#organizers">For Organizers</a>
        <a href="#workers">For Workers</a>
        <a href="#features">Features</a>
        <a href="#contact">Contact</a>
      </nav>
      <div class="lp-nav-actions">
        <a class="lp-btn lp-btn-ghost" href="<?= url('/login') ?>">Sign In</a>
        <a class="lp-btn lp-btn-mint" href="<?= url('/login') ?>">Get Started</a>
      </div>
      <button class="lp-burger" type="button" aria-label="Menu" aria-expanded="false" data-lp-menu>
        <span></span><span></span><span></span>
      </button>
    </div>
  </header>

  <!-- HERO -->
  <section class="lp-hero">
    <div class="lp-wrap lp-hero-grid">
      <div class="lp-hero-copy">
        <span class="lp-eyebrow">Temporary Workforce Platform</span>
        <h1>Trusted People.<br>Real Work. <em>Everywhere.</em></h1>
        <p class="lp-lede">
          SWON connects event organizers, businesses and service providers with verified
          temporary workers — quickly, safely and efficiently.
        </p>
        <div class="lp-cta-row">
          <a class="lp-btn lp-btn-mint lp-btn-lg" href="<?= url('/login') ?>">Hire a Crew</a>
          <a class="lp-btn lp-btn-outline lp-btn-lg" href="<?= url('/login') ?>">Find Work</a>
        </div>
        <ul class="lp-trust">
          <li>Verified Workers</li>
          <li>Fast &amp; Easy</li>
          <li>Secure Payments</li>
        </ul>
      </div>

      <div class="lp-showcase" aria-hidden="false">
        <div class="lp-blob lp-blob-a"></div>
        <div class="lp-blob lp-blob-b"></div>

        <figure class="lp-photo">
          <img src="<?= asset('img/jobs/event.webp') ?>" alt="Event crew at work" width="520" height="620" loading="lazy">
        </figure>

        <div class="lp-phone lp-phone-worker">
          <div class="lp-phone-bar"></div>
          <div class="lp-phone-head">
            <span class="lp-phone-kicker">Available now</span>
            <strong><?= $total ?> open</strong>
          </div>
          <?php if (empty($jobs)): ?>
            <p class="lp-phone-empty">New work is posted daily.</p>
          <?php else: ?>
            <?php foreach (array_slice($jobs, 0, 3) as $j): ?>
              <div class="lp-job">
                <div class="lp-job-main">
                  <span class="lp-job-title"><?= e($j['title']) ?></span>
                  <span class="lp-job-meta"><?= e($j['area_name']) ?> · <?= e($j['category_name']) ?></span>
                </div>
                <span class="lp-job-pay"><?= e(money($j['pay_amount_paise'])) ?></span>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <div class="lp-phone lp-phone-crew">
          <div class="lp-phone-bar"></div>
          <div class="lp-phone-head">
            <span class="lp-phone-kicker">Crew</span>
            <strong>Crew management</strong>
          </div>
          <?php foreach (array_slice($jobs, 0, 2) as $j): ?>
            <?php
              $need = (int) $j['headcount'];
              $have = (int) $j['hired_count'];
              $pct  = $need > 0 ? (int) round($have / $need * 100) : 0;
            ?>
            <div class="lp-crew">
              <span class="lp-crew-title"><?= e($j['title']) ?></span>
              <div class="lp-crew-bar"><i style="width: <?= $pct ?>%"></i></div>
              <span class="lp-crew-num"><?= $have ?>/<?= $need ?> filled</span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- BENEFITS -->
  <section class="lp-benefits" id="features">
    <div class="lp-wrap lp-benefit-grid">
      <article class="lp-benefit">
        <span class="lp-ico">◈</span>
        <h3>For Event Organizers</h3>
        <p>Book verified crews for events, setups, logistics and more.</p>
      </article>
      <article class="lp-benefit">
        <span class="lp-ico">▤</span>
        <h3>For Businesses</h3>
        <p>Hire temporary staff for your operations and projects.</p>
      </article>
      <article class="lp-benefit" id="workers">
        <span class="lp-ico">◇</span>
        <h3>For Workers</h3>
        <p>Find flexible work opportunities near you.</p>
      </article>
      <article class="lp-benefit">
        <span class="lp-ico">◉</span>
        <h3>Built for Trust</h3>
        <p>Verified profiles, secure payments and transparent processes.</p>
      </article>
    </div>
  </section>

  <!-- HOW IT WORKS -->
  <section class="lp-how" id="how-it-works">
    <div class="lp-wrap">
      <span class="lp-eyebrow lp-eyebrow-c">HOW IT WORKS</span>
      <h2 class="lp-h2">Get started in 3 simple steps</h2>
      <p class="lp-sub">Whether you’re hiring or looking for work, SWON makes it easy and hassle-free.</p>
      <ol class="lp-steps">
        <li><span class="lp-step-num">01</span><h3>Sign Up</h3><p>Create your account in minutes.</p></li>
        <li><span class="lp-step-num">02</span><h3>Find or Post</h3><p>Browse opportunities or post your workforce requirement.</p></li>
        <li><span class="lp-step-num">03</span><h3>Get to Work</h3><p>Connect, confirm and start working.</p></li>
      </ol>
    </div>
  </section>

  <!-- BANNERS -->
  <section class="lp-banners" id="organizers">
    <div class="lp-wrap lp-banner-grid">
      <article class="lp-banner lp-banner-dark">
        <div class="lp-banner-copy">
          <h2>Looking to Hire a Crew?</h2>
          <p>Get skilled and verified workers for your events, setups, logistics and more.</p>
          <a class="lp-btn lp-btn-mint" href="<?= url('/login') ?>">Post a Requirement</a>
        </div>
        <img src="<?= asset('img/jobs/construction.webp') ?>" alt="Crew at work" loading="lazy">
      </article>
      <article class="lp-banner lp-banner-mint">
        <div class="lp-banner-copy">
          <h2>Looking for Work?</h2>
          <p>Find flexible, well-paying opportunities near your location.</p>
          <a class="lp-btn lp-btn-navy" href="<?= url('/login') ?>">Explore Jobs</a>
        </div>
        <img src="<?= asset('img/jobs/hospitality.webp') ?>" alt="Worker on a shift" loading="lazy">
      </article>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="lp-footer" id="contact">
    <div class="lp-wrap lp-footer-grid">
      <div>
        <img class="lp-footer-logo" src="<?= asset('img/brand/swon-wordmark-light.png') ?>" alt="SWON" width="120" height="34">
        <p class="lp-footer-copy">Temporary workforce marketplace for Coimbatore and Tiruppur.</p>
      </div>
      <nav aria-label="Footer">
        <a href="<?= url('/login') ?>">Sign In</a>
        <a href="<?= url('/jobs') ?>">Browse Work</a>
        <a href="#how-it-works">How It Works</a>
        <a href="#contact">Contact</a>
      </nav>
      <div class="lp-footer-meta">
        <span>Coimbatore · Tiruppur</span>
        <span>© <?= date('Y') ?> SWON</span>
      </div>
    </div>
  </footer>
</div>

<script>
(function () {
  var b = document.querySelector('[data-lp-menu]');
  if (!b) return;
  b.addEventListener('click', function () {
    var open = document.body.classList.toggle('lp-nav-open');
    b.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
})();
</script>
