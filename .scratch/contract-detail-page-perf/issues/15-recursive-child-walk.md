# 15 — Replace the quadratic child-contract walk

Type: `wayfinder:task` (AFK)
Blocked by: 18, and step 0 below must happen first
Status: CLOSED - see Resolution

## Step 0 — the seeder has no parent-child chains, and this ticket is meaningless without them

Ticket 16 measured it: `SUM(parentcontract <> 0)` over 3,018 contracts is **0**. No contract has a
parent, so none has a child. **The parent walk and the child walk cost 2,496 ms to produce nothing**,
and they cost the same either way, because MySQL user variables and `FIND_IN_SET` make the optimiser
walk every row regardless of what it finds.

So a rewrite measured on this data proves nothing about a rewrite. Add chains to
[PerfDatasetSeeder](../../../database/seeders/PerfDatasetSeeder.php) first: a spread of depths — many
contracts with one child, some with a chain two or three deep, and at least one wide fan-out — because
a recursive walk behaves differently on a deep chain than on a wide one.

**Do this after [ticket 18](18-guard-the-scans-by-tab.md) has taken its numbers**, not before. Changing
the data mid-measurement invalidates it.

**How to handle the baseline.** Do not edit row 0. Add a fresh row saying "same page, data now has
parent-child chains", so the report stays honest about what changed when. Row 0 stays the record of a
page measured on data with no chains, and it says so.

**Check for cycles after seeding.** A contract whose `parentcontract` points at its own descendant makes
the recursive CTE loop until MariaDB stops it. The seeder must not create one, and the query should cap
depth anyway.

## Question

Nothing to decide. The query is quadratic and the index only hides it. Replace it.

## What it is

[ContractController.php:782](../../../Modules/Contract/app/Http/Controllers/ContractController.php:782),
and the same shape again at `:10662`:

```sql
SELECT GROUP_CONCAT(lv SEPARATOR ',') as childList FROM (
  SELECT @pv:=(SELECT GROUP_CONCAT(id SEPARATOR ',') FROM contracts
               WHERE FIND_IN_SET(parentcontract, @pv)) AS lv
  FROM contracts
  JOIN (SELECT @pv:=<id>) tmp
) a
```

It walks down a parent-child chain using a session variable. The inner subquery reads the whole
`contracts` table **once for every row of the outer one** — 3,018 x 3,018 row reads for one page view.
It gets slower with the square of the contract count, so it gets worse for every client as their data
grows.

It also runs inside a `foreach ($parentContractArr as $parCon)` loop, so a contract with several
ancestors pays it several times.

## What it should be

`WITH RECURSIVE`. MariaDB has it from 10.2 and this server is 10.4.24. The walk becomes one pass down
the tree, reading only the rows that are actually children:

```sql
WITH RECURSIVE descendants AS (
    SELECT id FROM contracts WHERE parentcontract = ?
    UNION ALL
    SELECT c.id FROM contracts c JOIN descendants d ON c.parentcontract = d.id
)
SELECT id FROM descendants
```

**This is a documented exception to the Eloquent rule.** [CLAUDE.md](../../../CLAUDE.md) puts
`DB::raw` last and asks for a comment saying why Eloquent cannot express it. A recursive CTE is one of
the cases where it genuinely cannot. Write that comment on the line.

Return ids, not a comma-joined string. The caller immediately does `explode(",", $childsList)`, so the
string is pure overhead — and `explode` on an empty result gives `['']`, which is the same class of bug
as ticket 01.

## Watch out

- **A cycle would loop forever.** A contract whose ancestor chain points back at itself makes the CTE
  recurse until MariaDB's `max_recursive_iterations` stops it. Check whether any cycle exists today
  (`parentcontract` pointing at a descendant), and cap the depth anyway.
- The `foreach` over `$parentContractArr` can go too. One recursive query can start from every ancestor
  at once by seeding the anchor with `WHERE parentcontract IN (<the ancestors>)` — but those ancestors
  are ids from PHP, so use a subquery, not a bound list ([CLAUDE.md](../../../CLAUDE.md), Query rules).
