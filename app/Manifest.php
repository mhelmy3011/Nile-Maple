<?php
namespace Nm;

/** Build manifest reader (hashed assets, doc 04 §5). */
final class Manifest
{
    private static ?array $m = null;
    public static function data(): array
    {
        if (self::$m !== null) return self::$m;
        $f = cfg('paths.public') . '/assets/manifest.json';
        return self::$m = is_file($f) ? (json_decode((string) file_get_contents($f), true) ?: []) : [];
    }
    public static function css(): string { return '/assets/css/' . (self::data()['css'] ?? 'app.css'); }
    public static function cssInline(): string
    {
        $f = cfg('paths.public') . self::css();
        return is_file($f) ? (string) file_get_contents($f) : '';
    }
    /** Admin CSS ships as a hashed file like everything else — reading assets/src/ at render
     *  time would couple the dashboard to a directory that is not in the webroot (and warn when
     *  it is missing). */
    public static function cssAdmin(): string { return '/assets/css/' . (self::data()['css-admin'] ?? 'admin.css'); }

    public static function js(string $bundle = 'base'): string
    {
        $k = "js-$bundle";
        return '/assets/js/' . (self::data()[$k] ?? "$bundle.js");
    }
    public static function flush(): void { self::$m = null; }
}
