<?php
declare(strict_types=1);

namespace Adepc;

/**
 * Everything the public site shows, read from MySQL in one go and cached as a
 * PHP array until an admin saves something (Content::flush()).
 */
final class Content
{
    private static ?array $data = null;

    public static function data(): array
    {
        return self::$data ??= Cache::remember('content', 0, [self::class, 'load']);
    }

    public static function flush(): void
    {
        self::$data = null;
        Cache::forget('content');
        Cache::forget('youtube');
    }

    /** Swaps in another snapshot (used by tests). */
    public static function useData(array $data): void
    {
        self::$data = $data;
    }

    public static function load(): array
    {
        $d = [];

        $d['locales'] = [];
        foreach (Db::all('SELECT * FROM locales ORDER BY sort, code') as $r) {
            $d['locales'][$r['code']] = $r;
        }
        $d['default_locale'] = 'fr';
        foreach ($d['locales'] as $code => $r) {
            if ((int) $r['is_default'] === 1) {
                $d['default_locale'] = $code;
            }
        }

        $d['settings'] = [];
        foreach (Db::all('SELECT skey, value FROM settings') as $r) {
            $d['settings'][$r['skey']] = (string) ($r['value'] ?? '');
        }

        $d['t'] = [];
        foreach (Db::all("SELECT locale, tkey, value FROM translations WHERE tkey NOT LIKE 'admin.%'") as $r) {
            $d['t'][$r['locale']][$r['tkey']] = $r['value'];
        }

        $d['pages'] = [];
        foreach (Db::all('SELECT * FROM pages ORDER BY sort') as $r) {
            $d['pages'][$r['pkey']] = $r;
        }

        $d['redirects'] = [];
        foreach (Db::all('SELECT from_path, to_path, status FROM redirects') as $r) {
            $d['redirects'][$r['from_path']] = $r;
        }

        $d['menus'] = [];
        foreach (Db::all('SELECT * FROM menu_items WHERE visible = 1 ORDER BY menu, sort, id') as $r) {
            $d['menus'][$r['menu']][] = $r;
        }

        $d['lists'] = [];
        foreach (Db::all('SELECT * FROM list_items WHERE visible = 1 ORDER BY list_key, sort, id') as $r) {
            $d['lists'][$r['list_key']][] = $r;
        }

        $d['media'] = [];
        foreach (Db::all('SELECT * FROM media ORDER BY sort, id') as $r) {
            $d['media'][(int) $r['id']] = $r;
        }

        $d['slots'] = [];
        foreach (Db::all('SELECT * FROM media_slots') as $r) {
            $d['slots'][$r['skey']] = $r;
        }

        $services = [];
        foreach (Db::all('SELECT * FROM church_services ORDER BY church_id, sort, id') as $r) {
            $services[(int) $r['church_id']][] = $r;
        }
        $d['churches'] = [];
        foreach (Db::all('SELECT * FROM churches WHERE published = 1 ORDER BY sort, id') as $r) {
            $r['services'] = $services[(int) $r['id']] ?? [];
            $d['churches'][] = $r;
        }

        $eventChurches = [];
        foreach (Db::all('SELECT * FROM event_churches ORDER BY event_id, sort') as $r) {
            $eventChurches[(int) $r['event_id']][] = (int) $r['church_id'];
        }
        $d['events'] = [];
        foreach (Db::all('SELECT * FROM events WHERE published = 1 ORDER BY sort, id') as $r) {
            $r['church_ids'] = $eventChurches[(int) $r['id']] ?? [];
            $d['events'][] = $r;
        }

        $d['categories'] = Db::all('SELECT * FROM video_categories WHERE visible = 1 ORDER BY sort, id');
        $d['videos'] = Db::all('SELECT * FROM videos WHERE published = 1 ORDER BY sort, id');
        $d['stories'] = Db::all('SELECT * FROM stories WHERE published = 1 AND consent = 1 ORDER BY sort, id');
        $d['purposes'] = Db::all('SELECT * FROM contact_purposes WHERE visible = 1 ORDER BY sort, id');

        return $d;
    }
}
