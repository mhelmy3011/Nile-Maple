<?php
namespace Nm;

/** Inline SVG icon set (doc 02 §7): 24px grid, 1.75 stroke, currentColor. */
final class Icons
{
    private const P = [
        'leaf' => '<path d="M5 19c8 1 14-4 14-13-9 0-14 5-14 13Zm0 0c2-5 6-8 10-9" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>',
        'sprout' => '<path d="M12 21v-8m0 0C12 8 9 6 4 6c0 5 3 7 8 7Zm0-2c0-4 3-6 8-6 0 4-3 6-8 6Z" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>',
        'citrus' => '<circle cx="12" cy="12" r="8" fill="none" stroke="currentColor" stroke-width="1.75"/><path d="M12 4v16M4 12h16m-13.7-5.7 11.4 11.4m0-11.4L4.3 17.7" stroke="currentColor" stroke-width="1.2"/>',
        'snowflake' => '<path d="M12 3v18M5 6.5l14 11M19 6.5l-14 11M12 3l-2 2m2-2 2 2m-2 14-2-2m2 2 2-2" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>',
        'can' => '<ellipse cx="12" cy="6" rx="7" ry="2.6" fill="none" stroke="currentColor" stroke-width="1.75"/><path d="M5 6v12c0 1.4 3.1 2.6 7 2.6s7-1.2 7-2.6V6M5 12c0 1.4 3.1 2.6 7 2.6s7-1.2 7-2.6" fill="none" stroke="currentColor" stroke-width="1.75"/>',
        'box' => '<path d="M3 8 12 4l9 4-9 4-9-4Zm0 0v8l9 4 9-4V8M12 12v8" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>',
        'tag' => '<path d="M4 4h7l9 9-7 7-9-9V4Z" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/><circle cx="8.5" cy="8.5" r="1.6" fill="currentColor"/>',
        'route' => '<circle cx="6" cy="6" r="2.5" fill="none" stroke="currentColor" stroke-width="1.75"/><circle cx="18" cy="18" r="2.5" fill="none" stroke="currentColor" stroke-width="1.75"/><path d="M8.5 6H15a3 3 0 0 1 0 6H9a3 3 0 0 0 0 6h6.5" fill="none" stroke="currentColor" stroke-width="1.75"/>',
        'ship' => '<path d="M4 15 3 11l9-2 9 2-1 4M6 15V9m6 6V7m6 8v-4M4 15c2 3 4 3 6 1.5 2 1.5 4 1.5 6 0 2 1.5 4 1.5 4 1.5" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>',
        'plane' => '<path d="M10 21l2-6 7-7a2 2 0 0 0-3-3l-7 7-6 2 3 2 5-4-1 5 3-1Z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>',
        'shield' => '<path d="M12 3 5 6v6c0 4.5 3 7.5 7 9 4-1.5 7-4.5 7-9V6l-7-3Z" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/><path d="m9 12 2 2 4-4" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>',
        'thermometer' => '<path d="M10 4a2 2 0 0 1 4 0v9.3a4 4 0 1 1-4 0V4Z" fill="none" stroke="currentColor" stroke-width="1.75"/><path d="M12 9v7" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>',
        'doc' => '<path d="M6 3h8l4 4v14H6V3Zm8 0v4h4M9 12h6M9 16h6" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>',
        'clipboard' => '<rect x="5" y="4" width="14" height="17" rx="2" fill="none" stroke="currentColor" stroke-width="1.75"/><path d="M9 4a3 3 0 0 1 6 0M9 10h6M9 14h6M9 18h4" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>',
        'globe' => '<circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="1.75"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18" fill="none" stroke="currentColor" stroke-width="1.5"/>',
        'phone' => '<path d="M6 3h3l2 5-2.5 1.5a12 12 0 0 0 5 5L15 12l5 2v3a2 2 0 0 1-2 2A16 16 0 0 1 4 5a2 2 0 0 1 2-2Z" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>',
        'mail' => '<path d="M4 6h16v12H4z" fill="none" stroke="currentColor" stroke-width="1.75"/><path d="m4 7 8 6 8-6" fill="none" stroke="currentColor" stroke-width="1.75"/>',
        'pin' => '<path d="M12 21s7-6 7-11a7 7 0 1 0-14 0c0 5 7 11 7 11Z" fill="none" stroke="currentColor" stroke-width="1.75"/><circle cx="12" cy="10" r="2.5" fill="none" stroke="currentColor" stroke-width="1.75"/>',
        'clock' => '<circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="1.75"/><path d="M12 7v5l3 3" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>',
        'check' => '<path d="m5 13 4 4L19 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'calendar' => '<rect x="4" y="5" width="16" height="16" rx="2" fill="none" stroke="currentColor" stroke-width="1.75"/><path d="M4 10h16M8 3v4m8-4v4" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>',
        'chart' => '<path d="M4 20V4m0 16h16M8 16v-5m4 5V8m4 8v-3" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>',
        'users' => '<circle cx="9" cy="8" r="3.2" fill="none" stroke="currentColor" stroke-width="1.75"/><path d="M3.5 19c.6-3.4 2.8-5 5.5-5s4.9 1.6 5.5 5M16 5.5a3.2 3.2 0 0 1 0 6.4m1.5 2.3c2 .6 3.2 2.2 3.6 4.8" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>',
        'spark' => '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3Z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>',
        'search' => '<circle cx="11" cy="11" r="5.5" fill="none" stroke="currentColor" stroke-width="1.75"/><path d="m15.5 15.5 4 4" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>',
        'eye' => '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" fill="none" stroke="currentColor" stroke-width="1.75"/><circle cx="12" cy="12" r="2.5" fill="none" stroke="currentColor" stroke-width="1.75"/>',
        'plus' => '<path d="M12 5v14M5 12h14" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>',
        'arrow-r' => '<path d="M9 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>',
        'arrow-l' => '<path d="M15 6l-6 6 6 6" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>',
        'wallet' => '<path d="M3 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z" fill="none" stroke="currentColor" stroke-width="1.75"/><path d="M14 12h5a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1h-5a1 1 0 0 1-1-1v-1a1 1 0 0 1 1-1Z" fill="none" stroke="currentColor" stroke-width="1.5"/><circle cx="16.5" cy="13" r="1" fill="currentColor"/>',
        'external' => '<path d="M14 4h6v6M10 14 20 4M20 10v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h6" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>',
    ];
    public static function all(): array { return self::P; }
    public static function svg(string $key, string $class = 'i'): string
    {
        $body = self::P[$key] ?? self::P['leaf'];
        return '<svg class="' . htmlspecialchars($class) . '" viewBox="0 0 24 24" aria-hidden="true">' . $body . '</svg>';
    }
}
