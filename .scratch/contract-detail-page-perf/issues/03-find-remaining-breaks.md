# 03 — Walk every tab and collect what else is broken

Type: `wayfinder:task` (AFK, subagent)
Blocked by: 01, 02
Status: OPEN

## Question

The reminder crash is the one the dev hit first. What else is broken on this page at N=3,018 that
was not broken at N=18?

## What is known

Four `whereIn` calls on this page take a list whose length grows with the dataset:

- `ContractPartyData::whereIn('contract_party_location_id', $contract_party_locations)`
- `ContractPartyData::whereIn('contract_party_exe_id', $contract_party_id)`
- `Contract::whereIn('id', $FinalContractList)`
- `Contract::whereIn('id', $finalListChild)`

On this stack a `whereIn` with 1,000 or more bound values silently returns **zero rows** — no
error, just empty. See [../../wherein-1000-bug/spec.md](../../wherein-1000-bug/spec.md). At 3,018
contracts these are the prime suspects: the symptom is a section of the page quietly going blank,
which is easy to miss and easy to blame on data.

## Done when

- Every tab loaded in the browser on a seeded contract and on a pre-existing contract, with the
  Laravel log and the browser console checked after each.
- Each break written down with the file, line and cause. Fixes that are one line land in this
  ticket's commit; anything that needs a decision becomes its own ticket.
- Committed on `claude/contract-edit-page-perf`.
