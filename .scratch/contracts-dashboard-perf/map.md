# Map: Contracts Dashboard Performance

## Destination

**Reached 2026-08-20 — the spec is [spec.md](spec.md).** Then **reopened twice the same day** by the
dev, and **a third time 2026-08-21** to bring page size in scope, and **a fourth time 2026-08-21** over the
query count. **No ticket is open. Every ticket on this map is closed.**

[Ticket 25](issues/25-memo-per-request-lookups.md) closed 2026-08-21 — **out of scope**, on the dev's
call. The 19 once-per-request lookups are the login and access code, not the dashboard, and they run on
every page. The work moves to its own effort, seeded with all the findings:
[.scratch/session-optimisation/spec.md](../session-optimisation/spec.md). No map for it yet; charting one
is a later session.

[Ticket 17](issues/17-plain-columns-experiment.md) closed 2026-08-21 — `approval_status` is plain text
and indexed, the counter is about 12x cheaper, and `username` stays encrypted. It was the last item of
the 2026-08-20 reopening.

[Ticket 22](issues/22-reduce-page-size.md) closed 2026-08-21 — six cuts decided, ~1.9 MB of the 2.9 MB
needs no rebuild at all, and the biggest single one is an IIS attribute.

[Ticket 18](issues/18-goalapp-apollo-note.md) closed 2026-08-21 — the dashboard never touches
`goalapp_apollo`, so nothing cheap is stuck behind the "do not touch" rule.
[Ticket 21](issues/21-page-weight-measurement.md) closed 2026-08-21 — page weight measured and attributed;
the whole page is 2.9 MB of stock template assets and Change F never moved it.

[Ticket 20](issues/20-migration-portability.md) closed 2026-08-20 — the table conversion is no longer
a migration at all.
[Ticket 19](issues/19-new-function-names.md) closed 2026-08-20 — names in [names.md](names.md). The spec has been amended in place; the rule changes
are recorded in Notes below and in [CLAUDE.md](../../CLAUDE.md).

