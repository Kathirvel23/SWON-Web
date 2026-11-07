/*
 * SWON PWA client
 * ---------------
 * Dependency-free progressive enhancement:
 *   - network awareness (browser online/offline hints)
 *   - native install prompt (Chromium) + iOS Safari guidance
 *   - service-worker registration and a user-controlled update
 *
 * It never stores credentials, never queues mutations and never caches data.
 */
(function () {
  'use strict';

  var root = document.documentElement;

  // ---------------------------------------------------------------- config
  var enabledMeta = document.querySelector('meta[name="swon-pwa"]');
  var enabled = !enabledMeta || enabledMeta.getAttribute('content') !== '0';

  function resolveScope() {
    var meta = document.querySelector('meta[name="swon-base"]');
    var base = meta ? (meta.getAttribute('content') || '') : '';
    if (!base) {
      var manifest = document.querySelector('link[rel="manifest"]');
      if (manifest && manifest.href) {
        try {
          base = new URL('.', manifest.href).pathname;
        } catch (e) {
          base = '';
        }
      }
    }
    base = base.replace(/\/+$/, '');
    return (base || '') + '/';
  }

  var scope = resolveScope();
  var swUrl = scope + 'sw.js';

  // ------------------------------------------------- network awareness
  var netBanner = document.getElementById('net-banner');
  var netMessage = document.querySelector('[data-net-message]');
  var DEFAULT_NET_MESSAGE = 'Check your connection. Pages you have already loaded may still be available.';

  function updateNetwork() {
    var offline = navigator.onLine === false;
    root.classList.toggle('is-offline', offline);
    if (netBanner) {
      netBanner.hidden = !offline;
    }
    if (!offline && netMessage) {
      netMessage.textContent = DEFAULT_NET_MESSAGE;
    }
  }

  window.addEventListener('online', updateNetwork);
  window.addEventListener('offline', updateNetwork);
  updateNetwork();

  var retry = document.querySelector('[data-net-retry]');
  if (retry) {
    retry.addEventListener('click', function () {
      window.location.reload();
    });
  }

  // A failed POST while offline is confusing; stop it early. Nothing is
  // queued or replayed — the user retries when the connection returns.
  document.addEventListener('submit', function (event) {
    var form = event.target;
    var method = form && form.method ? String(form.method).toLowerCase() : 'get';
    if (method === 'post' && navigator.onLine === false) {
      event.preventDefault();
      event.stopPropagation();
      if (netBanner) {
        netBanner.hidden = false;
        if (netMessage) {
          netMessage.textContent = "You're offline. Reconnect before submitting this form.";
        }
      }
    }
  }, true);

  if (!enabled) {
    root.setAttribute('data-sw', 'disabled');
    return;
  }

  // ------------------------------------------------------- update lifecycle
  var updateCard = document.getElementById('update-card');
  var userAskedReload = false;
  var refreshing = false;

  function showUpdate() {
    if (updateCard) {
      updateCard.hidden = false;
    }
  }

  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.addEventListener('controllerchange', function () {
      // Reload only when the user chose "Reload" and only once.
      if (!userAskedReload || refreshing) {
        return;
      }
      refreshing = true;
      window.location.reload();
    });
  }

  var updateReload = document.querySelector('[data-update-reload]');
  if (updateReload) {
    updateReload.addEventListener('click', function () {
      userAskedReload = true;
      navigator.serviceWorker.getRegistration().then(function (registration) {
        if (registration && registration.waiting) {
          registration.waiting.postMessage({ type: 'SKIP_WAITING' });
        } else {
          window.location.reload();
        }
      }).catch(function () {
        window.location.reload();
      });
    });
  }

  var updateDismiss = document.querySelector('[data-update-dismiss]');
  if (updateDismiss) {
    updateDismiss.addEventListener('click', function () {
      if (updateCard) {
        updateCard.hidden = true;
      }
    });
  }

  // ---------------------------------------------------------- install prompt
  var installCard = document.getElementById('install-card');
  var iosHint = document.getElementById('ios-hint');
  var deferredPrompt = null;
  var DISMISS_KEY = 'swon.install.dismissed';
  var DISMISS_MS = 30 * 24 * 60 * 60 * 1000;

  function storageGet(key) {
    try { return window.localStorage.getItem(key); } catch (e) { return null; }
  }

  function storageSet(key, value) {
    try { window.localStorage.setItem(key, value); } catch (e) { /* private mode */ }
  }

  function isStandalone() {
    return (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) ||
      window.navigator.standalone === true;
  }

  function recentlyDismissed() {
    var stamp = parseInt(storageGet(DISMISS_KEY) || '0', 10);
    return stamp > 0 && (Date.now() - stamp) < DISMISS_MS;
  }

  function dismissInstall() {
    storageSet(DISMISS_KEY, String(Date.now()));
    if (installCard) { installCard.hidden = true; }
    if (iosHint) { iosHint.hidden = true; }
  }

  function showInstallCard() {
    if (!installCard || isStandalone() || recentlyDismissed() || storageGet('swon.install.installed') === '1') {
      return;
    }
    // A short delay keeps the prompt from competing with the first paint.
    window.setTimeout(function () {
      if (installCard && deferredPrompt) {
        installCard.hidden = false;
      }
    }, 1200);
  }

  // The inline head listener captures beforeinstallprompt before this file
  // runs, so use the stored event when present.
  if (window.__swonInstallPrompt) {
    deferredPrompt = window.__swonInstallPrompt;
    showInstallCard();
  }

  window.addEventListener('beforeinstallprompt', function (event) {
    event.preventDefault();
    deferredPrompt = event;
    showInstallCard();
  });

  window.addEventListener('appinstalled', function () {
    deferredPrompt = null;
    storageSet('swon.install.installed', '1');
    if (installCard) { installCard.hidden = true; }
    if (iosHint) { iosHint.hidden = true; }
  });

  var installButton = document.querySelector('[data-install]');
  if (installButton) {
    installButton.addEventListener('click', function () {
      if (!deferredPrompt) {
        return;
      }
      deferredPrompt.prompt();
      deferredPrompt.userChoice.then(function (choice) {
        if (!choice || choice.outcome !== 'accepted') {
          dismissInstall();
        } else if (installCard) {
          installCard.hidden = true;
        }
        deferredPrompt = null;
      }).catch(function () {
        deferredPrompt = null;
      });
    });
  }

  Array.prototype.forEach.call(document.querySelectorAll('[data-install-dismiss]'), function (button) {
    button.addEventListener('click', dismissInstall);
  });

  // iOS Safari has no beforeinstallprompt; show concise Share-menu guidance.
  var ua = navigator.userAgent || '';
  var isIpadOs = /macintosh/i.test(ua) && navigator.maxTouchPoints > 1;
  var isIos = /iphone|ipad|ipod/i.test(ua) || isIpadOs;
  var isIosSafari = isIos && /safari/i.test(ua) && !/crios|fxios|edgios|chrome/i.test(ua);

  if (isIosSafari && iosHint && !isStandalone() && !recentlyDismissed() && !storageGet('swon.install.installed')) {
    window.setTimeout(function () {
      if (iosHint && !isStandalone()) {
        iosHint.hidden = false;
      }
    }, 2500);
  }

  if (window.matchMedia) {
    var standaloneQuery = window.matchMedia('(display-mode: standalone)');
    var onStandaloneChange = function () {
      if (isStandalone()) {
        if (installCard) { installCard.hidden = true; }
        if (iosHint) { iosHint.hidden = true; }
      }
    };
    if (standaloneQuery.addEventListener) {
      standaloneQuery.addEventListener('change', onStandaloneChange);
    } else if (standaloneQuery.addListener) {
      standaloneQuery.addListener(onStandaloneChange);
    }
  }

  // ------------------------------------------------- service worker register
  if (!('serviceWorker' in navigator) || !window.isSecureContext) {
    root.setAttribute('data-sw', 'unsupported');
    return;
  }

  window.addEventListener('load', function () {
    navigator.serviceWorker.register(swUrl, { scope: scope }).then(function (registration) {
      root.setAttribute('data-sw', 'registered');

      if (registration.waiting && navigator.serviceWorker.controller) {
        showUpdate();
      }

      registration.addEventListener('updatefound', function () {
        var installing = registration.installing;
        if (!installing) {
          return;
        }
        installing.addEventListener('statechange', function () {
          if (installing.state === 'installed' && navigator.serviceWorker.controller) {
            showUpdate();
          }
        });
      });

      // Ask the browser for a fresh worker at most once an hour.
      window.setInterval(function () {
        registration.update().catch(function () { /* offline is fine */ });
      }, 60 * 60 * 1000);
    }).catch(function () {
      root.setAttribute('data-sw', 'failed');
    });
  });
})();
