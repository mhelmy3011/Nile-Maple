<?php
/**
 * Trilingual seed content (EN source-of-truth from DOCX; AR/FR professionally written
 * where structural, controlled-language baseline for per-product prose — decision D-14).
 */
$L = static fn(string $en, string $ar, string $fr) => ['en' => $en, 'ar' => $ar, 'fr' => $fr];

$categories = [
    'fresh-fruits' => [
        'icon' => 'citrus', 'accent' => 'green',
        'name' => $L('Fresh Fruits', 'الفواكه الطازجة', 'Fruits frais'),
        'slug' => $L('fresh-fruits', 'فواكه-طازجة', 'fruits-frais'),
        'headline' => $L('Selected produce. Careful handling. Reliable global supply.', 'حاصلات منتقاة. تداول حريص. توريد عالمي موثوق.', 'Produits sélectionnés, manipulation soignée, supply fiable.'),
        'summary' => $L('Citrus, mangoes, grapes, pomegranates, strawberries, dates, melons, stone fruits and other seasonal lines.',
            'حمضيات ومانجو وعنب ورمان وفراولة وبلح وبطيخ وفاكهة ذات نواة وخطوط موسمية أخرى.',
            'Agrumes, mangues, raisins, grenades, fraises, dattes, melons, fruits à noyau et autres lignes saisonnières.'),
        'meta_t' => $L('Fresh Fruits from Egypt | 31 Export Lines | Nile-Maple', 'فواكه طازجة من مصر | 31 خط تصدير | نيل مابل', 'Fruits frais d’Égypte | 31 références | Nile-Maple'),
        'meta_d' => $L('Egyptian fresh fruits for export: varieties, export handling, packing and cold-chain guides for 31 product lines.',
            'فواكه مصرية طازجة للتصدير: أصناف وتداول تصديري وتعبئة وأدلة سلسلة تبريد لـ31 خطًا.',
            'Fruits frais égyptiens à l’export : variétés, manipulation, emballage et chaîne du froid pour 31 références.'),
    ],
    'fresh-vegetables' => [
        'icon' => 'sprout', 'accent' => 'green',
        'name' => $L('Fresh Vegetables', 'الخضروات الطازجة', 'Légumes frais'),
        'slug' => $L('fresh-vegetables', 'خضروات-طازجة', 'legumes-frais'),
        'headline' => $L('Harvest condition, sorting, ventilation, temperature and pack strength.', 'حالة الحصاد والفرز والتهوية ودرجة الحرارة ومتانة العبوة.', 'État à la récolte, tri, ventilation, température et solidité d’emballage.'),
        'summary' => $L('Potatoes, onions, garlic, tomatoes, peppers, artichokes, leafy vegetables, legumes and specialty produce.',
            'بطاطس وبصل وثوم وطماطم وفلفل وخرشوف وخضروات ورقية وبقوليات وحاصلات متخصصة.',
            'Pommes de terre, oignons, ail, tomates, poivrons, artichauts, feuilles, légumineuses et spécialités.'),
        'meta_t' => $L('Fresh Vegetables from Egypt | 37 Export Lines | Nile-Maple', 'خضروات طازجة من مصر | 37 خط تصدير | نيل مابل', 'Légumes frais d’Égypte | 37 références | Nile-Maple'),
        'meta_d' => $L('Egyptian fresh vegetables for export with handling, packing and cold-chain specifications for 37 lines.',
            'خضروات مصرية طازجة للتصدير مع مواصفات تداول وتعبئة وسلسلة تبريد لـ37 خطًا.',
            'Légumes frais égyptiens à l’export avec spécifications de manipulation, emballage et chaîne du froid.'),
    ],
    'frozen-products' => [
        'icon' => 'snowflake', 'accent' => 'pine',
        'name' => $L('Frozen Products', 'المنتجات المجمدة', 'Produits surgelés'),
        'slug' => $L('frozen-products', 'منتجات-مجمدة', 'produits-surgeles'),
        'headline' => $L('Rapid freezing, stable frozen storage and an uninterrupted cold chain.', 'تجميد سريع وتخزين مجمد مستقر وسلسلة تبريد غير منقطعة.', 'Surgélation rapide, stockage stable et chaîne du froid ininterrompue.'),
        'summary' => $L('IQF fruits, vegetables, herbs, mixed vegetables and buyer-specific blends.',
            'فواكه وخضروات وأعشاب مجمدة IQF وخلطات خضروات وخلطات حسب طلب المشتري.',
            'Fruits, légumes, herbes IQF, mélanges et blends spécifiques acheteurs.'),
        'meta_t' => $L('IQF Frozen Products from Egypt | 36 Lines | Nile-Maple', 'منتجات مجمدة IQF من مصر | 36 خطًا | نيل مابل', 'Produits surgelés IQF d’Égypte | 36 références | Nile-Maple'),
        'meta_d' => $L('Egyptian IQF frozen fruits, vegetables, herbs and mixes with forms, processing and frozen-chain guides.',
            'فواكه وخضروات وأعشاب وخلطات مصرية مجمدة IQF مع أشكال وتصنيع وأدلة سلسلة تجميد.',
            'Fruits, légumes, herbes et mélanges IQF égyptiens avec formes, transformation et guide congélation.'),
    ],
    'processed-canned' => [
        'icon' => 'can', 'accent' => 'amber',
        'name' => $L('Manufactured, Processed & Canned Products', 'المنتجات المصنعة والمعالجة والمعلبة', 'Produits transformés & appertisés'),
        'slug' => $L('processed-canned', 'مصنعات-ومعلبات', 'transformes-appertises'),
        'headline' => $L('Recipe specification, fill weight, thermal processing, sealing, shelf life and labelling.',
            'مواصفة الوصفة والوزن والمعاجة الحرارية والإغلاق ومدة الصلاحية والبطاقة.',
            'Recette, poids net, traitement thermique, sertissage, DLUO et étiquetage.'),
        'summary' => $L('Canned vegetables and fruits, pickles, olives, sauces, juices, jams, spreads and pantry products.',
            'خضروات وفاكهة معلبة ومخللات وزيتون وصلصات وعصائر ومربى ومستلزمات مخزن.',
            'Légumes et fruits appertisés, pickles, olives, sauces, jus, confitures et épicerie.'),
        'meta_t' => $L('Processed & Canned Foods from Egypt | 55 Lines | Nile-Maple', 'مصنعات ومعلبات مصرية | 55 خطًا | نيل مابل', 'Produits transformés & appertisés d’Égypte | 55 références | Nile-Maple'),
        'meta_d' => $L('Egyptian canned, pickled and shelf-stable foods: forms, processing, packing and storage guides for 55 lines.',
            'معلبات ومخللات وأغذية مصرية طويلة الحفظ: أشكال وتصنيع وتعبئة وأدلة تخزين لـ55 خطًا.',
            'Appertisés, pickles et produits longue conservation égyptiens : formes, transformation, emballage, stockage.'),
    ],
];

