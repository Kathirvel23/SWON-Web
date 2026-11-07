<?php
declare(strict_types=1);

use App\Auth;
use App\Csrf;
use App\Env;
use App\View;

/**
 * Global view/controller helpers. Deliberately small and explicit.
 */

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = '/'): string
{
    if (preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }

    $path = '/' . ltrim($path, '/');

    return BASE_URL . ($path === '/' ? '/' : $path);
}

function asset(string $path): string
{
    return url('/assets/' . ltrim($path, '/'));
}

function redirect(string $path): never
{
    header('Location: ' . url($path), true, 302);
    exit;
}

function back(): never
{
    $ref = $_SERVER['HTTP_REFERER'] ?? url('/');
    header('Location: ' . $ref, true, 302);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

/** @return list<array{type: string, message: string}> */
function flashes(): array
{
    $items = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);

    return $items;
}

function csrf_field(): string
{
    return Csrf::field();
}

/**
 * ARIA wiring for a form control that has a server-side error.
 * The matching error element must use id="<field>-error".
 *
 *   <input ... <?= field_aria($errors, 'title', 'title') ?>>
 *   <span class="field-error" id="title-error">...</span>
 */
function field_aria(array $errors, string $key, string $id): string
{
    return isset($errors[$key])
        ? ' aria-invalid="true" aria-describedby="' . e($id) . '-error"'
        : '';
}

/** @return array<string, mixed>|null */
function auth_user(): ?array
{
    return Auth::user();
}

/**
 * Paise -> display string. Preserves paise when they exist and never shows
 * fake decimals: 45000 -> ₹450, 45050 -> ₹450.50, 125075 -> ₹1,250.75.
 * Integer arithmetic only — no float division, so nothing is lost to rounding.
 */
function money(int|string|null $paise): string
{
    $value = (int) $paise;
    $sign  = $value < 0 ? '-' : '';
    $abs   = abs($value);

    $formatted = number_format(intdiv($abs, 100), 0, '.', ',');

    $coins = $abs % 100;
    if ($coins !== 0) {
        $formatted .= '.' . str_pad((string) $coins, 2, '0', STR_PAD_LEFT);
    }

    return $sign . '₹' . $formatted;
}

function pay_label(int|string $paise, string $type): string
{
    $suffix = match ($type) {
        'HOUR'  => ' / hour',
        'TOTAL' => ' total',
        default => ' / day',
    };

    return money($paise) . $suffix;
}

/** Bare pay unit, for layouts that print the amount as a hero figure. */
function pay_unit(string $type): string
{
    return match ($type) {
        'HOUR'  => '/ hour',
        'TOTAL' => 'total',
        default => '/ day',
    };
}

/**
 * Category photo for a job card, or null when the app ships a designed tile.
 * Only photos without third-party branding are listed — see assets/img/CREDITS.md.
 */
function category_photo(string $slug): ?string
{
    return match ($slug) {
        'event-crew'   => 'img/jobs/event',
        'hospitality'  => 'img/jobs/hospitality',
        'construction' => 'img/jobs/construction',
        'housekeeping' => 'img/jobs/housekeeping',
        default         => null,
    };
}

/** Icon used on the branded tile when a category has no photo. */
function category_icon(string $slug): string
{
    return match ($slug) {
        'event-crew'    => 'sparkles',
        'hospitality'   => 'utensils',
        'logistics'     => 'truck',
        'retail'        => 'tag',
        'construction'  => 'helmet',
        'housekeeping'  => 'broom',
        'promotions'    => 'megaphone',
        'warehouse'     => 'box',
        'manufacturing' => 'factory',
        'healthcare'    => 'heart',
        'security'      => 'shield',
        'transport'     => 'truck',
        default         => 'briefcase',
    };
}

function status_class(string $status): string
{
    return 'chip-' . strtolower($status);
}

/**
 * User-facing status names. SWON vocabulary (mobile app i18n):
 * Job → Opportunity, Application → Flight Request, Hiring → Hired.
 * Database enum values never change.
 */
function status_label(string $status): string
{
    return match (strtoupper($status)) {
        'APPLIED'     => 'Sent',
        'SHORTLISTED' => 'Shortlisted',
        'WAITLISTED'  => 'Waitlisted',
        'HIRED'       => 'Hired',
        'REJECTED'    => 'Not selected',
        'WITHDRAWN'   => 'Withdrawn',
        'RELEASED'    => 'Released',
        'OPEN'        => 'Open for Flight Requests',
        'DRAFT'       => 'Draft',
        'FILLED'      => 'Fully staffed',
        'CANCELLED'   => 'Cancelled',
        'COMPLETED'   => 'Completed',
        'EXPIRED'     => 'Expired',
        default       => ucwords(strtolower(str_replace('_', ' ', $status))),
    };
}

function format_date(?string $date): string
{
    if (!$date) {
        return '—';
    }
    $ts = strtotime($date);

    return $ts ? date('d M Y', $ts) : '—';
}

function format_time(?string $time): string
{
    if (!$time) {
        return '—';
    }
    $ts = strtotime($time);

    return $ts ? date('g:i A', $ts) : '—';
}

function format_range(?string $start, ?string $end): string
{
    if (!$start && !$end) {
        return '—';
    }
    if ($start === $end) {
        return format_date($start);
    }

    return format_date($start) . ' – ' . format_date($end);
}

