// Browser-only quality gates that tools/test/unit.php and tools/test/e2e.mjs cannot reach:
// axe-core accessibility, real touch-target geometry, real overflow, and RTL visual mirroring.
// Dev-only — never deployed (doc 04's vanilla-PHP pledge covers app/ and public_html/ only).
//
//   npm test                                   run everything against BASE_URL (default :8080)
//   npx playwright test --project=mobile-360   one project
//   npx playwright test --update-snapshots     accept new RTL baselines after an intentional change
import { defineConfig, devices } from '@playwright/test';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const here = path.dirname(fileURLToPath(import.meta.url));
const repoRoot = path.resolve(here, '../../..');
const BASE_URL = process.env.BASE_URL || 'http://127.0.0.1:8080';

export default defineConfig({
  testDir: './tests',
  globalSetup: './global-setup.mjs',
  fullyParallel: true,
  // tools/dev_server.php (php -S) is single-process — it genuinely serves one request at a
  // time. High worker counts against it don't speed anything up, they just queue requests
  // behind each other until slower multi-step flows (admin login + CSRF + form POST) blow past
  // the 30s test timeout. Production (real LiteSpeed/Apache) has no such ceiling; this is a
  // dev-server-only constraint. serve.mjs has the same one-interpreter limit (see its own
  // comment), so the cap applies regardless of which local server is in front.
  workers: 2,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  reporter: process.env.CI ? [['list'], ['html', { open: 'never' }]] : 'list',
  timeout: 30_000,
  expect: { timeout: 8_000, toHaveScreenshot: { maxDiffPixelRatio: 0.02 } },
  use: {
    baseURL: BASE_URL,
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },
  // Auto-starts the site so `npm test` works standalone; set BASE_URL to point at an
  // already-running server (e.g. real serve.mjs with .htaccess parity) to skip this.
  webServer: process.env.BASE_URL ? undefined : {
    command: `php -S 127.0.0.1:8080 -t ${JSON.stringify(path.join(repoRoot, 'public_html'))} ${JSON.stringify(path.join(repoRoot, 'tools', 'dev_server.php'))}`,
    cwd: repoRoot,
    url: BASE_URL,
    reuseExistingServer: !process.env.CI,
    timeout: 20_000,
  },
  projects: [
    // Contract floor (doc 03: "360px is the contract, not 375") — Chromium, real touch emulation.
    { name: 'mobile-360', use: { ...devices['Galaxy S5'], viewport: { width: 360, height: 740 }, isMobile: true, hasTouch: true } },
    { name: 'mobile-390', use: { ...devices['iPhone 12'], viewport: { width: 390, height: 844 } } },
    { name: 'tablet-768', use: { ...devices['iPad Mini'], viewport: { width: 768, height: 1024 } } },
    { name: 'desktop-1440', use: { viewport: { width: 1440, height: 900 } } },
    // Cross-engine smoke at the mobile floor only (full matrix would be 4 viewports x 3 engines x
    // 3 langs x N templates — the a11y/touch-target suites already run that on Chromium; WebKit and
    // Firefox exist here to catch engine-specific rendering breakage, not to duplicate every gate).
    { name: 'webkit-390', use: { ...devices['iPhone 12'], browserName: 'webkit', viewport: { width: 390, height: 844 } } },
    { name: 'firefox-360', use: { browserName: 'firefox', viewport: { width: 360, height: 740 } } },
  ],
});
