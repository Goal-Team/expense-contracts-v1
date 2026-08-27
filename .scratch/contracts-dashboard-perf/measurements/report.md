# Measurement report — contracts dashboard

One file for every change. Old number, new number, side by side. Never start a second report file.

**How to fill a row.** Measure the old function, then the new one, on the **same page, same data,
same session** — absolute milliseconds on this machine vary about 3× between sessions, so an old and
a new number measured hours apart mean nothing. Prefer query counts; they do not drift.

**Every row also records page weight, not only time** (dev's call, 2026-08-20). Three numbers, because
they answer different questions: **document bytes** — what the server rendered; **total transfer bytes**
and **request count** — what the browser actually pulled down. Do not report one as if it were the other:
the document here is ~60–70 KB and the whole page is ~5 MB, so a change that halves the document moves the
whole-page number by about 1 %. See [ticket 21](../issues/21-page-weight-measurement.md).

Dataset for every row unless the row says otherwise: seeded local set — **3,018 contracts**, 6,940
party rows, 13,867 approval rows ([ticket 04](../issues/04-seed-realistic-dataset.md)).

**Take every row with the debug bar OFF.** Debugbar was installed 2026-08-21
([ticket 16](../issues/16-debug-tooling-decision.md)) and it inflates the dashboard document from
**63,274 bytes to 359,490** - 5.7x - plus two extra asset requests. Set `DEBUGBAR_ENABLED=false` in `.env`
before measuring anything, or the numbers are meaningless.

## Results

| # | change | old function / state | new function / state | old | new | measure | side effects / remarks |
|---|---|---|---|---|---|---|---|
| 0 | baseline (no change) | `dashDetails` | — | 14,437 ms TTFB / 5,654 queries | — | timing middleware | Reference row. N=18 was 2,084–2,573 ms / 164 queries. |
| 1 | Change E part 1 — `public/hot` deleted, RTL off | dead Vite dev server, 26 refused asset requests | assets served from `public/build` | 28 of 31 browser requests failed ([ticket 01](../issues/01-attach-chrome-devtools.md)) | **0 of 53 fail on status**, 1 wrong MIME | DevTools, measured 2026-08-20 | The `hot` file was **already gone** when the work started — removed earlier the same day, and `.gitignore` hides it from git, so there is no record of by whom. Only `config/custom.php:13` was changed in this step. The one remaining failure is `assets/fonts/font-main.css`, which is not a Vite URL and needs its own fix. There is **no RTL cookie** — the ticket was wrong on that. The flag does live in `localStorage` (`templateCustomizer-*--Rtl`), so a user who once toggled RTL gets `dir="rtl"` markup against an LTR stylesheet until they clear it. |
| 2 | Change A — query layer | `dashDetails` | `dashboardSummary` | **4,827–4,974 ms** controller | **36 ms** controller | timing middleware, same session 22:00–22:01 | Counter-off comparison, so this row is Change A alone. All 15 stage counters and all 4 task counters **identical**. One cosmetic difference: the sidebar highlights the active item by URL, so nothing is highlighted on `/dashboard-summary`. It goes away when the new route takes over the old URL at step 11. |
| 2a | Change A — queries | `dashDetails` | `dashboardSummary` | **5,654** queries | **127** queries | timing middleware | 92 of the 127 are the menu composer (`View::composer('*')`), which is [ticket 11](../issues/11-per-request-overhead.md) and not this page's. The dashboard's own share is single digits. |
| 2b | Change A — whole request | `/` | `/dashboard-summary` | **5,434–5,680 ms** TTFB | **671 ms** TTFB | timing middleware | Bootstrap is ~400 ms of the 671 and is untouched by this spec. |
| 2c | Change A — browser wall clock | `/` | `/dashboard-summary` | **6,353 ms** to `load` | **1,887 ms** to `load` | DevTools, same session | Full page load, 53 vs 54 resources, actionable counter **on** for the new number. |
| 3 | Actionable-items counter | blade loop over `$approvalsArr` (silently zero) | `actionableApprovalRows` + `actionableItemCounts` | 36 ms controller / 127 queries (counter off) | **372 ms** controller / 134 queries | timing middleware, same session | The decrypt costs **~336 ms** for 11,527 decrypted values over 11,427 approval groups. Cheaper than the ~490 ms measured in [ticket 17](../issues/17-plain-columns-experiment.md) because `username` is only decrypted for rows whose `approval_status` already came back `pending`. The 7 extra queries are the chunked read (2,000 rows a chunk). **The numbers change on purpose**: "My Actionable Items" was `Total (0)` and is now `Total (1)` — the 1,000-id bug was hiding it. This is the one expected difference. |
| 4 | Migration — index `approval_contracts.contract_id` | no index, `PRIMARY` only | `idx_approval_contracts_contract_id` | **114 ms** | **23 ms** | `SHOW PROFILES`, same session, `IGNORE INDEX` for the old number | **Applied 2026-08-21** by the dev's go-ahead. Query is the dashboard's approvals join, `approval_contracts JOIN contracts ON c.id = a.contract_id`, 13,867 rows. **5× faster.** Old and new in the same session, `IGNORE INDEX (...)` standing in for "before", so the 3× between-session ms drift does not apply. Page weight unaffected — schema only. Stayed a migration after [ticket 20](../issues/20-migration-portability.md): it names no charset and no collation. Built online, no lock. |
| 5 | Change F — AJAX dropdowns (payload) | 136 `<option>` tags in the HTML | `contracts/option-lists` + `option-lists-js` partial | **71,294 bytes**, 136 options | **61,064 bytes**, 0 options | DevTools fetch of the document | **14.3 % smaller**, in line with the predicted ~15 %. Both selects fill from one request and end `data-option-state="ready"` with 73 and 63 options. |
| 5a | Change F — queries off the critical path | `dashDetails` built `$branchs` (11 `AES_DECRYPT` columns) and `$contractTypes` | endpoint builds them, cached 10 min | 2 queries on the page | 0 on the page | timing middleware | The endpoint itself: 19–31 ms controller, 11–15 queries, 541–587 ms total — and it is off the document's critical path. Cache is keyed on session identity because `BranchScope` filters by it. Neither `branch` nor `contract_type` has an `updated_at`, so `MAX(id)` stands in: added/removed rows bust the cache at once, a rename waits out the 10 minutes. |
| 6 | Change E part 2 — root `vite.config` | no config anywhere; `npm run build` impossible | committed `vite.config.mjs` | build fails | **build succeeds**, 1 m 34 s, 1,049 manifest entries | `npx vite build` to a throwaway outDir | No performance effect, as the spec says — it is what makes stylesheet changes possible at all. Parity with the old manifest: 1,049 entries both sides, nothing lost, one file added. **`.js` cannot work**: `laravel-vite-plugin@1.0.1` is ESM-only and `package.json` has no `"type": "module"`, so the config is `.mjs` and `.gitignore`'s blanket `*.mjs` was narrowed to `/vite.config.*.timestamp-*.mjs`. `manifest: 'manifest.json'` is set explicitly — plain `true` writes to `.vite/` where Laravel never looks. |
| 7 | ~~Migration~~ **artisan command** — convert `contract_party_data` | MyISAM / latin1, `TEXT` join columns, `PRIMARY` only | InnoDB / utf8mb4_unicode_ci, `varchar(32)`, 2 indexes | **42 ms** | **32 ms** | `SHOW PROFILES`, same session, `IGNORE INDEX` for the old number | **Applied 2026-08-21** via `php artisan contract:convert-party-data --apply`. 6,940 rows in, **6,940 out**; backup at [contract_party_data-before-convert.sql](contract_party_data-before-convert.sql). Query is the internal-party join, `contract_party_data JOIN contracts ON c.id = p.custom_field_group_id WHERE contract_party_type = 'Internal'`. Only **1.3×** — much smaller than the approvals index, as ticket 09 predicted (it said no index on this table was needed for speed; the conversion is about the collation boundary and being indexable at all). **Case-insensitivity confirmed after the change**: `'internal'` and `'Internal'` both match 3,920 rows, so [ContractController.php:725](../../../Modules/Contract/app/Http/Controllers/ContractController.php:725) and [:4358](../../../Modules/Contract/app/Http/Controllers/ContractController.php:4358) still work. **One side effect: `party_address` went `TEXT` -> `MEDIUMTEXT`** — that is `CONVERT TO` keeping the column's capacity in characters the same as bytes-per-character goes 1 -> 4. A widening, nothing lost, no action needed. **No longer a migration** — [ticket 20](../issues/20-migration-portability.md), dev's call: the collation name depends on the client's database, so this is [database/manual/001-...sql](../../../database/manual/001-contract-party-data-innodb-utf8mb4.sql), run by hand at deployment. Width is now **`varchar(32)`**, on those two columns only; `party_address` stays `TEXT`. Real maxima across all 8 client databases are 10 and 3. Confirmed still MyISAM / latin1_swedish_ci with both join columns `TEXT`; real max lengths 10 and 3, so `varchar(20)` truncates nothing. Needs a window — full table rebuild. |
| 8 | [Ticket 17](../issues/17-plain-columns-experiment.md) — `approval_status` plain instead of encrypted | `actionableApprovalRows` + `actionableItemCounts`, column AES-128-CBC `varchar(1000)` | `actionableApprovalRowsx` + `actionableItemCountsx` + `leadingStatusByGroup`, column plain `varchar(20)` + index | **5,043 / 5,402 / 8,820 ms** whole request | **999 / 1,059 / 1,184 ms** whole request | DevTools `fetch` of the document, 3 runs each, same session, debug bar off | **~4.2 s off the page at N=3,018.** Counter-off control in the same session was 632 / 657 / 662 ms, so the counter itself went from ~4.4–4.8 s to ~380 ms — **about 12× cheaper**. The 8,820 ms is one outlier; the other two old runs agree closely. Document bytes **63,267 in every single run**, old and new — no payload change, this row is pure server time. **The six numbers are identical**: `Total (1)`, Review 1, everything else 0, on both URLs, compared as text (`identical: true`). Old and new measured in one session by re-encrypting with `contract:convert-approval-status --apply --down` and converting back, which also proved `--down` works: 13,867 rows out and 13,867 back in, verified both ways. **One trap worth recording:** 61 write sites name `approval_status`, but only **56** write `approval_contracts` - the 5 in ContractPartiesController write `approval_parties`. Four tables here have that column (`approval_contracts`, `approval_parties`, `financial_limit`, `party_approval_rules`), so `PLAINTEXT_COLUMNS` is keyed `table.column`; a bare column name would have turned three unrelated tables plain. **The new counter became the default on 2026-08-21** once these numbers were in; the measuring flag inverted from `?plainApprovalStatus=1` to `?oldApprovalStatus=1`, and the old pair is deleted at spec §10 step 11. |
| 8a | Ticket 17 — values decrypted | one `approval_status` for every row, plus one `username` per pending row | one `username` per pending row, `approval_status` filtered in SQL | **15,988** values, 320–334 ms | **2,127** values, 45–58 ms | PHP probe over the same row set, 3 runs each | **7.5× fewer decryptions.** `username` stays encrypted by the dev's call — it holds JSON `{email,name}` whose name is printed in 13 blade files, so converting it is a much larger job for a few ms. 2,127 is therefore the floor while that holds. Whole-request time fell much further than these 280 ms because the old path also had to fetch and hydrate all 13,861 rows; the new one fetches 2,127 through the index. |
| 8b | Ticket 17 — index | `PRIMARY` + `idx_approval_contracts_contract_id` | `idx_approval_contracts_status_lookup (approval_status, row_status, superseded, contract_id)` | `type=ALL`, 13,861 rows read | **`type=ref`, 2,127 rows read**, `key_len=88` | `EXPLAIN`, after the conversion | Narrowing the column to `varchar(20)` is what makes the index possible at all — `varchar(1000)` in utf8mb4 is 4,000 bytes. Names no charset and no collation ([ticket 20](../issues/20-migration-portability.md)), so it inherited `utf8mb4_unicode_ci` from the table here and will inherit whatever a client server uses. `up()` refuses to run while any ciphertext is left, so it cannot cut values by running out of order. |
| 8c | Ticket 17 — seeder correctness (not a timing) | `PerfDatasetSeeder` wrote `approval_status` = `Pending`/`Approved` and `username` = bare email | writes `pending`/`approved` and `username` = JSON `{email,name}` | 2,100 of 13,740 seeded rows unmatchable, 13,740 usernames unreadable | **0 unmatchable, 0 unreadable** | decrypt-and-count over all 13,867 rows before and after re-seeding | **This invalidates the decrypt figures in row 3.** One `$encStatus` map was shared by `status` and `approval_status`, but they are not the same vocabulary: `status` really is capitalised in this database (`Approved`, `Draft`, `Signing`) while every one of the 61 `approval_status` write sites passes a lowercase word. So the counter's `=== 'pending'` matched nothing on 13,740 rows, and `json_decode($username)->email` returned `''` on all of them. Row 3's "11,527 decrypted values / ~336 ms" was measured against data the application would never write. Re-seeded 2026-08-21; the app code was never wrong here. |

| 9 | Icon fonts requested before any stylesheet is parsed | fonts discovered inside the icon CSS, so requested only after it downloaded | `<link rel="preload" as="font">` for the 3 fonts actually used, at the top of `<head>` | icon font starts **447 ms after** the first resource (2,381 vs 1,934) | **0 ms after** — the 3 fonts are the **first 3 requests on the page**, ahead of the first stylesheet | DevTools resource timing, `startTime` relative to the first resource | New partial [fontPreloads.blade.php](../../../resources/views/layouts/sections/fontPreloads.blade.php), included from [commonMaster.blade.php](../../../resources/views/layouts/commonMaster.blade.php). Measured on a Fast-3G throttled load where the icon CSS itself took **1,066 ms** to arrive — on the old order the font could not even be *discovered* until then, so the worse the network the bigger this is. **This is what "the icons load late and do not swap" was**: the built icon CSS sets no `font-display`, so the browser default applies and blocks — an `<i class="ti ti-file">` renders as *nothing* until the font lands. Icons were invisible from 1,936 ms to 2,787 ms and then appeared. `font-display` is deliberately left alone: `swap` would show a fallback box for every icon and then replace it, which looks worse than a short gap. **Only 3 fonts, checked not guessed** — `document.fonts` reports `tabler-icons`, `Font Awesome 6 Brands` and the `Public Sans` variable as `loaded`, and fa-solid, fa-regular and the second Public Sans face as `unloaded`, so preloading those would have been wasted bandwidth. Global change — every page gets it, and [/contracts/](http://apollo.contracts.legality:8888/contracts/) was checked for no regression. |
| 9a | Option-list fetch actually starting in `<head>` | `@yield('head-prefetch')` sat at the **end** of `<head>`, after 12 stylesheets | moved **above** the stylesheet includes | fetch starts **402 ms after** the first resource (2,336 vs 1,934) | **21–28 ms after** — 4th request on the page | DevTools resource timing, cold and Fast-3G runs | The partial was already correct and already in `<head>` — [ticket 21](../issues/21-page-weight-measurement.md)'s "Change F part 2" row called this done. It was not doing anything, and the reason is not obvious: **Chrome will not execute an inline `<script>` in `<head>` until every stylesheet it has already seen has finished loading**, because the script might ask for a computed style. So the fetch was waiting on 12 stylesheets it does not use. Being "in the head" was never the property that mattered — being *before the stylesheets* is. The comment above the `@yield` now says so, because moving it back down silently undoes this. Selects still end `data-option-state="ready"` with 73 and 63 options. |
| 9b | Requests in parallel — **not fixed, and not fixable in this codebase** | — | — | **http/1.1, one host, 54 requests** | unchanged | `nextHopProtocol` over every resource entry | The dev asked whether the majority of requests can go in parallel. **Not by changing code.** The site is served over **HTTP/1.1** from a single host, and Chrome allows **6 connections per host** on HTTP/1.1 — so 54 requests are 9 waves whatever order they are discovered in. Rows 9 and 9a fix *discovery order*, which is why they help; they cannot lift the connection cap. The fix is **HTTP/2**, which multiplexes every request over one connection. IIS supports it **only over HTTPS**, and this site runs plain HTTP on port 8888, so it needs a certificate and an HTTPS binding first. That is a server change and the dev's call, same category as [ticket 22](../issues/22-reduce-page-size.md)'s IIS gzip and `Cache-Control` findings. The only code-side lever on wave count is **fewer requests**, which is what ticket 22's six cuts already decide. |

| 8 | Change G — cache the menu composer | `View::composer('*')` closure in `MenuServiceProvider` | `App\Menu\MenuDataResolver::resolveForRole()` | **141** queries / 108 of them the menu / 391 ms menu DB time | **33** queries on a cache hit, **40** on a miss / **0** menu queries on a hit | timing middleware, browser reloads 2026-08-21 | **Applied and verified in the browser.** Cache **hit: 33 queries**, and zero `menu_configs` and zero `information_schema.tables` left in the request. Cache **miss: 40** — the predicted 33 + 7 (1 table check, 1 flag read, 2 side-menu lookups, 3 top-menu lookups), so the arithmetic closed exactly. `admin_settings` fell 18 -> 4, confirming 14 of those reads were the composer's. Sidebar verified on screen at 1440px: 22 menu links, Dashboard / Contracts / Setup / Parties / Tasks / Reports all present, counters unchanged (2508 all contracts, 128 expired, 83 pending activation, My Actionable Items Total (1)). At 926px the menu is off-canvas and looks absent — that is the Vuexy breakpoint, not this change. **Invalidation proved end to end:** `MenuDataResolver::flush()` bumped the version 1 -> 2; the model hook on `MenuConfig` bumped it 2 -> 3 on a `save()` of a clean row (no `UPDATE` issued, `updated_at` still 2026-03-28); the next request was a miss at 40 and the one after it a hit at 33. Exactly 2 misses logged against 2 flushes. **ms are indicative only** — the old 141-query readings came from earlier sessions today at 1,783-3,756 ms total, the new ones are 850-944 ms, and this machine drifts about 3x between sessions, so treat the query counts as the real evidence. **Two deviations from the spec, both found while building** and both written into [ticket 23](../issues/23-per-request-query-decision.md): flush is **all roles, not one**, because a role with no row of its own falls back to the `Default` row, so editing `Default` changes answers for roles the write never names; and the flush is a **model hook on `MenuConfig`** rather than calls in the controller's write methods — it catches tinker, seeders and any screen added later, and it is one place to read instead of four. There are **four** write methods in `MenuConfigController`, not the five the spec said. Page weight unchanged, as expected — the menu markup is identical: document **64,720** bytes, 56 requests, whole page **72,336** warm. |
| 9 | Change G part 2 — the composer runs on 2 named views, not `'*'` | `View::composer('*', ...)` — ran **16 times** a request | `View::composer(['layouts.sections.menu.verticalMenu', 'layouts.sections.menu.horizontalMenu'], ...)` — runs **1 time** | 16 composer runs | **1 composer run** | temporary `Log::debug` counting runs, same session 2026-08-21 | **Applied and verified.** The dev asked the fundamental question row 8 never answered: why does the composer run 15 times at all? It does not need to. Only two views read `$menuData`, and they are the only two menu view names in the codebase — every layout, root and all five modules, includes these same two ([contentNavbarLayout.blade.php:39](../../../resources/views/layouts/contentNavbarLayout.blade.php:39), [horizontalLayout.blade.php:54](../../../resources/views/layouts/horizontalLayout.blade.php:54)). With `'*'` the other 14 runs handed a value to a view that never reads it. **Measured directly:** a temporary `Log::debug` in the closure logged **1 run**, on `layouts.sections.menu.verticalMenu`, where the same probe would have logged 16 before. The horizontal view never renders, so its composer never fires. The log line was removed after measuring. **Query count does not move — it is 33 either way**, because row 8's cache had already taken the 15 extra runs down to 0 queries. What this row buys is the 15 redundant closure runs and their ~30 app-cache file reads, which the query counter cannot see. `view_render_ms` read 67 ms against 90–120 ms on the adjacent runs before it; consistent, but one sample and this machine drifts, so it is not offered as the measurement. **The two changes are not interchangeable:** narrowing alone would leave 40 queries (1 run x 7), caching alone leaves 33 but keeps 16 runs; together, 33 queries and 1 run. Sidebar re-verified after the change: 22 links, `Dashboard / Contracts / Setup / Parties / Tasks / Reports`, all counters unchanged. Page weight unchanged — same markup. |
| 10 | Step 11 — the old dashboard deleted, new one on the live URL | `dashDetails()` on `GET ''` and `POST 'filterDash'`, `viewDashboard1.blade.php`, `dashboard:compare-counters` | `dashboardSummary()` on those same two URLs; old method, old view and the compare command deleted | 141 queries / 5,654 at N=3,018 on the old code | **33** queries on `/` | timing middleware + browser, 2026-08-21 | **Applied and verified on the live URL.** `GET ''` and `POST 'filterDash'` now reach `dashboardSummary()`; the temporary `dashboard-summary` pair is gone. URLs are the original ones, so bookmarks and the `menu_configs` `"url": ""` entry keep working untouched. Loaded `/contracts/` in the browser: **33 queries**, sidebar renders, counters unchanged (2508 / 128 / 83 / Total (1)). **A cosmetic gap closed for free:** report row 2 noted nothing was highlighted in the sidebar on `/dashboard-summary`, because the menu highlights by route name and the menu JSON's slug is `contractDashboard`. Now that the route is named `contractDashboard` again, **`Dashboard` is the active item** — confirmed in the DOM. **A latent bug fixed while renaming:** the two old routes both carried the name `contractDashboard`, so `route('contractDashboard')` resolved to whichever Laravel registered last. The POST is now `contractDashboard.filter`, and `viewDashboardSummary.blade.php` points at it. **Deleted:** `dashDetails()` (210 lines), `viewDashboard1.blade.php`, `app/Console/Commands/CompareDashboardCounters.php` (it called `dashDetails()`, and its job was done — it had already reported all 15 stage counters and all 4 task counters identical), and three imports left unused (`ApprovalContracts`, `Tasks`, `ContractType`). **Not deleted, and why is in the notes below the table.** |
| 11 | `Cache-Control` on the built assets | `ETag` only, no `Cache-Control` | `<location path="build/assets">` in `web.config` — `immutable, max-age=365 days` | 54 conditional round-trips for a returning visitor | **0** | response headers read back over HTTP | **Applied.** Probed live: `Cache-Control: immutable,max-age=31536000` on `build/assets/core-7_a25xA8.css`. **Scoped with a `<location path="build/assets">` block inside `web.config`** — two deliberate changes from `config-proposals/web.config.proposed`, which put an unscoped block at that file's top level. **First**, unscoped it would also have covered `assets/` and `images/`, whose filenames never change. **Second**, the first attempt here put the block in `build/assets/web.config` and that was wrong for a different reason: **`/build` is git-ignored and completely untracked**, so the file would never have reached a server. Caught by checking `git check-ignore` before reporting it as shipped. **Then a third correction: `web.config` itself is git-ignored too** (`.gitignore` line 34), so neither location ships. The rule is applied here, and [DEPLOYMENT.md](../../../DEPLOYMENT.md) section 7 hands the exact XML over as a manual production step with a copy-from-dev note. Two facts fell out of the same check and are worth keeping: **`/build` is untracked, so a checkout gives a production server no compiled CSS or JS at all** — how it arrives is outside this repository, and DEPLOYMENT.md section 7b asks the question rather than guessing; and `web.config` being ignored means **every IIS change this effort makes is a manual server step**, never a deploy artifact. At the root it would also have covered `assets/` and `images/`, whose filenames never change, so a changed logo or stylesheet would have been pinned in every existing browser for a year. All **1,046** files under `build/assets` carry a content hash (checked: zero exceptions), which is what makes `immutable` safe there. **0 bytes saved on a first visit** — this is a warm-cache change only. Also noted: `web.config.proposed` is now **stale**; it predates the "static file 404 without booting PHP" rewrite rule in the live `web.config`, so copying it over would have silently deleted that rule. It was merged from, not copied. |
| 12 | Old "My Actionable Items" counter deleted, `x` dropped | `actionableItemCounts()` + `actionableApprovalRows()` (decrypt every status) and `?oldApprovalStatus=1` | `actionableItemCountsx()` + `actionableApprovalRowsx()` renamed onto the plain names | 2 methods + 1 flag + 90 lines | deleted | browser check, 2026-08-21 | **Dev's call, asked and answered.** I had held this back because the plain-text counter returns **zeros** against ciphertext, so the flag was the only way back if a production deploy ran the code before `contract:convert-approval-status --apply`. The dev chose to delete it. **The safety net is now procedural, not in code:** [DEPLOYMENT.md](../../../DEPLOYMENT.md) section 1, and the narrow migration **throws** rather than letting the order slip silently, so a wrong order fails loudly instead of quietly showing `Total (0)`. Numbers re-checked in the browser after the deletion and all unchanged: Total (1), 2508, 128, 83, `Dashboard` still the active menu item. `?withoutActionableItems=1` **stays** - it is the only way to reproduce row 3's measurement and it costs nothing. Controller is now **639 lines, down from 948** across rows 10 and 12. **`encryptStringx()` keeps its `x`, also the dev's call:** `encryptString()` has **525** call sites and ignores its second argument, `encryptStringx()` has 58 and the second argument names the `table.column` - merging them would silently convert three unrelated tables. |
| 13 | Ticket 22 cuts 2 and 3 — customizer off, ApexCharts lazy-loaded | `hasCustomizer => true`; `apexcharts.js` in the eager `vendor-script` bundle | `hasCustomizer => false`; library fetched by `IntersectionObserver` when a chart nears the viewport | **56** requests, **2,908,591** bytes, all before the load event | **36** requests / **2,360,704** bytes before the load event; **491,404** bytes after it | DevTools Resource Timing, same session 2026-08-21 | **Both applied, and neither needed a rebuild** — one config line and one blade change. **548 KB off the critical path** and 20 fewer requests. ApexCharts starts at **4,825 ms**, after the load event at 4,566 ms, and both charts still render — the milestones donut and the radial bar, verified on screen and by `#milestonesChart .apexcharts-canvas` existing. Zero customizer files fetched. **Users lose light/dark and theme switching**, and a saved `localStorage` choice stops applying; the dev accepted that in ticket 22. **The lazy loader resolves its URL through `Vite::asset()`**, so it follows the manifest and needs no hardcoded hash. Safe to defer past `cards-statistics.js`, which is also loaded here: all **12** of its chart elements are absent from this view and every one of its calls is null-guarded — so it currently parses ~1,300 lines to do nothing on this page, which is **not** fixed here and is worth its own look. **One trap worth writing down:** the first attempt wrote `@vite` inside a JavaScript comment in the blade. Blade compiles directives inside `<script>` too, and a bare `@vite` with no parentheses compiles to a zero-argument call — the whole page died with `Too few arguments to function Illuminate\Foundation\Vite::__invoke()`. Never write an `@`-directive name in a comment in a blade file. |
| 14 | Rebuild — tested, and **deliberately not swapped in** | current `public/build` + `build/` (both 33 MB, already out of sync) | `npx vite build` to a throwaway `public/build-test` | 1,049 manifest entries | **1,049 entries, exit 0, 3 m 29 s** | manifest comparison, 2026-08-21 | **The build works and was not adopted.** Built to a throwaway outDir on purpose, because `public/build/manifest.json` is what Laravel resolves against and overwriting it would have changed live asset URLs before the files behind them existed. **Four files come out with different hashes** — `jkanban`, `template-customizer`, `datatables-bootstrap5`, `charts-apex` — and **none of those four new filenames exists in the served `build/assets`**. `datatables-bootstrap5` alone is referenced by **56 blades**, including `contractList.blade.php`, so swapping the manifest without copying 33 MB of assets into **both** folders would 404 the datatable on every list page. Nothing on this map needs a rebuild: both of row 13's cuts were a config line and a blade change. So the current build stays. **The value here is the proof, which is what [ticket 07](../issues/07-asset-pipeline-decision.md) wanted:** `npm run build` runs at all now, and it produces the same 1,049 entries. **Two structural facts found while comparing, both pre-existing.** `public/build` and `build/` are **two separate real copies**, not a symlink or a junction — Laravel reads the manifest from the first, IIS serves files from the second, and **their manifests already disagree** (`contract.js`/`contractlist.js` against `script.js`; `app.css`/`app.js` crossed; 1,049 against 1,048 entries). And the module JS is **not loaded through Vite at all** — the blades use raw `<script src="{{url('/')}}/Modules/...">` URLs, which is why the manifest's `Modules/` vs `modules/` case difference between builds is harmless. The throwaway folder was deleted after measuring. |
| 15 | `serverRuntime/frequentHitThreshold=1` — **verified applied** | static gzip only from the 2nd request for a file | gzip on the **1st** request | `mapbox-gl-COawACmw.js`: **1,252,656** bytes, no `Content-Encoding` | **343,147** bytes, `Content-Encoding: gzip`, on attempt **1** | response headers, 2026-08-21 | **The dev applied this and told me so; I checked rather than taking it on trust, and it is on.** Same file the original ticket-21 probe used, so the numbers are directly comparable: it used to need a second hit to compress and now compresses immediately. **3.65x.** A second file picked at random (`app-ecommerce-dashboard-BaPyE985.js`) also came back gzipped on attempt 1, at 3,794 bytes. This is the ~1.39 MB of the 2.9 MB first visit that ticket 22 called the biggest single win, and it is now real on this machine. **Production still needs it** — `applicationHost.config` is per-machine, so nothing about this deploys. DEPLOYMENT.md section 6 now carries the verification command as well as the change. |
| 16 | One copy of the built assets — IIS serves `/contracts/build/*` from `public/build` | two separate 33 MB copies, `public/build` read by Laravel and `build/` served by IIS, nothing keeping them in step | one rewrite rule in `web.config`; `public/build` is now both | a rebuild wrote one folder and not the other — 4 files would 404 | **one folder; `npm run build` is sufficient on its own** | probe file + response headers + both pages in the browser, 2026-08-21 | **The dev's idea, and it works.** Rewrite rule `^build/(.*)$` -> `public/build/{R:1}`, placed **above** the "static file 404 without booting PHP" rule - that rule has `stopProcessing` and fires on any missing `.css`/`.js` path, so it would 404 every asset the moment the root folder went away. **Proved, not assumed:** a probe file was written into `public/build/assets` only, never into root `build/`, and `GET /contracts/build/assets/__rewrite-probe.txt` returned **200**. Probe deleted afterwards; it now 404s. **The risk I flagged before starting turned out to be real:** `<location path="build/assets">` **stopped matching** and `Cache-Control` disappeared entirely - `<location>` matches the path **after** the rewrite, not the URL the browser asked for. Changed to `<location path="public/build/assets">` and the header is back: `immutable,max-age=31536000`, with `favicon.ico` still correctly carrying none. Static gzip unaffected - `mapbox-gl` still 343,147 bytes gzipped on the first request. **Both pages verified in the browser, zero failed requests and an empty console on each:** dashboard 36 requests, 30 of them build assets, Total (1) / 2508, 22 menu links, `Dashboard` highlighted; contracts list 43 requests, `datatables-bootstrap5-DVJmVqGp.js` loads and `.dataTables_wrapper` renders. **Root `build/` is left in place, on the dev's instruction** - it is now unused, 79 MB of it, and gets deleted only after this has been lived with. **Way 2 was considered and rejected:** `$app->usePublicPath(base_path())` would have been 5 files, not 1, because `public_path()` has 4 call sites and one is the `storage:link` map at [filesystems.php:73](../../../config/filesystems.php:73), whose link name would collapse onto Laravel's own storage directory. **A correction on that:** an earlier note here said it could point a link at the uploads folder. It cannot - `StorageLinkCommand` errors with "The [...] link already exists" and skips, even with `--force`, because the path is a real directory and not a symlink. No data loss was ever possible; the real cost was 5 files changed instead of 1. |
| 17 | Root `build/` deleted | 78 MB duplicate: `assets` 33 MB, `assets-bc` 33 MB, `assets.zip` 13 MB, `index.html`, `modules` | gone; `public/build` is the only copy | 3 copies of every built file on disk | **1 copy**, 78 MB freed | md5 comparison, headers, both pages in the browser, 2026-08-21 | **Deleted on the dev's instruction, after row 16 was confirmed working.** Checked before deleting, not after: **`assets-bc` held exactly the same 1,046 filenames as `assets`, zero unique** - and since the names are content hashes, identical names mean identical bytes. Spot-checked `core-7_a25xA8.css` and `apexcharts-ZYWXGMLC.js` by md5 across all three copies: **identical in all three.** So nothing existed only in the deleted folder. Method was rename-verify-delete, not delete-and-hope: the folder was moved aside, both pages and all headers re-checked with it gone, and only then removed. After deletion: `Cache-Control: immutable` intact, gzip intact, dashboard 37 requests / 0 failed / Total (1) / 2508 / 22 menu links / sidebar fully styled, contracts list 43 requests / 0 failed / datatable rendering / empty console. **A correction that matters more than the deletion:** an earlier session note in this effort reported every counter on the contracts list page reading 0 and raised it as a suspected `whereIn` 1000-parameter bug. **That was wrong.** The DOM had been read immediately after navigation, before the page finished loading. Fully loaded it reads 406 / 399 / 205 / 148 / 223 / 205 / 747 / 2508, and those match the dashboard's milestone figures exactly. There is no bug on that page; the spun-off task was withdrawn. **Lesson for this report: never read a page's numbers straight after `navigate` - wait for it to settle, or the measurement is of a half-built page.** |
| 18 | Response compression made tunable — `CompressResponsex` + `config/compression.php`, gzip work shared in `App\Support\ResponseCompressor` | `CompressResponse`, level/min-size/content-types hardcoded as class constants | same three numbers read from config, every one with an env key | `send_terminate_ms` 5.27-11.88, median 6.28 (6 fetches); document 35,486 bytes | `send_terminate_ms` 5.27-13.70, median 7.46 (10 fetches); document **35,486 bytes** | perf log + `Log::debug` cost_ms, `100479?tab=edit`, 2026-08-22 | **Speed unchanged, and that is the point.** Both middlewares call the same `gzencode` in the same shared class, so the output is byte-identical — 35,486 on all 16 fetches, old and new. The two `send_terminate_ms` ranges overlap and `cost_ms` overlaps too (old 4.45-10.75, new 4.52-12.31), so the median gap is machine noise, not code. Gain is tunability, not ms: a client sets `RESPONSE_COMPRESSION_LEVEL`, `_MIN_BYTES`, `_TYPES` or `_ENABLED` in `.env` instead of editing a class constant. **No composer package was added** — every candidate was checked and rejected, see [ticket 23](../../contract-detail-page-perf/issues/23-response-compression-package.md). All 29 tab loads on `100479` and `1` returned 200, gzip, `Vary: Accept-Encoding`, and every decoded size matches ticket 17 exactly. |

## Page weight

Measured 2026-08-21, **one browser session**, debug Chrome profile, old `/` and new `/dashboard-summary`
alive side by side. Cold = force-reload, cache bypassed. Warm = plain reload.
Three figures, because they answer different questions ([ticket 21](../issues/21-page-weight-measurement.md)).

| # | page | cache | document bytes | resource bytes | **whole page** | requests | TTFB / load |
|---|---|---|---|---|---|---|---|
| 21a | old `/` | cold | 71,644 | 2,837,401 | **2,909,345** | 54 | 17,324 / 21,641 ms |
| 21b | new `/dashboard-summary` | cold | 63,274 | 2,845,017 | **2,908,591** | 55 | 5,508 / 8,582 ms |
| 21c | old `/` | warm | 71,644 | 0 — all 54 from cache | **71,944** | 54 | 25,078 / 27,271 ms |
| 21d | new `/dashboard-summary` | warm | 63,274 | 7,616 — 55 of 56 from cache | **71,190** | 56 | 5,359 / 7,187 ms |

### The dev's observation was right, and here is why

> I do not see much down size in the page. It is around 5MB in both old and new. Although the new loads faster.

**The whole page moved by 754 bytes out of 2.9 MB — 0.03 %.** Not 14 %, not 11 %. The dev was looking at
the true number.

The document really did shrink, 71,644 -> 63,274, **8,370 bytes off, 11.7 %**. But the option data did not
disappear — it came back as a **7,616-byte** `contracts/option-lists` request. 8,370 out, 7,616 back in,
net ~750 bytes. Change F was never a weight change. **It moves bytes off the document's critical path,
and that is the whole of its value** — same total, arriving later and in parallel, which is why the page
feels faster while weighing the same.

Row 5 above says "14.3 % smaller" on 71,294 -> 61,064. Rows 21a/21b say 11.7 % on 71,644 -> 63,274. Both
are document bytes, different sessions; the small drift is ordinary. Neither was ever a whole-page figure.

The measured whole page is **2.9 MB**, not 5 MB. No run in this session reached 5 MB. The 5 MB reading was
most likely the DevTools network panel with the log preserved across more than one navigation. The
conclusion does not change — 2.9 MB is still about 46x the document, so nothing this spec does to the
document is visible in it.

### What the 2.9 MB actually is

Identical on both pages, to the byte, except the one XHR.

| type | files | bytes | share |
|---|---|---|---|
| CSS | 13 | 966,325 | 33 % |
| JS | 19 | 940,050 | 32 % |
| fonts | 2 | 820,244 | 28 % |
| images | 19 | 90,457 | 3 % |
| XHR | 1-2 | 20,325 / 27,941 | 1 % |
| document | 1 | 63-72 K | 2 % |

Five files are **73 % of the whole page**:

| file | bytes |
|---|---|
| `build/assets/tabler-icons-C1kIx3_Z.woff2` | 702,572 |
| `build/assets/core-7_a25xA8.css` | 547,035 |
| `build/assets/apexcharts-ZYWXGMLC.js` | 486,274 |
| `build/assets/tabler-icons-XUw5yIc1.css` | 169,033 |
| `build/assets/fa-brands-400-C99Yv4gD.woff2` | 117,672 |

Every one is stock Vuexy template or a chart library. **None of it is contracts code.**

### Compression: on for assets, but only from the second request. Off for the document.

Two separate settings, and they behave differently. Both were probed directly, reading the response
headers back.

**Static files (CSS, JS, fonts): gzip is configured, but the first request for a file never gets it.**
Probed `mapbox-gl-COawACmw.js`, a 1,252,656-byte file this profile had never fetched, four times
back to back:

| attempt | `Content-Encoding` | `Content-Length` |
|---|---|---|
| 1 | none | 1,252,656 |
| 2 | gzip | 343,147 |
| 3 | gzip | 343,147 |
| 4 | gzip | 343,147 |

**3.65x, from the second request on.** This is IIS static compression: it only compresses a file once it
counts as frequently hit, and it counts hits inside a short window. A cold-cache first visit asks for
each asset **exactly once**, so it qualifies for nothing and pays **full uncompressed bytes** — which is
why rows 21a and 21b above total 2.9 MB.

This also explains the one odd reading in the session: `apexcharts.js` showed 126,796 transferred against
485,974 decoded on one load and 486,274 on the next. That was **real gzip**, not a measurement artifact —
the file had been hit twice close together. An earlier note in this report called it an artifact and that
was wrong; the correction is left visible rather than quietly removed.

**The HTML document is not compressed at all.** Three back-to-back requests for
`/contracts/dashboard-summary`, no gzip on any of them, `content-length` 63,274 every time. Same for the
`contracts/option-lists` JSON, 7,316 bytes uncompressed. IIS **dynamic** compression is a different
setting from static, and it is off. No number of hits will change it.

**Build assets carry no `Cache-Control`.** They have an `ETag` but no max-age, so a returning user makes a
conditional request per file and waits for a 304 on each — 54 round-trips before the page can finish. The
content hash is already in the filename, so these could safely be cached for a year.

All three are server configuration, not code. They are recorded here and acted on in
[ticket 22](../issues/22-reduce-page-size.md), which now owns page size.

### A returning user pays almost none of it

Warm cache is **71,944 bytes** old and **71,190** new — the document and, on the new page, the one XHR.
All 54-55 assets come from cache. So the 2.9 MB is a first-visit cost only, which is another reason it was
never the thing to chase for this page's response time.

The new page's XHR is the one thing a returning user still pays for, 7,616 bytes every load — it carries
no cache headers. Small, and it buys the 8,370 off the document, so it comes out even.

### Where shrinking the 2.9 MB belongs

**On this map — the dev widened the scope on 2026-08-21.**

This section previously ruled the opposite, in these words: *"no change on this map was ever going to move
the 2.9 MB, and none should be added to try."* That reading followed from the destination as it stood,
which covered response **time** only. The dev has overridden it: the destination now covers response
**size** as well, and [ticket 22](../issues/22-reduce-page-size.md) owns the cuts and their order. The old
ruling is left here as history rather than deleted.

What the measurements hand ticket 22, biggest first:

1. **Turn on dynamic compression, and make static compression apply to a first visit.** Static gzip is
   already configured and worth 3.65x on JS, but a cold visit never triggers it, and the HTML document is
   never compressed at all. Roughly 1.9 MB of CSS + JS + HTML sits behind this. **No code change.**
2. **Give the build assets a `Cache-Control` max-age.** They are content-hashed, so a year is safe. Saves
   54 conditional round-trips for a returning user — a latency win, not a byte win.
3. **Trim the template assets.** Subset the icon fonts (`tabler-icons.woff2` alone is 702 KB), drop
   `apexcharts` if the dashboard draws no charts, split `core.css`. Real work, and the only item of the
   three that touches the repo.

### What ticket 22 decided to cut

Settled 2026-08-21, [ticket 22](../issues/22-reduce-page-size.md). Nothing applied, so these are
predictions — each gets a real row above when it lands. This supersedes the three-item list directly
above, which was the measurement's suggestion; item 3 there guessed at "drop apexcharts" and "split
core.css" and neither survived the discussion.

| # | cut | rebuild? | predicted |
|---|---|---|---|
| 1 | `frequentHitThreshold="1"`, dynamic compression, `Cache-Control: immutable` | no | **~1.4 MB**; document 63 KB -> ~10 KB |
| 2 | `hasCustomizer => false` | no | 37 KB — users lose dark mode and theme switching |
| 3 | ApexCharts **lazy-loaded**, not dropped | no | 486 KB off first paint, chart unchanged |
| 4 | drop `fa-brands` + `fa-regular`, keep `fa-solid` | yes | 118 KB + fa-regular |
| 5 | remove the language switcher | yes | 33 KB of `flag-icons` + flag SVGs |
| 6 | Tabler woff2 subset | yes | **deferred** — up to ~680 KB, if ever |

Items 1-3 are ~1.9 MB of the 2.9 MB and touch no application code. Items 4-6 cannot be done at all until
a root `vite.config` exists ([ticket 07](../issues/07-asset-pipeline-decision.md)) — nothing can be
rebuilt today.

Item 1 is split across two files in [config-proposals/](../config-proposals/) because `contracts/` is
served under the parent GOAL app: what a folder `web.config` can set, and what needs
`applicationHost.config`. `frequentHitThreshold` is `AppHostOnly` and has no in-app equivalent.

### Backfill of the earlier rows

Rows 0 and 2b were measured in the `public/hot` era, when **28 of 31 requests failed**, so their transfer
totals are the weight of 404s and cannot be compared with anything. Document bytes from those runs are
sound and are the only figures carried forward:

| row | state | document bytes (raw / wire) | transfer total |
|---|---|---|---|
| 0 | baseline, N=18 | 70,254 / 71,668 | **not valid** — 120,869 with 28 of 31 requests failed |
| 0 | baseline, N=3,018 | 67,434 / 68,848 | **not valid** — 118,049, same reason |
| 5 | Change F, document only | 71,294 -> 61,064 | not taken; superseded by rows 21a-21d |

The N=3,018 baseline document is *smaller* than N=18 because "My Actionable Items" rendered empty — the
1,000-id bug, row 3.

### Also confirmed in the same session

- Both selects on the new page end `data-option-state="ready"` with **73 and 63** options, cold and warm.
- The option-list fetch starts at **7,062 ms**; the first script on the page starts at 5,535 ms — so it is
  leaving before the vendor scripts finish, which is what moving it to `<head>` was for. It is still gated
  behind a 5.5 s TTFB, so the head move cannot be worth more than the TTFB allows. That is the "Change F
  part 2" row below, now measurable.
- Request count 54 -> 55 cold: one added request, the option-lists endpoint.

## Regression report, 2026-08-21 — "dashboard-summary now takes 11 seconds"

Dev's report. Investigated with the [ticket 02](../issues/02-timing-middleware.md) recorder
(`storage/logs/perf-2026-08-21.log`, 93 records across the day) plus DevTools. **Nothing was changed to
fix it — this is a report.**

### The IIS compression change is not the cause. It worked.

| | before | after |
|---|---|---|
| whole page, cold | 2,908,591 | **1,422,077** |
| document TTFB | 5,508 ms | **5,391 ms** |
| `core.css` on the wire | 547,035 | **68,613** (7.97x) |
| `apexcharts.js` on the wire | 486,274 | **126,796** (3.83x) |

**1.49 MB off the page**, close to the predicted ~1.39 MB, and **TTFB is flat**. Asset compression is
server-side work on static files; it cannot touch PHP bootstrap. Exonerated.

*(The very first cold load after enabling it took 13,597 ms to `load` against 10,998 ms on the second —
that ~2.6 s is IIS gzipping each file once and caching the result. A one-time warm-up per file, not a
standing cost.)*

### The real cost is bootstrap, and it always was

One request, `/contracts/option-lists`, 16:20:36:

| phase | ms |
|---|---|
| **bootstrap** | **4,558** |
| routing | 48 |
| route middleware | 153 |
| controller | 59 |
| view render | 4 |
| **total** | **4,823** |

**94 % of the request happens before the controller runs.** The controller is 59 ms and its 11 queries
are 62 ms. There is nothing wrong with the dashboard code in this picture.

### Root cause: PHP opcache is switched off

`opcache: false` on **every one of the 93 records**, all day. Confirmed in the FastCGI php.ini:

```
C:\xampp\php\php.ini:980   ;zend_extension=opcache
C:\xampp\php\php.ini:1783  ;opcache.enable=1
```

Both commented out. So PHP recompiles every file on every request, and this install has a great deal to
recompile:

```
vendor/composer/autoload_static.php     17.9 MB
vendor/composer/autoload_classmap.php   17.1 MB   (77,878 entries)
```

**~35 MB of PHP array literals, parsed and compiled on every request.** With opcache on, that is
compiled once and read from shared memory thereafter. With it off it is paid every time, which is why
bootstrap ranges from 537 ms to 4,948 ms depending on nothing but machine load. The 77,878 entries come
mostly from `google/apiclient`, `microsoft/microsoft-graph` and `phpoffice/phpspreadsheet`, not from
anything this effort added.

### Three PHP requests per page load, not one

Every dashboard load fires three, and each pays the full no-opcache bootstrap:

| request | total | of which bootstrap |
|---|---|---|
| `/dashboard-summary` | 5,289 ms | 1,218 |
| `/assets/fonts/font-main.css` **(404)** | 2,323 ms | 2,184 |
| `/contracts/option-lists` | 4,823 ms | 4,558 |
| **sum** | **12,435 ms** | |

That sum is the 11 seconds. **The 404 font file is a full Laravel boot** — the missing asset does not fail
cheaply at the web server, it routes into PHP and renders an error page. Flagged by
[ticket 07](../issues/07-asset-pipeline-decision.md) item 4 and
[ticket 22](../issues/22-reduce-page-size.md), never fixed. It is now the second most expensive request on
the page.

### What this effort added on top, stated plainly

**Debugbar loads on every request even with `DEBUGBAR_ENABLED=false`.**
`LocalDebugbarServiceProvider` registers Debugbar's own provider whenever `APP_DEBUG` is true and
`APP_ENV` is `local`; Debugbar's internal gate then suppresses the bar. So its **107 PHP files are
compiled on every request** — and with no opcache, that is paid every time rather than once.

`/contracts/option-lists` bootstrap across the day:

| time | bootstrap | context |
|---|---|---|
| 10:03–10:20 | 572–1,368 ms | before any of today's work |
| 14:53–14:58 | 1,731–3,223 ms | **already degraded, before the composer work** |
| 15:02 | 946 ms | still before Debugbar |
| 16:06–16:20 | 3,120–4,948 ms | after Debugbar was installed |

**Honest reading: there is no clean single break.** The 14:53–14:58 window was already at 1,731–3,223 ms
with no Debugbar and no composer changes, and 15:02 came back down to 946 ms. Bootstrap on this machine
swings by more than 3x on its own — the same drift this report warns about at the top. Debugbar's 107
files are a real addition and the post-16:06 numbers are the worst of the day, so it is **contributing**,
but it cannot be shown to be the whole of a 946 -> 4,558 ms move, and the honest attribution is
"opcache off, made worse by everything added to the boot path".

### Cheapest fix by a wide margin, not applied

**Turn opcache on.** Uncomment `zend_extension=opcache` and `opcache.enable=1` in `C:\xampp\php\php.ini`
and restart the app pool. It is a one-line change that stops ~35 MB of autoload arrays being recompiled
per request, and it would help **every page in the application**, not just this one — 92 of the
dashboard's 127 queries belong to the menu composer, and all of that boot cost is on the same path.

Two cheaper-still follow-ups, both already known:

- **Fix or stub `assets/fonts/font-main.css`** so a missing file does not cost a Laravel boot. Worth
  ~2,300 ms per page load on its own.
- **Comment out `LocalDebugbarServiceProvider` in [config/app.php](../../../config/app.php)** while
  measuring, so Debugbar's 107 files leave the boot path entirely rather than being loaded and then
  suppressed.

Nothing above was applied.

## Fixing the regression, 2026-08-21 — items A, C, D and the font

Dev approved a four-part plan after the regression report above. All four applied and measured in one
session, `DEBUGBAR_ENABLED=false` throughout, opcache **left off** on the dev's instruction.

### PHP time per dashboard load

| | before | after | change |
|---|---|---|---|
| `/dashboard-summary` total | 5,289 ms | **4,515 ms** | -15 % |
| `/dashboard-summary` bootstrap | 1,218 ms | **926 ms** | -24 % |
| `/assets/fonts/font-main.css` (404) | 2,323 ms | **0 — never reaches PHP** | gone |
| `/contracts/option-lists` total | 4,823 ms | **1,825 ms** | -62 % |
| `/contracts/option-lists` bootstrap | 4,558 ms | **1,576 ms** | -65 % |
| **PHP requests per page** | **3** | **2** | |
| **PHP time per page** | **12,435 ms** | **6,340 ms** | **-49 %** |

Browser side, same session: TTFB **5,508 -> 4,563 ms**, `load` **10,998 -> 10,594 ms**, whole page
**1,422,077 -> 1,452,101 bytes** (the +29,724 is the newly-shipped font, below).

### A — a missing static file 404s at IIS instead of booting Laravel

New rewrite rule in [web.config](../../../web.config), placed **before** the Laravel rule: if the path
ends in a static extension and the file is not on disk, IIS answers 404 itself and stops processing.

| | before | after |
|---|---|---|
| `/assets/fonts/font-main.css` | **2,537 ms**, 2,184 ms of it Laravel bootstrap | **191–293 ms**, IIS error page |
| PHP records for it in the perf log | 33 that day | **0 after the change** |

Verified alongside: a real static file (`assets/json/search-vertical.json`) still returns 200 with the
right content type, and a normal Laravel route (`contracts/option-lists`) still returns 200 JSON. Laravel
URLs have no file extension, so the rule cannot touch them.

**This fixes the class, not the file.** No future missing asset can cost a framework boot. Checked against
`rewrite_schema.xml` before writing — `CustomResponse` is action value 3 and `statusCode` /
`subStatusCode` / `statusReason` / `statusDescription` are all valid; the validation excludes 300-307 only.
`.json` is in the extension list and is safe because no route in this app ends in `.json` (checked across
`routes/` and `Modules/*/routes/`); a note in the file says to remove it if one is ever added.

### C — `DEBUGBAR_ENABLED=false` now stops Debugbar loading at all

[LocalDebugbarServiceProvider](../../../app/Providers/LocalDebugbarServiceProvider.php) checks the flag
**before** registering Debugbar's provider. Previously it registered regardless and Debugbar's own gate
only suppressed the *bar*, so its **107 PHP files were compiled on every request** — including the AJAX
endpoint that never displays anything. Default is now false, so the measuring state is the resting state.

Caveat written into the file: `env()` is used because Debugbar's config is not published, and `env()`
returns null under `php artisan config:cache` (Laravel stops loading `.env` then). That reads as false and
leaves the bar off, which is the safe direction. No config cache on this install.

### D — the optimized classmap is a loss while opcache is off

`optimize-autoloader` set to false and the autoloader re-dumped.

| | optimized | not optimized |
|---|---|---|
| `autoload_static.php` | **17,862.9 KB** | **224.4 KB** |
| `autoload_classmap.php` | 17,143.5 KB | 179.2 KB |
| marginal parse cost (`php -l`, opcache off) | ~250 ms | **~0** (0.40 s vs 0.41 s for a trivial file) |

An optimized classmap is a win **with** opcache and a loss **without** it: ~18 MB of array literals get
recompiled on every request. All **17** module psr-4 entries verified present afterwards and the app
boots — that check exists because a bad dump broke every module earlier the same day.

Measured on the endpoint, standalone (the low-noise instrument):

| | bootstrap, 3 runs |
|---|---|
| optimized | 1,364 / 1,218 / 1,075 ms |
| not optimized | 999 / 1,367 / 870 ms |

**~220 ms standalone, matching the parse-cost prediction.** But under a real page load it was worth far
more — `option-lists` bootstrap went **4,123 -> 1,488 ms**, because with opcache off the 18 MB parse
competes with IIS gzipping 1.4 MB of assets on the same CPU.

**Reverting is one line**: set `optimize-autoloader` back to true in `composer.json` and re-dump. Do that
the moment opcache is enabled — at that point the optimized classmap becomes the faster option again.

### The font: it was never there, and now it is

The `<link>` to `/assets/fonts/font-main.css` is in the **first commit** (`81b62b1 "copied from
expense-v3"`) and the file has never existed in this repo. Evidence: `git log --all -- assets/fonts` is
empty; there is no `.woff`, `.woff2` or `.ttf` anywhere under `assets/` (681 tracked files); and
`font-main.css` is nowhere on `D:\Contract-Expense`, including the sibling `contracts_management` project.
`public/assets` was never the location either — the app is flattened, so the real tree is the app-root
`assets/`, which is why `assets/logo/OnTrackLogo.png` always worked. **Nothing broke it. It arrived
broken**, and the app silently ran on fallback fonts from day one.

Fixed properly rather than by deleting the line, on the dev's instruction that assets are served from this
server and never from Google at runtime. Downloaded **once** from `fonts.gstatic.com` and committed to
[assets/fonts/public-sans/](../../../assets/fonts/public-sans/):

| file | bytes | covers |
|---|---|---|
| `public-sans-latin-variable.woff2` | 26.2 KB | weights **300-700**, normal |
| `public-sans-latin-italic-variable.woff2` | 15.3 KB | weights 300-700, italic |

**Two files, 41.5 KB, not ten.** Public Sans v21 is a *variable* font, so one file covers every weight
rather than one per weight, and only the `latin` subset is shipped (`latin-ext` and `vietnamese` are not
used here). Both verified as genuine woff2 by their `wOF2` magic bytes.
[assets/fonts/font-main.css](../../../assets/fonts/font-main.css) declares them with the real
`unicode-range` from Google's stylesheet and `font-display: swap`, so text never waits on the font.

Confirmed in the browser: `font-main.css` returns **200**, the normal woff2 loads (27,132 bytes on the
wire), the italic face stays **unloaded** until italic text appears, and `document.body` computes to
`"Public Sans"` where it previously fell through to the system stack.

**Cost: +29,724 bytes on a cold load** — the only row in this effort that deliberately makes the page
heavier. It buys the typography the template was always designed for, and a returning user pays none of
it.

### What is left, and it is no longer the server

Every real resource now finishes by **6,697 ms**, but `load` is **10,594 ms**. The ~4 s gap has **no
network activity in it at all** — it is main-thread JavaScript: ~940 KB of jQuery, Bootstrap, select2,
ApexCharts, typeahead and the theme customizer executing after `DOMContentLoaded` (5,700 ms).

**The page is now JavaScript-execution bound, not server bound and not byte bound.** That points straight
at decisions [ticket 22](../issues/22-reduce-page-size.md) has already made and not yet applied — customizer
off (37 KB and 8 stylesheet paths), ApexCharts lazy-loaded (486 KB off first paint). Those are the next
real wins on this page.

And the standing one: **opcache is still off**, which is why bootstrap is 926 ms rather than tens of
milliseconds. Two commented lines in `C:\xampp\php\php.ini`. The dev has deferred it deliberately.

## Opcache on, and where the 3 seconds actually goes, 2026-08-21

Dev asked two things: why does the page take ~3 s of server time when bootstrap is under 1 s, and is it
the machine? Then: turn opcache on and measure. Both below. Opcache **enabled** at the dev's request.

### It is not the processor and it is not HTML rendering. It is 141 queries.

Phase breakdown of one `/dashboard-summary`, opcache **off**, total 4,515 ms:

| phase | ms |
|---|---|
| bootstrap | 926 |
| routing + route middleware | 160 |
| **controller** | **2,952** |
| view render | **476** |
| send + terminate | 1 |
| **of which: database** | **3,052** (141 queries, 17 shapes) |

**Building the HTML is 476 ms.** The controller time *is* database time. So the answer to "3 seconds to
build the page" is: it does not build for 3 seconds, it waits on MySQL for 3 seconds.

### Opcache: a big win on bootstrap, no effect on the database

`zend_extension=opcache` and `opcache.enable=1` uncommented in `C:\xampp\php\php.ini`, plus
`memory_consumption=256` and `max_accelerated_files=20000` for headroom (the default 10,000 is close to
the line for Laravel + 5 modules + vendor, and once full opcache silently stops caching). Backup at
`php.ini.bak.20260821`. No php-cgi worker was running, so the next request picked it up with no restart
needed. Confirmed live: `ext_loaded: true, enabled: true, cached_scripts: 826`.

| | opcache off | opcache on |
|---|---|---|
| `/dashboard-summary` bootstrap | 926 ms | **333–404 ms** |
| `/dashboard-summary` total | 4,515 ms | **3,645–3,756 ms** |
| `/contracts/option-lists` bootstrap | 1,576 ms | **663–755 ms** |
| `/contracts/option-lists` total | 1,825 ms | **857–990 ms** |
| `/dashboard-summary` **database** | 3,052 ms | **2,861–3,042 ms** |

**Bootstrap roughly halves. The database does not move at all.** That kills the earlier theory in the
regression report above that the DB time was CPU contention from PHP compiling — with PHP's compile cost
gone, MySQL is exactly as slow. The contention reading was wrong; the database cost is real.

**Note for [ticket 22](../issues/22-reduce-page-size.md) item D:** `optimize-autoloader` is currently
**false**, which was the right call while opcache was off. Now that opcache is on, the optimized classmap
becomes the faster option again. Set it back to true in `composer.json` and re-dump — one line, and worth
re-measuring.

### The one query that is 84 % of the database time

Of 141 queries and 2,861 ms, a single shape runs **6 times for 2,548.6 ms**: the approvals read behind
"My Actionable Items", [`actionableApprovalRows`](../../../Modules/Contract/app/Http/Controllers/ContractDashboardController.php:469).

Timed directly against MySQL, the real shape (join + visibility `EXISTS` on `contract_party_data` +
`ORDER BY approval_contracts.id DESC`):

| what | rows | time |
|---|---|---|
| chunk 1 — `LIMIT 2000 OFFSET 0` | 2,000 | **259 ms** |
| chunk 7 — `LIMIT 2000 OFFSET 12000` | 487 | **265 ms** |
| **one pass, no chunking** | **12,487** | **341 ms** |

**Chunk 7 costs the same as chunk 1.** That is the whole finding. `actionableItemCounts` uses Laravel's
**`chunk(2000)`**, which is `LIMIT/OFFSET` — so the entire join, the `EXISTS` and the sort are
re-evaluated on **every** chunk and all but 2,000 rows are thrown away. Seven chunks pay it seven times.

**Doing it once costs 341 ms. Doing it in seven chunks costs ~1,820–2,550 ms. That is a ~7x waste for no
benefit** — the chunking exists to bound memory, but peak memory on this request is only 28 MB.

`chunkById()` would fix the offset half (it seeks on the primary key instead of counting past rows), but
the `ORDER BY approval_contracts.id DESC` is load-bearing: the code relies on walking id descending so the
first row seen for a `unique_id` is its leader. Whether the answer is `chunkById`, a single pass, or moving
the leader-picking into SQL is a design decision, not a mechanical change.

### And the index from row 4 makes this particular query slower

Same query, same session, only the index hint changing:

| | time |
|---|---|
| with `idx_approval_contracts_contract_id` | **341 ms** |
| `IGNORE INDEX (idx_approval_contracts_contract_id)` | **211 ms** |

**~1.6x slower with the index.** Row 4 measured that index making the approvals join **5x faster**, and
that measurement stands — it is a different query. Here the optimizer drives from the index instead of
scanning `approval_contracts` in PRIMARY order, which then costs it the ordering the `ORDER BY id DESC`
wants. **An index that helps one query and hurts another.** Not a reason to drop it; a reason to measure
both queries whenever it is touched.

### Nothing in this section was changed except opcache

The `chunk()` finding and the index finding are **reported, not fixed** — per
[CLAUDE.md](../../../CLAUDE.md), a working function is not rewritten in place, and both want a decision
first. They are the next real server-side wins on this page: ~2,200 ms from the chunking alone, which is
more than opcache gave.

## Correctness check, not a timing

`php artisan dashboard:compare-counters` (spec section 9, names.md section 6) run 2026-08-20 over the
seeded set:

| run | users / roles | contracts counted (old = new) | result |
|---|---|---|---|
| entity 1, no filter | 1 user, Super Admin | 2,508 = 2,508 | all 15 stage counters and 4 task counters match |
| entity 2, no filter | 4 users - Super Admin, Admin, User, Branch Head | 2,508 = 2,508 (Super Admin), 0 = 0 (User) | match, every role |
| entity 2, filtered | Super Admin, `--locs=1,10,100 --types=1,10,11` | 5 = 5 | match |

**Two honest gaps.** `contract_tasks` is **empty** (0 rows), so the four task numbers were compared
only at zero - the task query shape is unproven against real rows. And the old "My Actionable Items"
numbers live in the blade, not in `dashDetails()`, so the command reports the new six rather than
diffing them; the browser check is what covers those (old `Total (0)` -> new `Total (1)`).

## Rows still to fill

- ~~Migration 1 (row 4)~~ — **done 2026-08-21**
- ~~Convert `contract_party_data` (row 7)~~ — **done 2026-08-21** on the dev database. Production is the dev's, at deployment, with the collation for that server
- **Whole-page effect of both schema changes** — rows 4 and 7 measure the two queries, not the dashboard. Still open: the 2026-08-21 session measured TTFB **5,508 ms** on the new page with both schema changes applied, against **671 ms** in row 2b before them — but that is a different session and between-session ms drift is about 3x, so the two cannot be subtracted. Needs old and new in one session on the same schema
- **Change F part 2 — option-list fetch moved to `<head>`** (2026-08-20, dev approved). Old: the fetch
  started inside `$(function(){})`, so it waited for jQuery and every vendor script. New:
  `partials/option-lists-head.blade.php` starts it in `<head>` and leaves the promise on
  `window.contractOptionListsPromise`. Measure how much earlier the request leaves, and confirm the
  selects still end `data-option-state="ready"` with 73 and 63 options. Needs a browser session.
- ~~Page weight~~ — **done 2026-08-21**, rows 21a-21d above. Rows 0 and 2b could only be backfilled for document bytes; their transfer totals were invalid because most requests failed
- Consolidate the duplicated asset trees — spec §10 step 10, not started
- ~~Plain-column experiment ([ticket 17](../issues/17-plain-columns-experiment.md))~~ — **done 2026-08-21**, rows 8 to 8c above. Scope was cut to `approval_status` only; `username` stays encrypted

## Notes on the columns

- **old / new** — put the unit in the cell (`ms`, `queries`, `KB`, `rows read`). One measure per row;
  add a second row for the same change if it needs a second measure.
- **page weight** — three figures: document bytes / total transfer bytes / request count. Say whether
  the cache was cold or warm; a returning user pays almost none of the 5 MB.
- **measure** — how it was taken: `timing middleware`, `SHOW PROFILES`, `DevTools`, `debug bar`.
- **side effects / remarks** — anything that got worse, any behaviour that changed, any number that
  moved for a reason other than speed. Blank means "checked, nothing".
