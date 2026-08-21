# 21 — The parent walk is now the slowest query on the page

Type: `wayfinder:task` (AFK)
Blocked by: nothing. Ticket 15 already wrote the replacement.
Status: CLOSED

## Question

Nothing to decide. It is the same session-variable shape ticket 15 just replaced, in the same method,
and the `ancestry` CTE ticket 15 wrote already answers it.

## What it is

[ContractController.php:748](../../../Modules/Contract/app/Http/Controllers/ContractController.php:748),
inside `relatedContractLists()`. It walks **up** the tree to find a contract's ancestors, using MySQL
user variables and `FIND_IN_SET`:

```sql
SELECT parentcontract FROM (
  SELECT ... CASE WHEN id in ('100479') THEN @idlist := CONCAT(...) ... 
) WHERE checkId IS NOT NULL
```

**222–255 ms, and it is now the slowest single query on the page** — ticket 15 took the child walk out
from under it. The optimiser cannot use an index on it, for the same reason: user variables and
`FIND_IN_SET` force a walk of every row.

## Why this is small work

Ticket 15's rewrite already built a recursive CTE over the same `parentcontract` column, and it is
already proven — its id sets matched the old query on 16 of 20 contracts and beat it on the other four.
Walking **up** is the same CTE with the join reversed:

```sql
WITH RECURSIVE ancestry AS (
    SELECT id, parentcontract FROM contracts WHERE id = ?
    UNION ALL
    SELECT c.id, c.parentcontract FROM contracts c JOIN ancestry a ON c.id = a.parentcontract
)
SELECT id FROM ancestry WHERE id <> ?
```

Read ticket 15's Resolution and reuse what it did rather than writing a second version. If the two walks
can share one method, share it — [CLAUDE.md](../../../CLAUDE.md), "one function, one concern, do not
copy blocks".

## Do the same three checks ticket 15 did

1. **Same id sets.** Compare old against new on a root, a contract one level down, a contract three
   levels down, and a fan-out member. Ticket 15 seeded all of those: 2,334 roots, 482 one deep, 151 two
   deep, 51 three deep, and fan-outs of 12 and 20 under contract `101101`.
2. **Cycles.** `parentcontract >= id` returns zero rows, so a rising chain terminates. Confirm it still
   does, and cap the depth anyway.
3. **The same gluing bug.** Ticket 15 found the child walk joined results with `.=` and no comma, so it
   invented ids that match nothing. **Check whether this walk does the same thing** before assuming the
   old output was right.

## Done when

- One recursive query, no user variables, no `FIND_IN_SET`.
- Same ancestors as before, proved by comparing id sets, not by looking at the page.
- The Parent Contracts table renders the same rows. `101101` is the contract that proves the family
  tables — ticket 15 found `100479`'s only child is invisible to the test user, so its document bytes
  never move.
- Every tab loads, per ticket 18's table. `?tab=historical` still returns HTTP 500 for ticket 03's
  reason; confirm it fails the same way.
- A report row: Details-tab TTFB and query count.

## Note

`GROUP_CONCAT` capped the old child walk at `group_concat_max_len`, 1,024 bytes, so a tree of more than
about 145 members lost its tail with no error. **Check whether this query has the same cap.** If it does,
say so — it means the old ancestor list was silently truncated on deep trees, and that is worth knowing
even though the rewrite removes it.

## Resolution

Status: **CLOSED**, 2026-08-22. One commit for the query, `c480b03`, and one for the report row,
`89087ae`.

### The two walks share the upward walk

They share it, and nothing is copied. The recursive climb up `parentcontract` is one method,
`ContractController::ancestryCte()`, which returns the SQL fragment that names a common table
expression called `ancestry` with columns `pid` and `depth`. Two callers read it:

- `ancestorContractIds($id)` - new. `SELECT pid FROM ancestry ORDER BY depth`. This is the
  Parent Contracts table's list, nearest parent first.
