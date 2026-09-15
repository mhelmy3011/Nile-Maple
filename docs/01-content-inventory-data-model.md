# 01 — Content Inventory, Entity Model & Database Schema

Source: `Nile-Maple_Company_Profile.docx` (+ duplicate `-1`), `Nile-Maple_Fresh_Fruits_Catalogue.docx`,
`Nile-Maple_Fresh_Vegetables_Catalogue.docx`, `Nile-Maple_Frozen_Products_Catalogue.docx`,
`Nile-Maple_Manufactured_Processed_Canned_Products.docx`.
Machine-readable extraction: **`docs/data/content-manifest.json`** (91 KB, 159 products, all fields) —
regenerate with `python3 tools/extract_content.py` (add `--media` to also unpack images).

---

## 1. Inventory totals (verified by parser, fails loudly on schema drift)

| Category key | Title (EN) | Products | Unique images | Field schema (per product, per language) |
|---|---|---|---|---|
| `fresh-fruits` | Fresh Fruits | 31 | 31 | description · varieties · export_handling · packing · cold_chain |
| `fresh-vegetables` | Fresh Vegetables | 37 | 37 | description · varieties · export_handling · packing · cold_chain |
| `frozen-products` | Frozen Products | 36 | 36 | description · forms · processing · packing · chain_guide |
| `processed-canned` | Manufactured, Processed & Canned Products | 55 | 54 ⚠ | description · forms · processing · packing · chain_guide |
| **Total** | | **159** | **158** | |

All product images are **800×800 JPEG** (~70–150 KB raw each; 20.4 MB raw media incl. logos/divisions).
Company profile media: mark-only logo `image1.png` 580×500 · EN lockup `image2.png` 875×745 ·
AR lockup `image7.png` 915×875 · 4 division photos (`image3–6.jpg`, 800×800 / 1280×1280).

### 1.1 Full product roster (for QA cross-check against the manifest)

- **Fresh Fruits (31):** Orange · Mandarin/Tangerine · Spanish Honey Murcott Mandarin · Lemon · Lime · Grapefruit · Mango · Grapes · Pomegranate · Strawberry · Fresh Dates · Guava · Fig · Peach · Nectarine · Apricot · Plum · Cherry · Watermelon · Cantaloupe · Honeydew Melon · Custard Apple · Persimmon/Kaki · Prickly Pear · Lychee · Apple · Pear · Kiwifruit · Avocado · Banana · Pineapple
- **Fresh Vegetables (37):** Potato · Sweet Potato · Onion · Spring Onion · Shallot · Garlic · Leek · Carrot · Beetroot · Radish · Turnip · Tomato · Cherry Tomato · Bell Pepper/Capsicum · Chili Pepper · Cucumber · Zucchini/Courgette · Eggplant/Aubergine · Okra · Sweet Corn · Green Beans · Green Peas · Snow Peas/Mangetout · Sugar Snap Peas · Broad Beans · Broccoli · Cauliflower · Cabbage · Brussels Sprouts · Iceberg Lettuce · Romaine Lettuce · Spinach · Celery · Artichoke · Fennel · Fresh Molokhia/Jute Mallow · Fresh Herbs
- **Frozen Products (36):** Frozen Okra · Frozen Molokhia · Frozen Green Peas · Frozen Green Beans · Frozen Carrots · Frozen Peas & Carrots · Frozen Mixed Vegetables · Frozen Spinach · Frozen Broccoli · Frozen Cauliflower · Frozen Artichoke · Frozen Yellow Sweet Corn · Frozen Broad Beans · Frozen Taro Cubes · Frozen French Fries & Potato Products · Frozen Onion · Frozen Bell Pepper · Frozen Zucchini · Frozen Grilled Eggplant · Frozen Strawberry · Frozen Mango · Frozen Guava · Frozen Pomegranate Arils · Frozen Fig · Frozen Apricot · Frozen Peach · Frozen Grapes · Frozen Blueberry · Frozen Raspberry · Frozen Blackberry · Frozen Pineapple · Frozen Mixed Berries · Frozen Tropical Fruit Mix · Frozen Mediterranean Vegetable Mix · Frozen Stir-Fry Vegetable Mix · Custom Frozen Mixes
- **Processed & Canned (55):** Canned Fava Beans · Canned Chickpeas · Canned White Beans · Canned Red Kidney Beans · Canned Green Peas · Canned Sweet Corn · Canned Green Beans · Canned Mixed Vegetables · Canned Artichokes · Canned Grape Leaves · Canned Whole Tomatoes · Canned Diced Tomatoes · Canned Mushrooms · Canned Fruit Cocktail · Canned Pineapple · Canned Peaches · Table Olives · Stuffed Green Olives · Pickled Cucumbers/Gherkins · Pickled Onions · Pickled Peppers · Pickled Jalapenos · Pickled Lemons with Safflower · Pickled Turnips · Pickled Carrots · Pickled Mixed Vegetables · Artichokes in Brine · Roasted Red Peppers · Eggplant Makdous · Grilled Eggplant · Tomato Paste · Tomato Sauce · Tomato Passata · Pizza Sauce · Tomato Ketchup · Chili Paste/Harissa · Hot Sauce · Fruit Jam · Orange Marmalade · Date Jam · Prickly Pear Jam · Black Molasses · Natural Honey · Fruit Juice · Fruit Nectar · Fruit Concentrates · Fruit Purees/Pulps · Tomato Juice · Fruit Cocktail Drink · Olive Oil · Tahini · Halva · Hummus · Baba Ghanoush · Natural Vinegar

