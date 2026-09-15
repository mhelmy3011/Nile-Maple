<?php
/**
 * SEO seed (Finalization-Plan §5.3, D-08) — populates seo_meta so no page falls back to a
 * formulaic global template, and the dashboard completeness board reads 100 %.
 *
 *   php tools/seed_seo.php            (re)build every seo_meta row — idempotent, delete+insert
 *
 * Composition rules:
 *  - categories / services / posts: the hand-authored per-locale meta from the seed content
 *    (written by a copywriter in seed_content*.php, mirrored into *_i18n.meta_*) is promoted
 *    into seo_meta — one authoritative override layer.
 *  - products: templated-but-differentiated titles + descriptions composed from REAL spec data
 *    (name, varieties, packing, cold-chain, temperature) so no two are identical (asserted).
 *  - static/structural pages + faq + home: the page-specific copy from app/lang/*.php.
 */
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

use Nm\Db;
use Nm\I18n;

Db::run('DELETE FROM seo_meta');
$ins = static function (string $type, int $id, string $lang, string $title, string $desc): void {
    Db::run('INSERT INTO seo_meta(entity_type,entity_id,lang,title,description) VALUES(?,?,?,?,?)',
        [$type, $id, $lang, mb_substr($title, 0, 70), mb_substr($desc, 0, 160)]);
};
$n = 0;

/* ── categories / services / posts: promote the hand-authored i18n meta ─────────────── */
/* Services & posts carry their authored copy here (their *_i18n.meta_* columns are an
 * override layer the dashboard fills; the seed authors the baseline per entity). */
