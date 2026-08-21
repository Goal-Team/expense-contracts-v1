# Decide the index and migration set

Type: grilling
Status: resolved
Assignee: kader (2026-08-20)
Blocked by: 08

## Question

The index situation is worse than "missing a few":

```
contracts (110 columns):  PRIMARY(id), unique_contract_name_hash, contracts_legal_advisor_id_index
contract_party_data:      PRIMARY(id)   <- that is the entire index list
```

`contract_party_data` has **no index on `custom_field_group_id` or `contract_party_location_id`** — the
two columns every party lookup joins on. That is a full table scan per contract inside the N+1, and the
`EXISTS` subquery the redesign in `08-query-layer-redesign` depends on would inherit the same scan.
Nothing indexes `contracts.status`, `contract_status`, `substatus`, or `contract_type` either.

There is also no `create_contracts_table` migration anywhere — the table predates this migration set and
every contracts migration is a `Schema::table` guarded by `Schema::hasTable`.

Decide, with the dev:

1. **Which indexes**, in what order of value, and composite vs single-column — driven by the actual
   query shapes settled in `08-query-layer-redesign`, not guessed in advance.
2. **Whether the index alone closes the gap.** It is entirely possible that indexing
   `contract_party_data` fixes the page without the aggregate rewrite. If so, the spec should say so
   and the rewrite becomes optional — measure before committing to the larger change.
3. **Migration safety on a 110-column table with live production data** — online DDL vs. a maintenance
   window, and how long the index build is expected to take at production row counts.
4. **Whether the missing `create_contracts_table` matters** for anyone provisioning a fresh environment,
   or is accepted debt.
5. **Rollback** — every prescribed migration needs a working `down()`.

Hard constraint from the dev: **migration files are written and shown for review before anything is
applied.** Never run them directly.

## Answer

Settled with the dev 2026-08-20.

### Measured first: the rewrite needs no indexes at all at this scale

Run against the seeded database (3,018 active contracts, 6,940 party rows, 12,816 approval rows),
with **no new indexes**, warm cache, timed with `SHOW PROFILES`:

| query shape from [ticket 08](08-query-layer-redesign.md) | time |
|---|---|
| all 15 counters — `GROUP BY contract_status, substatus` + `EXISTS` visibility | **13–17 ms** |
| approvals joined to the visibility set | **64–72 ms** warm, **431 ms** cold |

So the query-layer rewrite replaces **12.6 s of controller time with about 15 ms** on its own. The
counters full-scan 2,790 contracts and materialise a full scan of 6,940 party rows and still finish
in 13 ms, because at this size the tables are small. `EXPLAIN` confirms both scans:
`type=ALL` on `contracts`, `MATERIALIZED ... type=ALL` on `contract_party_data`.

**The index question is therefore not "what makes the dashboard fast" — the rewrite does that.** It
is "what stops this getting slow again as rows pile up", which is a much smaller question.

### 1. Which indexes (item 1)

**Only `approval_contracts`.** That table is the one place an index clearly earns its keep today:
12,816 rows, 17.5 MB, `PRIMARY` only, so every approvals query reads the whole table
(`EXPLAIN`: `type=ALL`, `rows=12816`).

Prescribed:

- `approval_contracts.contract_id` — single column. This is the join the rewrite depends on.
- `approval_contracts (approver_email, approval_status_plain)` — composite, in that order, over the
  two shadow columns [ticket 08](08-query-layer-redesign.md) adds. Email first because the
  "My Actionable Items" counter always filters on the logged-in user first and only then groups by
  status, so the email is the selective half. 500 approvers over 60,000 rows is ~120 rows per
  approver.

**Not prescribed:** nothing on `contracts` (`status`, `contract_status`, `substatus`,
`contract_type`) and nothing on `contract_party_data` for performance reasons. They measure fast
unindexed, and every extra index costs write time on a 110-column table. Recorded in the spec as
"measure first if rows grow well past 10,000", not as work.

### 2. Does the index alone close the gap? (item 2)

**No — and the reverse turned out to be true.** This ticket was written expecting indexes might
make the aggregate rewrite optional. The measurement says the opposite: the **rewrite** closes the
gap by itself with no indexes, and the indexes close nothing on their own. Indexing
`contract_party_data` would speed up the old N+1 path, but the old path is 3,018 separate queries —
no index turns 3,018 round trips into one.

The order in the spec is therefore: **rewrite the query layer and stop dumping unused data into the
page, then measure, then index.** The dev was explicit about this ordering — the N+1 and the
throwaway page payload are the lion's share, and load testing at bigger row counts comes after they
are fixed, not before.

### 3. The party table: convert it properly (dev's call)

`contract_party_data` is **MyISAM, latin1_swedish_ci**, while `contracts` is InnoDB, utf8mb4. Its
two join columns, `contract_party_type` and `contract_party_location_id`, are **`TEXT`** — which
cannot be indexed without a prefix length, and comparing them across a collation boundary would not
use an index well anyway.