### 1.2 Data-quality defects found (must be resolved in Phase 4 content pass)

| ID | Defect | Resolution |
|---|---|---|
| DQ-01 | `image44.jpg` used by both **Fruit Juice** and **Fruit Cocktail Drink** | Commission/source one distinct photo; until then dashboard flags product as `media=shared` and SEO/OG uses category fallback |
| DQ-02 | All logo PNGs RGB with opaque cream `#FDF9F6` background | Re-cut to transparent PNG + traced SVG (doc 02 §4) |
| DQ-03 | Logo inks (red/deep-green/charcoal) ≠ requested palette `#1dbf5a`/`#fcb929` | Token reconciliation (doc 02 §2) |
| DQ-04 | Cold-chain values written as "Typically 3-8 C" (no degree sign, text form) | Store structured `temp_min/temp_max/unit` + display string per language |
| DQ-05 | Company Profile exists twice (`-1` file, byte-identical) | Delete duplicate from repo after sign-off; manifest ignores it |
| DQ-06 | No AR/FR translations exist anywhere in source | Translation workbench + professional review (doc 06 §5, doc 07 Phase 4) |
| DQ-07 | No address/physical location, no working hours in source | Client to confirm; seed with "Cairo, Egypt (by appointment)" placeholder flagged `needs_confirmation` |

---

## 2. Entity model (public site + dashboard parity)

```
Category 1—n Product
Category 1—1 CategoryI18n(en,ar,fr)          Product 1—1 ProductI18n(en,ar,fr)
Service  1—1 ServiceI18n                     Post    1—1 PostI18n
Faq      1—1 FaqI18n                         Block   1—1 BlockI18n   (About/Home/section content)
Setting  (key → value per language)          Media   1—n MediaI18n(alt)   Media 1—n MediaVariant(file)
SeoMeta  (polymorphic: entity_type+entity_id+lang)
Enquiry  (form submissions)                  User, AuditLog, Redirect, BuildJob
```

Translation strategy: **child-table i18n** (`*_i18n`) — not JSON columns — so each language row can be
independently published, revisioned and measured for completeness, and so SQL can filter/sort per locale
without JSON functions (MariaDB-friendly on shared hosting).

## 3. Database schema (MariaDB/MySQL, utf8mb4_unicode_ci, InnoDB)

