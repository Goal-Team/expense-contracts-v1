# 20 — `$contractsoldothers` scans the whole table for one small list

Type: `wayfinder:task` (AFK)
Blocked by: nothing. **Top of the map now that ticket 18 has landed.**
Status: CLOSED

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

## Resolution

Status: **CLOSED**, 2026-08-22. Two commits: `5ffd9c1` (the index) and `8ae50df` (the columns).

### First, the two traps

**`availableContracts()` does not touch this list.** `relatedContractLists()` passes
`$contractspartsList`, `$contractsparentList` and `$contractsSubseqList` through it.
`$contractsoldothers` goes straight to the view. `grep` over `Modules/`, `app/` and `resources/`
finds one reader on this page: the `@foreach` at
[viewDetailContract.blade.php:2253](../../../Modules/Contract/resources/views/contract/viewDetailContract.blade.php:2253).
So the columns could be narrowed with nothing to sort out first.

**`Contract::boot()` does overwrite the select, and the fix works.** A temporary
`Log::debug($query->toSql())` printed the real SQL on a live page load:

```
select `id`, `contract_name`, `signing_date`, `currency`, `currency_value`, `fixed_date`,
`contract_end_date` from `contracts`
where (`catgoery_id` = ? and `department_id` = ? and `contract_type` = ?) and not `id` = ?
```

`withoutGlobalScope('accessLevelSelect')` holds. `ContractRoledBasedScope` stays - it only adds
`WHERE` clauses, so it costs the select list nothing. The temporary line is removed and `git status`
is clean.

### `EXPLAIN`, before and after

```
explain select * from contracts
where catgoery_id='2' and department_id=21 and contract_type='41' and not id=100479
```

| | before | after |
|---|---|---|
| `type` | `ALL` | `ref` |
| `possible_keys` | `PRIMARY` | `PRIMARY`, `contracts_type_department_category_index` |
| `key` | `NULL` | `contracts_type_department_category_index` |
| `key_len` | `NULL` | 171 |
| `ref` | `NULL` | `const,const,const` |
| `rows` | 1509 | **1** |
| `Extra` | `Using where` | `Using index condition; Using where` |

**One surprise, and it matters.** Write the same `EXPLAIN` with **numeric** literals -
`catgoery_id=2 ... contract_type=41` - and the optimiser goes back to `type ALL`, `key NULL`,
`rows 1509`. `catgoery_id` and `contract_type` are `TEXT` columns, so a numeric comparison forces a
number conversion on every row and no index can serve it. Eloquent binds the values as strings, so
the real page gets the index. Anyone who writes a number into that filter loses it silently.

### The winning column order, and why

`(contract_type, department_id, catgoery_id)`, not ticket 11's
`(catgoery_id, department_id, contract_type)`.

| column | distinct values | average rows per value |
|---|---|---|
| `contract_type` | 73 | 41 |
| `department_id` | 36 | 84 |
| `catgoery_id` | 3 | 1,006 |

All three tests are equality, so **this** query runs the same on either order - `rows 1` both ways.
The order is for the next query that uses only part of the index. A leading `catgoery_id` has 3
values and hands that query a third of the table.

The set is very selective: 2,633 of the 3,018 rows sit in a group of their own, and the biggest group
holds 12. So the old query read the whole table to return a handful of rows, and often none.

### Build time

**208 ms** on 3,018 rows. The `(parentcontract, id)` index took 474 s on the same table, so this was
the surprise of the ticket. The difference is what the index holds: three narrow columns with a
20-character prefix on the two `TEXT` ones, and no `id`, so InnoDB never reads the off-page text
blobs that make the table 110 MB. **This one needs no maintenance window.**

`contract_type` and `catgoery_id` are `TEXT`, and MariaDB refuses to index a whole `TEXT` column -
"Specified key was too long; max key length is 3072 bytes". A prefix length is required and
`$table->index()` cannot write one, so
[the migration](../../../database/migrations/2026_08_22_000002_add_category_department_type_index_to_contracts.php)
uses `DB::statement`. It names no character set and no collation, and `down()` drops exactly the one
index `up()` adds.

### What the blade actually needs

Seven columns. The table prints five cells and the first one is a link:

| column | used for |
|---|---|
| `id` | the `href` |
| `contract_name` | Contract Name, decrypted |
| `signing_date` | Signing Date, decrypted |
| `currency` | Contract Value, decrypted |
| `currency_value` | Contract Value, decrypted |
| `fixed_date` | Effective Date, decrypted |
| `contract_end_date` | Onetime end date, decrypted |

`select *` read all 111 columns of a 9,390-byte row for those seven.

**`contractPartyList` is not needed, and that is the third win.** `Contract::$with` eager-loads it on
every `Contract` query. This table shows no party data, so `without('contractPartyList')` drops one
query. It is small but free.

