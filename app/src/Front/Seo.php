<?php
declare(strict_types=1);

namespace Adepc\Front;

use Adepc\Img;
use Adepc\Schedule;
use Adepc\Site;

final class Seo
{
    /** Canonical + hreflang alternates for a page in every language. */
    public static function alternates(string $page, array $params = []): array
    {
        $out = [];
        foreach (Site::locales() as $code => $l) {
            $out[$code] = Site::url($page, $code, $params);
        }
        return $out;
    }

    public static function head(array $meta): string
    {
        $locale = Site::$locale;
        $short = Site::setting('site.short_name');
        $title = ($meta['absoluteTitle'] ?? false) ? $meta['title'] : str_replace('%s', $meta['title'], Site::t('meta.titleTemplate'));
        $description = (string) ($meta['description'] ?? '');
        $alternates = $meta['alternates'] ?? [];
        $canonical = isset($alternates[$locale]) ? Site::absolute($alternates[$locale]) : null;
        $share = Site::slot('brand.share');
        $icon = Site::slot('brand.icon');
        $apple = Site::slot('brand.apple_icon');

        $h = [];
        $h[] = '<meta name="theme-color" content="' . e(Site::setting('site.theme_color')) . '">';
        $h[] = '<meta name="color-scheme" content="light">';
        $h[] = '<title>' . e($title) . '</title>';
        if ($description !== '') {
            $h[] = '<meta name="description" content="' . e($description) . '">';
        }
        $h[] = '<meta name="application-name" content="' . e($short) . '">';
        if (!empty($meta['noindex'])) {
            $h[] = '<meta name="robots" content="noindex">';
        }
        if ($canonical && empty($meta['noindex'])) {
            $h[] = '<link rel="canonical" href="' . e($canonical) . '">';
            foreach (Site::locales() as $code => $l) {
                $h[] = '<link rel="alternate" hreflang="' . e($l['hreflang']) . '" href="' . e(Site::absolute($alternates[$code])) . '">';
            }
            $h[] = '<link rel="alternate" hreflang="x-default" href="' . e(Site::absolute($alternates[Site::defaultLocale()])) . '">';
        }
        $h[] = '<meta name="format-detection" content="telephone=no, address=no, email=no">';
        if (empty($meta['noindex'])) {
            $ogTitle = $meta['ogTitle'] ?? $meta['title'];
            $h[] = '<meta property="og:title" content="' . e($ogTitle) . '">';
            $h[] = '<meta property="og:description" content="' . e($description) . '">';
            if ($canonical) {
                $h[] = '<meta property="og:url" content="' . e($canonical) . '">';
            }
            $h[] = '<meta property="og:site_name" content="' . e($short) . '">';
            $h[] = '<meta property="og:locale" content="' . e(Site::locale()['og_locale']) . '">';
            if ($share) {
                $h[] = '<meta property="og:image" content="' . e(Site::absolute(Site::mediaUrl($share))) . '">';
                $h[] = '<meta property="og:image:type" content="' . e($share['mime']) . '">';
                $h[] = '<meta property="og:image:width" content="' . e($share['width']) . '">';
                $h[] = '<meta property="og:image:height" content="' . e($share['height']) . '">';
                $h[] = '<meta property="og:image:alt" content="' . e(Site::L($share, 'alt')) . '">';
            }
            foreach (Site::otherLocales() as $l) {
                $h[] = '<meta property="og:locale:alternate" content="' . e($l['og_locale']) . '">';
            }
            $h[] = '<meta property="og:type" content="website">';
            $h[] = '<meta name="twitter:card" content="summary_large_image">';
            $h[] = '<meta name="twitter:title" content="' . e($ogTitle) . '">';
            $h[] = '<meta name="twitter:description" content="' . e($description) . '">';
            if ($share) {
                $h[] = '<meta name="twitter:image" content="' . e(Site::absolute(Site::mediaUrl($share))) . '">';
                $h[] = '<meta name="twitter:image:alt" content="' . e(Site::L($share, 'alt')) . '">';
            }
        }
        if ($icon) {
            $h[] = '<link rel="icon" href="' . e(Site::mediaUrl($icon)) . '" sizes="' . e($icon['width'] . 'x' . $icon['height']) . '" type="' . e($icon['mime']) . '">';
        }
        if ($apple) {
            $h[] = '<link rel="apple-touch-icon" href="' . e(Site::mediaUrl($apple)) . '" sizes="' . e($apple['width'] . 'x' . $apple['height']) . '" type="' . e($apple['mime']) . '">';
        }
        foreach ($meta['jsonLd'] ?? [] as $data) {
            $h[] = '<script type="application/ld+json">' . str_replace('<', '<', (string) json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) . '</script>';
        }
        return implode("\n", $h);
    }

    /** <link rel=preload> for images rendered with preload/eager (filled while rendering). */
    public static function imagePreloads(): string
    {
        $out = '';
        $seen = [];
        foreach (Img::$preloads as $p) {
            $k = $p['srcset'] . '|' . $p['sizes'];
            if (isset($seen[$k]) || $p['srcset'] === '') {
                continue;
            }
            $seen[$k] = true;
            $out .= '<link rel="preload" as="image" imagesrcset="' . e($p['srcset']) . '" imagesizes="' . e($p['sizes']) . "\">\n";
        }
        return $out;
    }

    // ── Structured data ───────────────────────────────────────────────────

    private static function orgId(): string
    {
        return Site::absolute('/#organization');
    }

