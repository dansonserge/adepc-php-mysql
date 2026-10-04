<?php
declare(strict_types=1);

namespace Adepc;

use PDO;

/** Creates the tables and loads the seed content (app/database). */
final class Seeder
{
    /** Insert order respects references between tables. */
    public const TABLES = [
        'locales', 'settings', 'translations', 'pages', 'redirects', 'menu_items', 'media', 'media_slots',
        'list_items', 'churches', 'church_services', 'events', 'event_churches', 'video_categories',
        'videos', 'stories', 'contact_purposes', 'admin_notes',
    ];

    public static function dir(): string
    {
        return APP_DIR . '/database';
    }

    public static function schema(PDO $pdo): void
    {
        $sql = (string) file_get_contents(self::dir() . '/schema.sql');
        $sql = (string) preg_replace('/^\s*--.*$/m', '', $sql);
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
            $pdo->exec($statement);
        }
    }

    /** @return list<array> rows of a seed file */
    public static function rows(string $name): array
    {
        $file = self::dir() . '/seed/' . $name . '.json';
        return is_file($file) ? (array) json_decode((string) file_get_contents($file), true) : [];
    }

    public static function seed(PDO $pdo): void
    {
        $now = gmdate('Y-m-d H:i:s');
        foreach (self::TABLES as $table) {
            $rows = self::rows($table);
            if ($table === 'translations') {
                $rows = array_merge($rows, self::rows('admin_translations'));
            }
            if ($table === 'media') {
                $rows = array_map(static fn ($r) => $r + ['created_at' => $now, 'updated_at' => $now], $rows);
            }
            self::insertMany($pdo, $table, $rows);
        }
    }

    public static function insertMany(PDO $pdo, string $table, array $rows): void
    {
        foreach (array_chunk($rows, 100) as $chunk) {
            $cols = array_keys($chunk[0]);
            $placeholders = '(' . implode(',', array_fill(0, count($cols), '?')) . ')';
            $sql = sprintf(
                'INSERT INTO `%s` (%s) VALUES %s',
                $table,
                implode(',', array_map(static fn ($c) => "`$c`", $cols)),
                implode(',', array_fill(0, count($chunk), $placeholders))
            );
            $values = [];
            foreach ($chunk as $row) {
                foreach ($cols as $c) {
                    $v = $row[$c] ?? null;
                    $values[] = is_bool($v) ? (int) $v : $v;
                }
            }
            $pdo->prepare($sql)->execute($values);
        }
    }

    public static function installed(PDO $pdo): bool
    {
        try {
            return (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    public static function createAdmin(PDO $pdo, string $name, string $email, string $password, string $locale): void
    {
        $now = gmdate('Y-m-d H:i:s');
        $st = $pdo->prepare('INSERT INTO admins (email, name, password_hash, role, ui_locale, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $st->execute([strtolower(trim($email)), trim($name), password_hash($password, PASSWORD_DEFAULT), 'admin', $locale, $now, $now]);
    }
}
