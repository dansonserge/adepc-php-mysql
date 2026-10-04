<?php
declare(strict_types=1);

namespace Adepc;

final class View
{
    /** Renders app/views/<name>.php with the given variables and returns the HTML. */
    public static function render(string $name, array $vars = []): string
    {
        $file = APP_DIR . '/views/' . $name . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("View not found: $name");
        }
        extract($vars, EXTR_SKIP);
        ob_start();
        try {
            include $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }
}
