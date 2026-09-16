# Browser test layer (Playwright + axe-core)

The gates `tools/test/unit.php` and `tools/test/e2e.mjs` cannot reach because they need a real
rendering engine: WCAG accessibility (axe-core), real touch-target geometry, real horizontal
overflow, RTL visual mirroring, browser-measured performance budgets, and the dashboard's actual
click-through flows. See `Finalization-Plan.md` §7.4/§9 for the gates this fulfils.

Dev-only. Nothing here is deployed — same pledge as `tools/harness/` (doc 04).

## Setup (once)

```bash
cd tools/test/browser
npm install
npx playwright install chromium webkit firefox   # ~2GB, one-time
```

## Run

The site must be built (`bash tools/build.sh` from the repo root) before testing against it.

```bash
npm test                              # everything, auto-starts tools/dev_server.php on :8080
npx playwright test --project=mobile-360
npx playwright test tests/a11y.spec.mjs
npx playwright show-report            # after a run, opens the HTML report
```

Point at an already-running server (e.g. `node tools/serve.mjs`, which has full `.htaccess`
header parity that `dev_server.php` does not):

```bash
BASE_URL=http://127.0.0.1:8080 npm test
```

## What's here

| File | Gate |
|---|---|
| `tests/a11y.spec.mjs` | axe-core WCAG 2.1 AA, 0 critical/serious, every template × language + a full keyboard journey |
| `tests/touch-targets.spec.mjs` | D-05 regression lock: hard-fail under 44×44 (WCAG 2.5.8 floor), soft-warn under 48×48 (this project's own design-system target) |
| `tests/overflow.spec.mjs` | D-06 regression lock: zero horizontal overflow at the 360px contract floor, distinguishing intentional horizontal-scroll containers (topbar, tab pills) from genuine layout breakage |
| `tests/rtl.spec.mjs` | dir/lang correctness, mirrored chevrons, LTR-preserved phone/temperature values, D-02 Arabic-leak regression lock, + committed visual-snapshot baselines |
| `tests/perf.spec.mjs` | third-party requests = 0, transfer-size budgets, LCP/CLS sanity (D-03 regression lock — the empty hero's LCP candidate was a late-painting text node) |
| `tests/cross-engine.spec.mjs` | WebKit + Firefox smoke — the site isn't Chromium-only |
| `tests/admin-auth.spec.mjs` | D-01 regression lock in a real browser session: login loads with no fatal error, wrong credentials handled, logged-out redirect |
| `tests/admin-crud.spec.mjs` | Full FAQ create → edit → delete through the real UI; the publish-completeness gate; and a regression lock for the form-flash fix (a failed save must never lose what was typed — it did, before `Admin::flashFail`) |
| `global-setup.mjs` | Provisions one already-rotated admin fixture account via the app's own `password_hash()` (not a Node driver) so tests don't burn the real seeded owner account |

## RTL visual baselines

`tests/rtl.spec.mjs`'s snapshot tests compare against committed PNGs in
`tests/rtl.spec.mjs-snapshots/`. After a **deliberate** visual change, regenerate and review the
diff before committing:

```bash
npx playwright test tests/rtl.spec.mjs --update-snapshots
git diff --stat tests/rtl.spec.mjs-snapshots/
```

## CI

`.github/workflows/ci.yml` has a `browser` job that installs, builds the site fresh, and runs the
full suite on `ubuntu-latest`. It's slower than the `unit`/`lint` jobs (browser downloads +
image pipeline), so it's scoped to `mobile-360` in CI by default — the full 6-project matrix is
for local runs before a release.
