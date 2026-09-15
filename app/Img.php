<?php
namespace Nm;

/** GD image pipeline (doc 04 §4): resize/crop + avif/webp/jpg variants + OG composition. */
final class Img
{
    public const WIDTHS = [320, 480, 640, 800];

    public static function can(string $fmt): bool
    {
        return match ($fmt) {
            'webp' => function_exists('imagewebp'),
            'avif' => function_exists('imageavif'),
            default => function_exists('imagejpeg'),
        };
    }

    public static function load(string $path): ?\GdImage
    {
        $info = @getimagesize($path);
        if (!$info) return null;
        return match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            default => null,
        };
    }

    public static function resize(\GdImage $src, int $w, int $h): \GdImage
    {
        $d = imagecreatetruecolor($w, $h);
        imagealphablending($d, false);
        imagesavealpha($d, true);
        imagecopyresampled($d, $src, 0, 0, 0, 0, $w, $h, imagesx($src), imagesy($src));
        return $d;
    }

    /** focal-point cover crop */
    public static function crop(\GdImage $src, int $tw, int $th, float $fx = .5, float $fy = .5): \GdImage
    {
        $sw = imagesx($src); $sh = imagesy($src);
        $sr = $sw / $sh; $tr = $tw / $th;
        if ($sr > $tr) { $ch = $sh; $cw = (int) round($sh * $tr); $x = (int) round(($sw - $cw) * $fx); $y = 0; }
        else { $cw = $sw; $ch = (int) round($sw / $tr); $x = 0; $y = (int) round(($sh - $ch) * $fy); }
        $d = imagecreatetruecolor($tw, $th);
        imagecopyresampled($d, $src, 0, 0, $x, $y, $tw, $th, $cw, $ch);
        return $d;
    }

    public static function save(\GdImage $im, string $path, string $fmt, int $q): int
    {
        if (!is_dir(dirname($path))) mkdir(dirname($path), 0775, true);
        match ($fmt) {
            'webp' => imagewebp($im, $path, $q),
            'avif' => imageavif($im, $path, $q),
            default => imagejpeg($im, $path, $q),
        };
        return (int) filesize($path);
    }

    /** background-key removal for the logo PNGs (DQ-02): cream #FDF9F6 ± tol → transparent */
    public static function debackground(string $path, int $tol = 14): ?\GdImage
    {
        $im = self::load($path);
        if (!$im) return null;
        imagealphablending($im, false);
        imagesavealpha($im, true);
        $w = imagesx($im); $h = imagesy($im);
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $rgb = imagecolorat($im, $x, $y);
                $r = ($rgb >> 16) & 255; $g = ($rgb >> 8) & 255; $b = $rgb & 255;
                if (abs($r - 253) <= $tol && abs($g - 249) <= $tol && abs($b - 246) <= $tol) {
                    imagesetpixel($im, $x, $y, 127 << 24);
                }
            }
        }
        return $im;
    }
}
