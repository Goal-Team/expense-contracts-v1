# 09 — Replace every id-list `whereIn` on this page with a join

Type: `wayfinder:task` (AFK, subagent)
Blocked by: 08 — the inventory names the sites and their line numbers
Status: PART DONE - the Parent Contracts site is closed, four sites stay open. See the Resolution.

## Question

Nothing to decide. The dev ruled on it 2026-08-21: **never pass a list of ids into `whereIn`, use a
join.** The rule is now in [CLAUDE.md](../../../CLAUDE.md), "Query rules". This ticket applies it to
this page.

## Why it is not only about speed

Two queries where one join does the work, and every id bound across the wire. But the reason it is
urgent is correctness: **on this stack a `whereIn` with 1,000 or more bound values silently returns
zero rows.** No error. A section of the page just goes blank, and it looks like missing data. See
[../../wherein-1000-bug/spec.md](../../wherein-1000-bug/spec.md).

## The known sites

Four, found while charting. Ticket 08 confirms the list and the line numbers, and may find more.

- `ContractPartyData::whereIn('contract_party_location_id', $contract_party_locations)` — fed by a
  `pluck()` two lines above it.
- `ContractPartyData::whereIn('contract_party_exe_id', $contract_party_id)` — same shape.
- `Contract::whereIn('id', $FinalContractList)` — the list is built in PHP from the two above, so it
  grows with the data twice over.
- `Contract::whereIn('id', $finalListChild)`.

All four are in
[`ContractController::viewContract`](../../../Modules/Contract/app/Http/Controllers/ContractController.php:259).

## How to write the replacement

**Eloquent, not raw SQL** — the dev's call 2026-08-21, now in [CLAUDE.md](../../../CLAUDE.md) under
"Query rules". Relationships first (`whereHas`, and add the relationship to the model if it is
missing), then `whereIn` on an Eloquent **subquery**, then a query builder `join`, and `DB::raw` only
if nothing above can express it.

**Two traps on this page:**

- `Contract::select('id')` used as a subquery does **not** return one column. The global scope in
  `Contract::boot()` calls `select('*')` after your select and overwrites it, and MySQL answers
  `Operand should contain 1 column`. Use
  `Contract::withoutGlobalScope('accessLevelSelect')->select('id')`. Leave
  `ContractRoledBasedScope` alone — that one is the visibility rule.
- The old code leaned on `pluck()` collapsing duplicates. A join does not. Add `distinct()` where it
  matters.

## Done when

- Every one is a single query, written in Eloquent, so no id is ever bound.
- **The result is identical.** Prove it before and after on the same contract, by comparing the
  returned id sets, not by eye. A join can change the row count where a `whereIn` on a distinct
  plucked list did not — watch for duplicates and add `distinct()` where the old code relied on
  `pluck()` collapsing them.
- Verified in the browser on a seeded contract and on contract 1, with the related-contract and
  child-contract sections of the page showing the same entries as before.
- A report row: query count before and after.
- Committed on `claude/contract-edit-page-perf`, one commit per site if they are independent.

## Notes

Do not widen this into the query-layer rewrite (ticket 05). This ticket changes four query shapes and
nothing else.

## Correction to "The known sites", 2026-08-22

**The list above was wrong about three of the four.** Nothing removed them. Tickets 15 and 21 replaced
the two family-tree **walks** - the queries that produce the ids - with recursive CTEs, and left the
`Contract` queries that turn those ids into rows exactly as they were. So the count of sites that bind
a list of ids did not fall from four to one. This is what each one is today, all five of them in
`ContractController::relatedContractLists()`:

| site | line, before this ticket | state |
|---|---|---|
| `ContractPartyData::whereIn('contract_party_location_id', $contract_party_locations)` | 440 | **still binds**, fed by a `pluck()` two lines above |
| `ContractPartyData::whereIn('contract_party_exe_id', $contract_party_id)` | 442 | **still binds**, same shape |
| `Contract::whereIn('id', $FinalContractList)` | 447 | **still binds**. The list is a PHP `intersect` of the two `pluck()` results above |
| `Contract::whereIn('id', $parentContractArr)` | 456 | **fixed here** |
| `Contract::whereIn('id', $finalListChild)` | 464 | **still binds**. Ticket 15 gave it a CTE for the ids and left the `whereIn` |

