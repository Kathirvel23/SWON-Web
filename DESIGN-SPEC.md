# SWON Web — Design Spec

**Version:** 2.0 (navy + mint — June 2026 mobile alignment) · **Status:** implemented in `public/assets/css/app.css`
**Source of truth for brand:** `C:\Users\kathi\WorkBridge` →
`Mobile App/src/constants/theme.ts`, `Documentation/UI_UX_DESIGN.md`
**Scope:** the PHP web MVP in this repository. Route paths, DB tables and API
identifiers are out of scope and never change for design reasons.

---

## 1. Principles

1. **One product, two surfaces.** The web is a port of the mobile V3
   "Vibrant & Dynamic" system — same palette, type, radii, shadows and wing motif.
2. **Clarity beats decoration.** Branded where it adds identity (hero, chips,
   status), standard where it adds clarity (Payment, Location, Date).
3. **The pay is the hero.** Money is the largest figure on a card after the title.
4. **Every state is designed.** Default, hover, focus, active, disabled, busy,
   empty, error. No state is left to the browser.
5. **Accessible by default.** Keyboard reachable, AA contrast, visible focus,
   reduced-motion respected.
6. **Mobile first, desktop complete.** 390px must be usable; 1440px must not
   feel stretched.

---

## 2. Tokens

All values live in `:root` in `public/assets/css/app.css`. Views never hardcode
a colour, radius, shadow or spacing value.

### Colour

| Role | Token | Value |
|---|---|---|
| Brand navy | `--primary` | `#0F1E3D` |
| Brand navy dark | `--primary-dark` | `#0A162D` |
| Brand deep (text/fill) | `--primary-deep` | `#0A162D` |
| Mint fill (selected) | `--primary-light` | `#E8FBF5` |
| Soft mint fill | `--tint` / `--tint-2` | `#F1FBF8` / `#E8FBF5` |
| Mint accent (FILL ONLY) | `--accent` | `#14B8A6` |
| Mint text/icon ink | `--accent-ink` | `#0F766E` |
| Canvas | `--canvas` | `#F3F6F9` |
| Border | `--border` | `#E4E7EC` |
| Border strong (controls) | `--border-strong` | `#7E8A9C` |
| Canvas | `--canvas` | `#f6f3ec` |
| Card | `--card` | `#ffffff` |
| Ink | `--ink` | `#10201e` |
| Secondary ink | `--muted` | `#4f5f5c` |
| Placeholder | `--placeholder` | `#5f6f6b` |
| Border / hairline | `--border` / `--hair` | `#e4ddd0` / `#ece7db` |
| Success | `--success` | `#15803d` |
| Warning | `--warning` | `#b45309` |
| Danger | `--danger` | `#b91c1c` |
| Info | `--info` | `#1d4ed8` |
| Accent (by meaning) | `--orange`, `--lav`, `--coral` | `#c2762b`, `#6d5fa3`, `#b91c1c` |

Semantic soft fills (`--success-soft`, `--warning-soft`, `--danger-soft`,
`--info-soft`, `--neutral-soft`, `--lav-soft`) and their line colours
(`--success-line`, `--warning-line`, `--danger-line`, `--info-line`,
`--mint-line`) must be used in pairs: **soft background + matching foreground +
matching line.**

**Contrast floor:** body and secondary text ≥ 4.5:1; large display text ≥ 3:1;
placeholder ≥ 4.5:1 (`#5f6f6b` on white ≈ 4.9:1). Never use colour alone to
carry meaning — status chips always include a text label.

### Type

| Role | Family | Size / line |
|---|---|---|
| Display / money | Bricolage Grotesque 800 | `h1 clamp(1.9–2.5rem)`, `h2 clamp(1.35–1.6rem)` |
| Body | Manrope 400–800 | `16px / 1.55` (15px ≤ 640px) |
| Small | Manrope | `.tiny` 12.8px, `.field-hint` 12.5px |
| Label | Manrope 800 | `.stat-label` 12px uppercase, `+0.09em` |
| Money | Bricolage 800 | `.pay` 21px, `.pay-hero` 37px, tabular numerals |

Rules: headings use `text-wrap: balance`; paragraphs use `text-wrap: pretty`.
Do not go below 12px for any user-facing text.

### Space, radius, elevation

