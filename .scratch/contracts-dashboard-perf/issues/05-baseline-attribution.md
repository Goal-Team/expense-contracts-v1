# Baseline: where do the seconds actually go?

Type: task
Status: resolved
Blocked by: 02, 04

## Question

This is the pivot of the map. Every remaining decision is guesswork until it is answered.

**The client side is already measured** — see [ticket 01](01-attach-chrome-devtools.md). Do not redo it.
At N=18: wall clock ~21 s, of which ~18 s is 26 refused requests to the dead `[::1]:5173` host; and
**document TTFB is 1.9–2.7 s**. The client half of this ticket is therefore answered: the wall clock is
dead-dev-server stalls, full stop.

What remains is the **server** half, and it has a sharper question than originally written:

> **Where do 2 seconds go when there are only 18 contracts to count?**
>
> The `~10 + 4·N` pattern at N=18 is roughly 80 small queries against a local MySQL. That should be
> tens of milliseconds, not two seconds. So either the fixed queries are individually expensive (the
> `BranchUser` select applies `AES_DECRYPT` to 11 columns across 99 rows; `Contract` hydrates 110
> columns via the `select('*')` global scope), or the cost is not in the DB at all — `xssprotect` /
> `contractauth` middleware, session driver, IIS + FastCGI handler startup, or blade compilation.
> Attribute it before assuming.

The dev reports both local and production as slow, and named three suspected causes: inline dropdown
data, `core.css` compile time, and the stage-count computation. The evidence so far says these cannot
all be true of the local instance: at **18 contracts** the query pattern is ~80 queries and the
counting loop is trivial, yet the page is still slow — so something else dominates locally. Meanwhile
`@vite` appears to be emitting URLs to a dev server that is not running.

With the Chrome trace (01), the timing middleware (02), and a realistic dataset (04) in hand, produce a
single attribution table for the dashboard response, at **both N=18 and N≈3,000**:

| Component | ms at N=18 | ms at N=3,000 |
|---|---|---|
| IIS + PHP bootstrap | | |
| `xssprotect` + `contractauth` middleware | | |
| DB total (and query count, and duplicate-query count) | | |
| — of which the `BranchUser` 11-column `AES_DECRYPT` select | | |
| — of which `Contract` hydration (110 cols × N) | | |
| PHP counting loop | | |
| Blade render, `$approvalsArr` walk isolated | | |
| **measured document TTFB (must reconcile to the sum)** | **1,900–2,700** | |

The reconciliation matters: if the components do not add up to the measured TTFB, the missing time is
somewhere nobody has looked yet, and that gap is the finding.

Then decide, with the dev:

1. Which component is the **leading** cost locally, and which at scale. These may be different
   components, in which case this map has two distinct problems and the spec must address both.
2. **How much of the response is actually the inline dropdown HTML** — the byte count and the ms cost
   of building it. The AJAX conversion is already a committed decision, so this does not gate it; but
   it decides whether the conversion is presented in the spec as a performance fix or as an
   architecture cleanup with a small win. Getting this wrong is how the change ships and the page stays
   slow.
3. Whether `core.css` is a compilation cost, a dead-dev-server stall, or a non-issue.
4. Whether anything unexpected turns up that the map has not accounted for.

Use `diagnosing-bugs`. Measure before concluding; every hypothesis in this map so far is a hypothesis.

## Answer

Retyped from `grilling` to `task` on resolution: every question in it turned out to be answerable by
measurement, with nothing left for the dev to decide.

Measured 2026-08-14 with the middleware from [ticket 02](02-timing-middleware.md), at both scales, after
[ticket 04](04-seed-realistic-dataset.md) seeded to 3,018 contracts.

### The attribution table

| Component | N=18 | N=3,018 |
|---|---|---|
| bootstrap | 1,072–1,133 ms | **1,255 ms** (flat) |
| routing | 34–68 | 59 |
| route middleware | 149–163 | 159 |
| **controller** | 252–429 | **12,583 ms** |
| view render | 461–887 | **379 ms** (went *down*) |
| send + terminate | 1–5 | 1 |
| **total (document TTFB)** | **2,084–2,573 ms** | **14,437 ms** |
| queries | 164 | **5,654** |
| DB total | 507–1,109 ms | **8,336 ms** |
| duplicate executions | 153 | **5,643** |
| **HTML size** | **67 KB** | **67 KB** |

