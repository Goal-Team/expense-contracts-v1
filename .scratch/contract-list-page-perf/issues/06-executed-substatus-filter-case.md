# 06 — Fix the `executed_*` filters: status compared in the wrong case

Type: `wayfinder:task` (AFK)
Blocked by: 02
Assignee: claude subagent (session 2026-08-27)
Status: CLOSED 2026-08-27

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

Fixed in [ContractController.php:2328](../../../Modules/Contract/app/Http/Controllers/ContractController.php:2328).
Two changes on the one line:

- The status compare now uses `contractStatusKey($contract->contract_status) == 'executed'`.
  The helper lowercases, so `Executed` in the DB matches.
- The substatus token from `explode('_', $_POST['status'])[1]` is now lowercased too. The blade
  sends `executed_Terminated` with a capital T, and the left side of the compare is already
  lowercased, so the token had to be lowercased as well.

Verified in the logged-in browser, filter cookies cleared, POST to `contracts/data`:

| key | rows before | rows after | raw DB rows |
|---|---|---|---|
| executed_active | 0 | 332 | 400 |
| executed_expired | 0 | 128 | 150 |
| executed_pending | 0 | 83 | 100 |
| executed_renewed | 0 | 74 | 90 |
| executed_Terminated | 0 | 74 | 90 |
| executed_completed | 0 | 56 | 70 |
| executed_amended | 0 | 0 | 0 |
| executed | 747 | 747 | 900 |
| draft | 406 | 406 | — |

The six substatus counts sum to 747, the same as the `executed` tab. The after counts are ~83%
of the raw DB counts, which matches the visibility scope (~2,508 of 3,018 rows visible). Every
call ran 13 queries (perf log), same as baseline.