$authored = [
    'service' => [
        'sourcing-selection' => [
            'en' => ['Farm Sourcing & Quality Selection Service | Nile-Maple', 'Grower assessment, harvest planning and pre-shipment grading to your specification — the first control point of every Nile-Maple export programme.'],
            'ar' => ['خدمة التوريد والانتقاء من المزارع | نيل مابل', 'تقييم المزارعين وتخطيط الحصاد والفرز قبل الشحن وفق مواصفتك — نقطة الضبط الأولى في كل برنامج تصدير من نيل مابل.'],
            'fr' => ['Sourcing fermier & sélection qualité | Nile-Maple', 'Évaluation des producteurs, planification de récolte et calibrage avant expédition selon votre spécification — le premier point de contrôle de chaque programme Nile-Maple.'],
        ],
        'export-handling' => [
            'en' => ['Export Handling & Pre-Cooling Service | Nile-Maple', 'Post-harvest care, forced cooling and transport-ready packing so containers arrive the way they left — per-product handling protocols, never one-size-fits-all.'],
            'ar' => ['خدمة التداول التصديري والتبريد | نيل مابل', 'رعاية ما بعد الحصاد والتبريد الفعلي وتعبئة جاهزة للنقل حتى تصل الحاويات كما أُرسلت — بروتوكولات تداول لكل منتج لا مقاس واحد للجميع.'],
            'fr' => ['Manutention export & préréfrigération | Nile-Maple', 'Soins post-récolte, refroidissement forcé et emballage prêt au transport : vos conteneurs arrivent comme ils sont partis — des protocoles par produit, jamais standardisés.'],
        ],
        'cold-chain-logistics' => [
            'en' => ['Cold-Chain & Freight Coordination | Nile-Maple', 'Set points, ventilation patterns and transit windows planned per product, with supervised loading and arrival follow-up on every shipment.'],
            'ar' => ['تنسيق سلسلة التبريد والشحن | نيل مابل', 'نقاط ضبط وأنماط تهوية ونوافذ نقل مخططة لكل منتج، مع تحميل بإشراف ومتابعة عند الوصول لكل شحنة.'],
            'fr' => ['Chaîne du froid & affrètement | Nile-Maple', 'Consignes, schémas de ventilation et fenêtres de transit planifiés par produit, avec empotage supervisé et suivi à l’arrivée de chaque expédition.'],
        ],
        'packaging-labelling' => [
            'en' => ['Export Packaging & Labelling Service | Nile-Maple', 'Retail, food-service and private-label pack formats engineered for the channel, with destination-compliant labelling confirmed before every run.'],
            'ar' => ['خدمة التعبئة والبطاقة للتصدير | نيل مابل', 'صيغ تعبئة للتجزئة وقطاع الغذاء والعلامة الخاصة مصممة للقناة، مع بطاقة مطابقة لأسواق الوجهة تُعتمد قبل كل إنتاج.'],
            'fr' => ['Emballage & étiquetage export | Nile-Maple', 'Formats détail, food-service et MDD conçus pour le canal, avec un étiquetage conforme au marché de destination validé avant chaque production.'],
        ],
        'processing-freezing' => [
            'en' => ['IQF Freezing & Processing Service | Nile-Maple', 'Individual quick freezing and shelf-stable processing to agreed recipes — cut sizes, blanching and brix documented against the buyer specification.'],
            'ar' => ['خدمة التجميد السريع والتصنيع | نيل مابل', 'تجميد فردي سريع وتصنيع طويل الحياة وفق وصفات متفق عليها — مقاسات قطع و blanšing وبريكس موثقة مقابل مواصفة المشتري.'],
            'fr' => ['Surgélation IQF & transformation | Nile-Maple', 'Surgélation individuelle et appertisation selon recettes convenues — calibres de coupe, blanchiment et Brix documentés face à la spécification acheteur.'],
        ],
        'buyers-support' => [
            'en' => ['Buyer Support: Samples & Quotations | Nile-Maple', 'Product samples, specification sheets and firm quotations within 48 hours, plus after-shipment follow-up that keeps repeat programmes on schedule.'],
            'ar' => ['دعم المشترين: عينات وعروض أسعار | نيل مابل', 'عينات ومنتجات ومواصفات وعروض أسعار مؤكدة خلال 48 ساعة، ومتابعة بعد الشحن تُبقي البرامج المتكررة على الموعد.'],
            'fr' => ['Support acheteurs : échantillons & devis | Nile-Maple', 'Échantillons, fiches techniques et devis fermes sous 48 h, plus un suivi post-expédition qui maintient les programmes récurrents dans les délais.'],
        ],
    ],
    'post' => [
        'choosing-pack-formats-by-sales-channel' => [
            'en' => ['Choosing Pack Formats by Sales Channel | Nile-Maple', 'Retail shelf presence, food-service efficiency and industrial cost pull in opposite directions — how to match the pack to the channel before you price.'],
            'ar' => ['اختيار صيغ التعبئة حسب قناة البيع | نيل مابل', 'الحضور في الرف وكفاءة المطبخ وكلفة الصناعة تسحب اتجاهات متعاكسة — كيف تطابق العبوة بالقناة قبل التسعير.'],
            'fr' => ['Choisir le format selon le canal de vente | Nile-Maple', 'Présence en rayon, efficacité cuisine et coût industriel tirent dans des sens opposés — comment accorder le pack au canal avant de chiffrer.'],
        ],
        'cold-chain-what-actually-breaks-it' => [
            'en' => ['Cold Chain: What Actually Breaks It | Nile-Maple', 'The five failure points between pre-cooling and arrival — and the checks that keep the chain unbroken for Egyptian fresh produce.'],
            'ar' => ['سلسلة التبريد: ما الذي يكسرها فعلًا | نيل مابل', 'خمس نقاط فشل بين التبريد المسبق والوصول — والفحوصات التي تُبقي السلسلة غير منقطعة للمنتجات المصرية الطازجة.'],
            'fr' => ['Chaîne du froid : ce qui la rompt vraiment | Nile-Maple', 'Les cinq points de défaillance entre préréfrigération et arrivée — et les contrôles qui la maintiennent intacte pour les produits frais égyptiens.'],
        ],
        'egyptian-vegetables-a-buyer-s-handling-checklist' => [
            'en' => ['Egyptian Vegetables: A Buyer’s Handling Checklist | Nile-Maple', 'Harvest condition, ventilation, temperature and pack strength — the specification points that decide whether green beans and peppers arrive sellable.'],
            'ar' => ['الخضروات المصرية: قائمة فحص التداول للمشتري | نيل مابل', 'حالة الحصاد والتهوية والحرارة ومتانة العبوة — نقاط المواصفة التي تحدد وصول الفاصوليا والفلفل قابلة للبيع.'],
            'fr' => ['Légumes égyptiens : la checklist acheteur | Nile-Maple', 'État à la récolte, ventilation, température et solidité du pack — les points de spécification qui décident de la vendabilité de haricots et poivrons.'],
        ],
        'how-to-read-a-citrus-export-programme' => [
            'en' => ['How to Read a Citrus Export Programme | Nile-Maple', 'Variety windows, size counts, treatment protocols and price movements across an Egyptian citrus season — a buyer’s field guide.'],
            'ar' => ['كيف تقرأ برنامج تصدير الحمضيات | نيل مابل', 'نوافذ الأصناف ومقاسات العد وبروتوكولات المعالجة وحركة الأسعار عبر موسم الحمضيات المصري — دليل ميداني للمشتري.'],
            'fr' => ['Lire un programme d’export d’agrumes | Nile-Maple', 'Fenêtres variétales, calibrages, protocoles de traitement et mouvements de prix sur une saison d’agrumes égyptienne — guide de terrain pour l’acheteur.'],
        ],
        'iqf-vs-fresh-securing-year-round-supply' => [
            'en' => ['IQF vs Fresh: Securing Year-Round Supply | Nile-Maple', 'Where frozen lines protect a fresh programme, how IQF quality is judged, and the cost logic of holding both in the same category plan.'],
            'ar' => ['التجميد السريع مقابل الطازج: تأمين التوريد طوال العام | نيل مابل', 'أين تحمي الخطوط المجمدة برنامج الطازج، وكيف تُحاكم جودة IQF، ومنطق الكلفة عند الاحتفاظ بالخطين معًا.'],
            'fr' => ['IQF vs frais : sécuriser l’offre à l’année | Nile-Maple', 'Où le surgelé protège un programme frais, comment se juge la qualité IQF, et la logique économique de tenir les deux dans un même plan catégorie.'],
        ],
        'private-label-getting-packaging-right-the-first-time' => [
            'en' => ['Private Label: Getting Packaging Right the First Time | Nile-Maple', 'Specify before you design, comply before you brand, and test the pack you will actually ship — the five-step private-label process.'],
            'ar' => ['العلامة الخاصة: تعبئة صحيحة من المرة الأولى | نيل مابل', 'حدد المواصفة قبل التصميم، وحقق الامتثال قبل الهوية، واختبر العبوة التي ستشحنها فعلًا — عملية من خمس خطوات للعلامة الخاصة.'],
            'fr' => ['MDD : le bon emballage du premier coup | Nile-Maple', 'Spécifiez avant de concevoir, conformez avant de marquer, testez le pack réellement expédié — le processus MDD en cinq étapes.'],
        ],
        'seasonal-windows-what-egypt-ships-and-when' => [
            'en' => ['Seasonal Windows: What Egypt Ships, and When | Nile-Maple', 'A month-by-month reading of the Egyptian harvest calendar and how to plan citrus, vegetable, grape and mango orders around it.'],
            'ar' => ['النوافذ الموسمية: ماذا تصدّر مصر ومتى | نيل مابل', 'قراءة شهرية لتقويم الحصاد المصري وكيفية تخطيط طلبات الحمضيات والخضروات والعنب والمانجو حوله.'],
            'fr' => ['Fenêtres saisonnières : l’Égypte, quand ? | Nile-Maple', 'Lecture mois par mois du calendrier de récolte égyptien et planification des commandes d’agrumes, légumes, raisin et mangue autour de lui.'],
        ],
        'the-export-paper-pack-documents-that-clear-customs-faster' => [
            'en' => ['The Export Paper Pack: Documents That Clear Customs Faster | Nile-Maple', 'Phytosanitary certificate, certificate of origin, packing list, invoice — what each one must say so consignments never wait at the border.'],
            'ar' => ['ملف التصدير الورقي: مستندات تُسرّع التخليص | نيل مابل', 'الشهادة الصحية النباتية وشهادة المنشأ وقائمة التعبئة والفاتورة — ما يجب أن يقوله كل مستند حتى لا تنتظر الشحنات عند الحدود.'],
            'fr' => ['Le dossier export : des documents qui dédouanent vite | Nile-Maple', 'Certificat phytosanitaire, certificat d’origine, packing list, facture — ce que chacun doit dire pour que vos lots n’attendent jamais à la frontière.'],
        ],
    ],
];

