# Seed a realistic dataset in the local database

Type: task
Status: resolved
Blocked by: —

## Question

`apollo_contracts_expense` holds **18 contracts**. At that size the `10 + 4·N` query pattern is ~80
queries and the PHP counting loop is free — so nothing about the N-dependent half of this work can be
measured, and any fix would ship as "expected to help" rather than "measured to help". Production-like
tenants sit around 800–3,000 contracts. The dev has ruled other tenant databases out and asked for
Laravel seeding instead.

Build a seeder that populates `apollo_contracts_expense` to realistic scale:

- ~3,000 `contracts` rows spread across the stage values the counters branch on — `draft`, `review`,
  `pre-approval` (must appear, since `contractStatusKey()` folds it into `review`), `finalization`,
  `negotiation`, `approval`, `approved`, `signing`, `executed` — with `substatus` values on the
  executed ones including the **capitalised `Terminated`** alongside the lowercase `active`,
  `expired`, `pending`, `renewed`, `completed`. The case inconsistency is real in the code and the
  seeded data must preserve it or a later `GROUP BY` rewrite will appear correct when it isn't.
- proportional `contract_party_data` rows, including **internal** parties with
  `contract_party_location_id` values spread across the 99 existing branches — and deliberately some
  contracts with *no* internal party, because those are currently excluded from every counter
  ([Controller.php:221](../../../app/Http/Controllers/Controller.php:221)) and that behaviour must be
  preserved.
- proportional `approval_contracts` rows, since `$approvalsArr` is walked per row in the blade.
- `department_id` values spread across the existing `EntityBusiness` rows so the department visibility
  filter actually filters.
- Encrypted columns (`contract_name`, `currency`, `currency_value`, `end_contract_type`) written
  through `encryptString()` so they decrypt correctly, since `decryptString()` passes through
  unchanged unless the value starts with `ey`.

Requirements: idempotent or clearly re-runnable, reversible (a way back to the 18-row state), and it
must not touch any other database. Record the exact row counts produced — later tickets quote them.

## Answer

Seeded. `apollo_contracts_expense` now holds **3,018 contracts** (18 real + 3,000 synthetic), and the
dashboard still renders — but document **TTFB rose from 1.9–2.7 s to 14.46 s**. All writes went through
Laravel seeders; the `mysql` CLI was used read-only, for the backup and the verification queries.

### Files added

| File | Purpose |
| --- | --- |
| `database/seeders/PerfDatasetSeeder.php` | Inserts 3,000 contracts + 6,900 party rows + 13,740 approval rows |
| `database/seeders/PerfDatasetRollbackSeeder.php` | Deletes exactly those rows, restoring the pre-seed counts |

Present in both the worktree and the live app dir (`D:\Contract-Expense\GOALv4\contracts`). No migration
was needed — nothing about the schema had to change.

### Commands

```bash
cd D:/Contract-Expense/GOALv4/contracts

# seed
HTTP_HOST=apollo.contracts.legality:8888 php artisan db:seed --class=PerfDatasetSeeder --force

# roll back to the pre-seed state
HTTP_HOST=apollo.contracts.legality:8888 php artisan db:seed --class=PerfDatasetRollbackSeeder --force
```

**`HTTP_HOST` is mandatory, not cosmetic.** `config/app.php:7` builds `APP_ENCRYPTION_KEY` as
`"c0n|r@(t$" . explode('.', $_SERVER['HTTP_HOST'])[0] . "4"`. Under the web server that is
`c0n|r@(t$apollo4` — exactly the 16 bytes AES-128-CBC requires. Bare CLI falls back to `localhost`,
yielding a 19-byte key that cannot even construct an `Encrypter`. The seeder asserts the key is 16 bytes
and that `encryptString()`/`decryptString()` round-trip before writing anything.

Re-running the seeder without rolling back first aborts with a message rather than double-seeding.

### Row counts

