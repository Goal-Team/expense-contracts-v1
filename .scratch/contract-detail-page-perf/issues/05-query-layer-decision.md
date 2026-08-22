# 05 — Decide how the query layer of this page is rebuilt

Type: `wayfinder:task` (AFK)
Blocked by: 04, 09, 12, 13
Status: CLOSED — out of scope on the dev's call, 2026-08-22

## The decision, already made

**Split `viewContract`, one concern each. The dev's call 2026-08-21**, knowing the diff is large.
Contract load, parties, approvals, history and obligations become separate methods.

This ticket is the split itself, and it runs **last of the query work**, after the pieces that change
what the queries do (09, 12, 13). Splitting first would mean moving code twice.

## Original question, kept for the history

Given the baseline, what changes to `viewContract`'s queries get made, in what order, and what does
each one cost to build?

## Notes

Not chartable in detail before ticket 04 reports. The shape to expect, from the dashboard effort:
fold repeated per-row lookups into one query, stop selecting every column, and stop running the same
decoration loop four times. The dashboard's `Contract::boot()` finding applies here too — a global
`select('*')` scope overwrites every narrow `select()`, so narrowing the columns needs that dealt
with first ([Contract.php:114](../../../app/Models/Contract.php:114)).

## Done when

- `viewContract` is a short method that calls named methods, each doing one thing.
- **No behaviour change in this ticket.** It moves code. Anything that changes what a query returns
  belongs in 09, 12 or 13, not here.
- The page renders identically. Prove it by comparing the rendered document before and after, ignoring
  whitespace — not by looking at it.
- Committed in small commits, one concern moved per commit, so each hunk can be read on its own.

## CLOSED — out of scope, 2026-08-22

**The dev's call.** "Enough for now."

The expensive parts came out on their own while optimising, which was the point of asking for the split.
`viewContract` has lost four methods to the work: `relatedContractLists()`, `ancestryCte()`,
`ancestorContractIds()` and `subsequentContractIds()`. What is left — pulling parties, approvals, history
and obligations into their own methods — is pure readability with **no load-time effect**, and it is a
large diff to review for no measurable gain.

Not abandoned, just not this effort's. A later effort can take it with the page already fast, which is a
better time to move code than while it is being optimised.
