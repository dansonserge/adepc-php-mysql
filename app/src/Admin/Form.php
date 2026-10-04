<?php
declare(strict_types=1);

namespace Adepc\Admin;

use Adepc\Db;
use Adepc\Site;

/** Renders and reads the fields declared in Schema. */
final class Form
{
    public const INPUT = 'block w-full border border-ink/25 bg-white px-3 py-2.5 text-body focus:border-blue focus:outline-none aria-[invalid=true]:border-ember';

    /** Columns a field stores: l10n → name_fr/name_en (resources) or key.fr/key.en (settings). */
    public static function columns(array $f, string $sep = '_'): array
    {
        return $f[1] === 'l10n' ? [$f[0] . $sep . 'fr', $f[0] . $sep . 'en'] : [$f[0]];
    }

    public static function render(array $f, callable $get, string $prefix, array $errors = [], string $sep = '_'): string
    {
        [$name, $type] = $f;
        $label = A::t($f['label'] ?? 'admin.f.' . $name);
        $id = 'f-' . preg_replace('/[^a-z0-9]+/i', '-', $prefix . '-' . $name);
        $error = $errors[$name] ?? null;
        $req = !empty($f['required']);
        $help = isset($f['help']) ? '<p class="mt-1.5 text-small text-stone">' . e(A::t($f['help'])) . '</p>' : '';
        $show = isset($f['show_if']) ? ' data-show-if="' . e($prefix . '[' . $f['show_if'][0] . ']') . '" data-show-values="' . e(implode(',', $f['show_if'][1])) . '"' : '';
        $input = self::input($f, $get, $prefix, $id, $sep, $error !== null);
        $err = $error ? '<p class="mt-1.5 text-small font-semibold text-ember">' . e(A::t($error)) . '</p>' : '';
        $star = $req ? ' <span aria-hidden="true" class="text-ember">*</span>' : '';
        if ($type === 'checkbox') {
            return '<div class="py-1"' . $show . '>' . $input . $help . $err . '</div>';
        }
        $labelTag = in_array($type, ['l10n', 'churches', 'services'], true)
            ? '<p class="caption mb-2 text-stone">' . e($label) . $star . '</p>'
            : '<label for="' . e($id) . '" class="caption mb-2 block text-stone">' . e($label) . $star . '</label>';
        return '<div' . $show . '>' . $labelTag . $input . $help . $err . '</div>';
    }

