# 04 — Baseline the page and say where the time goes

Type: `wayfinder:task` (AFK)
Blocked by: 01, 02, 03
Status: CLOSED

## Question

How slow is this page, and which part is slow? No optimisation decision can be made before this
answers.

## What is known

The timing middleware from the dashboard effort is already installed and already reports per-request
query count and time. `viewContract` runs at least 60 queries by eye, including
`ContractParties::select('*')->get()` twice and four `availableContracts()` decoration passes.

## Done when

- Row 0 of [../measurements/report.md](../measurements/report.md) holds the baseline: TTFB, query
  count, document bytes, total transfer bytes, request count. Debug bar OFF.
- The time is attributed: how much is queries, how much is PHP, how much is the view, how much is
  bootstrap. Name the top five queries by total time and the top five by count.
- Say plainly which of the suspects — the two `select('*')->get()` party loads, the four
  `availableContracts()` passes, the eSign block, the dropdown data — actually costs anything.

## Findings

Measured 2026-08-22 on branch `claude/contract-edit-page-perf`, seeded set (3,018 contracts).
`DEBUGBAR_ENABLED=false` confirmed in [.env](../../../.env) line 34 before any reading.

### What measured what

Nothing new was built and no application code changed. The dashboard effort's
`PerfTimingMiddleware` already writes one JSON record per request to
`storage/logs/perf-Y-m-d.log`, with phase times, per-request query count, per-shape duplicate
counts and the ten slowest queries. That is the whole attribution.

One temporary line was added to [PerfRecorder::payload()](../../../app/Perf/PerfRecorder.php) to
dump **every** query shape sorted by total time, so the top-five list is exact and not read off the
slowest-single-query list. It is reverted; `git status` is clean.

Browser numbers come from the Navigation Timing API on the real logged-in page over CDP.
`SHOW GLOBAL STATUS LIKE 'Questions'` was used once as a cross-check only. It read 259 across one
load where the middleware read 253; the extra 6 are the two `mysql` CLI calls that took the
readings. **253 is the honest per-request number.**

### Where the time goes

Warm runs of `100479?tab=edit`, 21 records:

| phase | ms | share |
|---|---|---|
| bootstrap (index.php to first middleware) | 131-223 | 4% |
| routing | 3-11 | 0.2% |
| route middleware | 21-38 | 0.6% |
| controller body | 3,546-4,270 | **91%** |
| blade render (21 views) | 31-110 | 2% |
| send / terminate | 1 | 0% |
| **request total** | **4,137-4,774** | |
| of which the driver reports as query time | 3,454-4,282 | **89-90%** |

**The page is a database problem, not a PHP problem and not a view problem.** Take the database
time away and the whole request is about 450 ms, and half of that is bootstrap. Blade rendering
326 KB across 21 views costs under 110 ms. Peak memory is 12-14 MB. Opcache is on and 831 files
are included.

Inside the controller, PHP that is not waiting on a query is about 200 ms. That is the four
`availableContracts()` loops walking rows, and the decrypt passes.

### Top five queries by total time

One representative record. These five are 94% of all database time.

| ms | n | query | site |
|---|---|---|---|
| 2,616 | 1 | `SELECT GROUP_CONCAT(lv ...) FROM (SELECT @pv:=(SELECT GROUP_CONCAT(id ...) FROM contracts WHERE FIND_IN_SET(parentcontract, @pv)) ...)` — the recursive child walk | [ContractController.php:784](../../../Modules/Contract/app/Http/Controllers/ContractController.php:784) |
| 1,045 | 1 | `select * from contracts where (catgoery_id = ? and department_id = ? and contract_type = ?) and not id = ?` — `$contractsoldothers` | [ContractController.php:723](../../../Modules/Contract/app/Http/Controllers/ContractController.php:723) |
| 179 | 1 | `SELECT parentcontract FROM (... CASE WHEN id in ('100479') THEN @idlist := CONCAT(...) ...) WHERE checkId IS NOT NULL` — the parent walk | [ContractController.php:748](../../../Modules/Contract/app/Http/Controllers/ContractController.php:748) |
| 66 | 56 | `select * from contract_type where contract_type_id = ? and applicable = ? limit 1` | inside `availableContracts()` |
| 62 | 56 | `select * from contract_categories where contract_categories.id = ? limit 1` | inside `availableContracts()` |

