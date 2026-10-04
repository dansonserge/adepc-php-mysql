<?php
declare(strict_types=1);

namespace Adepc\Admin\Controllers;

use Adepc\Admin\A;
use Adepc\Admin\Auth;
use Adepc\Admin\Content;
use Adepc\Admin\Router;
use Adepc\Admin\Schema;
use Adepc\Http;
use Adepc\Mailer;
use Adepc\Site;

final class Settings
{
    public static function handle(?string $group): void
    {
        $groups = Schema::settings();
        $group ??= array_key_first($groups);
        if ($group === 'test-email' && Http::isPost()) {
            self::testEmail();
            return;
        }
        if (!isset($groups[$group])) {
            Router::notFound();
            return;
        }
        $errors = [];
        if (Http::isPost()) {
            $errors = Content::saveSettings($groups[$group]['fields'], (array) ($_POST['s'] ?? []));
            if (!$errors) {
                A::changed();
                A::flash('ok', 'admin.flash.saved');
                A::redirect('/admin/settings/' . $group);
            }
            A::flash('error', 'admin.flash.fixErrors');
        }
        $values = Content::settings();
        if ($errors) {
            foreach ((array) ($_POST['s'] ?? []) as $k => $v) {
                $values[$k] = is_string($v) ? $v : '';
            }
        }
        A::render('settings', [
            'title' => A::t('admin.nav.settings'),
            'section' => 'settings',
            'groups' => $groups,
            'group' => $group,
            'values' => $values,
            'errors' => $errors,
        ], $errors ? 422 : 200);
    }

    private static function testEmail(): void
    {
        \Adepc\Content::flush();
        $to = (string) (Auth::user()['email'] ?? '');
        if (!Mailer::configured()) {
            A::flash('error', 'admin.flash.mailNotConfigured');
        } else {
            try {
                Mailer::send($to, A::t('admin.mail.testSubject'), A::t('admin.mail.testBody', ['site' => Site::setting('site.url')]));
                A::flash('ok', 'admin.flash.mailSent', ['email' => $to]);
            } catch (\Throwable $e) {
                error_log('[mail test] ' . $e->getMessage());
                A::flash('error', 'admin.flash.mailFailed', ['error' => mb_strimwidth($e->getMessage(), 0, 180, '…')]);
            }
        }
        A::redirect('/admin/settings/email');
    }
}
