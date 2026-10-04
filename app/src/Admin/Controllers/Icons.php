<?php
declare(strict_types=1);

namespace Adepc\Admin\Controllers;

use Adepc\Admin\A;
use Adepc\Admin\Router;
use Adepc\Http;
use Adepc\SvgSanitizer;

/** Icon files (public/media/icons/*.svg): replace with a sanitized SVG, or reset. */
final class Icons
{
    public static function dir(): string
    {
        return PUBLIC_DIR . '/media/icons';
    }

    public static function keys(): array
    {
        return array_map(static fn ($f) => basename($f, '.svg'), glob(APP_DIR . '/database/seed/icons/*.svg') ?: []);
    }

    public static function handle(?string $key): void
    {
        if ($key !== null && Http::isPost()) {
            if (!in_array($key, self::keys(), true)) {
                Router::notFound();
                return;
            }
            if (!empty($_POST['reset'])) {
                copy(APP_DIR . '/database/seed/icons/' . $key . '.svg', self::dir() . '/' . $key . '.svg');
                A::flash('ok', 'admin.flash.iconReset');
            } else {
                $file = $_FILES['svg'] ?? null;
                $raw = $file && $file['error'] === UPLOAD_ERR_OK && $file['size'] < 100000 ? (string) file_get_contents($file['tmp_name']) : '';
                $clean = $raw !== '' ? SvgSanitizer::clean($raw) : null;
                if ($clean === null) {
                    A::flash('error', 'admin.flash.iconInvalid');
                } else {
                    file_put_contents(self::dir() . '/' . $key . '.svg', $clean . "\n");
                    A::flash('ok', 'admin.flash.saved');
                }
            }
            A::changed();
            A::redirect('/admin/icons');
        }
        A::render('icons', ['title' => A::t('admin.nav.icons'), 'section' => 'icons', 'keys' => self::keys()]);
    }
}
