<?php
/**
 * Nile-Maple content updates (2026-09-21) — idempotent data fixes.
 *
 *   1. Official contact email everywhere
 *      — settings.contact.email is set to the official mailbox;
 *      — every editable content field (blocks, posts, categories, products, services,
 *        FAQs, SEO) is swept: any @nilemaple.com address that is NOT the official one
 *        is replaced. Emails from other domains are reported but never touched.
 *   2. Category cover images
 *      — every published category without a cover is assigned the photo of one of its
 *        own products (featured first, then sort order) — the same professional
 *        imagery the product cards already use.
 *   3. Enquiry recipient
 *      — config/config.php mail.to is pointed at the official mailbox.
 *
 * Idempotent: a second run changes nothing and reports the same state.
 * CLI:  php tools/content_updates.php
 * Host: required and run from tools/apply-updates.php (the one-shot host script).
 */
declare(strict_types=1);
/* loaded standalone (CLI) or included by the one-shot host script (which bootstrapped already) */
if (!function_exists('nm_path')) require __DIR__ . '/../app/bootstrap.php';

use Nm\Db;
use Nm\Util;

const NM_OFFICIAL = 'contact@nilemaple.com';
const NM_EMAIL_RE = '/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/';

/** replace every non-official nilemaple.com email; returns [new, otherDomainEmailsFound[]].
    Replaced nilemaple addresses are documented in the caller's email_changes report;
    other-domain addresses are reported for manual review and never rewritten. */
function nm_updates_replace_emails(string $s): array
{
    $other = [];
    $out = (string) preg_replace_callback(NM_EMAIL_RE, static function (array $m) use (&$other): string {
        $e = strtolower($m[0]);
        if ($e === NM_OFFICIAL) return $m[0];
        if (str_ends_with($e, '@nilemaple.com')) return NM_OFFICIAL;
        $other[] = $m[0];
        return $m[0];
    }, $s);
    return [$out, $other];
}

/* Note on JSON columns: we replace emails in the RAW string, never decode/re-encode.
   The stored JSON keeps its exact escape style (\/, \uXXXX, key order), so a payload with
   no email is byte-identical after the pass — no false-positive "changes". Email addresses
   contain no characters that need JSON escaping, so a raw replacement is safe. */

/* Trailing temperature sentences that the FR/AR descriptions carry after the packing
   sentence: "…cartons télescopiques. Généralement 8 °C" / "…تلسكوبية. عادة 8 °م"
   (104 FR + 104 AR rows verified against the live content). Only the exact
   end-of-string forms are removed; anything else is reported, never blindly edited. */
function nm_updates_strip_desc_temps(string $d): array
{
    $orig = $d;
    $d = (string) preg_replace('/\s*Généralement\s+[-\x{2212}]?\d+(?:\.\d+)?\s*°C\s*$/u', '', $d);
    $d = (string) preg_replace('/\s*عادة\s+[-\x{2212}]?\d+(?:\.\d+)?\s*°م\s*$/u', '', $d);
    return [$d, $d !== $orig, Util::hasTempText($d)];
}

/* Embedded temperature clauses in the export-handling text (one distinct sentence per
   locale, verified against the live content):
     EN (grapes):  "…maintained close to 0 C to preserve crispness…"  → "…maintained at low temperature…"
     FR (frozen):  "…stockées à -18 °C, grains séparés…"              → "…stockées en congélation, grains séparés…"
     AR (frozen):  "…وتُخزن عند -18 °م مع فصل الحبات…"                → "…وتُخزن مجمدة مع فصل الحبات…" */
function nm_updates_clean_handling_temps(string $h): array
{
    $orig = $h;
    $h = (string) preg_replace('/close to\s+[-\x{2212}]?\d+(?:\.\d+)?\s?C\b/u', 'at low temperature', $h);
    $h = (string) preg_replace('/stockées à\s+[-\x{2212}]?\d+(?:\.\d+)?\s*°C/u', 'stockées en congélation', $h);
    $h = (string) preg_replace('/وتُخزن عند\s+[-\x{2212}]?\d+(?:\.\d+)?\s*°م/u', 'وتُخزن مجمدة', $h);
    return [$h, $h !== $orig, Util::hasTempText($h)];
}

/**
 * Run all content updates. Returns a report array:
 * ['email_changes'=>[], 'emails_found_other_domains'=>[], 'covers'=>[], 'config'=>[], 'settings'=>[], 'desc_temps'=>[]]
 */
