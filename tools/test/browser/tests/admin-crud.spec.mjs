// FAQ CRUD end to end through the real dashboard UI (Finalization-Plan §7.4 `admin-crud`).
// FAQs are the simplest entity (no media picker) so the full round trip — create, verify live,
// edit, publish-gate, delete — stays fast and unambiguous about what broke.
import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { test, expect } from '@playwright/test';
import { loginAsAdmin, ADMIN } from './helpers.mjs';

const repoRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../../..');

// Only "create -> edit -> delete" ever writes a row (Admin::saveEntity's validation and
// publish-gate checks both `flashFail()` — a redirect — before the INSERT, so the other two
// tests here can never persist one even on failure). That one test's own delete step is the
// real cleanup; this is the backstop for when it doesn't get there — found live, once, as a
// leftover "QA FAQ …" row surfacing as a permanent false "12/13 incomplete" on the real
// dashboard after an earlier run was killed mid-test during concurrency debugging. DB-direct,
// not through the UI, so it cleans up regardless of what state a failed run left the browser in.
test.afterEach(async ({}, testInfo) => {
  test.skip(testInfo.project.name !== 'mobile-390');
  execFileSync('php', ['-r', `
    require '${repoRoot}/app/bootstrap.php';
    use Nm\\Db;
    $ids = array_column(Db::all("SELECT faq_id FROM faq_i18n WHERE lang='en' AND (question LIKE 'QA FAQ %' OR question LIKE 'Incomplete publish test%' OR question LIKE 'Data-loss regression %')"), 'faq_id');
    foreach (array_unique($ids) as $id) { Db::run('DELETE FROM faqs WHERE id=?', [(int) $id]); Db::run('DELETE FROM faq_i18n WHERE faq_id=?', [(int) $id]); }
  `], { cwd: repoRoot });
});

test.describe('admin CRUD — FAQs', () => {
  test.beforeEach(async ({ page }, testInfo) => {
    test.skip(testInfo.project.name !== 'mobile-390', 'CRUD flow is desktop-identical logic; one project is enough, run at the phone width the client will actually use (docs/06: "client will edit from mobile")');
    await loginAsAdmin(page);
  });

  test('create -> appears in list -> edit -> delete', async ({ page }) => {
    const marker = `QA FAQ ${Date.now()}`;
    await page.goto(`${ADMIN}/faqs/new`);
    await page.fill('input[name="i18n[en][question]"]', marker);
    await page.fill('textarea[name="i18n[en][answer]"]', 'Answer written by the Playwright admin-crud suite.');
    await page.click('button[type="submit"]');
    // PRG: a successful save redirects to /faqs/{id}?saved=1 (Admin::saveEntity). Waiting on
    // the URL itself, not a generic waitForLoadState(), is what actually confirms that specific
    // navigation happened rather than racing the click's own in-flight request.
    await page.waitForURL(/\/faqs\/\d+/);

    await page.goto(`${ADMIN}/faqs`);
    await expect(page.getByText(marker)).toBeVisible();

    // edit
    const row = page.locator('tr', { has: page.getByText(marker) });
    await row.getByRole('link', { name: 'Edit' }).click();
    await page.waitForLoadState();
    await expect(page.locator('input[name="i18n[en][question]"]')).toHaveValue(marker);
    const updated = `${marker} (edited)`;
    await page.fill('input[name="i18n[en][question]"]', updated);
    await page.click('button[type="submit"]');
    await page.waitForURL(/\/faqs\/\d+/);

    await page.goto(`${ADMIN}/faqs`);
    await expect(page.getByText(updated)).toBeVisible();

    // delete
    const editedRow = page.locator('tr', { has: page.getByText(updated) });
    page.once('dialog', (d) => d.accept());
    await editedRow.getByRole('button', { name: 'Delete' }).click();
    await page.waitForLoadState();
    await expect(page.getByText(updated)).toHaveCount(0);
  });

  test('publish gate blocks an incomplete-translation record with a visible reason', async ({ page }) => {
    await page.goto(`${ADMIN}/faqs/new`);
    await page.fill('input[name="i18n[en][question]"]', 'Incomplete publish test');
    // answer left blank on purpose — required field, should block publish
    const publishToggle = page.locator('input[name="is_published"]');
    if (await publishToggle.count()) await publishToggle.check();
    await page.click('button[type="submit"]');
    await page.waitForLoadState();
    await expect(page.locator('p.adm-toast.err')).toBeVisible();
    await expect(page.locator('p.adm-toast.err')).toContainText(/answer/i);
  });

  test('a failed save never loses what was typed (admin form-flash fix)', async ({ page }) => {
    await page.goto(`${ADMIN}/faqs/new`);
    const question = `Data-loss regression ${Date.now()}`;
    const answer = 'This text must survive a validation failure and redisplay untouched.';
    await page.fill('input[name="i18n[en][question]"]', question);
    await page.fill('textarea[name="i18n[en][answer]"]', answer);
    // Force the *server-side* validation failure (Validator's `in:` rule on group_code — the
    // only rule any FAQ top-level field can actually fail; `int`/`bool` only cast, never
    // reject). A real <select> can't be driven to an out-of-list value through the UI, so this
    // simulates a tampered/stale request the way a defense-in-depth check should be exercised.
    await page.locator('select[name="group_code"]').evaluate((el) => {
      const opt = document.createElement('option');
      opt.value = 'not-a-real-group';
      el.appendChild(opt);
      el.value = 'not-a-real-group';
      el.dispatchEvent(new Event('change', { bubbles: true }));
    });
    await page.click('button[type="submit"]');
    await page.waitForLoadState();
    // the exact defect this regression-locks: before the fix, saveEntity's error path was a
    // bare GET redirect that re-rendered the form from the database, silently discarding
    // everything just typed and showing no error at all.
    await expect(page.locator('input[name="i18n[en][question]"]')).toHaveValue(question);
    await expect(page.locator('textarea[name="i18n[en][answer]"]')).toHaveValue(answer);
    await expect(page.locator('p.adm-toast.err')).toBeVisible();
  });
});
