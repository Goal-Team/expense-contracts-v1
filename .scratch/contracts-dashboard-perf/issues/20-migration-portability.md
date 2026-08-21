# Should migrations name a collation and a column width at all?

Type: `wayfinder:grilling` · Status: **resolved** · Assignee: kader (2026-08-20) · Blocked by: nothing

## Question

The dev reviewed the two migration files 2026-08-20 and passed them, with two objections:

1. **Hardcoding the collation is a bad idea.** This app is installed for different clients on
   different database servers. A collation named in a migration file may not exist there.
2. **Is `varchar(20)` enough?**

Both are about the same thing: a migration written against *this* machine's database, shipped to
somebody else's.

## What was measured before writing this ticket (2026-08-20)

### On the collation — the dev is right, and the proof is already on this machine

There are **8 client databases** on this local MySQL instance. `approval_contracts` collation:

| collation | databases |
|---|---|
| `utf8mb4_unicode_ci` | `apollo_contracts_expense` — **1 of 8** |
| `utf8mb4_general_ci` | the other **7**, and both databases' own *default* is `utf8mb4_general_ci` |

So `utf8mb4_unicode_ci` — named explicitly in both migration files, and written into the map's Notes
as a standing rule on 2026-08-20 — is the odd one out. Ship those files to any of the other seven
and the migration **creates the mixed-collation problem it was written to remove**: a column in
`utf8mb4_unicode_ci` inside a table in `utf8mb4_general_ci`.

`contract_party_data` is `MyISAM` / `latin1_swedish_ci` in **all 8**, so the conversion itself is
right everywhere. Only the target it names is wrong.

Version support is the smaller half of the worry but real: `utf8mb4_0900_ai_ci` was the first choice
on this effort and was dropped because MariaDB 10.4 does not have it
([ticket 09](09-index-and-migrations.md)). The same trap in reverse exists — a MySQL-8-only server
has `utf8mb4_0900_ai_ci` and still has `utf8mb4_general_ci` and `utf8mb4_unicode_ci`, so those two
are the safe pair, but *naming* either one is still a guess about a database we cannot see.

**Also load-bearing, found while checking:** `contract_party_type` is compared as `'Internal'` in
most places and `'internal'` in two
([ContractController.php:725](../../../Modules/Contract/app/Http/Controllers/ContractController.php:725),
[:4358](../../../Modules/Contract/app/Http/Controllers/ContractController.php:4358)). Those two
queries only work because the collation is **case-insensitive**. Any migration that lands a `_bin` or
`_cs` collation on that column silently breaks them. Whatever rule this ticket sets must keep `_ci`.

### On `varchar(20)` — measured across every client database on the machine

Real maximum lengths in `contract_party_data`:

| database | rows | `contract_party_type` | `contract_party_location_id` | `custom_field_group_id` |
|---|---|---|---|---|
| `apollo_contracts_expense` | 6,940 | 10 | 3 | 6 |
| `goalapp_apollo` | 5,970 | 10 | 3 | 4 |
| `goal_kims` | 1,731 | 10 | 2 | 3 |
| `goalapp_kims_goal` | 1,715 | 10 | 2 | 3 |
| `contracts_demo` | 57 | 8 | 1 | 2 |
| `goal-contracts` | 50 | 8 | 1 | 2 |
| `legality_contratcs` | 2 | 8 | 1 | 1 |

`contract_party_type` is **not open-ended data** — it is a three-value set hardcoded in PHP:
`Internal` (8), `External` (8), `Intergroup` (10). Nothing in the app writes a fourth value; there is
no admin screen for it. 10 characters is the ceiling by code, not by luck.

`contract_party_location_id` holds a branch id as text. 3 characters at ~6,900 party rows;
`varchar(20)` holds 20 digits.

**So `varchar(20)` is enough** — 2× headroom on the one column that has a fixed set, and far more on
the numeric one. But "enough because I measured seven databases" is a different claim from "enough by
construction", and the dev's question is worth a rule, not just an answer.

**Note the same portability problem exists in column widths, not just collations:** `approval_status`
is `varchar(1000)` in 5 client databases, `varchar(250)` in `contracts_demo`, `varchar(100)` in
`goal-contracts`. The client schemas are already not identical. A migration that assumes a width is
making the same mistake as one that assumes a collation.

## The options

**A. Name nothing — inherit.** Drop every `CHARACTER SET` / `COLLATE` clause from column definitions.
An added or modified column with no clause takes the **table's** default, so it always matches the
table it lives in, on every client, on every server version. This is the fix for migration 1 and for
the `MODIFY` statements in migration 2.

