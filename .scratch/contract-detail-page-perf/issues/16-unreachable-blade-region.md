# 16 — Find out whether the Related Contracts region renders at all

Type: `wayfinder:task` (AFK)
Blocked by: 04 — needs the baseline, because if this region is dead the baseline changes shape
Status: CLOSED

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

## Resolution

Status: **CLOSED**, 2026-08-22. **Answer 3 — the region renders through a tab value nobody tried.**
Nothing was deleted.

### What makes it render

`?tab=details`. That is the **Details** tab, and the nav bar links to it on every load of the page
([viewDetailContract.blade.php:704](../../../Modules/Contract/resources/views/contract/viewDetailContract.blade.php:704)).

The ticket guessed two tab chains testing the same values. That guess was wrong. The two chains do
different jobs and they test **different** sets:

- **Chain 1**, lines 703-990, builds the tab buttons. It tests `pre-approval`, `timeline`, `edit`,
  `flow`, `history`, `historical`, `attachment`, `obligation`, `e-stamp`.
- **Chain 2**, lines 1092-2624, builds the tab body. It tests `timeline`, `pre-approval`,
  `timelineedit`, `history`, `flow`, `edit`, `attachment`, `e-stamp`, `obligation`, then **`@else`**
  at line 1383.

`details` is in chain 1 and not in chain 2, so it falls into that `@else`. The `@else` block runs
from line 1383 to line 2624 — 1,241 lines — and the Related Contracts region sits inside it at
lines 2240-2450. `$currentTab` is set in the blade at line 690 from `$_GET['tab']`, and it defaults
to `timeline` or `pre-approval`. So the region needs an explicit `?tab=details`.

The ticket 14 agent and the ticket 04 agent both tried `edit`, `history` and no `?tab` at all. All
three hit a named branch in chain 2. None of them reached the `@else`.

### Evidence

Browser, real logged-in session over CDP, warm, debug bar off. Marker strings taken from **inside**
the region, not from the heading:

| URL | status | document bytes | `Related Contracts` | `accordionParentContract` | `Category Previous Contracts` |
|---|---|---|---|---|---|
| `100479?tab=details` | 200 | 239,091 | **yes** | **yes** | **yes** |
| `1?tab=details` | 200 | 261,223 | **yes** | — | **yes** |
| `4?tab=details` | 200 | 260,876 | **yes** | — | **yes** |

The query log agrees. On `?tab=details` the perf record holds
`select * from contracts where contracts.parentcontract = ? limit 1` **58 times for 65 ms**. That is
the blade's `$contractsoldother->contractParent` lazy load, and it only runs if the region renders.
It does not appear on the edit tab at all.

### Second finding: the region renders, and it renders empty

Both Related Contracts tables come back with **zero rows**. The data has nothing to show:

```
SELECT COUNT(*) total, SUM(parentcontract IS NOT NULL AND parentcontract<>0) nonzero_parent FROM contracts;
-- total 3018, nonzero_parent 0
```

**No contract in the set has a parent, so no contract has a child.** The parent walk
([ContractController.php:748](../../../Modules/Contract/app/Http/Controllers/ContractController.php:748),
159 ms) and the child walk
([:784](../../../Modules/Contract/app/Http/Controllers/ContractController.php:784), 2,337 ms)
together cost **2,496 ms to produce nothing**. They scan the whole 110 MB `contracts` table either
way, because MySQL user variables and `FIND_IN_SET` stop the optimiser using an index.

`Category Previous Contracts` does fill. 374 of the `(catgoery_id, department_id, contract_type)`
groups hold more than one row and the biggest holds 12. That table also carries `d-none`, so the
server builds it and the browser hides it.

### What this means for the map

- **Ticket 15 stands.** The recursive child walk is live code on a live tab. It is not deletable and
  it is still 2,337 ms.
- **`$contractsoldothers` at `:723` stands too** — 898 ms, and the table it feeds renders. Baseline
  note 2 asked "check ticket 16 first"; the answer is that the query stays and needs an index or a
  narrower `select`, not deletion.
- **The three scans run on every tab**, not only on Details. They sit in `viewContract` with no tab
  guard. So the edit tab pays 3,840 ms for a region it never renders. **Ticket 07, tabs on demand,
  is now the biggest win on the map**, not a page-weight change.
- **The seeder has no parent-child chains.** Add them to
  [PerfDatasetSeeder](../../../database/seeders/PerfDatasetSeeder.php) before ticket 15 measures the
  child walk, or ticket 15 measures a walk that returns nothing. Not done here: it changes the row-0
  baseline for the whole page, and that is the dev's call.

### Numbers

Row 4 of [../measurements/report.md](../measurements/report.md). `100479?tab=details`, warm, debug
bar off: **TTFB 4,268-4,571 ms, 362 queries, 239,091 document bytes, 61 requests.** The edit tab is
253 queries. The Details tab costs **109 more queries** for the region, and the same time.

The two rows both numbered 3 are now one row 3. They described the same three commits with the same
number, so the thinner one went.

### Written down, not fixed

**`?tab=historical` returns HTTP 500.** `Undefined array key "history"` at
[viewDetailContract.blade.php:904](../../../Modules/Contract/resources/views/contract/viewDetailContract.blade.php:904),
which reads `$_GET['history']` with no guard. The tab needs `&history=<id>` as well. Found while
trying every tab value. It breaks a page, so it belongs to
[ticket 03](03-find-remaining-breaks.md), not here.