    private static function input(array $f, callable $get, string $prefix, string $id, string $sep, bool $invalid): string
    {
        [$name, $type] = $f;
        $n = static fn (string $col) => e($prefix . '[' . $col . ']');
        $inv = $invalid ? ' aria-invalid="true"' : '';
        $req = !empty($f['required']) ? ' required' : '';
        $value = $type === 'l10n' ? null : $get($name);
        switch ($type) {
            case 'l10n':
                $html = '<div class="grid gap-3 md:grid-cols-2">';
                foreach (A::LOCALES as $l) {
                    $col = $name . $sep . $l;
                    $v = (string) $get($col);
                    $field = !empty($f['multiline'])
                        ? '<textarea id="' . e($id . '-' . $l) . '" name="' . $n($col) . '" rows="4" lang="' . $l . '" class="' . self::INPUT . '"' . $inv . $req . '>' . e($v) . '</textarea>'
                        : '<input id="' . e($id . '-' . $l) . '" name="' . $n($col) . '" value="' . e($v) . '" lang="' . $l . '" class="' . self::INPUT . '"' . $inv . $req . '>';
                    $html .= '<label class="block"><span class="mb-1 block text-caption font-semibold tracking-[0.08em] text-stone uppercase">' . e(Site::locale($l)['name'] ?? $l) . '</span>' . $field . '</label>';
                }
                return $html . '</div>';
            case 'textarea':
            case 'lines':
                return '<textarea id="' . e($id) . '" name="' . $n($name) . '" rows="' . ($type === 'lines' ? 3 : 5) . '" class="' . self::INPUT . '"' . $inv . $req . '>' . e((string) $value) . '</textarea>';
            case 'checkbox':
                return '<input type="hidden" name="' . $n($name) . '" value="0"><label class="inline-flex cursor-pointer items-center gap-3 text-body"><input id="' . e($id) . '" type="checkbox" name="' . $n($name) . '" value="1" class="check h-5 w-5 shrink-0 cursor-pointer border-2 border-ink"' . ((int) $value === 1 ? ' checked' : '') . '>' . e(A::t($f['label'] ?? 'admin.f.' . $name)) . '</label>';
            case 'select':
            case 'church':
                $opts = self::options($f);
                $html = '<select id="' . e($id) . '" name="' . $n($name) . '" class="' . self::INPUT . ' pr-9"' . $inv . '>';
                foreach ($opts as $v => $label) {
                    $html .= '<option value="' . e((string) $v) . '"' . ((string) $v === (string) $value ? ' selected' : '') . '>' . e($label) . '</option>';
                }
                return $html . '</select>';
            case 'churches':
                $selected = array_map('intval', (array) $get($name));
                $html = '<div class="flex flex-wrap gap-x-6 gap-y-2">';
                foreach (Db::all('SELECT id, name FROM churches ORDER BY sort, id') as $c) {
                    $html .= '<label class="inline-flex cursor-pointer items-center gap-2 text-body"><input type="checkbox" name="' . $n($name) . '[]" value="' . (int) $c['id'] . '" class="check h-5 w-5 cursor-pointer border-2 border-ink"' . (in_array((int) $c['id'], $selected, true) ? ' checked' : '') . '>' . e($c['name']) . '</label>';
                }
                return $html . '</div>';
            case 'media':
                return self::mediaPicker($f['kind'] ?? 'image', $n($name), $id, $value === null || $value === '' ? null : (int) $value, empty($f['required']));
            case 'password':
                return '<input id="' . e($id) . '" type="password" name="' . $n($name) . '" value="" autocomplete="new-password" class="' . self::INPUT . '">';
            default:
                $html5 = ['email' => 'email', 'url' => 'url', 'number' => 'number', 'decimal' => 'text', 'time' => 'time', 'date' => 'date'][$type] ?? 'text';
                $extra = $type === 'decimal' ? ' inputmode="decimal"' : '';
                $extra .= isset($f['max']) ? ' maxlength="' . (int) $f['max'] . '"' : '';
                return '<input id="' . e($id) . '" type="' . $html5 . '" name="' . $n($name) . '" value="' . e((string) $value) . '" class="' . self::INPUT . '"' . $inv . $req . $extra . '>';
        }
    }

    /** @return array<string, string> value => label */
    public static function options(array $f): array
    {
        if ($f[1] === 'church') {
            $out = ['' => A::t('admin.o.none')];
            foreach (Db::all('SELECT id, name FROM churches ORDER BY sort, id') as $c) {
                $out[(string) $c['id']] = $c['name'];
            }
            return $out;
        }
        if (($f['source'] ?? '') === 'categories') {
            $out = [];
            foreach (Db::all('SELECT id, name_fr, name_en FROM video_categories ORDER BY sort, id') as $c) {
                $out[(string) $c['id']] = $c['name_' . A::$locale];
            }
            return $out;
        }
        if (($f['source'] ?? '') === 'videos') {
            $out = ['' => A::t('admin.o.none')];
            foreach (Db::all('SELECT id, title_fr, title_en FROM videos ORDER BY sort, id') as $v) {
                $out[(string) $v['id']] = $v['title_' . A::$locale];
            }
            return $out;
        }
        return array_map(static fn ($k) => A::t($k), $f['options'] ?? []);
    }

    public static function mediaPicker(string $kind, string $name, string $id, ?int $value, bool $optional): string
    {
        $rows = Db::all('SELECT id, path, label, variants, alt_fr, alt_en, kind FROM media WHERE kind = ? ORDER BY sort DESC, id DESC', [$kind]);
        $html = '<div class="flex flex-wrap items-start gap-4" data-media-picker="' . e($kind) . '" data-close-label="' . e(A::t('admin.action.close')) . '">';
        $current = null;
        foreach ($rows as $r) {
            if ((int) $r['id'] === $value) {
                $current = $r;
            }
        }
        $html .= '<div class="h-24 w-32 shrink-0 overflow-hidden border border-ink/15 bg-ink/5" data-media-preview>' . ($current ? self::thumb($current, 'h-full w-full object-cover') : '') . '</div>';
        $html .= '<div class="min-w-0 flex-1"><select id="' . e($id) . '" name="' . $name . '" class="' . self::INPUT . ' pr-9" data-media-select>';
        if ($optional) {
            $html .= '<option value="">' . e(A::t('admin.o.none')) . '</option>';
        }
        foreach ($rows as $r) {
            $label = $r['label'] ?: basename($r['path']);
            $alt = $r['alt_' . A::$locale] ?? '';
            $html .= '<option value="' . (int) $r['id'] . '" data-thumb="' . e(self::thumbUrl($r)) . '"' . ((int) $r['id'] === $value ? ' selected' : '') . '>' . e('#' . $r['id'] . ' · ' . $label . ($alt ? ' — ' . mb_strimwidth($alt, 0, 60, '…') : '')) . '</option>';
        }
        $html .= '</select><button type="button" class="mt-2 text-small font-semibold text-blue underline underline-offset-4" data-media-browse>' . e(A::t('admin.media.browse')) . '</button></div></div>';
        return $html;
    }

