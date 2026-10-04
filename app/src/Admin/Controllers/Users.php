<?php
declare(strict_types=1);

namespace Adepc\Admin\Controllers;

use Adepc\Admin\A;
use Adepc\Admin\Auth;
use Adepc\Admin\Router;
use Adepc\Db;
use Adepc\Http;

/** Admin accounts (administrators only). Editors manage content but not users. */
final class Users
{
    public static function handle(?string $id): void
    {
        if ($id === null) {
            A::render('users', ['title' => A::t('admin.nav.users'), 'section' => 'users', 'rows' => Db::all('SELECT * FROM admins ORDER BY name')]);
            return;
        }
        $row = $id === 'new' ? null : Db::one('SELECT * FROM admins WHERE id = ?', [(int) $id]);
        if ($id !== 'new' && !$row) {
            Router::notFound();
            return;
        }
        if (Http::isPost() && ($_POST['action'] ?? '') === 'delete' && $row) {
            self::delete($row);
            return;
        }
        $errors = [];
        $values = $row ?? ['name' => '', 'email' => '', 'role' => 'editor', 'ui_locale' => 'fr'];
        if (Http::isPost()) {
            $values = [
                'name' => trim((string) ($_POST['name'] ?? '')),
                'email' => strtolower(trim((string) ($_POST['email'] ?? ''))),
                'role' => in_array($_POST['role'] ?? '', ['admin', 'editor'], true) ? $_POST['role'] : 'editor',
                'ui_locale' => in_array($_POST['ui_locale'] ?? '', A::LOCALES, true) ? $_POST['ui_locale'] : 'fr',
            ];
            $password = (string) ($_POST['password'] ?? '');
            if ($values['name'] === '') {
                $errors['name'] = 'admin.v.required';
            }
            if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'admin.v.email';
            } elseif (Db::value('SELECT id FROM admins WHERE email = ? AND id <> ?', [$values['email'], $row['id'] ?? 0])) {
                $errors['email'] = 'admin.v.unique';
            }
            if ((!$row || $password !== '') && !Auth::validPassword($password)) {
                $errors['password'] = 'admin.v.password';
            }
            if ($row && (int) $row['id'] === (int) Auth::user()['id'] && $values['role'] !== 'admin') {
                $errors['role'] = 'admin.v.ownRole';
            }
            if (!$errors) {
                $data = $values + ['updated_at' => A::now()];
                if ($password !== '') {
                    $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
                    $data['failed_attempts'] = 0;
                    $data['locked_until'] = null;
                }
                if ($row) {
                    Db::update('admins', $data, 'id = ?', [$row['id']]);
                } else {
                    $data['created_at'] = A::now();
                    Db::insert('admins', $data);
                }
                A::flash('ok', 'admin.flash.saved');
                A::redirect('/admin/users');
            }
            A::flash('error', 'admin.flash.fixErrors');
        }
        A::render('user-edit', ['title' => A::t($row ? 'admin.users.edit' : 'admin.users.new'), 'section' => 'users', 'row' => $row, 'values' => $values, 'errors' => $errors], $errors ? 422 : 200);
    }

    private static function delete(array $row): void
    {
        if ((int) $row['id'] === (int) Auth::user()['id']) {
            A::flash('error', 'admin.flash.cannotDeleteSelf');
        } elseif ($row['role'] === 'admin' && (int) Db::value("SELECT COUNT(*) FROM admins WHERE role = 'admin'") <= 1) {
            A::flash('error', 'admin.flash.lastAdmin');
        } else {
            Db::exec('DELETE FROM admins WHERE id = ?', [$row['id']]);
            A::flash('ok', 'admin.flash.deleted');
        }
        A::redirect('/admin/users');
    }
}
