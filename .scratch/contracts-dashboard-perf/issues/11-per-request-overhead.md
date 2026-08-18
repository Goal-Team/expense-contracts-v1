# Attribute the ~1.1s of per-request overhead

Type: task
Status: resolved
Blocked by: —

## Question

[Ticket 02](02-timing-middleware.md) measured the dashboard at N=18 and found the cost is **not** where
this map assumed:

| Phase | ms |
|---|---|
| **bootstrap** | **1,072–1,133** |
| routing | 34–68 |
| route middleware | 149–163 |
| controller | 252–429 |
| view render | 461–887 |
| **total** | **2,084–2,573** |

**Over half the response is spent before routing begins**, with none of the dashboard's own code involved.
And of 164 queries with 153 duplicate executions, the top offenders belong to nobody's feature:

- **`menu_configs` queried 65× per request** — three shapes (`role = ?`, `LOWER(role) = ?`, `role is null`)
  at 26 + 26 + 13 executions, ~92 ms combined.
- **`information_schema.tables` queried 13× for 198 ms**, plus a `SHOW TABLES` at 28 ms. Schema
  introspection on every page load; the slowest single query in the request (37.5 ms) is one of these.
- `ContractUsers` with 4 `AES_DECRYPT` columns, 9×, 42 ms. `UserCredential` with 5 `AES_DECRYPT` columns,
  5×, 13 ms. `admin_settings` 14×.

By contrast the dashboard's own `4·N` N+1 (`contract_categories` 18×, `contract_type` 18×) costs ~48 ms at
this scale.

This is in scope: it is the largest single component of dashboard TTFB, and no dashboard-specific fix can
reach the 2 s target while 1.1 s is spent before the controller runs.

Establish, with evidence:

1. **What happens during the 1.1 s of bootstrap.** Provider registration and booting is the prime suspect
   — enumerate the registered providers (`config/app.php`, package discovery, the five enabled modules'
   providers) and time each one's `register()` and `boot()`. `MenuServiceProvider` and
   `LaravelFileViewerServiceProvider` are worth particular attention. Also check: is config caching in use
   (`bootstrap/cache/config.php`), route caching, and view caching? An uncached config on a project this
   size is a plausible chunk of it.
2. **Who calls `information_schema.tables` 13× per request.** Almost certainly `Schema::hasTable()` or a
   `doctrine/dbal`-style introspection in a provider or a menu/permission layer. Find the call sites.
3. **Who queries `menu_configs` 65×.** Find whether it is one loop over menu items with no caching, or
   repeated independent lookups, and whether a single query or a request-scoped cache would serve.
4. **Whether view render (461–887 ms for 13 views) is blade compilation or data work.** Are compiled views
   cached, or is blade recompiling on every request?
5. **How much of this is Windows/IIS-specific** — opcache enabled? FastCGI process reuse, or a fresh PHP
   process per request? A cold-start-per-request configuration would explain a large constant bootstrap
   cost and would be invisible on a Linux deploy.

Use the existing timing middleware and extend it if needed. Report findings; **do not fix anything** — what
to do about it is the decision in the follow-on ticket, and several of these could have
whole-application blast radius.

## Answer

Measured 2026-08-17 at N=3,018. Instrumentation extended from [ticket 02](02-timing-middleware.md):
`app/Perf/PerfApplication.php` + `app/Perf/PerfBootTimer.php` time each bootstrapper and each provider's
`register()`/`boot()`; `bootstrap/app.php` swaps in `PerfApplication` **only** when
`storage/perf-boot-timing.enabled` exists, so deleting that marker file disables it; a clearly-marked
additive probe block in `viewDashboard1.blade.php` counts the `$approvalsArr` walk without altering the
original loop.