```sql
CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,          -- password_hash(ARGON2ID)
  full_name VARCHAR(120) NOT NULL,
  role ENUM('owner','editor') NOT NULL DEFAULT 'editor',
  status ENUM('active','disabled') NOT NULL DEFAULT 'active',
  failed_logins TINYINT UNSIGNED NOT NULL DEFAULT 0,
  locked_until DATETIME NULL,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(40) NOT NULL UNIQUE,             -- fresh-fruits | fresh-vegetables | frozen-products | processed-canned
  icon_key VARCHAR(40) NOT NULL,                -- design-system icon token
  accent ENUM('green','amber','pine','leaf') NOT NULL DEFAULT 'green',
  sort_order SMALLINT NOT NULL DEFAULT 0,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  cover_media_id INT UNSIGNED NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE category_i18n (
  category_id INT UNSIGNED NOT NULL,
  lang CHAR(2) NOT NULL,                        -- en | ar | fr
  name VARCHAR(160) NOT NULL,
  slug VARCHAR(190) NOT NULL,
  headline VARCHAR(190) NOT NULL,               -- hero line of the category page
  summary TEXT NOT NULL,
  meta_title VARCHAR(70) NULL, meta_description VARCHAR(165) NULL,
  PRIMARY KEY (category_id, lang),
  UNIQUE KEY uq_cat_slug (lang, slug),
  CONSTRAINT fk_cat FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,
  sku VARCHAR(40) NULL,                         -- e.g. FF-07
  source_index SMALLINT NULL,                   -- catalogue number (01..55) for traceability
  card_media_id INT UNSIGNED NULL,
  gallery JSON NULL,                            -- ordered media ids (phase 2 multi-image)
  sort_order SMALLINT NOT NULL DEFAULT 0,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,    -- home showcase tabs
  temp_min DECIMAL(4,1) NULL, temp_max DECIMAL(4,1) NULL, temp_unit CHAR(2) NOT NULL DEFAULT 'C',
  temp_note VARCHAR(80) NULL,                   -- "for table stock" etc.
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_cat_sort (category_id, sort_order),
  CONSTRAINT fk_prod_cat FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE product_i18n (
  product_id INT UNSIGNED NOT NULL,
  lang CHAR(2) NOT NULL,
  name VARCHAR(190) NOT NULL,
  slug VARCHAR(220) NOT NULL,
  description TEXT NOT NULL,
  label_varieties VARCHAR(60) NOT NULL,         -- "Varieties / Types" | "Available Forms" (per-category label, translated)
  varieties TEXT NOT NULL,                      -- or available forms
  label_handling VARCHAR(60) NOT NULL,          -- "Export Handling" | "Processing & Handling"
  handling TEXT NOT NULL,
  label_packing VARCHAR(60) NOT NULL,
  packing TEXT NOT NULL,
  label_chain VARCHAR(60) NOT NULL,             -- "Cold-Chain Guide" | "Frozen-Chain / Storage Guide"
  chain TEXT NOT NULL,
  meta_title VARCHAR(70) NULL, meta_description VARCHAR(165) NULL,
  PRIMARY KEY (product_id, lang),
  UNIQUE KEY uq_prod_slug (lang, slug),
  FULLTEXT KEY ft_prod (name, description, varieties),
  CONSTRAINT fk_prod FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE services (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(40) NOT NULL UNIQUE,
  icon_key VARCHAR(40) NOT NULL,
  sort_order SMALLINT NOT NULL DEFAULT 0,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  media_id INT UNSIGNED NULL
) ENGINE=InnoDB;
CREATE TABLE service_i18n (
  service_id INT UNSIGNED NOT NULL, lang CHAR(2) NOT NULL,
  name VARCHAR(160) NOT NULL, slug VARCHAR(190) NOT NULL,
  teaser VARCHAR(220) NOT NULL,                 -- card text
  body MEDIUMTEXT NOT NULL,                     -- rich (sanitised HTML subset)
  bullets JSON NOT NULL,                        -- ["...","..."]
  meta_title VARCHAR(70) NULL, meta_description VARCHAR(165) NULL,
  PRIMARY KEY (service_id, lang), UNIQUE KEY uq_svc_slug (lang, slug),
  CONSTRAINT fk_svc FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE posts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  author_user_id INT UNSIGNED NULL,
  cover_media_id INT UNSIGNED NULL,
  published_at DATETIME NULL,
  status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
  reading_minutes TINYINT UNSIGNED NOT NULL DEFAULT 3,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE post_i18n (
  post_id INT UNSIGNED NOT NULL, lang CHAR(2) NOT NULL,
  title VARCHAR(190) NOT NULL, slug VARCHAR(220) NOT NULL,
  excerpt VARCHAR(300) NOT NULL,
  body MEDIUMTEXT NOT NULL,
  meta_title VARCHAR(70) NULL, meta_description VARCHAR(165) NULL,
  PRIMARY KEY (post_id, lang), UNIQUE KEY uq_post_slug (lang, slug),
  CONSTRAINT fk_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE blocks (                            -- About / Home / supporting-page dynamic copy
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  zone VARCHAR(60) NOT NULL,                    -- about:intro | about:purpose | about:history | about:quality |
                                                -- about:export | about:progress | about:leadership | about:vision |
                                                -- home:hero | home:stats | home:divisions | home:process | home:why | ...
  kind ENUM('text','list','table','stat','person') NOT NULL,
  sort_order SMALLINT NOT NULL DEFAULT 0,
  payload JSON NOT NULL                         -- structured: {rows:[[..]], items:[..], value:"+12", label:".."}
) ENGINE=InnoDB;
CREATE TABLE block_i18n (
  block_id INT UNSIGNED NOT NULL, lang CHAR(2) NOT NULL,
  title VARCHAR(190) NULL, eyebrow VARCHAR(120) NULL,
  payload JSON NOT NULL,                        -- translated mirror of blocks.payload
  PRIMARY KEY (block_id, lang),
  CONSTRAINT fk_block FOREIGN KEY (block_id) REFERENCES blocks(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE faqs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  group_code VARCHAR(40) NOT NULL DEFAULT 'general',  -- general|products|packaging|payment|logistics|documents
  sort_order SMALLINT NOT NULL DEFAULT 0,
  is_published TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;
CREATE TABLE faq_i18n (
  faq_id INT UNSIGNED NOT NULL, lang CHAR(2) NOT NULL,
  question VARCHAR(250) NOT NULL, answer TEXT NOT NULL,
  PRIMARY KEY (faq_id, lang),
  CONSTRAINT fk_faq FOREIGN KEY (faq_id) REFERENCES faqs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE settings (
  s_key VARCHAR(80) NOT NULL,                   -- contact.email | contact.phone | contact.whatsapp |
                                                -- social.instagram | social.facebook | hours | address | legal.note
  lang CHAR(2) NOT NULL DEFAULT '*',            -- '*' = language-neutral
  value TEXT NOT NULL,
  PRIMARY KEY (s_key, lang)
) ENGINE=InnoDB;

CREATE TABLE media (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  filename VARCHAR(190) NOT NULL,               -- hashed name, ext = original
  mime VARCHAR(60) NOT NULL, width SMALLINT NOT NULL, height SMALLINT NOT NULL,
  bytes INT UNSIGNED NOT NULL, focal_x DECIMAL(4,3) NOT NULL DEFAULT .5, focal_y DECIMAL(4,3) NOT NULL DEFAULT .5,
  source_ref VARCHAR(120) NULL,                 -- e.g. "Fresh_Fruits_Catalogue.docx#image7"
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE media_variant (
  media_id INT UNSIGNED NOT NULL,
  fmt ENUM('avif','webp','jpg') NOT NULL, w SMALLINT NOT NULL,
  path VARCHAR(220) NOT NULL, bytes INT UNSIGNED NOT NULL,
  PRIMARY KEY (media_id, fmt, w),
  CONSTRAINT fk_media FOREIGN KEY (media_id) REFERENCES media(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE media_i18n (
  media_id INT UNSIGNED NOT NULL, lang CHAR(2) NOT NULL, alt VARCHAR(220) NOT NULL,
  PRIMARY KEY (media_id, lang),
  CONSTRAINT fk_media2 FOREIGN KEY (media_id) REFERENCES media(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE seo_meta (                          -- per-entity, per-language overrides + globals
  entity_type ENUM('page','category','product','service','post','faq') NOT NULL,
  entity_id INT UNSIGNED NOT NULL DEFAULT 0,    -- 0 for static pages
  lang CHAR(2) NOT NULL,
  title VARCHAR(70) NULL, description VARCHAR(165) NULL,
  canonical_override VARCHAR(250) NULL, robots VARCHAR(60) NULL,
  og_image_media_id INT UNSIGNED NULL,
  PRIMARY KEY (entity_type, entity_id, lang)
) ENGINE=InnoDB;

CREATE TABLE enquiries (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL, email VARCHAR(190) NOT NULL,
  phone VARCHAR(40) NULL, company VARCHAR(160) NULL, country VARCHAR(90) NULL,
  subject VARCHAR(190) NOT NULL, product_interest VARCHAR(190) NULL,
  message TEXT NOT NULL, consent TINYINT(1) NOT NULL DEFAULT 0,
  lang CHAR(2) NOT NULL, ip_hash CHAR(64) NOT NULL, ua VARCHAR(250) NULL,
  status ENUM('new','read','replied','closed') NOT NULL DEFAULT 'new',
  mailed TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_status (status, created_at)
) ENGINE=InnoDB;

CREATE TABLE redirects ( id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  from_path VARCHAR(250) NOT NULL, to_path VARCHAR(250) NOT NULL,
  code SMALLINT NOT NULL DEFAULT 301, UNIQUE KEY uq_from (from_path) ) ENGINE=InnoDB;

CREATE TABLE audit_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL, action VARCHAR(60) NOT NULL,
  entity_type VARCHAR(40) NULL, entity_id INT UNSIGNED NULL,
  diff JSON NULL, ip_hash CHAR(64) NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_entity (entity_type, entity_id)
) ENGINE=InnoDB;

CREATE TABLE build_jobs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  scope VARCHAR(120) NOT NULL,                    -- page:/en/products/orange | cat:fresh-fruits | full
  status ENUM('queued','running','done','failed') NOT NULL DEFAULT 'queued',
  pages SMALLINT NOT NULL DEFAULT 0, ms INT NULL, error TEXT NULL,
  started_at DATETIME NULL, finished_at DATETIME NULL
) ENGINE=InnoDB;
```

