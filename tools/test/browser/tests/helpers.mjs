import { TEST_ADMIN } from '../global-setup.mjs';

export const ADMIN = '/manage';

/** Logs the fixture admin in through the real form (not a cookie hack) so every admin test
    exercises the genuine session/CSRF flow. */
export async function loginAsAdmin(page) {
  await page.goto(`${ADMIN}/login`);
  await page.fill('input[name="email"]', TEST_ADMIN.email);
  await page.fill('input[name="password"]', TEST_ADMIN.password);
  await page.click('button[type="submit"]');
  await page.waitForLoadState();
}
