// Dashboard auth surface (Finalization-Plan §7.4 `admin-auth`, D-01 regression lock in the
// browser). tools/test/e2e.mjs already covers the HTTP status codes; this drives real Chromium
// through the login form to catch anything only a real DOM/session/cookie flow would show.
import { test, expect } from '@playwright/test';
import { TEST_ADMIN } from '../global-setup.mjs';

const ADMIN = '/manage';

test.describe('admin auth', () => {
  test('login page loads with no fatal error (D-01 regression lock)', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name !== 'mobile-390', 'one project is enough for a pure auth flow');
    const res = await page.goto(`${ADMIN}/login`);
    expect(res.status()).toBe(200);
    await expect(page.getByText(/fatal error/i)).toHaveCount(0);
    await expect(page.locator('input[name="email"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
  });

  test('wrong password is rejected with a visible error, not a crash', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name !== 'mobile-390');
    await page.goto(`${ADMIN}/login`);
    await page.fill('input[name="email"]', TEST_ADMIN.email);
    await page.fill('input[name="password"]', 'definitely-wrong');
    await page.click('button[type="submit"]');
    await page.waitForLoadState();
    expect(page.url()).toContain('/login');
    await expect(page.getByText(/fatal error/i)).toHaveCount(0);
  });

  test('valid credentials reach the dashboard', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name !== 'mobile-390');
    await page.goto(`${ADMIN}/login`);
    await page.fill('input[name="email"]', TEST_ADMIN.email);
    await page.fill('input[name="password"]', TEST_ADMIN.password);
    await page.click('button[type="submit"]');
    await page.waitForLoadState();
    expect(page.url()).not.toContain('/login');
    await expect(page.getByText(/fatal error/i)).toHaveCount(0);
    // Regression lock: the bare dashboard root (what login actually redirects to) 404'd under
    // dev_server.php specifically — e2e.mjs only ever checked this route logged OUT (expects
    // the auth redirect), so the authenticated path was never exercised anywhere until this.
    // Root cause: Admin::handle()'s REQUEST_URI-parsing fallback mishandled the bare "manage"
    // segment (no $_GET['path'] set), landing on a $page value with no matching pgXxx method.
    const res = await page.goto(`${ADMIN}/`);
    expect(res.status(), 'authenticated dashboard root').toBe(200);
    await expect(page.getByText(/not found/i)).toHaveCount(0);
  });

  test('direct access to an entity list while logged out redirects to login', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name !== 'mobile-390');
    await page.context().clearCookies();
    await page.goto(`${ADMIN}/categories`);
    await page.waitForLoadState();
    expect(page.url()).toContain('/login');
  });

  test('login form is keyboard-operable and touch targets meet 44px on mobile', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name !== 'mobile-360');
    await page.goto(`${ADMIN}/login`);
    const submit = page.locator('button[type="submit"]');
    const box = await submit.boundingBox();
    expect(box.height, 'login submit button height').toBeGreaterThanOrEqual(44);
    await page.keyboard.press('Tab'); // email
    await expect(page.locator('input[name="email"]')).toBeFocused();
    await page.keyboard.press('Tab'); // password
    await expect(page.locator('input[name="password"]')).toBeFocused();
  });
});