**Measurement caveat, stated up front.** This run reported TTFB 33.5 s / DB 24.5 s where the 2026-08-14 run
reported 14.4 s / 8.3 s — with an **identical 5,654 queries**. The machine varies by ~3× between sessions.
**Trust the query counts, the phase proportions, and the ratios; do not treat absolute milliseconds as
reproducible.** Part of the increase is also the boot instrumentation itself.

### 1. The bootstrap cost is `opcache` being off

```json
{"sapi": "cgi-fcgi", "ini_file": "C:\\xampp\\php\\php.ini",
 "ext_loaded": false, "enabled": false, "cached_scripts": null}
```

**opcache is not even loaded as an extension**, and the request includes **810 files**. Every request
recompiles all 810 from source. That is the dominant, flat, row-count-independent cost.

Compounding it, `bootstrap/cache/` contains **no `config.php` and no route cache** — only module and
package discovery files. Config is parsed from every file in `config/` on every request; routes are
rebuilt every request.

Bootstrapper breakdown (total 1,634 ms):

| Bootstrapper | ms |
|---|---|
| **BootProviders** | **717.5** |
| RegisterProviders | 145.2 |
| LoadConfiguration | 77.1 |
| LoadEnvironmentVariables | 53.0 |
| RegisterFacades | 4.8 |
| HandleExceptions | 1.7 |

**51 providers** boot per request, summing 686 ms. Top offenders: `ApprovalRulesServiceProvider` 114.4 ms,
`App\RouteServiceProvider` 57.5 ms, `LaravelModulesServiceProvider` 51.3 ms, `Carbon\ServiceProvider`
44.4 ms, then the five modules' service and route providers at 24–36 ms each. `register()` is cheaper —
`Nwidart BootstrapServiceProvider` 51.2 ms and `FoundationServiceProvider` 31.0 ms lead.

Note `PerfTimingServiceProvider` itself costs 53 ms of boot — subtract it when reading these numbers.

Peak memory 134 MB.

### 2 & 3. `MenuServiceProvider` alone owns all 92 overhead queries

[app/Providers/MenuServiceProvider.php:25](../../../app/Providers/MenuServiceProvider.php:25) registers
**`View::composer('*', ...)`** — a closure that runs for **every view**. 13 views compose per dashboard
request, and each execution performs, with no caching whatsoever:

- `Schema::hasTable('menu_configs')` at [:33](../../../app/Providers/MenuServiceProvider.php:33) → 1
  `information_schema.tables` query → **13 per request** ✓ exactly the observed count
- `admin_setting('enable_admin_level_menu_config')` at [:36](../../../app/Providers/MenuServiceProvider.php:36)
  → **13–14** `admin_settings` queries ✓
- `$getConfig()` at [:41-68](../../../app/Providers/MenuServiceProvider.php:41), called twice per composer
  (`Vertical` + `Horizontal`), each attempting a three-tier fallback:
  - `where('role', $currentRole)` → 2 × 13 = **26** ✓ matches `role = ?` 26×
  - `whereRaw('LOWER(role) = ?')` → 2 × 13 = **26** ✓ matches `LOWER(role) = ?` 26×
  - `whereNull('role')` → **13** ✓ (only one menu_type falls all the way through)

**13 + 14 + 65 = 92 queries per request, every request, on every page in the application** — recomputing
an identical answer 13 times. The `LOWER(role)` variant also cannot use an index.

This is not a dashboard problem. It is an application-wide problem that the dashboard merely reveals.

### 4. The `$approvalsArr` blade walk costs nothing — because it is empty

Probe output:

```json
{"approvals_walk_ms": 0.02, "approvals_groups": 0, "approvals_rows": 0,
 "approvals_pending_rows": 0, "contracts_visible": 2508,
 "contract_status_map_size": 2508, "counts_all": 2508,
 "contract_types_options": 73, "branch_options": 63}
```