The first three all read the whole `contracts` table. `contracts` is 110 MB against a 16 MB
buffer pool, so each one is a disk scan. None of them can use an index as written:

- `:784` and `:748` both use MySQL user variables and `FIND_IN_SET`, so the optimiser walks every
  row. The `contracts(parentcontract, id)` index from ticket 11 helps the inner subquery only; the
  outer walk still scans.
- `:723` filters on `catgoery_id`, `department_id` and `contract_type` and no index covers that
  triple. It also does `select *`, so every matching 9,390-byte row comes back.

### Top five queries by number of executions

| n | total ms | ms each | query |
|---|---|---|---|
| 56 | 62 | 1.1 | `select * from contract_categories where id = ? limit 1` |
| 56 | 66 | 1.2 | `select * from contract_type where contract_type_id = ? and applicable = ? limit 1` |
| 18 | 28 | 1.5 | `select ... AES_DECRYPT(username ...) from UserCredential where authtoken = ? limit 1` |
| 17 | 54 | 3.2 | `select ... from ContractUsers where AES_DECRYPT(UserName, ...) = ? and Customer = ? ...` |
| 11 | 8 | 0.7 | `select admin_value from admin_settings where admin_key = ? limit 1` |

**The two lists do not overlap in cost.** The five most frequent queries together cost 218 ms —
5% of the request. 158 of the 253 queries sit in this group. Every one of them is a fast
primary-key or indexed lookup repeated in a loop.

So the page has two separate problems and they need separate fixes:

1. **Three whole-table scans** cost 3,840 ms and 3 queries. Fix these and the page drops from
   4.4 s to about 600 ms.
2. **158 repeated small lookups** cost 218 ms and are 62% of the query count. Fix these and the
   page drops maybe 200 ms, but the query count — the number the map says must not regress — falls
   from 253 to about 95.

Both are worth doing. The first one buys the seconds. The second one buys the count.

### Bytes

| | warm | cold |
|---|---|---|
| document bytes | 326,254 | 326,254 |
| total transfer bytes | 326,854 | **2,979,504** |
| request count | 59-60 | 62 |
| full load time | 5,527-5,959 ms | 8,083 ms |

A cold cache pulls **9x** the bytes of a warm one. 39 of the asset requests are compressed on the
cold run.

**The document itself is never compressed.** `encodedBodySize` equals `decodedBodySize` at
326,254 on every run, cold and warm. IIS gzip engages for the static assets and not for the HTML
response. 326 KB of mostly-repeated markup would compress to well under 40 KB. This is a config
item, not page code, so it is written down and not fixed here.

### The plain answer: which suspects actually cost anything

Measured, not guessed.

**The two `ContractParties::select('*')->get()` calls — cost nothing today.**
[:701](../../../Modules/Contract/app/Http/Controllers/ContractController.php:701) and
[:769](../../../Modules/Contract/app/Http/Controllers/ContractController.php:769). `select * from
contract_parties` ran twice for **1.44 ms total**. The reason is that `contract_parties` holds
**one row** — the seeder made only one. So the call reads the whole table, and the whole table is
tiny. It is still a full table read with no `where`, and it is duplicated, so it grows with the
number of parties in production. Cheap to fix, worth 1 query, worth no time.