**Reopened again 2026-08-21** by the dev asking why the page runs ~147 queries. Measured: **141**, and
**108 of them are the menu composer** recomputing one answer for each of 15 views. The fog patch about the
per-request overhead has graduated into **two new tickets**:
[ticket 24](issues/24-attribute-remaining-overhead.md) (attribute the 19 auth-shaped leftovers, unblocked)
blocking [ticket 23](issues/23-per-request-query-decision.md) (what gets cut, and is it this map's job).

[Ticket 24](issues/24-attribute-remaining-overhead.md) closed 2026-08-21 — all 19 attributed, all 19
once-per-request, none scale with view count.

[Ticket 23](issues/23-per-request-query-decision.md) closed 2026-08-21 after four rounds of grilling —
the menu composer's **108 queries become 0 on a cache hit**, and **nothing is deleted**. It is
**Change G** in [spec.md](spec.md) section 8b, and it is **built, applied and verified in the browser the
same day** — 141 queries -> **33** on a cache hit, **40** on a miss, sidebar and counters unchanged
([report.md](measurements/report.md) row 8). **Then the dev asked why the composer runs once per view at all, and it does not need to** — it is now registered on the two menu views instead of `'*'`, **16 runs a request became 1** (row 9). The dev's challenge is what made it happen: this map had
already built Changes A, D, E, F and ticket 17, so stopping at a spec for G was not consistent with any of
it. Round 1 also settled two standing rules: **no query-count
ceiling** (milliseconds decide; a count is only worth cutting when the query is not real work, and the menu
logic is not rewritten), and the 19 once-per-request lookups get **their own ticket 25**.

**Scope widened 2026-08-21, by the dev: the ~5 MB page is now this map's to shrink.** It was ruled
past the destination — ticket 21 step 4 and [report.md](measurements/report.md) both said so, the
report in the words "no change on this map was ever going to move the 5 MB, and none should be added
to try." The dev has overridden that. So the destination now also covers **response size**, not only
response time. The old ruling stays written down as history in both files.

**Ordering, set by the dev 2026-08-21: [ticket 22](issues/22-reduce-page-size.md) before
[ticket 17](issues/17-plain-columns-experiment.md).** Ticket 22 had been written up as running *after* 17.
The dev reversed it, and both were resolved the same day, so the ordering is spent.

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
measured. Ticket 17 was the last thing on this map until 2026-08-21; the last thing is now
[ticket 22](issues/22-reduce-page-size.md), which runs after it.

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
  is off the table. **Settled 2026-08-21 by [ticket 17](issues/17-plain-columns-experiment.md), which
  took the real fix instead: `approval_status` is no longer encrypted at all.** It is plain
  `varchar(20)` with an index, so the pending filter runs in SQL and the counter went from ~4.4-4.8 s
  to ~380 ms. **`username` stays encrypted** — it holds JSON `{email,name}` whose name is printed in
  13 blade files, and it was never the expensive half. The `~0.5 s local / ~2 s at 60,000 rows` figure
  in [spec.md](spec.md) §4 was **wrong by about double**: it costed both columns for every row, but
  `username` is only decrypted for rows already found pending. The measured figure was 15,988 values /
  320-334 ms, now 2,127 / 45-58 ms. Which columns are plain is now data, not code:
  `config('app.PLAINTEXT_COLUMNS')`, read by `encryptStringx()`, keyed `table.column`.
- **Nothing is rewritten in place. Set by the dev 2026-08-20.** Every improvement is a new function
  beside the old one so both can run on the same page and be compared; the old one is deleted later,
  once the new one is proven. Names are PSR-1 / PSR-12 — classes `StudlyCaps`, **methods `camelCase`**,
  constants `UPPER_SNAKE_CASE`, plain procedural functions `snake_case`. Good old name -> add `x`; bad
  old name -> suggest a better one and get it approved. One function, one concern, no copied blocks. In
  doubt, ask. **All names are now decided and live in [names.md](names.md)** — the one file to edit if a
  name changes; every session writing code reads it first. Rule in [CLAUDE.md](../../CLAUDE.md).
- **Page weight is measured too, set by the dev 2026-08-20 (second pass).** Every row also records the
  size of the page, not only the time. Three numbers, because they answer different questions: **document
  bytes**, **total transfer bytes**, and **request count**. Reason it was added: the dev sees ~5 MB on both
  old and new and the report only ever showed a byte figure for one row. Both are true — report row 5's
  71 KB → 61 KB is the **HTML document**, the 5 MB is document plus 53 assets, so a 10 KB saving is 0.2 %
  of it and cannot show. [Ticket 21](issues/21-page-weight-measurement.md) adds the column, backfills the
  rows already taken, and attributes the 5 MB.
- **Page size is in scope, set by the dev 2026-08-21.** The ~5 MB is this map's to shrink, not a
  follow-on effort. Reverses ticket 21 step 4 and the report's own ruling.
  [Ticket 22](issues/22-reduce-page-size.md) decides which cuts get made and in what order; it runs
  last, after [ticket 17](issues/17-plain-columns-experiment.md). Three findings already measured and
  written into that ticket: IIS gzip only engages from the **second** request for a file, so a cold
  cache serves full uncompressed bytes (apexcharts 486 KB -> 126 KB); the HTML document is not
  compressed at all; and content-hashed build assets carry no `Cache-Control`, so a returning user
  pays a 304 round-trip per file.
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
- **Character set and collation — SETTLED 2026-08-20 by
  [ticket 20](issues/20-migration-portability.md): a migration never names one.** The dev's call: the
  collation name depends on the client's database type and version, so a migration cannot know it. The
  `contract_party_data` conversion is therefore **not a migration** — it is
  [database/manual/001-contract-party-data-innodb-utf8mb4.sql](../../database/manual/001-contract-party-data-innodb-utf8mb4.sql),
  run by hand at deployment by someone who can see that database. It carries `utf8mb4_unicode_ci` plus a
  check at the top telling the runner to compare against `contracts` and change it if it differs — which
  it will on 7 of the 8 known client databases. This **crosses the [CLAUDE.md](../../CLAUDE.md) "every
  schema change is a migration" rule**, knowingly, because the alternative was a migration that guesses.
  Only [migration 1](../../database/migrations/2026_08_20_000001_add_index_to_approval_contracts_contract_id.php)
  stays a migration: it adds one index and names no charset or collation.
  **Both applied to the dev database 2026-08-21**, by the dev's go-ahead, through
  [`php artisan contract:convert-party-data`](../../app/Console/Commands/ConvertPartyDataCollation.php)
  — the script the dev asked for, collation passed in, `utf8mb4_unicode_ci` by default, checks-only
  unless `--apply`. 6,940 rows in and out; approvals join 114 ms -> 23 ms, party join 42 ms -> 32 ms
  ([report.md](measurements/report.md) rows 4 and 7). Production is the dev's to do at deployment with
  that server's collation. Whatever collation a client
  uses, it must be case-**insensitive** — two queries compare `contract_party_type` against lowercase
  `'internal'` and only work because case is ignored.
  **Column widths: `varchar(32)`**, on exactly two columns (`contract_party_type`,
  `contract_party_location_id`). `party_address` stays `TEXT`. 32 rather than 20 so a fourth party type
  needs no second maintenance window. The reasoning below was the earlier version, kept for the history.
- ~~**Character set and collation — superseded 2026-08-20.**~~ The rule below said name `utf8mb4_unicode_ci`
  explicitly in every migration. The dev rejected that on review: this app is installed for different
  clients on different database servers, and a named collation may not exist there. Checking proved him
  right on stronger grounds than version support — of the **8 client databases on this machine**,
  `approval_contracts` is `utf8mb4_unicode_ci` in **one** (`apollo_contracts_expense`) and
  `utf8mb4_general_ci` in the other **seven**, which is also both databases' own default. So the named
  value would have *created* the mixed-collation problem it was meant to prevent. Ticket 20 sets the
  replacement rule and rewrites both migration files. The original text follows for the history.
- ~~**Character set and collation, decided 2026-08-20.**~~ Everything this effort creates or changes uses
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
- **Step 11 and two page-size cuts, applied 2026-08-21 on the dev's instruction** (not a ticket - direct
  work, recorded here so the map stays the index). `dashboardSummary()` is on the live URLs `GET ''` and
  `POST 'filterDash'`; `dashDetails()`, `viewDashboard1.blade.php`, the compare command, the old
  actionable-items pair and the `?oldApprovalStatus=1` flag are **deleted**, and the `x` suffixes are gone.
  Controller **948 -> 639 lines**. The sidebar highlight works again as a side effect. `encryptStringx()`
  **keeps its `x`** - 525 call sites on `encryptString()` against 58, with a different meaning for the
  second argument. Then ticket 22 cuts 2 and 3: **customizer off** (users lose dark mode, accepted) and
  **ApexCharts lazy-loaded** - **548 KB off the critical path, 56 requests -> 36**, neither needing a
  rebuild. Rows 10-13 of [report.md](measurements/report.md); spec sections **8c** and **8d**.
  **Three things fell out of this that nobody had written down:** `web.config` was **git-ignored**, so every
  IIS change was invisible to git - now tracked, with a warning about the production server's own copy;
  **the built assets are untracked**, so a checkout gives a server no CSS or JS at all; and **`public/build`
  and `build/` were two separate 33 MB copies** whose manifests had **already drifted** - Laravel read one,
  IIS served the other, so a rebuild would have 404'd four files and broken the datatable on 56 pages.
  **That last one is now fixed** (rows 16-17): one rewrite rule in `web.config` points
  `/contracts/build/*` at `public/build`, the root copy is **deleted**, 78 MB freed, and `npm run build`
  is finally sufficient on its own. The `<location>` cache rule had to move to `public/build/assets` with
  it - `<location>` matches the path *after* a rewrite, which cost the `Cache-Control` header until it was
  measured and fixed. All of it is in [DEPLOYMENT.md](../../DEPLOYMENT.md), the new production checklist.
  **One retraction on the record:** an earlier note in this effort reported every counter on the contracts
  list page reading 0 and raised it as a suspected `whereIn` 1000-parameter bug. It was a page read
  mid-load. Fully loaded the numbers match the dashboard exactly, the task was withdrawn, and the lesson
  is written into [report.md](measurements/report.md) row 17: never read a page's numbers straight after
  navigating.
