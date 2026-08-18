# Map: Contracts Dashboard Performance

## Destination

An agreed, measurement-backed optimisation spec for the contracts dashboard — the page served at
`http://apollo.contracts.legality:8888/contracts/`, which is Laravel path `/` →
[`ContractDashboardController::dashDetails`](../../Modules/Contract/app/Http/Controllers/ContractDashboardController.php:35) →
[`viewDashboard1.blade.php`](../../Modules/Contract/resources/views/dashboard/viewDashboard1.blade.php).

The spec is implementable by someone else and its committed centrepiece is **moving dropdown option
data out of the HTML response into AJAX endpoints**. Any prescribed migrations are written as files
and shown for review before anything is applied.

Done when: the spec is agreed, every open question below is closed, and nothing is left to decide
before implementation begins.

## Notes

**Domain.** Laravel 11 + nwidart/laravel-modules + Vuexy template. `/contracts` is the **IIS base
path**, not a Laravel route segment — `GOALv4/` is the IIS document root holding a legacy Angular
app, and `contracts/` is the Laravel app flattened so `index.php` sits at the app root.

**Skills to consult each session:** `diagnosing-bugs` (performance attribution), `grilling` +
`domain-modeling` (any decision ticket), `research` (AFK fact-finding), `tdd` if code lands.

**Standing constraints for this effort:**

- Scope is the `contracts/` folder only. The legacy Angular app and `/login/` are out.
- **Dropdown option data must move to AJAX endpoints.** Decided by the dev 2026-08-14; this is a
  given, not an open question. What remains open is endpoint shape, caching, and selection state.
- **Instrumentation may not add vendor packages.** `vendor/` ships with the project, so a shipped
  debugger is an unacceptable risk. Use a self-written local-only middleware instead.
- **Migrations are allowed** but the files are shown for review before being applied. Never apply
  directly.
- **Seeding synthetic data into `apollo_contracts_expense` is allowed and expected** for realistic-N
  measurement.
- **Targets:** under 2s is good; around 2s is tolerable; over 10s is unacceptable. A query-count
  ceiling matters more than the millisecond figure — it is what stops the regression returning.
- This map is **planning**. It produces a spec, not the implementation.

**Baseline facts established while charting** (all verified, 2026-08-14):

- `dashDetails` runs roughly **10 fixed queries + 4·N**, N = every active contract, no pagination and
  no eager loading. All 15 stage counters are computed in a PHP `foreach`
  ([ContractDashboardController.php:87-169](../../Modules/Contract/app/Http/Controllers/ContractDashboardController.php:87)).
- `$contract->contractPartyList` is lazy-loaded **twice in a row**
  ([Controller.php:219](../../app/Http/Controllers/Controller.php:219) and
  [:223](../../app/Http/Controllers/Controller.php:223)).
- `Contract::boot()` adds a global `select('*')` scope ([Contract.php:114](../../app/Models/Contract.php:114))
  that **overwrites** every explicit narrow `select()`, so all 110 columns hydrate regardless.
  `protected $with = ['contractPartyList']` ([Contract.php:17](../../app/Models/Contract.php:17))
  eager-loads party rows on every contract query.
- **Every column the counters branch on is plaintext** — `contract_status`, `substatus`, `status`,
  `contract_type`. Encryption is not a blocker. `contracts` uses PHP-side `encryptString` only for
  `contract_name`, `currency`, `currency_value`, `end_contract_type`; the SQL-side `AES_DECRYPT`
  helper `decrypt_data()` applies only to legacy master tables.
- `contractStatusKey()` ([helpers.php:116](../../app/helpers.php:116)) is a pure map: identity except
  `pre-approval` → `review`. Expressible as a SQL `CASE`.
- `availableContracts()` visibility is SQL-expressible: department `IN` plus an `EXISTS` on
  `contract_party_data` for an internal party in an accessible branch. No decrypted value
  participates in any filter.
