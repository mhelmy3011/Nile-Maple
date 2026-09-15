<?php
namespace Nm;

/** Media repository + responsive <picture> markup contract (doc 04 §4, doc 01 §6). */
final class Media
{
    public static function row(int $id): ?array { return Db::one('SELECT * FROM media WHERE id=?', [$id]); }

    public static function variants(int $id): array
    {
        return Cache::remember('data', "mv-$id", static fn() =>
            Db::all('SELECT fmt,w,path,bytes FROM media_variant WHERE media_id=? ORDER BY w', [$id]), 600);
    }

    public static function alt(int $id, ?string $lang = null): string
    {
        $lang ??= I18n::lang();
        $r = Db::one('SELECT alt FROM media_i18n WHERE media_id=? AND lang=?', [$id, $lang]);
        if ($r) return $r['alt'];
        $r = Db::one('SELECT alt FROM media_i18n WHERE media_id=? AND lang=?', [$id, cfg('default_lang')]);
        return $r['alt'] ?? '';
    }

    /**
     * <picture> markup. Emits <source> only for formats that actually exist (graceful
     * degradation when a build host lacks AVIF, doc 07 risk register).
     */
    public static function img(int $id, string $fallbackAlt = '', string $mode = 'lazy', string $sizes = '(min-width:1024px) 25vw, 50vw', ?int $forceW = null): string
    {
        $m = self::row($id);
        if (!$m) return '';
        $vs = self::variants($id);
        if (!$vs) return '';
        $alt = self::alt($id) ?: $fallbackAlt;
        $byFmt = [];
        foreach ($vs as $v) $byFmt[$v['fmt']][$v['w']] = $v;
        $base = '/assets/media/';
        $srcset = static function (string $fmt) use ($byFmt, $base): string {
            $parts = [];
            foreach ($byFmt[$fmt] as $w => $v) $parts[] = $base . $v['path'] . ' ' . $w . 'w';
            return implode(', ', $parts);
        };
        $widest = static function (string $fmt) use ($byFmt): ?array {
            if (empty($byFmt[$fmt])) return null;
            ksort($byFmt[$fmt]);
            return end($byFmt[$fmt]);
        };
        $out = '<picture>';
        foreach (['avif', 'webp'] as $fmt) {
            if ($w = $widest($fmt)) {
                $out .= '<source type="image/' . $fmt . '" srcset="' . htmlspecialchars($srcset($fmt)) . '" sizes="' . htmlspecialchars($sizes) . '">';
            }
        }
        $j = $widest('jpg') ?? $widest('webp') ?? $widest('avif');
        $lazy = $mode === 'lazy' ? ' loading="lazy" decoding="async"' : ' decoding="async"' . ($mode === 'hero' ? ' fetchpriority="high"' : '');
        $out .= '<img src="' . htmlspecialchars($base . $j['path']) . '"'
            . ' width="' . (int) $m['width'] . '" height="' . (int) $m['height'] . '"'
            . ' alt="' . htmlspecialchars($alt) . '"' . $lazy . '>';
        return $out . '</picture>';
    }

    /** best URL for OG/meta (jpg 800 or widest) */
    public static function url(int $id, int $preferW = 800, string $fmt = 'jpg'): string
    {
        $vs = self::variants($id);
        $pick = null;
        foreach ($vs as $v) if ($v['fmt'] === $fmt && $v['w'] <= $preferW) $pick = $v;
        if (!$pick) foreach ($vs as $v) if ($v['fmt'] === 'jpg') $pick = $v;
        if (!$pick) $pick = $vs[0] ?? null;
        return $pick ? '/assets/media/' . $pick['path'] : '';
    }

    public static function usage(int $id): array
    {
        $u = [];
        foreach ([['products', 'card_media_id'], ['categories', 'cover_media_id'], ['services', 'media_id'], ['posts', 'cover_media_id']] as [$t, $c]) {
            $n = (int) Db::val("SELECT COUNT(*) FROM $t WHERE $c=?", [$id]);
            if ($n) $u[] = "$n $t";
        }
        return $u;
    }
}
