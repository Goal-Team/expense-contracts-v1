# 02 — Baseline the page and say where the time goes

Type: `wayfinder:task` (AFK)
Blocked by: 01
Assignee: claude subagent (session 2026-08-27)
Status: CLOSED 2026-08-27

## Question

What does the page cost today, and which parts hold the cost? Row 0 of
[measurements/report.md](../measurements/report.md).

Measure warm, three runs, `DEBUGBAR_ENABLED=false`, seeded 3,018-contract set, logged-in browser
session. Two requests, measured separately:

1. **GET `contracts/list`** — TTFB, query count, document bytes, total transfer, request count.
2. **AJAX `listContractData`** — the shape the page actually fires on load (read
   [contractlist.js](../../../Modules/Contract/resources/assets/js/contractlist.js) for the real
   payload), TTFB, query count, **JSON bytes**, and the same with `myFilterStatus` set.

Browser numbers too: first render and last render of the table on the seeded set — the browser's
own work is performance per the dev's list, and 3,018 client-side DataTables rows are the suspect.

Attribution the way ticket 04 did it on the detail page: where do the milliseconds sit — database,
decrypt/PHP loops, blade/JSON serialisation, browser render? The timing middleware and
`DB::listen` tooling from the earlier efforts are already in the repo.

## Resolution

Row 0 is in [measurements/report.md](../measurements/report.md). Method: `DEBUGBAR_ENABLED=false`
(already set, no `.env` change), warm, three runs, seeded 3,018-contract set, logged-in CDP
session. Server numbers come from the perf log
(`storage/logs/perf-2026-08-27.log`, written by the existing
[PerfTimingMiddleware](../../../app/Http/Middleware/PerfTimingMiddleware.php) +
[PerfRecorder](../../../app/Perf/PerfRecorder.php) `DB::listen` recorder). Block timers were
added inside `listContractData` with the repo's `PerfRecorder::probe()` pattern — they are
inert outside `APP_ENV=local` + `APP_DEBUG=true`, same as the dashboard blade probe, and they
stay in for the next tickets to measure against.

### The numbers, three runs each

**GET `contracts/list`** — TTFB 2,241 / 2,970 / 2,784 ms. 14 queries, 1.2–1.8 s in the
database. Document 69,572 bytes (12,504 gz).

**POST `contracts/data`, default shape** (`status=draft`, `userData=0`, `contype=0`,
`locations=0`, `party_id=0` — verified in
[contractlist.js:324-336](../../../Modules/Contract/resources/assets/js/contractlist.js)) —
TTFB 5,637 / 5,600 / 4,997 ms. 13 queries. 406 rows, 5,535,399 bytes decoded (1,142,051 gz).

**POST `contracts/data`, `status=all`** — TTFB 5,852 / 5,837 / 5,735 ms. 13 queries. 2,508
rows, 34,248,287 bytes decoded (7,012,310 gz). ~1.4 s of it is `json_encode`, ~1.4 s more is
sending the 7 MB body.

**POST with `myFilterStatus` cookie set** — 0 rows, 14 queries, TTFB 4,692 ms. The extra
query is the `ApprovalContracts` `whereIn` with ~2,508 bound ids — over the 1,000-binding
line, so it silently returns nothing and "My contracts" goes empty. Known bug, ticket 05.
Not fixed here.

**Browser, full page load** — 47 requests, 1,154,555 bytes transfer warm (the data call is
99% of it; static assets come from cache). First paint 2.8 / 3.6 / 3.5 s. Last render of the
table 13.5 / 14.7 / 15.2 s after navigation start.

### Where the time sits (default AJAX call, ~5.3 s server)

| block | ms | share |
|---|---|---|
| bootstrap + routing + middleware | ~350 | 7% |
| contract fetch (13 queries + hydration + eager party rows) | ~1,000 | 19% |
| `availableContracts($contracts, true)` per-row PHP work | ~3,100 | 58% |
| filter + counter loop | ~200 | 4% |
| `json_encode` of 406 rows | ~400 | 8% |
| send | ~300 | 6% |

`availableContracts()` is the cost. The database holds ~0.7 s of the 5.3 s; the rest is PHP
looping 3,018 rows. Both POST branches fetch **every** active contract — the status filter
runs in PHP after the decorate pass, so `status=draft` pays the same server cost as
`status=all` and only the JSON size differs.

On the GET, the single slowest query is `select * from contracts where status = ? order by
id desc` at 1.0–1.7 s — the dead query of ticket 04. Note it runs as `select *`, not the
11-column select written in the code: the `accessLevelSelect` global scope overwrites the
column list, so every encrypted blob column crosses the wire too. The same overwrite applies
to the POST's fetch.

In the browser, the response ends ~10–13 s in and the table's last render is ~13.5–15 s in:
~3 s of JSON parse plus DataTables draw on the client.

### Written down, not fixed

- The 1,000-binding empty result on `myFilterStatus` (ticket 05).
- The global scope `select *` overwrite — makes both list queries fetch all columns.
  Candidate for tickets 03/04.
- `filterStatus` is set twice per AJAX call: PHP `setcookie()` at :2210 and again by JS.
  Ticket 07 territory.