`$contractsold`, two lines above, is narrowed the same way: it is read for `catgoery_id`,
`department_id` and `contract_type` and nothing else. It stays a query on purpose - a null result is
the guard [ticket 14](14-correctness-bugs.md) added for a contract the user may not see.

### The numbers, each half on its own

Report rows 7 and 8. `100479?tab=details`, warm, `DEBUGBAR_ENABLED=false` confirmed at
[.env](../../../.env) line 34.

| | TTFB | server `total_ms` | queries | the query itself |
|---|---|---|---|---|
| before (row 6) | 4,088-5,233 ms | 4,075-5,220 | 360 | 928-1,823 ms |
| after the index | 3,295-3,433 ms | 3,295-3,433 | 360 | **under 5 ms** |
| after the narrowed select | 2,997-3,576 ms | 2,972-3,567 | **359** | under 5 ms |

The index is the whole time win. "Under 5 ms" is the honest reading: the query drops out of the perf
record's ten-slowest list, and the smallest entry left on that list is 4.31-7.39 ms.

The narrowed select wins one query, not time, because the index already stopped the scan - about one
row is read either way. On control contract `1?tab=details` the count falls **415 to 413**, by two:
`$contractsoldothers` returns 11 rows there and 0 on 100479, and Laravel skips an eager load on an
empty result.

**What is left on the Details tab is the child walk**, 1,977-3,377 ms of the remaining 3 s. It varies
run to run more than anything else on the page. [Ticket 15](15-recursive-child-walk.md).

### The rows are the same

`100479?tab=details` and `1?tab=details` were both fetched before the change and after, same session,
warm, debug bar off. Each pair is **identical when whitespace is ignored** (`diff -w`), and each pair
has the same byte count: 239,076 and 261,223.

The `Category Previous Contracts` block was pulled out of both documents and compared on its own:

- `1?tab=details` renders **11 rows**, before and after, with the same ids and the same cells. The
  database agrees: contract 1's group holds 12 rows, so 11 others.
- `100479?tab=details` renders **0 rows**, before and after. Its group holds only itself. So contract
  1 is the contract that proves this table, not 100479.

### Every tab loaded

Both contracts, all ten tab values and no `?tab`, after each half. Same result both times, and it
matches [ticket 18](18-guard-the-scans-by-tab.md)'s table.

| contract | tab | status | document characters |
|---|---|---|---|
| 100479 | `details` | 200 | 239,076 |
| 100479 | `pre-approval` | 200 | 105,779 |
| 100479 | `timeline` | 200 | 105,779 |
| 100479 | `edit` | 200 | 326,231 |
| 100479 | `flow` | 200 | 72,388 |
| 100479 | `history` | 200 | 61,437 |
| 100479 | `historical` | **500** | IIS error page |
| 100479 | `attachment` | 200 | 61,350 |
| 100479 | `obligation` | 200 | 85,534 |
| 100479 | `e-stamp` | 200 | 67,003 |
| 100479 | none | 200 | 105,779 |
| 1 | `details` | 200 | 261,223 |
| 1 | `pre-approval` | 200 | 100,708 |
| 1 | `timeline` | 200 | 100,708 |
| 1 | `edit` | 200 | 321,430 |
| 1 | `flow` | 200 | 89,080 |
| 1 | `history` | 200 | 62,752 |
| 1 | `historical` | **500** | IIS error page |
| 1 | `attachment` | 200 | 57,345 |
| 1 | `obligation` | 200 | 81,584 |
| 1 | `e-stamp` | 200 | 62,993 |
| 1 | none | 200 | 100,708 |

`?tab=historical` fails the same way as before: `local.ERROR: Undefined array key "history"` in
`storage/logs/laravel.log`, from `viewDetailContract.blade.php`. Not touched -
[ticket 03](03-find-remaining-breaks.md) owns it.

`storage/logs/laravel.log` holds no new error and no new warning across all 44 loads, only the
existing `Contract detail page skips the Related Contracts queries` debug lines from ticket 18. The
browser console on `100479?tab=details` holds the same nine entries ticket 18 recorded - three
accessibility issues, one deprecation, one 403 for an asset, one Tagify warning, three logs. Nothing
new.

### Written down, not fixed

- **A numeric literal against `catgoery_id` or `contract_type` loses the index**, because both are
  `TEXT`. Nothing on this page does it today. Any future filter on those columns must bind a string.
  The real fix is to make them integer columns, which is a data migration and not this ticket's.
- `$contractsold` repeats a row the caller already holds. `relatedContractLists($id, $contracts)`
  receives `$contracts`, which is this contract, already loaded with all its columns. The extra query
  survives because its `null` result is the visibility guard. Folding the guard into the caller would
  remove one query and is [ticket 12](12-delete-waste.md)'s or
  [ticket 13](13-visible-to-scope.md)'s.