- 4px grid: `4 / 8 / 12 / 16 / 24 / 32` (`--s1`–`--s6`), with utility classes.
- Radius: `8 / 12 / 14 / 18 / 24 / 30 / pill` (`--radius-sm` … `--radius-2xl`).
- Elevation: `--shadow-card`, `--shadow-lift`, `--shadow-btn`, `--shadow-pop`.
  Cards use `card`; hoverable cards may use `card + lift`; static cards never lift.

### Motion

`--t-fast: 140ms`, `--t-med: 220ms`, `--ease: cubic-bezier(.22,.8,.36,1)`.
All non-essential motion is removed under `prefers-reduced-motion: reduce`.
Hover transforms are removed on touch devices (`@media (hover: none)`).

---

## 3. Layout

| Breakpoint | Behaviour |
|---|---|
| ≥ 1081px | 3-column opportunity grid; 4-column stats; sticky pay card; 2-column hero |
| 901–1080px | 2-column grid; 2-column stats; single-column hero |
| 641–900px | Collapsible nav; 1-column auth; 2-column grid |
| ≤ 640px | 1-column grid; 1-column forms; stats 2-up (third spans 2); full-width list actions |

- Container: `max-width: 1200px`, 20px gutter.
- Page padding: `26px 0 56px` (18px top on mobile).
- Sticky topbar is 70px; the mobile nav dropdown starts at 70px.
- `:target` uses `scroll-margin-top: 92px` so anchors clear the topbar.

---

## 4. Components & states

### Button (`.btn-sw`)

| State | Rule |
|---|---|
| Default | primary fill, white text, 46px min height, `--shadow-btn` |
| Hover | `--primary-dark`, `translateY(-1px)` |
| Active | `translateY(1px) scale(.99)` |
| Focus | global `:focus-visible` 3px ring, 2px offset |
| Disabled | `--disabled-bg`, no shadow, `not-allowed` |
| Busy | `.is-busy` + `aria-busy="true"` → 14px spinner after the label |

Variants: `.btn-ghost` (card fill, border), `.btn-soft` (tint), `.btn-danger-soft`
(soft red), `.btn-sm` (38px), `.btn-lg-sw` (52px), `.btn-block`.

### Field (`.input`, `.select`, `.textarea`)

- 48px min height, 1.5px border, 12px radius, 16px-ish text.
- Hover: `--border-strong`; focus: primary border + 4px `--focus-ring`.
- Error: `.is-invalid` (danger border + `--danger-ring`) **and**
  `aria-invalid="true"` + `aria-describedby="<id>-error"` via `field_aria()`.
- Label is always visible; hint sits under the control; error replaces/augments
  the hint and has `id="<field>-error"`.

### Status chip (`.chip`)

Soft background + matching text + 1px line. Job statuses: draft, open, filled,
cancelled, completed, expired. Flight Request statuses: sent, shortlisted,
waitlisted, bonded, not selected, withdrawn, released. **The database enum is the
source; `status_label()` owns the display name.**

### Opportunity card (`.job-card`)

Media (photo or gradient tile) → category chip + urgency badge → title →
host mark + area → date/time pills → pay + seats → “View opportunity”.
Hover/focus-within lifts 3px; the whole card is not a link — the title and CTA are.

### Stat tile (`.stat`)

Static. No hover lift. Icon tile, uppercase label, display value, note.
Accent variants only recolour the icon: `accent-warm`, `accent-money`, `accent-ink`.

### List row (`.list-row`)

Static container. Border highlights on hover/focus-within only; no lift.
Actions wrap below the content at ≤ 640px.

### Empty state (`.empty`)

Dashed card, icon tile, heading, one explanatory sentence, one action.
Never a dead end.

### Flash message (`.flash`)

`aria-live="polite"` container; auto-dismiss after 6s (instant under reduced
motion). Types: success, error, info. Icons are decorative.

### Navigation

- Sticky, blurred topbar; active item uses `.active` **and**
  `aria-current="page"`.
- ≤ 900px: toggle with `aria-expanded` + `aria-controls`; closes on link click,
  Escape (focus returns to the toggle) and outside click.
- Skip link is the first tab stop and targets `#main-content`.

### Pagination (`.pager`)

40px targets, current page filled primary with `aria-current="page"`.

---

## 5. Accessibility acceptance criteria

A page ships only if all are true:

