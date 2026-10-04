<?php
declare(strict_types=1);

namespace Adepc;

use GdImage;
use RuntimeException;

/**
 * Turns an uploaded image into what the site serves: the original (re-encoded,
 * which strips metadata and any hidden payload), WebP copies at every size the
 * pages ask for, and an 8 px blur placeholder. Uses GD only (standard on cPanel).
 */
final class MediaProcessor
{
    public const MAX_SIDE = 2560;

    public static function root(): string
    {
        return PUBLIC_DIR . '/media';
    }

    /** @return array{width:int,height:int,variants:string,blur:string,mime:string,path:string} */
    public static function process(string $dir, string $source, bool $reencodeOriginal): array
    {
        @ini_set('memory_limit', '512M');
        $info = @getimagesize($source);
        if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw new RuntimeException('unsupported_image');
        }
        $img = self::open($source, $info[2]);
        $img = self::orient($img, $source, $info[2]);
        $alpha = $info[2] !== IMAGETYPE_JPEG;

        // Very large photos are scaled down; everything the site needs fits in 2560 px.
        $w = imagesx($img);
        $h = imagesy($img);
        if (max($w, $h) > self::MAX_SIDE) {
            $scale = self::MAX_SIDE / max($w, $h);
            $img = self::resize($img, (int) round($w * $scale), (int) round($h * $scale), $alpha);
            $w = imagesx($img);
            $h = imagesy($img);
        }

        $abs = self::root() . '/' . $dir;
        if (!is_dir($abs) && !mkdir($abs, 0755, true) && !is_dir($abs)) {
            throw new RuntimeException('cannot_write');
        }
        $ext = $alpha ? 'png' : 'jpg';
        $original = $abs . '/original.' . $ext;
        if ($reencodeOriginal || !is_file($original)) {
            $alpha ? imagepng($img, $original, 9) : imagejpeg($img, $original, 88);
        }

        $quality = $alpha ? 85 : 75;
        $widths = Img::variantWidths($w);
        foreach ($widths as $vw) {
            $vh = max(1, (int) round($h * $vw / $w));
            $copy = $vw === $w ? $img : self::resize($img, $vw, $vh, $alpha);
            if ($alpha) {
                imagesavealpha($copy, true);
            }
            imagewebp($copy, $abs . '/' . $vw . '.webp', $quality);
        }

        // Same blur dimensions next/image used (8 px on the long side).
        [$bw, $bh] = $w >= $h ? [8, max(1, (int) round($h / $w * 8))] : [max(1, (int) round($w / $h * 8)), 8];
        $tiny = self::resize($img, $bw, $bh, false);
        ob_start();
        imagejpeg($tiny, null, 70);
        $blur = 'data:image/jpeg;base64,' . base64_encode((string) ob_get_clean());

        return [
            'width' => $w,
            'height' => $h,
            'variants' => implode(',', $widths),
            'blur' => $blur,
            'mime' => $alpha ? 'image/png' : 'image/jpeg',
            'path' => $dir . '/original.' . $ext,
        ];
    }

    private static function open(string $file, int $type): GdImage
    {
        $img = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($file),
            IMAGETYPE_PNG => @imagecreatefrompng($file),
            IMAGETYPE_WEBP => @imagecreatefromwebp($file),
            default => false,
        };
        if (!$img instanceof GdImage) {
            throw new RuntimeException('unsupported_image');
        }
        if (!imageistruecolor($img)) {
            imagepalettetotruecolor($img);
        }
        return $img;
    }

    /** Applies the camera's EXIF rotation so phone photos stand upright. */
    private static function orient(GdImage $img, string $file, int $type): GdImage
    {
        if ($type !== IMAGETYPE_JPEG || !function_exists('exif_read_data')) {
            return $img;
        }
        $exif = @exif_read_data($file);
        $o = (int) ($exif['Orientation'] ?? 1);
        $rotated = match ($o) {
            3 => imagerotate($img, 180, 0),
            6 => imagerotate($img, -90, 0),
            8 => imagerotate($img, 90, 0),
            default => $img,
        };
        return $rotated instanceof GdImage ? $rotated : $img;
    }

    private static function resize(GdImage $src, int $w, int $h, bool $alpha): GdImage
    {
        $dst = imagecreatetruecolor($w, $h);
        if ($alpha) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $w, $h, imagesx($src), imagesy($src));
        return $dst;
    }

    /** Deletes every file of an image (original and variants). */
    public static function remove(string $path): void
    {
        $dir = self::root() . '/' . dirname($path);
        if (str_starts_with(dirname($path), 'images/')) {
            foreach (glob($dir . '/*') ?: [] as $f) {
                @unlink($f);
            }
            @rmdir($dir);
        } else {
            @unlink(self::root() . '/' . $path);
        }
    }
}