The one statement that cannot inherit is `CONVERT TO CHARACTER SET` — converting away from latin1 has
to name a target. Two sub-options:

- **A1. Read it from the table it joins.** Look up `contracts`' character set and collation at run
  time and convert `contract_party_data` to that. This is exactly the goal — the two tables compare
  without a collation mismatch — expressed as the rule instead of as a guessed value. Works on any
  client whatever their collation is.
- **A2. Read it from Laravel's config.** `config('database.connections.mysql.charset')` and
  `.collation`. Simpler, but it describes what the *connection* uses, not what the tables use, and on
  this machine those disagree (`apollo_contracts_expense` tables are `unicode_ci`, the database
  default is `general_ci`).

**B. Keep naming a collation, but check first.** Query `information_schema.COLLATIONS` at the top of
`up()` and abort with a clear message if the named collation is missing. Honest, but it makes the
migration fail on 7 of 8 known clients rather than adapt to them.

**C. Widths: measure, don't assume.** For any `MODIFY` narrowing a column, read the real maximum
length first and fail loudly if it exceeds the target, instead of truncating silently. `varchar(20)`
stays, but the migration proves it rather than asserting it.

## What this ticket has to produce

1. A rule for **collation in migrations** on this effort, replacing the 2026-08-20 Notes rule that
   says "`utf8mb4_unicode_ci`, named explicitly in the migrations".
2. A rule for **column widths** in migrations that narrow a type.
3. Both migration files rewritten to follow it, shown for review again, still not applied.

**Recommendation: A + A1 + C.** Inherit everywhere it is possible to inherit, derive the one
conversion target from `contracts`, and make the narrowing check itself. That way nothing in either
file is a statement about a database we have not seen.

## Open sub-question for the dev

`contract_party_type` — leave at `varchar(20)`, or make it `varchar(32)` so a future fourth party
type cannot need a migration? Costs a few bytes in the composite index, nothing else.

## Correction, 2026-08-20 — there is no status column in either migration

The dev said `varchar(20)` looked too small **for status**. Checked: no migration on this branch
touches a status column at all.

`approval_status_plain varchar(20)` was real once — it is in
[ticket 15](15-approval-backfill-plan.md)'s plan — but it went away the same day the dev dropped
shadow columns. "My Actionable Items" decrypts in PHP instead
([report.md](../measurements/report.md) row 3), so there is nothing to size.

The only `varchar(20)` still live is `contract_party_type` and `contract_party_location_id` in
migration 2, and the dev has now approved 20 for party type.

**If [ticket 17](17-plain-columns-experiment.md) ever runs**, a status column comes back — and the
existing `approval_contracts.approval_status` is already `varchar(1000)` in 5 of 8 client databases
(`varchar(250)` and `varchar(100)` in two others). Making it plaintext **widens nothing and narrows
nothing**; the column already exists at 1000. So the width question never arises there either. Values
are `pending`, `approved`, `rejected` — longest 8. Written here so ticket 17 does not re-ask it.

---

## Resolution — 2026-08-20, the dev's call

**Status: resolved.**

### 1. The conversion stops being a migration

Dev's words: *"do not create migration for it. just change the collation to unicode_ci is fine
through script. I will take care of it at the time of deployment because the collation name depends
on the database type and version."*

So none of options A, B or C above. The migration file is **deleted**. In its place:
[database/manual/001-contract-party-data-innodb-utf8mb4.sql](../../../database/manual/001-contract-party-data-innodb-utf8mb4.sql)
— plain SQL, run by hand at deployment by someone who can see the client's database.

`utf8mb4_unicode_ci` is written into it, as asked, with a check at the top telling the person running
it to compare against `contracts` on that database and change the value if it differs. Given 7 of the
8 known client databases are `utf8mb4_general_ci`, that check will usually fire — so it is a step,
not a footnote.

**This crosses a [CLAUDE.md](../../../CLAUDE.md) rule** — *"every schema change goes through a Laravel
migration, no ad-hoc SQL scripts"* — and the dev knows: a migration cannot name a collation it cannot
see, and the alternative was making the migration guess. Noted once here, not argued.

**What moved with it.** The whole of migration 2, not only the collation line. The four steps are one
chain — engine, then character set, then `TEXT` → `varchar`, then the indexes — and every step after
the first needs the collation name. Splitting the chain would leave a migration that cannot run
without the script anyway.

