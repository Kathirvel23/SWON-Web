<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database;
use App\Env;
use App\Repositories\AccountRepository;
use App\Repositories\CatalogRepository;
use App\Validator;
use App\View;
use PDOException;

final class AuthController
{
    private AccountRepository $accounts;
    private CatalogRepository $catalog;

    public function __construct()
    {
        $this->accounts = new AccountRepository();
        $this->catalog  = new CatalogRepository();
    }

    public function showLogin(array $params = []): void
    {
        if (Auth::check()) {
            redirect(Auth::homeFor(Auth::role()));
        }

        View::render('auth/login', [
            'title'   => 'Sign in',
            'devMode' => Env::bool('OTP_DEV_MODE', false),
            'otpBypass' => $this->otpBypass(),
        ], 'layouts/auth');
    }

    public function requestOtp(array $params = []): void
    {
        if (Auth::check()) {
            redirect(Auth::homeFor(Auth::role()));
        }

        $rawPhone = trim((string) ($_POST['phone'] ?? ''));
        $phone    = Validator::normalizePhone($rawPhone);

        $errors = Validator::validate(['phone' => $phone], ['phone' => 'required|phone']);
        if ($errors !== []) {
            View::render('auth/login', [
                'title'   => 'Sign in',
                'errors'  => $errors,
                'old'     => ['phone' => $rawPhone],
                'devMode' => Env::bool('OTP_DEV_MODE', false),
            'otpBypass' => $this->otpBypass(),
            ], 'layouts/auth');

            return;
        }

        if ($this->otpBypass()) {
            $this->loginDirect($phone);

            return;
        }

        $error = $this->issueOtp($phone);
        if ($error !== null) {
            View::render('auth/login', [
                'title'   => 'Sign in',
                'errors'  => ['phone' => $error],
                'old'     => ['phone' => $rawPhone],
                'devMode' => Env::bool('OTP_DEV_MODE', false),
            'otpBypass' => $this->otpBypass(),
            ], 'layouts/auth');

            return;
        }

        redirect('/otp');
    }

    public function resendOtp(array $params = []): void
    {
        if ($this->otpBypass()) {
            redirect('/login');
        }

        $phone = (string) ($_SESSION['otp_phone'] ?? '');
        if ($phone === '') {
            redirect('/login');
        }

        $error = $this->issueOtp($phone);
        if ($error !== null) {
            flash('error', $error);
        } else {
            flash('success', 'A new code has been sent.');
        }

        redirect('/otp');
    }

    /**
     * Test-mode sign-in without OTP (OTP_ENABLED=false, the default).
     * Creates the account when needed, then goes to role selection.
     */
    private function loginDirect(string $phone): void
    {
        $account = $this->accounts->findByPhone($phone);
        if ($account === null) {
            try {
                $id      = $this->accounts->create($phone);
                $account = $this->accounts->findById($id);
            } catch (PDOException $e) {
                $account = $this->accounts->findByPhone($phone);
                if ($account === null) {
                    throw $e;
                }
            }
        }

        if ($account === null) {
            abort(500, 'Could not create your account. Please try again.');
        }
        if ($account['status'] === 'SUSPENDED' || $account['status'] === 'CLOSED') {
            flash('error', 'This account cannot sign in. Contact support.');
            redirect('/login');
        }

        $this->accounts->touchLogin((int) $account['id']);
        Auth::login($account);
        unset($_SESSION['otp_phone'], $_SESSION['dev_otp'], $_SESSION['otp_delivery']);

        if (empty($account['role'])) {
            redirect('/role');
        }
        if (empty($account['onboarded_at'])) {
            redirect('/onboarding');
        }

        redirect(Auth::homeFor((string) $account['role']));
    }

    private function otpBypass(): bool
    {
        // OTP is OFF by default in this test build. Set OTP_ENABLED=true
        // in .env to require the OTP page again.
        return !Env::bool('OTP_ENABLED', false);
    }

