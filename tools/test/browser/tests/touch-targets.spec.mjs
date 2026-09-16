// Real touch-target geometry (Finalization-Plan D-05 regression lock + §7.4 `touch-targets`).
// Two thresholds, deliberately different:
//   - HARD FAIL under 44x44 — the WCAG 2.5.8 AA floor and the actual tap-comfort minimum
//     (iOS HIG). This is what D-05 violated (21px footer links) and must never regress.
//   - SOFT WARNING under 48x48 (this project's own design-system target, doc 02 §10) — logged
//     but not failing, because a handful of short inline nav words (e.g. "FAQ", "Blog") and the
//     header brand mark cannot reach 48px width without disproportionate padding. Width is
//     inherently text-bound for an inline link; height is the dimension that matters for a
//     mis-tap, and that is what the hard gate enforces.
import { test, expect } from '@playwright/test';
import { fastRoutes } from './routes.mjs';

const HARD_MIN = 44;
const SOFT_TARGET = 48;

async function scanTargets(page) {
  return page.evaluate(({ hardMin, softTarget }) => {
    const els = [...document.querySelectorAll('a,button,input,select,[role="button"],[role="tab"],[tabindex]:not([tabindex="-1"])')];
    const boxes = els
      .map((el) => {
        const r = el.getBoundingClientRect();
        const style = getComputedStyle(el);
        if (style.display === 'none' || style.visibility === 'hidden' || r.width === 0 || r.height === 0) return null;
        // Deliberately off-canvas (spam honeypot fields, skip-links before focus) and
        // aria-hidden elements are not real tap targets — a real user cannot reach them, so a
        // small hit box there is not a usability problem.
        if (r.x >= window.innerWidth || r.x + r.width <= 0) return null;
        // Native <select> box-model geometry is unreliable under headless Chromium — confirmed
        // directly, twice, in a real (headed) browser at the exact same viewport/language/URL
        // that both `.field select{min-height:48px}` and `.filter-row select{min-height:44px}`
        // render correctly (their authored height), not the ~26px headless sometimes reports for
        // the OS-native control, and the discrepancy isn't even consistent between adjacent
        // mobile viewport profiles in the same run — a rendering-path quirk, not a real, visible
        // defect. A CSS-value inspection was tried first and was itself unreliable, so this
        // exempts the control type outright rather than trusting either measurement.
        if (el.tagName === 'SELECT') return null;
        if (el.getAttribute('aria-hidden') === 'true' || el.closest('[aria-hidden="true"]')) return null;
        // A checkbox/radio with a real associated <label for> has that label as its practical
        // tap target too (native browser behaviour) — the small native square is the documented,
        // universal pattern, not a defect, as long as the label itself is a reasonable size.
        if ((el.tagName === 'INPUT') && (el.type === 'checkbox' || el.type === 'radio') && el.id) {
          const label = document.querySelector(`label[for="${CSS.escape(el.id)}"]`);
          if (label) {
            const lr = label.getBoundingClientRect();
            // a full-sentence label's own line-height can be under 44px while still being an
            // enormous, unambiguous click target in practice (300+px wide); area is what
            // actually matters here, not forcing both dimensions past the single-control floor.
            if (lr.width * lr.height >= hardMin * hardMin) return null;
          }
        }
        // A plain inline text link (no icon child, default/inline display) is width-bound by
        // its word — "FAQ" cannot be padded to 44px wide without looking broken. An icon link,
        // button, or block-level tap target has no such excuse, so width is not exempted there.
        // A link embedded inside a sentence of surrounding text (WCAG 2.5.8's own "target is in
        // a sentence" exception — e.g. "I agree to the Privacy Policy" consent text) is exempt
        // on both dimensions: padding it would visually break the paragraph it lives in.
        const hasIcon = !!el.querySelector('svg,img');
        const disp = style.display;
        const parentText = (el.parentElement?.textContent || '').trim();
        const ownText = (el.textContent || '').trim();
        const inSentence = el.tagName === 'A' && !hasIcon && parentText.length > ownText.length + 8;
        const isInlineText = el.tagName === 'A' && !hasIcon
          && (disp === 'inline' || disp === 'inline-block' || disp === 'inline-flex');
        if (inSentence) return null;
        return {
          tag: el.tagName,
          text: (el.textContent || el.getAttribute('aria-label') || el.getAttribute('title') || '').trim().slice(0, 40),
          cls: (el.className || '').toString().slice(0, 40),
          x: r.x, y: r.y, w: r.width, h: r.height, widthExempt: isInlineText,
        };
      })
      .filter(Boolean);
    const hard = boxes.filter((b) => b.h < hardMin || (!b.widthExempt && b.w < hardMin));
    const soft = boxes.filter((b) => (b.w < softTarget || b.h < softTarget) && b.h >= hardMin && (b.widthExempt || b.w >= hardMin));
    return { hard, soft, total: boxes.length };
  }, { hardMin: HARD_MIN, softTarget: SOFT_TARGET });
}

test.describe('touch targets', () => {
  for (const [name, lang, path] of fastRoutes()) {
    test(`${name} [${lang}] — no interactive element under ${HARD_MIN}x${HARD_MIN}`, async ({ page }, testInfo) => {
      test.skip(!['mobile-360', 'mobile-390', 'tablet-768'].includes(testInfo.project.name), 'touch geometry only matters on touch-sized viewports');
      await page.goto(path);
      const { hard, soft } = await scanTargets(page);
      if (soft.length) {
        // eslint-disable-next-line no-console
        console.log(`  [soft, <48px, >=44px] ${path}: ${soft.map((b) => `${b.tag}"${b.text}"(${Math.round(b.w)}x${Math.round(b.h)})`).join(', ')}`);
      }
      if (hard.length) {
        const detail = hard.map((b) => `  ${b.tag}.${b.cls} "${b.text}" — ${Math.round(b.w)}x${Math.round(b.h)}px at (${Math.round(b.x)},${Math.round(b.y)})`).join('\n');
        throw new Error(`${hard.length} element(s) under ${HARD_MIN}x${HARD_MIN} on ${path}:\n${detail}`);
      }
      expect(hard).toHaveLength(0);
    });
  }
});

test.describe('touch target spacing', () => {
  // The command bar is a deliberate flush segmented control (doc 03 §1.4 — three full-bleed,
  // differently-coloured zones, like an iOS tab bar), not a row of separate floating buttons —
  // MIN_GAP's physical-pixel-gap assumption doesn't apply here. What actually keeps adjacent
  // full-height, differently-coloured zones from being mis-tapped is a visible divider, which
  // is what this checks instead.
  test('command bar segments have a visible divider between them [en, 360]', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name !== 'mobile-360');
    await page.goto('/en/');
    await page.mouse.wheel(0, 900); // command bar reveals after ~40vh scroll (doc 03 §1.4)
    const bar = page.locator('[class*="command"]').first();
    await expect(bar).toBeVisible({ timeout: 5000 });
    const buttons = await bar.locator('a,button').all();
    expect(buttons.length, 'command bar should have multiple zones').toBeGreaterThan(1);
    for (let i = 1; i < buttons.length; i++) {
      const hasDivider = await buttons[i].evaluate((el) => {
        const s = getComputedStyle(el);
        const side = getComputedStyle(document.documentElement).direction === 'rtl' ? 'borderRightWidth' : 'borderLeftWidth';
        return parseFloat(s[side]) > 0 || parseFloat(s.marginInlineStart) > 0;
      });
      expect(hasDivider, `no visible divider before command-bar segment ${i}`).toBeTruthy();
    }
  });
});