- `:10662` is **another page**. Scope says leave it. Fix the one on this page, and note the other so a
  later effort finds it.

## Done when

- The walk is one recursive query per page view, not one full-table scan per row.
- **Same child ids as before.** Prove it by comparing the id sets on several contracts with real
  parent-child chains — find those first, because a contract with no children proves nothing.
- A report row: TTFB and query count.
- Verified in the browser: the subsequent-contracts panel lists the same entries.

## Resolution

Status: **CLOSED**, 2026-08-22. Two commits: `a4aa1bc` (the seed) and `05831d1` (the query).

### Step 0: the chain shapes seeded

`PerfDatasetSeeder::assignParentChains()` links **684 of the 3,000 seeded rows** to a parent.
It is called from `run()`, and it is `public` so it can run on an already-seeded database.

| shape | how many | rows used |
|---|---|---|
| one contract, one renewal | 300 pairs | 600 |
| chain three rows deep | 100 chains | 300 |
| chain four rows deep | 50 chains | 200 |
| wide fan-out, 12 children | 1 | 13 |
| wide fan-out, 20 children | 1 | 21 |
| one branch of the first fan-out, two rows deeper | 1 | 2 |

**77% of contracts stay a root.** Not every contract is a renewal.

Depth in the whole set: 2,334 roots, 482 rows one deep, 151 two deep, 51 three deep.

### The cycle check, and it was run

Two checks, both after the rows were written.

1. **`parentcontract >= id` returns 0 rows.** Every parent id sits below its child id, so an
   ancestor chain of strictly falling ids cannot return to its start. `assertNoParentCycles()`
   runs this on every seed and throws if it finds one.
2. **A recursive walk from the 2,334 roots reaches all 3,018 rows exactly once**, maximum depth
   3, and `SUM(root = id AND depth > 0)` is 0 - no row reaches itself.

The seeder cannot build a cycle either: it asserts every parent row number is below its child
row number before it writes.

### The re-measured starting number, on the new data

**Report row 9. No code change** - the same page on different data. Rows 0 to 8 were taken on
data where `SUM(parentcontract <> 0)` was 0, so both walks returned nothing. Row 0 is untouched.

`100479?tab=details`, warm, `DEBUGBAR_ENABLED=false` at [.env](../../../.env) line 34:

| | server `total_ms` | queries | the child walk |
|---|---|---|---|
| row 8, old data | 2,972-3,567 | 359 | 1,977-3,377 ms |
| **row 9, new data** | **3,235-7,109** | **369** | **1,996-5,365 ms** |

The walk got slower and it swings much wider, because now it finds rows.

Query count rises by 10 because the blade lazy-loads
`select * from contracts where parentcontract = ? limit 1` for each related contract, and rows
that now have a parent add hits. That count belongs to [ticket 13](13-visible-to-scope.md).

### The new query

`ContractController::subsequentContractIds($id)`, one method, one concern.

```sql
WITH RECURSIVE ancestry (pid, depth) AS (
    SELECT c.parentcontract, 1
      FROM contracts c
     WHERE c.id = ? AND c.parentcontract > 0
    UNION ALL
    SELECT p.parentcontract, a.depth + 1
      FROM contracts p
      JOIN ancestry a ON p.id = a.pid
     WHERE p.parentcontract > 0 AND a.depth < 32
),
descendants (id, depth) AS (
    SELECT c.id, 1
      FROM contracts c
     WHERE c.parentcontract = COALESCE(
               (SELECT pid FROM ancestry ORDER BY depth DESC LIMIT 1), ?)
    UNION ALL
    SELECT c.id, d.depth + 1
      FROM contracts c
      JOIN descendants d ON c.parentcontract = d.id
     WHERE d.depth < 32
)
SELECT DISTINCT id FROM descendants
```

Four things about it:

