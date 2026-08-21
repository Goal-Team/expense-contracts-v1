# Who issues the 19 auth-shaped queries left over per request?

Type: task
Status: resolved
Blocks: [23-per-request-query-decision.md](23-per-request-query-decision.md)
Blocked by: —

## Question

[Ticket 11](11-per-request-overhead.md) attributed 92 of the per-request queries to one caller —
`MenuServiceProvider`'s `View::composer('*')`. At 15 composed views that same closure now accounts for
**108**. But the measured request also carries a group nobody has traced yet:

| shape | n | ms |
|---|---|---|
| `ContractUsers` with 4 `AES_DECRYPT` columns | 9 | 60 |
| `file_storage` — `select type where id = ?` | 5 | 4 |
| `UserCredential` with 5 `AES_DECRYPT` columns | 3 | 6 |
| `SHOW TABLES` | 2 | 9 |

Source: last `/dashboard-summary` request in `storage/logs/perf-2026-08-21.log` — 141 queries,
17 distinct shapes, 135 duplicate executions.

Cheap in milliseconds, but 19 queries repeating the same lookup, and the two decrypting shapes touch the
user tables on every page in the application. Before [ticket 23](23-per-request-query-decision.md) can
decide what to cut, we need the call sites.

Establish, with evidence:

1. **Who runs the `ContractUsers` lookup 9 times.** Same user, same predicate every time — find whether it
   is one helper called from several views, a middleware, or a blade partial.
2. **Who runs `UserCredential` 3 times** — auth resolution is the obvious suspect; confirm it.
3. **What the 5 `file_storage` reads are.** Avatar or logo lookups per view is the guess.
4. **Whether the 2 `SHOW TABLES` belong to the menu composer's `Schema::hasTable()`** or to a second
   introspection caller. Ticket 11 counted `information_schema.tables` but not this.
5. **Which are per-view (scale with view count) and which are once-per-request.** This is the number that
   matters — a per-view query at 15 views is a caching problem, a once-per-request query is not.

Report findings; **do not fix anything.** What to do is [ticket 23](23-per-request-query-decision.md).

## Answer

**Measurement caveat, stated up front.** The numbers below come from re-reading the same
`/dashboard-summary` entry the question quotes (`storage/logs/perf-2026-08-21.log`, ts
`2026-08-21T17:10:05+05:30`, 141 queries, 15 views composed). The millisecond figures in the question
were rounded up from an earlier read; the entry itself says ContractUsers **34.1 ms**, `SHOW TABLES`
**16.9 ms**, `file_storage` **3.9 ms**, `UserCredential` **6.1 ms**. Milliseconds on this machine vary
by ~3x between sessions — trust the counts, not the times.

**No instrumentation was added.** Every line below is static reading. It closes arithmetically, which
is why no stack trace was needed (see "The count adds up exactly").

### The table

| shape | n | caller | per-view or per-request |
|---|---|---|---|
| `ContractUsers` 6 cols | 9 | `Helpers::userInfo()` — 9 separate call sites, listed below | per-request (9 fixed call sites) |
| `ContractUsers` 7 cols (`BusinessFunctionAccess`) | 2 | `Helpers::getEntityBranches()` [Helpers.php:314](../../../app/Helpers/Helpers.php:314), from two global scopes | per-request |
| `ContractUsers` 2 cols (`id`, `AccessScope`) | 1 | [ContractSessionMiddleware.php:65](../../../app/Http/Middleware/ContractSessionMiddleware.php:65) | per-request |
| `UserCredential` | 3 | 1 x middleware, 2 x `getEntityBranches()` | per-request |
| `file_storage` | 5 | 2 x `storageAvailableCheck()`, 3 x the vertical menu partial | per-request |
| `SHOW TABLES` | 2 | `Controller::checkTablesConfiguration()`, called twice by the middleware | per-request |

**None of the 19 are per-view.** Not one of them scales with the 15 composed views. The menu composer
is the only per-view offender, and ticket 11 already owns it. Caching keyed on "once per request"
would collapse 19 queries to about 4; caching keyed on the view count would gain nothing here.

### 1. The 9 `ContractUsers` reads are all `Helpers::userInfo()`

[app/Helpers/Helpers.php:242](../../../app/Helpers/Helpers.php:242) defines it; the query is at
[:254](../../../app/Helpers/Helpers.php:254) and its column list — `id`, `AccessLevel`, `branchhead`,
`email`, `FirstName`, `UserName` — matches the logged shape exactly. The `AccessScope`/`Status`
predicates come from `UserContractScope`, bolted on at
[AddUsers.php:20](../../../app/Models/AddUsers.php:20).

