<?php
declare(strict_types=1);

namespace Adepc\Admin;

use Adepc\Http;

final class Router
{
    public static function dispatch(string $path): void
    {
        header('X-Frame-Options: DENY');
        header('Cache-Control: no-store');
        header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob: https://i.ytimg.com; media-src 'self' blob:; style-src 'self' 'unsafe-inline'; script-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
        header('X-Robots-Tag: noindex, nofollow');

        require_once __DIR__ . '/helpers.php';
        Auth::start();
        A::boot();

        if (Http::isPost() && !Auth::checkCsrf()) {
            A::render('error', ['title' => A::t('admin.error.csrfTitle'), 'message' => A::t('admin.error.csrf')], 403);
            return;
        }

        $parts = array_values(array_filter(explode('/', substr($path, strlen('/admin'))), 'strlen'));
        $first = $parts[0] ?? '';

        // Public admin routes.
        if ($first === 'login') {
            Controllers\Login::login();
            return;
        }
        if ($first === 'forgot') {
            Controllers\Login::forgot();
            return;
        }
        if ($first === 'reset') {
            Controllers\Login::reset();
            return;
        }
        if ($first === 'ui-language' && Http::isPost()) {
            Controllers\Account::uiLanguage();
            return;
        }

        if (!Auth::user()) {
            A::redirect('/admin/login');
        }
        if ($first === 'logout' && Http::isPost()) {
            Auth::logout();
            A::redirect('/admin/login');
        }

        $id = $parts[1] ?? null;
        match ($first) {
            '' => Controllers\Dashboard::show(),
            'pages' => Controllers\Pages::handle($id),
            'churches', 'events', 'videos', 'stories', 'categories', 'purposes', 'redirects' => Controllers\Resources::handle($first, $id, $parts[2] ?? null),
            'media' => Controllers\Media::handle($id, $parts[2] ?? null),
            'icons' => Controllers\Icons::handle($id),
            'menus' => Controllers\Menus::handle(),
            'texts' => Controllers\Texts::handle(),
            'settings' => Controllers\Settings::handle($id),
            'languages' => Controllers\Languages::handle(),
            'users' => Auth::isAdmin() ? Controllers\Users::handle($id) : self::forbidden(),
            'account' => Controllers\Account::handle(),
            default => self::notFound(),
        };
    }

    public static function notFound(): void
    {
        A::render('error', ['title' => A::t('admin.error.notFoundTitle'), 'message' => A::t('admin.error.notFound')], 404);
    }

    public static function forbidden(): void
    {
        A::render('error', ['title' => A::t('admin.error.forbiddenTitle'), 'message' => A::t('admin.error.forbidden')], 403);
    }
}
