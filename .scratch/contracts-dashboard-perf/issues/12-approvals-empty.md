# Why is $approvalsArr empty?

Type: task
Status: resolved
Blocked by: —

## Question

[Ticket 11](11-per-request-overhead.md) found that `$approvalsArr` is **completely empty** on the dashboard
at N=3,018 — the blade walk at
[viewDashboard1.blade.php:292](../../../Modules/Contract/resources/views/dashboard/viewDashboard1.blade.php:292)
runs zero iterations (`approvals_groups: 0, approvals_rows: 0`). That is why view render *fell* when the
dataset grew 168×.

**It should not be empty.** The data is present and joinable:

| check | result |
|---|---|
| `approval_contracts` total rows | 13,867 |
| rows joining `contracts` on `contract_id = id` | **13,867** (all of them) |
| seeded approvals (`contract_id` between 100001 and 103000) | 13,740 |
| seeded contracts visible (internal party in an `entityid = 2` branch) | 2,490 |
| …of those, that also have approvals | **2,490 (all)** |
| `contracts_visible` / `contract_status_map_size` in the same request | 2,508 |

So `ApprovalContracts::select('*')->whereIn('contract_id', $contractIds)` at
[ContractDashboardController.php:171](../../../Modules/Contract/app/Http/Controllers/ContractDashboardController.php:171)
should match roughly 11,000 rows and returns none.

**Already ruled out** — do not redo:

- No global scopes on `ApprovalContracts`; its `boot()` only registers a `creating` hook.
- `$approvalsArr` **is** passed to the view (`compact('approvalsArr', ...)` at
  [ContractDashboardController.php:239](../../../Modules/Contract/app/Http/Controllers/ContractDashboardController.php:239)).
- The probe is inside `@section('content')` (line 287–728) and definitely executes — it reported
  `contracts_visible: 2508` correctly from the same scope in the same request.
- The only type asymmetry is `approval_contracts.contract_id int(11)` vs `contracts.id bigint(20) unsigned`,
  which should not defeat `whereIn`.

A prior investigation observed "a raw `whereIn` returns 0 but a `JOIN` returns 13,867" before being
interrupted, so the `whereIn` itself is the suspect.

### Establish

1. **Dump the actual SQL and bindings** for that query in a real request — `DB::listen` is already wired by
   the perf instrumentation, so log the statement and the first/last few bindings for this specific query.
   Confirm whether the query runs at all, and what it is asked to match.
2. **Inspect `$contractIds` in the request** — count it, and dump its first few values *with their PHP
   types*. `contracts_visible` is 2,508 but `$contractIds` is populated separately inside the
   `if($applicable)` branch of the counting loop
   ([ContractDashboardController.php:104](../../../Modules/Contract/app/Http/Controllers/ContractDashboardController.php:104)) —
   verify it actually has 2,508 integer entries and is not empty, string-typed, or keyed oddly.
3. **Check the `->groupBy('unique_id')->reverse()` chain** at
   [:184-185](../../../Modules/Contract/app/Http/Controllers/ContractDashboardController.php:184). If the
   query returns rows but `groupBy` produces nothing, the fault is downstream of the query — note the
   seeded `unique_id` values are `seedperf_<contractid>_<n>`, all non-null.
4. **Check whether `decryptString()` inside the `->map()`** at
   [:175-183](../../../Modules/Contract/app/Http/Controllers/ContractDashboardController.php:175) can
   swallow rows or throw. Seeded `approval_status` values start `eyJpdiI6...`, so they are genuinely
   encrypted and should decrypt.
5. **Determine whether this reproduces at N=18** — roll back with
   `HTTP_HOST=apollo.contracts.legality:8888 php artisan db:seed --class=PerfDatasetRollbackSeeder --force`,
   measure, then re-seed. This is the decisive question: if it is empty at N=18 too, the dashboard's
   approvals panel has been **silently blank for every real user** and this is a correctness bug that
   predates all of this work. If it is populated at N=18 and empty at N=3,018, it is a seeding artifact and
   the performance conclusions need revisiting.

### Why this matters beyond curiosity

- **Correctness:** if real users see an empty approvals panel, that is a live bug worth more than any of the
  performance work in this map.
- **Measurement validity:** [ticket 05](05-baseline-attribution.md)'s N=3,018 numbers were taken with this
  code path dormant. If it is a seeding artifact, real production load includes work our measurements
  never exercised, and the 14.4 s figure is an **under**-estimate.
- [Ticket 08](08-query-layer-redesign.md) is currently instructed not to optimise the `$approvalsArr` walk.
  That instruction is only correct if the emptiness is a genuine property of the application.

## Answer

**Root cause found and proven. This is a live correctness bug, not a seeding artifact, and it is not
specific to the dashboard.**

### MariaDB silently returns zero rows for `IN` with ≥1000 bound parameters

Server is **MariaDB 10.4.24** with `in_predicate_conversion_threshold = 1000`. At or above that many
values, MariaDB rewrites the `IN` predicate into a materialised subquery. **With bound parameters that
rewrite returns zero rows** — no error, no warning, no exception.

