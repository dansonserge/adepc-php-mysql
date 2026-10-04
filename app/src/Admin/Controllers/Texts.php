<?php
declare(strict_types=1);

namespace Adepc\Admin\Controllers;

use Adepc\Admin\A;
use Adepc\Admin\Content;
use Adepc\Http;

/** Every string of the site (and of this admin), searchable, FR and EN side by side. */
final class Texts
{
    public static function handle(): void
    {
        $errors = [];
        if (Http::isPost()) {
            $errors = Content::saveTranslations((array) ($_POST['t'] ?? []), (array) ($_POST['reset'] ?? []));
            if (!$errors) {
                A::changed();
                A::flash('ok', 'admin.flash.saved');
                A::redirect('/admin/texts?' . http_build_query(array_filter(['group' => $_POST['group'] ?? '', 'q' => $_POST['q'] ?? ''])));
            }
            A::flash('error', 'admin.flash.fixErrors');
        }
        $all = [];
        foreach (\Adepc\Db::all('SELECT locale, tkey, value FROM translations ORDER BY tkey') as $r) {
            $all[$r['tkey']][$r['locale']] = $r['value'];
        }
        $groups = [];
        foreach (array_keys($all) as $k) {
            $groups[explode('.', $k)[0]] = true;
        }
        $group = is_string($_GET['group'] ?? $_POST['group'] ?? null) ? (string) ($_GET['group'] ?? $_POST['group']) : '';
        $q = is_string($_GET['q'] ?? $_POST['q'] ?? null) ? trim((string) ($_GET['q'] ?? $_POST['q'])) : '';
        $rows = array_filter($all, static function ($v, $k) use ($group, $q) {
            if ($group !== '' && explode('.', $k)[0] !== $group) {
                return false;
            }
            if ($q === '') {
                return true;
            }
            return mb_stripos($k . ' ' . implode(' ', $v), $q) !== false;
        }, ARRAY_FILTER_USE_BOTH);
        if ($errors) {
            foreach (A::LOCALES as $l) {
                foreach ((array) ($_POST['t'][$l] ?? []) as $k => $v) {
                    if (isset($rows[$k])) {
                        $rows[$k][$l] = (string) $v;
                    }
                }
            }
        }
        A::render('texts', [
            'title' => A::t('admin.nav.texts'),
            'section' => 'texts',
            'rows' => array_slice($rows, 0, 400, true),
            'total' => count($rows),
            'groups' => array_keys($groups),
            'group' => $group,
            'q' => $q,
            'errors' => $errors,
            'defaults' => Content::defaults(),
        ], $errors ? 422 : 200);
    }
}
