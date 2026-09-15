#!/usr/bin/env node
/**
 * build_hero.mjs — art-directed home hero composition (D-03 fallback art direction).
 *
 * The site owns 158 studio product shots but no dedicated hero photography, so the hero is
 * composed from four division-representative shots — a clean 2×2 studio mosaic on the brand
 * cream — instead of shipping an empty hero. Regenerated only when sources change; the output
 * is ingested by `php tools/build_images.php` (pass 3) which cuts the responsive variants.
 *
 *   node tools/build_hero.mjs            # writes docs/data/media/brand/hero-collage.jpg (2400×1350)
 */
import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';

const here = path.dirname(fileURLToPath(import.meta.url));
const repo = path.resolve(here, '..');
const require = createRequire(path.join(here, 'harness', 'noop.js'));
const sharp = require('sharp');

const W = 2400, H = 1350, GUT = 14;                     // 16:9 master, cream gutters
const TILES = [                                          // one representative per division
  'fresh-fruits/image1.jpg',        // orange          — Fresh Fruits
  'fresh-vegetables/image1.jpg',    // potato          — Fresh Vegetables
  'frozen-products/image1.jpg',     // frozen okra     — Frozen Products
  'processed-canned/image1.jpg',    // canned fava     — Processed & Canned
];
const srcRoot = path.join(repo, 'docs', 'data', 'media');
const outDir = path.join(srcRoot, 'brand');
const outFile = path.join(outDir, 'hero-collage.jpg');

const tile = Math.floor((Math.min(W, H) - GUT * 3) / 2); // square-ish tiles that fit two rows
const grid = tile * 2 + GUT * 3;
const offsetX = Math.floor((W - grid) / 2), offsetY = Math.floor((H - grid) / 2);

const composites = [];
for (let i = 0; i < TILES.length; i++) {
  const src = path.join(srcRoot, TILES[i]);
  if (!fs.existsSync(src)) { console.error('missing source: ' + TILES[i]); process.exit(1); }
  const buf = await sharp(src).resize(tile, tile, { fit: 'cover', position: 'centre' }).jpeg({ quality: 90 }).toBuffer();
  composites.push({
    input: buf,
    left: offsetX + GUT + (i % 2) * (tile + GUT),
    top: offsetY + GUT + Math.floor(i / 2) * (tile + GUT),
  });
}
fs.mkdirSync(outDir, { recursive: true });
await sharp({ create: { width: W, height: H, channels: 3, background: '#FDF9F6' } })
  .composite(composites)
  .jpeg({ quality: 88, progressive: true, mozjpeg: true })
  .toFile(outFile);
console.log(`hero collage → ${path.relative(repo, outFile)} (${W}×${H})`);
