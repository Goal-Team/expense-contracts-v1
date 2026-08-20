# Map: Contracts Dashboard Performance

## Destination

**Reached 2026-08-20 — the spec is [spec.md](spec.md).** Then **reopened the same day** by the dev
with eight changes to the plan. Two tickets are still open:
[ticket 17](issues/17-plain-columns-experiment.md) and
[ticket 18](issues/18-goalapp-apollo-note.md).
[Ticket 19](issues/19-new-function-names.md) closed 2026-08-20 — names in [names.md](names.md). The spec has been amended in place; the rule changes
are recorded in Notes below and in [CLAUDE.md](../../CLAUDE.md).

An agreed, measurement-backed optimisation spec for the contracts dashboard — the page served at
`http://apollo.contracts.legality:8888/contracts/`, which is Laravel path `/` →
[`ContractDashboardController::dashDetails`](../../Modules/Contract/app/Http/Controllers/ContractDashboardController.php:35) →
[`viewDashboard1.blade.php`](../../Modules/Contract/resources/views/dashboard/viewDashboard1.blade.php).

The spec is implementable by someone else and its committed centrepiece is **moving dropdown option
data out of the HTML response into AJAX endpoints**. Any prescribed migrations are written as files
and shown for review before anything is applied.

Done when: the spec is agreed, every open question below is closed, and nothing is left to decide
before implementation begins.

**Reopened scope, 2026-08-20.** Names for every new function are now agreed
([names.md](names.md), from [ticket 19](issues/19-new-function-names.md)); the `goalapp_apollo` note must be written
([ticket 18](issues/18-goalapp-apollo-note.md)); and the plain-column experiment
([ticket 17](issues/17-plain-columns-experiment.md)) runs **after** the spec has shipped and been
measured, so it is the last thing on this map.

## Notes

**Domain.** **Laravel 10.48.29** (not 11 — the map said 11 until 2026-08-20; corrected from
`vendor/laravel/framework/.../Application.php:43` and `composer.json:14`, which asks `^10.0`) +
nwidart/laravel-modules + Vuexy template. `/contracts` is the **IIS base
path**, not a Laravel route segment — `GOALv4/` is the IIS document root holding a legacy Angular
app, and `contracts/` is the Laravel app flattened so `index.php` sits at the app root.

**Skills to consult each session:** `diagnosing-bugs` (performance attribution), `grilling` +
`domain-modeling` (any decision ticket), `research` (AFK fact-finding), `tdd` if code lands.

**Standing constraints for this effort:**

- Scope is the `contracts/` folder only. The legacy Angular app and `/login/` are out.
- **Dropdown option data must move to AJAX endpoints.** Decided by the dev 2026-08-14; this is a
  given, not an open question. What remains open is endpoint shape, caching, and selection state.