- Indexes: `contracts` has only `PRIMARY`, `unique_contract_name_hash`, `legal_advisor_id`.
  **`contract_party_data` has only `PRIMARY`** — nothing on `custom_field_group_id` or
  `contract_party_location_id`, the two columns every party lookup joins on.
- Local row counts: **18 contracts**, 40 party rows, 127 approval rows, 99 branches, 73 contract
  types. At N=18 the query pattern cannot explain local slowness — something else dominates.
- **No root `vite.config.js`/`.mjs` exists** anywhere in the repo, its parent, or git history.
  `vite-module-loader.js` is imported by nothing, and every enabled module has its `paths` export
  commented out — and since `collectModuleAssetsPaths` reads *only* `paths` and ignores a module's
  `defineConfig`, the loader would contribute zero entries even if a root config existed.
  Both `hot` and `public/hot` exist containing `http://[::1]:5173` and **nothing listens on 5173**;
  see [ticket 03](issues/03-vite-setup-research.md) for which of the two is actually read and why
  there is no fallback.
- **`composer.lock` contains unresolved merge conflict markers** (lines 7, 3040, 3068, …) and is
  invalid JSON. Installed `nwidart/laravel-modules` is 10.0.6 against a `^9.0` constraint. Found
  incidentally; not a dashboard-performance matter but it means dependency state is unreproducible.

## Decisions so far

<!-- one line per closed ticket: gist + link -->

- [Why is $approvalsArr empty?](issues/12-approvals-empty.md) — **A live production correctness bug, found
  by accident.** MariaDB 10.4.24 has `in_predicate_conversion_threshold = 1000`; at or above 1000 **bound
  parameters** it rewrites `IN` into a materialised subquery that **silently returns zero rows** — no
  error, no warning. Proven exactly: 999 ids → 6,684 rows, **1000 ids → 0**, same 2,508 values as literals
  → 11,506, same values bound with the conversion disabled → 11,506. PDO has `EMULATE_PREPARES=0` here, so
  every Laravel `whereIn` is on the broken path. **Any tenant with ≥1000 visible contracts gets a blank
  approvals panel and silently zero task counters** ([:171](../../Modules/Contract/app/Http/Controllers/ContractDashboardController.php:171)
  and [:187](../../Modules/Contract/app/Http/Controllers/ContractDashboardController.php:187)) —
  `goalapp_apollo` has 2,886. Also means ticket 05's 14.4 s is an **under**-estimate (those paths were
  dormant) and makes ticket 08's `EXISTS`/`JOIN` rewrite a **correctness** fix, not just an optimisation.
- [Attribute the ~1.1s of per-request overhead](issues/11-per-request-overhead.md) — **`opcache` is not
  even loaded** (`cgi-fcgi` on `C:\xampp\php\php.ini`), so all **810 included files recompile every
  request**; `bootstrap/cache/` has **no config or route cache**; 51 providers boot per request summing
  686 ms. And **`MenuServiceProvider`'s `View::composer('*')`
  ([MenuServiceProvider.php:25](../../app/Providers/MenuServiceProvider.php:25)) alone owns all 92 overhead
  queries** — it re-runs an uncached three-tier menu lookup for each of 13 views: 13 `information_schema` +
  14 `admin_settings` + 65 `menu_configs`, application-wide, on every page. Also: **`$approvalsArr` is
  empty**, so the blade walk costs nothing and ticket 08 must not optimise it — but it *should* have data,
  spun out as [Why is $approvalsArr empty?](issues/12-approvals-empty.md). **Caveat: absolute ms vary ~3×
  between sessions** on this machine (identical 5,654 queries measured at both 8.3 s and 24.5 s of DB time)
  — trust counts and proportions, not milliseconds.
