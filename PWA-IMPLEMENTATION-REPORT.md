# SWON Web — PWA Implementation Report

**Date:** 2026-10-25
**Scope:** installable, offline-aware Progressive Web App foundation for the existing
SWON PHP web application. No product rewrite, no backend changes, no database changes.

---

## 1. Repository architecture discovered

| Area | Actual implementation |
|---|---|
| Language / runtime | PHP 8.2 (works on 8.1+), core PHP, no framework, no Composer |
| Frontend | Server-rendered PHP views, HTML5, CSS3, vanilla JS |
| CSS | Bundled Bootstrap 5.3 CSS + `public/assets/css/app.css` (SWON V3 tokens) |
| Fonts | Bricolage Grotesque + Manrope via Google Fonts (`display=swap`) |
| Build system | **None** — no Node build, no bundler, no package manager for the web app |
| Routing | `public/index.php` front controller + `app/Router.php`; `public/router.php` for `php -S`; `.htaccess` for Apache |
| Base path | `BASE_URL` auto-detected in `app/bootstrap.php`; supports root **and** subdirectory deployments (`/swon-web/`) |
| Auth | PHP sessions, phone + OTP, CSRF token on every POST (`app/Csrf.php`) |
| Data | MySQL 8 / MariaDB via PDO (`swon_web`) |
| Existing PWA | **None** — no manifest, no service worker, no offline page, no PWA dependency |
| Icons / brand | `public/assets/img/brand/` — 48, 180, 192, 256 px PNGs; 559×161 wordmarks; theme `#0F766E`, canvas `#F6F3EC` |
| Production | Apache shared hosting per `DEPLOY.md`; HTTPS required for service workers |
| Tests | `php -l`; no automated test suite in this repo |

Because there is no build pipeline, the PWA is implemented as plain static files
(`manifest.webmanifest`, `sw.js`, `offline.html`, `pwa.js`) with no new dependencies.

---

## 2. PWA files created or modified

### Created

| File | Purpose |
|---|---|
| `public/manifest.webmanifest` | Web app manifest (relative paths, scope, icons, colors) |
| `public/sw.js` | Hand-written service worker (versioned precache + runtime cache + offline fallback) |
| `public/offline.html` | Self-contained branded offline page with embedded SWON icon |
| `public/assets/js/pwa.js` | Install prompt, iOS guidance, update lifecycle, network awareness |
| `app/views/partials/pwa.php` | Offline / install / update UI (hidden until JS state applies) |
| `public/assets/img/brand/icon-512.png` | 512×512 app icon (derived from the existing icon) |
| `public/assets/img/brand/icon-maskable-192.png` | 192×192 maskable icon with safe-zone padding |
| `public/assets/img/brand/icon-maskable-512.png` | 512×512 maskable icon with safe-zone padding |
| `PWA-IMPLEMENTATION-REPORT.md` | This report |

### Modified

| File | Change |
|---|---|
| `public/assets/css/app.css` | PWA layer components, safe-area insets, `viewport-fit` support |
| `app/helpers.php` | Added `download`, `wifi-off`, `share`, `refresh` icons |
| `app/views/layouts/app.php` | Manifest link, PWA meta, `beforeinstallprompt` capture, PWA partial, `pwa.js` |
| `app/views/layouts/auth.php` | Same head/meta/partial integration |
| `app/views/layouts/splash.php` | Same head/meta/partial integration |
| `app/views/layouts/plain.php` | Manifest/meta + `pwa.js` for error pages |
| `public/.htaccess` | `.webmanifest` MIME type + no-cache headers for worker/manifest/offline page |
| `public/router.php` | Serve `.webmanifest` with the correct MIME type under `php -S` |
| `.env` / `.env.example` | `PWA_ENABLED=true` |
| `README.md` | PWA section + link to this report |
| `DEPLOY.md` | PWA deployment requirements |

No backend route, API, schema, payment, auth rule or business rule was changed.

---

## 3. Manifest and icon configuration

`public/manifest.webmanifest`:

```json
{
  "name": "SWON",
  "short_name": "SWON",
  "start_url": "./",
  "scope": "./",
  "display": "standalone",
  "background_color": "#F6F3EC",
  "theme_color": "#0F766E",
  "icons": ["192 any", "512 any", "192 maskable", "512 maskable"]
}
```

