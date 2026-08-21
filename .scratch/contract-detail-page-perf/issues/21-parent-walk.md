# 21 — The parent walk is now the slowest query on the page

Type: `wayfinder:task` (AFK)
Blocked by: nothing. Ticket 15 already wrote the replacement.
Status: OPEN

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
