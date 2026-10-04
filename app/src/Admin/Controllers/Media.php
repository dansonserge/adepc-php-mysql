<?php
declare(strict_types=1);

namespace Adepc\Admin\Controllers;

use Adepc\Admin\A;
use Adepc\Admin\Form;
use Adepc\Admin\Router;
use Adepc\Admin\Usage;
use Adepc\Db;
use Adepc\Http;
use Adepc\MediaProcessor;

/** Photo and video library: upload, describe (alt text, focus point), replace, delete. */
final class Media
{
    public const VIDEO_TYPES = ['video/mp4' => 'mp4', 'video/webm' => 'webm'];

    public static function handle(?string $id, ?string $action): void
    {
        if ($id === null) {
            self::index();
            return;
        }
        if ($id === 'upload' && Http::isPost()) {
            self::upload();
            return;
        }
        $row = ctype_digit($id) ? Db::one('SELECT * FROM media WHERE id = ?', [(int) $id]) : null;
        if (!$row) {
            Router::notFound();
            return;
        }
        if (Http::isPost()) {
            match ($action) {
                'delete' => self::delete($row),
                'replace' => self::replace($row),
                default => self::save($row),
            };
            return;
        }
        self::edit($row, []);
    }

    public static function limit(): int
    {
        $parse = static function (string $v): int {
            $n = (int) $v;
            return match (strtolower(substr(trim($v), -1))) {
                'g' => $n * 1024 ** 3,
                'm' => $n * 1024 ** 2,
                'k' => $n * 1024,
                default => $n,
            };
        };
        return min($parse((string) ini_get('upload_max_filesize')), $parse((string) ini_get('post_max_size')));
    }

    private static function index(): void
    {
        $kind = in_array($_GET['kind'] ?? '', ['image', 'video'], true) ? $_GET['kind'] : 'image';
        $q = trim((string) ($_GET['q'] ?? ''));
        $rows = Db::all('SELECT * FROM media WHERE kind = ? ORDER BY id DESC', [$kind]);
        if ($q !== '') {
            $rows = array_filter($rows, static fn ($r) => mb_stripos($r['label'] . ' ' . $r['alt_fr'] . ' ' . $r['alt_en'] . ' ' . $r['tags'], $q) !== false);
        }
        A::render('media-index', ['title' => A::t('admin.nav.media'), 'section' => 'media', 'rows' => $rows, 'kind' => $kind, 'q' => $q, 'limit' => self::limit()]);
    }

    private static function upload(): void
    {
        $files = $_FILES['files'] ?? null;
        if (!$files || !is_array($files['name'] ?? null)) {
            A::flash('error', 'admin.flash.uploadEmpty', ['size' => self::human(self::limit())]);
            A::redirect('/admin/media');
        }
        $done = 0;
        $last = null;
        foreach (array_keys($files['name']) as $i) {
            $err = $files['error'][$i];
            $name = (string) $files['name'][$i];
            if ($err !== UPLOAD_ERR_OK) {
                A::flash('error', in_array($err, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) ? 'admin.flash.uploadTooBig' : 'admin.flash.uploadFailed', ['file' => $name, 'size' => self::human(self::limit())]);
                continue;
            }
            $tmp = $files['tmp_name'][$i];
            $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
            try {
                $last = isset(self::VIDEO_TYPES[$mime]) ? self::storeVideo($tmp, $name, $mime) : self::storeImage($tmp, $name);
                $done++;
            } catch (\Throwable $e) {
                error_log('[upload] ' . $name . ': ' . $e->getMessage());
                A::flash('error', 'admin.flash.uploadUnsupported', ['file' => $name]);
            }
        }
        if ($done) {
            A::changed();
            A::flash('ok', 'admin.flash.uploaded', ['count' => $done]);
        }
        A::redirect($done === 1 && $last ? '/admin/media/' . $last : '/admin/media');
    }

    private static function storeImage(string $tmp, string $name): int
    {
        $id = Db::insert('media', ['kind' => 'image', 'path' => 'pending', 'mime' => 'image/jpeg', 'label' => mb_substr($name, 0, 255), 'created_at' => A::now(), 'updated_at' => A::now()]);
        try {
            $r = MediaProcessor::process('images/' . $id, $tmp, true);
        } catch (\Throwable $e) {
            Db::exec('DELETE FROM media WHERE id = ?', [$id]);
            MediaProcessor::remove('images/' . $id . '/x');
            throw $e;
        }
        Db::update('media', ['path' => $r['path'], 'mime' => $r['mime'], 'width' => $r['width'], 'height' => $r['height'], 'variants' => $r['variants'], 'blur' => $r['blur'], 'sort' => $id], 'id = ?', [$id]);
        return $id;
    }

