# SWON Web — live deployment (shared hosting / Hostinger, cPanel, Plesk)

Plain PHP 8.1 + MySQL. No Composer, no Node, no build step. Upload, import,
fill in `.env`, done.

---

## 1. Before you upload — the one thing that decides whether people can log in

**Login is by SMS one-time code.** There is no password. `OTP_DEV_MODE=true`
prints the code on the screen instead of sending an SMS, which is how the local
demo works.

| Setting | Effect on a live site |
|---|---|
| `OTP_DEV_MODE=false` + a real SMS provider | Correct. Real users get a real code. **An SMS integration still has to be written** (see §6) — until then nobody can sign in. |
| `OTP_DEV_MODE=true` on live | **Anyone can sign in as anyone** by typing their phone number and reading the code off the screen. Works for a private demo, never for real users. |

So: either keep the site private (a hidden URL, and do **not** keep dev mode on
for long), or wire the SMS provider first.

**Until the SMS provider exists, there is a middle path** — put your own numbers
in `OTP_DEV_PHONES` and leave `OTP_DEV_MODE=false`:

```ini
OTP_DEV_MODE=false
OTP_DEV_PHONES=9876543210        # comma separated
OTP_DELIVERY=auto
```

Then *your* numbers see the code on screen (so you can sign in and demo the app
on the live domain) while everyone else gets a normal "we sent a code" screen and
their code — which no SMS provider delivers yet — is written to
`storage/logs/otp.log`. Read that file in the file manager when you need a code
for another number.

`OTP_DELIVERY` accepts `auto` (default, the behaviour above), `screen` (always on
screen), `log` (only to the log file, never shown) and `sms` (reserved for the
MSG91 integration; until it exists it falls back to the log file rather than
losing the code).

Once real SMS is live, set `OTP_DELIVERY=sms`, clear `OTP_DEV_PHONES`, and delete
`storage/logs/otp.log` — it contains live codes.

**No-SMS testing:** set `OTP_DELIVERY=screen`. The generated code is shown in an
in-app notification on the OTP page (with a **Fill code** button) and is never
sent by SMS. This is for testing only — never use `screen` for a public launch.

**OTP off (default in this build):** `OTP_ENABLED=false` signs in directly from
the phone number. New numbers go to role selection; existing accounts go to their
dashboard. The OTP pages redirect to login. Set `OTP_ENABLED=true` to require OTP.
Test only — anyone can sign in as any number while OTP is off.

---

## 2. Create the database and import the schema

In your host's control panel:

1. Create a MySQL database and a database user (note the full names — hosts
   usually prefix them, e.g. `u123456_swon`). Give the user all privileges on
   that one database.
2. Open **phpMyAdmin**, select that database in the left sidebar.
3. **Import** → choose `database/live/schema.sql` → Go. (8 tables)
4. **Import** → choose `database/live/seed.sql` → Go.
   - This loads demo areas, categories, 5 demo accounts, 10 opportunities, 7 Flight Requests
     so the site looks alive for a first look.
   - **Skip it** if you want to start empty — the app renders proper empty
     screens (verified) and areas/categories are needed for the create-opportunity
     form. If you skip it, import `database/live/schema.sql` only, then add
     areas and categories yourself; the seed is the easy way to get them.
5. If you imported the seed and now want the demo gone (keeping areas and
   categories), import `database/live/remove-demo-data.sql`.

### Or use the web installer

If you prefer not to use phpMyAdmin:

1. Open `https://yourdomain/install.php`.
2. Enter the database details from cPanel (host, name, username, password).
   The current values are prefilled; leave the password blank to keep the saved one.
3. Choose **Demo data** or **Reference data only**, then click **Save & install**.
   The installer writes the values to `.env` and imports the tables.
4. Delete `public/install.php` immediately afterwards.

The installer has no setup key. It locks itself with `storage/installed.lock` after
success and never displays the database password. Delete it as soon as installation
finishes.

The `database/*.sql` files at the project root (`schema.sql`, `seed.sql`)
contain `CREATE DATABASE swon_web` / `USE swon_web`. Those are for XAMPP on
your own machine. **Use the files in `database/live/` on a host** — they leave
the database choice to phpMyAdmin.

---

## 3. Upload the files

Unzip the archive and upload the contents.

**Recommended:** point your domain's document root at the `public/` folder.
On Hostinger: hPanel → Websites → your site → *Change document root* →
`/public_html/public`. In cPanel/Plesk, set the same in the domain settings.

**If you cannot change the document root** (you must upload into
`public_html` itself): the project root `.htaccess` is already written for
this case — it forwards every request into `public/` and blocks `app/`,
`database/`, `storage/`, `bin/` and all dotfiles. Upload everything into
`public_html` and it works.

