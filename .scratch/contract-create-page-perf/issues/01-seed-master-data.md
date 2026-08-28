# 01 — Seed the create page master data

Type: `wayfinder:task` (AFK)
Blocked by: nothing
Assignee: unclaimed
Status: OPEN

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
