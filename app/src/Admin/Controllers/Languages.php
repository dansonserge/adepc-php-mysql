<?php
declare(strict_types=1);

namespace Adepc\Admin\Controllers;

use Adepc\Admin\A;
use Adepc\Db;
use Adepc\Http;

/** Names and codes of the two site languages, and which one is the default. */
final class Languages
{
    public static function handle(): void
    {
        $rows = Db::all('SELECT * FROM locales ORDER BY sort, code');
        $errors = [];
        $updates = [];
        if (Http::isPost()) {
            $default = (string) ($_POST['default'] ?? '');
            foreach ($rows as $r) {
                $in = (array) ($_POST['l'][$r['code']] ?? []);
                $data = [];
                foreach (['name' => 64, 'short_label' => 8, 'hreflang' => 16, 'og_locale' => 16] as $col => $max) {
                    $v = trim((string) ($in[$col] ?? ''));
                    if ($v === '' || mb_strlen($v) > $max) {
                        $errors[$r['code'] . '.' . $col] = 'admin.v.required';
                    }
                    $data[$col] = $v;
                }
                $data['is_default'] = $default === $r['code'] ? 1 : 0;
                $updates[$r['code']] = $data;
            }
            if (!$errors) {
                foreach ($updates as $code => $data) {
                    Db::update('locales', $data, 'code = ?', [$code]);
                }
                A::changed();
                A::flash('ok', 'admin.flash.saved');
                A::redirect('/admin/languages');
            }
            A::flash('error', 'admin.flash.fixErrors');
        }
        A::render('languages', ['title' => A::t('admin.nav.languages'), 'section' => 'languages', 'rows' => $rows, 'errors' => $errors]);
    }
}
