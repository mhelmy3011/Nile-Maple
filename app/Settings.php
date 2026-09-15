<?php
namespace Nm;

/** Key/value settings with per-language values and cached snapshot (doc 01 §3). */
final class Settings
{
    private static ?array $all = null;

    public static function all(): array
    {
        if (self::$all !== null) return self::$all;
        $rows = Cache::remember('data', 'settings', static fn() => Db::all('SELECT s_key, lang, value FROM settings'), 300);
        $map = [];
        foreach ($rows as $r) $map[$r['s_key']][$r['lang']] = $r['value'];
        return self::$all = $map;
    }
    public static function get(string $key, ?string $lang = null): string
    {
        $lang ??= I18n::lang();
        $e = self::all()[$key] ?? null;
        if (!$e) return '';
        return $e[$lang] ?? $e['*'] ?? $e[cfg('default_lang')] ?? (reset($e) ?: '');
    }
    public static function set(string $key, string $value, string $lang = '*'): void
    {
        Db::upsert('settings', ['s_key' => $key, 'lang' => $lang, 'value' => $value], ['s_key', 'lang']);
        self::$all = null; Cache::forgetGroup('data');
    }
    public static function flush(): void { self::$all = null; Cache::forgetGroup('data'); }
}
