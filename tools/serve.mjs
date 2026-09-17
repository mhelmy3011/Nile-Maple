#!/usr/bin/env node
/**
 * Local web server for Nile-Maple (dev / QA / preview).
 *
 * On Hostinger, LiteSpeed + public_html/.htaccess does this job. Here we reproduce the same
 * contract on top of a real PHP 8 interpreter: static files (the pre-built HTML + hashed assets)
 * are served straight from disk, everything else is rewritten into the PHP front controllers —
 * exactly the rules in public_html/.htaccess.
 *
 *   node tools/serve.mjs            # http://0.0.0.0:8080
 *   PORT=9000 NM_ROOT=... node tools/serve.mjs
 */
import http from 'node:http';
import zlib from 'node:zlib';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const here = path.dirname(fileURLToPath(import.meta.url));
const repo = process.env.NM_REPO || path.resolve(here, '..');
const root = process.env.NM_ROOT || path.join(repo, 'public_html');
/*
 * The dev server runs with the repo root as its CWD: useHostFilesystem() chdirs the interpreter
 * there, so relative paths in config and cache resolve the same way they do on the host.
 */
process.chdir(repo);
const port = Number(process.env.PORT || 8080);
const phpVersion = process.env.PHP_VERSION ? Number(process.env.PHP_VERSION) : undefined;

/**
 * The PHP runtime is a dev-only Node dependency (never deployed — Hostinger runs plain PHP).
 * Look for it in the obvious places instead of shipping it inside the repo.
 */
async function loadRuntime() {
  const bases = [
    process.env.NM_NODE_MODULES,
    path.join(repo, 'node_modules'),
    path.join(repo, 'tools', 'harness', 'node_modules'),
    '/home/user/phpruntime/node_modules',
    path.join(process.env.HOME || '', 'phpruntime', 'node_modules'),
  ].filter(Boolean);
  for (const b of bases) {
    const node = path.join(b, '@php-wasm', 'node', 'index.js');
    const universal = path.join(b, '@php-wasm', 'universal', 'index.js');
    if (fs.existsSync(node) && fs.existsSync(universal)) {
      return {
        loadNodeRuntime: (await import(pathToFileURL(node).href)).loadNodeRuntime,
        useHostFilesystem: (await import(pathToFileURL(node).href)).useHostFilesystem,
        PHPRequestHandler: (await import(pathToFileURL(universal).href)).PHPRequestHandler,
        PHP: (await import(pathToFileURL(universal).href)).PHP,
      };
    }
  }
  console.error('PHP dev runtime not found. Install it once (dev-only, not deployed):\n' +
    '  npm install --prefix . --no-audit --no-fund @php-wasm/node@3 @php-wasm/universal@3\n' +
    'Or use your system PHP instead:\n' +
    '  php -S 0.0.0.0:8080 -t public_html tools/dev_server.php');
  process.exit(1);
}
const { loadNodeRuntime, useHostFilesystem, PHPRequestHandler, PHP } = await loadRuntime();

const MIME = {
  '.html': 'text/html; charset=utf-8', '.css': 'text/css; charset=utf-8',
  '.js': 'application/javascript; charset=utf-8', '.mjs': 'application/javascript; charset=utf-8',
  '.json': 'application/json; charset=utf-8', '.webmanifest': 'application/manifest+json',
  '.svg': 'image/svg+xml', '.png': 'image/png', '.jpg': 'image/jpeg', '.jpeg': 'image/jpeg',
  '.webp': 'image/webp', '.avif': 'image/avif', '.gif': 'image/gif', '.ico': 'image/x-icon',
  '.woff2': 'font/woff2', '.woff': 'font/woff', '.txt': 'text/plain; charset=utf-8',
  '.xml': 'application/xml; charset=utf-8', '.pdf': 'application/pdf',
};

/** .htaccess parity: the anonymous site is cookie-less, assets are immutable */
const SECURITY = {
  'X-Content-Type-Options': 'nosniff',
  'X-Frame-Options': 'SAMEORIGIN',
  'Referrer-Policy': 'strict-origin-when-cross-origin',
  'Permissions-Policy': 'camera=(), geolocation=(), microphone=(), payment=()',
  'Content-Security-Policy':
    "default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; " +
    "script-src 'self'; font-src 'self'; connect-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'; object-src 'none'",
};

/* By default the WASM interpreter only sees its own in-memory filesystem. useHostFilesystem()
   mounts the real host tree so the front controllers can require app/ and config/ — without it
   every PHP route 404s on "file not found". */
const php = new PHP(await loadNodeRuntime(phpVersion ?? '8.3', {
  /* required by the node runtime: each interpreter owns one process id */
  emscriptenOptions: { processId: Number(process.env.NM_PROCESS_ID || 4242) },
}));
/**
 * .htaccess parity. php-wasm applies a rule as `path.replace(match, replacement)` with a
 * *string* pattern — the `match` is tested as a regex but replaced literally — so each rule
 * below rewrites on a plain prefix and mirrors one RewriteRule from public_html/.htaccess:
 *   ^api/(.*)$            → index.php?r=api&path=$1
 *   ^manage/?(.*)$        → manage/index.php?path=$1
 *   ^(.+)$  (not a file)  → index.php?r=render      (path handed over as ?p=, dev-only)
 * The bare "/" is rewritten by this server instead, since "/" is the whole path.
 */
useHostFilesystem(php);

