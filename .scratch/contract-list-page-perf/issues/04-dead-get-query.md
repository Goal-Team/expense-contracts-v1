# 04 — Delete the dead all-contracts query on the GET

Type: `wayfinder:task` (AFK)
Blocked by: 02
Assignee: —
Status: OPEN

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

_Open._