- [Seed a realistic dataset in the local database](issues/04-seed-realistic-dataset.md) — Laravel seeders
  only, no raw SQL writes. **18 → 3,018 contracts**, 6,940 party rows, 13,867 approval rows; rollback
  tested and re-seeded; backup at [measurements/pre-seed-backup.sql](measurements/pre-seed-backup.sql).
  All required distributions achieved including `Pre-Approval` (151), capitalised **`Terminated`** (90),
  and **300 contracts with zero Internal parties**. Seeding requires
  `HTTP_HOST=apollo.contracts.legality:8888 php artisan db:seed` because `APP_ENCRYPTION_KEY` is derived
  from the hostname. `contract_party_data` is **MyISAM/latin1, PRIMARY key only**.
- [Baseline: where do the seconds actually go?](issues/05-baseline-attribution.md) — Attributed at both
  scales, reconciling with no unexplained gap. **N=18: bootstrap is the leading cost** (1.1 s of 2.1 s).
  **N=3,018: the controller is 12.6 s of 14.4 s**, and two N+1s are essentially all of it —
  `contract_categories` 3,018× (4,236 ms) and `contract_type` 2,508× (3,460 ms). **The pattern is 2·N, not
  4·N**, and the "duplicate lazy load" costs *nothing* (`$with` already eager-loads it). **HTML stayed
  67 KB at both scales**, so the entire 12 s regression is work on rows that never reach the response —
  the dropdown payload cannot be a scale problem. 14.4 s is already past the 10 s unacceptable threshold.
- [Decide the asset pipeline](issues/07-asset-pipeline-decision.md) — **Delete `public/hot`** (recovers
  ~18 s for one file deletion; all 441 blades' entrypoints verified in the manifest) and **turn RTL off**
  at [config/custom.php:13](../../config/custom.php:13) *in the same change*, since deleting `hot` while
  `myRTLSupport` is `true` would serve precompiled RTL CSS for the first time. Vite dev is not reinstated
  — **but `vite build` cannot run at all today** (no root config ⇒ no manifest emitted, wrong `outDir`,
  missing entry), so a committed working `vite.config` becomes a **required** spec deliverable, not
  deferred work: without it nobody can change a stylesheet. Consolidate the duplicated `hot`/`build`/scss
  trees afterwards. `font-main.css`'s `ERR_ABORTED` needs its own fix.
- [Design the AJAX dropdown endpoints](issues/06-ajax-dropdown-design.md) — **Dashboard only** for now
  (other pages follow), but the endpoints are built **shared** so `contractList` adopts them unchanged.
  **One combined endpoint** returning all lists in a single request, respecting the page's existing
  scopes. **Fetch once and populate, `select2` stays static, no pagination** — with server-side windowing
  (~10 options + infinite scroll) recorded as a named fallback if the simple version measurably fails.
  Honest expected win: **~15 % payload cut and two queries off the critical path, not seconds** — ticket
  02 showed the dropdown queries are nowhere near the top costs.
- [Build the local-only timing middleware](issues/02-timing-middleware.md) — Built, zero vendor
  footprint, gated on `APP_DEBUG && trim(APP_ENV)==='local'`, logging to `storage/logs/perf-Y-m-d.log`.
  **First measurement overturns the map's assumption:** of ~2.1 s TTFB at N=18, **bootstrap alone is
  ~1.1 s** before routing begins, view render 461 ms, controller 252 ms. 164 queries / 153 duplicate
  executions, dominated by **`menu_configs` queried 65×** and **`information_schema.tables` 13× for
  198 ms** — neither is the dashboard. The dashboard's own N+1 (`contract_categories` 18×,
  `contract_type` 18×) costs only ~48 ms at this scale. The stage-counting problem is real but is **not**
  the leading cost at N=18; it becomes dominant at N=3,000.