    public static function thumbUrl(array $m): string
    {
        if ($m['kind'] !== 'image') {
            return '';
        }
        $variants = array_map('intval', array_filter(explode(',', (string) ($m['variants'] ?? ''))));
        $pick = null;
        foreach ($variants as $w) {
            if ($w >= 256) {
                $pick = $w;
                break;
            }
        }
        $pick ??= $variants ? max($variants) : null;
        return $pick ? '/media/' . dirname($m['path']) . '/' . $pick . '.webp' : '/media/' . $m['path'];
    }

    public static function thumb(array $m, string $class): string
    {
        if ($m['kind'] === 'video') {
            return '<video src="/media/' . e($m['path']) . '" class="' . e($class) . '" muted preload="metadata"></video>';
        }
        return '<img src="' . e(self::thumbUrl($m)) . '" alt="" loading="lazy" class="' . e($class) . '">';
    }

    /**
     * Reads one field from submitted input.
     * @return array<string, mixed> column => value
     */
    public static function read(array $f, array $in, array &$errors, string $sep = '_'): array
    {
        [$name, $type] = $f;
        $req = !empty($f['required']);
        $str = static fn ($v) => is_string($v) ? trim(str_replace("\r\n", "\n", $v)) : '';
        switch ($type) {
            case 'l10n':
                $out = [];
                foreach (A::LOCALES as $l) {
                    $v = $str($in[$name . $sep . $l] ?? '');
                    if ($req && $v === '') {
                        $errors[$name] = 'admin.v.required';
                    }
                    $out[$name . $sep . $l] = $v === '' ? null : $v;
                }
                return $out;
            case 'checkbox':
                $v = $in[$name] ?? '0';
                return [$name => (is_array($v) ? end($v) : $v) === '1' ? 1 : 0];
            case 'churches':
                return [$name => array_values(array_map('intval', array_filter((array) ($in[$name] ?? []), 'is_numeric')))];
            case 'media':
            case 'church':
                $v = $str($in[$name] ?? '');
                if ($req && $v === '') {
                    $errors[$name] = 'admin.v.required';
                }
                return [$name => $v === '' ? null : (int) $v];
            case 'select':
                $v = $str($in[$name] ?? '');
                $opts = self::options($f);
                if (!array_key_exists($v, $opts)) {
                    $v = (string) array_key_first($opts);
                }
                return [$name => $v];
            case 'password':
                $v = is_string($in[$name] ?? null) ? (string) $in[$name] : '';
                return $v === '' ? [] : [$name => $v];
        }
        $v = $str($in[$name] ?? '');
        if ($v === '') {
            if ($req) {
                $errors[$name] = 'admin.v.required';
            }
            return [$name => null];
        }
        $ok = match ($type) {
            'email' => filter_var($v, FILTER_VALIDATE_EMAIL) !== false,
            'url' => filter_var($v, FILTER_VALIDATE_URL) !== false && preg_match('#^https?://#i', $v),
            'time' => (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $v),
            'date' => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && checkdate((int) substr($v, 5, 2), (int) substr($v, 8, 2), (int) substr($v, 0, 4)),
            'slug' => (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $v),
            'number' => (bool) preg_match('/^-?\d+$/', $v),
            'decimal' => is_numeric($v),
            default => true,
        };
        if (!$ok) {
            $errors[$name] = 'admin.v.' . $type;
        }
        if (isset($f['max']) && mb_strlen($v, 'UTF-8') > (int) $f['max']) {
            $errors[$name] = 'admin.v.max';
        }
        return [$name => $v];
    }
}
