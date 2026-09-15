<?php
namespace Nm;

/** Slugger: latin transliteration + Arabic-readable slugs (doc 05 §1). */
final class Slug
{
    private const TRANSLIT = [
        'ä'=>'a','ö'=>'o','ü'=>'u','ß'=>'ss','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e','à'=>'a','â'=>'a','ç'=>'c',
        'î'=>'i','ï'=>'i','ô'=>'o','œ'=>'oe','ù'=>'u','û'=>'u','ñ'=>'n','ý'=>'y','å'=>'a','ø'=>'o','æ'=>'ae',
    ];

    public static function make(string $s, string $lang = 'en'): string
    {
        $s = trim($s);
        if ($lang === 'ar') {
            // keep Arabic letters + digits, collapse separators (readable AR slugs, doc 05)
            $s = mb_strtolower($s);
            $s = preg_replace('/[^\p{Arabic}0-9]+/u', '-', $s);
            return trim(preg_replace('/-+/', '-', $s), '-') ?: 'item';
        }
        $s = strtr($s, self::TRANSLIT);
        $s = preg_replace('/[^A-Za-z0-9]+/', '-', $s);
        $s = trim(preg_replace('/-+/', '-', strtolower($s)), '-');
        return $s ?: 'item';
    }

    public static function unique(string $base, string $lang, callable $exists): string
    {
        $slug = $base; $i = 2;
        while ($exists($slug, $lang)) { $slug = $base . '-' . $i; $i++; }
        return $slug;
    }
}