/* 159 product names — AR / FR (trade-standard terminology) */
$N = static fn(string $ar, string $fr) => ['ar' => $ar, 'fr' => $fr];
$product_names = [
 'ORANGE' => $N('برتقال', 'Orange'), 'MANDARIN / TANGERINE' => $N('ماندرين / يوسفي', 'Mandarine / Clémentine'),
 'SPANISH HONEY MURCOTT MANDARIN' => $N('ماندرين سبانيش هني مركوت', 'Mandarine Spanish Honey Murcott'),
 'LEMON' => $N('ليمون', 'Citron'), 'LIME' => $N('ليمون أخضر (لايم)', 'Lime'), 'GRAPEFRUIT' => $N('جريب فروت', 'Pamplemousse'),
 'MANGO' => $N('مانجو', 'Mangue'), 'GRAPES' => $N('عنب', 'Raisin de table'), 'POMEGRANATE' => $N('رمان', 'Grenade'),
 'STRAWBERRY' => $N('فراولة', 'Fraise'), 'FRESH DATES' => $N('بلح طازج', 'Dattes fraîches'), 'GUAVA' => $N('جوافة', 'Goyave'),
 'FIG' => $N('تين', 'Figue'), 'PEACH' => $N('خوخ', 'Pêche'), 'NECTARINE' => $N('نكتارين', 'Nectarine'),
 'APRICOT' => $N('مشمش', 'Abricot'), 'PLUM' => $N('برقوق', 'Prune'), 'CHERRY' => $N('كرز', 'Cerise'),
 'WATERMELON' => $N('بطيخ', 'Pastèque'), 'CANTALOUPE' => $N('كانتلوب', 'Cantaloup'), 'HONEYDEW MELON' => $N('شمام هانيديو', 'Melon honeydew'),
 'CUSTARD APPLE' => $N('قشطة (أنونا)', 'Pomme cannelle'), 'PERSIMMON / KAKI' => $N('كاكا (برسيمون)', 'Kaki / Persimmon'),
 'PRICKLY PEAR' => $N('تين شوكي', 'Figue de Barbarie'), 'LYCHEE' => $N('ليتشي', 'Litchi'), 'APPLE' => $N('تفاح', 'Pomme'),
 'PEAR' => $N('كمثرى', 'Poire'), 'KIWIFRUIT' => $N('كيوي', 'Kiwi'), 'AVOCADO' => $N('أفوكادو', 'Avocat'),
 'BANANA' => $N('موز', 'Banane'), 'PINEAPPLE' => $N('أناناس', 'Ananas'),
 'POTATO' => $N('بطاطس', 'Pomme de terre'), 'SWEET POTATO' => $N('بطاطا حلوة', 'Patate douce'), 'ONION' => $N('بصل', 'Oignon'),
 'SPRING ONION' => $N('بصل أخضر', 'Oignon nouveau'), 'SHALLOT' => $N('شالوت', 'Échalote'), 'GARLIC' => $N('ثوم', 'Ail'),
 'LEEK' => $N('كراث', 'Poireau'), 'CARROT' => $N('جزر', 'Carotte'), 'BEETROOT' => $N('بنجر', 'Betterave'),
 'RADISH' => $N('فجل', 'Radis'), 'TURNIP' => $N('لفت', 'Navet'), 'TOMATO' => $N('طماطم', 'Tomate'),
 'CHERRY TOMATO' => $N('طماطم شيري', 'Tomate cerise'), 'BELL PEPPER / CAPSICUM' => $N('فلفل رومي', 'Poivron'),
 'CHILI PEPPER' => $N('فلفل حار', 'Piment'), 'CUCUMBER' => $N('خيار', 'Concombre'), 'ZUCCHINI / COURGETTE' => $N('كوسة', 'Courgette'),
 'EGGPLANT / AUBERGINE' => $N('باذنجان', 'Aubergine'), 'OKRA' => $N('بامية', 'Gombo'), 'SWEET CORN' => $N('ذرة حلوة', 'Maïs doux'),
 'GREEN BEANS' => $N('فاصوليا خضراء', 'Haricots verts'), 'GREEN PEAS' => $N('بازلاء', 'Petits pois'),
 'SNOW PEAS / MANGETOUT' => $N('بازلاء الثلج', 'Pois mangetout'), 'SUGAR SNAP PEAS' => $N('بازلاء سكرية', 'Pois gourmands'),
 'BROAD BEANS' => $N('فول أخضر', 'Fèves'), 'BROCCOLI' => $N('بروكلي', 'Brocoli'), 'CAULIFLOWER' => $N('قرنبيط', 'Chou-fleur'),
 'CABBAGE' => $N('كرنب', 'Chou'), 'BRUSSELS SPROUTS' => $N('كرنب بروكسل', 'Choux de Bruxelles'),
 'ICEBERG LETTUCE' => $N('خس آيسبرج', 'Salade iceberg'), 'ROMAINE LETTUCE' => $N('خس رومين', 'Salade romaine'),
 'SPINACH' => $N('سبانخ', 'Épinards'), 'CELERY' => $N('كرفس', 'Céleri'), 'ARTICHOKE' => $N('خرشوف', 'Artichaut'),
 'FENNEL' => $N('شمر', 'Fenouil'), 'FRESH MOLOKHIA / JUTE MALLOW' => $N('ملوخية طازجة', 'Molokhia fraîche (corète)'),
 'FRESH HERBS' => $N('أعشاب طازجة', 'Herbes fraîches'),
 'FROZEN OKRA' => $N('بامية مجمدة', 'Gombo surgelé'), 'FROZEN MOLOKHIA' => $N('ملوخية مجمدة', 'Molokhia surgelée'),
 'FROZEN GREEN PEAS' => $N('بازلاء مجمدة', 'Petits pois surgelés'), 'FROZEN GREEN BEANS' => $N('فاصوليا خضراء مجمدة', 'Haricots verts surgelés'),
 'FROZEN CARROTS' => $N('جزر مجمد', 'Carottes surgelées'), 'FROZEN PEAS & CARROTS' => $N('بازلاء وجزر مجمدات', 'Petits pois et carottes surgelés'),
 'FROZEN MIXED VEGETABLES' => $N('خضروات مشكلة مجمدة', 'Légumes mélangés surgelés'), 'FROZEN SPINACH' => $N('سبانخ مجمدة', 'Épinards surgelés'),
 'FROZEN BROCCOLI' => $N('بروكلي مجمد', 'Brocoli surgelé'), 'FROZEN CAULIFLOWER' => $N('قرنبيط مجمد', 'Chou-fleur surgelé'),
 'FROZEN ARTICHOKE' => $N('خرشوف مجمد', 'Artichauts surgelés'), 'FROZEN YELLOW SWEET CORN' => $N('ذرة حلوة صفراء مجمدة', 'Maïs doux jaune surgelé'),
 'FROZEN BROAD BEANS' => $N('فول أخضر مجمد', 'Fèves surgelées'), 'FROZEN TARO CUBES' => $N('مكعبات قلقاس مجمدة', 'Cubes de taro surgelés'),
 'FROZEN FRENCH FRIES & POTATO PRODUCTS' => $N('بطاطس مقلية مجمدة ومنتجات بطاطس', 'Frites surgelées & produits de pomme de terre'),
 'FROZEN ONION' => $N('بصل مجمد', 'Oignons surgelés'), 'FROZEN BELL PEPPER' => $N('فلفل رومي مجمد', 'Poivrons surgelés'),
 'FROZEN ZUCCHINI' => $N('كوسة مجمدة', 'Courgettes surgelées'), 'FROZEN GRILLED EGGPLANT' => $N('باذنجان مشوي مجمد', 'Aubergines grillées surgelées'),
 'FROZEN STRAWBERRY' => $N('فراولة مجمدة', 'Fraises surgelées'), 'FROZEN MANGO' => $N('مانجو مجمدة', 'Mangues surgelées'),
 'FROZEN GUAVA' => $N('جوافة مجمدة', 'Goyaves surgelées'), 'FROZEN POMEGRANATE ARILS' => $N('حبوب رمان مجمدة', 'Grains de grenade surgelés'),
 'FROZEN FIG' => $N('تين مجمد', 'Figues surgelées'), 'FROZEN APRICOT' => $N('مشمش مجمد', 'Abricots surgelés'),
 'FROZEN PEACH' => $N('خوخ مجمد', 'Pêches surgelées'), 'FROZEN GRAPES' => $N('عنب مجمد', 'Raisins surgelés'),
 'FROZEN BLUEBERRY' => $N('توت أزرق مجمد', 'Myrtilles surgelées'), 'FROZEN RASPBERRY' => $N('توت العليق مجمد', 'Framboises surgelées'),
 'FROZEN BLACKBERRY' => $N('توت أسود مجمد', 'Mûres surgelées'), 'FROZEN PINEAPPLE' => $N('أناناس مجمد', 'Ananas surgelé'),
 'FROZEN MIXED BERRIES' => $N('توت مشكل مجمد', 'Fruits rouges mélangés surgelés'),
 'FROZEN TROPICAL FRUIT MIX' => $N('خليط فواكه استوائية مجمد', 'Mélange de fruits tropicaux surgelé'),
 'FROZEN MEDITERRANEAN VEGETABLE MIX' => $N('خليط خضروات متوسطي مجمد', 'Mélange de légumes méditerranéens surgelé'),
 'FROZEN STIR-FRY VEGETABLE MIX' => $N('خليط خضروات ستير-فراي مجمد', 'Mélange stir-fry surgelé'),
 'CUSTOM FROZEN MIXES' => $N('خلطات مجمدة حسب الطلب', 'Mélanges surgelés sur mesure'),
 'CANNED FAVA BEANS' => $N('فول معلب', 'Fèves appertisées'), 'CANNED CHICKPEAS' => $N('حمص معلب', 'Pois chiches appertisés'),
 'CANNED WHITE BEANS' => $N('فاصوليا بيضاء معلبة', 'Haricots blancs appertisés'), 'CANNED RED KIDNEY BEANS' => $N('فاصوليا حمراء معلبة', 'Haricots rouges appertisés'),
 'CANNED GREEN PEAS' => $N('بازلاء معلبة', 'Petits pois appertisés'), 'CANNED SWEET CORN' => $N('ذرة حلوة معلبة', 'Maïs doux appertisé'),
 'CANNED GREEN BEANS' => $N('فاصوليا خضراء معلبة', 'Haricots verts appertisés'), 'CANNED MIXED VEGETABLES' => $N('خضروات مشكلة معلبة', 'Légumes mélangés appertisés'),
 'CANNED ARTICHOKES' => $N('خرشوف معلب', 'Artichauts appertisés'), 'CANNED GRAPE LEAVES' => $N('ورق عنب معلب', 'Feuilles de vigne appertisées'),
 'CANNED WHOLE TOMATOES' => $N('طماطم صحيحة معلبة', 'Tomates entières appertisées'), 'CANNED DICED TOMATOES' => $N('طماطم مقطعة معلبة', 'Tomates dés appertisées'),
 'CANNED MUSHROOMS' => $N('مشروم معلب', 'Champignons appertisés'), 'CANNED FRUIT COCKTAIL' => $N('كوكتيل فواكه معلب', 'Cocktail de fruits appertisé'),
 'CANNED PINEAPPLE' => $N('أناناس معلب', 'Ananas appertisé'), 'CANNED PEACHES' => $N('خوخ معلب', 'Pêches appertisées'),
 'TABLE OLIVES' => $N('زيتون مائدة', 'Olives de table'), 'STUFFED GREEN OLIVES' => $N('زيتون أخضر محشي', 'Olives vertes farcies'),
 'PICKLED CUCUMBERS / GHERKINS' => $N('مخلل خيار / كورنيش', 'Cornichons au vinaigre'), 'PICKLED ONIONS' => $N('مخلل بصل', 'Oignons au vinaigre'),
 'PICKLED PEPPERS' => $N('مخلل فلفل', 'Poivrons au vinaigre'), 'PICKLED JALAPENOS' => $N('مخلل هالبينو', 'Jalapeños au vinaigre'),
 'PICKLED LEMONS WITH SAFFLOWER' => $N('مخلل ليمون بالقرطوم', 'Citrons confits au carthame'), 'PICKLED TURNIPS' => $N('مخلل لفت', 'Navets au vinaigre'),
 'PICKLED CARROTS' => $N('مخلل جزر', 'Carottes au vinaigre'), 'PICKLED MIXED VEGETABLES' => $N('مخلل خضروات مشكل', 'Pickles de légumes mélangés'),
 'ARTICHOKES IN BRINE' => $N('خرشوف في محلول ملحي', 'Artichauts en saumure'), 'ROASTED RED PEPPERS' => $N('فلفل أحمر مشوي', 'Poivrons rouges rôtis'),
 'EGGPLANT MAKDOUS' => $N('مقدوس باذنجان', 'Makdous d’aubergine'), 'GRILLED EGGPLANT' => $N('باذنجان مشوي', 'Aubergines grillées'),
 'TOMATO PASTE' => $N('صلصة طماطم مركزة', 'Concentré de tomate'), 'TOMATO SAUCE' => $N('صوص طماطم', 'Sauce tomate'),
 'TOMATO PASSATA' => $N('طماطم باساتا', 'Passata de tomate'), 'PIZZA SAUCE' => $N('صوص بيتزا', 'Sauce pizza'),
 'TOMATO KETCHUP' => $N('كتشاب طماطم', 'Ketchup'), 'CHILI PASTE / HARISSA' => $N('شطة / هريسة', 'Harissa / pâte de piment'),
 'HOT SAUCE' => $N('صوص حار', 'Sauce piquante'), 'FRUIT JAM' => $N('مربى فواكه', 'Confiture de fruits'),
 'ORANGE MARMALADE' => $N('مربى برتقال', 'Marmelade d’orange'), 'DATE JAM' => $N('مربى بلح', 'Confiture de dattes'),
 'PRICKLY PEAR JAM' => $N('مربى تين شوكي', 'Confiture de figue de Barbarie'), 'BLACK MOLASSES' => $N('دبس أسود', 'Mélasse noire'),
 'NATURAL HONEY' => $N('عسل طبيعي', 'Miel naturel'), 'FRUIT JUICE' => $N('عصير فواكه', 'Jus de fruits'),
 'FRUIT NECTAR' => $N('رحيق فواكه', 'Nectar de fruits'), 'FRUIT CONCENTRATES' => $N('مركزات فواكه', 'Concentrés de fruits'),
 'FRUIT PUREES / PULPS' => $N('بيوريه / لب فواكه', 'Purées et pulpes de fruits'), 'TOMATO JUICE' => $N('عصير طماطم', 'Jus de tomate'),
 'FRUIT COCKTAIL DRINK' => $N('مشروب كوكتيل فواكه', 'Boisson cocktail de fruits'), 'OLIVE OIL' => $N('زيت زيتون', 'Huile d’olive'),
 'TAHINI' => $N('طحينة', 'Tahini'), 'HALVA' => $N('حلاوة طحينية', 'Halva'), 'HUMMUS' => $N('حمص بطحينة', 'Houmous'),
 'BABA GHANOUSH' => $N('بابا غنوج', 'Baba ghanoush'), 'NATURAL VINEGAR' => $N('خل طبيعي', 'Vinaigre naturel'),
];