- [141 queries a request — what gets cut, and does it belong to this map?](issues/23-per-request-query-decision.md) —
  **cache the menu composer, change nothing else.** In scope, both halves, in this map: the app cache is
  already on (`CACHE_DRIVER=file`) and the only existing precedent is
  [ContractOptionListController.php:78](../../Modules/Contract/app/Http/Controllers/ContractOptionListController.php:78).
  **108 queries and 391 ms become 7 on a cache miss and 0 on a hit**; the whole request goes 141 -> 33. Key
  on **role only**; `Schema::hasTable()` **inside** the cache, because it is 226 ms of the 391; **cleared on
  write** from the five write points in `MenuConfigController` rather than paying a version-stamp query. New
  class `App\Menu\MenuDataResolver` ([names.md](names.md) §7), and the old closure body becomes the
  cache closure's body so there is no second copy to keep in step. Written up as **Change G in
  [spec.md](spec.md) §8b**.
  **The reversal worth remembering:** round 2 ruled the top-menu lookup dead code and proposed deleting 45
  queries a request. The dev challenged it and **round 4 reversed it** — the three-step fallback finds no
  row for a Super Admin because nobody made one, and none for the top menu for the same reason. That is the
  fallback working, not dead code, and after caching it costs 3 queries per miss. Both rulings stay written
  down. **One thing applied**, separately and not for performance: the missing
  `@if($menuData[1]->menu ?? false)` guard at
  [horizontalMenu.blade.php:8](../../resources/views/layouts/sections/menu/horizontalMenu.blade.php:8), which
  would have failed every page if anyone had ever switched the layout to horizontal. Compile-checked.
  **Not done, deliberately:** removing the horizontal layout altogether — 9 items including 6 stock
  template files, a `menu_type` enum change and the menu admin screen. Not performance work, not this map's.