const handler = new PHPRequestHandler({
  php,
  documentRoot: root,
  absoluteUrl: `http://localhost:${port}/`,
});

let busy = Promise.resolve();   // one interpreter → serialise PHP requests
const server = http.createServer(async (req, res) => {
  const url = new URL(req.url, 'http://localhost');
  let p = decodeURIComponent(url.pathname);
  if (p.includes('..')) { res.writeHead(400).end('bad path'); return; }

  /* 1 · static from disk: file, or the pretty-directory form /en/x/ → /en/x/index.html */
  let file = path.join(root, p);
  if (!file.startsWith(root)) { res.writeHead(400).end('bad path'); return; }
  if (fs.existsSync(file) && fs.statSync(file).isDirectory()) file = path.join(file, 'index.html');
  if (!fs.existsSync(file) && fs.existsSync(file + '.html')) file += '.html';
  if (fs.existsSync(file) && fs.statSync(file).isFile() && !file.endsWith('.php')) {
    const ext = path.extname(file).toLowerCase();
    const heads = { 'Content-Type': MIME[ext] || 'application/octet-stream', ...SECURITY };
    if (p.startsWith('/assets/')) heads['Cache-Control'] = 'public, max-age=31536000, immutable';
    else if (ext === '.html') heads['Cache-Control'] = 'no-cache';
    else heads['Cache-Control'] = 'public, max-age=3600, must-revalidate';
    /* gzip parity with LiteSpeed: compress text payloads over 1 KiB when the client accepts it */
    const compressible = /^(text\/|application\/(javascript|json|xml|manifest))/.test(String(heads['Content-Type']));
    if (compressible && (req.headers['accept-encoding'] || '').includes('gzip') && fs.statSync(file).size > 1024) {
      const gz = zlib.gzipSync(fs.readFileSync(file), { level: 6 });
      heads['Content-Encoding'] = 'gzip';
      heads['Content-Length'] = gz.length;
      heads.Vary = 'Accept-Encoding';
      res.writeHead(200, heads);
      res.end(gz);
      return;
    }
    heads['Content-Length'] = fs.statSync(file).size;
    res.writeHead(200, heads);
    fs.createReadStream(file).pipe(res);
    return;
  }

  /* 2 · PHP: rewrite rules → front controller */
  const body = [];
  for await (const c of req) body.push(c);
  const headers = {};
  for (const [k, v] of Object.entries(req.headers)) headers[k] = Array.isArray(v) ? v.join(', ') : v;

  /* .htaccess parity, applied here instead of php-wasm rewriteRules: the WASM rewriter does a
     literal string replace of the matched prefix, which double-rewrites a target that already
     starts with the replacement (e.g. /manage/ → /manage/index.php?path=manage/index.php?path=).
     Explicit mapping below mirrors the same rules exactly:
       ^$                      → index.php?r=lang
       ^api/(.*)$              → index.php?r=api&path=$1
       ^manage/?(.*)$          → manage/index.php?path=$1
       everything else         → index.php?r=render&p=$path          (dev-only catch-all) */
  const pathname = url.pathname;
  let target;
  if (pathname === '/') target = '/index.php?r=lang' + (url.search ? '&' + url.search.slice(1) : '');
  else if (pathname === '/api/' || pathname.startsWith('/api/'))
    target = '/index.php?r=api&path=' + pathname.slice('/api/'.length) + (url.search ? '&' + url.search.slice(1) : '');
  else if (pathname === '/manage' || pathname === '/manage/' || pathname.startsWith('/manage/'))
    target = '/manage/index.php?path=' + pathname.replace(/^\/manage\/?/, '') + (url.search ? '&' + url.search.slice(1) : '');
  else if (pathname === '/instructor' || pathname === '/instructor/' || pathname.startsWith('/instructor/'))
    target = '/instructor/index.php?path=' + pathname.replace(/^\/instructor\/?/, '') + (url.search ? '&' + url.search.slice(1) : '');
  else target = '/index.php?r=render&p=' + pathname + (url.search ? '&' + url.search.slice(1) : '');

  const run = () => handler.request({
    url: target,
    method: req.method,
    headers: { ...headers, host: `localhost:${port}` },
    body: body.length ? Buffer.concat(body) : undefined,
  });

  busy = busy.then(run, run);
  try {
    const r = await busy;
    const heads = { ...SECURITY };
    for (const [k, v] of Object.entries(r.headers || {})) {
      if (['x-powered-by', 'connection', 'content-length', 'transfer-encoding'].includes(k.toLowerCase())) continue;
      // Set-Cookie must stay a list — a comma-joined header is a single, unparsable cookie.
      heads[k] = Array.isArray(v) ? (k.toLowerCase() === 'set-cookie' ? v : v.join(', ')) : v;
    }
    res.writeHead(r.httpCode ?? r.httpStatusCode ?? 200, heads);
    res.end(Buffer.from(r.bytes ?? new Uint8Array()));
  } catch (e) {
    res.writeHead(500, { 'Content-Type': 'text/plain; charset=utf-8' });
    res.end('dev server error: ' + (e?.message || e));
  }
});

server.listen(port, '0.0.0.0', () => {
  console.log(`Nile-Maple dev server → http://0.0.0.0:${port}   (docRoot ${root})`);
  console.log('routes: / → lang detect · /en|ar|fr/… → static HTML · /api/* · /manage → dashboard');
});
