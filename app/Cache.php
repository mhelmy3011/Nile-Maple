<?php
namespace Nm;

/** File cache (doc 04 §3). Atomic writes; hash-keyed; group invalidation. */
final class Cache
{
    public static function file(string $group, string $key): string
    {
        return nm_path("cache/$group/") . preg_replace('/[^A-Za-z0-9_.-]/', '_', $key) . '.cache';
    }
    public static function get(string $group, string $key): mixed
    {
        $f = self::file($group, $key);
        if (!is_file($f)) return null;
        $r = @unserialize(file_get_contents($f), ['allowed_classes' => false]);
        return $r === false ? null : $r['v'] ?? null;
    }
    public static function set(string $group, string $key, mixed $v, int $ttl = 0): void
    {
        $f = self::file($group, $key);
        if (!is_dir(dirname($f))) mkdir(dirname($f), 0775, true);
        $tmp = $f . '.' . getmypid() . '.tmp';
        file_put_contents($tmp, serialize(['v' => $v, 't' => time(), 'ttl' => $ttl]));
        rename($tmp, $f);
    }
    public static function remember(string $group, string $key, callable $fn, int $ttl = 0): mixed
    {
        $hit = self::get($group, $key);
        if ($hit !== null) return $hit;
        $v = $fn();
        self::set($group, $key, $v, $ttl);
        return $v;
    }
    public static function forgetGroup(string $group): void
    {
        $d = nm_path("cache/$group/");
        if (!is_dir($d)) return;
        foreach (glob($d . '*.cache') ?: [] as $f) @unlink($f);
    }
    public static function forgetAll(): void
    {
        foreach (['data', 'lang', 'frag'] as $g) self::forgetGroup($g);
    }
}
