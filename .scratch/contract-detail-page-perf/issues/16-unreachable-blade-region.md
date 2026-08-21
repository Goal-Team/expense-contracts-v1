# 16 — Find out whether the Related Contracts region renders at all

Type: `wayfinder:task` (AFK)
Blocked by: 04 — needs the baseline, because if this region is dead the baseline changes shape
Status: OPEN

## Why this may be the biggest item on the map

The ticket 14 agent could not get the Related Contracts panel to render on **any** contract it loaded.
It sits inside `@elseif ($currentTab == 'history')` in the second tab chain at
[viewDetailContract.blade.php:1197](../../../Modules/Contract/resources/views/contract/viewDetailContract.blade.php:1197),
and `?tab=history` on contracts 100479 and 4 both rendered **without** it. `Category Previous
Contracts` — the `$contractsoldothers` table at line 2244, which also carries `d-none` — is in the same
region and also did not render.

If that region never renders, then the work the controller does to fill it is **pure waste**. Ticket 08
priced that work:

- `availableContracts()` at line 738 costs **121 queries** — 5 fixed, plus a `ContractCategories::find`
  and a `contractTypeData` lazy load for each of 58 rows.
- `$contractsoldother->contractParent` in the blade costs **116 more** — 58 lazy loads with no index
  before ticket 11, plus 58 `admin_setting` calls.
- Line 718 scans the whole `contracts` table to build `$contractsoldothers`.
- The two recursive `DB::select` walks at 751 and 782 build the parent and child lists that feed it.

That is most of the page. **This ticket is worth doing before any of the clever work**, because a
deletion beats an optimisation.

## The question

Three possible answers, and they lead to very different work:

1. **The region is genuinely unreachable.** A condition nobody intends — two tab chains, and the second
   one never wins. Then delete the region and everything that feeds it.
2. **It renders, but only on contracts the test set has none of.** For instance only when the contract
   has a parent or a child, and the seeder made no parent-child chains. Then it is live code, the
   queries stay, and the seeder needs a chain so it can be measured honestly.
3. **It renders through a route or a parameter nobody tried.** Then find it and say what it is.

## How to find out, in this order

- Read both tab chains in `viewDetailContract.blade.php` and write down every value of `$currentTab`
  each chain tests, and where `$currentTab` is set. Two chains testing the same values is answer 1.
- Check the data: does any contract in the database have a non-zero `parentcontract`, or any child? If
  none do, that is answer 2 and it explains everything without a blade bug.
- Then load the page in the browser for each candidate tab value and each candidate contract, and look
  for a marker string from inside the region.

## Done when

- One of the three answers, with the evidence for it.
- If answer 1: the region and everything that only feeds it are deleted, with `grep` behind each
  deletion, and the query count before and after in the report. Expect a large number.
- If answer 2: the seeder gains parent-child chains, and the region gets measured for real. Say what it
  then costs.
- If answer 3: say what makes it render, and it becomes ordinary in-scope work.

## Watch out

`d-none` on the `Category Previous Contracts` table means it is in the document but hidden by CSS. That
is **not** the same as unreachable — the server still built it and still sent the bytes. Do not confuse
"the user cannot see it" with "the code never runs". Check the rendered HTML for the markup, not the
screen.
