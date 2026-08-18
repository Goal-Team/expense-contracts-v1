# Attach Chrome DevTools and capture the dashboard's real response

Type: task
Status: resolved
Blocked by: —

## Question

Nothing about the browser side of this page is verified, because the app is behind a login the agent
cannot pass. `.mcp.json` already declares `chrome-devtools-mcp@1.7.0` attaching to
`http://127.0.0.1:9222`.

**How to get CDP up (established 2026-08-14 — do not rediscover this).** Installed Chrome is 151, and
**since Chrome 136 `--remote-debugging-port` is silently ignored when Chrome runs on its default
profile directory.** The flag appears to work, Chrome starts normally, and nothing listens on 9222.
A dedicated `--user-data-dir` is mandatory:

```powershell
Start-Process "C:\Program Files\Google\Chrome\Application\chrome.exe" -ArgumentList `
  "--remote-debugging-port=9222", `
  "--user-data-dir=C:\Users\karun\.claude\chrome-debug-profile", `
  "--no-first-run","--no-default-browser-check", `
  "http://apollo.contracts.legality:8888/contracts/"
```

Chrome must be fully closed first. Verify with `http://127.0.0.1:9222/json/version`, and list page
targets with `http://127.0.0.1:9222/json/list`.

Consequence: that profile starts with **no session**, so the dev logs into the contracts app once in
that window. The profile directory persists, so subsequent launches are already authenticated. Confirm
the tab is on the dashboard and not redirected to `apollo.contracts.legality:8888/` (the legacy login
app) before measuring.

The Claude Code session must be restarted after `.mcp.json` was added for the MCP tools to load. If a
restart is undesirable, CDP can be driven directly over the WebSocket with Node 22 (present on PATH),
but the MCP's performance-trace tooling is the reason it was chosen — prefer it.

Then capture, on the logged-in dashboard at `http://apollo.contracts.legality:8888/contracts/`:

1. **The rendered `<head>`** — specifically, does the `<link>` for `core` point at
   `http://[::1]:5173/resources/assets/vendor/scss/core.scss` (dead dev server) or at
   `build/assets/core-*.css` (precompiled)? This settles whether "core.css takes too long to compile"
   is a compilation cost at all. Note the app is flattened, so which of `hot` vs `public/hot` Laravel
   actually reads is itself unverified.
2. **The full network waterfall** — TTFB on the document, then every asset with its status and
   duration. Flag anything failing, timing out, or pointing at `[::1]`.
3. **Total HTML response size**, and within it the byte cost of the two inline `<option>` loops
   (contract types at [viewDashboard1.blade.php:314](../../../Modules/Contract/resources/views/dashboard/viewDashboard1.blade.php:314),
   branches at [:323](../../../Modules/Contract/resources/views/dashboard/viewDashboard1.blade.php:323)).
   This is the number that tells us whether the AJAX conversion is a performance fix or an
   architecture cleanup.
4. **A performance trace** — main-thread breakdown and LCP, so we know how much of the wall-clock is
   server time versus client rendering.

Also do the same capture for `contractList`, which carries four inline dropdowns rather than two.

## Answer

Measured 2026-08-14 over CDP directly (Node 22, no MCP needed — see
[measurements/cdp-measure.mjs](../measurements/cdp-measure.mjs), raw JSON alongside it). Cache
disabled. Local DB at N=18 contracts.

### Dashboard — `http://apollo.contracts.legality:8888/contracts/`

| | run 1 | run 2 | run 3 | run 4 |
|---|---|---|---|---|
| wall clock to load + settle | 20,861 ms | 21,530 ms | 20,560 ms | 20,681 ms |
| **document TTFB** | 2,074 ms | 2,679 ms | 1,882 ms | 1,945 ms |
| requests failed | 28/31 | 28/31 | 28/31 | 28/31 |

HTML 70,254 bytes raw (71,668 encoded). Total transferred 120,869 bytes.

### Contract list — `http://apollo.contracts.legality:8888/contracts/contracts/list`

Wall clock 24,698 ms; TTFB 2,095 ms; HTML 65,335 bytes; **34 of 37 requests failed**.

### 1. Rendered `<head>` — the dead dev server is confirmed

`hasDeadViteHost: true`. **26 requests to `http://[::1]:5173/...` all fail with
`ERR_CONNECTION_REFUSED`**, each burning 2,058–6,116 ms, and they serialise. Captured head at
[measurements/dashboard-head.html](../measurements/dashboard-head.html). Refused entrypoints include
`@vite/client`, `rtl/core.scss`, `rtl/theme-default.scss`, `demo.css`, `tabler-icons.scss`,
`fontawesome.scss`, `flag-icons.scss`, `node-waves.scss`, `perfect-scrollbar.scss`.

**Only 3 of 31 requests succeed.** The page is running essentially unstyled.

Two incidental findings:

- The **RTL** stylesheet variant is being requested (`resources/assets/vendor/scss/rtl/core.scss`), so
  `$configData['rtlSupport']` resolves to `rtl`. Whether that is intended is unknown — folded into
  `07-asset-pipeline-decision`.
- `contracts/assets/fonts/font-main.css` fails with `ERR_ABORTED` (~0.9–1.3 s) and is **not** a Vite
  URL — a separately broken local asset.

### 2. Waterfall — the 20 s is the failed requests, not the server

`OnTrackLogo.png` takes **14,397 ms with a 7 ms TTFB**, and `dashboard.js` takes 14,384 ms with a
102 ms TTFB. Both are fast to first byte and then stall — they are queued behind the wall of refused
connections. Nothing server-side explains those numbers.

**So wall-clock and TTFB are two independent problems.** Roughly 18 s of the ~21 s is the dead Vite
host; deleting `public/hot` should recover nearly all of it. That does nothing for TTFB.

### 3. Inline dropdown accounting — the dev's instinct was directionally right

| Page | `<option>` tags | option bytes | **% of HTML** |
|---|---|---|---|
| Dashboard | 136 | 10,761 | **15.3 %** |
| Contract list | 163 | 13,306 | **20.4 %** |

Dashboard selects: `contracttype` 73 options / 9,239 B; a second unnamed `select2` 63 options /
6,080 B. Contract list has five, the largest three being 73 / 63 / 14 options.

Higher than the ~172-tags-few-KB estimate made while charting — 15–20 % of the response body is
`<option>` markup. But 10–13 KB cannot cost 2 s of TTFB. The real value of the AJAX conversion is
**taking the queries that build these lists off the critical path** — the `BranchUser` select with 11
`AES_DECRYPT` columns and `ContractType::get()` — plus a 15–20 % payload cut. Genuine, not the leading
cause.

### 4. Performance trace

Superseded by the finding above: with 28 of 31 requests failing, a main-thread trace measures a broken
page. Re-trace after `07-asset-pipeline-decision` lands, when the page actually renders.

### The headline

**TTFB is already 1.9–2.7 s at N=18** — the smallest dataset this schema can hold. The agreed target is
under 2 s total, so the server is at or past budget before a single realistic row exists, and the
`~10 + 4·N` query pattern has barely engaged. This is the number that matters, and
`05-baseline-attribution` now has to explain where 2 s goes when there are 18 rows to count.
