<?php
/**
 * Narrative zones the About page renders but no seed ever filled: about:history,
 * about:quality, about:progress (doc 03 §4 expects history/quality/progress sections).
 *
 * Copy is derived from the operating model already stated in about:intro and from the
 * home:quality pillar set — nothing here invents a date, a milestone or a claim. It all
 * stays editable in the dashboard (Blocks), so the profile's own milestone wording can be
 * dropped in by the client without a code change.
 *
 * The payload is a JSON blob on purpose: trilingual arrays can be edited without brace
 * surgery, and a bad edit fails loudly at decode instead of breaking the PHP parser.
 * Required from seed_content2.php, which supplies $blocks and $L.
 */
$nmExtra = json_decode(<<<'JSON'
{
 "blocks": [
  {
   "zone": "about:history",
   "kind": "list",
   "base": [],
   "l": {
    "en": {
     "title": "How a Nile-Maple order develops",
     "payload": {
      "items": [
       {
        "t": "Sourcing & selection",
        "x": "Farms and packhouses are chosen per category, and every lot is inspected against the agreed specification before it moves forward."
       },
       {
        "t": "Quality & processing discipline",
        "x": "Grading, cleaning and product-specific handling protocols are applied and recorded at each step."
       },
       {
        "t": "Packing & labelling",
        "x": "Pack format, protection and label content are built to the destination market’s requirements, private-label artwork included."
       },
       {
        "t": "Cold-chain export coordination",
        "x": "Pre-cooling, temperature set points, documentation and loading supervision follow the shipment through to arrival."
       }
      ]
     }
    },
    "ar": {
     "title": "كيف يتطور طلب نيل مابل",
     "payload": {
      "items": [
       {
        "t": "التوريد والانتقاء",
        "x": "تُختار المزارع وبيانات الفرز حسب الفئة، وتُفحص كل دفعة مقابل المواصفة المتفق عليها قبل تقدّمها."
       },
       {
        "t": "انضباط الجودة والتصنيع",
        "x": "تُطبَّق وتُسجَّل خطوات التدريج والتنظيف والمناولة الخاصة بكل منتج عند كل مرحلة."
       },
       {
        "t": "التعبئة والبطاقة",
        "x": "تُبنى صيغة العبوة والحماية ومحتوى البطاقة وفق متطلبات سوق الوجهة، بما في ذلك علامة المشتري."
       },
       {
        "t": "تنسيق تصدير سلسلة التبريد",
        "x": "يتابع فريق التصدير التبريد المسبق ونقاط الحرارة والمستندات والتحميل حتى الوصول."
       }
      ]
     }
    },
    "fr": {
     "title": "Comment une commande Nile-Maple se déroule",
     "payload": {
      "items": [
       {
        "t": "Sourcing & sélection",
        "x": "Fermes et ateliers choisis par catégorie ; chaque lot est contrôlé contre la spécification convenue."
       },
       {
        "t": "Discipline qualité & transformation",
        "x": "Calibrage, lavage et manutention spécifiques au produit, appliqués et enregistrés à chaque étape."
       },
       {
        "t": "Emballage & étiquetage",
        "x": "Format, protection et contenu d’étiquetage conformes au marché de destination, marque de l’acheteur comprise."
       },
       {
        "t": "Coordination export chaîne du froid",
        "x": "Pré-refroidissement, consignes de température, documentation et chargement suivis jusqu’à l’arrivée."
       }
      ]
     }
    }
   }
  },
  {
   "zone": "about:quality",
   "kind": "list",
   "base": [],
   "l": {
    "en": {
     "title": "Quality commitments",
     "payload": {
      "items": [
       {
        "i": "shield",
        "t": "Incoming inspection",
        "x": "Every lot checked against specification on arrival."
       },
       {
        "i": "thermometer",
        "t": "Temperature control",
        "x": "Set points and transit windows per product guide."
       },
       {
        "i": "check",
        "t": "Packing verification",
        "x": "Protection, ventilation and labelling confirmed before loading."
       },
       {
        "i": "clipboard",
        "t": "Document readiness",
        "x": "Certificates and lists prepared before the container moves."
       },
       {
        "i": "clipboard",
        "t": "Lot traceability",
        "x": "One record per lot, so a question raised weeks later can still be answered."
       },
       {
        "i": "globe",
        "t": "Arrival follow-up",
        "x": "Condition at destination reviewed and fed back into handling protocols."
       }
      ],
      "commitments": [
       "We quote against a specification, not a generic catalogue line.",
       "We confirm pack format and label content before production starts.",
       "We record temperature and handling steps for every shipment.",
       "We answer questions with documents, not recollections."
      ]
     }
    },
    "ar": {
     "title": "التزامات الجودة",
     "payload": {
      "items": [
       {
        "i": "shield",
        "t": "فحص الاستلام",
        "x": "تُفحص كل دفعة مقابل المواصفة عند الوصول."
       },
       {
        "i": "thermometer",
        "t": "ضبط الحرارة",
        "x": "نقاط ضبط وفترات عبور وفق دليل كل منتج."
       },
       {
        "i": "check",
        "t": "التحقق من التعبئة",
        "x": "تُؤكد الحماية والتهوية والبطاقة قبل التحميل."
       },
       {
        "i": "clipboard",
        "t": "جاهزية المستندات",
        "x": "تُجهَّز الشهادات والقوائم قبل تحرك الحاوية."
       },
       {
        "i": "clipboard",
        "t": "تتبع الدفعة",
        "x": "سجل واحد لكل دفعة يمكن به الرد على سؤال يطرأ بعد أسابيع."
       },
       {
        "i": "globe",
        "t": "متابعة الوصول",
        "x": "تُراجع الحالة عند الوجهة وتُغذَّى مرة أخرى في بروتوكولات المناولة."
       }
      ],
      "commitments": [
       "نُسعّر مقابل مواصفة وليس سطر كتالوج عام.",
       "نؤكد صيغة العبوة ومحتوى البطاقة قبل بدء الإنتاج.",
       "نسجّل الحرارة وخطوات المناولة لكل شحنة.",
       "نجيب عن الأسئلة بمستندات لا بذاكرتنا."
      ]
     }
    },
    "fr": {
     "title": "Engagements qualité",
     "payload": {
      "items": [
       {
        "i": "shield",
        "t": "Inspection à réception",
        "x": "Chaque lot vérifié contre la spécification."
       },
       {
        "i": "thermometer",
        "t": "Contrôle température",
        "x": "Consignes et fenêtres de transit par produit."
       },
       {
        "i": "check",
        "t": "Vérification emballage",
        "x": "Protection, ventilation et étiquetage confirmés avant chargement."
       },
       {
        "i": "clipboard",
        "t": "Préparation documentaire",
        "x": "Certificats et listes prêts avant le départ du conteneur."
       },
       {
        "i": "clipboard",
        "t": "Traçabilité des lots",
        "x": "Un dossier par lot pour répondre encore à une question soulevée des semaines plus tard."
       },
       {
        "i": "globe",
        "t": "Suivi à l’arrivée",
        "x": "L’état à destination est revu et réinjecté dans les protocoles de manutention."
       }
      ],
      "commitments": [
       "Nous chiffrons sur spécification, pas sur une ligne de catalogue générique.",
       "Nous confirmons format d’emballage et étiquetage avant production.",
       "Nous enregistrons température et manutention pour chaque expédition.",
       "Nous répondons par documents, pas par souvenirs."
      ]
     }
    }
   }
  },
  {
   "zone": "about:progress",
   "kind": "list",
   "base": [],
   "l": {
    "en": {
     "title": "Where the platform stands",
     "payload": {
      "paragraphs": [
       "The public catalogue covers 159 product lines across four divisions, each carrying product name, specification summary, packing format, handling notes and season window in three languages.",
       "The dashboard behind it lets the team adjust a season window, add a line or republish a page without waiting on a developer."
      ],
      "items": [
       {
        "t": "Three languages, one record",
        "x": "Every page is generated from a single dashboard entry in English, Arabic and French."
       },
       {
        "t": "Media handled per product",
        "x": "Photography is cropped, compressed and published as modern formats automatically."
       },
       {
        "t": "Same-day republication",
        "x": "A change saved in the dashboard is rebuilt and live in seconds."
       }
      ]
     }
    },
    "ar": {
     "title": "أين يقف النظام الآن",
     "payload": {
      "paragraphs": [
       "يغطي الكتالوج العام 159 خط منتج موزعة على أربعة قطاعات، لكل خط اسم المنتج وملخص المواصفة وصيغة العبوة وملاحظات التداول والنافذة الموسمية بثلاث لغات.",
       "تتيح لوحة التحكم للفريق تعديل نافذة موسمية أو إضافة خط أو إعادة نشر صفحة دون انتظار مطوّر."
      ],
      "items": [
       {
        "t": "ثلاث لغات بسجل واحد",
        "x": "تُولَّد كل صفحة من مدخل واحد في اللوحة بالإنجليزية والعربية والفرنسية."
       },
       {
        "t": "وسائط لكل منتج",
        "x": "تُقصّ الصور وتُضغط وتُنشر تلقائيًا بصيغ حديثة."
       },
       {
        "t": "إعادة نشر في نفس اليوم",
        "x": "أي تعديل يُحفظ في اللوحة يُعاد بناؤه وينشر خلال ثوانٍ."
       }
      ]
     }
    },
    "fr": {
     "title": "Où en est la plateforme",
     "payload": {
      "paragraphs": [
       "Le catalogue public couvre 159 références réparties sur quatre divisions, chacune avec nom, spécification, format d’emballage, manutention et fenêtre saisonnière dans trois langues.",
       "Le tableau de bord permet à l’équipe d’ajuster une fenêtre, d’ajouter une référence ou de republier une page sans attendre un développeur."
      ],
      "items": [
       {
        "t": "Republication le jour même",
        "x": "Une modification enregistrée est reconstruite et publiée en quelques secondes."
       },
       {
        "t": "Trois langues, un enregistrement",
        "x": "Chaque page est générée depuis une seule fiche du tableau de bord, en anglais, arabe et français."
       },
       {
        "t": "Médias gérés par produit",
        "x": "Les photos sont recadrées, compressées et publiées en formats modernes automatiquement."
       }
      ]
     }
    }
   }
  }
 ]
}
JSON, true);
if (!is_array($nmExtra)) {{
    fwrite(STDERR, "seed_content3.php: payload JSON did not decode — refusing to continue\n");
    exit(1);
}}
foreach ($nmExtra['blocks'] as $nb) {{
    $pick = static fn(string $k, string $l) => $nb['l'][$l][$k] ?? null;
    $blocks[] = [
        'zone'    => $nb['zone'],
        'kind'    => $nb['kind'],
        'base'    => $nb['base'] ?? [],
        'title'   => $L($pick('title', 'en'), $pick('title', 'ar'), $pick('title', 'fr')),
        'eyebrow' => $L($pick('eyebrow', 'en'), $pick('eyebrow', 'ar'), $pick('eyebrow', 'fr')),
        'payload' => $L($pick('payload', 'en') ?? [], $pick('payload', 'ar') ?? [], $pick('payload', 'fr') ?? []),
    ];
}}