## 4. i18n contract

- Languages fixed: `en` (default/x-default), `ar` (RTL), `fr`.
- UI strings: PHP lang files `app/lang/{lang}.php` returning arrays (compiled to a cached PHP array).
- Content strings: the `*_i18n` tables above. **Nothing user-facing is hard-coded in templates.**
- URL: `/{lang}/{...}` ; language switch = same path with swapped prefix (client requirement).
- First visit to `/`: 302 to cookie language, else `Accept-Language` (ar→/ar/, fr→/fr/, else /en/); cookie `nm_lang` set for 1 year.
- `<html lang="ar" dir="rtl">` etc.; every template renders from logical-property CSS so RTL is free of layout forks.
- Numbers: Western digits everywhere for specs/temps/phones (D-11); dates via `IntlDateFormatter` per locale.
- Completeness: dashboard computes per-entity, per-language fill % (fields non-empty) → gates publishing.

## 5. Seed content derived from the Company Profile (drafted, ready for dashboard import)

### 5.1 Services (6) — seeded into `services` + `service_i18n`

| code | EN name | Teaser source (profile) | Icon |
|---|---|---|---|
| `sourcing` | Global Sourcing & Supplier Management | "Flexible sourcing — seasonal and buyer-specific sourcing across fresh, frozen and shelf-stable categories"; grower/packer/processor network | compass/leaf |
| `export-programme` | Export Programme Management | the 6-step path: enquiry → sourcing → offer → preparation → quality review → shipment | route/clipboard |
| `private-label` | Private Label & Custom Packaging | "supplied under the Nile-Maple name and label… packaging and packing configurations can be customized upon request" | tag/box |
| `quality-coldchain` | Quality Control & Cold-Chain Management | 6 quality pillars + handling controls + pre-shipment review | shield/snowflake |
| `freight` | Freight & Logistics Coordination | air freight for high-value fresh; sea freight reefer/ambient; forwarder & carrier coordination | ship/plane |
| `documentation` | Export Documentation & Compliance | commercial invoice, packing list, certificate of origin, product certificates, transport docs | document/stamp |

