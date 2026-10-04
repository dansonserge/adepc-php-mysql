<?php
declare(strict_types=1);

namespace Adepc;

/**
 * Message formatting compatible with the subset of ICU the site uses:
 * {var} interpolation and {count, plural, =0 {…} one {…} other {…}} with #.
 */
final class I18n
{
    public static function format(string $pattern, array $vars, string $locale): string
    {
        if (!str_contains($pattern, '{')) {
            return $pattern;
        }
        $out = '';
        $len = strlen($pattern);
        for ($i = 0; $i < $len; $i++) {
            $ch = $pattern[$i];
            if ($ch !== '{') {
                $out .= $ch;
                continue;
            }
            $end = self::matching($pattern, $i);
            if ($end === null) {
                $out .= substr($pattern, $i);
                break;
            }
            $out .= self::argument(substr($pattern, $i + 1, $end - $i - 1), $vars, $locale);
            $i = $end;
        }
        return $out;
    }

    private static function matching(string $s, int $open): ?int
    {
        $depth = 0;
        $len = strlen($s);
        for ($i = $open; $i < $len; $i++) {
            if ($s[$i] === '{') {
                $depth++;
            } elseif ($s[$i] === '}') {
                $depth--;
                if ($depth === 0) {
                    return $i;
                }
            }
        }
        return null;
    }

    private static function argument(string $inner, array $vars, string $locale): string
    {
        if (preg_match('/^\s*(\w+)\s*,\s*plural\s*,(.*)$/s', $inner, $m)) {
            $n = (float) ($vars[$m[1]] ?? 0);
            $options = self::options($m[2]);
            $message = $options['=' . (string) (int) $n] ?? $options[self::category($n, $locale)] ?? $options['other'] ?? '';
            $formatted = self::format($message, $vars, $locale);
            return str_replace('#', self::number($n), $formatted);
        }
        $name = trim($inner);
        return array_key_exists($name, $vars) ? (string) $vars[$name] : '{' . $inner . '}';
    }

    /** @return array<string, string> */
    private static function options(string $s): array
    {
        $options = [];
        $len = strlen($s);
        $i = 0;
        while ($i < $len) {
            if (!preg_match('/\G\s*([=\w]+)\s*/', $s, $m, 0, $i)) {
                break;
            }
            $i += strlen($m[0]);
            if (($s[$i] ?? '') !== '{') {
                break;
            }
            $end = self::matching($s, $i);
            if ($end === null) {
                break;
            }
            $options[$m[1]] = substr($s, $i + 1, $end - $i - 1);
            $i = $end + 1;
        }
        return $options;
    }

    /** CLDR cardinal plural categories for the site's languages. */
    public static function category(float $n, string $locale): string
    {
        if ($locale === 'fr') {
            return ($n >= 0 && $n < 2) ? 'one' : 'other';
        }
        return $n == 1 ? 'one' : 'other';
    }

    private static function number(float $n): string
    {
        return floor($n) == $n ? (string) (int) $n : (string) $n;
    }

    /** Placeholders a translation must keep, e.g. ["{city}", "{count, plural…}"]. */
    public static function placeholders(string $s): array
    {
        preg_match_all('/\{\s*(\w+)\s*(,\s*plural)?/', $s, $m);
        $found = [];
        foreach ($m[1] as $k => $name) {
            $found[] = $name . ($m[2][$k] !== '' ? ',plural' : '');
        }
        sort($found);
        return array_values(array_unique($found));
    }
}
