# 09 — Replace every id-list `whereIn` on this page with a join

Type: `wayfinder:task` (AFK, subagent)
Blocked by: 08 — the inventory names the sites and their line numbers
Status: OPEN

## Question

Nothing to decide. The dev ruled on it 2026-08-21: **never pass a list of ids into `whereIn`, use a
join.** The rule is now in [CLAUDE.md](../../../CLAUDE.md), "Query rules". This ticket applies it to
this page.

## Why it is not only about speed

Two queries where one join does the work, and every id bound across the wire. But the reason it is
urgent is correctness: **on this stack a `whereIn` with 1,000 or more bound values silently returns
zero rows.** No error. A section of the page just goes blank, and it looks like missing data. See
[../../wherein-1000-bug/spec.md](../../wherein-1000-bug/spec.md).

## The known sites

Four, found while charting. Ticket 08 confirms the list and the line numbers, and may find more.

- `ContractPartyData::whereIn('contract_party_location_id', $contract_party_locations)` — fed by a
  `pluck()` two lines above it.
- `ContractPartyData::whereIn('contract_party_exe_id', $contract_party_id)` — same shape.
- `Contract::whereIn('id', $FinalContractList)` — the list is built in PHP from the two above, so it
  grows with the data twice over.
- `Contract::whereIn('id', $finalListChild)`.

All four are in
[`ContractController::viewContract`](../../../Modules/Contract/app/Http/Controllers/ContractController.php:259).

## Done when

- Every one is a single query: a `join`, or a `whereIn` / `whereExists` on a **subquery**, so no id
  is ever bound.
- **The result is identical.** Prove it before and after on the same contract, by comparing the
  returned id sets, not by eye. A join can change the row count where a `whereIn` on a distinct
  plucked list did not — watch for duplicates and add `distinct()` where the old code relied on
  `pluck()` collapsing them.
- Verified in the browser on a seeded contract and on contract 1, with the related-contract and
  child-contract sections of the page showing the same entries as before.
- A report row: query count before and after.
- Committed on `claude/contract-edit-page-perf`, one commit per site if they are independent.

## Notes

Do not widen this into the query-layer rewrite (ticket 05). This ticket changes four query shapes and
nothing else.
