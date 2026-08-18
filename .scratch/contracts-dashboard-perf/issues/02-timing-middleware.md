# Build the local-only timing middleware

Type: task
Status: resolved
Blocked by: —

## Question

We have no server-side timing at all, and a browser waterfall only says the response was slow, not
why. `vendor/` ships with this project, so Clockwork or Debugbar as `require-dev` risks one careless
build shipping a web-accessible debugger on a contracts system. The dev chose the zero-vendor option.

Build a middleware in `app/Http/Middleware/` that, gated on `APP_DEBUG && APP_ENV === 'local'` and
registered from a local-only service provider, records per request:

- total wall-clock ms
- query count and total DB ms (via `DB::enableQueryLog()` / `DB::getQueryLog()`)
- the N slowest queries with bindings elided
- a count of *duplicate* queries (same SQL, different bindings) — this is what will expose the N+1s
- time to controller entry vs. time in blade rendering, so asset/middleware/PHP/blade costs separate

Write to `storage/logs/`, not the response body — the response is a full HTML page and injecting into
it would distort what we are measuring.

Constraints: no Composer packages, nothing that survives into a production build, no changes to
`composer.json`. It must be trivially removable.

This is deliberately *doing* rather than deciding, and it earns its place by unblocking
`05-baseline-attribution`, which cannot be answered without it.

## Answer

Built and verified 2026-08-14. Zero Composer packages; `composer.json`/`composer.lock` untouched.

### Files

| File | Purpose |
|---|---|
| `app/Perf/PerfRecorder.php` | aggregation, bounded — folds query events into counters rather than retaining statements |
| `app/Http/Middleware/PerfTimingMiddleware.php` | outermost pipe; writes the record in `terminate()` |
| `app/Providers/PerfTimingServiceProvider.php` | gating, event wiring, middleware injection |
| `config/app.php:179` | the single registration line |

Placed in **both** the worktree and the live app dir `D:\Contract-Expense\GOALv4\contracts` (IIS serves
the latter, so the code must exist there to run).

**Gating:** inert unless `APP_DEBUG` is truthy **and** `trim(APP_ENV) === 'local'` — the `trim()` matters,
the local `.env` has a trailing space after `APP_ENV=local`. Also skips `runningInConsole()`.

**Removal:** delete the three files and drop the one line from `config/app.php:179`.

**Log:** `storage/logs/perf-Y-m-d.log`, one JSON object per line. Nothing is written into the response
body.

**Design note:** uses `DB::listen()` rather than `DB::enableQueryLog()`/`getQueryLog()`, because the query
log retains every statement and its bindings for the whole request — at ~12k queries that is tens of MB
allocated inside the thing being measured. The recorder keeps bounded aggregates and reports
`shapes_truncated` if it ever caps.

**Phase boundaries achieved:** `bootstrap` → `routing` → `route_middleware` → `controller` →
`view_render` → `send_terminate`, via `RouteMatched`, a marker middleware appended to the matched route
during `RouteMatched` (which lands before `runRouteWithinStack` gathers middleware), `PreparingResponse`,
and `ResponsePrepared`, cross-checked against the first `composing:` event.

### First real measurement — dashboard `/` at N=18

Two runs:

| Phase | run A | run B |
|---|---|---|
| **bootstrap** | **1,072 ms** | **1,133 ms** |
| routing | 34 | 68 |
| route middleware | 149 | 163 |
| controller | 429 | 252 |
| view render | 887 | 461 |
| send + terminate | 1 | 5 |
| **total** | **2,573 ms** | **2,084 ms** |
| queries | 164 | 164 |
| DB total | 1,109 ms | 507 ms |

First view composed: `contract::dashboard.viewDashboard1`, 13 views composed.

**164 queries, 23 distinct shapes, 12 duplicate groups, 153 duplicate executions.**

### This overturns the map's working assumption

**Bootstrap alone is ~1.1 s — over half the response, before routing begins.** Nothing in the dashboard's
own code has run at that point. The top duplicate offenders are mostly *not* the dashboard either:

| n | total ms | query |
|---|---|---|
| 26 | 33.6 | `menu_configs where menu_type = ? and role = ?` |
| 26 | 49.6 | `menu_configs where menu_type = ? and LOWER(role) = ?` |
| 13 | 8.9 | `menu_configs where menu_type = ? and role is null` |
| **13** | **197.9** | **`information_schema.tables where table_schema = 'apollo_contracts_expense'`** |
| 18 | 25.7 | `contract_categories where id = ?` |
| 18 | 22.6 | `contract_type where contract_type_id = ?` |
| 9 | 41.8 | `ContractUsers` with 4 `AES_DECRYPT` columns |
| 5 | 13.0 | `UserCredential` with 5 `AES_DECRYPT` columns |

- **`menu_configs` is queried 65 times per request.** Menu building, not the dashboard.
- **`information_schema.tables` is queried 13 times for 198 ms**, plus a `SHOW TABLES` at 28 ms — schema
  introspection on every page load, ~226 ms of pure waste. Slowest single query in the request (37.5 ms)
  is one of these.
- `contract_categories` 18× and `contract_type` 18× **are** the dashboard's N+1 — exactly N=18, confirming
  the `4·N` pattern — but together they cost only ~48 ms at this scale.

So at N=18 the dashboard's stage-counting and N+1 are **not** the leading cost. Bootstrap, menu building,
and schema introspection are. The `4·N` pattern will become dominant at N=3,000 (~12,000 queries), so both
matter — but they are separate problems and the map only had one of them.

`05-baseline-attribution` now has its N=18 column filled and a much better question to chase: **what is
happening during 1.1 s of bootstrap?**
