# 05 — Stop binding id lists on this page

Type: `wayfinder:task` (AFK)
Blocked by: 02, 03
Assignee: claude subagent (session 2026-08-28)
Status: CLOSED 2026-08-28

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

Fixed 2026-08-28, commit `a7fbca7`. The block at
[ContractController.php:2240](../../../Modules/Contract/app/Http/Controllers/ContractController.php:2240)
no longer binds any id list.

**The rewrite.** The visible-id set is not a pure SQL subquery — `availableContracts()` drops
rows in PHP (branch access, department access, the location-report cookie). So the direction is
flipped, option 2 of the ticket brief. The query fetches approval rows on its own terms and the
existing `in_array($contract->id, $contractIds)` test in the loop below intersects with
`$ContractsFinal`, exactly as before. The query:

- `whereIn('unique_id', ApprovalContracts::select('unique_id')->where('approval_status', 'pending'))`
  — `approval_status` is plain text now, so only groups holding a pending row are fetched
  (2,192 rows of 13,867 on the seed). Every row of those groups comes back so the group's
  leading row (highest id) still names the contract — 6 unique_ids in this database span more
  than one contract, so the leader genuinely matters.
- `whereIn('contract_id', Contract::withoutGlobalScope('accessLevelSelect')->select('id')->where('status', 1))`
  — bounds the rows to live contracts. `ContractRoledBasedScope` stays on, so a role-restricted
  user restricts this subquery the same way the page's own contract query is restricted.
- Both `whereIn` calls take a query. Zero bound ids, one SQL statement (~210 ms on the seed).

**The decrypts.** Five of the six per-row `decryptString` calls are gone. `approval_status` is
plain (checked in the database: `approved` 11,736 / `pending` 2,129 / `rejected` 2, all plain
text). `status`, `previous_status`, `next_action_item`, `next_action_description` were decrypted
and never read. `username` is still encrypted (checked; the dev's call 2026-08-21 keeps it so)
and is the one column still decrypted — only for pending rows: 2,129 values instead of
6 × 13,867 = 83,202.

**Id-set proof.** Old and new logic were run side by side in tinker
(`HTTP_HOST=apollo.contracts.legality php artisan tinker <script>`), with
`SET SESSION in_predicate_conversion_threshold=0` so the old code's 2,508-binding `whereIn`
returned its true rows instead of zero. Both sets were intersected with the
`$ContractsFinal` ids, which is what the page shows. Results:

| session user | visible | old page ids | new page ids | equal |
|---|---|---|---|---|
| `approver.one@example.com` (350 pending rows, Super Admin) | 2,508 | 302 | 302 | yes, identical sets |
| `mohamed@legalitysimplified.com` (the real dev login) | 2,508 | 1 (`[1]`) | 1 (`[1]`) | yes |
| role `User` (role-restricted scope) | 0–7 | 0 | 0 | yes |

The browser confirmed the same on a sub-1,000 shape where the OLD code works
(`contype` = types 1–20, ~840 visible, cookie set, `status=all`): old code returned `[1]`,
new code returns `[1]`.

**Browser verification (logged-in CDP session).** With `myFilterStatus` set, `status=all` on the
full seeded set now returns 1 row (contract 1 — the one pending approval belonging to the
logged-in user) instead of 0. Without the cookie: draft 406, all 2,508, executed 747, unchanged.
Query count: 13 default, 14 with the cookie — no regression. Numbers in
[measurements/report.md](../measurements/report.md) row 3.

**Left alone, written down:**

- The leading-row quirk is preserved, not fixed: a group where a pending row matches the user
  contributes the group's *leading* row's `contract_id`, which for the 6 multi-contract
  unique_ids may not be the pending row's own contract. Same answer as the old code, by design.
- The old `reverse()` after `groupBy` only changed iteration order, never membership; it is gone.
- `$appr[0]` inside a group is the highest-id row because the query orders `id DESC` — same
  leader the old walk used.