- `subsequentContractIds($id)` - ticket 15's. It joins the fragment to a second `descendants`
  expression and walks back down from the top of the chain.

The two walks differ only in which side of the join carries `parentcontract`, so the downward
walk stays where it is and only the upward half moved.

The cap is shared too: `self::FAMILY_TREE_MAX_DEPTH` is 32, and it now guards both walks from
one place.

### The id sets match, all 20 of them

A script ran the old shape and the new one side by side, on one fresh connection each, with
native prepared statements - the same way Laravel runs them.

| shape | contracts checked | result |
|---|---|---|
| root | 1, 2, 3, 100479, 101101, 101121 | match |
| one ancestor | 100002, 100004, 101102, 101103, 101110 | match |
| two ancestors | 100603, 100606, 100612 | match |
| three ancestors | 100904, 100908, 100912, 100916, 101142, 101143 | match |

**20 of 20 match, and the order matches as well** - nearest parent first in both.

One difference, and it is in the new query's favour. The old query returned **one extra row
holding `0`** for every root, because a root's `parentcontract` is 0. The caller bound that 0
into `Contract::whereIn('id', ...)` and no contract has id 0, so the page never showed it. The
new query returns an empty array instead.

### Cycles

`SELECT COUNT(*) FROM contracts WHERE parentcontract >= id AND parentcontract > 0` returns
**0 rows**, so every parent id sits below its child id and a rising chain must stop. The cap is
in the query anyway: `a.depth < 32`, the same constant the downward walk uses.

### The old query was right, and here is what it depended on

**No gluing bug and no truncation.** Ticket 15's child walk had both faults because it ran one
query for each ancestor and joined the results with `.=` and no comma, and because it used
`GROUP_CONCAT`. This query has neither. It is **one** query, it returns **rows**, and it never
calls `GROUP_CONCAT`, so `group_concat_max_len` cannot reach it. Checked on the page: the
Previous Contracts table on `101143` printed `101101, 101102, 101142` before the change and the
same three after it, and `100904` printed `100901, 100902, 100903` both ways.

**But its answer depends on a PDO connection flag, and that is worth writing down.** The walk
reads `@idlist` in the `FIND_IN_SET` branch while it writes it in the branch above. With
**emulated** prepared statements MariaDB reads the value the variable held when the statement
began - NULL, because nothing sets it - so the `FIND_IN_SET` branch never matches and the walk
**stops after the immediate parent**. Measured, five runs out of five on a fresh connection:

| how the query runs | result for 101143 |
|---|---|
| `PDO::ATTR_EMULATE_PREPARES => false` (what Laravel does) | `101142, 101102, 101101, 0` - correct |
| `PDO::ATTR_EMULATE_PREPARES => true` | `101142` - the rest of the chain lost |
| emulated, but `SET @idlist := ''` first | `101142, 101102, 101101, 0` - correct again |

So the old shape was one config line away from silently losing every ancestor above the first,
on the **202 contracts that have two or more** (151 two deep, 51 three deep). The rewrite does
not depend on any of that. Nothing was fixed here because nothing was broken - this is the
fragility, recorded.

### The numbers

Report row 11. `100479?tab=details`, warm, `DEBUGBAR_ENABLED=false`.

| | TTFB | server `total_ms` | queries | the parent walk |
|---|---|---|---|---|
| row 10, before | 1,237 ms | 1,198-2,207 | 369 | 197-225 ms, slowest query on the page |
| **row 11, after** | **824 ms** | **686-785** | 369 | **not in the ten slowest**, 8.85 ms when it shows |

The slowest query on the Details tab is now **7-11 ms**. Both recursive walks are gone from the
ten-slowest list, on all four contracts measured.

| contract | before `total_ms` | after `total_ms` | queries |
|---|---|---|---|
| 100479 | 1,198-2,207 | 686-785 | 369 |
| 101101 (12-child fan-out) | 1,883-2,053 | 1,121-1,294 | 619 |
| 1 (control) | 1,543-1,784 | 842-1,021 | 426 |
| 101143 (three ancestors) | 1,609 | 888-1,081 | 400 |
| 100904 (three ancestors) | - | 780-808 | 379 |

