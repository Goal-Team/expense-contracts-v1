# 15 — Replace the quadratic child-contract walk

Type: `wayfinder:task` (AFK)
Blocked by: 18, and step 0 below must happen first
Status: OPEN

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
