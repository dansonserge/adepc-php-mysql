<?php
declare(strict_types=1);

namespace Adepc\Front;

use Adepc\Schedule;
use Adepc\Site;
use Adepc\View;

/** One method per public page: gathers its data, then renders the view in the layout. */
final class Pages
{
    private static function render(string $view, array $vars, array $meta, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        $body = View::render('site/pages/' . $view, $vars);
        echo compact_html(View::render('site/layout', ['body' => $body, 'meta' => $meta]));
    }

    private static function meta(string $page, array $params = [], array $extra = []): array
    {
        return $extra + [
            'page' => $page,
            'params' => $params,
            'alternates' => Seo::alternates($page, $params),
            'title' => array_key_exists('title', $extra) ? $extra['title'] : Site::t("meta.$page.title"),
            'description' => array_key_exists('description', $extra) ? $extra['description'] : Site::t("meta.$page.description"),
        ];
    }

    public static function home(): void
    {
        $film = Videos::find((int) Site::setting('home.film.video_id'));
        $messages = Videos::inRole('messages');
        self::render('home', [
            'churches' => Site::churches(),
            'events' => Site::d()['events'],
            'film' => $film,
            'latest' => $messages[0] ?? null,
            'videos' => Videos::all(),
            'story' => Site::d()['stories'][0] ?? null,
        ], self::meta('home', [], [
            'absoluteTitle' => true,
            'jsonLd' => [Seo::organization(Site::$locale)],
        ]));
    }

    public static function churches(): void
    {
        self::render('churches', ['churches' => Site::churches()], self::meta('churches'));
    }

    public static function church(string $slug): void
    {
        $church = Site::church($slug);
        if (!$church) {
            self::notFound();
            return;
        }
        $sunday = Schedule::serviceOn($church, 'sunday');
        $meta = self::meta('church', ['slug' => $slug], [
            'title' => $church['name'],
            'description' => Site::t('meta.church.description', [
                'name' => $church['name'],
                'time' => $sunday ? Schedule::formatTime($sunday['time']) : '',
                'address' => Site::t('format.streetLocality', ['street' => $church['street'], 'locality' => $church['locality']]),
            ]),
            'active' => 'churches',
            'jsonLd' => [Seo::church($church, Site::$locale)],
        ]);
        $others = array_values(array_filter(Site::churches(), static fn ($c) => $c['slug'] !== $church['slug']));
        self::render('church', ['church' => $church, 'others' => $others], $meta);
    }

    public static function events(): void
    {
        $jsonLd = array_values(array_filter(array_map(static fn ($e) => Seo::event($e, Site::$locale), Site::d()['events'])));
        self::render('events', ['events' => Site::d()['events'], 'churches' => Site::churches()], self::meta('events', [], ['jsonLd' => $jsonLd]));
    }

    public static function watch(): void
    {
        $videos = Videos::all();
        $messages = Videos::inRole('messages');
        $latest = $messages[0] ?? null;
        $filled = [];
        $empty = [];
        foreach (Site::d()['categories'] as $c) {
            $list = array_values(array_filter($videos, static fn ($v) => (int) $v['category']['id'] === (int) $c['id'] && $v['key'] !== ($latest['key'] ?? null)));
            if ($list) {
                $filled[] = ['category' => $c, 'list' => $list];
            } else {
                $empty[] = $c;
            }
        }
        self::render('watch', ['latest' => $latest, 'filled' => $filled, 'empty' => $empty], self::meta('watch'));
    }

    public static function about(): void
    {
        self::render('about', ['churches' => Site::churches()], self::meta('about'));
    }

    public static function stories(): void
    {
        $gallery = array_values(array_filter(Site::d()['media'], static fn ($m) => $m['kind'] === 'image' && (int) $m['in_gallery'] === 1));
        self::render('stories', ['stories' => Site::d()['stories'], 'gallery' => $gallery, 'churches' => Site::churches()], self::meta('stories'));
    }

    public static function give(): void
    {
        self::render('give', [], self::meta('give'));
    }

    public static function contact(array $state = [], int $status = 200): void
    {
        $one = static fn ($v) => is_array($v) ? (string) reset($v) : (string) ($v ?? '');
        self::render('contact', [
            'churches' => Site::churches(),
            'defaultChurch' => $one($_GET['church'] ?? ''),
            'defaultPurpose' => $one($_GET['purpose'] ?? ''),
            'state' => $state + ['status' => ($_GET['sent'] ?? '') === '1' ? 'sent' : 'idle'],
        ], self::meta('contact'), $status);
    }

    public static function privacy(): void
    {
        self::render('privacy', [], self::meta('privacy'));
    }

    /** 404 inside a language: header, footer and the localized message. */
    public static function notFound(): void
    {
        self::render('not-found', [], [
            'page' => 'notFound',
            'params' => [],
            'alternates' => [],
            'title' => Site::t('notFound.title'),
            'description' => '',
            'noindex' => true,
        ], 404);
    }

    /** 404 outside any language: its own document, in every language. */
    public static function globalNotFound(): void
    {
        http_response_code(404);
        header('Content-Type: text/html; charset=utf-8');
        Site::$locale = Site::defaultLocale();
        echo compact_html(View::render('site/global-not-found', []));
    }
}
