# What changes in `goalapp_apollo` would help performance? (note only, change nothing)

Type: `wayfinder:research` · Status: **open** · Blocked by: nothing

## Question

`goalapp_apollo` is **never changed** — no schema, no data, no seeds. That stands
([CLAUDE.md](../../../CLAUDE.md)).

But the dev wants a note for a later effort: **if** that database could be changed, which changes would
make the contracts dashboard faster, and by how much? Read-only investigation, ending in a written
list. Nothing is applied. Nothing is prescribed for this spec.

### What to look at

1. **Which of this page's queries actually hit `goalapp_apollo` at all.** Establish this first — if the
   dashboard never touches it, the answer is "nothing", and that is a useful answer. Check the second
   connection in [config/database.php](../../../config/database.php), every model with a `$connection`
   property, and any cross-database name in a raw query (`goalapp_apollo.` written into SQL).
2. **The user, role and branch lookups.** [Helpers.php:323](../../../app/Helpers/Helpers.php:323)
   builds the reachable-branch list that the whole visibility rule depends on. Where do those rows
   live? If they live in `goalapp_apollo`, an index or a cache there is on the dashboard's critical
   path.
3. **The menu composer.** `MenuServiceProvider`'s `View::composer('*')`
   ([MenuServiceProvider.php:25](../../../app/Providers/MenuServiceProvider.php:25)) owns **all 92
   overhead queries** on every page: 13 `information_schema` + 14 `admin_settings` + 65 `menu_configs`
   ([ticket 11](11-per-request-overhead.md)). Which database are `admin_settings` and `menu_configs`
   in? If they are in `goalapp_apollo`, the cheapest fix in the whole application sits on the wrong side
   of the "do not touch" line, and the dev needs to know that.
4. **The 1000-bound-parameter bug.** MariaDB's `in_predicate_conversion_threshold = 1000` is a
   **server** setting, not a database one, so it applies to `goalapp_apollo` too — and that database has
   **2,886 contracts** ([ticket 12](12-approvals-empty.md)). Worth stating plainly in the note: any
   `whereIn` anywhere in the application, against either database, is on the broken path. The fix is a
   server variable or a code change, not a schema change.
5. **Indexes that would help, with the cost of each.** For each one: the query it serves, the rows read
   now, the rows read after, and the write cost. Extrapolated where it cannot be measured, and say so.

### Rules for this ticket

- **Read-only.** `SELECT`, `SHOW`, `EXPLAIN`, `information_schema`. No `ALTER`, no `INSERT`, no
  `UPDATE`, no seeder, no migration, not even in a transaction.
- Nothing measured here goes into [spec.md](../spec.md) as work. It goes into the note.
- **Deliverable:** `research/goalapp-apollo-note.md` — a plain list, biggest expected win first, each
  item with what it costs and how confident the number is. The dev keeps it for a later effort.

## Answer

<!-- filled on resolution -->