/* packing glossary for AR/FR controlled rendering */
$packing_glossary = [
 'ar' => ['Ventilated cartons' => 'كراتين مهواة', 'ventilated cartons' => 'كراتين مهواة', 'Telescopic' => 'تلسكوبية', 'telescopic' => 'تلسكوبية',
   'Open-top' => 'مفتوحة الأعلى', 'Mesh bags' => 'أكياس شبكية', 'mesh bags' => 'أكياس شبكية', 'Jumbo bags' => 'أكياس جامبو',
   'Punnets' => 'عبوات صغيرة', 'punnets' => 'عبوات صغيرة', 'Clamshell' => 'عبوات صدفية', 'Cartons' => 'كراتين', 'cartons' => 'كراتين',
   'Carton' => 'كرتونة', 'Bags' => 'أكياس', 'bags' => 'أكياس', 'Baskets' => 'أقفاص', 'Bins' => 'صناديق كبيرة', 'bins' => 'صناديق كبيرة',
   'Trays' => 'صوانٍ', 'trays' => 'صوانٍ', 'Tray' => 'صينية', 'Pouches' => 'أكياس محكمة', 'pouches' => 'أكياس محكمة',
   'Drums' => 'براميل', 'Bulk' => 'سائب', 'bulk' => 'سائب', 'Lined' => 'مبطنة', 'lined' => 'مبطنة', 'Single-layer' => 'بطبقة واحدة',
   'Master cartons' => 'كراتين رئيسية', 'Consumer packs' => 'عبوات استهلاكية', 'food-service' => 'للقطاع الغذائي', 'Food-service' => 'للقطاع الغذائي',
   'Retail' => 'للتجزئة', 'retail' => 'للتجزئة', 'Glass jars' => 'برطمانات زجاجية', 'jars' => 'برطمانات', 'Cans' => 'عبوات معدنية', 'cans' => 'عبوات معدنية',
   'Bunches' => 'حزم', 'Braids' => 'ضفائر', 'Nets' => 'شبك', 'Flow-wrapped' => 'مغلفة', 'Pallet' => 'طبلات', 'pallet' => 'طبلات'],
 'fr' => ['Ventilated cartons' => 'cartons ventilés', 'ventilated cartons' => 'cartons ventilés', 'Telescopic' => 'télescopiques', 'telescopic' => 'télescopiques',
   'Open-top' => 'ouverts dessus', 'Mesh bags' => 'filets', 'mesh bags' => 'filets', 'Jumbo bags' => 'sacs jumbo', 'Punnets' => 'barquettes', 'punnets' => 'barquettes',
   'Clamshell' => 'barquettes clapet', 'Cartons' => 'cartons', 'cartons' => 'cartons', 'Bags' => 'sacs', 'bags' => 'sacs', 'Baskets' => 'paniers',
   'Bins' => 'bacs', 'bins' => 'bacs', 'Trays' => 'plateaux', 'trays' => 'plateaux', 'Pouches' => 'sachets', 'pouches' => 'sachets', 'Drums' => 'fûts',
   'Bulk' => 'vrac', 'bulk' => 'vrac', 'Lined' => 'doublés', 'lined' => 'doublés', 'Single-layer' => 'simple couche', 'Master cartons' => 'cartons maîtres',
   'Consumer packs' => 'emballages consommateur', 'food-service' => 'food-service', 'Food-service' => 'food-service', 'Retail' => 'détail', 'retail' => 'détail',
   'Glass jars' => 'bocaux en verre', 'jars' => 'bocaux', 'Cans' => 'boîtes métal', 'cans' => 'boîtes métal', 'Bunches' => 'bottes', 'Braids' => 'tresses',
   'Nets' => 'filets', 'Flow-wrapped' => 'flow-pack', 'Pallet' => 'palette', 'pallet' => 'palette'],
];

