// Engine-specific rendering breakage (WebKit/Firefox) — not a duplicate of the a11y/touch-target
// matrix, which already runs on Chromium. This just proves the site isn't Chromium-only.
import { test, expect } from '@playwright/test';

test.describe('cross-engine smoke', () => {
  for (const [name, lang, path] of [
    ['home', 'en', '/en/'],
    ['home', 'ar', '/ar/'],
    ['product-detail', 'en', '/en/products/orange/'],
    ['category-listing', 'fr', '/fr/categories/fruits-frais/'],
  ]) {
    test(`${name} [${lang}] loads, hero paints, no console errors`, async ({ page }, testInfo) => {
      test.skip(!['webkit-390', 'firefox-360'].includes(testInfo.project.name));
      const errors = [];
      page.on('console', (msg) => { if (msg.type() === 'error') errors.push(msg.text()); });
      page.on('pageerror', (err) => errors.push(String(err)));

      const res = await page.goto(path);
      expect(res.status()).toBe(200);
      await expect(page.locator('h1')).toBeVisible();

      const hero = page.locator('.hero picture img, .hero img').first();
      if (await hero.count()) {
        await expect(hero).toBeVisible();
        const natural = await hero.evaluate((img) => img.naturalWidth);
        expect(natural, `hero image failed to decode on ${testInfo.project.name}`).toBeGreaterThan(0);
      }

      expect(errors, `console/page errors on ${path} [${testInfo.project.name}]: ${errors.join(' | ')}`).toEqual([]);
    });
  }
});