- **Instrumentation tooling: reversed 2026-08-20.** The earlier rule ("no vendor packages, a shipped
  debugger is an unacceptable risk") was the dev's call on 2026-08-14 and drove
  [ticket 02](issues/02-timing-middleware.md)'s self-written middleware. The dev has since decided a
  debug bar is worth having, for humans and for the agent, on the grounds that `APP_DEBUG=false` in
  production hides it — the same discipline as the `LOG_LEVEL` rule. **Debugbar is now allowed,
  gated on the production `.env` being correct.** The ticket 02 middleware stays; it is already
  built, costs nothing, and found things a debug bar would not have (the 1000-id bug).
  [Ticket 14](issues/14-debug-tooling-research.md) has now reported and **weakened the reasoning**: the
  `APP_DEBUG`/`LOG_LEVEL` analogy does not hold, because a wrong `APP_DEBUG` puts query bindings on a
  public page rather than noise in a file. The call itself moved to
  [ticket 16](issues/16-debug-tooling-decision.md).
- **Migrations are allowed** but the files are shown for review before being applied. Never apply
  directly.
- **No shadow columns. Reversed by the dev 2026-08-20.** Adding plaintext copies of encrypted columns
  is off the table. "My Actionable Items" instead decrypts in PHP over a narrowed row set and pays
  ~0.5 s locally / ~2 s expected at 60,000 rows on every load ([spec.md](spec.md) §4). The real fix —
  those two columns not being encrypted at all — is
  [ticket 17](issues/17-plain-columns-experiment.md), scoped to
  `apollo_contracts_expense.approval_contracts.approval_status` and `.username` **only**, and it runs
  last so its win can be measured on its own.
- **Nothing is rewritten in place. Set by the dev 2026-08-20.** Every improvement is a new function
  beside the old one so both can run on the same page and be compared; the old one is deleted later,
  once the new one is proven. Names are PSR-1 / PSR-12 — classes `StudlyCaps`, **methods `camelCase`**,
  constants `UPPER_SNAKE_CASE`, plain procedural functions `snake_case`. Good old name -> add `x`; bad
  old name -> suggest a better one and get it approved. One function, one concern, no copied blocks. In
  doubt, ask. **All names are now decided and live in [names.md](names.md)** — the one file to edit if a
  name changes; every session writing code reads it first. Rule in [CLAUDE.md](../../CLAUDE.md).
- **One measurement report, set by the dev 2026-08-20.** Every change writes a row into
  [measurements/report.md](measurements/report.md) — old number, new number, how measured, and a remark
  for any side effect. Old and new measured in the same session on the same data, because absolute
  milliseconds drift about 3× between sessions. Never a second report file.
- **Local `.env` is ours to change, set by the dev 2026-08-20.** Debug-bar variables are in
  [.env](../../.env) and documented with their production values in
  [.env.example](../../.env.example): `DEBUGBAR_ENABLED=true` local / `false` production,
  `DEBUGBAR_OPEN_STORAGE=false` both. The dev copies the keys to production and sets them there.
- **Caveman English for questions, set by the dev 2026-08-20.** Questions are short and blunt, filler
  cut. Explanations and specs stay in normal plain words. **No summary unless asked.** See
  [CLAUDE.md](../../CLAUDE.md).
- **Character set and collation, decided 2026-08-20.** Everything this effort creates or changes uses
  character set **`utf8mb4`** and collation **`utf8mb4_unicode_ci`** — named explicitly in the migrations.
  It works on both MySQL 8 and MariaDB 10.4, is case-insensitive, and is **already** the collation of
  `contracts` and `approval_contracts`, so no mixed-collation comparison appears in the tables this work
  touches. `utf8mb4_0900_ai_ci` was asked for first and dropped: it is MySQL 8 only and does not exist on
  the local MariaDB 10.4.24. See [ticket 09](issues/09-index-and-migrations.md).
- **Order of work, set by the dev 2026-08-20.** Fix the N+1 queries and the page dumping data the blade
  never uses **first**. Measure. Only then consider indexes, and only then load-test at bigger row counts.
  Growing the dataset before those two are fixed measures the wrong thing.
- **Assumed production scale** for sizing migrations, given production data is off limits: ~10,000
  contracts, ~500 approvers, ~60,000 approval/workflow rows.
- **What that ~60,000 actually is, checked 2026-08-20** because the dev asked whether it meant hand-made
  JSON. It does not. Two separate things:
  - `approval_contracts` — **real rows**, one per contract per approver per stage, 12,816 locally for
    3,018 contracts (about four per contract). The seeder already made 13,867 of them
    ([ticket 04](issues/04-seed-realistic-dataset.md)); nothing is hand-written and there is no JSON in
    it. Scale to ~10,000 contracts and it is ~40–60,000 rows.
  - The approver **rules** are the JSON — `financial_limit.approval_required_users` and its seven
    sibling columns, **one row locally**, holding the review / negotiation / finalization / approval
    stages with approver name and email in each. Also `extension_approval_rules.approvers_json` (2 rows)
    and `party_approval_rules` (0 rows).
  **The dashboard never reads `financial_limit`**, so no rule JSON needs creating or copying — not 600
  entries, not one. Worth noting for [ticket 17](issues/17-plain-columns-experiment.md): approver emails
  already sit in **plaintext** in this same database, in that JSON and in
  `approval_group_approvers.approver_email`.
- **Seeding synthetic data into `apollo_contracts_expense` is allowed and expected** for realistic-N
  measurement.
- **Targets:** under 2s is good; around 2s is tolerable; over 10s is unacceptable. A query-count
  ceiling matters more than the millisecond figure — it is what stops the regression returning.
- This map is **planning**. It produces a spec, not the implementation.
- **No git worktrees.** Branches only, in the primary working directory. See
  [CLAUDE.md](../../CLAUDE.md). Currently on `claude/contracts-dashboard-perf-42d34c`; no new
  branch needed.
- **Only the `apollo_contracts_expense` database.** `goalapp_apollo` and every other database on
  the local server are never changed. Every schema change is a Laravel migration, shown for review
  before it runs. Adding columns is allowed when the gain is worth it. See [CLAUDE.md](../../CLAUDE.md).
- **Plain words when talking to the dev.** Terms worth keeping live in
  [CONTEXT.md](../../CONTEXT.md) with a plain meaning next to each. Add a term the first time it
  is used. See [CLAUDE.md](../../CLAUDE.md).
- `original_username` on `approval_contracts` has another purpose. It is **not** a plaintext
  fallback for `username` and must not be repurposed.

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
- [Assemble and agree the spec](issues/10-assemble-spec.md) — **Done: [spec.md](spec.md)**, the
  destination of this map. 14 sections covering the measured baseline at both scales, the four
  independent problems plus the 1000-id correctness bug, targets and **query-count ceilings** (10 queries
  from `dashDetails`, flat as N grows), six changes A–F, all three migration files written out with
  working `down()`s, preserved-vs-changed behaviour, a 12-step order with dependencies, expected outcome
  per change, what was deliberately not done, and deployment notes. States plainly that it **does not
  reach "under 2 s" on its own** — ~1.25 s of bootstrap is left for
  [ticket 11](issues/11-per-request-overhead.md).
- [Decide what debug tooling we actually add](issues/16-debug-tooling-decision.md) — **Debugbar: yes,
  behind three locks** — a local-only wrapper provider (`APP_DEBUG` *and* `trim(APP_ENV)==='local'`,
  copying [PerfTimingServiceProvider](../../app/Providers/PerfTimingServiceProvider.php)) with
  auto-discovery disabled, `DEBUGBAR_ENABLED=false` in the production `.env`, and request storage off.
  **Caps stay at 100/500** — raising them was rejected; Debugbar is for humans on normal pages, and the
  tool for *this* effort stays the `DB::listen` recorder. **Boost: no** (v1.8.13 needs Laravel 10.49+,
  upgrades are refused; v1.1.5 ships a bare `eval()` tool, and Boost has no query log anyway). **New
  fact: `composer.lock` cannot be repaired by picking a side** — its `nwidart/laravel-modules` hunk is
  v9.0.6 vs v11.1.4 while **10.0.6 is installed**, so neither side is real;
  `vendor/composer/installed.json` (161 packages, `laravel/framework v10.48.29`) is the only truthful
  record and the lock gets rebuilt from it, gated on `composer install --dry-run` reporting nothing to
  do. **Standing rule from the dev: add new packages, never upgrade installed ones; escalate serious
  security holes instead of bumping.** Ships separately; the spec does not depend on it.
- [Decide the approval_contracts backfill plan](issues/15-approval-backfill-plan.md) — **The long pole
  isn't one.** Measured: all 13,867 local rows, both columns, **27,734 values decrypted in 0.49 s** —
  about **2 seconds one time** at the assumed 60,000 rows. So: no queue, no window, no progress table.
  **The key is now understood exactly** — `APP_ENCRYPTION_KEY` is `"c0n|r@(t$" . <first dot-piece of
  HTTP_HOST> . "4"`, i.e. `c0n|r@(t$apollo4`, and `encryptString`/`decryptString` **throw away their
  `$key` argument**; the table-name-in-the-key scheme is the *legacy SQL* one
  ([helpers.php:386](../../app/helpers.php:386)), not this table's. **Dev's call: a standalone script
  with the key hardcoded**, bypassing the helpers, so there is no host dependency and no manual step
  (he removes the literal later; if production's host doesn't start with `apollo`, the literal changes).
  `chunkById(1000)`, stateless and re-runnable, **never `whereIn`**. Failed rows get a **marker**, are
  logged by id only, and are retried on a later run. **Ticket 08's interim slow counter is dropped** —
  the gap it covered is two seconds inside one deploy; release order is add columns -> fill -> switch.
  Verification compares **every** row, not a sample. Columns: `approver_email varchar(191)`,
  `approval_status_plain varchar(20)`, utf8mb4_unicode_ci, **no case normalising** (the 127 real rows
  are lowercase, the capitalised ones are all seeded; the collation is case-insensitive anyway).
- [Decide the logging and debug-output policy](issues/13-logging-policy.md) — **No lint gate**, by the
  dev's call: there is **no build to fail** in this repo (no `.github/`, no CI config, no husky, no git
  hooks; deploy is a file copy to IIS), and a write-blocking Claude Code hook was offered and declined.
  The [CLAUDE.md](../../CLAUDE.md) rule stands on review alone, and applies to **new code only** — the
  6 live and 58 commented-out print-debug calls are left alone. **Production `.env` is not this map's
  problem:** the dev says those variables are carefully mapped and the production pipeline is not to be
  worried about now, so nothing here waits on someone reading the live file. Carry-forward for
  [ticket 16](issues/16-debug-tooling-decision.md): "`APP_DEBUG=false` hides it in production" is
  reliable, which kills the *reachability* worry but not the risk itself — a wrong `APP_DEBUG` still
  paints query bindings onto a public page. Nothing spun out; the spec carries one line naming the four
  production values (`APP_ENV=production`, `APP_DEBUG=false`, `LOG_CHANNEL=daily`, `LOG_LEVEL=warning`).

