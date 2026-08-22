# 11 — Add the six indexes this page needs

Type: `wayfinder:task` (AFK)
Blocked by: 04 — take the baseline before adding an index, or the baseline measures the wrong thing
Status: CLOSED 2026-08-22 — four indexes applied across tickets 11, 20 and this one's Resolution. Two of the six
were dropped on measurement, not on opinion. See the Resolution at the end.

## Question

Nothing to decide. Ticket 08 named six indexes the page needs and does not have. Add them.

**Migrations get applied on the local dev database and reported after** — the dev's call 2026-08-21,
so this does not wait. Every migration still needs a working `down()`, and it is still committed as a
file. Production stays the dev's to run.

## The six

| Table | Columns | Why |
|---|---|---|
| `contracts` | `parentcontract` | The `contractParent` lazy load scans 3,018 rows, 58 times per request. Biggest single win on the list. |
| ~~`contracts`~~ | ~~`catgoery_id, department_id, contract_type`~~ | **DONE, [ticket 20](20-contractsoldothers-scan.md).** Applied as `(contract_type, department_id, catgoery_id)` - most selective column first. |
| `contracts_history` | `id` | Line 410 filters on `id`, which is **not** the primary key of that table, so it scans. |
| `user_action_log` | `group_id` | Line 951 scans it. Note the result feeds `$signedHistory`, which no blade reads — ticket 12 may delete the query instead, and then this index is not needed. **Check ticket 12 first.** |
| `contract_party_data` | `contract_party_exe_id` | Line 731 scans 6,940 rows. |
| `custom_field_data` | `custom_field_group_id, custom_field_id, custom_field_group` | The table has no index at all beyond its primary key, and `dataCustomFields()` is called about 48 times per request. |

## Rules that apply

- **A migration never names a character set or a collation.** Settled by the dashboard effort: the
  collation depends on the client's database server and a migration cannot know it. An index migration
  names no charset anyway — keep it that way.
- Every migration has a working `down()` that drops exactly what `up()` added.
- Name each index so the `down()` can drop it by name.

## Done when

- Six migration files committed, each with a working `down()`.
- Applied on `apollo_contracts_expense` only.
- `SHOW INDEX` confirms each one exists.
- A report row per index, or one row for the set: query count and TTFB after.
- **Say if an index did nothing.** An index that does not move the number is still worth reporting,
  because the next person will otherwise add it again.

## Note from ticket 02, 2026-08-21

Make the `contracts.parentcontract` index a **covering** one on `(parentcontract, id)`. It then
serves a second reader as well as the `contractParent` lazy load: the child-contract
`GROUP_CONCAT` query at
[ContractController.php:780](../../../Modules/Contract/app/Http/Controllers/ContractController.php:780),
which scans the whole table once per row and needs only those two columns.

Measured 2026-08-21 on the re-seeded set, same 3,018 `(id, parentcontract)` pairs, same SQL:

| source | time |
|---|---|
| two-column temporary table | 3 s |
| the real `contracts` table | over 120 s, then the IIS FastCGI timeout |

`contracts` now reports 110 MB of `DATA_LENGTH` for 27 MB of content, because `ROW_FORMAT=Dynamic`
pushes the long encrypted text columns off-page and each off-page value costs a whole 16 KB page.
Local `innodb_buffer_pool_size` is 16 MB, so every scan reads from disk.

Migration drafted for review, **not run**:
[proposed-migration-parentcontract-index.php](../proposed-migration-parentcontract-index.php).

## Progress — 2026-08-22

**`contracts(parentcontract, id)` applied.** Migration
[2026_08_22_000001_add_covering_index_to_contracts_parentcontract.php](../../../database/migrations/2026_08_22_000001_add_covering_index_to_contracts_parentcontract.php),
commit `378ba21`. Applied on the local dev database under the dev's standing approval. `SHOW INDEX`
confirms both columns.

Result: the page went from **HTTP 500** on the FastCGI timeout to **4,422 ms TTFB**. Report row 2.

**Two things to carry to production**, both worth more than the index itself:

- **The index took 474 s to build** on 3,018 rows. On a client database this needs a maintenance
  window, not a quiet deploy. Say so in the deployment notes.
- **This hides the real problem.** The child-contract query stays quadratic, so it gets worse as any
  client's data grows. [Ticket 15](15-recursive-child-walk.md) is the actual fix. Do not treat the
  index as done work on its own.