| Table | Pre-seed | Post-seed | Seeded |
| --- | --- | --- | --- |
| `contracts` | 18 | **3,018** | 3,000 |
| `contract_party_data` | 40 | **6,940** | 6,900 |
| `approval_contracts` | 127 | **13,867** | 13,740 |
| `branch` | 99 | 99 | 0 (read-only) |
| `contract_type` | 73 | 73 | 0 (read-only) |
| `entitybusiness` | 214 | 214 | 0 (read-only) |

Rollback was tested before the final seed and returned the DB to exactly `18 / 40 / 127`.

### Distributions achieved

`contract_status`, all `status = 1` rows (includes the 18 pre-existing):

| contract_status | count | seeded |
| --- | --- | --- |
| Executed | 900 | 900 |
| Draft | 482 | 480 |
| Review | 331 | 330 |
| Approval | 271 | 270 |
| Signing | 250 | 240 |
| Negotiation | 243 | 240 |
| Approved | 210 | 210 |
| Finalization | 180 | 180 |
| **Pre-Approval** | 151 | 150 |

`Pre-Approval` is present at 150 rows; `contractStatusKey()` folds it into `review`, so the dashboard's
review counter should read 331 + 151 = 482. Any SQL rewrite must reproduce that fold.

`substatus` on the 900 Executed contracts, grouped under `BINARY` so the case survives:

| substatus | count |
| --- | --- |
| `active` | 400 |
| `expired` | 150 |
| `pending` | 100 |
| `renewed` | 90 |
| **`Terminated`** (capitalised) | 90 |
| `completed` | 70 |

Grouping the same column *without* `BINARY` collapses nothing today, but a rewrite that lowercases or
relies on `utf8mb4_unicode_ci` will silently move the 90 `Terminated` rows — which is exactly what this
data exists to catch.

Party and visibility spread:

- **300 contracts have zero Internal parties** (every 10th). `Controller.php:221` drops these from every
  counter; the behaviour stays testable.
- Internal-party `contract_party_location_id` covers **all 99 branch rows** (verified
  `COUNT(DISTINCT) = 99`).
- Of the 2,700 contracts that do have an Internal party, **2,510 sit in an `entityid = 2` branch**
  (the session-accessible set) and **390 sit in a branch belonging to another entity**, so the
  branch-access exclusion in `availableContracts()` is exercised as well as the missing-party one.
- `department_id` spans the **35 `entitybusiness` rows with `applicable = 1` and `entityid = 2`**
  (36 distinct values in the table counting the pre-existing dept 9). Departments were kept inside the
  visible set deliberately: `DepartmentScope` adds `applicable = 1` plus the session entity, so spreading
  across all 214 rows would have added a second axis of invisibility on top of the branch one and shrunk
  the measurable counter population for no gain. 35 distinct values is plenty for the filter to filter.
- `contract_type` spans all 73 types; `catgoery_id` all 3 categories.

Encrypted columns (`contract_name`, `currency`, `currency_value`, `end_contract_type`, plus
`contract_mode` for consistency with the real rows) are written through `encryptString()` and all begin
`ey`, so `decryptString()` actually decrypts them instead of passing them through.

### How seeded rows are identified

Three independent, redundant markers, none of which required a schema change:

1. **Explicit id range from 100001.** Every seeded row gets a hand-assigned `id` starting at 100001;
   all pre-existing ids in the three tables are below 200. Actual ranges:
   `contracts` 100001–103000, `contract_party_data` 100001–106900, `approval_contracts` 100001–113740.
   Assigning ids explicitly also let the party/approval rows reference their parent contracts without
   3,000 round-trips to read back auto-increment ids.
2. **`contracts.contract_unique_id LIKE 'SEEDPERF-%'`** — the column already exists and already holds
   a human-readable business reference (`CON2922xxxx` on the real rows), so a distinct prefix is both
   plausible data and a reliable filter.
3. **`contract_party_data.party_address LIKE '%[SEEDPERF]%'` and
   `approval_contracts.unique_id LIKE 'seedperf%'`** — same idea in existing text columns.

The rollback seeder requires **both** the id range and the marker on every delete, so a marker typo can
never reach a real row.