    /** Returns an error message, or null when a challenge was created. */
    private function issueOtp(string $phone): ?string
    {
        $cooldown = Env::int('OTP_COOLDOWN_SECONDS', 60);
        $ttl      = Env::int('OTP_TTL_SECONDS', 120);
        $maxPer15 = Env::int('OTP_MAX_PER_15MIN', 5);

        $last = Database::one(
            'SELECT cooldown_until FROM otp_challenge WHERE phone = ? ORDER BY id DESC LIMIT 1',
            [$phone]
        );
        if ($last !== null) {
            $remaining = strtotime((string) $last['cooldown_until']) - time();
            if ($remaining > 0) {
                return 'Please wait ' . $remaining . ' seconds before requesting another code.';
            }
        }

        $recent = (int) Database::scalar(
            'SELECT COUNT(*) FROM otp_challenge WHERE phone = ? AND created_at > (NOW() - INTERVAL 15 MINUTE)',
            [$phone]
        );
        if ($recent >= $maxPer15) {
            return 'Too many codes requested. Please try again in 15 minutes.';
        }

        $code = (string) random_int(100000, 999999);

        Database::insert('otp_challenge', [
            'phone'          => $phone,
            'code_hash'      => hash_hmac('sha256', $code, (string) Env::get('APP_KEY', 'swon')),
            'attempts'       => 0,
            'expires_at'     => date('Y-m-d H:i:s', time() + $ttl),
            'cooldown_until' => date('Y-m-d H:i:s', time() + $cooldown),
        ]);

        $_SESSION['otp_phone'] = $phone;

        // How the code reaches the person asking for it.
        //   auto   (default) - on screen for allow-listed/dev numbers, otherwise the log file
        //   screen           - always on screen (same as the old OTP_DEV_MODE=true)
        //   log              - written to storage/logs/otp.log, never shown
        //   sms              - not integrated yet; falls back to the log file so a code is never lost
        $allowListed = $this->isAllowListedPhone($phone);
        $showOnScreen = Env::bool('OTP_DEV_MODE', false) || $allowListed;
        $mode = strtolower(trim((string) Env::get('OTP_DELIVERY', 'auto')));
        $note = '';

        if ($mode === 'sms') {
            $mode = 'log';
            $note = 'OTP_DELIVERY=sms but no SMS provider is configured yet';
        }

        if ($mode === 'auto') {
            $mode = $showOnScreen ? 'screen' : 'log';
        }

        if ($mode === 'log') {
            $this->appendOtpLog($phone, $code, $note);
        }

        if ($mode === 'screen' || $showOnScreen) {
            $_SESSION['dev_otp'] = [
                'phone'   => $phone,
                'code'    => $code,
                'expires' => time() + $ttl,
            ];
        }

        // Remember how this code was delivered, so the verification screen
        // knows whether it may show it (allow-listed numbers included).
        $_SESSION['otp_delivery'] = ($mode === 'screen' || $showOnScreen) ? 'screen' : 'log';

        return null;
    }

    /**
     * Numbers listed in OTP_DEV_PHONES get their code on screen even when
     * OTP_DEV_MODE is off — the owner can sign in on the live site without
     * opening the door for everyone else.
     */
    private function isAllowListedPhone(string $phone): bool
    {
        $raw = trim((string) Env::get('OTP_DEV_PHONES', ''));
        if ($raw === '') {
            return false;
        }

        $mine = substr(preg_replace('/\D+/', '', $phone) ?? '', -10);
        if ($mine === '') {
            return false;
        }

        foreach (preg_split('/[\s,;]+/', $raw) ?: [] as $candidate) {
            $digits = substr(preg_replace('/\D+/', '', $candidate) ?? '', -10);
            if ($digits !== '' && hash_equals($digits, $mine)) {
                return true;
            }
        }

        return false;
    }

    /** Append-only record of issued codes, for when no SMS provider exists yet. */
    private function appendOtpLog(string $phone, string $code, string $note = ''): void
    {
        $dir = BASE_PATH . '/storage/logs';
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return;
        }

        $file = $dir . '/otp.log';
        if (is_file($file) && (int) filesize($file) > 200_000) {
            @rename($file, $file . '.1');
        }