Document bytes do not move. `101101`, `1`, `101143` and `100904` are **byte-identical** before
and after, 313,381 / 261,223 / 247,844 / 237,888 characters.

### Every tab loaded

Contract `101101` and control contract `1`, all ten tab values and no `?tab`.

| contract | tab | status | document characters | ms |
|---|---|---|---|---|
| 101101 | `details` | 200 | 313,381 | 1,294 |
| 101101 | `pre-approval` | 200 | 125,430 | 340 |
| 101101 | `timeline` | 200 | 125,430 | 347 |
| 101101 | `edit` | 200 | 349,476 | 416 |
| 101101 | `flow` | 200 | 80,855 | 330 |
| 101101 | `history` | 200 | 62,805 | 325 |
| 101101 | `historical` | **500** | error page | 4,073 |
| 101101 | `attachment` | 200 | 57,654 | 2,230 |
| 101101 | `obligation` | 200 | 82,901 | 406 |
| 101101 | `e-stamp` | 200 | 63,307 | 464 |
| 101101 | none | 200 | 125,430 | 365 |
| 1 | `details` | 200 | 261,223 | 924 |
| 1 | `pre-approval` | 200 | 100,708 | 354 |
| 1 | `timeline` | 200 | 100,708 | 381 |
| 1 | `edit` | 200 | 321,430 | 384 |
| 1 | `flow` | 200 | 89,080 | 343 |
| 1 | `history` | 200 | 62,752 | 376 |
| 1 | `historical` | **500** | error page | 3,724 |
| 1 | `attachment` | 200 | 57,345 | 2,113 |
| 1 | `obligation` | 200 | 81,584 | 362 |
| 1 | `e-stamp` | 200 | 62,993 | 336 |
| 1 | none | 200 | 100,708 | 373 |

Every document on contract `1` is the same size ticket 15 recorded, character for character.

`?tab=historical` **fails the same way**: `local.ERROR: Undefined array key "history"` from
`viewDetailContract.blade.php`, twice, one for each contract. Not fixed -
[ticket 03](03-find-remaining-breaks.md) owns it.

`storage/logs/laravel.log` across all 22 loads holds **those two errors and nothing else**. The
26 debug lines are the expected ones: 18 `skips the Related Contracts queries` from ticket 18,
4 `walked the contract family tree` from the downward walk, and 4 `walked up the contract
family tree` from the new one. Four, not two, because `?tab=historical` falls into the same
branch as the Details tab.

Browser console on `101101?tab=details`: the same nine entries ticket 20 and ticket 15 recorded
- three accessibility issues, one deprecation, one 403 for an asset, one Tagify warning, three
logs - plus **two font preload warnings** (`fa-brands-400` and `tabler-icons` woff2, "preloaded
but not used"). Those two are asset preload timing, not this change; they appear because the
page now finishes before the fonts are wanted.

### Written down, not fixed

- **`Contract::select('*')->whereIn('id', $parentContractArr)` still binds a list of ids**, and
  it still reads all 111 columns for the five cells the Previous Contracts table prints. The
  list is now the ancestor chain, so it is short - 3 at the deepest point in this set. Passing
  the recursive query as a subquery is [ticket 09](09-replace-wherein-with-joins.md)'s, and the
  narrow select is the same shape ticket 20 did for `$contractsoldothers`.
- **The second copy of the old parent-walk shape is at
  [ContractController.php:10769](../../../Modules/Contract/app/Http/Controllers/ContractController.php:10769),
  and it is another page.** Left alone, as this map's scope says. A later effort on that page
  can call `ancestorContractIds()`.
- **The slowest query on the Details tab is now 7-11 ms, so the tab's remaining second is not
  one query any more.** It is the query count: 619 on `101101`, 426 on `1`. That is
  [ticket 13](13-visible-to-scope.md)'s.
