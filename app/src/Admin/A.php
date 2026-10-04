<?php
declare(strict_types=1);

namespace Adepc\Admin;

use Adepc\Cache;
use Adepc\Content;
use Adepc\Db;
use Adepc\I18n;
use Adepc\View;

/** Admin context: interface language, translations, flash messages, rendering. */
final class A
{
    public const LOCALES = ['fr', 'en'];
    private static ?array $strings = null;
    public static string $locale = 'fr';

    public static function boot(): void
    {
        $user = Auth::user();
        $wanted = $user['ui_locale'] ?? ($_SESSION['ui_locale'] ?? 'fr');
        self::$locale = in_array($wanted, self::LOCALES, true) ? $wanted : 'fr';
    }

    /** Admin interface strings (translations with keys "admin.*"), editable like the rest. */
    public static function t(string $key, array $vars = []): string
    {
        self::$strings ??= Cache::remember('admin_strings', 0, static function (): array {
            $out = [];
            foreach (Db::all("SELECT locale, tkey, value FROM translations WHERE tkey LIKE 'admin.%'") as $r) {
                $out[$r['locale']][$r['tkey']] = $r['value'];
            }
            return $out;
        });
        $value = self::$strings[self::$locale][$key] ?? self::$strings['fr'][$key] ?? null;
        if ($value === null) {
            error_log("[admin i18n] missing $key");
            return $key;
        }
        return I18n::format($value, $vars, self::$locale);
    }

    public static function flash(string $type, string $key, array $vars = []): void
    {
        $_SESSION['flash'][] = [$type, $key, $vars];
    }

    public static function takeFlash(): array
    {
        $f = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $f;
    }

    public static function render(string $view, array $vars = [], int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        $body = View::render('admin/' . $view, $vars);
        echo View::render('admin/layout', ['body' => $body, 'title' => $vars['title'] ?? '', 'section' => $vars['section'] ?? '', 'bare' => $vars['bare'] ?? false]);
    }

    public static function redirect(string $path): never
    {
        header('Location: ' . $path, true, 303);
        exit;
    }

    /** Every change to content clears the public site's cache. */
    public static function changed(): void
    {
        Content::flush();
        Cache::forget('admin_strings');
        self::$strings = null;
    }

    public static function now(): string
    {
        return gmdate('Y-m-d H:i:s');
    }
}