So four sites are open, not three closed. The one this ticket was told to take is the Parent Contracts
one, and that is the one that is done.

**The `$finalListChild` one is the biggest of the four and it is measured.** On `101101?tab=details` it
binds **111** ids and it is the slowest query on the page at **92.62 ms**. That is the next one to take.

## Resolution

Status: **the Parent Contracts site is CLOSED**, 2026-08-22. Four sites stay open; the table above
names them.

### The query

`ancestorContractIds($id)` used to run the recursive walk and return an array of ids. It returns the
**query** now, and the caller passes it to `whereIn`, so nothing is bound and no id crosses the wire.

```php
private function ancestorContractIds($id): QueryBuilder
{
    return DB::query()
        ->select('pid')
        ->fromRaw(
            '(WITH RECURSIVE ' . $this->ancestryCte() . ' SELECT pid FROM ancestry) AS ancestry_ids',
            [$id]
        );
}

$contractsparentList = Contract::select('*')
    ->whereIn('id', $this->ancestorContractIds($id))
    ->orderBy('id')
    ->get();
```

Compiled, on contract `101143`, with **one** binding - the contract id:

```sql
select * from `contracts`
 where `id` in (select `pid` from (WITH RECURSIVE ancestry (pid, depth) AS (
                 SELECT c.parentcontract, 1 FROM contracts c WHERE c.id = ? AND c.parentcontract > 0
                 UNION ALL
                 SELECT p.parentcontract, a.depth + 1 FROM contracts p JOIN ancestry a ON p.id = a.pid
                  WHERE p.parentcontract > 0 AND a.depth < 32) SELECT pid FROM ancestry) AS ancestry_ids)
 order by `id` asc
```

**This is level 2 of CLAUDE.md's order, an Eloquent subquery, and level 1 cannot reach it.** A
relationship needs a fixed number of joins; the ancestor chain has no fixed depth, so no `whereHas`
and no `with` can express it. The recursion itself stays raw, which CLAUDE.md already names as the one
real exception, and `ancestryCte()` is the same fragment tickets 15 and 21 built - nothing new is raw,
and nothing is copied.

**MariaDB accepts a `WITH` clause inside a derived table.** That is the whole trick: the recursive walk
goes in the `FROM` of an ordinary one-column select, and then `whereIn` can take it. Checked on this
server, 10.4.24, in both shapes - `IN (WITH RECURSIVE ... SELECT ...)` also parses, but the derived
table is the one Laravel's query builder can hold.

**`withoutGlobalScope('accessLevelSelect')` is not needed here**, unlike ticket 20. The trap only fires
when the subquery is a `Contract` query, because it is `Contract::boot()` that adds the `select('*')`
scope. This subquery is a plain `DB::query()`, so nothing overwrites its one column.
`ContractRoledBasedScope` still applies to the outer `Contract` query, as it must.

**No `distinct()` is needed.** A contract has one `parentcontract`, so the chain cannot fork and the
walk cannot return an id twice. `IN` would drop a duplicate anyway.

### `orderBy('id')` is the old order written down

The old code got its order from the plan, not from a rule. A `whereIn` on a bound list read the primary
key in ascending order, so the table printed the lowest id first. The subquery makes MariaDB
materialise the walk first and probe `contracts` from it, and the walk returns the **nearest parent
first** - so `101143` came back `101142, 101102, 101101` instead of `101101, 101102, 101142`.

Nothing on the page asks for either order, so the sort keeps the rendered table exactly as it was.
Measured before the change: `101143` prints `101101, 101102, 101142` and `100904` prints
`100901, 100902, 100903`, both ascending.

### The id sets match, 22 of 22

The old shape and the new one ran side by side on the same connection, and their **row lists** were
compared - not the ids the walk found, the rows the page loops.

| shape | contracts checked | result |
|---|---|---|
| root | 1, 2, 3, 4, 16, 100479, 101101, 101121 | match, all empty |
| one ancestor | 100002, 100004, 101102, 101103, 101110 | match |
| two ancestors | 100603, 100606, 100612, 101142 | match |
| three ancestors | 100904, 100908, 100912, 100916, 101143 | match |

**22 of 22 match, ids and order both.**

### It reads no more of the table than before

`EXPLAIN` on the new query, contract `101143`:

| select_type | table | type | key | rows |
|---|---|---|---|---|
| PRIMARY | `<subquery2>` | ALL | NULL | 2 |
| PRIMARY | `contracts` | **eq_ref** | **PRIMARY** | 1 |
| MATERIALIZED | `<derived3>` | ALL | NULL | 2 |
| DERIVED | `c` | **const** | **PRIMARY** | 1 |
| RECURSIVE UNION | `p` | **eq_ref** | **PRIMARY** | 1 |

Every read of `contracts` is a primary-key lookup. No scan, at any depth.

### The numbers

Report row 16. Warm, `DEBUGBAR_ENABLED=false`, three fetches per contract, before and after taken in
one session with `git stash` between.

| contract, `?tab=details` | queries before | queries after | `total_ms` before | `total_ms` after |
|---|---|---|---|---|
| 100479 (root) | 369 | **368** | 693-804 | 688-834 |
| 1 (control) | 426 | **425** | 777-853 | 746-924 |
| 101143 (three ancestors) | 400 | **399** | 693-920 | 756-831 |
| 101101 (20-child fan-out) | 619 | **618** | 1,152-1,300 | 1,099-1,142 |

**One query less on every contract, and the time does not move.**

**Honest note: the one merged query is a few milliseconds slower than the two it replaced.** Six runs
of each shape from `tinker`, first run dropped:

| contract | old pair | new single query |
|---|---|---|
| 101143 | 5.54-9.00 ms | 7.96-9.47 ms |
| 100904 | 3.08-11.67 ms | 7.72-19.89 ms |
| 101102 | 2.85-8.63 ms | 8.20-13.99 ms |
| 100479 | 1.94-7.08 ms | 5.71-9.94 ms |

So the new query enters the ten-slowest list on the Details tab at 6.51-13.10 ms, where neither of the
old two appeared. On a 700-900 ms page that is inside the run-to-run swing, and the page `total_ms`
above shows it. **The win here is not speed. It is that the table cannot silently go blank**, plus one
round trip less.

### The columns stay at `select('*')`, and here is why

Ticket 20 narrowed `$contractsoldothers` to seven columns. **That is not safe on this query.** Two
reasons, both checked in the code:

- `availableContracts()` runs over these rows and reads at least `contract_name`, `currency`,
  `currency_value`, `end_contract_type`, `fixed_date`, `contract_end_date`, `catgoery_id`,
  `department_id`, `contract_type` and `status`, and it **branches on `isset()`** - `isset($contract->catgoery_id)`
  decides whether it runs a `ContractCategories::find()` at all. A column left out of the select is
  `null` on the model, `isset()` is false, and the row comes back different with no error and one
  query fewer. Ticket 20's query has no `availableContracts()` pass, so it had none of this.
- The Previous Contracts table loops `$contractsoldother->contractPartyList` for its Unlink button, so
  the eager load must stay too - `without('contractPartyList')` cannot be used here either.

The row is 9,390 bytes and the table has at most three rows on this data, so the waste is small and the
risk of getting it wrong is not. Left alone on purpose.

### Output unchanged

30 documents, 13 tab values on `100479` and on `1`, plus `101143`, `101101`, `100904` and `101102` on
the Details tab. Every document has the **same length and the same hash as before the change**,
whitespace ignored. The Parent Contracts table prints the same ids in the same order on every contract
that has one.

| contract | Parent Contracts rows, before and after |
|---|---|
| 101143 | 101101, 101102, 101142 |
| 101102 | 101101 |
| 100904 | 100901, 100902, 100903 |
| 100479, 1, 101101 | none |

All 13 tab values return **200** on both contracts, `?tab=timelineedit` included.

`laravel.log` holds **no error and no warning** across the run. The debug lines are the expected ones:
`skips the Related Contracts queries`, `walked the contract family tree`, `read the parent contracts`
and the Drive cache-hit lines. Browser console on `1?tab=timelineedit`: the same two known entries, a
403 for an asset and the Tagify warning.

### One log line changed name

`walked up the contract family tree` is gone. It counted the ids the walk returned, and the ids no
longer come back to PHP, so nothing can count them. `read the parent contracts` replaced it and logs
the number of rows the page shows, which is the more useful number anyway - it is after the visibility
scope, not before it.