$spec_labels = [
 'varieties' => $L('Varieties / Types', 'الأصناف / الأنواع', 'Variétés / Types'),
 'handling'  => $L('Export Handling', 'التداول التصديري', 'Manipulation export'),
 'packing'   => $L('Packing', 'التعبئة', 'Emballage'),
 'chain'     => $L('Cold-Chain Guide', 'دليل السلسلة الباردة', 'Guide chaîne du froid'),
];
/* frozen/processed label overrides */
$spec_labels['forms'] = $L('Available Forms', 'الأشكال المتاحة', 'Formes disponibles');
$spec_labels['processing'] = $L('Processing & Handling', 'التصنيع والتداول', 'Transformation & manipulation');
$spec_labels['storage'] = $L('Frozen-Chain / Storage Guide', 'دليل السلسلة المجمدة / التخزين', 'Guide congélation / stockage');

$desc_templates = [
 'fresh-fruits' => [
   'ar' => static fn($n, $v, $p, $t) => "يُورَّد منتج $n ضمن برنامج نيل مابل للفواكه الطازجة بانتقاء دقيق للون والحجم والنضج وقوة العرض في الأسواق. الأصناف: $v. التعبئة: $p. $t",
   'fr' => static fn($n, $v, $p, $t) => "$n est fourni dans le programme fruits frais Nile-Maple, avec une sélection soignée de couleur, calibre, maturité et tenue en rayon. Variétés : $v. Emballage : $p. $t"],
 'fresh-vegetables' => [
   'ar' => static fn($n, $v, $p, $t) => "يُورَّد $n بجودة تصديرية منتظمة: شكل موحد وقشرة نظيفة وجودة طهي أو تصنيع معوّل عليها. الأصناف: $v. التعبئة: $p. $t",
   'fr' => static fn($n, $v, $p, $t) => "$n est fourni en qualité export constante : forme uniforme, peau propre et qualité culinaire ou industrielle fiable. Variétés : $v. Emballage : $p. $t"],
 'frozen-products' => [
   'ar' => static fn($n, $v, $p, $t) => "يُحفظ $n بنظام تجميد سريع IQF للحفاظ على اللون والقوام والنكهة على مدار العام. الأشكال: $v. التعبئة: $p. $t",
   'fr' => static fn($n, $v, $p, $t) => "$n est préservé par surgélation IQF pour garder couleur, texture et saveur toute l’année. Formes : $v. Emballage : $p. $t"],
 'processed-canned' => [
   'ar' => static fn($n, $v, $p, $t) => "يُجهّز $n وفق وصفات متفق عليها وبأوزان تعبئة مضبوطة ومعالجة حرارية وإغلاق محكم ومدة صلاحية موثقة. الأشكال: $v. التعبئة: $p. $t",
   'fr' => static fn($n, $v, $p, $t) => "$n est préparé selon recette convenue, poids net maîtrisé, traitement thermique, sertissage et DLUO documentée. Formes : $v. Emballage : $p. $t"],
];
$handling_templates = [
 'fresh-fruits' => [
   'ar' => 'تُقتطف الثمار عند النضج التجاري ويُزال حرار الحقل سريعًا، ثم تُغسل وتُدرج بالحجم واللون وتُعبأ بحماية مناسبة مع سلسلة تبريد نظيفة حتى الشحن.',
   'fr' => 'Les fruits sont récoltés à maturité commerciale, refroidis rapidement, lavés, calibrés par taille et couleur puis emballés avec protection et chaîne du froid propre jusqu’au chargement.'],
 'fresh-vegetables' => [
   'ar' => 'تُجهّز الحاصلات حسب المواصفة: معالجة ما بعد الحصاد، تنظيف أو غسيل، فرز وتدريج وفحص، ثم تبريد سريع مع رطوبة وتهوية مناسبتين لحماية المنتج في النقل.',
   'fr' => 'Les légumes sont préparés selon spécification : soins post-récolte, nettoyage ou lavage, tri, calibrage et contrôle, puis refroidissement rapide avec humidité et ventilation adaptées.'],
 'frozen-products' => [
   'ar' => 'تُغسل الخامات وتُقطع وتُدرج ثم تُجمد فرديًا بسرعة (IQF) وتُخزن عند −18 °م مع فصل الحبات وسلسلة تجميد غير منقطعة حتى الوجهة.',
   'fr' => 'Les matières sont lavées, coupées, calibrées puis surgelées individuellement (IQF) et stockées à −18 °C, grains séparés et chaîne du froid ininterrompue jusqu’à destination.'],
 'processed-canned' => [
   'ar' => 'تُفرز الخامات وتُغسل وتُجهز وفق الوصفة، ثم تُعبأ بالوزن الصافي المتفق عليه وتُعقم حراريًا وتُغلق بإحكام مع ضبط مدة الصلاحية ومتطلبات البطاقة.',
   'fr' => 'Les matières sont triées, lavées et préparées selon recette, puis remplies au poids net convenu, traitées thermiquement, serties, avec DLUO et étiquetage maîtrisés.'],
];
$chain_templates = [
 'ar' => static fn($a, $b) => $a !== null && $b !== null ? "عادة $a–$b °م" : ($a !== null ? "عادة $a °م" : 'حسب المواصفة'),
 'fr' => static fn($a, $b) => $a !== null && $b !== null ? "Généralement $a–$b °C" : ($a !== null ? "Généralement $a °C" : 'selon spécification'),
];

return compact('categories', 'product_names', 'packing_glossary', 'spec_labels', 'desc_templates', 'handling_templates', 'chain_templates')
    + require __DIR__ . '/seed_content2.php';