- [What do we gain if `approval_status` and `username` are just plain columns?](issues/17-plain-columns-experiment.md) —
  **`approval_status` only. `username` stays encrypted.** The ticket's own premise was wrong: it costed
  both columns for every row, but the code only decrypts `username` for rows already found pending, so
  the real figure was **15,988 values / 320-334 ms, not 27,734 / 0.49 s** — one column, and it was
  `approval_status`. Converting that one and indexing it lets SQL cut 13,861 rows to 2,127 before PHP
  sees them, which removes the cost without touching `username` at all. Measured **~4.4-4.8 s -> ~380 ms**
  for the counter, six numbers identical, [report.md](measurements/report.md) rows 8-8c. Encryption there
  was habit, not a rule (dev, 2026-08-21) — the same approver emails already sit in plaintext in
  `approval_group_approvers` and `financial_limit`. **Reads needed no change**: `decryptString()` returns
  anything not starting with `ey` untouched, so all 63 read sites cope with plain values and with a
  half-converted table, which is also why mixed data needs no handling. **56 of the 61 write sites**
  now call `encryptStringx()` — the other 5 write `approval_parties`, a different table, and stay
  encrypted. Four tables here have an `approval_status` column, so the config key is written
  `table.column`; 8 sites had also been passing an email where the column name belongs.
  **`username` is left encrypted deliberately** — it holds JSON `{email,name}` whose name is printed in
  13 blade files, so 2,127 decryptions a load remain as the floor. **Found a seeder bug, not an app bug**:
  `PerfDatasetSeeder` wrote `Pending` and bare emails where the app writes `pending` and JSON, so
  report row 3's decrypt figures were measured against impossible data. Fixed and re-seeded.
  **Swapped in as the default the same day** — `?oldApprovalStatus=1` selects the old counter and is the
  way back if a deployment runs code before conversion; [spec.md](spec.md) §10 step 11 now lists what is
  swapped against what is still to delete.
