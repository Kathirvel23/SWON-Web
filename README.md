# SWON Web — core MVP

A standalone PHP + MySQL web application that reproduces the **core** of the SWON
mobile marketplace: phone OTP sign-in, role selection, worker and host
dashboards, opportunity discovery, Flight Requests and bonding decisions.

This project lives **outside** `C:\Users\kathi\WorkBridge`. The original mobile
app, NestJS backend and database are untouched and are not required at runtime.

## Product context

The mobile app in `C:\Users\kathi\WorkBridge` is the source of truth for product
context, vocabulary and reference data. This web MVP mirrors it:

- **Launch footprint:** Coimbatore + Tiruppur (Tamil Nadu) — 15 + 10 service
  areas, identical to the mobile seed. The old Chennai/Bengaluru demo data is gone.
- **Vocabulary:** Worker / **Host**, **Opportunity** (job), **Flight Request**
  (application), **Bond** (hire), **Formation** (crew), **FlashMatch**,
  **WhiteWing**. Database tables and route paths keep `job`, `application` and
  `employer` so nothing breaks.
- **Brand line:** "Find Opportunities. Bond fast. Get paid fairly."

## Stack

- **PHP 8.2** (works on 8.1+), core PHP, no framework, no Composer
- **MySQL 8 / MariaDB 10.4** via **PDO** with prepared statements
- **HTML5 + CSS3 + Bootstrap 5.3** (bundled locally) + vanilla JS
- **phpMyAdmin** compatible schema in `database/`
- Responsive: desktop, tablet and mobile browser

## Design system

The UI is a port of the mobile app's V3 "Vibrant & Dynamic" system, so the
website and the app look like one product rather than two projects. The full
contract — tokens, component states, accessibility criteria, content rules and
the design QA checklist — lives in [`DESIGN-SPEC.md`](DESIGN-SPEC.md).

- Every token in `public/assets/css/app.css` mirrors
  `Mobile App/src/constants/theme.ts` — same palette, radii, spacing, shadows,
  gradients and the wing motif used on the mobile hero.
- Type: **Bricolage Grotesque** for headings, money and big numbers (`.pay`,
  `.pay-hero`, `.stat-value`), **Manrope** for body copy. Both load from Google
  Fonts; self-host the woff2 files if the site must work fully offline.
- Status chips use the app's status table: applied (teal), shortlisted (blue),
  waitlisted (amber), hired (solid green), rejected (red), draft/filled
  (neutral/teal). Add new statuses to `status_class()` and to the `chip-*`
  rules together.
- Job cards show the category photo (or a branded gradient tile), a company
  mark, a category accent chip, a seats-open pill, an urgency badge when two or
  fewer seats remain, and the pay as the hero figure. Keep them in
  `app/views/partials/job-card.php` only.
- Accessibility baseline: WCAG AA body contrast, a visible `:focus-visible` ring
  on every control, a skip link, `aria-current` on the active nav item, ARIA
  wiring on form errors (`field_aria()`), `prefers-reduced-motion` and
  `prefers-contrast` support, and 44–48px touch targets. Dark mode is
  deliberately not shipped yet — the mobile app renders light mode only.

## Images

All artwork is the product's own, copied out of the mobile app bundle (see
`public/assets/img/CREDITS.md`):

| Where | Asset |
|---|---|
| Header lockup | `img/brand/swon-wordmark.png` (dark) |
| Landing + auth panel | `img/brand/swon-wordmark-light.png` (reversed) |
| Favicon / phone home-screen icon / social card | `img/brand/favicon.png`, `apple-touch-icon.png`, `icon-192.png` |
| Sign-in panel background | `img/login-bg.webp` |
| Job card + job detail photo | `img/jobs/<category>.webp` (+ a `-sm.webp` for `srcset`) |

Photo → category mapping lives in `category_photo()` in `app/helpers.php`, the
icon used on the tile fallback in `category_icon()`. **Two of the mobile app's
photos are deliberately not used here** — `warehouse.jpg` shows Oxfam markings
and `delivery.jpg` shows Grab branding; a photo's CC licence does not cover a
third-party trademark. Replace those two in the mobile bundle as well.

`logistics`, `retail` and `promotions` have no photo, so they render a branded
gradient tile (accent gradient + category icon + dot texture). That is
deliberate. Real employer photography per job is the proper long-term fix.

## What is implemented

| Area | Included |
|---|---|
| Splash / brand landing | Yes |
| Phone + OTP login (dev OTP mode) | Yes |
| Role selection (one role per account) | Yes |
| Onboarding (worker or host profile) | Yes |
| Worker dashboard, stats, recent opportunities | Yes |
| Host dashboard, stats, recent opportunities | Yes |
| Opportunity discovery: search, category, area, pay sort, pagination | Yes |
| Opportunity detail + Flight Request (seat-safe, one per opportunity) | Yes |
| My Flight Requests + withdraw | Yes |
| Host: create opportunity, draft/publish, cancel | Yes |
| Flight Request queue: shortlist, waitlist, bond, reject, release | Yes |
| Basic profile edit for both roles | Yes |
| CSRF, session auth, role guards, rate-limited OTP | Yes |
| Installable PWA: manifest, service worker, offline page, install/update UI | Yes |