- [ ] Keyboard: every action reachable in DOM order; focus visible at all times.
- [ ] Skip link works and moves focus into `<main>`.
- [ ] One `<h1>`; heading levels never skip.
- [ ] Form errors: `aria-invalid`, `aria-describedby`, visible text.
- [ ] Active nav has `aria-current="page"`.
- [ ] Icon-only controls have an accessible name; decorative SVGs are `aria-hidden`.
- [ ] Contrast: body ≥ 4.5:1, large ≥ 3:1, placeholder ≥ 4.5:1.
- [ ] Status is never colour-only.
- [ ] Touch targets ≥ 44px (48px preferred).
- [ ] `prefers-reduced-motion` and `prefers-contrast` both respected.
- [ ] Zoom to 200% at 390px width causes no horizontal scroll.

---

## 6. Content & vocabulary

Use the mobile app's vocabulary in UI copy. Database/route identifiers do not change.

| Concept | UI term | Code keeps |
|---|---|---|
| Employer | **Host** | `employer`, `/employer` |
| Job | **Opportunity** | `job`, `/jobs` |
| Application | **Flight Request** | `application`, `/applications` |
| Hire | **Bond** | `HIRE`, `HIRED` |
| Crew | **Formation** | — |
| Worker community | **Flock** | — |
| Quick match | **FlashMatch** | `quickmatch` |
| KYC badge | **WhiteWing** | `kyc` |
| Reward points | **Feathers** | — |
| Reputation | **TrustScore** | — |

Tone: short, concrete, second person. Say what happens and what it costs. No
"click here". Money is always `₹` with no decimals (`₹900 / day`).

Location strings always read **Area, City** using the canonical names:
15 Coimbatore areas + 10 Tiruppur areas (`database/seed.sql`).

---

## 7. Data display

- **Money:** `money()` → `₹` + grouped rupees, no decimals. `pay_label()` adds
  `/ day`, `/ hour`, ` total`.
- **Dates:** `d M Y`; ranges use `–`; single-day ranges collapse to one date.
- **Times:** 12-hour with AM/PM; ranges use `–`.
- **Seats:** `X of Y bonded`; `N seats left`; `Fully staffed`.
- **Phones:** masked as `+91 ••••• ••1234` outside the owner’s own profile.
- **Counts:** never show a negative; clamp at 0.

---

## 8. Out of scope (deliberate)

- **Dark mode** — mobile renders light only today; the token layer is ready but
  no dark palette is shipped.
- **Self-hosted fonts** — Google Fonts CDN with `display=swap`; self-host before
  an offline-first deployment.
- **Skeleton loaders** — server-rendered pages; busy states cover the only waits.
- **Tamil / Hindi UI** — mobile has the string tables; the web is English-only
  for this MVP.

---

## 9. Design QA checklist

1. 390px, 768px, 1024px, 1440px — no overflow, no clipped text.
2. Tab through the page: focus ring visible on every control.
3. Submit every form empty: errors are visible, linked and announced.
4. Trigger a busy state: button shows spinner, is disabled, `aria-busy`.
5. Open the mobile nav: Escape closes it and focus returns to the toggle.
6. Check all six statuses render with the right chip pair.
7. Toggle OS “reduce motion”: no card entrance, no hover lift.
8. Toggle OS “increase contrast”: text and borders darken.
9. Run `php -l` on all PHP files.
10. Confirm zero inline `style=` attributes in `app/views` (dynamic background
    image on the auth panel is the only allowed exception).


---

## 2b. Rules inherited from the mobile app (v2)

1. **Mint is a fill, never text.** White on `#14B8A6` is 2.49:1. Text and icons in the mint family
   use `--accent-ink` `#0F766E`. Primary buttons are navy.
2. **Controls use `--border-strong`.** `--border` `#E4E7EC` on canvas is 1.14:1 — below the 3:1
   needed for a control edge.
3. **Money preserves paise.** `money()` in `app/helpers.php` renders `₹450` / `₹450.50` /
   `₹1,250.75` — never fake `.00`, and never float division. Views must call `money()` rather than
   formatting rupees themselves.
4. **Terms:** "Bond" is retired from all user-facing copy (it is "Hired" / "Hiring"). Enum values,
   DB columns and API fields are unchanged.
5. **Display type is Quicksand 700/600; body is Manrope.** The 8-step scale is
   10 / 12 / 13 / 14 / 16 / 18 / 22 / 28 px.
6. **Every list has four designed states:** loading (`.skeleton`), empty (`.empty-state`),
   error (`.error-card`, retryable), and populated. An empty state must never appear while loading.
