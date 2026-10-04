<?php
declare(strict_types=1);

namespace Adepc;

/** File cache in app/storage/cache. Values are stored as PHP so opcache serves them. */
final class Cache
{
    public static function dir(): string
    {
        return APP_DIR . '/storage/cache';
    }

    public static function remember(string $key, int $ttl, callable $build): mixed
    {
        $file = self::dir() . '/' . preg_replace('/[^a-z0-9_.-]/i', '_', $key) . '.php';
        if (is_file($file) && ($ttl === 0 || filemtime($file) > time() - $ttl)) {
            $data = @include $file;
            if (is_array($data) && array_key_exists('v', $data)) {
                return $data['v'];
            }
        }
        $value = $build();
        $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (@file_put_contents($tmp, '<?php return ' . var_export(['v' => $value], true) . ';', LOCK_EX) !== false) {
            @rename($tmp, $file);
            if (function_exists('opcache_invalidate')) {
                @opcache_invalidate($file, true);
            }
        }
        return $value;
    }

    public static function forget(string $prefix = ''): void
    {
        foreach (glob(self::dir() . '/' . $prefix . '*.php') ?: [] as $file) {
            @unlink($file);
            if (function_exists('opcache_invalidate')) {
                @opcache_invalidate($file, true);
            }
        }
    }
}