**Five indexes left**, and one of them may not be needed: the `user_action_log.group_id` one exists
only to serve `$signedHistory`, which no blade reads. Check [ticket 12](12-delete-waste.md) first — if
that query is deleted, the index is not needed.

**`contracts(contract_type, department_id, catgoery_id)` applied by [ticket 20](20-contractsoldothers-scan.md).**
Migration
[2026_08_22_000002_add_category_department_type_index_to_contracts.php](../../../database/migrations/2026_08_22_000002_add_category_department_type_index_to_contracts.php),
commit `5ffd9c1`.

The column order is reversed from the row above, on purpose. All three tests are equality, so any
order serves that query, but a leading `catgoery_id` has only 3 values and gives the next partial user
a third of the table. Measured on the seeded set: `contract_type` about 41 rows per value,
`department_id` 84, `catgoery_id` 1,006.

Two facts worth carrying:

- **It built in 208 ms**, not 474 s. It holds three narrow columns and no `id`, so InnoDB never
  touches the off-page text. Only the `(parentcontract, id)` index needs a window.
- **`contract_type` and `catgoery_id` are `TEXT` columns.** MariaDB refuses the whole column - "key
  was too long" - so the index needs a prefix length, and `$table->index()` cannot write one. That
  migration uses `DB::statement`.

**Four indexes left.** One of them may still not be needed: `user_action_log.group_id` serves
`$signedHistory`, which no blade reads. Check [ticket 12](12-delete-waste.md) first.

## Resolution — 2026-08-22

**Four of the six are in. The other two are dropped, and the row counts are the reason.**

Applied, with the commit that did it:

| Table | Columns | Applied by | Build | Effect |
|---|---|---|---|---|
| `contracts` | `parentcontract, id` | this ticket, `378ba21` | **474 s** | HTTP 500 to 4,422 ms |
| `contracts` | `contract_type(20), department_id, catgoery_id(20)` | [ticket 20](20-contractsoldothers-scan.md), `5ffd9c1` | 208 ms | the query 928-1,823 ms to under 5 ms |
| `contracts_history` | `id, created_at` | this Resolution | 307 ms | no number moves |
| `custom_field_data` | `custom_field_group_id, custom_field_id, custom_field_group(20), id` | this Resolution | 221 ms | no number moves |

Dropped:

- **`contract_party_data(contract_party_exe_id)`.** The column holds **one** distinct value across
  6,940 rows: 3,018 rows hold `1` and 3,922 hold `NULL`. A filter that matches 44% of the table
  cannot use an index - MariaDB scans whatever we build. The query is also
  [ticket 09](09-replace-wherein-with-joins.md)'s to rewrite, at
  [ContractController.php:445](../../../Modules/Contract/app/Http/Controllers/ContractController.php:445).
  If a client's data spreads that column over many parties, the index becomes worth adding; on this
  data it is dead weight on every write.
- **`user_action_log(group_id)`.** Held, as this ticket already said, for
  [ticket 12](12-delete-waste.md). It serves `$signedHistory`, which no blade reads. The table holds
  49 rows.

### The two new ones move no number, and that is reported, not hidden

Report row 18. Same **368** queries on `?tab=details` and **96** on `?tab=edit` before and after, and
the millisecond spreads overlap. `EXPLAIN` proves both indexes are picked up - full scan to
`type ref, rows 1`, `key_len` 8 and 30 - so they are doing their job on a table too small for the job
to show:

| Table | Rows on the seeded set |
|---|---|
| `contracts_history` | 17 |
| `custom_field_data` | 6 |

They are kept because both tables grow with use on a client database and neither growth has a ceiling.
`contracts_history` gains a row every time any user saves any contract. `custom_field_data` gains a
row per custom field per contract, and this page calls `dataCustomFields()` 8 times on the Details tab
and about 48 on the tabs that draw the whole set.

### One thing to remember

**Read the row count before writing an index migration.** Ticket 08 named these six by reading the
code, which is the right way to find a missing index and the wrong way to size it. Four of the six sat
on tables of 6, 17 and 49 rows, or on a column with one value. The two that paid - both on
`contracts` - are the two on the only large table on this page. One `information_schema.statistics`
query and one `COUNT(DISTINCT ...)` would have sorted the list in a minute.
