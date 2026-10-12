<?php
declare(strict_types=1);

namespace Adepc;

use Adepc\Admin\Controllers\Resources;

/**
 * Brings an existing install up to date with the code: new tables, columns and
 * texts, without touching the content. Every step is safe to run again. A marker
 * file records the version reached, so a normal request only reads that file.
 */
final class Upgrade
{
    public const VERSION = 1;

    public static function file(): string
    {
        return Cache::dir() . '/schema-version';
    }

    public static function ensure(): void
    {
        if ((int) @file_get_contents(self::file()) >= self::VERSION) {
            return;
        }
        $lock = @fopen(self::file(), 'c+');
        if ($lock === false) {
            error_log('[upgrade] cannot write ' . self::file());
            return;
        }
        try {
            flock($lock, LOCK_EX);
            // Another request may have finished the upgrade while this one waited.
            if ((int) stream_get_contents($lock) >= self::VERSION) {
                return;
            }
            self::run();
            ftruncate($lock, 0);
            rewind($lock);
            fwrite($lock, (string) self::VERSION);
            fflush($lock);
        } catch (\Throwable $e) {
            error_log('[upgrade] ' . $e->getMessage());
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private static function run(): void
    {
        // New tables: schema.sql only uses CREATE TABLE IF NOT EXISTS.
        Seeder::schema(Db::pdo());

        // v1: each event has its own page, addressed by a slug.
        if (!Db::all("SHOW COLUMNS FROM events LIKE 'slug'")) {
            Db::pdo()->exec("ALTER TABLE events ADD COLUMN slug VARCHAR(120) NOT NULL DEFAULT '' AFTER kind");
        }
        foreach (Db::all("SELECT id, title_fr FROM events WHERE slug = '' ORDER BY id") as $r) {
            Db::update('events', ['slug' => Resources::uniqueSlug('events', (string) $r['title_fr'], (int) $r['id'])], 'id = ?', [$r['id']]);
        }
        if (!Db::all("SHOW INDEX FROM events WHERE Key_name = 'uq_event_slug'")) {
            Db::pdo()->exec('ALTER TABLE events ADD UNIQUE KEY uq_event_slug (slug)');
        }

        // Texts added since the install. Existing ones, possibly edited, are kept.
        Seeder::insertMany(Db::pdo(), 'translations', array_merge(Seeder::rows('translations'), Seeder::rows('admin_translations')), true);

        Content::flush();
        Cache::forget('admin_strings');
    }
}