- **Relative `start_url` and `scope`** are resolved against the manifest URL, so the
  same file works at `https://example.com/` and `https://example.com/swon-web/`.
- `display: standalone`; `orientation` intentionally omitted (not required).
- Icons are the existing SWON app icon; no replacement wordmark was generated.
- The 512 icon is a high-quality resize of the supplied 192 icon.
- Maskable icons place the original icon at 90 % on its native `#E5FAE9` background,
  keeping the wordmark inside the platform safe zone.
- The manifest is linked from every layout with `url('/manifest.webmanifest')`, which
  honours `BASE_URL`.

---

## 4. Service-worker strategy and caching rules

`public/sw.js` is scope-aware (`self.registration.scope`) and works at the root or in
a subdirectory.

### Caches

| Cache | Contents | Strategy |
|---|---|---|
| `swon-precache-<VERSION>` | `offline.html`, `manifest.webmanifest`, app CSS/JS, bundled Bootstrap CSS, brand icons | precache on install |
| `swon-runtime-<VERSION>` | same-origin public `/assets/` files actually requested | stale-while-revalidate, max 80 entries |

### Request rules

| Request | Behaviour |
|---|---|
| `GET` navigation (HTML) | **Network-first**; on failure return the cached offline page. HTML is **never stored**. |
| `GET` `/manifest.webmanifest` | cache-first (public, safe) |
| `GET` same-origin `/assets/{css,js,vendor,img}/…` | serve cache, revalidate in background |
| `GET` any other route | pass through to the network, no caching |
| `POST` / `PUT` / `PATCH` / `DELETE` | pass through, never intercepted, never cached |
| Cross-origin requests (fonts, etc.) | not intercepted |
| Range requests | not intercepted |

- **Versioning:** `const VERSION = 'v1'` — bump on every production deploy. Activation
  deletes all older `swon-*` caches. Confirmed in the update test: only
  `swon-precache-v2-test` and `swon-runtime-v2-test` remained after the v2 activation.
- **Offline fallback:** `public/offline.html`, self-contained (inline CSS + embedded
  base64 icon), so it renders correctly even for a deep link such as `/jobs/abc`.
- **Scope-safe paths:** asset matching strips `self.registration.scope`; a subdirectory
  deployment therefore matches `/swon-web/assets/...` correctly.

---

## 5. Installation behavior

`public/assets/js/pwa.js`:

- Captures `beforeinstallprompt` (also captured by a tiny head script so an early
  event is not missed), calls `preventDefault()`, and shows the SWON install card only
  when the browser actually offers installation.
- Install button calls `prompt()`; the result is handled via `userChoice`.
- `appinstalled` hides the card and records the installed state.
- Dismissal is stored in `localStorage` (`swon.install.dismissed`) for **30 days**;
  dismissed users are not prompted again in that window.
- Already-installed detection: `display-mode: standalone` and `navigator.standalone`
  (iOS). The card is hidden in standalone mode.
- iOS Safari (including iPadOS desktop-UA detection) gets a separate, non-button
  guidance card: “Tap **Share** in Safari, then **Add to Home Screen**.”
- No install UI is shown on browsers where installation is unavailable.
- The card is `role="region"`, has a labelled dismiss button, and is responsive;
  it sits above the safe-area inset.

**Not verified in a real browser:** the native install dialog cannot be triggered in
headless Chrome. Chrome’s installability check is verified instead
(`Page.getInstallabilityErrors` → `[]`). Real Android/desktop installation remains a
manual QA item.

---

## 6. Offline and update behavior

### Offline

- A fixed `role="status"` banner appears when the browser reports offline.
- POST forms are blocked while offline with a clear message; nothing is queued or
  replayed.
- On a failed navigation the service worker returns `offline.html`, which explains the
  situation, offers **Try again**, links to the app root (resolved from the SW scope),
  and auto-recovers once when the `online` event fires (guarded against loops).
- When connectivity returns, the banner hides; the app is not reloaded unnecessarily.
- Safe static assets remain available from cache; private HTML is not available offline
  by design.

### Updates

- A new worker installs into `waiting`; the page shows a non-modal
  “Update available — Reload” card.