Deliberately **out of scope for this MVP**: KYC/document upload, maps and
geolocation, attendance/QR, payments and payouts, ratings, complaints,
notifications, matching algorithm, admin console, Tamil/Hindi UI, push
notifications. These can be added later without changing the architecture.

## Progressive Web App (PWA)

SWON is installable on supported mobile and desktop browsers and keeps a safe
offline fallback. This is a PWA enhancement of the same PHP app — no separate
mobile client and no backend changes.

- **Install** — Android/desktop Chromium shows an in-app “Install SWON” card after
  the browser's `beforeinstallprompt` event. iOS Safari shows Share → Add to Home
  Screen guidance. Dismissals are remembered for 30 days.
- **Offline** — only public static assets (`/assets/`), the manifest and a generic
  `offline.html` are cached. Private HTML and authenticated responses are never
  cached, so logout or account switching cannot expose stale data. A nested route
  with no connection shows the branded offline page with a Retry action.
- **Updates** — a new service worker waits; the page offers “Reload” and only then
  activates it. No auto-reload loop and no interrupted forms.
- **Files** — `public/manifest.webmanifest`, `public/sw.js`, `public/offline.html`,
  `public/assets/js/pwa.js`, `app/views/partials/pwa.php`.
- **Deploy** — HTTPS is required (localhost is exempt), `sw.js` must stay at the app
  root, and `const VERSION` in `public/sw.js` must be bumped on every deploy that
  changes static assets, the manifest or `offline.html`. Set `PWA_ENABLED=false` to
  disable the worker locally.
- **Not included** — push notifications. Requirements and open questions are in the
  implementation report.

Full audit, security model and test evidence: [`PWA-IMPLEMENTATION-REPORT.md`](PWA-IMPLEMENTATION-REPORT.md).

## Opening page & handover

`/` shows the approved **Grand Opening** page (`public/index.html`). It celebrates
once per browser, then hands over to `/home` automatically. `/home` is the normal
app entry: the public splash for guests, or the worker/host dashboard when signed in.

- **Launch time:** edit `data-launch` on `<main>` in `public/index.html`
  (ISO local time, e.g. `2026-11-01T11:00`). A past date opens immediately.
- **Destination:** edit `data-next` (default `./home`).
- **Preview without waiting:** `/?preview=1` · **skip:** `/?instant=1` ·
  **disable auto-handover:** `/?auto=0`.
- The installed PWA skips the intro and goes straight to `/home`.
- The same page is also available as `public/grand-opening.html` and the editable
  source `swon_grand_opening.html`.

## Setup (XAMPP)

1. Start **MySQL** from the XAMPP control panel (Apache is optional).
2. Copy the environment file if needed:

   ```powershell
   Copy-Item .env.example .env
   ```

3. Create the database and demo data:

   ```powershell
   C:\xampp\php\php.exe bin\setup.php
   ```

   Or import `database/schema.sql` then `database/seed.sql` through
   **phpMyAdmin** (database name: `swon_web`).

4. Run the app with the built-in server:

   ```powershell
   C:\xampp\php\php.exe -S 127.0.0.1:8080 -t public public/router.php
   ```

5. Open <http://127.0.0.1:8080>.

### Apache instead of the built-in server

Point a virtual host (or `C:\xampp\htdocs\swon-web`) at the `public/` directory.
`public/.htaccess` already routes non-file requests to `index.php`. Never point
the document root at the project root — `app/`, `database/` and `.env` must stay
outside the web root.

## Web database installer (live hosting)

For shared hosting without CLI access, use the one-time installer after uploading:

1. Open `https://yourdomain/install.php`.
2. Enter the database details from cPanel (host, name, username, password).
   Current values are prefilled; leave the password blank to keep the saved one.
3. Choose **Demo data** or **Reference data only**, then click **Save & install**.
   The installer writes the values to `.env` and imports the tables.
4. Delete `public/install.php` immediately afterwards.

The installer has no setup key. It locks itself with `storage/installed.lock` after
success, uses the same idempotent SQL as `bin/setup.php`, and never prints the
database password. Because it is unprotected, delete it as soon as installation
finishes.

## OTP test mode (no SMS)

OTP is **off by default** in this test build (`OTP_ENABLED=false`). Entering a
mobile number signs in directly: a new number goes to role selection, an existing
account goes to its dashboard. The `/otp` routes redirect back to login.

