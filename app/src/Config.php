<?php
declare(strict_types=1);

namespace Adepc;

/** Server-side configuration written by the installer (database, secret key). */
final class Config
{
    private static ?array $values = null;

    public static function file(): string
    {
        return APP_DIR . '/config.php';
    }

    public static function exists(): bool
    {
        return is_file(self::file());
    }

    public static function all(): array
    {
        if (self::$values === null) {
            self::$values = self::exists() ? (array) require self::file() : [];
        }
        return self::$values;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::all();
        foreach (explode('.', $key) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }
        return $value;
    }

    public static function write(array $values): void
    {
        $php = "<?php\n// Written by the ADEPC installer. Keep this file private.\nreturn " . var_export($values, true) . ";\n";
        file_put_contents(self::file(), $php, LOCK_EX);
        @chmod(self::file(), 0640);
        self::$values = $values;
    }
}
