<?php
declare(strict_types=1);

namespace App;

use RuntimeException;

/** Tiny template renderer: view file + layout file, no template engine. */
final class View
{
    /** @param array<string, mixed> $data */
    public static function render(string $view, array $data = [], string $layout = 'layouts/app'): void
    {
        $viewFile   = BASE_PATH . '/app/views/' . $view . '.php';
        $layoutFile = BASE_PATH . '/app/views/' . $layout . '.php';

        if (!is_file($viewFile)) {
            throw new RuntimeException('View not found: ' . $view);
        }
        if (!is_file($layoutFile)) {
            throw new RuntimeException('Layout not found: ' . $layout);
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewFile;
        $content = (string) ob_get_clean();

        require $layoutFile;
    }

    public static function partial(string $view, array $data = []): void
    {
        $file = BASE_PATH . '/app/views/' . $view . '.php';
        if (!is_file($file)) {
            throw new RuntimeException('Partial not found: ' . $view);
        }
        extract($data, EXTR_SKIP);
        require $file;
    }
}