Each service detail page body = the matching profile paragraphs (HOW WE WORK table, QUALITY table,
EXPORT CAPABILITY bullets) restructured into H2 sections with the 6-step diagram where relevant.

### 5.2 FAQs (12 seeds, grouped) — from profile §FAQ intent + commercial-flexibility paragraph

1. Which product categories do you export? (4 divisions, 159 lines)
2. What quantities / minimum order can you handle? (container programmes; mixed containers after feasibility review)
3. Are products supplied under the Nile-Maple label? (yes; private label & custom packs on request)
4. Can packaging, pack size, net weight or count be customized? (yes, subject to feasibility & destination rules)
5. How are ocean-freight pallets calculated? (standard container-loading plan; **not** customizable — exact client caveat)
6. Can I mix different products in one container? (yes after Nile-Maple review & confirmation of feasibility/availability)
7. Which freight modes do you arrange? (air for time-sensitive fresh; sea reefer/ambient; temperature & ventilation planning)
8. Which export documents do you provide? (invoice, packing list, COO, product-specific certificates, transport docs)
9. How is quality controlled before shipment? (specification agreement, handling controls, packing integrity, pre-shipment review, records)
10. What are your typical lead times? (season & product dependent; confirmed at offer stage)
11. How do I request a quotation? (form / WhatsApp / email; what to include: product, quantity, destination, timing, terms)
12. In which languages can we work? (EN / AR / FR; site & documents)