- [Who issues the 19 auth-shaped queries left over per request?](issues/24-attribute-remaining-overhead.md) —
  **all 19 attributed, and every one is once-per-request.** None scale with the 15 views, so per-view
  caching buys nothing here — this group is a different problem from the menu composer's 108.
  `Helpers::userInfo()` ([Helpers.php:254](../../app/Helpers/Helpers.php:254)) is 9 of them, called from 9
  hand-written sites with no memo; `getEntityBranches()` adds 2 more `ContractUsers` reads via
  `BranchScope` and `DepartmentScope`, and those two scopes also repeat the `UserCredential` token lookup
  once each just to get a username back — so **auth is only 1 of the 3** `UserCredential` reads. The 5
  `file_storage` reads are **not avatars**: they are `fileStorageType()`
  ([helpers.php:112](../../app/helpers.php:112)), which hits the database on every call; the value is
  `Google` here, which is why it is 5 and not 4. The 2 `SHOW TABLES` are a **second introspection caller**,
  `Controller::checkTablesConfiguration()` ([Controller.php:381](../../app/Http/Controllers/Controller.php:381)),
  called twice from `ContractSessionMiddleware` — **and its required-tables list is empty, so both runs
  prove nothing.** A request-scoped memo on those three helpers plus dropping the duplicate
  `checkTablesConfiguration()` call takes 19 down to about 4. Nothing applied; the decision is
  [ticket 23](issues/23-per-request-query-decision.md). Caveat: measured on a Super Admin session, which
  takes an early return in `getEntityBranches()` — a normal user would issue more.
- [Make the page smaller — what do we cut, and in what order?](issues/22-reduce-page-size.md) — six cuts,
  ordered, nothing applied. **~1.4 MB is config, not code**: `frequentHitThreshold="1"` (static gzip never
  helps a first visit — 1.25 MB raw on request 1, 343 KB gzipped from request 2), dynamic compression for
  the uncompressed 63 KB document, and `Cache-Control: immutable` on content-hashed assets. Both files
  written for review in [config-proposals/](config-proposals/), each rule labelled folder-level or
  parent-level, because `contracts/` sits under the GOAL app. Then: customizer **off** (37 KB, users lose
  dark mode), ApexCharts **lazy-loaded** not swapped (486 KB off first paint, no rebuild), **fa-brands +
  fa-regular dropped** (118 KB; only real casualty is `fab fa-google` at verticalMenu.blade.php:138 — the
  rest is a stock demo page), **language switcher removed** (33 KB), Tabler font subset **deferred**. Items
  4-6 are gated on [ticket 07](issues/07-asset-pipeline-decision.md)'s missing root `vite.config`.
- [Should migrations name a collation and a column width at all?](issues/20-migration-portability.md) —
  **No, and the conversion stops being a migration.** The dev rejected the hardcoded
  `utf8mb4_unicode_ci` on review, and checking proved him right harder than expected: of the **8 client
  databases on this machine**, `approval_contracts` is `utf8mb4_unicode_ci` in **one** and
  `utf8mb4_general_ci` in **seven** — which is also both databases' own default — so the named value
  would have *created* the mixed-collation problem it was written to prevent. Client widths already
  differ too (`approval_status` is `varchar(1000)`, `varchar(250)` and `varchar(100)` across clients).
  **Dev's call: `contract_party_data`'s conversion is a hand-run deployment script**, not a migration —
  [database/manual/001-...sql](../../database/manual/001-contract-party-data-innodb-utf8mb4.sql), with a
  pre-flight check against `contracts`. Knowingly crosses the CLAUDE.md migration rule. **Migration 1
  survives** as a migration (one index, no collation named), still not applied. **Widths: `varchar(32)`
  on exactly two columns**; `party_address` stays `TEXT`. **And there was no status column to size** —
  `approval_status_plain varchar(20)` died with the shadow columns; the live `approval_status` is already
  `varchar(1000)`, so ticket 17 never has to widen anything either. New fact: `contract_party_type` is
  compared against lowercase `'internal'` at
  [ContractController.php:725](../../Modules/Contract/app/Http/Controllers/ContractController.php:725)
  and [:4358](../../Modules/Contract/app/Http/Controllers/ContractController.php:4358), so the collation
  must stay case-insensitive or those two queries break silently.
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