- [Attach Chrome DevTools and capture the dashboard's real response](issues/01-attach-chrome-devtools.md)
  — Measured at N=18: wall clock **~21 s**, document **TTFB 1.9–2.7 s**, and **28 of 31 requests fail**
  (26 refused by the dead `[::1]:5173` host, each burning 2–6 s and serialising). ~18 s of the 21 s is
  the dead Vite host and deleting `public/hot` should recover it; TTFB is a wholly separate problem and
  is **already at or past the 2 s budget with 18 rows**. Inline `<option>` markup is **15.3 %** of the
  dashboard HTML and **20.4 %** of the contract list — higher than estimated, but 10–13 KB cannot cost
  2 s, so the AJAX win is really about taking the `BranchUser`/`ContractType` queries off the critical
  path. Raw data in [measurements/](measurements/).
- [What is the correct Vite setup this repo is missing?](issues/03-vite-setup-research.md) — Only
  `public/hot` is read (root `hot` is inert), detection is a bare `is_file()` with **no dev-server probe
  and no manifest fallback**, so the stale Sep-2024 hot file emits dead `[::1]:5173` URLs
  unrecoverably; deleting it is safe (all 441 blades' entrypoints resolve in
  `public/build/manifest.json`). **But `core.scss` genuinely costs 5.6–7.9s** — `sass@1.71.0` pure-JS on
  Vite 5.1.3's *legacy* sass API, and `api:'modern-compiler'` needs Vite 5.4+, so that is an upgrade not
  a flag. Both problems are real and independent.

## Not yet specified

- Whether cached counts belong in the design at all. There is a working precedent —
  [ContractController.php:6896](../../Modules/Contract/app/Http/Controllers/ContractController.php:6896)
  caches the party list for 10 minutes keyed by a `COUNT(*)`/`MAX(updated_at)` version stamp — but
  whether the dashboard needs it depends on what the aggregate rewrite measures at.
- Whether the fix to `availableContracts()` propagates to the other pages that call it (notably
  `contractList`), and whether that is in scope or a follow-on effort. Hangs on the shape of the
  query-layer redesign.
- Whether the 110-column `contracts` table and its two coexisting encryption schemes need addressing
  for this page's performance, or are merely adjacent debt.
- How to confirm the production symptom is the same one reproduced locally, given production data is
  off-limits for this effort.
- What to **do** about the per-request overhead once
  [Attribute the ~1.1s of per-request overhead](issues/11-per-request-overhead.md) reports. Several of the
  candidate fixes (config caching, provider trimming, killing the schema introspection, caching
  `menu_configs`) have whole-application blast radius rather than dashboard-only, so the scope question —
  fix here, or hand off as its own effort — cannot be phrased sharply until we know what the 1.1 s
  actually is.
- Whether the AJAX dropdown conversion extends to `contractList` and the other pages, now that the
  dashboard-first decision is made and the endpoints are specified as shared. Depends on the dashboard
  conversion proving out.

## Out of scope

- The `goalapp_apollo` database and all other tenant databases on the local MySQL instance — ruled
  out by the dev; realistic-N measurement uses seeded synthetic data instead.
- The legacy Angular `/login/` app and everything in `GOALv4/` outside `contracts/`.
- A MySQL MCP server. The `mysql` CLI already on PATH covers every need, so an npm MCP server
  holding DB credentials would add supply-chain surface for no capability gain.
- **`APP_ENCRYPTION_KEY` being derived from `$_SERVER['HTTP_HOST']`**
  ([config/app.php:7](../../config/app.php:7)) — under the web server it is a 16-byte string, from a bare
  CLI it is `localhost` and the Encrypter will not construct. This makes the hostname load-bearing for
  decrypting stored contract data. Genuinely alarming, entirely unrelated to dashboard response time.
- Repairing the conflicted `composer.lock` and the `nwidart/laravel-modules` version mismatch. Real
  problems, but they sit past this destination — they affect dependency reproducibility, not dashboard
  response time. Worth a separate effort.
- Local MySQL `root` having an empty password with ~40 databases present, several appearing to hold
  real client data. Noted once; a security matter for a separate effort, not a performance decision.
