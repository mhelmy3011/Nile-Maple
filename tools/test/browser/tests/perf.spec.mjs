// Performance budgets a real browser must measure (Finalization-Plan §4.2/§7.4 `performance`).
// Not a Lighthouse-CI replacement (no lab scoring here) — a lighter, dependency-free budget
// enforcement using the browser's own Performance/PerformanceObserver APIs and Playwright's
// network capture, on Chromium only (LCP/CLS timing APIs are most reliable there).
import { test, expect } from '@playwright/test';

const BUDGETS = {
  home: { transferKB: 700, requests: 12 },
  'category-listing': { transferKB: 900, requests: 60 }, // 30 products x picture srcset
  'product-detail': { transferKB: 400, requests: 15 },
};

async function measure(page, path) {
  const requests = [];
  page.on('response', async (res) => {
    try {
      const url = res.url();
      const headers = res.headers();
      const len = Number(headers['content-length'] || 0);
      requests.push({ url, len, thirdParty: !url.startsWith(page.url().split('/').slice(0, 3).join('/')) && !url.startsWith('http://127.0.0.1') });
    } catch { /* response may be gone by the time we read it — ignore */ }
  });

  await page.addInitScript(() => {
    window.__lcp = 0; window.__cls = 0;
    try {
      new PerformanceObserver((list) => {
        for (const e of list.getEntries()) window.__lcp = Math.max(window.__lcp, e.startTime);
      }).observe({ type: 'largest-contentful-paint', buffered: true });
      new PerformanceObserver((list) => {
        for (const e of list.getEntries()) if (!e.hadRecentInput) window.__cls += e.value;
      }).observe({ type: 'layout-shift', buffered: true });
    } catch { /* older engines — skip, this suite is Chromium-only anyway */ }
  });

  await page.goto(path);
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(500); // let LCP/CLS observers settle after paint
  const { lcp, cls } = await page.evaluate(() => ({ lcp: window.__lcp, cls: window.__cls }));
  return { requests, lcp, cls };
}

test.describe('performance budgets [en, 360, Chromium]', () => {
  for (const [name, path] of [
    ['home', '/en/'],
    ['category-listing', '/en/categories/fresh-fruits/'],
    ['product-detail', '/en/products/orange/'],
  ]) {
    test(`${name}: LCP/requests/third-party budgets`, async ({ page }, testInfo) => {
      test.skip(testInfo.project.name !== 'mobile-360', 'one representative project keeps CI time sane');
      const { requests, lcp, cls } = await measure(page, path);
      const budget = BUDGETS[name];

      const thirdParty = requests.filter((r) => r.thirdParty && !r.url.includes('127.0.0.1') && !r.url.startsWith('data:'));
      expect(thirdParty, `third-party request(s): ${thirdParty.map((r) => r.url).join(', ')}`).toEqual([]);

      const totalKB = requests.reduce((s, r) => s + r.len, 0) / 1024;
      expect(totalKB, `${name} transferred ${totalKB.toFixed(0)}KB, budget ${budget.transferKB}KB`).toBeLessThanOrEqual(budget.transferKB);

      expect(requests.length, `${name} made ${requests.length} requests, budget ${budget.requests}`).toBeLessThanOrEqual(budget.requests);

      // Lab LCP on an unthrottled local server is not comparable to the field p75 budget (1.8s on
      // throttled 4G) — asserted generously here (5s) just to catch a genuinely broken LCP (e.g.
      // D-03's empty hero, where the candidate was a text node that painted late behind webfonts).
      if (lcp > 0) expect(lcp, `${name} LCP ${lcp.toFixed(0)}ms`).toBeLessThan(5000);
      if (cls > 0) expect(cls, `${name} CLS ${cls.toFixed(3)}`).toBeLessThan(0.1);
    });
  }
});
