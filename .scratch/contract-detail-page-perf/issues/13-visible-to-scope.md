# 13 — Replace `availableContracts()` with an Eloquent scope and eager loading

Type: `wayfinder:task` (AFK)
Blocked by: 04, 12
Status: OPEN

## The decision, already made

**The dev's call 2026-08-21.** `availableContracts()` does two jobs and the dev does not like the name.
It becomes:

1. **`Contract::visibleTo($user)`** — a real Eloquent scope that picks the contracts a user may see.
   The dashboard effort already proved the visibility rule is expressible in SQL: department `IN` plus
   an `EXISTS` on `contract_party_data` for an internal party in an accessible branch. No decrypted
   value takes part in any filter.
2. **`->with(...)` eager loading** for the category and contract-type names, replacing the per-row
   loop.

**Avoiding the N+1 is the point of the ticket**, in the dev's words. A scope that still lazy-loads per
row has failed.

## Why it is written beside the old one

55 call sites. The new function goes in beside `availableContracts()`, callers move over a few at a
time, and the old one is deleted when nothing depends on it — [CLAUDE.md](../../../CLAUDE.md), "many
callers, and the name is bad". This is the one case in this repo where two functions live side by side,
and the reason is reviewability, not comparison.

**This ticket moves only this page's four call sites.** The other 51 are other pages, and the scope rule
says leave them. The old function stays until a later effort moves them.

## What it costs today

Ticket 08 measured all four calls together at **138 of the page's 375 queries**. The call at line 738
alone is 121: five fixed queries, plus a `ContractCategories::find` and a `contractTypeData` lazy load
for each of the 58 rows it decorates.

## Done when

- `Contract::visibleTo()` exists as an Eloquent scope and returns the same contract ids
  `availableContracts()` returned. **Prove it by comparing the id sets** on several contracts and
  several users, not by looking at the page.
- The category and type names come from eager loading, and the per-row loop is gone.
- This page's four call sites use it. The other 51 are untouched and `availableContracts()` still works
  for them.
- Query count before and after, in the report.
- Verified in the browser on a seeded contract and on contract 1: the Related Contracts, parent and
  child panels list the same entries as before.

## Watch out

- **`Contract::boot()` adds a global `select('*')` scope** that runs after your own `select()`. A
  one-column subquery comes back as all columns. Use
  `Contract::withoutGlobalScope('accessLevelSelect')` where that matters, and leave
  `ContractRoledBasedScope` alone — that one is the visibility rule and dropping it would show a user
  contracts they may not see.
- `Contract::$with` already eager-loads `contractPartyList` on every contract query. Check you are not
  loading party rows twice.
- `contract_party_type` is compared against lowercase `'internal'` in two places and only works because
  the collation ignores case. Do not tighten that into a case-sensitive comparison.