        $line = sprintf(
            "[%s] +91%s code %s%s\n",
            date('Y-m-d H:i:s'),
            substr(preg_replace('/\D+/', '', $phone) ?? '', -10),
            $code,
            $note === '' ? '' : '  (' . $note . ')'
        );

        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    public function showOtp(array $params = []): void
    {
        if (Auth::check()) {
            redirect(Auth::homeFor(Auth::role()));
        }

        if ($this->otpBypass()) {
            redirect('/login');
        }

        $phone = (string) ($_SESSION['otp_phone'] ?? '');
        if ($phone === '') {
            redirect('/login');
        }

        $this->renderOtp($phone);
    }

    private function renderOtp(string $phone, array $errors = []): void
    {
        $dev = null;
        $delivery = (string) ($_SESSION['otp_delivery'] ?? 'log');
        if ($delivery === 'screen') {
            $candidate = $_SESSION['dev_otp'] ?? null;
            if (is_array($candidate) && ($candidate['phone'] ?? '') === $phone && (int) ($candidate['expires'] ?? 0) > time()) {
                $dev = $candidate;
            }
        }

        View::render('auth/otp', [
            'title'  => 'Verify your number',
            'phone'  => $phone,
            'dev'    => $dev,
            'errors' => $errors,
        ], 'layouts/auth');
    }

    public function verifyOtp(array $params = []): void
    {
        if ($this->otpBypass()) {
            redirect('/login');
        }

        $phone = (string) ($_SESSION['otp_phone'] ?? '');
        $code  = trim((string) ($_POST['code'] ?? ''));

        if ($phone === '') {
            redirect('/login');
        }

        $challenge = Database::one(
            'SELECT * FROM otp_challenge WHERE phone = ? AND consumed_at IS NULL ORDER BY id DESC LIMIT 1',
            [$phone]
        );

        if ($challenge === null) {
            $this->renderOtp($phone, ['code' => 'No active code. Please request a new one.']);

            return;
        }
        if (strtotime((string) $challenge['expires_at']) < time()) {
            $this->renderOtp($phone, ['code' => 'That code expired. Please request a new one.']);

            return;
        }

        $maxAttempts = Env::int('OTP_MAX_ATTEMPTS', 5);
        if ((int) $challenge['attempts'] >= $maxAttempts) {
            Database::run('UPDATE otp_challenge SET consumed_at = NOW() WHERE id = ?', [$challenge['id']]);
            $this->renderOtp($phone, ['code' => 'Too many attempts. Please request a new code.']);

            return;
        }

        Database::run('UPDATE otp_challenge SET attempts = attempts + 1 WHERE id = ?', [$challenge['id']]);

        $expected = hash_hmac('sha256', $code, (string) Env::get('APP_KEY', 'swon'));
        if (!hash_equals((string) $challenge['code_hash'], $expected)) {
            $remaining = $maxAttempts - ((int) $challenge['attempts'] + 1);
            $message   = $remaining > 0
                ? 'Incorrect code. ' . $remaining . ' attempt' . ($remaining === 1 ? '' : 's') . ' left.'
                : 'Too many attempts. Please request a new code.';

            if ($remaining <= 0) {
                Database::run('UPDATE otp_challenge SET consumed_at = NOW() WHERE id = ?', [$challenge['id']]);
            }

            $this->renderOtp($phone, ['code' => $message]);

            return;
        }

        Database::run('UPDATE otp_challenge SET consumed_at = NOW() WHERE id = ?', [$challenge['id']]);

        $account = $this->accounts->findByPhone($phone);
        if ($account === null) {
            try {
                $id      = $this->accounts->create($phone);
                $account = $this->accounts->findById($id);
            } catch (PDOException $e) {
                // Concurrent first login for the same phone: use the winner's row.
                $account = $this->accounts->findByPhone($phone);
                if ($account === null) {
                    throw $e;
                }
            }
        }

        if ($account === null) {
            abort(500, 'Could not create your account. Please try again.');
        }
        if ($account['status'] === 'SUSPENDED' || $account['status'] === 'CLOSED') {
            $this->renderOtp($phone, ['code' => 'This account cannot sign in. Contact support.']);

            return;
        }

        $this->accounts->touchLogin((int) $account['id']);
        Auth::login($account);
        unset($_SESSION['otp_phone'], $_SESSION['dev_otp'], $_SESSION['otp_delivery']);

        if (empty($account['role'])) {
            redirect('/role');
        }
        if (empty($account['onboarded_at'])) {
            redirect('/onboarding');
        }

        redirect(Auth::homeFor((string) $account['role']));
    }