**Migration 1 stays a migration.**
[2026_08_20_000001_add_index_to_approval_contracts_contract_id.php](../../../database/migrations/2026_08_20_000001_add_index_to_approval_contracts_contract_id.php)
adds one index and names no character set and no collation, so it is portable as written. Nothing
about it is a guess about someone else's database. **It is still not applied** and is still the dev's
to run.

### 2. Width: 32, and only on these two columns

Dev's words: *"32 is safe, but not for all columns."* Correct — `party_address` is a free-form
address and stays `TEXT`.

**The full list of columns this work changes the type of is two.** The dev said he had no idea what
other columns were involved; the answer is that there are none. `contract_party_data` has ten columns:

| column | type today | what this work does |
|---|---|---|
| `id` | `int(11)` | nothing |
| `custom_field_group_id` | `int(11)` | **indexed**, type unchanged |
| `contract_party_type` | `text` | **-> `varchar(32)`**, indexed |
| `party_sub_type` | `varchar(100)` | character set only |
| `contract_party_id` | `int(11)` | nothing |
| `contract_party_exe_id` | `int(11)` | nothing |
| `contract_party_location_id` | `text` | **-> `varchar(32)`**, indexed |
| `vendor_code` | `varchar(100)` | character set only |
| `party_address` | `text` | character set only — **stays `TEXT`** |
| `contact_details` | `varchar(255)` | character set only |

"Character set only" is the table-wide `CONVERT TO` in step 2. It changes the character set of all six
text columns and the type of none of them. That is deliberate: a half-converted table still has a
collation boundary inside it.

No status column is involved — see the correction above.

---

## Carried out — 2026-08-21

The dev asked for a script he could pass the collation to, with `utf8mb4_unicode_ci` as the default,
and gave the go-ahead to run it.

**The script is an artisan command, not the SQL file.**
[ConvertPartyDataCollation](../../../app/Console/Commands/ConvertPartyDataCollation.php) —
`php artisan contract:convert-party-data`. `--collation` defaults to `utf8mb4_unicode_ci`,
`--width` to 32, and **nothing runs without `--apply`** — bare, it only checks.

Four checks it will not run past:

1. the collation exists on this server;
2. it **ignores case** — proved by asking the server `'a' = 'A' COLLATE <name>`, not by reading `_ci`
   off the end of the name;
3. it matches what the `contracts` table uses, or `--force-mismatch` is given;
4. no existing value is longer than the target width, so nothing is ever cut.

It also refuses outright on `goalapp_apollo` and the system databases, whatever it is pointed at.

[database/manual/001-...sql](../../../database/manual/001-contract-party-data-innodb-utf8mb4.sql) is
kept as the by-hand fallback, for a database only reachable with a SQL client. It now points at the
command first.

### What ran, and what it did

`apollo_contracts_expense`, `utf8mb4_unicode_ci`, `varchar(32)`. Backup taken first:
[contract_party_data-before-convert.sql](../measurements/contract_party_data-before-convert.sql).

- **6,940 rows in, 6,940 rows out.**
- MyISAM / latin1_swedish_ci -> **InnoDB / utf8mb4_unicode_ci**.
- Both join columns `TEXT` -> **`varchar(32)`**.
- Two indexes added.
- Migration 1 also applied: `idx_approval_contracts_contract_id`.

**Case-insensitivity confirmed after the fact**, which was the one thing that could have broken
quietly: `contract_party_type = 'internal'` and `= 'Internal'` each match 3,920 rows.

Measured, same session, `IGNORE INDEX` standing in for "before" — see
[report.md](../measurements/report.md) rows 4 and 7:

| join | before | after |
|---|---|---|
| approvals (`approval_contracts` -> `contracts`, 13,867 rows) | 114 ms | **23 ms** |
| internal parties (`contract_party_data` -> `contracts`) | 42 ms | **32 ms** |

The party join gaining little is what [ticket 09](09-index-and-migrations.md) predicted — it said no
index on that table was needed for speed, and the conversion is about the collation boundary and
being indexable at all.

### One unlisted side effect

`party_address` went `TEXT` -> **`MEDIUMTEXT`**. Not in the plan and not a mistake: `CONVERT TO
CHARACTER SET` keeps a column's capacity in *characters* constant as bytes-per-character goes 1 -> 4,
so it promotes the type. The column got bigger; nothing was lost. Written down here so nobody
"fixes" it later. The SQL file and the report both say so now.