Side effect worth noting: `AUTO_INCREMENT` on the three tables is left above 100000 even after rollback.
Harmless, but it means the counts return to baseline while the id sequence does not.

### Backup

`mysqldump` of `contracts`, `contract_party_data` and `approval_contracts` taken before any write:

```
.scratch/contracts-dashboard-perf/measurements/pre-seed-backup.sql   (404 KB, 3 INSERT statements)
```

Only `apollo_contracts_expense` was read or written throughout. No other database on the instance was
touched, dumped, or enumerated.

### Schema surprises in the 110-column table

- **The encryption key depends on the HTTP host** (above). This is the single biggest trap: any future
  CLI script that writes an encrypted contract column will silently produce garbage, or crash, unless it
  sets `HTTP_HOST`. It also means the app is unusable under any hostname whose first label is not
  exactly 6 characters, since AES-128-CBC demands 16 bytes and the prefix+suffix contribute 10.
- **Nothing in `contracts` actually needs a value.** All 110 columns are either nullable or carry a
  default; `id` is the only genuinely required one. The columns that *look* mandatory
  (`legal_contact_status`, `renewtype`, `has_active_extension`, `fileMoved`, `storage_type`,
  `created_by`) are all `NOT NULL DEFAULT ...`. The 110-column width was a non-issue for seeding.
- **Two required columns live in the child tables, not the parent.**
  `contract_party_data.custom_field_group_id` is `NOT NULL` with no default, and
  `approval_contracts.fileType` is a `NOT NULL` enum with no default — omit either and the insert fails.
- **`contract_party_data` is MyISAM** while the other two are InnoDB, so the seed is not transactional
  across all three tables. Combined with it having only a `PRIMARY` key and no index on
  `custom_field_group_id`, it is the obvious index candidate. It is also `latin1`, not `utf8mb4`.
- **`contract_party_data.custom_field_group_id` is the contract id**, despite the name suggesting a
  custom-field grouping. `Contract::contractPartyList()` joins on it.
- **`contract_type` is a `text` column holding a numeric id** as a string, joined to
  `contract_type.contract_type_id`. Likewise `catgoery_id` is `text` holding an id.
- **`unique_contract_name_hash` is a unique index on a column that is `NULL` for all 18 real rows.** The
  seeder populates it with `sha256(name)`; had it been left NULL that would have worked too (MySQL
  permits repeated NULLs in a unique index), meaning the uniqueness guarantee is currently unenforced in
  practice.
- Eloquent was avoided entirely in favour of `DB::table()`. `Contract::boot()` adds a `select('*')`
  scope, `$with = ['contractPartyList']`, and `ContractRoledBasedScope`; `BranchUser` and
  `EntityBusiness` carry `BranchScope` / `DepartmentScope`. All of those read the HTTP session, which
  does not exist under `artisan`.

### Dashboard behaviour at 3,000 contracts

One authenticated load through the already-running Chrome on port 9222:

| | 18 contracts | 3,018 contracts |
| --- | --- | --- |
| Document TTFB | 1.9–2.7 s | **14.46 s** |
| Document total | ~2.7 s | 14.47 s |
| Wall clock | ~21 s | **33.3 s** |
| HTTP status | 200 | 200 |
| HTML size | ~67 KB | 67.4 KB |

The page **still functions** — 200, complete HTML, no timeout, no memory exhaustion. But TTFB grew
**roughly 6×** for a 168× increase in rows, which is sub-linear yet lands the page far past the 10 s
"unacceptable" threshold on its own, before any of the front-end cost. The ~19 s of wall clock beyond
TTFB is still the 26 refused requests to the dead `[::1]:5173` Vite host, unchanged and unrelated.

Note that the HTML barely grew (67 KB at both sizes) because the dashboard emits counters and dropdowns,
not per-contract markup — so the entire 12 s regression is server-side work on rows that never reach the
response. Raw capture: `measurements/post-seed-3018-contracts.json`.

Per-status figures for the counters to be checked against are in the tables above; the review counter in
particular should read **482**, not 331, once `Pre-Approval` is folded in.
