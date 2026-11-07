<?php
/**
 * SWON — single-screen Android download page.
 * Real values only: version from the app (1.0.0), APK size from the file on disk.
 */
$apkPath = __DIR__ . '/../../../public/downloads/swon-android.apk';
$apkBytes = is_file($apkPath) ? filesize($apkPath) : null;
$apkSize  = $apkBytes ? round($apkBytes / 1048576, 1) . ' MB' : 'APK';
?>
<div class="dl">
  <header class="dl-top">
    <a class="dl-brand" href="<?= url('/') ?>"><img src="<?= asset('img/brand/swon-logo.png') ?>" alt="SWON"></a>
    <span class="dl-android"><span class="dl-android-dot"></span>Android app</span>
  </header>

  <main class="dl-main">
    <div class="dl-copy">
      <span class="dl-pill">Temporary Workforce Platform</span>
      <h1>Find Crews.<br>Get Work.<br><em>On the Go.</em></h1>
      <p class="dl-lede">Hire reliable temporary workers or discover flexible work opportunities — all in one app.</p>

      <ul class="dl-benefits">
        <li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l7 3v6c0 4.4-3 8-7 9-4-1-7-4.6-7-9V6l7-3z"/><path d="M9 12l2 2 4-4"/></svg>Verified Workers</li>
        <li><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l4 2"/></svg>Quick Hiring</li>
        <li><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 018 0v3"/></svg>Secure Payments</li>
      </ul>

      <a class="dl-btn" href="<?= url('downloads/swon-android.apk') ?>" download>
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4v10"/><path d="M8 10l4 4 4-4"/><path d="M5 19h14"/></svg>
        Download APK
      </a>
      <p class="dl-meta">
        Android 8.0 and above<span>·</span>Version 1.0.0<span>·</span><?= e($apkSize) ?>
      </p>

      <div class="dl-cats">
        <span class="dl-cat">Events</span>
        <span class="dl-cat">Setups</span>
        <span class="dl-cat">Logistics</span>
        <span class="dl-cat">Cleaning</span>
        <span class="dl-cat">More</span>
      </div>
    </div>

    <div class="dl-showcase">
      <span class="dl-ribbon dl-ribbon-a"></span>
      <span class="dl-ribbon dl-ribbon-b"></span>
      <img class="dl-photo" src="<?= asset('img/jobs/event.webp') ?>" alt="Event crew at work">

      <div class="dl-phone dl-phone-back">
        <i class="dl-notch"></i>
        <span class="dl-k">Crew</span>
        <strong>Fill your shift</strong>
        <div class="dl-row"><div style="width:100%"><div class="dl-t">Banquet Stewards</div><div class="dl-bar"><span style="width:80%"></span></div><div class="dl-m">4 of 5 filled</div></div></div>
        <div class="dl-row"><div style="width:100%"><div class="dl-t">Event Crew</div><div class="dl-bar"><span style="width:60%"></span></div><div class="dl-m">6 of 10 filled</div></div></div>
        <span class="dl-mini">Confirm crew</span>
      </div>

      <div class="dl-phone dl-phone-front">
        <i class="dl-notch"></i>
        <span class="dl-k">Available now</span>
        <strong>Work near you</strong>
        <div class="dl-row"><div><div class="dl-t">Banquet Stewards</div><div class="dl-m">Hotels &amp; Catering</div></div><span class="dl-p">₹700</span></div>
        <div class="dl-row"><div><div class="dl-t">Construction Helpers</div><div class="dl-m">Construction &amp; Labour</div></div><span class="dl-p">₹800</span></div>
        <div class="dl-row"><div><div class="dl-t">Warehouse Loaders</div><div class="dl-m">Warehouse &amp; Loading</div></div><span class="dl-p">₹750</span></div>
        <span class="dl-mini">Apply in 2 taps</span>
      </div>
    </div>
  </main>

  <footer class="dl-foot">
    <span>© <?= date('Y') ?> SWON · Coimbatore &amp; Tiruppur</span>
    <span>English · हिंदी · தமிழ்</span>
  </footer>
</div>
