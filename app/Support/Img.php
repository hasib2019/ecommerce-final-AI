<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Throwable;

/**
 * On-demand responsive WebP thumbnails.
 *
 * img('products/a.jpg', 400) returns /storage/cache/400/products/a.webp, generating it the
 * first time it is requested. Afterwards the web server serves the static file directly.
 */
class Img
{
    public const SIZES = [160, 320, 480, 640, 960, 1280, 1600];

    private static ?ImageManager $manager = null;

    private static ?string $base = null;

    /** Root-relative prefix for public storage ("/storage/" or "/sub/dir/storage/"). */
    private static function base(): string
    {
        return self::$base ??= rtrim(app()->runningInConsole() ? (string) parse_url((string) config('app.url'), PHP_URL_PATH) : request()->getBasePath(), '/').'/storage/';
    }

    /** Absolute URL – for Open Graph, JSON-LD, sitemaps and feeds. */
    public static function absolute(?string $path, int $width = 0): string
    {
        $url = self::url($path, $width);

        return str_starts_with($url, 'http') ? $url : url($url);
    }

    public static function url(?string $path, int $width = 0): string
    {
        if (blank($path)) {
            return rtrim(request()->getBasePath(), '/').'/images/placeholder.svg';
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($width <= 0 || in_array($ext, ['svg', 'gif', 'ico'], true)) {
            return self::base().$path;
        }

        $width = self::snap($width);
        $target = 'cache/'.$width.'/'.preg_replace('/\.[^.\/]+$/', '', $path).'.webp';
        $disk = Storage::disk('public');

        if (! $disk->exists($target)) {
            if (! self::generate($path, $target, $width)) {
                return self::base().$path;
            }
        }

        return self::base().$target;
    }

    /** "url 320w, url 640w, …" */
    public static function srcset(?string $path, array $widths = [320, 640, 960]): string
    {
        if (blank($path) || str_starts_with($path, 'http') || str_ends_with(strtolower($path), '.svg')) {
            return '';
        }

        return collect($widths)->map(fn ($w) => self::url($path, $w).' '.self::snap($w).'w')->implode(', ');
    }

    public static function generate(string $source, string $target, int $width): bool
    {
        $disk = Storage::disk('public');

        try {
            if (! $disk->exists($source)) {
                return false;
            }

            self::$manager ??= ImageManager::gd();
            $image = self::$manager->read($disk->path($source));
            $image->scaleDown(width: $width);

            $disk->makeDirectory(dirname($target));
            $image->toWebp(quality: 78)->save($disk->path($target));

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }

    /** Snap any width to the nearest predefined size so the cache stays small. */
    private static function snap(int $width): int
    {
        foreach (self::SIZES as $size) {
            if ($width <= $size) {
                return $size;
            }
        }

        return self::SIZES[count(self::SIZES) - 1];
    }

    /** Remove generated thumbnails for an original file (call after replacing an image). */
    public static function purge(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        $base = preg_replace('/\.[^.\/]+$/', '', $path).'.webp';
        foreach (self::SIZES as $size) {
            Storage::disk('public')->delete('cache/'.$size.'/'.$base);
        }
    }
}
