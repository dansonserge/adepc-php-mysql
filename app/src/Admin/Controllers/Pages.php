<?php
declare(strict_types=1);

namespace Adepc\Admin\Controllers;

use Adepc\Admin\A;
use Adepc\Admin\Content;
use Adepc\Admin\Router;
use Adepc\Admin\Schema;
use Adepc\Db;
use Adepc\Http;
use Adepc\Site;

/** One screen per page, mirroring its sections: texts, lists, images, settings, URL and SEO. */
final class Pages
{
    public static function handle(?string $key): void
    {
        $pages = Schema::pages();
        if ($key === null) {
            A::render('pages-index', ['title' => A::t('admin.nav.pages'), 'section' => 'pages', 'pages' => $pages]);
            return;
        }
        if (!isset($pages[$key])) {
            Router::notFound();
            return;
        }
        if (Http::isPost()) {
            self::save($key, $pages[$key]);
            return;
        }
        self::show($key, $pages[$key], [], null);
    }

    /** Groups translation keys under the first section whose prefix matches. */
    public static function sections(array $page, array $translations): array
    {
        $taken = [];
        $out = [];
        foreach ($page['sections'] as $s) {
            [$label, $prefixes, $lists, $slots] = $s;
            $settings = $s[4] ?? [];
            $keys = [];
            foreach (array_keys($translations) as $tk) {
                if (isset($taken[$tk]) || str_starts_with($tk, 'meta.') && $tk !== 'meta.titleTemplate') {
                    continue;
                }
                foreach ($prefixes as $p) {
                    if ($tk === $p || (str_ends_with($p, '.') && str_starts_with($tk, $p))) {
                        $keys[] = $tk;
                        $taken[$tk] = true;
                        break;
                    }
                }
            }
            $out[] = compact('label', 'keys', 'lists', 'slots', 'settings');
        }
        return $out;
    }

    private static function show(string $key, array $page, array $errors, ?array $posted): void
    {
        $translations = Content::translations();
        if ($posted) {
            foreach (A::LOCALES as $l) {
                foreach ((array) ($posted['t'][$l] ?? []) as $k => $v) {
                    if (isset($translations[$k])) {
                        $translations[$k][$l] = (string) $v;
                    }
                }
            }
        }
        $lists = [];
        foreach (self::sections($page, $translations) as $s) {
            foreach ($s['lists'] as $lk) {
                $lists[$lk] = Content::listItems($lk);
            }
        }
        A::render('page-edit', [
            'title' => A::t($page['label']),
            'section' => 'pages',
            'key' => $key,
            'page' => $page,
            'row' => Db::one('SELECT * FROM pages WHERE pkey = ?', [$key]),
            'sections' => self::sections($page, $translations),
            'translations' => $translations,
            'lists' => $lists,
            'slots' => array_column(Db::all('SELECT * FROM media_slots'), null, 'skey'),
            'settings' => Content::settings(),
            'errors' => $errors,
            'posted' => $posted,
        ], $errors ? 422 : 200);
    }

    private static function save(string $key, array $page): void
    {
        $errors = [];
        $sections = self::sections($page, Content::translations());

        // Validate everything first, then save in one transaction.
        $listsPosted = (array) ($_POST['lists'] ?? []);
        foreach ($listsPosted as $lk => $items) {
            if (isset(Schema::lists()[$lk]) && ($e = Content::saveList($lk, (array) $items, true))) {
                $errors['lists'][$lk] = $e;
            }
        }
        $settingFields = [];
        foreach ($sections as $s) {
            foreach ($s['settings'] as $sk) {
                if ($f = Schema::settingField($sk)) {
                    $settingFields[] = $f;
                }
            }
        }
        $pageRow = Db::one('SELECT * FROM pages WHERE pkey = ?', [$key]);
        $slugs = [];
        if ($pageRow && $key !== 'home') {
            foreach (A::LOCALES as $l) {
                $slug = trim((string) ($_POST['page']['slug_' . $l] ?? ''));
                if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
                    $errors['page']['slug_' . $l] = 'admin.v.slug';
                } elseif (Db::value('SELECT pkey FROM pages WHERE slug_' . $l . ' = ? AND pkey <> ?', [$slug, $key])) {
                    $errors['page']['slug_' . $l] = 'admin.v.unique';
                }
                $slugs['slug_' . $l] = $slug;
            }
        }

