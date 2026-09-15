<?php
namespace Nm;

/** UI-string i18n with EN fallback + compiled cache (doc 04 §3). */
final class I18n
{
    private static array $bag = [];
    private static array $loaded = [];
    private static string $lang = 'en';

    /**
     * Loads the default bag (fallback) plus the active one. The bag is keyed by a file hash so a
     * deployed lang file is picked up without clearing the cache by hand (doc 04 §3).
     */
    public static function boot(string $lang): void
    {
        self::$lang = in_array($lang, cfg('langs'), true) ? $lang : cfg('default_lang');
        self::bag(cfg('default_lang'));
        self::bag(self::$lang);
    }

    private static function bag(string $l): void
    {
        $f = cfg('paths.app') . "/lang/$l.php";
        $key = 'ui-' . $l . '-' . (is_file($f) ? substr((string) md5_file($f), 0, 10) : '0');
        if (self::$loaded[$key] ?? false) return;
        self::$bag[$l] = Cache::remember('lang', $key, static fn() => is_file($f) ? (array) require $f : []);
        self::$loaded[$key] = true;
    }
    public static function lang(): string { return self::$lang; }
    public static function isRtl(): bool { return in_array(self::$lang, cfg('rtl'), true); }
    public static function dir(): string { return self::isRtl() ? 'rtl' : 'ltr'; }
    public static function t(string $key, array $p = []): string
    {
        $s = self::$bag[self::$lang][$key] ?? self::$bag[cfg('default_lang')][$key] ?? $key;
        foreach ($p as $k => $v) $s = str_replace('{' . $k . '}', (string) $v, $s);
        return $s;
    }
    /** locale-formatted date (Western digits everywhere else, doc D-11) */
    public static function date(string $iso, string $style = 'medium'): string
    {
        $ts = strtotime($iso);
        if (!$ts) return $iso;
        $loc = ['en' => 'en-GB', 'ar' => 'ar-EG-u-ca-gregory-nu-latn', 'fr' => 'fr-FR'][self::$lang];
        if (class_exists(\IntlDateFormatter::class)) {
            $f = new \IntlDateFormatter($loc, $style === 'long' ? \IntlDateFormatter::LONG : \IntlDateFormatter::MEDIUM, \IntlDateFormatter::NONE);
            return $f->format($ts);
        }
        return date('j M Y', $ts);
    }
}