- [What is the safe way to add a debug bar, and is a Laravel MCP server the better fit?](issues/14-debug-tooling-research.md)
  — Reported; decides nothing. Full findings in [research/debug-tooling.md](research/debug-tooling.md).
  **Corrected two of this map's own facts:** the app is **Laravel 10.48.29, not 11**, and the ticket-02
  middleware uses `DB::listen`, not `enableQueryLog` (deliberately — the query log would allocate tens of MB
  at 12k queries). **The MCP server is real:** `laravel/boost` is official — but it has **no query-log tool**,
  so it cannot show what a page ran, and only **v1.1.5** installs on Laravel 10.48, which ships a `Tinker`
  tool running a bare `eval()` with the real `.env`. **Debugbar** is gated by `DEBUGBAR_ENABLED` else
  `APP_DEBUG`, with **no local-only guard**; left on in production it shows query bindings to every visitor.
  Its default caps (100/500 queries) make it **useless on a 12,000-query page** anyway; per-query cost is
  microseconds but it adds ~19–21 MB. **No double counting** with the middleware — both listen to the same
  event and each gets its own copy. **And nothing is installable at all until `composer.lock` is repaired**
  (15 conflict markers, invalid JSON), which promotes that from adjacent debt to a prerequisite. Decision
  moved to [ticket 16](issues/16-debug-tooling-decision.md).