**There is no memo.** Every call is a fresh query. Nine calls, nine queries:

| # | call site |
|---|---|
| 1 | [ContractDashboardController.php:434](../../../Modules/Contract/app/Http/Controllers/ContractDashboardController.php:434) — `myTaskCounts()`, one call from `dashboardSummary()` |
| 2 | [viewDashboardSummary.blade.php:294](../../../Modules/Contract/resources/views/dashboard/viewDashboardSummary.blade.php:294) — "Welcome Back" name |
| 3 | [viewDashboardSummary.blade.php:468](../../../Modules/Contract/resources/views/dashboard/viewDashboardSummary.blade.php:468) — `data-user` on the pending tile |
| 4 | [viewDashboardSummary.blade.php:477](../../../Modules/Contract/resources/views/dashboard/viewDashboardSummary.blade.php:477) — same, inprogress tile |
| 5 | [viewDashboardSummary.blade.php:486](../../../Modules/Contract/resources/views/dashboard/viewDashboardSummary.blade.php:486) — same, completed tile |
| 6 | [verticalMenu.blade.php:156](../../../resources/views/layouts/sections/menu/verticalMenu.blade.php:156) — sidebar name |
| 7 | [verticalMenu.blade.php:159](../../../resources/views/layouts/sections/menu/verticalMenu.blade.php:159) — sidebar email |
| 8 | [navbar.blade.php:95](../../../resources/views/layouts/sections/navbar/navbar.blade.php:95) — dropdown name |
| 9 | [navbar.blade.php:98](../../../resources/views/layouts/sections/navbar/navbar.blade.php:98) — dropdown email |

So it is **not** one shared helper called per view, and **not** a view composer. It is one shared
helper written out nine times by hand — four times in the dashboard view, twice in the sidebar, twice
in the navbar, once in the controller. Three call sites print the same `id`; four print a name or an
email. Both layout partials render unconditionally on this page.

A separate 2-column `ContractUsers` read (`id`, `AccessScope`) runs once in the middleware at
[ContractSessionMiddleware.php:65](../../../app/Http/Middleware/ContractSessionMiddleware.php:65).
Different shape, so it does not appear in the group of 9.

### 2. `UserCredential` — auth resolution, but only one of the three

Auth resolution is confirmed for **one** of them:
[ContractSessionMiddleware.php:60](../../../app/Http/Middleware/ContractSessionMiddleware.php:60)
looks the user up by `authtoken` on every request.

The **other two are not auth**. They come from `Helpers::getEntityBranches()`, which repeats the same
token lookup at [Helpers.php:308](../../../app/Helpers/Helpers.php:308) purely to turn the token back
into a username it then uses for the `ContractUsers` read at
[:314](../../../app/Helpers/Helpers.php:314). It runs twice because two global scopes call it:

- [BranchScope.php:17](../../../app/Models/Scopes/BranchScope.php:17), fired by
  `BranchUser::pluck('id')` at [ContractVisibilityQuery.php:110](../../../Modules/Contract/app/Services/ContractVisibilityQuery.php:110)
- [DepartmentScope.php:17](../../../app/Models/Scopes/DepartmentScope.php:17), fired by
  `EntityBusiness::pluck('id')` at [ContractVisibilityQuery.php:125](../../../Modules/Contract/app/Services/ContractVisibilityQuery.php:125)

`ContractVisibilityQuery` memoises both plucks, so each scope fires once and the count stops at two.
**On a page that queries a scoped model more than once, this pair grows with it** — the scope has no
cache of its own.

Both `getEntityBranches()` calls also bail out early at
[Helpers.php:323](../../../app/Helpers/Helpers.php:323) — this user is Super Admin or Admin, so it
returns an empty list before touching `GeographicalHierarchy` or `branch`. That early return is why
only two extra queries appear per call and not five.

### 3. The 5 `file_storage` reads — not avatars, storage-provider checks

All five are `fileStorageType()` at [app/helpers.php:112](../../../app/helpers.php:112), which reads
`file_storage.type` from the database every single call and caches nothing.

`file_storage.type` is **`Google`** on this install, and that matters — it changes the count:

| where | calls | why |
|---|---|---|
| [Controller.php:430](../../../app/Http/Controllers/Controller.php:430) | 1 | the `!= "Local"` test |
| [Controller.php:431](../../../app/Http/Controllers/Controller.php:431) | 1 | only reached **because** the type is not `Local` — asked again to build the `ConfigStorageConfig` lookup |
| [verticalMenu.blade.php:172](../../../resources/views/layouts/sections/menu/verticalMenu.blade.php:172) | 2 | one call for the logo icon, another for the logo class, on the same line |
| [verticalMenu.blade.php:177](../../../resources/views/layouts/sections/menu/verticalMenu.blade.php:177) | 1 | prints the provider name in the sidebar |

