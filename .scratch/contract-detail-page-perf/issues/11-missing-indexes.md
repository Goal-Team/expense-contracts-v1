# 11 — Add the six indexes this page needs

Type: `wayfinder:task` (AFK)
Blocked by: 04 — take the baseline before adding an index, or the baseline measures the wrong thing
Status: OPEN

## Question

Nothing to decide. Ticket 08 named six indexes the page needs and does not have. Add them.

**Migrations get applied on the local dev database and reported after** — the dev's call 2026-08-21,
so this does not wait. Every migration still needs a working `down()`, and it is still committed as a
file. Production stays the dev's to run.

## The six

| Table | Columns | Why |
|---|---|---|
| `contracts` | `parentcontract` | The `contractParent` lazy load scans 3,018 rows, 58 times per request. Biggest single win on the list. |
| `contracts` | `catgoery_id, department_id, contract_type` | Line 718 filters on all three and scans the table. |
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