Reconciles cleanly — no unexplained gap at either scale.

### 1. The leading cost is different at each scale, and both are real

**At N=18: bootstrap**, 1.1 s of a 2.1 s response, before any dashboard code runs. Detail and the
`menu_configs` / `information_schema` findings are in [ticket 02](02-timing-middleware.md); the follow-up
is [ticket 11](11-per-request-overhead.md).

**At N=3,018: the controller**, 12.6 s of a 14.4 s response — 87 %. Two queries account for essentially
all of it:

| n | total ms | query |
|---|---|---|
| **3,018** | **4,236** | `select * from contract_categories where id = ? limit 1` |
| **2,508** | **3,460** | `select * from contract_type where contract_type_id = ? and applicable = ?` |

5,526 of 5,654 queries are those two. They cost 7.7 s of the 8.3 s DB time. The remaining ~4.2 s of
controller time is PHP — hydrating 3,018 rows × 110 columns and running the counting loop.

**The pattern is 2·N, not 4·N.** The map assumed 4·N. `contractPartyList` does *not* appear in the
duplicates because `protected $with = ['contractPartyList']` eager-loads it in one query — so the
"duplicate lazy load at [Controller.php:219](../../../app/Http/Controllers/Controller.php:219) and
[:223](../../../app/Http/Controllers/Controller.php:223)" that ticket 08 called "the cheapest win
available" **costs nothing at all**. The two real N+1s are `ContractCategories::find()` at
[Controller.php:228](../../../app/Http/Controllers/Controller.php:228) and the `contractTypeData` hop at
[Controller.php:321](../../../app/Http/Controllers/Controller.php:321). Ticket 08 corrected accordingly.

Scaling: 168× the rows produced ~6× the TTFB. Sub-linear because bootstrap is a flat ~1.25 s and the DB is
local, but **14.4 s is already past the dev's 10 s unacceptable threshold at a row count several tenants
already exceed.**

### 2. The inline dropdown HTML is not a scale problem at all

**HTML stayed 67 KB at both 18 and 3,018 contracts.** The page emits counters and dropdowns, not
per-contract markup — so the entire 12 s regression is server-side work on rows that never reach the
response. The `<option>` markup is a fixed 10.8 KB / 15.3 % that does not grow with the dataset.

This settles the sizing question in [ticket 06](06-ajax-dropdown-design.md): the AJAX conversion cannot
address the scale problem, because the scale problem never touches the payload. It stays worth doing for
the reasons recorded there, at the size recorded there.

### 3. `core.css` is not a server-side cost

Confirmed a dead-dev-server stall, not compilation — see [tickets 03](03-vite-setup-research.md) and
[07](07-asset-pipeline-decision.md). The ~19 s of failed `[::1]:5173` requests persists at both scales and
is independent of everything above.

### 4. Unexpected findings

- **View render *decreased*** at 168× the data (461–887 ms → 379 ms). The blade walks `$approvalsArr`, so
  it was expected to grow. Worth understanding before ticket 08 assumes the blade walk is a cost — it may
  be that the seeded approval rows do not satisfy the filters the blade applies.
- **`APP_ENCRYPTION_KEY` is derived from `$_SERVER['HTTP_HOST']`** ([config/app.php:7](../../../config/app.php:7)).
  Under the web server it is `c0n|r@(t$apollo4` (16 bytes); from a bare CLI it becomes `localhost`
  (19 bytes) and the Encrypter will not construct. Found because seeding required
  `HTTP_HOST=apollo.contracts.legality:8888 php artisan db:seed`. Not a performance matter — recorded on
  the map as out of scope — but it means the hostname is load-bearing for decrypting stored data.
- `contract_party_data` is **MyISAM/latin1** with only a PRIMARY key, which sharpens
  [ticket 09](09-index-and-migrations.md): MyISAM has no transactional DDL and different index behaviour.