function nm_updates_run(): array
{
    $report = ['email_changes' => [], 'emails_found_other_domains' => [], 'covers' => [], 'config' => [], 'settings' => [], 'desc_temps' => []];
    $stamp = date('Y-m-d H:i:s');

    /* ── 1a · settings: the displayed contact email ─────────────────────────────── */
    $before = (string) Db::val('SELECT value FROM settings WHERE s_key=? AND lang=?', ['contact.email', '*']);
    Db::upsert('settings', ['s_key' => 'contact.email', 'lang' => '*', 'value' => NM_OFFICIAL], ['s_key', 'lang']);
    $report['settings'][] = ['contact.email', $before, NM_OFFICIAL];

    /* ── 1b · sweep every editable content field ────────────────────────────────── */
    $fields = [
        ['settings', 's_key', null, ['value']],
        ['blocks', 'id', null, ['payload']],
        ['block_i18n', 'block_id', null, ['payload', 'title', 'eyebrow']],
        ['post_i18n', 'post_id', null, ['title', 'excerpt', 'body']],
        ['category_i18n', 'category_id', null, ['name', 'headline', 'summary']],
        ['product_i18n', 'product_id', null, ['name', 'description', 'varieties', 'handling', 'packing', 'chain']],
        ['service_i18n', 'service_id', null, ['name', 'teaser', 'body', 'bullets']],
        ['faq_i18n', 'faq_id', null, ['question', 'answer']],
        ['seo_meta', 'entity_id', 'entity_type', ['title', 'description']],
    ];
    foreach ($fields as [$table, $pk, $pk2, $cols]) {
        $rows = Db::all('SELECT * FROM ' . $table);
        foreach ($rows as $r) {
            $id = (string) $r[$pk] . ($pk2 ? ' ' . $r[$pk2] : '');
            foreach ($cols as $col) {
                $old = (string) ($r[$col] ?? '');
                if ($old === '') continue;
                [$new, $hits] = nm_updates_replace_emails($old);
                foreach (array_unique($hits) as $h) $report['emails_found_other_domains'][] = "$table#$id.$col: $h";
                if ($new !== $old) {
                    Db::run('UPDATE ' . $table . ' SET ' . $col . '=? WHERE ' . $pk . '=?' . ($pk2 ? ' AND ' . $pk2 . '=?' : ''),
                        array_merge([$new], $pk2 ? [$r[$pk], $r[$pk2]] : [$r[$pk]]));
                    $report['email_changes'][] = ["$table #$id → $col", $old, $new];
                }
            }
        }
    }
    $report['emails_found_other_domains'] = array_values(array_unique($report['emails_found_other_domains']));

    /* ── 1c · product page text: remove remaining temperature wording ─────────────────
       descriptions: drop the trailing temperature sentence (FR/AR);
       handling:     replace the embedded temperature clause (EN grapes, AR/FR frozen).
       Anything still containing a temperature afterwards is reported for manual review. */
    foreach (Db::all('SELECT product_id, lang, description, handling FROM product_i18n') as $r) {
        [$nd, $dc, $dt] = nm_updates_strip_desc_temps((string) $r['description']);
        if ($dc) {
            Db::run('UPDATE product_i18n SET description=? WHERE product_id=? AND lang=?', [$nd, (int) $r['product_id'], $r['lang']]);
            $report['desc_temps'][] = "product #$r[product_id] ($r[lang]) description — trailing temperature sentence removed";
        } elseif ($dt) {
            $report['desc_temps'][] = "product #$r[product_id] ($r[lang]) description — NOT changed, unusual temperature form, review manually";
        }
        if ((string) $r['handling'] !== '') {
            [$nh, $hc, $ht] = nm_updates_clean_handling_temps((string) $r['handling']);
            if ($hc) {
                Db::run('UPDATE product_i18n SET handling=? WHERE product_id=? AND lang=?', [$nh, (int) $r['product_id'], $r['lang']]);
                $report['desc_temps'][] = "product #$r[product_id] ($r[lang]) handling — temperature clause reworded";
            } elseif ($ht) {
                $report['desc_temps'][] = "product #$r[product_id] ($r[lang]) handling — NOT changed, unusual temperature form, review manually";
            }
        }
    }

    /* ── 2 · category cover images from the category's own product photos ───────── */
    foreach (Db::all('SELECT id, code FROM categories WHERE is_published=1 AND (cover_media_id IS NULL OR cover_media_id=0)') as $c) {
        $mid = Db::val('SELECT p.card_media_id FROM products p
                        WHERE p.category_id=? AND p.is_published=1 AND p.card_media_id IS NOT NULL
                          AND EXISTS (SELECT 1 FROM media m WHERE m.id=p.card_media_id)
                        ORDER BY p.is_featured DESC, p.sort_order, p.id LIMIT 1', [(int) $c['id']]);
        if ($mid) {
            Db::run('UPDATE categories SET cover_media_id=?, updated_at=? WHERE id=?', [(int) $mid, $stamp, (int) $c['id']]);
            $report['covers'][] = ["category {$c['code']}", (int) $mid];
        } else {
            $report['covers'][] = ["category {$c['code']}", null];
        }
    }

    /* ── 3 · config: enquiry recipient ──────────────────────────────────────────── */
    $cf = nm_path('config/config.php');
    if (is_file($cf)) {
        $src = (string) file_get_contents($cf);
        $toNow = null;
        if (preg_match("/'to'\s*=>\s*'([^']*)'/", $src, $m)) $toNow = $m[1];
        if ($toNow !== NM_OFFICIAL) {
            $src2 = (string) preg_replace("/('to'\s*=>\s*)'[^']*'/", '$1' . NM_OFFICIAL . "'", $src);
            if ($src2 !== $src) {
                file_put_contents($cf, $src2);
                @chmod($cf, 0600);
                $report['config'][] = ['mail.to', $toNow, NM_OFFICIAL];
            }
        }
        if (preg_match("/'from'\s*=>\s*\['([^']*)'/", $src, $m) && strtolower($m[1]) !== NM_OFFICIAL) {
            $report['config'][] = ['mail.from (NOT changed — sending mailbox, check SMTP user)', $m[1], '(kept)'];
        }
    }

    return $report;
}

/* auto-run only from a terminal (Util::isCli = cli or the php-wasm 'wasm' harness);
   the one-shot host script defines NM_UPDATES_EMBEDDED and calls nm_updates_run() itself */
if (Util::isCli() && !defined('NM_UPDATES_EMBEDDED')) {
    $r = nm_updates_run();
    echo json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), "\n";
}