- [Decide the index and migration set](issues/09-index-and-migrations.md) — **Measured: the rewrite needs no
  indexes at all.** With zero new indexes at N=3,018, the 15 counters run in **13–17 ms** and the approvals
  join in **64–72 ms** — so ticket 08's rewrite replaces 12.6 s of controller time with ~15 ms on its own.
  This ticket expected indexes might make the rewrite optional; the truth is the reverse — no index turns
  3,018 round trips into one. So: **index `approval_contracts` only** (`contract_id`, plus a composite
  `(approver_email, approval_status_plain)` over ticket 08's shadow columns, email first), nothing on
  `contracts` or `contract_party_data` for speed. `contract_party_data` **is** converted — MyISAM/latin1 with
  `TEXT` join columns becomes **InnoDB + utf8mb4 / utf8mb4_unicode_ci with `varchar`**, as its own migration
  so the dashboard fix is not blocked on it. Missing `create_contracts_table` is **accepted debt**, written down. Build times
  extrapolated at ~10,000 contracts / 500 approvers / ~60,000 approval rows: indexes are seconds and online;
  the party conversion needs a window; **the shadow-column backfill is the long pole** (PHP decrypt per row).
  **Order fixed by the dev: fix the N+1 and the throwaway page payload first, then measure, then index —
  load testing at bigger row counts comes last.**

- [Redesign the dashboard query layer](issues/08-query-layer-redesign.md) — **The dashboard stops calling
  `availableContracts()`** and gets its own `DB::table()` query (the builder sidesteps `Contract`'s
  app-wide `select('*')` and `$with` scopes by construction). Counters become **one
  `GROUP BY contract_status, substatus`** → ~20 rows folded in PHP, keeping `contractStatusKey()` and the
  `Terminated` casing where they already work. **`$contractIds` is deleted, not chunked** — approvals and
  tasks `JOIN` the visibility scope, so the 1000-id bug becomes impossible rather than avoided; `$contracts`
  stops being passed to the view (0 real uses). **"My Actionable Items" has no panel** — 13,867 rows are
  decrypted to make six integers, and it is unfixable in SQL: `approval_status`/`username` are AES-CBC with
  a random IV (all 13,867 rows), `original_username` is off limits. So **plain-text shadow columns +
  index**, filled by a `saving` hook on the model — verified safe because all 6 controllers' 43 `create`
  calls go through the model, zero raw SQL. Preserved: no-internal-party exclusion, `Terminated` casing,
  Super Admin empty-branch (**role checked in PHP before the query**, never `IN ()`). Changed on purpose:
  the `filterByLocationReport` cookie is dropped, and the Actionable-Items numbers move (they are silently
  zero today). Proved by a throwaway artisan command diffing old vs new across roles. Expected recovery
  **~11.9 s of the 12.6 s** controller time. The 55-call-site `availableContracts()` rewrite is **not**
  here — scoped as a follow-on that extracts only the visibility predicate.

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
- [Name every new function and route, old beside new](issues/19-new-function-names.md) — Dev delegated
  the naming; all names decided and recorded in **[names.md](names.md)**, the one file to edit if a name
  changes. `dashboardSummary()` gets its **own routes** (`contractDashboardSummary`) rather than a
  request flag, so the old URL cannot serve new behaviour by accident; the shared visibility rule
  becomes the `ContractVisibilityQuery` service — deliberately not called a `Scope`, that word already
  means an Eloquent global scope here. The duplicate `contractDashboard` route name is left alone as
  pre-existing. Deleting an old function needs a report.md row **and** `dashboard:compare-counters`
  showing no unexpected difference.

## Not yet specified

<!-- All of the below now sit PAST the destination. The spec records each as deliberately
     not done (spec.md section 12). They are follow-on efforts, not unfinished business here. -->

- **What the `availableContracts()` follow-on effort actually contains.** Ticket 08 sized it (55 call
  sites, 52 of one shape; the risk is verifying 23 report/export consumers with no test suite) and
  framed it as "extract the visibility predicate, leave the decoration loop alone" — but its scope,
  ordering against this spec, and whether `contractList` rides along are a separate charting job.
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

- **Changing** the `goalapp_apollo` database and all other tenant databases on the local MySQL
  instance — ruled out by the dev; realistic-N measurement uses seeded synthetic data instead.
  **Amended 2026-08-20:** still never changed, but the dev wants a written note of which changes there
  *would* help, for a later effort. That note is [ticket 18](issues/18-goalapp-apollo-note.md) —
  read-only investigation, nothing applied, nothing prescribed in this spec.
- The legacy Angular `/login/` app and everything in `GOALv4/` outside `contracts/`.
- A MySQL MCP server. The `mysql` CLI already on PATH covers every need, so an npm MCP server
  holding DB credentials would add supply-chain surface for no capability gain.
- **`APP_ENCRYPTION_KEY` being derived from `$_SERVER['HTTP_HOST']`**
  ([config/app.php:7](../../config/app.php:7)) — under the web server it is a 16-byte string, from a bare
  CLI it is `localhost` and the Encrypter will not construct. This makes the hostname load-bearing for
  decrypting stored contract data. Genuinely alarming, entirely unrelated to dashboard response time.
- ~~Repairing the conflicted `composer.lock`~~ — **moved out of Out of scope 2026-08-20**, see
  [ticket 16](issues/16-debug-tooling-decision.md): it is now a prerequisite because Debugbar is being
  installed. Kept below for the history.
- Repairing the conflicted `composer.lock` and the `nwidart/laravel-modules` version mismatch. Real
  problems, but they sit past this destination — they affect dependency reproducibility, not dashboard
  response time. Worth a separate effort. **Caveat added 2026-08-20:** ticket 14 found that **no composer
  command can run at all** while the lock file is invalid JSON, and that repairing it risks a
  `composer update` downgrading `nwidart/laravel-modules` from the installed 10.0.6 to the `^9.0` that
  `composer.json` asks for, breaking all five modules. So this stays out of scope only for as long as we
  install nothing. If [ticket 16](issues/16-debug-tooling-decision.md) says install, this ruling is
  revisited and it becomes a prerequisite.
- **The 1000-id bug inside `ApprovalEntriesBackfillService`.** `buildLocationMap()`
  ([:1073](../../Modules/Contract/app/Services/ApprovalEntriesBackfillService.php:1073)) does
  `whereIn('custom_field_group_id', $contractIds)`, and "insert all" feeds it every missing contract id
  - so at 1,000 or more it silently returns nothing and every contract gets location `-`. Same bug as
  [ticket 12](issues/12-approvals-empty.md), different feature. Found while reading the backfill
  precedent; it is not the dashboard, so it is not this effort's to fix. Worth its own ticket elsewhere.
- Local MySQL `root` having an empty password with ~40 databases present, several appearing to hold
  real client data. Noted once; a security matter for a separate effort, not a performance decision.
