# 05 — Stop binding id lists on this page

Type: `wayfinder:task` (AFK)
Blocked by: 02, 03
Assignee: —
Status: OPEN

## Question

The `myFilterStatus` path builds `$contractIds` by looping `$ContractsFinal` (every visible
contract) and feeds it to
`ApprovalContracts::whereIn('contract_id', $contractIds)` at
[ContractController.php:2231](../../../Modules/Contract/app/Http/Controllers/ContractController.php:2231).
On the seeded set that is ~3,018 bound values — past the 1,000 line where this stack silently
returns **zero rows** ([the bug](../../wherein-1000-bug/spec.md)). So "My contracts" comes back
empty on the seed, wrong and errorless.

Rewrite per the repo rule: pass the query, not the values — the visible-contract id set is
already expressible as a `Contract` subquery (remember `withoutGlobalScope('accessLevelSelect')`
on any `Contract` subquery, and keep `ContractRoledBasedScope`). Ticket 03's inventory names any
further binding sites; take them all here.

While in that block, check what the six per-row `decryptString` calls still feed —
`approval_status` is a **plain column now** (dashboard effort), so the filter test
`$appr->approval_status == 'pending'` may not need any decrypt at all. If the whole PHP loop
collapses into the query, compare the id sets before and after, not the look of the page.

Measure with `myFilterStatus` set, report row, small commits.

## Resolution

_Open._