        if (!$errors) {
            try {
                Db::transaction(function () use ($key, $listsPosted, $settingFields, $pageRow, $slugs, &$errors): void {
                $tErrors = Content::saveTranslations((array) ($_POST['t'] ?? []), (array) ($_POST['reset'] ?? []));
                if ($tErrors) {
                    $errors['t'] = $tErrors;
                    throw new \RuntimeException('rollback');
                }
                foreach ($listsPosted as $lk => $items) {
                    if (isset(Schema::lists()[$lk])) {
                        Content::saveList($lk, (array) $items, false);
                    }
                }
                Content::saveSlots((array) ($_POST['slots'] ?? []));
                if ($settingFields && ($sErrors = Content::saveSettings($settingFields, (array) ($_POST['s'] ?? [])))) {
                    $errors['s'] = $sErrors;
                    throw new \RuntimeException('rollback');
                }
                if ($pageRow) {
                    $update = $slugs + [
                        'in_sitemap' => !empty($_POST['page']['in_sitemap']) ? 1 : 0,
                        'changefreq' => in_array($_POST['page']['changefreq'] ?? '', ['daily', 'weekly', 'monthly', 'yearly'], true) ? $_POST['page']['changefreq'] : $pageRow['changefreq'],
                        'priority' => max(0, min(1, round((float) ($_POST['page']['priority'] ?? $pageRow['priority']), 1))),
                    ];
                    Db::update('pages', $update, 'pkey = ?', [$key]);
                    self::redirectSlugChanges($key, $pageRow, $slugs);
                }
                });
            } catch (\RuntimeException $e) {
                if ($e->getMessage() !== 'rollback') {
                    throw $e;
                }
            }
        }
        if ($errors) {
            A::flash('error', 'admin.flash.fixErrors');
            self::show($key, $page, $errors, $_POST);
            return;
        }
        A::changed();
        A::flash('ok', 'admin.flash.saved');
        A::redirect('/admin/pages/' . $key);
    }

    /** Old URLs (and, for the churches page, every church URL) redirect to the new slug. */
    private static function redirectSlugChanges(string $key, array $old, array $new): void
    {
        foreach (A::LOCALES as $l) {
            $from = $old['slug_' . $l] ?? '';
            $to = $new['slug_' . $l] ?? $from;
            if ($from === '' || $from === $to) {
                continue;
            }
            $pairs = [["/$l/$from", "/$l/$to"]];
            if ($key === 'churches') {
                foreach (Db::all('SELECT slug FROM churches') as $c) {
                    $pairs[] = ["/$l/$from/{$c['slug']}", "/$l/$to/{$c['slug']}"];
                }
            }
            foreach ($pairs as [$f, $t]) {
                Db::exec('DELETE FROM redirects WHERE from_path = ?', [$t]);
                Db::exec('UPDATE redirects SET to_path = ? WHERE to_path = ?', [$t, $f]);
                Db::exec('INSERT INTO redirects (from_path, to_path, status, is_auto) VALUES (?, ?, 308, 1) ON DUPLICATE KEY UPDATE to_path = VALUES(to_path)', [$f, $t]);
            }
        }
    }

    /** Public URL of an admin page key, for the "view on site" links. */
    public static function publicUrl(string $key, string $locale): ?string
    {
        if ($key === 'common') {
            return Site::url('home', $locale);
        }
        return Site::page($key) ? Site::url($key, $locale) : null;
    }
}
