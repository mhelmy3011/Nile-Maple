<?php
namespace Nm;

/** Small shared helpers. */
final class Util
{
    public static function e(?string $s): string { return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
    public static function ipHash(): string { return hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? 'cli') . '|' . cfg('env')); }
    public static function ua(): string { return mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250); }
    public static function isCli(): bool { return PHP_SAPI === 'cli' || PHP_SAPI === 'wasm'; }
    public static function json(mixed $v, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    public static function redirect(string $to, int $code = 302): never
    {
        header('Location: ' . $to, true, $code);
        exit;
    }
    public static function startsWith(string $h, string $n): bool { return str_starts_with($h, $n); }
    public static function tempBadge(?float $min, ?float $max, string $unit = 'C'): string
    {
        if ($min === null && $max === null) return '';
        $fmt = static fn($v) => rtrim(rtrim(number_format((float) $v, 1, '.', ''), '0'), '.');
        return $min !== null && $max !== null ? $fmt($min) . '–' . $fmt($max) . ' °' . $unit : $fmt($min ?? $max) . ' °' . $unit;
    }
    /**
     * True when a piece of text IS (essentially) a storage temperature: "0–4 °C",
     * "Généralement 8 °C", "Généralement -18 °C", "عادة 8 °م" (Arabic Celsius),
     * "Typically 0-4 C", "Keep frozen at -18 C or below".
     *
     * Client update (2026-09-21): temperatures are no longer displayed on product pages.
     * Rows whose value matches this are hidden (spec rows, JSON-LD); rows with real
     * handling guidance (e.g. canned storage notes, no temperature figures) are kept.
     * Deliberately narrow: a bare "م"/"C" without a figure or degree sign is NOT matched.
     */
    public static function hasTempText(string $v): bool
    {
        return (bool) preg_match('/°\s*[CFم]|℃|-?\d+(?:\.\d+)?\s?C\b/i', $v);
    }
    public static function waLink(string $text = ''): string
    {
        $num = preg_replace('/\D/', '', (string) Settings::get('contact.whatsapp'));
        return 'https://wa.me/' . $num . ($text ? '?text=' . rawurlencode($text) : '');
    }
}