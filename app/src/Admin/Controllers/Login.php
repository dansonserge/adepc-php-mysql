<?php
declare(strict_types=1);

namespace Adepc\Admin\Controllers;

use Adepc\Admin\A;
use Adepc\Admin\Auth;
use Adepc\Content;
use Adepc\Db;
use Adepc\Http;
use Adepc\Mailer;
use Adepc\Site;

final class Login
{
    public static function login(): void
    {
        if (Auth::user()) {
            A::redirect('/admin');
        }
        $error = null;
        $email = '';
        if (Http::isPost()) {
            $email = (string) ($_POST['email'] ?? '');
            $error = Auth::attempt($email, (string) ($_POST['password'] ?? ''));
            if ($error === null) {
                A::redirect('/admin');
            }
        }
        A::render('login', ['title' => A::t('admin.login.title'), 'error' => $error, 'email' => $email, 'bare' => true], $error ? 401 : 200);
    }

    public static function forgot(): void
    {
        $sent = false;
        if (Http::isPost()) {
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $row = Db::one('SELECT id FROM admins WHERE email = ?', [$email]);
            Content::flush();
            if ($row && Mailer::configured()) {
                $token = Auth::createResetToken((int) $row['id']);
                $link = rtrim(Site::setting('site.url'), '/') . '/admin/reset?token=' . $token;
                try {
                    Mailer::send($email, A::t('admin.mail.resetSubject'), A::t('admin.mail.resetBody', ['link' => $link]));
                } catch (\Throwable $e) {
                    error_log('[reset] ' . $e->getMessage());
                }
            }
            $sent = true; // Same answer whether or not the address exists.
        }
        A::render('forgot', ['title' => A::t('admin.forgot.title'), 'sent' => $sent, 'mailReady' => Mailer::configured(), 'bare' => true]);
    }

    public static function reset(): void
    {
        $token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
        $row = Auth::adminForToken($token);
        $error = null;
        if ($row && Http::isPost()) {
            $password = (string) ($_POST['password'] ?? '');
            if (!Auth::validPassword($password)) {
                $error = 'admin.v.password';
            } else {
                Db::update('admins', ['password_hash' => password_hash($password, PASSWORD_DEFAULT), 'reset_token_hash' => null, 'reset_expires' => null, 'failed_attempts' => 0, 'locked_until' => null], 'id = ?', [$row['id']]);
                A::flash('ok', 'admin.flash.passwordReset');
                A::redirect('/admin/login');
            }
        }
        A::render('reset', ['title' => A::t('admin.reset.title'), 'valid' => (bool) $row, 'token' => $token, 'error' => $error, 'bare' => true]);
    }
}
