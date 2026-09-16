// Admin blocks editor (Finalization-Plan real-user audit, 2026-09-16). Two real bugs found by
// actually opening this screen and reading the database, not by reasoning about the code:
//   1. The i18n payload column was aliased (i.payload AS i18n_payload) to avoid colliding with
//      the base blocks.payload in `SELECT b.*` — correct at the SQL level — but the row-grouping
//      code then stored the *whole* joined row under langs[$lang] without remapping, so the
//      template's $lr['payload'] silently read the base column instead: every multi-language
//      block whose real content lives only per-language (e.g. about:export's Packing/
//      Documentation/Loading/Arrival cards) showed as empty "[]" in the edit form despite
//      rendering correctly on the live site.
//   2. The save handler read $_POST['payload'] for the *base* payload, but the form has no
//      field named that (only payload_en/ar/fr) — so it was always the '{}' fallback, and every
//      single block save silently wiped blocks.payload, including media refs like the D-03
//      hero's media_id. Caught before anyone had successfully saved a block (D-01 blocked
//      dashboard login until this same audit), so nothing was actually corrupted in production
//      data — this test exists so it never gets the chance to be.
import { test, expect } from '@playwright/test';
import { loginAsAdmin, ADMIN } from './helpers.mjs';

test.describe('admin blocks editor', () => {
  test.beforeEach(async ({ page }, testInfo) => {
    test.skip(testInfo.project.name !== 'mobile-390');
    await loginAsAdmin(page);
  });

  test('a block with real per-language content is NOT shown empty in the edit form', async ({ page }) => {
    await page.goto(`${ADMIN}/blocks?zone=about:export`);
    const payloadField = page.locator('textarea[name="payload_en"]').first();
    await expect(payloadField).toBeVisible();
    const value = await payloadField.inputValue();
    expect(value, 'about:export EN payload should show its real items, not an empty base payload').toContain('Packing');
  });

  test('saving a block never wipes its base payload (media refs survive a save)', async ({ page }) => {
    await page.goto(`${ADMIN}/blocks?zone=home:hero`);
    // touch a harmless field (eyebrow) and save — this alone used to be enough to zero out
    // blocks.payload (and with it home:hero's media_id, silently reintroducing the D-03 empty
    // hero) on every single block save, of any block, regardless of what was actually edited.
    const eyebrow = page.locator('input[name="eyebrow_en"]').first();
    const before = await eyebrow.inputValue();
    await eyebrow.fill(before); // no-op edit — the bug didn't need a real change to trigger
    await page.locator('button:has-text("Save block")').first().click();
    await page.waitForLoadState();
    await page.goto(`${ADMIN}/blocks?zone=home:hero`);
    const hint = await page.locator('.hint code').first().textContent();
    expect(hint, 'home:hero base payload (media_id) must survive a save').toMatch(/media_id/);
  });
});