The first two are entered from
[ContractSessionMiddleware.php:87](../../../app/Http/Middleware/ContractSessionMiddleware.php:87)
(`storageAvailableCheck()`). **On a `Local` install this shape would be 4, not 5.**

### 4. The 2 `SHOW TABLES` are not the menu composer

They belong to `Controller::checkTablesConfiguration()`, whose first line is
`DB::select('SHOW TABLES')` at
[Controller.php:381](../../../app/Http/Controllers/Controller.php:381). The middleware calls it
**twice in one request**:

- [ContractSessionMiddleware.php:54](../../../app/Http/Middleware/ContractSessionMiddleware.php:54)
- [ContractSessionMiddleware.php:85](../../../app/Http/Middleware/ContractSessionMiddleware.php:85)

The menu composer's `Schema::hasTable()` produces the `information_schema.tables` query, which the log
shows separately at 15x / 150.6 ms — and 15 is exactly the composed-view count, so ticket 11's
attribution still holds. `SHOW TABLES` is a **second, unrelated introspection caller**.

Worth noting for ticket 23: the required-tables list inside `checkTablesConfiguration()` is an
**empty array**. The function reads the whole table list, loops over nothing, and always returns
`true`. Both `SHOW TABLES` executions and 16.9 ms are spent proving nothing.

### 5. Per-view vs once-per-request

**All 19 are once-per-request.** Every one traces to a fixed call site — a middleware line, a
controller line, or a specific line in one of three blade files that each render once. None of them
sits in a `View::composer`, so none multiplies by the 15 views.

What they *do* scale with is call-site count in code, which is a different lever:

| shape | what makes the number bigger |
|---|---|
| `ContractUsers` 6 cols | writing `Helper::userInfo()` in one more place |
| `UserCredential`, `ContractUsers` 7 cols | querying one more model that carries `BranchScope`/`DepartmentScope`/`UserBranchScope` without memoising |
| `file_storage` | writing `fileStorageType()` in one more place, plus one extra if the provider is not `Local` |
| `SHOW TABLES` | the middleware calling `checkTablesConfiguration()` twice |

For ticket 23 that means a **request-scoped memo on three functions** — `Helpers::userInfo()`,
`fileStorageType()`, and `Helpers::getEntityBranches()` — plus dropping the duplicate
`checkTablesConfiguration()` call would take these 19 queries down to about 4. No per-view caching is
involved.

### The count adds up exactly

The log lists only the top 10 duplicate groups but reports `duplicate_groups: 11`,
`duplicate_execs: 135`, `distinct_shapes: 17`, `query_count: 141`. The 10 shown groups sum to 133, so
the hidden 11th group has n = 2 — the 7-column `ContractUsers` shape from `getEntityBranches()`.
Then 135 duplicate executions + 6 single-execution shapes (17 minus 11) = **141**, the exact total.
The 6 singles are the middleware's 2-column `ContractUsers` read, `ConfigStorageConfig`, the contracts
`GROUP BY` counter, the `contract_tasks` counter, `BranchUser::pluck`, and `EntityBusiness::pluck`.
Nothing is left over, so no query in this request is unattributed.

### What I could not settle

- **The hidden 11th duplicate group is inferred, not read.** `PerfRecorder` caps `top_duplicates` at
  10 ([PerfRecorder.php:48](../../../app/Perf/PerfRecorder.php:48)). The n = 2 count and the
  identification as the 7-column `ContractUsers` shape follow from arithmetic and from
  `getEntityBranches()` running twice — both are solid, but the SQL text itself is not in the log.
  Raising `SLOW_KEEP` on a future run would print it.
- **Why the middleware calls `checkTablesConfiguration()` twice** — line 54 runs before the user
  lookup and line 85 again after. It looks like an old edit left behind rather than a deliberate
  re-check, but nothing in the code says so.
- **`file_storage` on a `Local` install would be 4, not 5.** The count depends on the provider row, so
  do not treat 5 as a constant across deployments.
- Everything here is one request by one user, who is Super Admin or Admin. A non-admin user does
  **not** take the early return at [Helpers.php:323](../../../app/Helpers/Helpers.php:323) and would
  add several more queries per `getEntityBranches()` call. Not measured.