    private static function storeVideo(string $tmp, string $name, string $mime): int
    {
        $ext = self::VIDEO_TYPES[$mime];
        $base = slugify(pathinfo($name, PATHINFO_FILENAME)) ?: 'video';
        $file = 'video/' . $base . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
        if (!move_uploaded_file($tmp, MediaProcessor::root() . '/' . $file)) {
            throw new \RuntimeException('move failed');
        }
        return Db::insert('media', ['kind' => 'video', 'path' => $file, 'mime' => $mime, 'label' => mb_substr($name, 0, 255), 'created_at' => A::now(), 'updated_at' => A::now()]);
    }

    private static function edit(array $row, array $errors): void
    {
        A::render('media-edit', [
            'title' => A::t('admin.media.edit'),
            'section' => 'media',
            'row' => $row,
            'usage' => Usage::of((int) $row['id']),
            'errors' => $errors,
            'churches' => Db::all('SELECT id, name FROM churches ORDER BY sort, id'),
        ], $errors ? 422 : 200);
    }

    private static function save(array $row): void
    {
        $in = (array) ($_POST['f'] ?? []);
        $str = static fn ($k) => trim((string) ($in[$k] ?? ''));
        $data = ['label' => $str('label') ?: $row['label']];
        $errors = [];
        if ($row['kind'] === 'image') {
            $data['alt_fr'] = $str('alt_fr');
            $data['alt_en'] = $str('alt_en');
            $focus = $str('focus');
            $data['focus'] = preg_match('/^\d{1,3}(\.\d+)?% \d{1,3}(\.\d+)?%$/', $focus) ? $focus : null;
            $data['church_id'] = ctype_digit($str('church_id')) ? (int) $str('church_id') : null;
            $data['tags'] = implode(',', array_filter(array_map('trim', explode(',', $str('tags')))));
            $data['in_gallery'] = !empty($in['in_gallery']) ? 1 : 0;
            $data['in_church_moments'] = !empty($in['in_church_moments']) ? 1 : 0;
            if (Usage::of((int) $row['id']) || $data['in_gallery'] || $data['in_church_moments']) {
                // Photos shown on the site need a description for screen readers.
                foreach (['alt_fr', 'alt_en'] as $k) {
                    if ($data[$k] === '' && !str_starts_with((string) $row['ref'], 'brand-')) {
                        $errors[$k] = 'admin.v.required';
                    }
                }
            }
        }
        if ($errors) {
            A::flash('error', 'admin.flash.fixErrors');
            self::edit(array_merge($row, $data), $errors);
            return;
        }
        Db::update('media', $data + ['updated_at' => A::now()], 'id = ?', [$row['id']]);
        A::changed();
        A::flash('ok', 'admin.flash.saved');
        A::redirect('/admin/media/' . $row['id']);
    }

    /** Replaces the file but keeps the item (and everywhere it is used). */
    private static function replace(array $row): void
    {
        $file = $_FILES['file'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            A::flash('error', 'admin.flash.uploadFailed', ['file' => (string) ($file['name'] ?? ''), 'size' => self::human(self::limit())]);
            A::redirect('/admin/media/' . $row['id']);
        }
        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        try {
            if ($row['kind'] === 'image') {
                MediaProcessor::remove($row['path']);
                $r = MediaProcessor::process('images/' . $row['id'], $file['tmp_name'], true);
                Db::update('media', ['path' => $r['path'], 'mime' => $r['mime'], 'width' => $r['width'], 'height' => $r['height'], 'variants' => $r['variants'], 'blur' => $r['blur'], 'updated_at' => A::now()], 'id = ?', [$row['id']]);
            } elseif (isset(self::VIDEO_TYPES[$mime])) {
                $new = self::storeVideo($file['tmp_name'], (string) $file['name'], $mime);
                $moved = Db::one('SELECT path, mime FROM media WHERE id = ?', [$new]);
                Db::exec('DELETE FROM media WHERE id = ?', [$new]);
                MediaProcessor::remove($row['path']);
                Db::update('media', ['path' => $moved['path'], 'mime' => $moved['mime'], 'updated_at' => A::now()], 'id = ?', [$row['id']]);
            } else {
                throw new \RuntimeException('type');
            }
        } catch (\Throwable $e) {
            error_log('[replace] ' . $e->getMessage());
            A::flash('error', 'admin.flash.uploadUnsupported', ['file' => (string) $file['name']]);
            A::redirect('/admin/media/' . $row['id']);
        }
        A::changed();
        A::flash('ok', 'admin.flash.saved');
        A::redirect('/admin/media/' . $row['id']);
    }

    private static function delete(array $row): void
    {
        if (Usage::of((int) $row['id'])) {
            A::flash('error', 'admin.flash.mediaInUse');
            A::redirect('/admin/media/' . $row['id']);
        }
        Db::exec('DELETE FROM media WHERE id = ?', [$row['id']]);
        MediaProcessor::remove($row['path']);
        A::changed();
        A::flash('ok', 'admin.flash.deleted');
        A::redirect('/admin/media?kind=' . $row['kind']);
    }

    public static function human(int $bytes): string
    {
        return $bytes >= 1048576 ? round($bytes / 1048576) . ' MB' : round($bytes / 1024) . ' KB';
    }
}
