<?php
declare(strict_types=1);

/**
 * Router for the PHP built-in server:
 *   php -S 127.0.0.1:8080 -t public public/router.php
 * Static files are served directly; everything else goes to the front controller.
 */
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . $path;

// Serve the static grand-opening page at the site root under `php -S`
// (the built-in server does not apply Apache's DirectoryIndex here).
if ($path === '/' && is_file(__DIR__ . '/index.html')) {
    header('Content-Type: text/html; charset=UTF-8');
    readfile(__DIR__ . '/index.html');
    return true;
}

// The PHP built-in server does not know the .webmanifest MIME type.
if ($path === '/manifest.webmanifest' && is_file($file)) {
    header('Content-Type: application/manifest+json; charset=utf-8');
    readfile($file);
    return true;
}

if ($path !== '/' && is_file($file)) {
    return false;
}

require __DIR__ . '/index.php';