To turn OTP back on, set `OTP_ENABLED=true`. To show the code without SMS, also set
`OTP_DELIVERY=screen`: the generated 6-digit code appears in an in-app notification
with a **Fill code** button. Verification still uses the normal hashed code in
`otp_challenge`; no SMS provider is called.

**Testing only.** Anyone who can open the login page can sign in as any phone
number while OTP is off. Set `OTP_ENABLED=true` and connect a real SMS provider
before a public launch.

## Demo accounts

OTP dev mode is enabled in `.env`, so the code is displayed on the verification
screen instead of being sent by SMS.

| Role | Phone | Person |
|---|---|---|
| Worker | 9000000001 | Arun Kumar (1 bonded, 2 Flight Requests) |
| Host | 9000000002 | Kovai Event Works (5 open opportunities) |
| Worker | 9000000003 | Priya Sharma (2 Flight Requests) |
| Host | 9000000004 | Tiruppur Knit Staffing (4 open opportunities) |
| Worker | 9000000005 | Karthik Raja (2 Flight Requests) |

Any other valid Indian mobile number creates a new account on first verification.

## Project structure

```
WorkBridge-Web/
├── app/
│   ├── bootstrap.php        # env, autoload, session, error policy
│   ├── routes.php           # every route in one place
│   ├── Router.php           # {param} path router
│   ├── Auth.php             # session auth + role guards
│   ├── Database.php         # PDO singleton + helpers + transactions
│   ├── Csrf.php             # CSRF tokens
│   ├── Validator.php        # rule-based validation
│   ├── helpers.php          # escaping, URLs, money, icons, formatting
│   ├── Controllers/         # Auth, Dashboard, Job, Application, Profile
│   ├── Repositories/        # all SQL (prepared statements only)
│   ├── Services/            # MarketplaceService — apply/decide/publish/cancel
│   └── views/               # layouts, partials, pages
├── public/
│   ├── index.php            # front controller
│   ├── router.php           # php -S router
│   ├── .htaccess            # Apache rewrite
│   └── assets/              # CSS, JS, bundled Bootstrap
├── database/
│   ├── schema.sql           # 8 tables (subset of the mobile schema)
│   └── seed.sql             # areas, categories, demo accounts/jobs/applications
├── storage/logs/            # runtime error log
├── bin/setup.php            # create DB + import schema/seed
└── .env                     # local config (gitignored)
```

## Business rules enforced

- One application per worker per job (DB unique constraint + service check).
- Applying is only possible while the job is `OPEN` and seats remain.
- Hiring re-checks seats inside a `SELECT … FOR UPDATE` transaction; a job flips
  to `FILLED` automatically when the headcount is reached, and back to `OPEN`
  when a worker is released.
- Employers can only act on their own jobs/applications (server-side checks).
- Legal status transitions: `APPLIED → SHORTLISTED/WAITLISTED/HIRED/REJECTED`,
  `HIRED → RELEASED`, pre-hire `→ WITHDRAWN`.
- OTP: 6 digits, HMAC-hashed, 2-minute TTL, 60-second cooldown, 5 attempts,
  5 requests per phone per 15 minutes.
- All POST requests require a CSRF token; all output is escaped.

## Security notes before going live

- Set `OTP_DEV_MODE=false` and integrate a real SMS/OTP provider (MSG91 or similar).
  Without that, **nobody can sign in** on production.
- Set a long random `APP_KEY`, a dedicated MySQL user (not `root`), and
  `APP_ENV=production`.
- Serve only over HTTPS; session cookies are marked `Secure` automatically when
  HTTPS is detected.
- Move `.env` and `storage/` outside the web root on shared hosting if possible.
- Add an OTP-provider outbound IP allow-list if your vendor requires it.

## Next steps (in rough order)

1. SMS/OTP provider + production secrets.
2. Admin console (KYC review, job moderation, complaints) — the biggest gap.
3. Attendance check-in/out and earnings, reusing the existing MySQL design.
4. Notifications (in-app inbox first, then email/SMS).
5. Maps and location filters on job discovery.
6. KYC uploads with private object storage.
7. Tamil / Hindi string tables (the mobile app already has 706 keys to reuse).

## Verified

`php -l` passes on every PHP file (40+ files). The following was exercised end
to end against **MariaDB 10.4** on this machine:

- fresh signup → phone OTP → role selection → onboarding → worker dashboard
- worker: browse/filter jobs, open a job, apply, duplicate-apply blocked,
  withdraw permission checks, profile update
- employer: dashboard, post a draft job, publish it, cancel it, applicant queue,
  shortlist/waitlist/hire/release, seat counting
- role guards (worker opening `/employer` is redirected), CSRF rejection (419),
  404 handling, server-side validation messages
- responsive layouts checked at 1440 px and 390 px widths

Not yet exercised: real SMS delivery, HTTPS deployment, high-concurrency load,
and the skipped modules listed above.