Then check these URLs in a browser:

- `https://yourdomain/` → the SWON **Grand Opening** page (hands over to `/home`
  after the celebration)
- `https://yourdomain/home` → the SWON app landing page (or the dashboard when
  signed in)
- `https://yourdomain/app/bootstrap.php` → must be **403 Forbidden**
- `https://yourdomain/.env` → must be **403 Forbidden**

If `/` shows the app landing page instead of the Grand Opening, the host is
ignoring `DirectoryIndex`/`.htaccess`; point the document root at `public/` or set
`index.html` ahead of `index.php` in the directory index.

If either of the last two loads, stop and fix the document root before going
further: your database password and application code are downloadable.

---

## 4. Create `.env`

Copy `.env.example` to `.env` (same folder as `app/`) and edit:

```ini
APP_NAME="SWON"
APP_ENV=production                # hides PHP errors from visitors
APP_URL=https://yourdomain
APP_KEY=<paste 32+ random characters here>

DB_HOST=localhost                 # some hosts need 127.0.0.1
DB_PORT=3306
DB_NAME=u123456_swon              # the FULL name from step 2
DB_USER=u123456_swon
DB_PASS=<the database password>

SESSION_NAME=swon_session
OTP_DEV_MODE=false                # see §1
OTP_TTL_SECONDS=120
OTP_COOLDOWN_SECONDS=60
OTP_MAX_ATTEMPTS=5
OTP_MAX_PER_15MIN=5
```

Generate `APP_KEY` with anything that produces random characters, e.g.
`openssl rand -hex 32`.

Keep `.env` out of any public folder and never commit it. It is deliberately
**not** inside this archive.

---

## 5. Permissions and first checks

- `storage/logs/` must be writable by PHP (chmod 755, or 775 if the host runs
  PHP as a different user). This is where runtime errors are written —
  `storage/logs/app.log`.
- Open the site and check:
  1. Landing page, then **Sign in**.
  2. A real mobile number → code → role selection → onboarding → dashboard.
  3. As a host: **Create Opportunity** → publish → open the Flight Requests queue.
  4. `storage/logs/app.log` should be empty. If it has entries, read them.

---

## 6. Not built yet — say this out loud before announcing a launch

- **SMS / OTP provider.** Required. Login cannot work for real users without
  it. (MSG91 is the intended provider, per `Documentation/MSG91_OTP_INTEGRATION.md`
  in the mobile project.)
- **KYC / document upload, maps and geolocation, attendance and QR check-in,
  payments and payouts, ratings, complaints, notifications, matching, admin
  console, Tamil/Hindi UI.** These were deliberately left out of this MVP.
- **Photo credits.** `public/assets/img/CREDITS.md` lists the CC BY / CC BY-SA
  photos used on opportunity cards. Attribution must be visible somewhere on the live
  site (a Credits/About page) or the photos must be replaced before a
  commercial launch.
- Two of the mobile app's photos are deliberately not used here: `warehouse.jpg`
  (Oxfam markings) and `delivery.jpg` (Grab branding). Replace them in the
  mobile bundle too.
- **HTTPS.** Turn it on before taking any real signups; the session cookie
  marks itself `Secure` automatically once HTTPS is detected.

---

## 7. Updating later

Re-upload the changed files (or the whole `public/` folder). Nothing to
migrate or build. If a future version adds tables, it will ship its own
`database/live/*.sql` file to import.

If you changed CSS, JS, icons, the manifest or `offline.html`, also bump
`const VERSION` in `public/sw.js` (for example `v1` → `v2`). The new worker
precaches the new assets, then deletes the old `swon-*` caches when it activates.
Visitors see a "Reload" card rather than being interrupted.

---

## 8. PWA checklist

- [ ] The site is served over **HTTPS** (service workers require it).
- [ ] `public/sw.js`, `public/manifest.webmanifest` and `public/offline.html`
      return `200` at the app root (or the subdirectory root).
- [ ] `.webmanifest` is served as `application/manifest+json`; `public/.htaccess`
      adds this plus no-cache headers (requires `mod_mime` / `mod_headers`).
- [ ] `PWA_ENABLED=true` in `.env`.
- [ ] `const VERSION` in `public/sw.js` is bumped for this release.
- [ ] After deploying, open the site once and check DevTools → Application →
      Manifest (no errors) and Service Workers (activated).
- [ ] Install on a real Android/desktop device; on iPhone use Share → Add to Home
      Screen.
- [ ] Stop the network and open a nested route; the SWON offline page must appear.
- [ ] Log out and confirm no private page is available offline.

Push notifications are **not** implemented. Requirements and open questions are
in `PWA-IMPLEMENTATION-REPORT.md`.
