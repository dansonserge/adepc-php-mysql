<?php
declare(strict_types=1);

namespace Adepc;

/** The public site's view of the content snapshot, for the current language. */
final class Site
{
    public static string $locale = 'fr';

    public static function d(): array
    {
        return Content::data();
    }

    /** @return array<string, array> */
    public static function locales(): array
    {
        return self::d()['locales'];
    }

    public static function defaultLocale(): string
    {
        return self::d()['default_locale'];
    }

    public static function locale(?string $code = null): array
    {
        return self::locales()[$code ?? self::$locale];
    }

    public static function otherLocales(): array
    {
        return array_filter(self::locales(), static fn ($l) => $l['code'] !== self::$locale);
    }

    public static function setting(string $key, string $default = ''): string
    {
        $v = self::d()['settings'][$key] ?? null;
        return ($v === null || $v === '') ? $default : $v;
    }

    /** A setting stored per language, e.g. "org.name" → "org.name.fr". */
    public static function settingL(string $key, ?string $locale = null): string
    {
        return self::setting($key . '.' . ($locale ?? self::$locale));
    }

    public static function t(string $key, array $vars = [], ?string $locale = null): string
    {
        $locale ??= self::$locale;
        $value = self::d()['t'][$locale][$key] ?? null;
        if ($value === null) {
            error_log("[i18n] missing translation $locale:$key");
            return $key;
        }
        return I18n::format($value, $vars, $locale);
    }

    /** Localized column: L($row, 'title') reads $row['title_fr'] or $row['title_en']. */
    public static function L(array $row, string $field, ?string $locale = null): string
    {
        return (string) ($row[$field . '_' . ($locale ?? self::$locale)] ?? '');
    }

    /** @return list<array> visible items of a list, in order */
    public static function items(string $key): array
    {
        return self::d()['lists'][$key] ?? [];
    }

    /** @return list<array> */
    public static function menu(string $name): array
    {
        return self::d()['menus'][$name] ?? [];
    }

    public static function media(mixed $id): ?array
    {
        return $id === null ? null : (self::d()['media'][(int) $id] ?? null);
    }

    /** Media assigned to a slot; a slot's own focus overrides the media's. */
    public static function slot(string $key): ?array
    {
        $slot = self::d()['slots'][$key] ?? null;
        $media = $slot ? self::media($slot['media_id']) : null;
        if ($media && !empty($slot['focus'])) {
            $media['focus'] = $slot['focus'];
        }
        return $media;
    }

    public static function mediaUrl(?array $media): string
    {
        return $media ? '/media/' . $media['path'] : '';
    }

    /** @return list<array> */
    public static function churches(): array
    {
        return self::d()['churches'];
    }

    public static function church(string $slug): ?array
    {
        foreach (self::churches() as $c) {
            if ($c['slug'] === $slug) {
                return $c;
            }
        }
        return null;
    }

    public static function churchById(mixed $id): ?array
    {
        foreach (self::churches() as $c) {
            if ((int) $c['id'] === (int) $id) {
                return $c;
            }
        }
        return null;
    }

    public static function page(string $key): ?array
    {
        return self::d()['pages'][$key] ?? null;
    }

    /** Path of a page in a language, e.g. url('churches', 'fr') → /fr/eglises. */
    public static function url(string $page, ?string $locale = null, array $params = [], array $query = [], string $hash = ''): string
    {
        $locale ??= self::$locale;
        $path = '/' . $locale;
        if ($page === 'church') {
            $path .= '/' . self::slugOf('churches', $locale) . '/' . rawurlencode((string) $params['slug']);
        } elseif ($page !== 'home') {
            $slug = self::slugOf($page, $locale);
            $path .= $slug === '' ? '' : '/' . $slug;
        }
        if ($query) {
            $path .= '?' . http_build_query($query);
        }
        return $path . ($hash !== '' ? '#' . $hash : '');
    }

    public static function slugOf(string $page, string $locale): string
    {
        $p = self::page($page);
        return $p ? (string) $p['slug_' . $locale] : $page;
    }

    public static function absolute(string $path): string
    {
        return rtrim(self::setting('site.url'), '/') . $path;
    }
}
