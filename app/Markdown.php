<?php
namespace Nm;

/**
 * Markdown-lite renderer with a STRICT whitelist (doc 06 §3.4): headings, lists, bold/em,
 * links, images (media ids only), quotes, tables, hr. Output is safe by construction —
 * no raw HTML passes through.
 */
final class Markdown
{
    public static function render(string $md): string
    {
        $lines = preg_split('/\R/u', str_replace("\r", '', $md));
        $out = []; $inList = false; $inOl = false; $inTable = false; $inQuote = false; $para = [];
        $closeAll = function () use (&$out, &$inList, &$inOl, &$inTable, &$inQuote, &$para) {
            if ($para) { $out[] = '<p>' . implode(' ', $para) . '</p>'; $para = []; }
            if ($inList) { $out[] = '</ul>'; $inList = false; }
            if ($inOl) { $out[] = '</ol>'; $inOl = false; }
            if ($inQuote) { $out[] = '</blockquote>'; $inQuote = false; }
            if ($inTable) { $out[] = '</tbody></table>'; $inTable = false; }
        };
        foreach ($lines as $line) {
            $t = rtrim($line);
            if (trim($t) === '') { $closeAll(); continue; }
            if (preg_match('/^#{1,4}\s+(.*)$/', $t, $m)) {
                $closeAll(); $lv = min(4, strlen(explode(' ', $t)[0])); $out[] = "<h$lv>" . self::inline($m[1]) . "</h$lv>"; continue;
            }
            if (preg_match('/^[-*]\s+(.*)$/', $t, $m)) {
                if ($para) { $out[] = '<p>' . implode(' ', $para) . '</p>'; $para = []; }
                if (!$inList) { $out[] = '<ul>'; $inList = true; }
                $out[] = '<li>' . self::inline($m[1]) . '</li>'; continue;
            }
            if (preg_match('/^\d+\.\s+(.*)$/', $t, $m)) {
                if ($para) { $out[] = '<p>' . implode(' ', $para) . '</p>'; $para = []; }
                if (!$inOl) { $out[] = '<ol>'; $inOl = true; }
                $out[] = '<li>' . self::inline($m[1]) . '</li>'; continue;
            }
            if (preg_match('/^>\s?(.*)$/', $t, $m)) {
                if (!$inQuote) { $out[] = '<blockquote>'; $inQuote = true; }
                $out[] = '<p>' . self::inline($m[1]) . '</p>'; continue;
            }
            if (preg_match('/^---+$/', trim($t))) { $closeAll(); $out[] = '<hr>'; continue; }
            if (preg_match('/^\|(.+)\|$/', trim($t), $m)) {
                $cells = array_map('trim', explode('|', $m[1]));
                if (preg_match('/^[\s:-]+$/', implode('', $cells))) continue;
                if (!$inTable) {
                    $out[] = '<table><thead><tr>';
                    foreach ($cells as $c) $out[] = '<th>' . self::inline($c) . '</th>';
                    $out[] = '</tr></thead><tbody>'; $inTable = true; continue;
                }
                $out[] = '<tr>'; foreach ($cells as $c) $out[] = '<td>' . self::inline($c) . '</td>'; $out[] = '</tr>'; continue;
            }
            $para[] = self::inline($t);
        }
        $closeAll();
        return implode("\n", $out);
    }

    private static function inline(string $s): string
    {
        $s = htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $s = preg_replace_callback('/!\[([^\]]*)\]\(media:(\d+)\)/', static fn($m) => Media::img((int) $m[2], $m[1], 'md'), $s);
        $s = preg_replace_callback('/\[([^\]]+)\]\((https?:\/\/[^\s)]+|\/[^\s)]+)\)/', static function ($m) {
            $href = htmlspecialchars($m[2], ENT_QUOTES);
            $ext = str_starts_with($m[2], 'http') ? ' rel="noopener" target="_blank"' : '';
            return '<a href="' . $href . '"' . $ext . '>' . $m[1] . '</a>';
        }, $s);
        $s = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $s);
        $s = preg_replace('/(?<![\w*])\*([^*]+)\*(?![\w*])/', '<em>$1</em>', $s);
        $s = preg_replace('/`([^`]+)`/', '<code>$1</code>', $s);
        return $s;
    }

    public static function excerpt(string $md, int $chars = 160): string
    {
        $txt = trim(preg_replace('/[#>*`|-]/', ' ', preg_replace('/\R+/u', ' ', $md)));
        return mb_strlen($txt) > $chars ? mb_substr($txt, 0, $chars - 1) . '…' : $txt;
    }
    public static function readingMinutes(string $md): int
    {
        return max(1, (int) round(str_word_count(preg_replace('/\R+/u', ' ', $md)) / 200));
    }
}
