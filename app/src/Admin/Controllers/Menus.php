<?php
declare(strict_types=1);

namespace Adepc\Admin\Controllers;

use Adepc\Admin\A;
use Adepc\Db;
use Adepc\Http;

/** Header, overlay menu, Give button, footer and phone tab bar. */
final class Menus
{
    public const MENUS = ['header', 'overlay', 'cta', 'footer', 'tabbar'];
    public const PAGES = ['home', 'churches', 'events', 'watch', 'about', 'stories', 'give', 'contact', 'privacy'];

    public static function handle(): void
    {
        $errors = [];
        if (Http::isPost()) {
            $rows = [];
            foreach (self::MENUS as $menu) {
                $sort = 0;
                foreach ((array) ($_POST['menu'][$menu] ?? []) as $id => $in) {
                    if (!is_array($in)) {
                        continue;
                    }
                    if (!empty($in['_delete'])) {
                        $rows[] = ['delete' => $id];
                        continue;
                    }
                    $label = ['label_fr' => trim((string) ($in['label_fr'] ?? '')), 'label_en' => trim((string) ($in['label_en'] ?? ''))];
                    if (str_starts_with((string) $id, 'new') && $label['label_fr'] === '' && $label['label_en'] === '') {
                        continue;
                    }
                    if ($label['label_fr'] === '' || $label['label_en'] === '') {
                        $errors[$menu . '.' . $id] = 'admin.v.required';
                    }
                    $page = in_array($in['page_key'] ?? '', self::PAGES, true) ? $in['page_key'] : 'home';
                    $icon = preg_match('/^[a-z0-9-]+$/', (string) ($in['icon'] ?? '')) ? $in['icon'] : null;
                    $rows[] = ['id' => $id, 'data' => ['menu' => $menu, 'page_key' => $page] + $label + [
                        'icon' => $icon, 'highlight' => !empty($in['highlight']) ? 1 : 0, 'visible' => !empty($in['visible']) ? 1 : 0, 'sort' => ++$sort,
                    ]];
                }
            }
            if (!$errors) {
                Db::transaction(static function () use ($rows): void {
                    foreach ($rows as $r) {
                        if (isset($r['delete'])) {
                            if (ctype_digit((string) $r['delete'])) {
                                Db::exec('DELETE FROM menu_items WHERE id = ?', [(int) $r['delete']]);
                            }
                        } elseif (ctype_digit((string) $r['id'])) {
                            Db::update('menu_items', $r['data'], 'id = ?', [(int) $r['id']]);
                        } else {
                            Db::insert('menu_items', $r['data']);
                        }
                    }
                });
                A::changed();
                A::flash('ok', 'admin.flash.saved');
                A::redirect('/admin/menus');
            }
            A::flash('error', 'admin.flash.fixErrors');
        }
        $items = [];
        foreach (Db::all('SELECT * FROM menu_items ORDER BY menu, sort, id') as $r) {
            $items[$r['menu']][] = $r;
        }
        A::render('menus', [
            'title' => A::t('admin.nav.menus'),
            'section' => 'menus',
            'items' => $items,
            'errors' => $errors,
            'icons' => Icons::keys(),
        ], $errors ? 422 : 200);
    }
}
