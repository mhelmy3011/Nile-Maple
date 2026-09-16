<?php
/**
 * Assigns a real, topically-matched product photo as each seeded blog post's cover image.
 * DEPLOY.md §9 flagged this as a known gap ("post cover images reference profile/imageN.jpg,
 * which the extractor does not export... set the post cover manually") — every seeded post
 * shipped with cover_media_id NULL, which app/templates/ui/card-post.php rendered as a blank
 * grey box on the blog index (found live, reported by the client). Idempotent — re-running it
 * just reasserts the same mapping.
 *
 *   php tools/fix_post_covers.php
 */
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

use Nm\Db;

/* post id (seed order, tools/seed_content*.php) => media id (existing product photography,
   picked to match each post's subject) */
$map = [
    1 => 'fresh-fruits/image1.jpg',        // How to Read a Citrus Export Programme -> Orange
    2 => 'frozen-products/image7.jpg',     // IQF vs Fresh -> Frozen Mixed Vegetables
    3 => 'fresh-vegetables/image1.jpg',    // Egyptian Vegetables: Handling Checklist -> Potato
    4 => 'processed-canned/image8.jpg',    // Private Label: Packaging -> Canned Mixed Vegetables
    5 => 'frozen-products/image8.jpg',     // Cold Chain: What Breaks It -> Frozen Spinach
    6 => 'processed-canned/image2.jpg',    // Choosing Pack Formats -> Canned Chickpeas
    7 => 'fresh-fruits/image2.jpg',        // Seasonal Windows -> Mandarin/Tangerine
    8 => 'processed-canned/image1.jpg',    // The Export Paper Pack -> Canned Fava Beans
];

$fixed = 0;
foreach ($map as $postId => $ref) {
    $mediaId = Db::val('SELECT id FROM media WHERE source_ref=?', [$ref]);
    if (!$mediaId) { fwrite(STDERR, "media not found for post $postId: $ref\n"); continue; }
    if ((int) Db::val('SELECT id FROM posts WHERE id=?', [$postId]) !== $postId) continue;
    Db::run('UPDATE posts SET cover_media_id=? WHERE id=?', [(int) $mediaId, $postId]);
    $fixed++;
}
echo "post covers set: $fixed row(s)\n";
