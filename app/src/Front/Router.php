<?php
declare(strict_types=1);

namespace Adepc\Front;

use Adepc\Http;
use Adepc\Site;

/**
 * Public URLs, identical to the Next.js site:
 *   /                       → 307 to /fr or /en (cookie, then Accept-Language)
 *   /{locale}/{page slug}   → localized slugs from the `pages` table
 *   /{locale}/{churches}/{slug}
 *   legacy paths            → `redirects` table (308)
 */
final class Router
{
    public const COOKIE = 'NEXT_LOCALE';

    public static function dispatch(string $path): void
    {
        $d = Site::d();

        if ($path === '/sitemap.xml') {
            Seo::sitemap();
            return;
        }
        if ($path === '/robots.txt') {
            Seo::robots();
            return;
        }

        if (isset($d['redirects'][$path])) {
            $r = $d['redirects'][$path];
            Http::redirect($r['to_path'], (int) $r['status']);
        }

        if ($path === '/') {
            header('Vary: Accept-Language, Cookie');
            Http::redirect('/' . self::negotiate() . Http::query(), 307);
        }

        if (strlen($path) > 1 && str_ends_with($path, '/')) {
            Http::redirect(rtrim($path, '/') . Http::query(), 308);
        }

        $segments = explode('/', trim($path, '/'));
        $locale = $segments[0];

        if (!isset($d['locales'][$locale])) {
            // Files (anything with a dot) never get a locale; they are just missing.
            if (str_contains(end($segments) ?: '', '.')) {
                Pages::globalNotFound();
                return;
            }
            Http::redirect('/' . self::negotiate() . $path . Http::query(), 307);
        }

        Site::$locale = $locale;
        if (($_COOKIE[self::COOKIE] ?? '') !== $locale) {
            setcookie(self::COOKIE, $locale, ['path' => '/', 'samesite' => 'Lax']);
        }

        $rest = array_slice($segments, 1);
        if ($rest === []) {
            Pages::home();
            return;
        }

        $page = self::pageForSlug($rest[0], $locale);
        if ($page === null) {
            // A slug from another language (/en/eglises) goes to this language's slug.
            foreach (array_keys($d['locales']) as $other) {
                $foreign = self::pageForSlug($rest[0], $other);
                if ($foreign !== null && $other !== $locale) {
                    $rest[0] = Site::slugOf($foreign, $locale);
                    Http::redirect('/' . $locale . '/' . implode('/', $rest) . Http::query(), 307);
                }
            }
            Pages::notFound();
            return;
        }

        if ($page === 'churches' && count($rest) === 2) {
            Pages::church($rest[1]);
            return;
        }
        if (count($rest) !== 1 || $page === 'home') {
            Pages::notFound();
            return;
        }

        match ($page) {
            'churches' => Pages::churches(),
            'events' => Pages::events(),
            'watch' => Pages::watch(),
            'about' => Pages::about(),
            'stories' => Pages::stories(),
            'give' => Pages::give(),
            'contact' => Http::isPost() ? Contact::submit() : Pages::contact(),
            'privacy' => Pages::privacy(),
            default => Pages::notFound(),
        };
    }

    private static function pageForSlug(string $slug, string $locale): ?string
    {
        foreach (Site::d()['pages'] as $key => $p) {
            if ($key !== 'home' && $p['slug_' . $locale] === $slug) {
                return $key;
            }
        }
        return null;
    }

    /** Locale cookie first, then Accept-Language, then the default language. */
    public static function negotiate(): string
    {
        $locales = Site::locales();
        $cookie = (string) ($_COOKIE[self::COOKIE] ?? '');
        if (isset($locales[$cookie])) {
            return $cookie;
        }
        $header = (string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
        $ranked = [];
        foreach (explode(',', $header) as $i => $part) {
            $bits = explode(';', trim($part));
            $tag = strtolower(trim($bits[0]));
            if ($tag === '') {
                continue;
            }
            $q = 1.0;
            foreach (array_slice($bits, 1) as $param) {
                if (preg_match('/^\s*q=([0-9.]+)/', $param, $m)) {
                    $q = (float) $m[1];
                }
            }
            $ranked[] = [$q, -$i, $tag];
        }
        rsort($ranked);
        foreach ($ranked as [, , $tag]) {
            $base = explode('-', $tag)[0];
            if (isset($locales[$base])) {
                return $base;
            }
        }
        return Site::defaultLocale();
    }
}