    public function showRole(array $params = []): void
    {
        Auth::requireLogin();
        $user = Auth::user();

        if (!empty($user['role'])) {
            redirect(empty($user['onboarded_at']) ? '/onboarding' : Auth::homeFor((string) $user['role']));
        }

        View::render('auth/role', ['title' => 'Choose how you will use SWON'], 'layouts/auth');
    }

    public function chooseRole(array $params = []): void
    {
        Auth::requireLogin();
        $user = Auth::user();

        if (!empty($user['role'])) {
            redirect(empty($user['onboarded_at']) ? '/onboarding' : Auth::homeFor((string) $user['role']));
        }

        $role = (string) ($_POST['role'] ?? '');
        if (!in_array($role, ['worker', 'employer'], true)) {
            View::render('auth/role', [
                'title'  => 'Choose how you will use SWON',
                'errors' => ['role' => 'Please choose Worker or Host.'],
            ], 'layouts/auth');

            return;
        }

        $this->accounts->setRole((int) $user['id'], $role);

        redirect('/onboarding');
    }

    public function showOnboarding(array $params = []): void
    {
        Auth::requireLogin();
        $user = Auth::user();

        if (empty($user['role'])) {
            redirect('/role');
        }
        if (!empty($user['onboarded_at'])) {
            redirect(Auth::homeFor((string) $user['role']));
        }

        View::render('auth/onboarding', [
            'title'      => 'Complete your profile',
            'user'       => $user,
            'areas'      => $this->catalog->areas(),
            'errors'     => [],
            'old'        => [],
        ], 'layouts/auth');
    }

    public function onboard(array $params = []): void
    {
        Auth::requireLogin();
        $user = Auth::user();

        if (empty($user['role'])) {
            redirect('/role');
        }
        if (!empty($user['onboarded_at'])) {
            redirect(Auth::homeFor((string) $user['role']));
        }

        $role   = (string) $user['role'];
        $isWork = $role === 'worker';

        $rules = $isWork
            ? [
                'full_name'    => 'required|min:2|max:160',
                'area_id'      => 'required|int',
                'availability' => 'required|in:FULL_TIME,PART_TIME,WEEKENDS,FLEXIBLE',
                'skills'       => 'max:255',
                'about'        => 'max:600',
            ]
            : [
                'org_name'     => 'required|min:2|max:160',
                'contact_name' => 'max:160',
                'area_id'      => 'required|int',
                'about'        => 'max:600',
            ];

        $errors = Validator::validate($_POST, $rules);

        $areaId = (int) ($_POST['area_id'] ?? 0);
        if ($areaId > 0 && !$this->catalog->areaExists($areaId)) {
            $errors['area_id'] = 'Please choose a valid area.';
        }

        if ($errors !== []) {
            View::render('auth/onboarding', [
                'title'  => 'Complete your profile',
                'user'   => $user,
                'areas'  => $this->catalog->areas(),
                'errors' => $errors,
                'old'    => $_POST,
            ], 'layouts/auth');

            return;
        }

        if ($isWork) {
            $this->accounts->createWorkerProfile((int) $user['id'], [
                'full_name'    => trim((string) $_POST['full_name']),
                'area_id'      => $areaId,
                'skills'       => trim((string) ($_POST['skills'] ?? '')),
                'availability' => (string) $_POST['availability'],
                'about'        => trim((string) ($_POST['about'] ?? '')),
            ]);
        } else {
            $this->accounts->createEmployerProfile((int) $user['id'], [
                'org_name'     => trim((string) $_POST['org_name']),
                'contact_name' => trim((string) ($_POST['contact_name'] ?? '')),
                'area_id'      => $areaId,
                'about'        => trim((string) ($_POST['about'] ?? '')),
            ]);
        }

        $this->accounts->markOnboarded((int) $user['id']);
        flash('success', 'Your profile is ready. Welcome to SWON!');

        redirect(Auth::homeFor($role));
    }

    public function logout(array $params = []): void
    {
        Auth::logout();
        session_start();
        session_regenerate_id(true);
        flash('success', 'You have been signed out.');

        redirect('/login');
    }
}
