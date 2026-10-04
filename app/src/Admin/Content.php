<?php
declare(strict_types=1);

namespace Adepc\Admin;

use Adepc\Db;
use Adepc\I18n;
use Adepc\Seeder;

/** Shared reads/writes for translations, lists, slots and settings. */
final class Content
{
    /** @return array<string, array{fr: string, en: string}> public translation keys */
    public static function translations(): array
    {
        $out = [];
        foreach (Db::all("SELECT locale, tkey, value FROM translations WHERE tkey NOT LIKE 'admin.%' ORDER BY tkey") as $r) {
            $out[$r['tkey']][$r['locale']] = $r['value'];
        }
        return $out;
    }

    /** Seeded value of every translation, for "reset to default" and placeholder checks. */
    public static function defaults(): array
    {
        static $d = null;
        if ($d === null) {
            $d = [];
            foreach (array_merge(Seeder::rows('translations'), Seeder::rows('admin_translations')) as $r) {
                $d[$r['tkey']][$r['locale']] = $r['value'];
            }
        }
        return $d;
    }

    /**
     * Saves submitted translations ($posted[locale][key] = value).
     * @return array<string, string> key => error (nothing is saved when non-empty)
     */
    public static function saveTranslations(array $posted, array $resetKeys = []): array
    {
        $current = [];
        foreach (Db::all('SELECT locale, tkey, value FROM translations') as $r) {
            $current[$r['tkey']][$r['locale']] = $r['value'];
        }
        $defaults = self::defaults();
        $errors = [];
        $changes = [];
        foreach (A::LOCALES as $l) {
            foreach ((array) ($posted[$l] ?? []) as $key => $value) {
                if (!is_string($value) || !isset($current[$key][$l])) {
                    continue;
                }
                $value = str_replace("\r\n", "\n", $value);
                if (in_array($key, $resetKeys, true) && isset($defaults[$key][$l])) {
                    $value = $defaults[$key][$l];
                }
                if ($value === $current[$key][$l]) {
                    continue;
                }
                $reference = $defaults[$key][$l] ?? $current[$key][$l];
                if (I18n::placeholders($value) !== I18n::placeholders($reference)) {
                    $errors[$key] = 'admin.v.placeholders';
                    continue;
                }
                $changes[] = [$l, $key, $value];
            }
        }
        if ($errors) {
            return $errors;
        }
        foreach ($changes as [$l, $key, $value]) {
            Db::exec('UPDATE translations SET value = ?, updated_at = ? WHERE locale = ? AND tkey = ?', [$value, A::now(), $l, $key]);
        }
        return [];
    }

    public static function listItems(string $key): array
    {
        return Db::all('SELECT * FROM list_items WHERE list_key = ? ORDER BY sort, id', [$key]);
    }

    /**
     * Saves one list from $posted[itemId|new-N][field]; rows flagged _delete are removed.
     * @return array<string, array> errors per item
     */
    public static function saveList(string $key, array $posted, bool $dryRun): array
    {
        $def = Schema::lists()[$key];
        $errors = [];
        $rows = [];
        $sort = 0;
        foreach ($posted as $itemId => $in) {
            if (!is_array($in)) {
                continue;
            }
            if (!empty($in['_delete'])) {
                $rows[] = ['id' => $itemId, 'delete' => true];
                continue;
            }
            $itemErrors = [];
            $data = ['visible' => !empty($in['visible']) ? 1 : 0];
            foreach ($def['fields'] as $f) {
                $data += Form::read($f, $in, $itemErrors);
            }
            if (str_starts_with((string) $itemId, 'new') && self::blank($data)) {
                continue;
            }
            if ($itemErrors) {
                $errors[$itemId] = $itemErrors;
            }
            $data['sort'] = ++$sort;
            $rows[] = ['id' => $itemId, 'data' => $data];
        }
        if ($dryRun || $errors) {
            return $errors;
        }
        foreach ($rows as $r) {
            if (!empty($r['delete'])) {
                if (ctype_digit((string) $r['id'])) {
                    Db::exec('DELETE FROM list_items WHERE id = ? AND list_key = ?', [(int) $r['id'], $key]);
                }
            } elseif (ctype_digit((string) $r['id'])) {
                Db::update('list_items', $r['data'], 'id = ? AND list_key = ?', [(int) $r['id'], $key]);
            } else {
                Db::insert('list_items', ['list_key' => $key] + $r['data']);
            }
        }
        return [];
    }

    private static function blank(array $data): bool
    {
        foreach ($data as $k => $v) {
            if ($k !== 'visible' && $k !== 'value_source' && $v !== null && $v !== '') {
                return false;
            }
        }
        return true;
    }

    public static function saveSlots(array $posted): void
    {
        $slots = Schema::slots();
        foreach ($posted as $key => $in) {
            if (!isset($slots[$key]) || !is_array($in)) {
                continue;
            }
            $media = isset($in['media']) && ctype_digit((string) $in['media']) ? (int) $in['media'] : null;
            $focus = trim((string) ($in['focus'] ?? ''));
            $focus = preg_match('/^\d{1,3}(\.\d+)?% \d{1,3}(\.\d+)?%$/', $focus) ? $focus : null;
            Db::exec('INSERT INTO media_slots (skey, media_id, focus) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE media_id = VALUES(media_id), focus = VALUES(focus)', [$key, $media, $focus]);
        }
    }

    /** @return array<string, string> setting key => value (raw) */
    public static function settings(): array
    {
        $out = [];
        foreach (Db::all('SELECT skey, value, is_default FROM settings') as $r) {
            $out[$r['skey']] = (string) $r['value'];
            $out['__default.' . $r['skey']] = (int) $r['is_default'];
        }
        return $out;
    }

    /**
     * Reads and validates settings fields; saves when valid.
     * @return array<string, string> errors
     */
    public static function saveSettings(array $fields, array $in): array
    {
        $errors = [];
        $data = [];
        foreach ($fields as $f) {
            $data += Form::read($f, $in, $errors, '.');
        }
        if ($errors) {
            return $errors;
        }
        $current = self::settings();
        foreach ($data as $key => $value) {
            $value = $value === null ? '' : (string) $value;
            if (array_key_exists($key, $current) && $current[$key] === $value) {
                continue;
            }
            Db::exec('INSERT INTO settings (skey, value, is_default, updated_at) VALUES (?, ?, 0, ?) ON DUPLICATE KEY UPDATE value = VALUES(value), is_default = 0, updated_at = VALUES(updated_at)', [$key, $value, A::now()]);
        }
        return [];
    }
}
