<?php
declare(strict_types=1);

use App\Auth;
use App\Env;
use App\View;

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/Env.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file     = BASE_PATH . '/app/' . $relative . '.php';
    if (is_file($file)) {
        require $file;
    }
});

Env::load(BASE_PATH . '/.env');
date_default_timezone_set('Asia/Kolkata');

/** Subdirectory the front controller lives in ('' at web root). */
define('BASE_URL', (static function (): string {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $dir    = rtrim(str_replace('\\', '/', dirname($script)), '/');

    // The front controller sits in public/. When the document root is the
    // project root (shared hosting that cannot move it), Apache rewrites into
    // public/ and PHP reports SCRIPT_NAME as /public/index.php — but the site
    // is still served from the domain root, so the canonical base is ''.
    // Same for a project inside a subfolder (/mysite/public -> /mysite).
    if (str_ends_with($dir, '/public')) {
        $dir = substr($dir, 0, -strlen('/public'));
    }

    return ($dir === '.' || $dir === '/') ? '' : $dir;
})());

require BASE_PATH . '/app/helpers.php';

// --------------------------------------------------------- security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Content-Type: text/html; charset=UTF-8');

// ---------------------------------------------------------------- session
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.gc_maxlifetime', '7200');

$isSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

session_name(Env::get('SESSION_NAME', 'swon_session'));
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => BASE_URL === '' ? '/' : BASE_URL . '/',
    'domain'   => '',
    'secure'   => $isSecure,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// Idle timeout (2 hours).
if (isset($_SESSION['account_id']) && time() - (int) ($_SESSION['last_active'] ?? time()) > 7200) {
    Auth::logout();
    session_start();
    flash('info', 'Your session expired. Please sign in again.');
} elseif (isset($_SESSION['account_id'])) {
    $_SESSION['last_active'] = time();
}

// ----------------------------------------------------------- error policy
$appEnv = Env::get('APP_ENV', 'local');
error_reporting(E_ALL);
ini_set('display_errors', $appEnv === 'local' ? '1' : '0');

set_exception_handler(static function (Throwable $e) use ($appEnv): void {
    $logDir = BASE_PATH . '/storage/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }
    @file_put_contents(
        $logDir . '/app.log',
        '[' . date('c') . '] ' . $e::class . ': ' . $e->getMessage()
        . ' in ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL,
        FILE_APPEND
    );

    http_response_code(500);
    $message = $appEnv === 'local'
        ? $e->getMessage()
        : 'Something went wrong. Please try again.';

    try {
        View::render('errors/500', ['title' => 'Error', 'message' => $message], 'layouts/plain');
    } catch (Throwable) {
        echo 'Something went wrong.';
    }
});
