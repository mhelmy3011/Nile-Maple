// Zero horizontal overflow at the 360px contract floor (Finalization-Plan D-06 regression lock).
// A naive `scrollWidth > innerWidth` check would false-negative on this codebase because the
// topbar and category-tab pills are *intentionally* horizontally scrollable (doc 03 §1.1/§2) —
// their children legitimately extend past the viewport inside their own overflow-x:auto
// container. So this asserts two things instead: (1) the document itself never scrolls
// sideways, and (2) anything overflowing the viewport is contained by a designated
// horizontal-scroll ancestor, not floating loose (which is what clipped the phone number in D-06).
import { test, expect } from '@playwright/test';
import { allRoutes } from './routes.mjs';

test.describe('overflow (360px contract floor)', () => {
  for (const [name, lang, path] of allRoutes()) {
    test(`${name} [${lang}] — no uncontained horizontal overflow`, async ({ page }, testInfo) => {
      test.skip(testInfo.project.name !== 'mobile-360', 'the 360px floor is the contract; other widths are strictly wider');
      await page.goto(path);

      const docOverflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
      expect(docOverflow, `document.scrollWidth exceeds viewport by ${docOverflow}px on ${path}`).toBeLessThanOrEqual(1);

      const rogue = await page.evaluate(() => {
        const w = window.innerWidth;
        const scrollAncestor = (el) => {
          let n = el.parentElement;
          while (n) {
            const s = getComputedStyle(n);
            if (/(auto|scroll)/.test(s.overflowX)) return true;
            n = n.parentElement;
          }
          return false;
        };
        const offenders = [];
        document.querySelectorAll('body *').forEach((el) => {
          const r = el.getBoundingClientRect();
          if (r.width === 0 || r.height === 0) return;
          // deliberately off-canvas (e.g. .skip-link's inset-inline-start:-9999px — logical
          // properties correctly flip that to the right edge in RTL) is not "overflow": it
          // starts past the viewport on purpose. Genuine overflow starts on/before the edge
          // and spills past it.
          if (r.left >= w) return;
          if (r.right > w + 1 && !scrollAncestor(el)) {
            offenders.push(`${el.tagName}.${String(el.className).slice(0, 30)} right=${Math.round(r.right)}`);
          }
        });
        return [...new Set(offenders)];
      });
      expect(rogue, `element(s) overflow the viewport without a scrollable ancestor on ${path}`).toEqual([]);
    });
  }
});