- Reload posts `SKIP_WAITING`; the new worker activates; `controllerchange` reloads
  once. The reload is guarded by `userAskedReload` and a `refreshing` flag.
- No auto-reload, no interruption of an open form, no infinite loop.
- Verified: v1 → v2 produced `waiting: true`, the card was shown, Reload set a
  `sessionStorage` marker, the v2 worker became `activated`, and old caches were
  deleted.

### Logo animation

The approved **Grand Opening** page is now the live site root
(`public/index.html`). It plays the celebration once per browser (tracked per
launch timestamp in `localStorage`) and hands over to `/home` automatically.
`/home` is the normal app entry — the public splash for guests, or the worker/host
dashboard when signed in.

- A past `data-launch` opens immediately; after the ~4.1s celebration it goes to
  `data-next` (`./home`).
- A visitor who has already seen it goes straight to `/home`.
- The **installed PWA skips the intro** and goes directly to `/home`, so the app
  does not replay it on every launch.
- The page includes a `<noscript>` fallback to `/home`, a Skip/Enter control, a
  sound toggle with `aria-pressed`, and reduced-motion support.
- The standalone previews `public/grand-opening.html` and
  `swon_grand_opening.html` are preserved. They are not part of the PHP app shell
  and do not block `pwa.js` registration or service-worker updates.

---

## 7. Security considerations addressed

| Risk | Mitigation |
|---|---|
| Private HTML in shared Cache Storage | Navigation responses are **never** cached; only the generic offline page is stored. |
| Authenticated API / records / documents | The worker only caches same-origin `/assets/` files and the public manifest; no PHP route or data endpoint is cached. |
| Auth, OTP, payment, logout mutations | Non-GET requests are never intercepted or stored; nothing is queued for replay. |
| Tokens / personal data in cache | No tokens, session data or PII are written by the worker or `pwa.js`. |
| Stale private content after logout | Private HTML is not cached; after logout only public assets remain. Verified: `PRIVATE_CACHE_HITS=[]`. |
| Cross-user leakage in an installed app | No user-specific response is available from Cache Storage. |
| Financial / attendance data treated as current | No such data is cached. |
| Scope creep into other origins | Cross-origin requests are ignored; only same-origin `/assets/` is cached. |
| Unbounded cache growth | Runtime cache is capped at 80 entries. |
| Old vulnerable JS after deploy | Versioned caches + `cache: 'reload'` precache + SW update card. |

The install/update UI stores only dismissal/installed timestamps in `localStorage`.

---

## 8. Tests actually executed and results

Environment: Windows, PHP 8.2.12, MySQL/MariaDB (XAMPP), Node 24.21, Chrome
(`--headless=new`, DevTools Protocol). Server: `php -S 127.0.0.1:8099 -t public public/router.php`.

| Test | Result |
|---|---|
| `php -l` on every PHP file | **Pass** |
| `node --check public/sw.js` | **Pass** |
| `node --check public/assets/js/pwa.js` | **Pass** |
| Manifest JSON parse | **Pass** |
| `/manifest.webmanifest` | **200**, `application/manifest+json` |
| `/sw.js` | **200**, `application/javascript` |
| `/offline.html` | **200**, `text/html` |
| 192 / 512 / maskable 192 / maskable 512 icons | **200**, `image/png` |
| Manifest link, PWA meta, `pwa.js`, PWA layer in HTML | **Present on `/` and `/login`** |
| `Page.getAppManifest` | URL correct, **0 errors**, name `SWON`, start `./`, **4 icons** |
| `Page.getInstallabilityErrors` | **`[]`** (installable) |
| SW registration / activation | **registered → activated**, controller `…/sw.js` |
| Precache contents | only `/assets/…`, `/manifest.webmanifest`, `/offline.html` |
| Offline navigation with the PHP server **stopped** (`/jobs`) | **Offline page served** — title `Offline · SWON`, heading “You're offline”, Retry present |
| Login → dashboard → nested job route → logout (server-side) | **Pass** |
| Login through the real form with `pwa.js` active (headless Chrome) | **Pass** |
| Caches after login + `/jobs`, `/applications`, `/profile`, job detail | **No private routes** (`PRIVATE_CACHE_HITS=[]`) |
| Caches after logout | unchanged; only public assets |
| Update v1 → v2 | waiting worker, card shown, Reload worked, v2 activated, old caches deleted, **no loop** |
| Existing business flows | Login/OTP/logout and nested routes re-tested; no change |