- **The `foreach` over `$parentContractArr` is gone.** The ancestors form a chain, so the walk
  down from the highest ancestor already covers every lower one. One query does what up to four
  did. `ancestry` finds the top of the chain; `COALESCE` falls back to this contract when it has
  no parent, which is what the old `if (count($parentContractArr) == 1 && ...)` line did.
- **No list of ids crosses the wire.** Both bindings are the one contract id from the URL.
- **Both recursions cap at 32 levels** (`self::FAMILY_TREE_MAX_DEPTH`). The seeded set is 3 deep.
- **It returns ids.** The old code returned a comma-joined string and the caller ran
  `explode(",", $childsList)`, which gives `[""]` on an empty result - the class of bug
  [ticket 01](01-fix-null-reminder-crash.md) fixed. Contract 1 shows it: the old walk gave
  `final: [""]`, the new one gives `[]`.

`DB::select` with the comment on the query saying why Eloquent cannot express it, as
[CLAUDE.md](../../../CLAUDE.md) asks. Eloquent has no expression for a table that refers to
itself.

### The id sets: same where the old query was right, and the old query was not always right

A script ran both shapes side by side on 20 contracts - roots, one-deep, mid-chain, chain
bottoms, fan-out parents, fan-out children, the pre-existing contracts 1 and 2, and two
contracts with no relations at all.

**16 of 20 match exactly.** Every root, every one-ancestor contract, every fan-out member, and
both empty cases.

**4 differ, and in all four the old query was wrong.** They are the contracts with **two or more
ancestors**. The old code ran one walk per ancestor and joined the results with
`$childsList .= $conSubSeq->childList;` - **no comma between them**. So the last id of one walk
and the first id of the next were glued into one number:

| contract | old set | new set |
|---|---|---|
| 100603 | `100603`, `100603100602` | `100602`, `100603` |
| 100904 | `100903`, `100904`, `100904100902`, `100904100903` | `100902`, `100903`, `100904` |
| 101142 | `101103`...`101143`, `101143101102` | `101102`, `101103`...`101143` |
| 101143 | `101103`...`101143`, `101143101102`, `101143101142` | `101102`, `101103`...`101143` |

Each extra ancestor cost one real id and invented one number that matches nothing.
**202 of the 3,018 contracts have two or more ancestors**, so 202 contracts were affected.

**The rendered page does not change, and here is why.** The id the old code lost is always an
ancestor of the contract, and the Parent Contracts table above prints the ancestors first. The
blade shares one `$prevContracts` list across all four tables
([viewDetailContract.blade.php:2353](../../../Modules/Contract/resources/views/contract/viewDetailContract.blade.php:2353)),
so the Subsequent Contracts table skips a row that table already printed. The recovered id lands
in a row that is skipped.

Measured, not argued: `100603` and `101143` were fetched with the old code and with the new one,
same session, warm. Each pair is **identical when whitespace is ignored**, and each new document
is **exactly 88 characters longer**. All 88 are whitespace - the `@foreach` runs one more time
and prints nothing.

### The numbers

Report row 10. `100479?tab=details`, warm, debug bar off.

| | TTFB | server `total_ms` | queries | the walk |
|---|---|---|---|---|
| row 9, before | 3,436 ms | 3,235-7,109 | 369 | 1,996-5,365 ms |
| row 10, after | **1,237 ms** | **1,198-2,207** | 369 | **not in the ten slowest** |

The slowest query on the page is now the parent walk at 222-255 ms. The second slowest is
20-99 ms. Query count does not move: it was one query and it is one query.

Other contracts on the Details tab, before and after:

| contract | before `total_ms` | after `total_ms` | queries |
|---|---|---|---|
| 100479 | 3,235-7,109 | 1,198-2,207 | 369 |
| 101101 (12-child fan-out) | 3,427-4,747 | 1,883-2,053 | 619 |
| 1 (control) | 3,375-3,510 | 1,543-1,784 | 426 |
| 100603 (two ancestors) | - | 1,378 | 381 |
| 101143 (three ancestors) | - | 1,609 | 400 |

### Every tab loaded

Both contracts, all ten tab values and no `?tab`. Every document is the same size ticket 20
recorded, character for character.

