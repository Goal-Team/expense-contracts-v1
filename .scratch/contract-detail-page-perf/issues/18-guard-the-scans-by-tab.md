# 18 — Only run the Details-tab queries when the Details tab is open

Type: `wayfinder:task` (AFK)
Blocked by: nothing. **This is the top of the map.**
Status: OPEN

## Question

Nothing to decide. Ticket 16 found the three most expensive queries on the page run on **every tab**,
including tabs that never render their results. Guard them.

## What ticket 16 established

The Related Contracts region is live, but it only renders on **`?tab=details`**. It sits inside the
`@else` at
[viewDetailContract.blade.php:1383](../../../Modules/Contract/resources/views/contract/viewDetailContract.blade.php:1383),
and `details` is the only tab value that reaches it. Every other tab — `edit`, `timeline`,
`pre-approval`, `history`, `flow`, `attachment`, `obligation`, `e-stamp`, and no `?tab` at all — hits a
named branch and never renders it.

But the three queries that fill it sit in `viewContract` with **no tab guard**:

| ms | site | what |
|---|---|---|
| 2,337 | [:784](../../../Modules/Contract/app/Http/Controllers/ContractController.php:784) | the recursive child walk |
| 898 | [:723](../../../Modules/Contract/app/Http/Controllers/ContractController.php:723) | `$contractsoldothers`, `select *` on three unindexed columns |
| 159 | [:748](../../../Modules/Contract/app/Http/Controllers/ContractController.php:748) | the parent walk |

So **the edit tab spends about 3,400 ms building a region it does not render.** So does the default
page load. That is 80% of the request, thrown away, on every tab but one.

This is a bigger win than optimising any of the three queries, it is far less risky than
[ticket 07](07-page-size-decision.md), and it does not stop either of them happening later.

## How to do it

- `$currentTab` is worked out in the blade at line 690 from `$_GET['tab']`, defaulting to `timeline` or
  `pre-approval`. **The controller needs the same answer, computed once, in one place** — do not write
  the rule twice. Work it out in `viewContract` and pass it to the view, or put it in one small method
  both can call.
- Guard the three queries and everything that only feeds them: `$parentContractArr`, `$finalListChild`,
  `$contractspartsList`, `$contractsparentList`, `$contractsSubseqList`, `$contractsoldothers`, and the
  `availableContracts()` calls that decorate them.
- **Every guarded variable still has to reach the view**, as an empty collection. The blade loops
  several of them without guards — that is what ticket 14 item 3 was about. An undefined variable
  throws the page.

## Done when

- On `?tab=edit` and on the default load, none of the three scans runs. Prove it from
  `storage/logs/perf-*.log`, by the query shapes being absent, not by the time being lower.
- On `?tab=details`, **every panel shows exactly what it showed before.** Compare the rendered HTML
  before and after, ignoring whitespace. `Related Contracts`, `accordionParentContract` and
  `Category Previous Contracts` must all still be there.
- **Every tab loads.** All nine tab values from chain 1, plus no `?tab`. `?tab=historical` already
  returns HTTP 500 for an unrelated reason ([ticket 03](03-find-remaining-breaks.md)) — do not let that
  hide a new break.
- Report rows for the edit tab **and** the details tab. The details tab number should barely move; that
  is the point.

## Watch out

The naive guard is `if ($_GET['tab'] === 'details')`. That is wrong twice over: the default load has no
`tab` key at all, and a second place in the code would then own the same rule. One source of truth for
which tab is open, used by both the controller and the blade.