    public static function churchNode(array $c, string $locale): array
    {
        $icon = Site::slot('brand.icon');
        $node = [
            '@type' => 'Church',
            '@id' => Site::absolute('/#church-' . $c['slug']),
            'name' => $c['name'],
            'url' => Site::absolute(Site::url('church', $locale, ['slug' => $c['slug']])),
            'image' => $icon ? Site::absolute(Site::mediaUrl($icon)) : null,
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $c['street'],
                'addressLocality' => $c['locality'],
                'addressRegion' => $c['region'],
                'postalCode' => $c['postal_code'],
                'addressCountry' => Site::setting('org.country'),
            ],
            'geo' => ['@type' => 'GeoCoordinates', 'latitude' => (float) $c['lat'], 'longitude' => (float) $c['lng']],
        ];
        if (!empty($c['phone'])) {
            $node['telephone'] = '+' . Site::setting('phone.country_code') . ' ' . $c['phone'];
        }
        if (!empty($c['email'])) {
            $node['email'] = $c['email'];
        }
        $node['parentOrganization'] = ['@id' => self::orgId()];
        return array_filter($node, static fn ($v) => $v !== null);
    }

    public static function organization(string $locale): array
    {
        $icon = Site::slot('brand.icon');
        $social = array_values(array_filter(array_map(static fn ($s) => (string) $s['url'], Site::items('social'))));
        $org = [
            '@type' => 'Organization',
            '@id' => self::orgId(),
            'name' => Site::setting('site.short_name'),
            'legalName' => Site::setting('org.name.' . Site::defaultLocale()),
            'alternateName' => Site::setting('org.name.' . (current(array_diff(array_keys(Site::locales()), [Site::defaultLocale()])) ?: Site::defaultLocale())),
            'url' => Site::setting('site.url'),
            'logo' => $icon ? Site::absolute(Site::mediaUrl($icon)) : null,
            'email' => Site::setting('org.email'),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => Site::setting('hq.street'),
                'addressLocality' => Site::setting('hq.city'),
                'addressRegion' => Site::setting('hq.region'),
                'postalCode' => Site::setting('hq.postal_code'),
                'addressCountry' => Site::setting('org.country'),
            ],
        ];
        if ($social) {
            $org['sameAs'] = $social;
        }
        return [
            '@context' => 'https://schema.org',
            '@graph' => [array_filter($org, static fn ($v) => $v !== null), ...array_map(static fn ($c) => self::churchNode($c, $locale), Site::churches())],
        ];
    }

    public static function church(array $c, string $locale): array
    {
        return ['@context' => 'https://schema.org'] + self::churchNode($c, $locale);
    }

    /** Only for featured events with a real date. */
    public static function event(array $e, string $locale): ?array
    {
        if ($e['kind'] !== 'featured' || $e['date_mode'] !== 'date' || empty($e['event_date'])) {
            return null;
        }
        $time = $e['time_mode'] === 'time' && $e['event_time'] ? 'T' . $e['event_time'] . ':00' : '';
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => Site::L($e, 'title', $locale),
            'description' => Site::L($e, 'description', $locale),
            'startDate' => $e['event_date'] . $time,
            'eventStatus' => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'organizer' => ['@id' => self::orgId()],
        ];
        if (!$e['all_churches'] && $e['church_ids']) {
            $data['location'] = ['@id' => Site::absolute('/#church-' . (Site::churchById($e['church_ids'][0])['slug'] ?? ''))];
        }
        $people = array_values(array_filter(array_map('trim', explode("\n", (string) $e['people']))));
        if ($people) {
            $data['performer'] = array_map(static fn ($n) => ['@type' => 'Person', 'name' => $n], $people);
        }
        return $data;
    }

    // ── sitemap.xml / robots.txt ─────────────────────────────────────────

    public static function sitemap(): void
    {
        $entries = [];
        foreach (Site::d()['pages'] as $key => $p) {
            if ((int) $p['in_sitemap'] === 1) {
                $entries[] = [$key, [], $p['changefreq'], $p['priority']];
            }
        }
        $churchesPage = Site::page('churches');
        foreach (Site::churches() as $c) {
            $entries[] = ['church', ['slug' => $c['slug']], $churchesPage['changefreq'] ?? 'monthly', '0.7'];
        }
        $now = gmdate('Y-m-d\TH:i:s.v\Z');
        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
        foreach ($entries as [$page, $params, $freq, $priority]) {
            foreach (Site::locales() as $code => $l) {
                echo "<url>\n<loc>" . e(Site::absolute(Site::url($page, $code, $params))) . "</loc>\n";
                foreach (Site::locales() as $alt) {
                    echo '<xhtml:link rel="alternate" hreflang="' . e($alt['hreflang']) . '" href="' . e(Site::absolute(Site::url($page, $alt['code'], $params))) . '" />' . "\n";
                }
                echo "<lastmod>$now</lastmod>\n<changefreq>" . e($freq) . "</changefreq>\n<priority>" . e((string) (float) $priority) . "</priority>\n</url>\n";
            }
        }
        echo '</urlset>';
    }

    public static function robots(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        $lines = ['User-Agent: *', 'Allow: /'];
        foreach (preg_split('/\R/', Site::setting('seo.robots_disallow')) ?: [] as $path) {
            if (trim($path) !== '') {
                $lines[] = 'Disallow: ' . trim($path);
            }
        }
        $lines[] = '';
        $lines[] = 'Host: ' . Site::setting('site.url');
        $lines[] = 'Sitemap: ' . Site::absolute('/sitemap.xml');
        echo implode("\n", $lines) . "\n";
    }
}
