<?php
declare(strict_types=1);

use App\Csrf;
use App\Router;
use App\View;

/* Grand-opening page is the front door.
   Serving it here means it works even when the host ignores .htaccess/DirectoryIndex
   (which is why "/" was showing the app landing page instead of the countdown).
   Remove this block — or delete public/index.html — once the launch is over. */
$gatePath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$docRoot  = rtrim(str_replace('\\', '/', (string) ($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
$gateBase = $docRoot !== '' && str_starts_with(str_replace('\\', '/', __DIR__), $docRoot)
    ? rtrim(substr(str_replace('\\', '/', __DIR__), strlen($docRoot)), '/')
    : '';
if (in_array($gatePath, [$gateBase . '/', $gateBase, '', '/'], true) && is_file(__DIR__ . '/index.html')) {
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store');
    readfile(__DIR__ . '/index.html');
    return;
}

require __DIR__ . '/../app/bootstrap.php';

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if (BASE_URL !== '' && str_starts_with($uri, BASE_URL)) {
    $uri = substr($uri, strlen(BASE_URL));
}
$uri = '/' . ltrim($uri, '/');

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if ($method === 'HEAD') {
    $method = 'GET';
}

// Every state-changing request must carry the session CSRF token.
if ($method === 'POST' && !Csrf::check($_POST['_csrf'] ?? null)) {
    http_response_code(419);
    View::render('errors/419', ['title' => 'Session expired'], 'layouts/plain');
    exit;
}

/** @var Router $router */
$router = require __DIR__ . '/../app/routes.php';
$router->dispatch($method, $uri);
