<?php
namespace Nm;

/** File-based token bucket (shared hosting has no Redis, doc 04 §7). */
final class RateLimit
{
    public static function hit(string $bucket, int $max, int $windowSec): bool
    {
        $dir = nm_path('cache/data');
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $f = $dir . '/rl-' . preg_replace('/[^A-Za-z0-9_-]/', '_', $bucket) . '.cache';
        $now = time();
        $st = is_file($f) ? json_decode((string) file_get_contents($f), true) : null;
        if (!$st || ($now - $st['t0']) > $windowSec) $st = ['t0' => $now, 'n' => 0];
        $st['n']++;
        file_put_contents($f, json_encode($st), LOCK_EX);
        return $st['n'] <= $max;
    }
    public static function reset(string $bucket): void
    {
        @unlink(nm_path('cache/data') . '/rl-' . preg_replace('/[^A-Za-z0-9_-]/', '_', $bucket) . '.cache');
    }
}
