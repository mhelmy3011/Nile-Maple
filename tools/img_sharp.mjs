#!/usr/bin/env node
/**
 * img_sharp.mjs — native (libvips) pixel encoder for the sandbox/CI image pipeline.
 *
 * `php tools/build_images.php --engine=node` plans every variant and writes one JSON job per
 * line to a job file; this script executes them and then removes the file. The PHP side
 * (`--collect`) re-walks the media_variant rows afterwards to record real byte sizes.
 *
 *   node tools/img_sharp.mjs storage/tmp/img-jobs-<pid>.json
 *
 * Job: {"src":"/abs/in.jpg","dst":"/abs/out.avif","w":640,"h":null,"fmt":"avif","q":55}
 * Same crop/resize contract as Nm\Img: square resize, or focal centre-crop to w×h.
 * Dev-only tooling — never deployed (Hostinger uses the gd/imagick engine).
 */
import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';

/* sharp lives in tools/harness/node_modules (dev-only); fall back to normal resolution */
const here = path.dirname(fileURLToPath(import.meta.url));
const require = createRequire(path.join(here, 'harness', 'noop.js'));
const sharp = require('sharp');

const jobFile = process.argv[2];
if (!jobFile || !fs.existsSync(jobFile)) {
  console.error('usage: node tools/img_sharp.mjs <jobs.jsonl>');
  process.exit(2);
}

const raw = fs.readFileSync(jobFile, 'utf8').split('\n').filter((l) => l.trim());
let ok = 0, fail = 0;
const failed = [];

for (const line of raw) {
  let job;
  try { job = JSON.parse(line); } catch { continue; }
  try {
    fs.mkdirSync(path.dirname(job.dst), { recursive: true });
    let img = sharp(job.src, { failOn: 'none' });
    const meta = await img.metadata();
    const w = job.w, h = job.h && job.h !== job.w ? job.h : null;
    if (h) {
      /* cover-crop: scale so both dimensions cover, then centre-crop (focal 0.5/0.5) */
      img = img.resize(w, h, { fit: 'cover', position: 'centre' });
    } else {
      img = img.resize(w, w, { fit: 'fill' }); // square sources — same as Img::resize
    }
    const enc = job.fmt === 'avif' ? { avif: { quality: job.q } }
      : job.fmt === 'webp' ? { webp: { quality: job.q } }
      : { jpeg: { quality: job.q, progressive: true, mozjpeg: true } };
    await img.toFile(job.dst, enc);
    ok++;
  } catch (e) {
    fail++;
    failed.push(`${job.dst}: ${e.message}`);
    try { fs.unlinkSync(job.dst); } catch { /* ignore */ }
  }
}
fs.unlinkSync(jobFile);
console.log(`sharp: ${ok} written, ${fail} failed`);
if (failed.length) {
  console.error(failed.slice(0, 20).join('\n'));
  process.exit(1);
}
