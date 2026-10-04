<?php
declare(strict_types=1);

namespace Adepc;

/**
 * Renders <img> exactly as next/image did (same attributes, inline styles,
 * blur placeholder and srcset selection), pointing at pre-generated WebP files.
 */
final class Img
{
    public const DEVICE_SIZES = [640, 750, 828, 1080, 1200, 1920, 2048, 3840];
    public const IMAGE_SIZES = [32, 48, 64, 96, 128, 256, 384];

    /** @var list<array{srcset: string, sizes: string}> image preloads for <head> */
    public static array $preloads = [];

    public static function allSizes(): array
    {
        $all = array_merge(self::IMAGE_SIZES, self::DEVICE_SIZES);
        sort($all);
        return $all;
    }

    /** Widths a source image is resized to: every Next size up to its width, plus its width. */
    public static function variantWidths(int $originalWidth): array
    {
        $widths = array_values(array_filter(self::allSizes(), static fn ($w) => $w < $originalWidth));
        $widths[] = $originalWidth;
        return $widths;
    }

    /** next/image getWidths() for the "w" descriptor case. */
    public static function widthsFor(?string $sizes): array
    {
        if ($sizes !== null && $sizes !== '') {
            preg_match_all('/(^|\s)(1?\d?\d)vw/', $sizes, $m);
            if ($m[2]) {
                $ratio = min(array_map('intval', $m[2])) * 0.01;
                return array_values(array_filter(self::allSizes(), static fn ($s) => $s >= self::DEVICE_SIZES[0] * $ratio));
            }
            return self::allSizes();
        }
        return self::DEVICE_SIZES;
    }

    /** @return array{0: string, 1: string} [srcset, src] */
    public static function sources(array $media, ?string $sizes): array
    {
        $available = array_map('intval', array_filter(explode(',', (string) $media['variants'])));
        $original = (int) $media['width'];
        if (!$available) {
            $url = Site::mediaUrl($media);
            return ['', $url];
        }
        $base = '/media/' . dirname($media['path']) . '/';
        $parts = [];
        $seen = [];
        foreach (self::widthsFor($sizes) as $w) {
            $use = $w >= $original ? $original : $w;
            if (!in_array($use, $available, true) || isset($seen[$use])) {
                continue;
            }
            $seen[$use] = true;
            $parts[] = $base . $use . '.webp ' . $use . 'w';
        }
        $largest = max(array_keys($seen) ?: [$original]);
        return [implode(', ', $parts), $base . $largest . '.webp'];
    }

    /** next/image's blurred SVG placeholder around the tiny JPEG. */
    public static function blurStyle(array $media, string $position): string
    {
        $w = (int) $media['width'];
        $h = (int) $media['height'];
        if ($w >= $h) {
            $bw = 8;
            $bh = max((int) round($h / max($w, 1) * 8), 1);
        } else {
            $bw = max((int) round($w / max($h, 1) * 8), 1);
            $bh = 8;
        }
        $svg = "%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 " . ($bw * 40) . ' ' . ($bh * 40) . "'%3E"
            . "%3Cfilter id='b' color-interpolation-filters='sRGB'%3E%3CfeGaussianBlur stdDeviation='20'/%3E"
            . "%3CfeColorMatrix values='1 0 0 0 0 0 1 0 0 0 0 0 1 0 0 0 0 0 100 -1' result='s'/%3E"
            . "%3CfeFlood x='0' y='0' width='100%25' height='100%25'/%3E%3CfeComposite operator='out' in='s'/%3E"
            . "%3CfeComposite in2='SourceGraphic'/%3E%3CfeGaussianBlur stdDeviation='20'/%3E%3C/filter%3E"
            . "%3Cimage width='100%25' height='100%25' x='0' y='0' preserveAspectRatio='none' style='filter: url(%23b);' href='"
            . $media['blur'] . "'/%3E%3C/svg%3E";
        return 'background-size:cover;background-position:' . $position
            . ';background-repeat:no-repeat;background-image:url("data:image/svg+xml;charset=utf-8,' . $svg . '")';
    }

    /**
     * Options: alt, class, sizes, fill (bool), loading ('lazy'|'eager'|null when preloaded),
     * preload (bool), blur (bool), position (object-position), attrs (extra attributes).
     */
    public static function tag(array $media, array $o): string
    {
        $fill = (bool) ($o['fill'] ?? false);
        $sizes = $o['sizes'] ?? null;
        [$srcset, $src] = self::sources($media, $sizes);
        $preload = (bool) ($o['preload'] ?? false);
        $loading = $preload ? null : ($o['loading'] ?? 'lazy');
        if ($preload || $loading === 'eager') {
            self::$preloads[] = ['srcset' => $srcset, 'sizes' => (string) $sizes];
        }

        $style = [];
        if ($fill) {
            $style[] = 'position:absolute;height:100%;width:100%;left:0;top:0;right:0;bottom:0';
        }
        $position = $o['position'] ?? null;
        if ($position !== null) {
            $style[] = 'object-position:' . $position;
        }
        $style[] = 'color:transparent';
        if (($o['blur'] ?? false) && !empty($media['blur'])) {
            $style[] = self::blurStyle($media, $position ?? '50% 50%');
        }

        $a = ['alt' => (string) ($o['alt'] ?? '')];
        foreach ((array) ($o['attrs'] ?? []) as $k => $v) {
            $a[$k] = $v;
        }
        if ($loading !== null) {
            $a['loading'] = $loading;
        }
        if (!$fill) {
            $a['width'] = (string) $media['width'];
            $a['height'] = (string) $media['height'];
        }
        $a['decoding'] = 'async';
        $a['data-nimg'] = $fill ? 'fill' : '1';
        $a['class'] = (string) ($o['class'] ?? '');
        $a['style'] = implode(';', $style);
        if ($sizes !== null) {
            $a['sizes'] = $sizes;
        }
        if ($srcset !== '') {
            $a['srcset'] = $srcset;
        }
        $a['src'] = $src;
        return '<img' . attrs($a) . '>';
    }
}
