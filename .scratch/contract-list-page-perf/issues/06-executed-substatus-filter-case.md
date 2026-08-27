# 06 — Fix the `executed_*` filters: status compared in the wrong case

Type: `wayfinder:task` (AFK)
Blocked by: 02
Assignee: —
Status: OPEN

## Question

All six `executed_*` tab filters return an empty list. Found by ticket 01's walk.

[ContractController.php:2311](../../../Modules/Contract/app/Http/Controllers/ContractController.php:2311)
compares `$contract->contract_status == 'executed'`, but the DB stores `Executed`. The compare
is case-sensitive, so no row ever matches. The seed has 900 Executed rows across all six
substatuses (active 400, expired 150, pending 100, renewed 90, Terminated 90, completed 70).

Fix: compare through `contractStatusKey()` like the neighbouring branch does, or lowercase both
sides. Verify each of the six tabs in the browser shows the right count. Bytes and row counts
change (0 → hundreds of rows per tab), so measure after the baseline exists — that is why this
waits on ticket 02.

## Resolution

_Open._
