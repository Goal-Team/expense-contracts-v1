# 04 — Delete the dead all-contracts query on the GET

Type: `wayfinder:task` (AFK)
Blocked by: 02
Assignee: claude subagent (session 2026-08-27)
Status: CLOSED 2026-08-27

## Question

`listContract` loads every contract at
[ContractController.php:2443–2447](../../../Modules/Contract/app/Http/Controllers/ContractController.php:2443)
into `$contracts`. The only consumer, `availableContracts($contracts, true)` at :2493, is
commented out, and the view's `compact()` does not pass `$contracts`. So the GET fetches ~3,018
rows — plus whatever `Contract::$with` eager-loads behind them — and drops them.

Confirm with the ticket 03 trace (not by reading) that nothing consumes the result, then delete
the query and the dead `filterConType` branch that feeds it. Measure against row 0, report row,
one commit. Browser-verify the page and its filters still work.

## Resolution

Deleted in commit `c27eea8`. Three things went, all in `listContract`:

1. The all-contracts query (`$contracts_query` build + `->get()`, old :2468–2475).
2. The `filterConType` guard branch. It only added a `whereIn` to that query.
3. The commented-out consumer `//$ContractsFinal = $this->availableContracts($contracts, true);`.
   It was the last mention of `$contracts`.

Proof nothing reads the result: `$contracts`, `$contracts_query` and `$filterConTypes` appear
nowhere else inside the method, the view `compact()` never included `contracts`, and a grep of
`contractList.blade.php` finds no `$contracts`. Other files with a `$contracts_query` are other
pages with their own local variables. `listContractData` was not touched.

Measured warm, three runs, cookies cleared, `DEBUGBAR_ENABLED=false`, perf log
(`storage/logs/perf-2026-08-27.log`):

- GET server time 378 / 346 / 285 ms (baseline 2,241 / 2,970 / 2,784).
- GET queries 11 (baseline 14). The dropped three are the dead `select * from contracts`
  (1.0–1.7 s at baseline, `select *` because of the `accessLevelSelect` scope) and the
  eager loads `Contract::$with` ran behind it.
- GET database time 29–47 ms (baseline 1.2–1.8 s).
- Document 70,544 bytes (12,674 gz); the GET body varies a little run to run.
- AJAX unchanged: 13 queries, 200 OK, server ~3.8–5.0 s.

Browser check: page renders, table fills, no error — with cookies cleared and again with a
`filterConType=[1,2]` cookie set. The cookie no longer touches the GET at all; the type filter
itself travels in the AJAX POST (`contype`) and still works.

Nothing new written down; nothing else was touched.
