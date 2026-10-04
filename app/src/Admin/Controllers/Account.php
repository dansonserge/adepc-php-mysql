<?php
declare(strict_types=1);

namespace Adepc\Admin\Controllers;

use Adepc\Admin\A;
use Adepc\Admin\Auth;
use Adepc\Db;
use Adepc\Http;

final class Account
{
    public static function handle(): void
    {
        $user = Db::one('SELECT * FROM admins WHERE id = ?', [Auth::user()['id']]);
        $errors = [];
        if (Http::isPost()) {
            $name = trim((string) ($_POST['name'] ?? ''));
            $locale = in_array($_POST['ui_locale'] ?? '', A::LOCALES, true) ? $_POST['ui_locale'] : $user['ui_locale'];
            $current = (string) ($_POST['current'] ?? '');
            $new = (string) ($_POST['password'] ?? '');
            if ($name === '') {
                $errors['name'] = 'admin.v.required';
            }
            if ($new !== '') {
                if (!password_verify($current, $user['password_hash'])) {
                    $errors['current'] = 'admin.v.currentPassword';
                }
                if (!Auth::validPassword($new)) {
                    $errors['password'] = 'admin.v.password';
                }
            }
            if (!$errors) {
                $data = ['name' => $name, 'ui_locale' => $locale, 'updated_at' => A::now()];
                if ($new !== '') {
                    $data['password_hash'] = password_hash($new, PASSWORD_DEFAULT);
                }
                Db::update('admins', $data, 'id = ?', [$user['id']]);
                A::flash('ok', 'admin.flash.saved');
                A::redirect('/admin/account');
            }
            A::flash('error', 'admin.flash.fixErrors');
            $user['name'] = $name;
        }
        A::render('account', ['title' => A::t('admin.nav.account'), 'section' => 'account', 'user' => $user, 'errors' => $errors], $errors ? 422 : 200);
    }

    /** The FR/EN switch, available on every admin screen (and on the login page). */
    public static function uiLanguage(): void
    {
        $locale = in_array($_POST['lang'] ?? '', A::LOCALES, true) ? $_POST['lang'] : 'fr';
        $_SESSION['ui_locale'] = $locale;
        if ($user = Auth::user()) {
            Db::update('admins', ['ui_locale' => $locale], 'id = ?', [$user['id']]);
        }
        $back = (string) ($_POST['back'] ?? '/admin');
        A::redirect(str_starts_with($back, '/admin') ? $back : '/admin');
    }
}