The dev chose to **fix the root problem rather than work around it**: convert the table to InnoDB and
utf8mb4, and change the two join columns from `TEXT` to `varchar`, then index them. This is a full
table rebuild and needs a window. It is prescribed as its **own migration, separate from the
dashboard change**, so the dashboard fix is not blocked on it and can be measured independently.

`custom_field_group_id` is already `int(11)` and can be indexed safely with no type change.

### Standing rule from the dev: character set and collation

**Every table and column this effort creates or changes uses character set `utf8mb4` and collation
`utf8mb4_unicode_ci`.**

The dev first asked for `utf8mb4_0900_ai_ci`, then — on being shown the problem — asked for whatever
works on both MySQL 8 and MariaDB, general and case-insensitive, since no non-latin characters are
stored. `utf8mb4_unicode_ci` is that choice.

Verified on the local server (MariaDB 10.4.24):

| collation | exists locally | exists on MySQL 8 | case-insensitive |
|---|---|---|---|
| `utf8mb4_0900_ai_ci` | **no** — `SHOW COLLATION` returns nothing | yes | yes |
| `utf8mb4_general_ci` | yes (the database default) | yes | yes |
| `utf8mb4_unicode_ci` | yes | yes | yes |

`utf8mb4_0900_ai_ci` is dropped: it is MySQL 8 only, so a migration naming it fails on this server.

**Why `utf8mb4_unicode_ci` over `utf8mb4_general_ci`**, both being portable and case-insensitive:
`contracts` and `approval_contracts` are **already `utf8mb4_unicode_ci`**, so a new column added to
`approval_contracts` inherits its own table's collation and no mixed-collation comparison can appear
inside the tables this work changes. `utf8mb4_general_ci` would create exactly that mismatch. It is
also already the value in [config/database.php:56](../../../config/database.php:56), so no config
change is needed. Unlike the 0900 collation, `utf8mb4_unicode_ci` means the same thing on both
servers, so local and production sort and match alike — no local-versus-production divergence to
warn about.

The `contract_party_data` conversion below therefore targets **utf8mb4 / utf8mb4_unicode_ci**, which
also brings it into line with the `contracts` table it joins to.

**One thing this does not change:** every `_ci` collation treats `Terminated` and `terminated` as the
same value, while the PHP switch does not. That is exactly why
[ticket 08](08-query-layer-redesign.md) keeps the status fold in PHP. Choosing a case-insensitive
collation here does not reopen it.

### 4. The missing `create_contracts_table` (item 4)

**Accepted debt, written down.** No file produced in this effort. The consequence goes in the spec:
nobody can build a fresh environment from migrations alone, because `contracts` has no create
migration and every contracts migration is a `Schema::table` guarded by `Schema::hasTable`. A new
environment needs a schema dump. Worth its own effort; not a performance change.

### 5. Rollback and build times (items 3, 5)

Every prescribed migration ships a working `down()`:

- Index migrations: `down()` drops the index. Clean and instant.
- The party-table conversion: `down()` returns the engine to MyISAM, the collation to
  latin1_swedish_ci, and the two columns to `TEXT`. Written and reviewed, but note honestly in the
  spec that **converting back is not risk-free** — latin1 cannot hold every character utf8mb4 can,
  so a round trip is only safe if nothing wrote non-latin1 text in between.

**Expected build times.** Production row counts are unknown and production data is off limits, so
these are extrapolated from local measurements at the dev's assumed scale — **~10,000 contracts,
500 approvers, ~60,000 approval/workflow rows**:

| migration | rows | expected | lock |
|---|---|---|---|
| index `approval_contracts.contract_id` | ~60,000 (~82 MB by local bytes-per-row) | seconds | InnoDB online DDL, no table lock |
| index `approval_contracts (approver_email, approval_status_plain)` | ~60,000 | seconds | InnoDB online DDL, no table lock |
| backfill the two shadow columns | ~60,000 | minutes — every row must be decrypted in PHP | no DDL lock; see the backfill plan, still open |
| convert `contract_party_data` to InnoDB + utf8mb4 + varchar | ~23,000 | seconds | **full table rebuild, table locked** — needs a window |

The two `approval_contracts` indexes are small enough to run online. The party-table conversion
takes a window, and the **backfill is the long pole** — it is PHP decrypt work per row, not DDL, and
it needs the hostname-derived encryption key to resolve. That plan is still open on the map.

**A number worth keeping:** the numbers above are byte-per-row extrapolations, not measurements. No
index was built on this database, because every schema change goes through a reviewed migration.

## Correction, 2026-08-20

This ticket called the shadow-column backfill **the long pole**. It is not.
[Ticket 15](15-approval-backfill-plan.md) measured it: **27,734 values decrypted in 0.49 s** locally,
so roughly **2 seconds one time** at the assumed ~60,000 production rows. The long pole, if there is
one, is the `contract_party_data` engine/charset conversion — not this.
