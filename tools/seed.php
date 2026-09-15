<?php
/**
 * Nile-Maple seed: settings, categories, 159 products (EN from manifest; AR/FR via
 * controlled-language baseline D-14), services, FAQs, blocks, posts, admin user.
 * Idempotent: safe to re-run (upserts by code/slug).
 */
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/seed_content.php';   // returns content arrays
use Nm\Db; use Nm\Slug;

$C = require __DIR__ . '/seed_content.php';
$manifest = json_decode((string) file_get_contents(nm_path('docs/data/content-manifest.json')), true);
if (!$manifest) { fwrite(STDERR, "manifest missing — run tools/extract_content.py\n"); exit(1); }

/* settings (client-mandated contact visibility) */
$settings = [
    'contact.email' => $manifest['company']['email'], 'contact.phone' => $manifest['company']['phone'],
    'contact.whatsapp' => $manifest['company']['whatsapp'], 'social.instagram' => $manifest['company']['instagram'],
    'social.facebook' => $manifest['company']['facebook'], 'hours' => 'Sun–Thu 9:00–17:00 (GMT+2)',
    'address' => '', 'quote_promise' => 'Replies within one working day.',
    'seo.title_tpl' => '{title} | Nile-Maple', 'sys.maintenance' => '0',
];
foreach ($settings as $k => $v) Db::upsert('settings', ['s_key' => $k, 'lang' => '*', 'value' => $v], ['s_key', 'lang']);
foreach (['wa.prefill' => ['en' => "Hello Nile-Maple, I'd like to discuss your products.", 'ar' => 'مرحبًا نيل مابل، أرغب في الاستفسار عن منتجاتكم.', 'fr' => 'Bonjour Nile-Maple, je souhaite discuter de vos produits.']] as $k => $langs) {
    foreach ($langs as $l => $v) Db::upsert('settings', ['s_key' => $k, 'lang' => $l, 'value' => $v], ['s_key', 'lang']);
}

/* categories */
$catIds = [];
foreach ($manifest['categories'] as $i => $mc) {
    $cc = $C['categories'][$mc['key']];
    Db::run('DELETE FROM categories WHERE code=?', [$mc['key']]);
    Db::run('INSERT INTO categories(code,icon_key,accent,sort_order,is_published) VALUES(?,?,?,?,1)',
        [$mc['key'], $cc['icon'], $cc['accent'], $i]);
    $id = Db::lastId(); $catIds[$mc['key']] = $id;
    foreach (cfg('langs') as $l) {
        Db::upsert('category_i18n', ['category_id' => $id, 'lang' => $l, 'name' => $cc['name'][$l],
            'slug' => Slug::make($cc['slug'][$l], $l), 'headline' => $cc['headline'][$l], 'summary' => $cc['summary'][$l],
            'meta_title' => $cc['meta_t'][$l], 'meta_description' => $cc['meta_d'][$l]], ['category_id', 'lang']);
    }
}