`$approvalsArr` is **completely empty** at N=3,018, so the walk at
[viewDashboard1.blade.php:292](../../../Modules/Contract/resources/views/dashboard/viewDashboard1.blade.php:292)
runs zero iterations. That fully explains why view render *fell* from 461–887 ms to 379 ms — and it means
**[ticket 08](08-query-layer-redesign.md) must not treat the `$approvalsArr` walk as a cost to optimise.**

**But it should be empty, and it is not clear why — this is unresolved and possibly a real bug.** The data
is present and joinable:

| check | result |
|---|---|
| `approval_contracts` total | 13,867 |
| rows joining `contracts` on `contract_id = id` | **13,867** (all) |
| seeded approvals (`contract_id` 100001–103000) | 13,740 |
| seeded contracts visible (internal party in an `entityid=2` branch) | 2,490 |
| …of those, that also have approvals | **2,490 (all)** |

So `ApprovalContracts::whereIn('contract_id', $contractIds)` at
[ContractDashboardController.php:171](../../../Modules/Contract/app/Http/Controllers/ContractDashboardController.php:171)
should match ~11,000 rows for the 2,508 visible ids, and returns none. Ruled out: no global scopes on the
model (`boot()` only adds a `creating` hook), `$approvalsArr` *is* passed to the view, and the probe is
inside `@section('content')` which renders (proven — it reported `contracts_visible: 2508` correctly from
the same scope). The only type asymmetry found is `approval_contracts.contract_id int(11)` vs
`contracts.id bigint(20) unsigned`, which should not defeat `whereIn`.

Spun out as [Why is $approvalsArr empty?](12-approvals-empty.md) — **if this reproduces on real data, the
dashboard's approvals panel is silently blank for every user**, which is a correctness bug, not a
performance one.

### 5. IIS/PHP configuration

SAPI is `cgi-fcgi` against `C:\xampp\php\php.ini` — a XAMPP PHP under IIS FastCGI. With opcache absent,
this is the worst case: no compiled-code reuse regardless of process reuse. Whether FastCGI is recycling
processes is secondary while every request recompiles 810 files anyway. **A production Linux deploy with
opcache on would not show this cost**, which is a strong reason not to generalise the ~1.3 s bootstrap
figure to production without measuring there.

### Candidate fixes, not applied

| Fix | Est. saving | Blast radius |
|---|---|---|
| **Enable `opcache`** in `C:\xampp\php\php.ini` (`zend_extension=opcache`, `opcache.enable=1`) | large share of the flat ~1.3 s | **Local environment only** — a php.ini change, no app code. Cheapest possible win. |
| Cache the `MenuServiceProvider` composer result per request (or memoise `$getConfig`) | 92 queries → ~3 | **Whole application** — every page, every user |
| Drop `Schema::hasTable()` from the hot path | 13 queries, 139–198 ms | Whole application |
| Index or remove the `LOWER(role)` predicate | modest | Whole application |
| `php artisan config:cache` + `route:cache` | 77 ms + routing | Whole application; needs care — cached config breaks `env()` calls outside config files, and this app derives `APP_ENCRYPTION_KEY` from `$_SERVER['HTTP_HOST']` at [config/app.php:7](../../../config/app.php:7), which **will** bake a wrong key into a cached config if cached from CLI |
| Trim the 51 providers / defer module providers | up to ~686 ms | Whole application, highest risk |

The `config:cache` interaction with the hostname-derived encryption key is a genuine trap — cache it from
the wrong context and stored contract data stops decrypting.

### Removing the instrumentation

Delete `storage/perf-boot-timing.enabled` to disable boot timing while leaving the code inert. To remove
fully: delete `app/Perf/`, `app/Http/Middleware/PerfTimingMiddleware.php`,
`app/Providers/PerfTimingServiceProvider.php`, revert the `bootstrap/app.php` hunk, revert the two probe
`@php` blocks in `viewDashboard1.blade.php`, and drop `config/app.php:179`. Both the worktree and
`D:\Contract-Expense\GOALv4\contracts` hold copies.
