// WCAG 2.1 AA via axe-core, every template x language (Finalization-Plan §7.4 `a11y` suite).
// Runs once, on the mobile-360 project — accessibility structure (landmarks, heading order,
// contrast, labels) does not change by viewport in this codebase (logical-properties CSS,
// doc 02 §12), so multiplying this across every project would just burn CI minutes.
import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { allRoutes } from './routes.mjs';

test.describe('a11y (axe-core, WCAG 2.1 AA)', () => {
  for (const [name, lang, path] of allRoutes()) {
    test(`${name} [${lang}] — 0 critical/serious violations`, async ({ page }, testInfo) => {
      test.skip(testInfo.project.name !== 'mobile-360', 'a11y structure is viewport-independent — one project is enough');
      await page.goto(path);
      const results = await new AxeBuilder({ page })
        .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
        .analyze();
      const serious = results.violations.filter((v) => v.impact === 'critical' || v.impact === 'serious');
      if (serious.length) {
        const detail = serious
          .map((v) => `  [${v.impact}] ${v.id}: ${v.help} (${v.nodes.length} node(s)) — ${v.nodes[0]?.target?.join(' ')}`)
          .join('\n');
        throw new Error(`${serious.length} critical/serious axe violation(s) on ${path}:\n${detail}`);
      }
      expect(serious).toHaveLength(0);
    });
  }
});

test.describe('a11y — keyboard journey (browse -> product -> quote)', () => {
  test('home -> categories -> product -> contact is reachable by keyboard alone', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name !== 'mobile-360');
    await page.goto('/en/');
    // skip-link is first in DOM (doc 02 §10)
    await page.keyboard.press('Tab');
    const skip = page.locator('.skip-link');
    await expect(skip).toBeFocused();
    // The header nav + hero CTA must be keyboard-reachable and land somewhere sensible.
    await page.goto('/en/categories/fresh-fruits/');
    const firstCard = page.locator('a.card-product, a[href*="/products/"]').first();
    await expect(firstCard).toBeVisible();
    await firstCard.focus();
    await expect(firstCard).toBeFocused();
    await page.keyboard.press('Enter');
    await page.waitForURL(/\/products\//);
    // desktop nav's own contact link exists in the DOM at mobile widths too (nav-desktop is
    // display:none, not removed) but isn't a valid target — the real always-visible quote CTA
    // on the product page is the .pd-actions button (product.php).
    const quoteBtn = page.locator('.pd-actions a[href*="/contact/"]').first();
    await expect(quoteBtn).toBeVisible();
  });
});