| contract | tab | status | document characters | ms |
|---|---|---|---|---|
| 100479 | `details` | 200 | 239,076 | 1,108 |
| 100479 | `pre-approval` | 200 | 105,779 | 347 |
| 100479 | `timeline` | 200 | 105,779 | 330 |
| 100479 | `edit` | 200 | 326,231 | 484 |
| 100479 | `flow` | 200 | 72,388 | 319 |
| 100479 | `history` | 200 | 61,437 | 306 |
| 100479 | `historical` | **500** | error page | 3,985 |
| 100479 | `attachment` | 200 | 61,350 | 2,183 |
| 100479 | `obligation` | 200 | 85,534 | 319 |
| 100479 | `e-stamp` | 200 | 67,003 | 514 |
| 100479 | none | 200 | 105,779 | 367 |
| 1 | `details` | 200 | 261,223 | 1,142 |
| 1 | `pre-approval` | 200 | 100,708 | 345 |
| 1 | `timeline` | 200 | 100,708 | 345 |
| 1 | `edit` | 200 | 321,430 | 378 |
| 1 | `flow` | 200 | 89,080 | 360 |
| 1 | `history` | 200 | 62,752 | 370 |
| 1 | `historical` | **500** | error page | 3,919 |
| 1 | `attachment` | 200 | 57,345 | 2,135 |
| 1 | `obligation` | 200 | 81,584 | 338 |
| 1 | `e-stamp` | 200 | 62,993 | 359 |
| 1 | none | 200 | 100,708 | 475 |

`?tab=historical` **fails the same way**: `local.ERROR: Undefined array key "history"` from
`viewDetailContract.blade.php`, twice, one for each contract. Not fixed -
[ticket 03](03-find-remaining-breaks.md) owns it. It is now 3,919-3,985 ms instead of 6,676,
because it falls into the same branch as the Details tab and paid the same walk.

`storage/logs/laravel.log` holds **no other error and no other warning** across all 22 loads,
only the `Contract detail page skips the Related Contracts queries` debug lines from ticket 18
and the new `Contract detail page walked the contract family tree` line.

Browser console on `101101?tab=details` and `100479?tab=details`: the **same nine entries**
ticket 20 recorded - three accessibility issues, one deprecation, one 403 for an asset, one
Tagify warning, three logs. Nothing new.

### Written down, not fixed

- **The second copy of the old query shape is at
  [ContractController.php:10662](../../../Modules/Contract/app/Http/Controllers/ContractController.php:10662),
  and it is another page.** Left alone, as this map's scope says. There are in fact **three**
  sites near it that read a child list: `:10748` and `:10885` both do
  `Contract::whereIn('id', $finalListChild)`. A later effort on that page gets the same win by
  calling `subsequentContractIds()`, and the same correctness fix - **that page still glues ids
  together for any contract with two or more ancestors**.
- **The parent walk is now the slowest query on the page**, 222-255 ms, inside
  `relatedContractLists()`. It is the same session-variable shape, it reads the whole table with
  an `ORDER BY id DESC` filesort, and the same `ancestry` CTE can replace it. It was 179 ms on
  data with no chains and it is 222-255 ms now. Not this ticket's - this ticket was the child
  walk - but it is the next thing on the Details tab after
  [ticket 13](13-visible-to-scope.md).
- **`Contract::select('*')->whereIn('id', $finalListChild)` still binds a list of ids.** The
  list is now a real one, and it grows with the size of the family tree, not with the dataset.
  The widest tree here holds 20. A production tree with 1,000 members would hit the
  [whereIn 1,000-binding bug](../../wherein-1000-bug/spec.md) and empty the table with no error.
  [Ticket 09](09-replace-wherein-with-joins.md) owns it, and the fix is easy now: pass the
  recursive query as a subquery instead of the ids.
- **The old query truncated silently as well.** `GROUP_CONCAT` obeys `group_concat_max_len`,
  1,024 bytes by default, so a tree with more than about 145 members lost its tail without an
  error. The recursive query has no such limit.
