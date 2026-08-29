# 01 — Seed the create page master data

Type: `wayfinder:task` (AFK)
Blocked by: nothing
Assignee: claude (session 2026-08-29)
Status: CLOSED 2026-08-29

## Question

The create page loads twelve master lists. Three of them are effectively empty on the local
database, so the dropdown cost the dev named cannot be seen:

| table | rows now |
|---|---|
| `country` | 1 |
| `contract_parties` | 1 |
| `legal_advisors` | 0 |
| `branch` | 99 |
| `ContractUsers` | 1,605 |
| `entitybusiness` | 214 |
| `contract_type` | 73 |
| `category` | 31 |
| `entity` | 6 |
| `contract_parties_label` | 5 |
| `contract_categories` | 3 |

Seed the empty and thin ones to production-like volumes so ticket 03 measures something real.
`country` and `contract_parties` are the two that grow organically in production — parties above
all, because every counterparty a customer signs with lands there.

Rules: a Laravel **seeder** (not a migration — no schema change), `apollo_contracts_expense`
only, and it must be re-runnable without making duplicates. Encrypted columns must be written
through the same encryption the app reads, or the dropdowns show blank labels — check how the
existing rows are stored before writing a single row.

Pick the volumes and state them in the resolution. Say what the count of each table is after the
seed, and confirm each dropdown renders real labels in the browser.

## Resolution

Seeded in commit `8b47bce`. Two seeders, following the `PerfDatasetSeeder` pattern the
dashboard effort set:

- `database/seeders/CreatePageMasterDataSeeder.php`
- `database/seeders/CreatePageMasterDataRollbackSeeder.php`

Run with:

```
HTTP_HOST=apollo.contracts.legality:8888 php artisan db:seed --class=CreatePageMasterDataSeeder
```

`HTTP_HOST` is required. `config/app.php` derives `APP_ENCRYPTION_KEY` from the serving
hostname, and on the CLI without it the fallback key is the wrong length. The seeder asserts
the key is 16 bytes and throws before writing a row.

**Volumes chosen and why:**

| table | rows after | why this number |
|---|---|---|
| `contract_parties` | **5,001** (was 1) | A mid-size customer's counterparty book. It is the list that grows organically — every party a customer signs with lands there. 5,000 is also well past the 1,000 bound-parameter line where this stack's `whereIn` silently returns zero rows, so a later change that reaches for a plucked id list fails loudly here instead of in production. |
| `country` | **71** (was 1) | The countries a contract counterparty realistically sits in. Not the full ISO 249 — the dropdown cost scales the same and 71 keeps the seed readable. |
| `legal_advisors` | **50** (was 0) | The table was empty, so the dropdown rendered nothing at all. 50 is a large in-house panel. |

Lists left alone because they already hold production-like counts: `branch` 99,
`ContractUsers` 1,605, `entitybusiness` 214, `contract_type` 73, `category` 31, `entity` 6,
`contract_parties_label` 5, `contract_categories` 3.

**Encryption.** `contract_parties.company_name`, `pan` and `gst` are Laravel `Crypt` payloads,
not `AES_ENCRYPT` — the blade reads them with `decryptString()`. The seeder writes them with
`encryptString()`, the same helper the app uses. Verified in tinker: row 100001 reads back
`Seed Counterparty 00001 Private Limited`, and the pre-existing row 1 still reads
`LEGALITY SIMPLIFIED LIMITED LIABILITY PARTNERSHIP` — untouched.

The `_hash` columns (`company_name_hash`, `pan_hash`, `gst_hash`) are left NULL. They are NULL
on the pre-existing row too, nothing in the PHP writes or reads them, and their unique indexes
allow many NULLs.

**Proved re-runnable and reversible:**

- Ran the seeder twice. Counts after both runs: parties 5,001, country 71, advisors 50. Each
  insert is preceded by a delete of its own marked rows.
- Ran the rollback. Counts returned to parties 1, country 1, advisors 0 — the exact pre-seed
  state. Every delete is constrained twice, by `id >= 100001` **and** by a marker in a plain
  column (`vendor_code LIKE 'SEEDCREATE-%'`, `email_id LIKE '%@seedcreate.test'`,
  `UniqueCode LIKE 'SC%'`), so a marker typo cannot reach a real row.
- Re-seeded afterwards, so the database is left in the seeded state for ticket 03.

Only `apollo_contracts_expense` was touched. No schema change, so no migration — a seeder is
the right tool here.

**Browser check of the dropdown labels is not done yet.** The debug session had expired when
this ticket ran, so it is carried into ticket 02, which walks every page shape anyway.

**Written down while seeding, for ticket 08 — the page renders the party list four times:**

`partyDetailsCreate.blade.php` loops `$contractParties` at
[:114](../../../Modules/Contract/resources/views/contract/partyDetailsCreate.blade.php:114),
[:128](../../../Modules/Contract/resources/views/contract/partyDetailsCreate.blade.php:128) and
[:244](../../../Modules/Contract/resources/views/contract/partyDetailsCreate.blade.php:244).
Each option calls `decryptString()` in PHP, once per party per loop.

Worse, the address list at :244 prints one `<li>` per party and calls
`get_state($contractPartie->state)` inside it.
[`get_state()`](../../../app/helpers.php:483) runs **one `State` query per call**. On the
seeded set that is 5,000 queries on a single GET. This is the biggest single find so far and it
needs its own ticket — see map ticket 10.