### 5.3 Stat/proof blocks (home + about counters) — use only defensible claims

`4` product divisions · `159` catalogue lines · `3` working languages · `24/48h` quotation response target
(**never** invent "+12 years / +1000 clients" like the reference site — the profile explicitly rejects
unsupported claims: *"steady, responsible growth rather than claims that cannot be supported"*).

### 5.4 Blog seed briefs (8, editorial calendar for Phase 4)

1. The Egyptian Citrus Export Window: Orange & Mandarin Season, Month by Month
2. A Buyer's Guide to Egyptian Mango Varieties (Owais → Tommy Atkins)
3. IQF Explained: Grades, Cuts and How to Specify Frozen Vegetables
4. Berry Cold-Chain: Keeping Strawberries at 0–2 °C from Farm to Reefer
5. Export Documents Checklist for Egyptian Food Shipments
6. Private-Label Packing: What Can (and Cannot) Be Customized in a Container Programme
7. Barhi & Beyond: Specifying Fresh Dates for Export
8. Molokhia, Okra, Artichoke: Egypt's Mediterranean Vegetable Advantage

## 6. Media asset plan (naming + storage)

- Ingest: `tools/extract_content.py --media` → `docs/data/media/{category}/imageN.jpg` (build-time only, **not** committed).
- Production path: `public_html/assets/media/{category}/{slug}-{w}.{avif|webp|jpg}` widths `320/480/640/800` + `og-1200x630`.
- Logos: `assets/brand/logo-{mark,en,ar}.{svg,webp,png}` after re-cut (doc 02 §4); `favicon.svg` + `apple-touch.png` from mark.
- Every `<img>` ships `width`/`height`, `srcset`/`sizes`, `alt` from `media_i18n`, `decoding="async"`; LCP image gets `fetchpriority="high"` + `<link rel="preload">`.
