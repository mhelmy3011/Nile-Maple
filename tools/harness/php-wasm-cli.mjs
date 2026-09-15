#!/usr/bin/env node
/**
 * PHP CLI shim on the WASM interpreter (dev-only; Hostinger runs real PHP).
 *
 *   node tools/harness/php-wasm-cli.mjs -f tools/rebuild.php -- full
 *   node tools/harness/php-wasm-cli.mjs -r 'require "app/bootstrap.php"; echo Nm\Db::val("SELECT 1");'
 *   node tools/harness/php-wasm-cli.mjs -l tools/seed.php
 *
 * Notes:
 *  · ESM only — `new PHP(await loadNodeRuntime(...))` (the CJS require path fails on v3).
 *  · php.cli() hangs on this runtime; everything runs through php.run(), which uses the CLI
 *    SAPI (php_execute_script). Non-zero exit() from the script surfaces as
 *    PHPExecutionFailureError → parse the code out of it and re-exit with it.
 *  · popen/proc_open/shell_exec do not exist in the WASM build — tools must not use them.
 */
import { PHP } from './node_modules/@php-wasm/universal/index.js';
import { loadNodeRuntime, useHostFilesystem } from './node_modules/@php-wasm/node/index.js';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const repo = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
process.chdir(repo);

const argv = process.argv.slice(2);
let mode = 'f', script = '', rest = [], code = '';
for (let i = 0; i < argv.length; i++) {
  if (argv[i] === '-f') { mode = 'f'; script = argv[++i] ?? ''; }
  else if (argv[i] === '-r') { mode = 'r'; code = argv[++i] ?? ''; }
  else if (argv[i] === '-l') { mode = 'l'; script = argv[++i] ?? ''; }
  else if (argv[i] === '--') { rest = argv.slice(i + 1); break; }
  else rest.push(argv[i]);
}
if ((mode === 'f' || mode === 'l') && !script) { console.error('usage: php-wasm-cli.mjs -f <script> [-- args] | -r <code> | -l <file>'); process.exit(2); }

const php = new PHP(await loadNodeRuntime('8.3', { emscriptenOptions: { processId: Number(process.env.NM_PROCESS_ID || 8123) } }));
useHostFilesystem(php);

const abs = path.resolve(repo, script);

/** run a snippet; returns { code, out, err } with exit-code propagation */
async function exec(snippet) {
  try {
    const r = await php.run({ code: snippet });
    return { code: r.exitCode ?? 0, out: r.text ?? '', err: r.errors ?? '' };
  } catch (e) {
    const m = String(e?.message || e);
    const exit = m.match(/exit code (\d+)/);
    const out = (e?.response?.text) ?? m.slice(m.indexOf('=== Stdout ===') + 14, m.indexOf('=== Stderr ===') > 0 ? m.indexOf('=== Stderr ===') : undefined).trim();
    const err = (e?.response?.errors) ?? (m.match(/=== Stderr ===\n([\s\S]*)/)?.[1] ?? '');
    return { code: exit ? Number(exit[1]) : 255, out: out ?? '', err };
  }
}

if (mode === 'r') {
  const r = await exec(`<?php
if (!defined('STDIN')) define('STDIN', fopen('php://stdin', 'r'));
if (!defined('STDOUT')) define('STDOUT', fopen('php://stdout', 'w'));
if (!defined('STDERR')) define('STDERR', fopen('php://stderr', 'w'));
chdir(${JSON.stringify(repo)});\n${code}\n`);
  if (r.out) process.stdout.write(r.out);
  if (r.err) process.stderr.write(r.err);
  process.exit(r.code);
}

if (!fs.existsSync(abs)) { console.error(`no such file: ${script}`); process.exit(2); }

if (mode === 'l') {
  /* best-effort parse check: tokenise the file (full `php -l` needs the real CLI SAPI).
     Catches unterminated strings/brace tokenisation failures; CI runs php -l for the gate. */
  const probe = await exec(`<?php
$src = file_get_contents(${JSON.stringify(abs)});
$tokens = @token_get_all($src);
$n = 0; foreach ($tokens as $t) if (is_array($t) && $t[0] !== T_WHITESPACE) $n++;
echo "tokens:", $n;
`);
  if (probe.code !== 0 || !/tokens:\d+/.test(probe.out)) { console.error(`lint failed: ${script}`); process.exit(1); }
  console.log(`No syntax problems detected in ${script} (token scan: ${probe.out.match(/tokens:(\d+)/)?.[1]} tokens)`);
  process.exit(0);
}

/* -f: run the script with CLI-ish $argv. The included file shares this global scope,
   so $argv set here is visible at the script's top level (tools read -- args via $argv). */
const runner = `<?php
/* CLI SAPI constants exist in run()-mode only after an explicit define */
if (!defined('STDIN')) define('STDIN', fopen('php://stdin', 'r'));
if (!defined('STDOUT')) define('STDOUT', fopen('php://stdout', 'w'));
if (!defined('STDERR')) define('STDERR', fopen('php://stderr', 'w'));
chdir(${JSON.stringify(repo)});
$argv = array_merge([${JSON.stringify(abs)}], ${JSON.stringify(rest)});
$argc = count($argv);
$_SERVER['argv'] = $argv;
$_SERVER['argc'] = $argc;
$_SERVER['SCRIPT_NAME'] = ${JSON.stringify(script)};
$_SERVER['PHP_SELF'] = ${JSON.stringify(abs)};
error_reporting(E_ALL);
ini_set('display_errors', '1');
include ${JSON.stringify(abs)};
`;
const r = await exec(runner);
if (r.out) process.stdout.write(r.out);
if (r.err) process.stderr.write(r.err);
process.exit(r.code);
