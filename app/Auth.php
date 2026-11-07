<?php
declare(strict_types=1);

namespace App;

/**
 * Session-backed authentication. PHP session cookie is the only credential
 * the browser holds; no tokens are exposed to JavaScript.
 */
final class Auth
{
    private static ?array $user = null;
    private static bool $loaded = false;

    /** @param array<string, mixed> $account */
    public static function login(array $account): void
    {
        session_regenerate_id(true);
        $_SESSION['account_id']  = (int) $account['id'];
        $_SESSION['last_active'] = time();
        self::$user              = null;
        self::$loaded            = false;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $params['path'],
                'domain'   => $params['domain'],
                'secure'   => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => 'Lax',
            ]);
        }
        session_destroy();
        self::$user   = null;
        self::$loaded = false;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    /** @return array<string, mixed>|null */
    public static function user(): ?array
    {
        if (self::$loaded) {
            return self::$user;
        }
        self::$loaded = true;

        $id = $_SESSION['account_id'] ?? null;
        if (!is_int($id) && !is_numeric($id)) {
            return null;
        }

        $row = Database::one(
            'SELECT a.id, a.public_id, a.phone, a.role, a.status, a.onboarded_at,
                    wp.full_name, wp.skills, wp.availability, wp.about AS worker_about,
                    wp.area_id AS worker_area_id,
                    ep.org_name, ep.contact_name, ep.about AS employer_about,
                    ep.area_id AS employer_area_id
               FROM account a
               LEFT JOIN worker_profile wp ON wp.account_id = a.id
               LEFT JOIN employer_profile ep ON ep.account_id = a.id
              WHERE a.id = ?',
            [(int) $id]
        );

        if ($row === null || $row['status'] !== 'ACTIVE' && $row['status'] !== 'PENDING') {
            return null;
        }

        $row['display_name'] = $row['role'] === 'employer'
            ? ($row['org_name'] ?: 'Employer')
            : ($row['full_name'] ?: 'Worker');
        $row['area_id'] = $row['role'] === 'employer'
            ? $row['employer_area_id']
            : $row['worker_area_id'];

        self::$user = $row;

        return self::$user;
    }

    public static function id(): ?int
    {
        $user = self::user();

        return $user === null ? null : (int) $user['id'];
    }

    public static function role(): ?string
    {
        $user = self::user();

        return $user === null ? null : (string) ($user['role'] ?? '');
    }

    public static function isWorker(): bool
    {
        return self::role() === 'worker';
    }

    public static function isEmployer(): bool
    {
        return self::role() === 'employer';
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            $_SESSION['intended'] = $_SERVER['REQUEST_URI'] ?? '/';
            flash('info', 'Please sign in to continue.');
            redirect('/login');
        }
    }

    /** Role gate: unauthenticated -> login, unselected -> role picker, wrong role -> own home. */
    public static function requireRole(string $role): void
    {
        self::requireLogin();
        $user = self::user();

        if (empty($user['role'])) {
            redirect('/role');
        }
        if ($user['role'] !== $role) {
            redirect(self::homeFor((string) $user['role']));
        }
        if (empty($user['onboarded_at'])) {
            redirect('/onboarding');
        }
    }

    public static function requireGuest(): void
    {
        if (self::check()) {
            redirect(self::homeFor(self::role()));
        }
    }

    public static function homeFor(?string $role): string
    {
        return match ($role) {
            'worker'   => '/worker',
            'employer' => '/employer',
            default    => '/role',
        };
    }
}