function days_between(?string $start, ?string $end): int
{
    if (!$start || !$end) {
        return 0;
    }
    $a = strtotime($start);
    $b = strtotime($end);
    if ($a === false || $b === false) {
        return 0;
    }

    return max(1, (int) floor(($b - $a) / 86400) + 1);
}

function mask_phone(string $phone): string
{
    if (strlen($phone) < 4) {
        return $phone;
    }

    return '+91 ••••• ' . substr($phone, -4);
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $out   = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $out .= mb_strtoupper(mb_substr($part, 0, 1));
    }

    return $out !== '' ? $out : 'S';
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** Current request path with the deployment subdirectory removed. */
function current_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if (BASE_URL !== '' && str_starts_with($path, BASE_URL)) {
        $path = substr($path, strlen(BASE_URL));
    }

    return '/' . trim($path, '/');
}

/** Opaque, URL-safe identifier used in routes instead of numeric ids. */
function gen_public_id(string $prefix = ''): string
{
    return ($prefix !== '' ? $prefix . '_' : '') . bin2hex(random_bytes(8));
}

/** Inline SVG icon set (stroke icons, no icon font dependency). */
function icon(string $name, string $class = 'icon'): string
{
    $paths = [
        'briefcase' => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/><path d="M3 12h18"/>',
        'truck'     => '<path d="M3 7h11v9H3z"/><path d="M14 10h4l3 3v3h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
        'box'       => '<path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z"/><path d="M4 7.5 12 12l8-4.5M12 12v9"/>',
        'factory'   => '<path d="M3 21V9l5 3V9l5 3V9l5 3v9H3Z"/><path d="M7 21v-4M12 21v-4M17 21v-4"/>',
        'heart'     => '<path d="M20.8 5.6a5 5 0 0 0-7.1 0L12 7.3l-1.7-1.7a5 5 0 0 0-7.1 7.1L12 21l8.8-8.3a5 5 0 0 0 0-7.1Z"/>',
        'tag'       => '<path d="M20.6 12.6 12 21.2l-8.2-8.2V4h9Z"/><circle cx="16" cy="8" r="1.6"/>',
        'megaphone' => '<path d="M3 11v2l4 1 9 4V6L7 10Z"/><path d="M7 14v5"/><path d="M19 10.5a3 3 0 0 1 0 3"/>',
        'utensils'  => '<path d="M5 3v7a2 2 0 0 0 4 0V3"/><path d="M7 10v11"/><path d="M17 3c-1.7 1-2.5 2.7-2.5 5S15.3 12 17 13v8"/>',
        'helmet'    => '<path d="M4 15a8 8 0 0 1 16 0"/><path d="M9 8.5V6a3 3 0 0 1 6 0v2.5"/><path d="M3 15h18v2H3z"/>',
        'broom'     => '<path d="M14 3 8 9"/><path d="m10 7 7 7-4 5-7-7z"/><path d="M13 19l-1 3M16 16l3 3"/>',
        'sparkle'   => '<path d="m12 3 1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3Z"/>',
        'search'    => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.2-3.2"/>',
        'pin'       => '<path d="M12 21s7-5.5 7-11a7 7 0 1 0-14 0c0 5.5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/>',
        'calendar'  => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/>',
        'clock'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'users'     => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'plus'      => '<path d="M12 5v14M5 12h14"/>',
        'arrow'     => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'check'     => '<path d="m5 12 5 5L20 7"/>',
        'x'         => '<path d="M6 6l12 12M18 6 6 18"/>',
        'logout'    => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>',
        'user'      => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'sparkles'  => '<path d="m12 3 1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3Z"/><path d="M19 15l.9 2.1L22 18l-2.1.9L19 21l-.9-2.1L16 18l2.1-.9L19 15Z"/>',
        'shield'    => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/>',
        'menu'      => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'grid'      => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'file'      => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z"/><path d="M14 3v5h5"/><path d="M9 13h6M9 17h6"/>',
        'inbox'     => '<path d="M4 4h16v16H4z"/><path d="M4 13h4l2 3h4l2-3h4"/>',
        'alert'     => '<path d="M12 3 2 21h20L12 3Z"/><path d="M12 9v5M12 17.5v.5"/>',
        'info'      => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 7.5v.5"/>',
        'chevron'   => '<path d="m9 6 6 6-6 6"/>',
        'bell'      => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/>',
        'download'  => '<path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/>',
        'wifi-off'  => '<path d="m2 2 20 20"/><path d="M8.5 8.5A6 6 0 0 1 12 7c2.5 0 4.7 1 6.4 2.6"/><path d="M5 12.5a10 10 0 0 1 2.2-1.4"/><path d="M8.5 16.5a5 5 0 0 1 7 0"/><path d="M12 20h.01"/>',
        'share'     => '<path d="M12 3v12"/><path d="m8 7 4-4 4 4"/><path d="M5 13v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6"/>',
        'refresh'   => '<path d="M21 12a9 9 0 1 1-2.6-6.4"/><path d="M21 3v6h-6"/>',
    ];

    $path = $paths[$name] ?? $paths['info'];

    return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
        . 'stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . $path . '</svg>';
}

function client_ip(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

function abort(int $status, string $message = 'Something went wrong.'): never
{
    http_response_code($status);
    View::render('errors/500', ['title' => 'Error', 'message' => $message], 'layouts/plain');
    exit;
}