foreach ([['category', 'categories', 'category_i18n', 'category_id'],
          ['service', 'services', 'service_i18n', 'service_id'],
          ['post', 'posts', 'post_i18n', 'post_id']] as [$type, $t, $i18n, $fk]) {
    foreach (Db::all("SELECT t.id AS tid, i.lang, i.meta_title, i.meta_description FROM $t t JOIN $i18n i ON i.$fk=t.id") as $r) {
        if (trim((string) $r['meta_title']) !== '' && trim((string) $r['meta_description']) !== '') {
            $ins($type, (int) $r['tid'], $r['lang'], (string) $r['meta_title'], (string) $r['meta_description']);
            $n++;
        }
    }
}
/* authored service/post baseline */
foreach ($authored as $type => $map) {
    foreach ($map as $key => $per) {
        $id = $type === 'service'
            ? (int) (Db::one('SELECT id FROM services WHERE code=?', [$key])['id'] ?? 0)
            : (int) (Db::one("SELECT post_id AS id FROM post_i18n WHERE slug=? AND lang='en'", [$key])['id'] ?? 0);
        if (!$id) { fwrite(STDERR, "seed_seo: no entity for authored key '$key'\n"); continue; }
        foreach ($per as $l => [$ti, $de]) { $ins($type, $id, $l, $ti, $de); $n++; }
    }
}