**The four `availableContracts()` passes — cost queries, not seconds.** They are the 112
`contract_categories` + `contract_type` lookups, plus the `contracts where id in (58 ids)` reads
(2 x 35 ms), plus the overhead the scopes add to every `Contract` query. Together about **200 ms of
query time and 200 ms of PHP**, so roughly 9% of the request. But they are **158 of the 253
queries**. Ticket 13 is right that this is the biggest win by count, and wrong that it is the
biggest win by time.

**The eSign block — costs nothing on either test contract, and cannot be measured here.**
Contract 100479 is `Draft` and contract 1 is `Pre-Approval / Review`. The block at
[:281](../../../Modules/Contract/app/Http/Controllers/ContractController.php:281) only runs for a
Signing contract, so it never fired in any of the 21 runs. Non-database time in the controller is
about 200 ms, which is far too little to hold two outbound HTTP calls. **Ticket 10 still stands but
it is unmeasured**: on a Signing contract the two HTTP calls add whatever the remote service takes,
and nothing on this machine bounds that. To price it, ticket 10 needs a copy of a real contract set
to Signing.

**The dropdown data — costs almost nothing today.** Measured piece by piece:

- The two full reads of `ContractUsers` (1,605 rows, five `AES_DECRYPT` columns each) cost
  **6.6 ms and 3.0 ms**. Not three reads on this path — two.
- The branch reads (99 rows) cost **16.7 + 10.1 + 1.2 = 28 ms**, plus 4 small
  `branch where entityid = ?` reads at 4.0 ms total.
- The department reads (`entitybusiness`, 214 rows) cost **9.2 ms** across 6 queries.

Total for every dropdown on the page: **about 60 ms, 1.4% of the request.** The `AES_DECRYPT` is
fast because the rows are narrow. The expensive decrypt is not the dropdown data at all — it is
`BranchScope` putting `AES_DECRYPT(UserName, ...)` in the `WHERE` clause, which scans all 1,605
users **23 times** for **80 ms**. Still small.

Ticket 06 is a page-weight and a user-experience change, not a speed change. Say so before spending
front-end work on it.

### Tab observation for ticket 16

Not investigated — recorded only, as asked. One grep of the rendered HTML per load:

| URL loaded | document bytes | queries | contains "Related Contracts" |
|---|---|---|---|
| `100479?tab=edit` | 326,254 | 253 | **no** |
| `100479?tab=history` | 61,452 | 240 | **no** — and no case-insensitive match for "related contract" either |
| `100479` (no `?tab`) | 105,797 | 243 | **no** |
| `1?tab=edit` | 321,453 | 282 | **no** |

`?tab=history` is the value that reaches the `@elseif ($currentTab == 'history')` branch holding
line 1197, and the string still does not appear. `?tab=history` is also the **cheapest** document
of the three tabs at 61 KB, while it runs only 13 fewer queries than the edit tab. That pairing is
ticket 16's to explain.

### Written down, not fixed

Per CLAUDE.md, "Staying on a performance task":

1. **IIS does not gzip the HTML response.** 326 KB uncompressed on every load, warm and cold,
   while the static assets are compressed. Server config, not page code.
2. **`$contractsoldothers` at [:723](../../../Modules/Contract/app/Http/Controllers/ContractController.php:723)
   costs 1,045 ms — 24% of the page — and is the second most expensive query.** It has no ticket.
   It needs either an index on `contracts(catgoery_id, department_id, contract_type)`, a narrower
   `select`, or deletion if the blade region that reads it is the dead one ticket 16 is chasing.
   **Check ticket 16 first**; if that region is dead, this query goes with it.
3. **`ContractParties::select('*')->get()` runs twice in one request**
   ([:701](../../../Modules/Contract/app/Http/Controllers/ContractController.php:701),
   [:769](../../../Modules/Contract/app/Http/Controllers/ContractController.php:769)) into the same
   variable name. The second overwrites the first. One is dead. Ticket 12.
4. **`SHOW TABLES` runs twice per request** (6.9 ms). Something asks the schema on a page view.
   Small, but it is 2 queries for nothing.
