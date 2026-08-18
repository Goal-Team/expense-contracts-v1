# Decide the index and migration set

Type: grilling
Status: open
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

<!-- filled on resolution -->