PDO has `ATTR_EMULATE_PREPARES = 0` here, so Laravel sends real server-side prepared statements and every
id arrives as a bound parameter. That is what puts the app on the broken path.

### The evidence

All measured in one real request at N=3,018, `$contractIds` holding 2,508 genuine PHP integers
(`is_int` = 1, sequential keys, min 1, max 102997):

| probe | rows |
|---|---|
| `whereIn` with the first **3** ids | 21 ✓ |
| `whereIn` with the first **500** | 3,500 ✓ |
| `whereIn` with the first **900** | 6,147 ✓ |
| `whereIn` with the first **999** | 6,684 ✓ |
| `whereIn` with the first **1000** | **0** ✗ |
| `whereIn` with the first **1001** | **0** ✗ |
| `whereIn` with all **2,508** | **0** ✗ |
| same 2,508 values **inlined as SQL literals** | **11,506** ✓ |
| same 2,508 values bound, with `SET SESSION in_predicate_conversion_threshold=0` | **11,506** ✓ |
| `ApprovalContracts::count()` (no `whereIn`) | 13,867 ✓ |

**The threshold is exact: 999 works, 1000 fails.** Disabling the conversion restores the correct 11,506
rows from the identical bound query, which isolates the cause to the conversion and nothing else.

Note raw SQL with **1001 literals** returns correct results (2,084 rows on a test range) — so the server
handles large `IN` lists fine when they are literals. **It is specifically the bound-parameter path that
breaks**, which is why this was invisible to every hand-run SQL check.

### Scope of the bug — wider than this ticket

This is not one panel. Any `whereIn` reaching ≥1000 bindings anywhere in this application silently returns
nothing. In this controller alone:

- `ApprovalContracts::whereIn('contract_id', $contractIds)` at
  [ContractDashboardController.php:171](../../../Modules/Contract/app/Http/Controllers/ContractDashboardController.php:171)
  → the **approvals panel** is blank.
- `Tasks::whereIn('contract_id', $contractIds)` at
  [:187-190](../../../Modules/Contract/app/Http/Controllers/ContractDashboardController.php:187)
  → the **task counters** (`$stusMyTask`: all / pending / inprogress / completed) are silently **zero**.

**Any tenant with ≥1000 visible contracts is affected.** `goalapp_apollo` holds 2,886 contracts, so this
is almost certainly happening in production today — the dashboard renders 200 OK with a blank approvals
list and zero task counts, and nothing anywhere reports an error.

The whole codebase should be swept for `whereIn` calls whose argument can grow with row count. This
ticket only enumerated the dashboard's two.

### Consequences for the rest of the map

1. **[Ticket 05](05-baseline-attribution.md)'s N=3,018 figure is an under-estimate.** Those measurements
   ran with the approvals and tasks code paths dormant. Real load at scale includes decrypting six fields
   across ~11,500 approval rows plus the blade walk over them — work our 14.4 s never included.
2. **[Ticket 08](08-query-layer-redesign.md)'s instruction to ignore the `$approvalsArr` walk was correct
   for the wrong reason** and must be reversed: the walk is dormant only because of this bug. Once fixed,
   it becomes a real cost and needs designing for.
3. **It strengthens the case for the aggregate rewrite.** Passing 2,508 ids from PHP back into SQL is not
   merely slow — on this server it is *incorrect*. An `EXISTS`/`JOIN` formulation has no id list, so the
   bug cannot occur. That makes ticket 08's redesign a correctness fix, not just an optimisation.

### Candidate fixes, not applied

| Fix | Effect | Blast radius / risk |
|---|---|---|
| **Restructure to `JOIN`/`EXISTS`**, eliminating the id list | removes the bug class entirely and is faster | Dashboard + wherever else the pattern appears. **The right fix**, and already ticket 08's direction. |
| `SET SESSION in_predicate_conversion_threshold=0` on connect (via the `mysql` connection's PDO options in `config/database.php`) | fixes every `whereIn` app-wide immediately | Whole app; one config line; may lose an optimisation MariaDB intends for genuinely large lists. **The right stopgap** — cheap, and it stops silent data loss today. |
| `PDO::ATTR_EMULATE_PREPARES => true` | values become literals, avoiding the path | Whole app; changes how every query is sent; broader side effects than the above |
| Chunk large `whereIn` into batches under 1000 | works | Must be applied at every call site; easy to miss one, and the failure mode is silent |
| Upgrade MariaDB | proper fix | Infrastructure; out of scope for this map |

Recommended sequencing: apply the session-variable stopgap so production stops silently losing data, then
remove the id-list pattern in ticket 08's redesign, then revisit whether the stopgap is still wanted.

### Instrumentation state

The probes used here have been **reverted** — `ContractDashboardController.php` is back to its committed
state in both the worktree and `D:\Contract-Expense\GOALv4\contracts` (verified: zero `t12_` references
remain). The reusable instrumentation from tickets 02 and 11 is untouched and still live.

One incidental note: calling `PerfRecorder::setNote()` statically is a fatal error — it is an instance
method. Use `PerfRecorder::probe()` (static) or resolve the recorder from the container.
