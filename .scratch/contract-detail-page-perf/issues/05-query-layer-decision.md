# 05 — Decide how the query layer of this page is rebuilt

Type: `wayfinder:grilling` (HITL)
Blocked by: 04
Status: OPEN

## Question

Given the baseline, what changes to `viewContract`'s queries get made, in what order, and what does
each one cost to build?

## Notes

Not chartable in detail before ticket 04 reports. The shape to expect, from the dashboard effort:
fold repeated per-row lookups into one query, stop selecting every column, and stop running the same
decoration loop four times. The dashboard's `Contract::boot()` finding applies here too — a global
`select('*')` scope overwrites every narrow `select()`, so narrowing the columns needs that dealt
with first ([Contract.php:114](../../../app/Models/Contract.php:114)).

Invoke `/grilling` and `/domain-modeling`. The change lands on this branch in the same session that
decides it, or the next one — not in a spec that nobody executes.
