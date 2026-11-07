/*
 * SWON service worker
 * -------------------
 * Scope: public static assets + a generic offline page only.
 *
 * Security model:
 *   - HTML documents are NEVER cached (they can contain private worker/host data).
 *   - Only same-origin GET requests under /assets/ (relative to the app scope)
 *     are cached, plus the public manifest and the offline page.
 *   - POST/PUT/PATCH/DELETE, auth, OTP, payments, profile and all PHP routes are
 *     passed straight to the network and are never stored.
 *
 * Update model:
 *   - Bump VERSION on every production deploy. The new worker precaches first,
 *     then deletes older `swon-*` caches on activate.
 *   - The page asks this worker to skipWaiting only after the user chooses
 *     "Reload", so an open form is never interrupted.
 */
'use strict';

const VERSION = 'v1';
const PRECACHE = `swon-precache-${VERSION}`;
const RUNTIME = `swon-runtime-${VERSION}`;
const RUNTIME_LIMIT = 80;

const scope = self.registration.scope;
const scopePath = new URL(scope).pathname; // always ends with "/"
const abs = (path) => new URL(path, scope).href;

const PRECACHE_URLS = [
  './offline.html',
  './manifest.webmanifest',
  './assets/css/app.css',
  './assets/js/app.js',
  './assets/js/pwa.js',
  './assets/vendor/bootstrap.min.css',
  './assets/img/brand/icon-192.png',
  './assets/img/brand/icon-512.png',
  './assets/img/brand/icon-maskable-192.png',
  './assets/img/brand/icon-maskable-512.png',
  './assets/img/brand/favicon.png',
  './assets/img/brand/apple-touch-icon.png',
].map(abs);

const OFFLINE_URL = abs('./offline.html');
const MANIFEST_PATH = new URL(abs('./manifest.webmanifest')).pathname;
const STATIC_PREFIXES = ['assets/css/', 'assets/js/', 'assets/vendor/', 'assets/img/'];

self.addEventListener('install', (event) => {
  event.waitUntil((async () => {
    const cache = await caches.open(PRECACHE);
    // Add individually: one missing optional asset must not fail the install.
    // `cache: 'reload'` bypasses the HTTP cache so a deploy never precaches
    // yesterday's CSS/JS.
    await Promise.all(
      PRECACHE_URLS.map((url) => cache.add(new Request(url, { cache: 'reload' })).catch(() => null))
    );
  })());
});

self.addEventListener('activate', (event) => {
  event.waitUntil((async () => {
    const keep = new Set([PRECACHE, RUNTIME]);
    const names = await caches.keys();
    await Promise.all(
      names
        .filter((name) => name.startsWith('swon-') && !keep.has(name))
        .map((name) => caches.delete(name))
    );
    await self.clients.claim();
  })());
});

self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (request.method !== 'GET') {
    return;
  }

  let url;
  try {
    url = new URL(request.url);
  } catch {
    return;
  }
  if (url.origin !== self.location.origin) {
    return;
  }
  if (request.headers.has('range')) {
    return;
  }

  // App shell navigations: network-first. HTML is never stored, so private
  // pages cannot leak to another account or survive logout.
  if (request.mode === 'navigate') {
    event.respondWith((async () => {
      try {
        return await fetch(request);
      } catch {
        const offline = await caches.match(OFFLINE_URL);
        return offline || new Response(
          '<!doctype html><meta charset="utf-8"><title>Offline</title><p>You are offline.</p>',
          { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8' } }
        );
      }
    })());
    return;
  }

  // The public manifest is precached and safe to serve offline.
  if (url.pathname === MANIFEST_PATH) {
    event.respondWith(caches.match(request).then((cached) => cached || fetch(request)));
    return;
  }

  // Cache only public static assets, relative to the app scope so a
  // subdirectory deployment (/swon-web/) works exactly like the domain root.
  const relative = url.pathname.startsWith(scopePath)
    ? url.pathname.slice(scopePath.length)
    : null;
  if (!relative || !STATIC_PREFIXES.some((prefix) => relative.startsWith(prefix))) {
    return;
  }

  event.respondWith((async () => {
    const runtime = await caches.open(RUNTIME);
    const cached = await caches.match(request); // precache or runtime

    // Stale-while-revalidate: serve immediately, refresh in the background.
    const network = fetch(request)
      .then((response) => {
        if (response && response.ok && response.type === 'basic') {
          runtime.put(request, response.clone())
            .then(() => trimCache(RUNTIME, RUNTIME_LIMIT))
            .catch(() => null);
        }
        return response;
      })
      .catch(() => null);

    return cached || (await network) || new Response('', { status: 504, statusText: 'Gateway Timeout' });
  })());
});

/** Keep the runtime cache bounded so it never grows without limit. */
async function trimCache(name, limit) {
  const cache = await caches.open(name);
  const keys = await cache.keys();
  if (keys.length <= limit) {
    return;
  }
  await Promise.all(keys.slice(0, keys.length - limit).map((key) => cache.delete(key)));
}
