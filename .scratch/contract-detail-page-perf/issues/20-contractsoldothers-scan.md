# 20 — `$contractsoldothers` scans the whole table for one small list

Type: `wayfinder:task` (AFK)
Blocked by: nothing. **Top of the map now that ticket 18 has landed.**
Status: OPEN

## Question

Nothing to decide. It is the second most expensive query on the page and it is a plain missing index
plus a `select *`.

## What it is

[ContractController.php:723](../../../Modules/Contract/app/Http/Controllers/ContractController.php:723),
now inside `relatedContractLists()`:

```sql
select * from contracts
where (catgoery_id = ? and department_id = ? and contract_type = ?)
  and not id = ?
```

Measured at **898–1,045 ms**, about 24% of the Details tab. Two reasons:

- **No index covers the triple.** `contracts` has `PRIMARY`, `unique_contract_name_hash`,
  `legal_advisor_id` and the new `(parentcontract, id)`. Nothing on `catgoery_id`, `department_id` or
  `contract_type`, so it scans all 3,018 rows.
- **`select *` on a 9,390-byte row.** `contracts` is 110 MB against a 16 MB buffer pool, so every
  matching row is read off disk in full. Ticket 16 found the biggest group holds 12 rows and 374 groups
  hold more than one — so the query reads the whole table to return a handful of rows.

The result feeds the `Category Previous Contracts` table at
[viewDetailContract.blade.php:2244](../../../Modules/Contract/resources/views/contract/viewDetailContract.blade.php:2244),
which renders on the Details tab and **carries `d-none`**, so the browser hides it. The server still
builds it and still sends the bytes. That is worth remembering but it is not a reason to delete it —
[CLAUDE.md](../../../CLAUDE.md) says leave behaviour alone unless it throws or costs time.

## Do both halves

1. **The index.** `contracts (catgoery_id, department_id, contract_type)`. It is one of the six ticket 11
   already asked for, so this ticket takes that one and [ticket 11](11-missing-indexes.md) drops to four.
   Column order matters: put the most selective column first, and check with `EXPLAIN` that the
   optimiser actually uses it rather than assuming.
2. **The columns.** Find out which columns the blade table really reads, and select only those. Watch
   the trap: **`Contract::boot()` adds a global `select('*')` scope that runs after your own `select()`
   and overwrites it** ([Contract.php:114](../../../app/Models/Contract.php:114)). Use
   `withoutGlobalScope('accessLevelSelect')` for that one scope only, and leave
   `ContractRoledBasedScope` alone — that one is the visibility rule.

Also check whether `$contractsoldothers` is passed through `availableContracts()`. If it is, narrowing
the columns will break the decoration loop, which reads whatever it reads. Sort that out before
narrowing.

## Rules that apply

- The migration names no character set and no collation.
- It has a working `down()` that drops exactly what `up()` adds.
- **Applied on the local dev database, then reported** — the dev's standing approval. Production stays
  theirs. **Say how long the build took**: the `(parentcontract, id)` index took 474 s on 3,018 rows, so
  a client needs a window.

## Done when

- `EXPLAIN` shows the index used, not merely present.
- The Details tab query count and TTFB in the report, before and after each half separately — the index
  and the narrower select are two changes and each deserves its own number.
- The `Category Previous Contracts` table renders the same rows. Compare the rendered HTML, ignoring
  whitespace.
- **Every tab still loads.** Ticket 18's table in
  [ticket 18](18-guard-the-scans-by-tab.md) is the list to walk.