/* ── products: templated-but-differentiated from real spec data ─────────────────────── */
$catName = static fn(int $id, string $lang) => (string) (Db::one(
    'SELECT name FROM category_i18n WHERE category_id=? AND lang=?', [$id, $lang])['name'] ?? '');
foreach (Db::all('SELECT p.id, p.category_id, p.temp_min, p.temp_max, p.temp_unit, i.lang, i.name, i.varieties, i.packing, i.chain
                  FROM products p JOIN product_i18n i ON i.product_id=p.id') as $r) {
    $l = $r['lang'];
    $cat = $catName((int) $r['category_id'], $l);
    $first = static fn(string $s) => trim(explode("\n", trim((string) $s))[0] ?? '', "•-\t ");
    $var = $first($r['varieties']); $pack = $first($r['packing']); $chain = $first($r['chain']);
    $temp = '';
    if ($r['temp_min'] !== null && $r['temp_min'] !== '') {
        $temp = $r['temp_max'] !== null && $r['temp_max'] !== ''
            ? sprintf(' %s–%s °C.', (string) $r['temp_min'], (string) $r['temp_max'])
            : sprintf(' %s °C.', (string) $r['temp_min']);
    }
    /* per-locale composition (the copy direction differs; the data is per-locale already) */
    $title = match ($l) {
        'ar' => sprintf('%s للتصدير من مصر | %s | نيل مابل', $r['name'], $cat),
        'fr' => sprintf('%s d’Égypte à l’export | %s | Nile-Maple', $r['name'], $cat),
        default => sprintf('%s Export from Egypt | %s | Nile-Maple', $r['name'], $cat),
    };
    $desc = match ($l) {
        'ar' => trim(sprintf('%s: أصناف %s. تعبئة %s.%s سلسلة تبريد%s عرض سعر B2B خلال 48 ساعة.',
            $r['name'], $var ?: 'موسمية', $pack ?: 'تصدير', $chain !== '' ? ' ' . rtrim($chain, '.') . '.' : '', $temp)),
        'fr' => trim(sprintf('%s : variétés %s. Emballage %s.%s Chaîne%s Devis B2B sous 48 h.',
            $r['name'], $var ?: 'saisonnières', $pack ?: 'export', $chain !== '' ? ' ' . rtrim($chain, '.') . '.' : '', $temp)),
        default => trim(sprintf('%s: varieties %s. Packing %s.%s Chain%s B2B quote within 48 h.',
            $r['name'], $var ?: 'in season', $pack ?: 'export-grade', $chain !== '' ? ' ' . rtrim($chain, '.') . '.' : '', $temp)),
    };
    $ins('product', (int) $r['id'], $l, $title, $desc);
    $n++;
}

/* ── structural pages: page-specific copy from the language files ───────────────────── */
$pages = ['', 'about', 'services', 'categories', 'blog', 'contact', 'faq',
          'quality-handling', 'packaging-logistics', 'seasonal-availability', 'export-documentation', 'privacy', 'terms'];
foreach (cfg('langs') as $l) {
    I18n::boot($l);
    foreach ($pages as $p) {
        $id = crc32(SeoCanon::for($l, $p)) % 100000;         /* mirrors PublicController::page */
        $key = $p === '' ? 'home' : $p;
        $title = (string) I18n::t("seo.$key.title");
        $desc = (string) I18n::t("seo.$key.desc");
        if ($title !== '' && $desc !== '') { $ins('page', (int) $id, $l, $title, $desc); $n++; }
    }
}

/* ── uniqueness assertion (DoD 15) ───────────────────────────────────────────────────── */
$dupes = Db::all('SELECT COUNT(*) c, lang, title FROM seo_meta GROUP BY lang, title HAVING c > 1');
$ddesc = Db::all('SELECT COUNT(*) c, lang, description FROM seo_meta GROUP BY lang, description HAVING c > 1');
printf("seo_meta seeded: %d rows (%d titles, %d descriptions duplicated)\n", $n, count($dupes), count($ddesc));
foreach (array_slice(array_merge($dupes, $ddesc), 0, 8) as $d) printf("  DUP [%s] %s\n", $d['lang'], mb_substr($d['title'] ?? $d['description'], 0, 70));
Nm\Cache::forgetGroup('data');
exit(($dupes || $ddesc) ? 1 : 0);

/** canonical-path helper identical to the router's canonical for structural pages */
final class SeoCanon
{
    public static function for(string $lang, string $path): string
    {
        return rtrim(\Nm\Seo::urlFor($lang, $path), '/');
    }
}