/* products: EN verbatim from manifest; AR/FR controlled-language baseline (D-14) */
$names = $C['product_names']; $pack = $C['packing_glossary']; $tmpl = $C['desc_templates']; $chain = $C['chain_templates'];
$labels = $C['spec_labels'];
$n = 0;
foreach ($manifest['categories'] as $mc) {
    $ck = $mc['key'];
    foreach ($mc['products'] as $p) {
        $nm = $names[$p['name_en']] ?? null;
        if (!$nm) { fwrite(STDERR, "MISSING NAME: {$p['name_en']}\n"); continue; }
        $exist = Db::one('SELECT id FROM products WHERE category_id=? AND source_index=?', [$catIds[$ck], $p['index']]);
        if ($exist) { $pid = (int) $exist['id']; Db::run('DELETE FROM product_i18n WHERE product_id=?', [$pid]); }
        else {
            Db::run('INSERT INTO products(category_id,sku,source_index,sort_order,is_published,temp_min,temp_max,temp_unit,temp_note)
                     VALUES(?,?,?,?,1,?,?,?,?)',
                [$catIds[$ck], strtoupper(substr($ck, 0, 2)) . '-' . str_pad((string) $p['index'], 2, '0', STR_PAD_LEFT),
                 $p['index'], $p['index'], ...parseTemp($p[ $mc['field_schema'][3] ])]);
            $pid = Db::lastId();
        }
        $media = Db::one("SELECT id FROM media WHERE source_ref=?", ["{$mc['key']}/{$p['image']}"]);
        if ($media) Db::run('UPDATE products SET card_media_id=? WHERE id=?', [$media['id'], $pid]);
        $en = [
            'name' => titleCase($p['name_en']), 'slug' => Slug::make(titleCase($p['name_en']), 'en'),
            'description' => $p['description_en'],
            'label_varieties' => $mc['field_schema'][0] === 'varieties' ? 'Varieties / Types' : 'Available Forms',
            'varieties' => $p[$mc['field_schema'][0]],
            'label_handling' => $mc['field_schema'][1] === 'export_handling' ? 'Export Handling' : 'Processing & Handling',
            'handling' => $p[$mc['field_schema'][1]],
            'label_packing' => 'Packing', 'packing' => $p['packing'],
            'label_chain' => $mc['field_schema'][3] === 'cold_chain' ? 'Cold-Chain Guide' : 'Frozen-Chain / Storage Guide',
            'chain' => $p[$mc['field_schema'][3]],
            'meta_title' => null, 'meta_description' => null,
        ];
        $rows = ['en' => $en];
        foreach (['ar', 'fr'] as $l) {
            $packingL = gloss($p['packing'], $pack[$l]);
            [$tmin, $tmax] = tempNums($p[$mc['field_schema'][3]]);
            $tempStr = $chain[$l]($tmin, $tmax);
            $rows[$l] = [
                'name' => $nm[$l], 'slug' => Slug::make($nm[$l], $l),
                'description' => $tmpl[$ck][$l]($nm[$l], $p[$mc['field_schema'][0]], $packingL, $tempStr),
                'label_varieties' => $labels['varieties'][$l], 'varieties' => $p[$mc['field_schema'][0]],
                'label_handling' => $labels['handling'][$l], 'handling' => $C['handling_templates'][$ck][$l],
                'label_packing' => $labels['packing'][$l], 'packing' => $packingL,
                'label_chain' => $labels['chain'][$l], 'chain' => $tempStr,
                'meta_title' => null, 'meta_description' => null,
            ];
        }
        foreach ($rows as $l => $r) {
            Db::run('INSERT INTO product_i18n(product_id,lang,name,slug,description,label_varieties,varieties,label_handling,handling,label_packing,packing,label_chain,chain,meta_title,meta_description)
                     VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [$pid, $l, $r['name'], $r['slug'], $r['description'], $r['label_varieties'], $r['varieties'],
                 $r['label_handling'], $r['handling'], $r['label_packing'], $r['packing'], $r['label_chain'], $r['chain'], null, null]);
        }
        /* featured: first 4 of each category */
        if ($p['index'] <= 4) Db::run('UPDATE products SET is_featured=1 WHERE id=?', [$pid]);
        $n++;
    }
}

/* services / faqs / blocks / posts / users */
seed_services($C['services']);
seed_faqs($C['faqs']);
seed_blocks($C['blocks']);
seed_posts($C['posts']);
if (!Db::one('SELECT 1 FROM users LIMIT 1')) {
    Db::run('INSERT INTO users(email,password_hash,full_name,role,status) VALUES(?,?,?,?,\'active\')',
        ['owner@nilemaple.com', password_hash('ChangeMe!2026', PASSWORD_DEFAULT), 'Eng. Omar Issa', 'owner']);
    fwrite(STDERR, "created owner@nilemaple.com / ChangeMe!2026 — change immediately\n");
}
echo "seeded: $n products, " . count($catIds) . " categories\n";

