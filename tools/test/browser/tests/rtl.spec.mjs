// RTL correctness (Finalization-Plan §7.4 `rtl-visual`, doc 03 §12). Two layers:
//   1. Functional mirroring assertions — robust, no committed baseline needed, catch real
//      regressions (wrong dir, un-mirrored icons, LTR content that leaked out of its <bdi>).
//   2. Screenshot snapshots on the two highest-traffic templates — real pixel-level regression
//      protection. First run creates the baseline (`--update-snapshots`); commit it deliberately.
import { test, expect } from '@playwright/test';

test.describe('RTL — functional mirroring', () => {
  test('html[dir] and lang are correct per locale', async ({ page }) => {
    await page.goto('/ar/');
    await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
    await expect(page.locator('html')).toHaveAttribute('lang', 'ar');
    await page.goto('/en/');
    await expect(page.locator('html')).toHaveAttribute('dir', 'ltr');
    await page.goto('/fr/');
    await expect(page.locator('html')).toHaveAttribute('dir', 'ltr');
  });

  test('chevrons/arrows are mirrored in RTL (doc 03 §12)', async ({ page }, testInfo) => {
    // the only .chev in this template's DOM lives inside the mobile sheet-menu, which doesn't
    // exist on desktop (nav-desktop is used there instead) — mobile-only assertion.
    test.skip(!testInfo.project.name.startsWith('mobile'), 'sheet-menu chevron only exists on mobile');
    await page.goto('/ar/categories/فواكه-طازجة/');
    // must open the sheet first, since a closed [hidden] ancestor can make a browser report a
    // misleading computed transform even when the CSS rule genuinely matches (verified with
    // el.matches()).
    await page.click('[data-sheet-open]');
    await page.waitForSelector('.sheet:not([hidden])');
    const mirrored = await page.evaluate(() => {
      const el = document.querySelector('.chev, .cs-more .i, .tab-all .i, .cp-go .i');
      if (!el) return null;
      return getComputedStyle(el).transform;
    });
    // logical-properties CSS (doc 02 §12) mirrors via `html[dir="rtl"] .chev{transform:scaleX(-1)}`
    expect(mirrored, 'no .chev/.cs-more/.tab-all/.cp-go icon found to check').not.toBeNull();
    expect(mirrored).toMatch(/matrix\(-1|scaleX\(-1\)/);
  });

  test('phone numbers and temperatures stay LTR inside Arabic content', async ({ page }) => {
    await page.goto('/ar/products/برتقال/');
    const tempBadge = page.locator('.temp-badge').first();
    if (await tempBadge.count()) {
      const dir = await tempBadge.evaluate((el) => getComputedStyle(el).direction || el.closest('[dir]')?.getAttribute('dir'));
      expect(dir === 'ltr' || dir === null).toBeTruthy();
    }
    const phone = page.locator('a[href^="tel:"]').first();
    await expect(phone).toBeVisible();
    const text = (await phone.textContent()) || '';
    expect(text).toMatch(/\+?\d[\d\s]+/); // Western digits, never Arabic-Indic (D-11 sibling rule, doc 03 §12)
  });

  test('no untranslated Arabic leaks into EN/FR pages (D-02 regression lock, browser-rendered)', async ({ page }) => {
    for (const lang of ['en', 'fr']) {
      await page.goto(`/${lang}/`);
      const bodyText = await page.locator('body').innerText();
      const arabicOutsideAr = /[؀-ۿ]/.test(bodyText.replace(/العربية/g, ''));
      expect(arabicOutsideAr, `Arabic characters rendered on /${lang}/`).toBeFalsy();
    }
  });
});

test.describe('RTL — visual snapshots', () => {
  test('home page renders correctly mirrored [ar, 360]', async ({ page }, testInfo) => {
    // Baselines were captured on macOS (Playwright's default snapshot naming is per-OS) and
    // committed as the local pre-release check the README documents; CI runs on ubuntu-latest,
    // where a pixel-identical baseline doesn't exist yet, so this would false-fail there rather
    // than catch anything real — skip in CI, not just narrow the project list.
    test.skip(!!process.env.CI || !['mobile-360', 'desktop-1440'].includes(testInfo.project.name));
    await page.goto('/ar/');
    await page.waitForLoadState('networkidle');
    await expect(page).toHaveScreenshot(`home-ar-${testInfo.project.name}.png`, { fullPage: false });
  });

  test('product detail renders correctly mirrored [ar, 360]', async ({ page }, testInfo) => {
    test.skip(!!process.env.CI || !['mobile-360', 'desktop-1440'].includes(testInfo.project.name));
    await page.goto('/ar/products/برتقال/');
    await page.waitForLoadState('networkidle');
    await expect(page).toHaveScreenshot(`product-ar-${testInfo.project.name}.png`, { fullPage: false });
  });
});
