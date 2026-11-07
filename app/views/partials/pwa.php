<?php
/**
 * PWA layer: connectivity, install and update surfaces.
 * Rendered once per layout. Hidden until the matching JS state applies.
 */
?>
<div class="pwa-layer" id="pwa-layer">
    <div class="pwa-card pwa-offline" id="net-banner" role="status" aria-live="polite" hidden>
        <span class="pwa-icon" aria-hidden="true"><?= icon('wifi-off') ?></span>
        <div class="pwa-copy">
            <strong>You're offline</strong>
            <p data-net-message>Check your connection. Pages you have already loaded may still be available.</p>
        </div>
        <button type="button" class="btn-sw btn-sm btn-ghost" data-net-retry>Retry</button>
    </div>

    <div class="pwa-card" id="install-card" role="region" aria-label="Install SWON" hidden>
        <span class="pwa-icon" aria-hidden="true"><?= icon('download') ?></span>
        <div class="pwa-copy">
            <strong>Install SWON</strong>
            <p>Add SWON to your home screen for a faster, full-screen experience.</p>
        </div>
        <div class="pwa-actions">
            <button type="button" class="btn-sw btn-sm" data-install>Install</button>
            <button type="button" class="btn-link-sw pwa-dismiss" data-install-dismiss aria-label="Dismiss install prompt">Not now</button>
        </div>
    </div>

    <div class="pwa-card" id="ios-hint" role="region" aria-label="Install SWON on iPhone or iPad" hidden>
        <span class="pwa-icon" aria-hidden="true"><?= icon('share') ?></span>
        <div class="pwa-copy">
            <strong>Install SWON</strong>
            <p>Tap <b>Share</b> in Safari, then <b>Add to Home Screen</b>.</p>
        </div>
        <button type="button" class="btn-link-sw pwa-dismiss" data-install-dismiss aria-label="Dismiss install guidance">Got it</button>
    </div>

    <div class="pwa-card" id="update-card" role="status" aria-live="polite" hidden>
        <span class="pwa-icon" aria-hidden="true"><?= icon('refresh') ?></span>
        <div class="pwa-copy">
            <strong>Update available</strong>
            <p>A newer version of SWON is ready.</p>
        </div>
        <div class="pwa-actions">
            <button type="button" class="btn-sw btn-sm" data-update-reload>Reload</button>
            <button type="button" class="btn-link-sw pwa-dismiss" data-update-dismiss>Later</button>
        </div>
    </div>
</div>