### Not tested (needs a real device/browser)

- Actual “Install app” click and native install dialog on Android/desktop Chrome.
- iOS Safari Share → Add to Home Screen.
- Firefox / Safari / Edge compatibility.
- Apache `.htaccess` MIME/no-cache headers (the PHP built-in server was used).
- A subdirectory deployment (`/swon-web/`) end-to-end; the path logic is scope-relative
  and reviewed, but not exercised on a live Apache vhost.
- Lighthouse / performance score.
- Real offline download of large job photos (only the assets actually requested are
  cached, by design).
- The standalone logo-intro pages were inspected for reduced-motion, Skip and
  sound-toggle controls but not played end-to-end in a real browser.

---

## 9. Deployment requirements

1. **HTTPS** — service workers require a secure context. `localhost` and `127.0.0.1`
   are exempt for testing.
2. **Document root** — serve `public/` (recommended) or keep the root `.htaccess`
   safety net. `sw.js`, `manifest.webmanifest` and `offline.html` must be reachable
   from the app root scope.
3. **MIME / cache headers** — `public/.htaccess` now adds
   `AddType application/manifest+json .webmanifest` and no-cache headers for
   `sw.js`, `manifest.webmanifest`, `offline.html` (requires `mod_mime` / `mod_headers`).
4. **Bump the worker version** — change `const VERSION = 'v1'` in `public/sw.js` on every
   deploy that changes CSS, JS, icons, the manifest or `offline.html`. Old caches are
   removed automatically.
5. **Environment** — `PWA_ENABLED=true`; set `false` locally if a stale worker
   interferes with debugging.
6. **Content Security Policy** — if a CSP is added later, allow the tiny inline
   `beforeinstallprompt` capture and the inline script/style in `offline.html`
   (or move them to nonce-tagged external files).
7. **Do not** put `sw.js` behind authentication or rewrite it to the PHP front controller.

---

## 10. Remaining limitations and recommended next steps

### Push notifications — assessed, not implemented

There is no existing push infrastructure (no subscription model, no VAPID keys, no
push service, no notification preferences). Implementing push would require:

- HTTPS and browser support (iOS requires the PWA to be installed first).
- A user-visible permission flow and consent record.
- `push` / `notificationclick` handlers in the service worker.
- A secure server-side subscription store, cleanup on logout/expiry, and VAPID keys
  kept out of frontend code.
- A delivery service and operational cost/ownership.
- Notification preference UI and privacy review.

None of this is fabricated here. The service worker has no `push` handler and the app
does not request notification permission.

### Other recommended next steps

1. Manual install test on a real Android device and desktop Chrome; capture the native
   prompt.
2. iOS Safari manual test of the Share-menu guidance.
3. Test a real Apache subdirectory deployment (the path logic is ready).
4. Self-host the Bricolage/Manrope woff2 files for reliable offline typography.
5. Add a small design-system note that PWA status surfaces use the same tokens
   (`DESIGN-SPEC.md` already covers the base UI).
6. If offline data is ever required, design a separate, reviewed private cache model —
   do not extend this public-asset cache to user data.
7. Consider manifest `shortcuts` once the authenticated routes are stable.

---

## Acceptance summary

| Criterion | Status |
|---|---|
| Manifest served, valid, correctly scoped | ✅ |
| 192/512 + maskable icons resolve | ✅ |
| Installable per Chrome (`getInstallabilityErrors`) | ✅ |
| Install prompt / iOS guidance implemented | ✅ (native prompt not manually clicked) |
| SW registers and activates | ✅ |
| Static assets cached deliberately | ✅ |
| Offline fallback works for a nested route with the server down | ✅ |
| No private/authenticated HTML cached | ✅ (verified) |
| No POST caching or mutation replay | ✅ |
| Update available without reload loop | ✅ (verified v1→v2) |
| Login/logout and nested routes preserved | ✅ |
| Existing logo animation preserved | ✅ — the Grand Opening is now `/` and hands over to `/home`; installed PWA skips it; reduced motion, sound toggle and noscript fallback intact |
| Push notifications | Not implemented — requirements documented |