function parseTemp(string $s): array
{
    [$a, $b] = tempNums($s);
    return [$a, $b, 'C', null];
}
function tempNums(string $s): array
{
    if (preg_match('/(-?\d+(?:\.\d+)?)\s*-\s*(-?\d+(?:\.\d+)?)/', $s, $m)) return [(float) $m[1], (float) $m[2]];
    if (preg_match('/(-?\d+(?:\.\d+)?)/', $s, $m)) return [(float) $m[1], null];
    return [null, null];
}
function titleCase(string $s): string { return mb_convert_case(strtolower($s), MB_CASE_TITLE, 'UTF-8'); }
function gloss(string $v, array $g): string { return strtr($v, $g); }

function seed_services(array $S): void
{
    foreach ($S as $i => $s) {
        $ex = Db::one('SELECT id FROM services WHERE code=?', [$s['code']]);
        if ($ex) { $id = (int) $ex['id']; Db::run('DELETE FROM service_i18n WHERE service_id=?', [$id]); }
        else { Db::run('INSERT INTO services(code,icon_key,sort_order,is_published) VALUES(?,?,?,1)', [$s['code'], $s['icon'], $i]); $id = Db::lastId(); }
        foreach (cfg('langs') as $l) {
            Db::run('INSERT INTO service_i18n(service_id,lang,name,slug,teaser,body,bullets,meta_title,meta_description) VALUES(?,?,?,?,?,?,?,?,?)',
                [$id, $l, $s['name'][$l], Slug::make($s['name'][$l], $l), $s['teaser'][$l], $s['body'][$l],
                 json_encode($s['bullets'][$l], JSON_UNESCAPED_UNICODE), null, null]);
        }
    }
}
function seed_faqs(array $F): void
{
    Db::run('DELETE FROM faq_i18n'); Db::run('DELETE FROM faqs');
    foreach ($F as $i => $f) {
        Db::run('INSERT INTO faqs(group_code,sort_order,is_published) VALUES(?,?,1)', [$f['group'], $i]);
        $id = Db::lastId();
        foreach (cfg('langs') as $l) Db::run('INSERT INTO faq_i18n(faq_id,lang,question,answer) VALUES(?,?,?,?)', [$id, $l, $f['q'][$l], $f['a'][$l]]);
    }
}
function seed_blocks(array $B): void
{
    Db::run('DELETE FROM block_i18n'); Db::run('DELETE FROM blocks');
    foreach ($B as $i => $b) {
        Db::run('INSERT INTO blocks(zone,kind,sort_order,payload) VALUES(?,?,?,?)', [$b['zone'], $b['kind'], $i, json_encode($b['base'], JSON_UNESCAPED_UNICODE)]);
        $id = Db::lastId();
        foreach (cfg('langs') as $l) {
            Db::run('INSERT INTO block_i18n(block_id,lang,title,eyebrow,payload) VALUES(?,?,?,?,?)',
                [$id, $l, $b['title'][$l] ?? null, $b['eyebrow'][$l] ?? null, json_encode($b['payload'][$l] ?? $b['base'], JSON_UNESCAPED_UNICODE)]);
        }
    }
}
function seed_posts(array $P): void
{
    foreach ($P as $i => $p) {
        $ex = Db::one("SELECT id FROM posts p JOIN post_i18n i ON i.post_id=p.id AND i.lang='en' WHERE i.slug=?", [Slug::make($p['title']['en'], 'en')]);
        if ($ex) { $id = (int) $ex['id']; Db::run('DELETE FROM post_i18n WHERE post_id=?', [$id]); }
        else {
            Db::run("INSERT INTO posts(author_user_id,status,published_at,reading_minutes) VALUES(1,'published',?,?)",
                [date('Y-m-d H:i:s', time() - $i * 86400 * 6), max(2, (int) round(str_word_count($p['body']['en']) / 200))]);
            $id = Db::lastId();
        }
        foreach (cfg('langs') as $l) {
            Db::run('INSERT INTO post_i18n(post_id,lang,title,slug,excerpt,body,meta_title,meta_description) VALUES(?,?,?,?,?,?,?,?)',
                [$id, $l, $p['title'][$l], Slug::make($p['title'][$l], $l), $p['excerpt'][$l], $p['body'][$l], null, null]);
        }
    }
}
