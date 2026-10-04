<?php
declare(strict_types=1);

namespace Adepc;

final class App
{
    public static function run(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), interest-cohort=()');

        if (!Config::exists()) {
            Http::redirect('/install/', 302);
        }

        $path = rawurldecode((string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'));
        try {
            if ($path === '/admin' || str_starts_with($path, '/admin/')) {
                Admin\Router::dispatch($path);
                return;
            }
            Front\Router::dispatch($path);
        } catch (\Throwable $e) {
            error_log((string) $e);
            if (!headers_sent()) {
                http_response_code(500);
            }
            if (Config::get('debug')) {
                echo '<pre>' . e((string) $e) . '</pre>';
            }
        }
    }
}