- [What changes in `goalapp_apollo` would help performance?](issues/18-goalapp-apollo-note.md) —
  **The dashboard never touches `goalapp_apollo`. Not one query.** One connection in `config/database.php`,
  no `$connection` on any model, no `DB::connection()` in app code, no database name in any raw SQL. So
  both worries in the ticket are unfounded: `menu_configs` and `admin_settings` are in **our** database,
  which makes the 92-query menu composer fix ordinary in-scope work, and the user/role/branch tables are
  ours too. Nothing cheap is stuck behind the "do not touch" rule. The note still earns its keep because
  that database shows how much our local numbers flatter us: its `contract_party_data` is MyISAM with no
  indexes (**7x** on the visibility query), its `contracts` is **71.6 MB for 2,783 rows** against our
  6.5 MB for 3,018 (**9x slower to scan**, cause unexplained), and its `approval_contracts` holds only
  **21 rows** — so the 5x approvals index we measured is worth nothing to a client. Read-only throughout.
  Note in [research/goalapp-apollo-note.md](research/goalapp-apollo-note.md).
- [Measure the page weight, old against new](issues/21-page-weight-measurement.md) — **The dev was right
  and the report was misleading.** One session, both pages alive, cold and warm: the whole page moved
  **754 bytes out of 2.9 MB — 0.03 %**. The document did shrink 71,644 -> 63,274, but the options came
  back as a 7,616-byte `option-lists` request, so **Change F is not a weight change at all** — it moves
  bytes off the critical path, and that is its whole value. Page weight is now a standing part of
  [report.md](measurements/report.md) (rows 21a-21d); rows 0 and 2b could only be backfilled for document
  bytes, their transfer totals being the weight of 404s from the `public/hot` era. The page is **2.9 MB,
  not 5 MB**, and it is **all stock Vuexy template and chart JS** — CSS 33 %, JS 32 %, fonts 28 %, five
  files making 73 % of it, none of it contracts code. A returning user pays **71 KB**, not 2.9 MB.
  **Biggest finding, not asked for: compression barely works.** Static gzip is configured but only engages
  from the **second** request for a file (1,252,656 -> 343,147 bytes, 3.65x), so a cold visit that asks
  once per asset gets none of it; the **HTML document is never compressed on any request** (dynamic
  compression is a separate setting and it is off); and build assets carry an `ETag` but **no
  `Cache-Control`**, so a returning user pays 54 conditional round-trips on filenames that are already
  content-hashed. This confirms ticket 22's three findings independently. **Step 4 of this ticket answered
  "shrinking the page is past the destination" and the dev overrode it the same day** — page size is now in
  scope, and the cuts belong to [ticket 22](issues/22-reduce-page-size.md). One correction recorded in
  both files: an earlier pass called the single gzipped reading a measurement artifact and concluded
  "compression is off"; it was real gzip, and the wrong conclusion is left visible rather than removed.

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
<!-- The per-request overhead patch GRADUATED 2026-08-21: ticket 11 has reported, so the scope question
     can now be phrased. It is [ticket 23](issues/23-per-request-query-decision.md), blocked by
     [ticket 24](issues/24-attribute-remaining-overhead.md). -->
- Whether the AJAX dropdown conversion extends to `contractList` and the other pages, now that the
  dashboard-first decision is made and the endpoints are specified as shared. Depends on the dashboard
  conversion proving out.

## Out of scope

- **The session and access queries that run on every page** — the 19 once-per-request lookups.
  `Helpers::userInfo()` read 9 times, `fileStorageType()` 5 times, the login token 3 times, and
  `SHOW TABLES` twice. Ruled out of scope 2026-08-21 by the dev after four rounds of grilling on
  [ticket 25](issues/25-memo-per-request-lookups.md), now closed. **Why:** this is the login and access
  code, not the dashboard's, and it runs on every page in the app. Ticket 23 kept the menu composer for
  the same reason it could have been ruled out, because that one was 391 ms and the cache pattern already
  existed. This one is 45 ms on the only session anybody measured. The six decisions already taken and
  every fact found are in [.scratch/session-optimisation/spec.md](../session-optimisation/spec.md), so
  the later effort starts from those.

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
  precedent; it is not the dashboard, so it is not this effort's to fix. **It now has its own effort,
  2026-08-21, on the dev's call:** [.scratch/wherein-1000-bug/spec.md](../wherein-1000-bug/spec.md).
  That spec covers all four places the bug lives, not only the backfill.
- Local MySQL `root` having an empty password with ~40 databases present, several appearing to hold
  real client data. Noted once; a security matter for a separate effort, not a performance decision.
