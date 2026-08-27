# 18 — Only run the Details-tab queries when the Details tab is open

Type: `wayfinder:task` (AFK)
Blocked by: nothing. **This is the top of the map.**
Status: **CLOSED** 2026-08-22, commit `47b4932`. See the Resolution at the end.

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

## Resolution

Status: **CLOSED**, 2026-08-22. Commit `47b4932`.

### Where the rule lives

Two functions in [app/helpers.php](../../../app/helpers.php), and nowhere else:

- `contract_detail_current_tab($contract)` — says which tab is open. It reads `$_GET['tab']`,
  defaults to `pre-approval` or `timeline`, and applies the two forcing rules. The body is the
  blade's old `@php` block at line 688, moved.
- `contract_detail_shows_related_contracts($currentTab)` — says if that tab renders the region. It
  holds the list of the nine tab values that have their own body block, and answers true for
  anything else.

Both callers read them:

- `viewDetailContract.blade.php:688` now says `$currentTab = contract_detail_current_tab($contract);`
  in place of the twelve lines that worked it out.
- The `@else` that opened the Details body at line 1383 is now
  `@elseif (contract_detail_shows_related_contracts($currentTab))`. So the blade and the controller
  agree by construction, not by two people writing the same list.
- `ContractController::viewContract()` calls both, and skips the work when the answer is false.

### What is guarded

The whole data build moved out of `viewContract()` into a new method,
`ContractController::relatedContractLists($id, $contracts)`. It is one concern — the contracts that
relate to this one — and it returns the four lists the blade loops. `viewContract()` calls it only
when the open tab renders the region.

Guarded: `$contractsold`, `$contractsoldothers`, `$contract_party_locations`, `$contract_party_id`,
`$ContractPartyLocList`, `$ContractPartyDataList`, `$FinalContractList`, `$contractspartsList`,
`$getParentContracts`, `$parentContractArr`, `$contractsparentList`, `$childsList`,
`$finalListChild`, `$contractsSubseqList`, and the four `availableContracts()` decoration loops.

Every variable the view needs still arrives. On a tab that does not render the region the four lists
— `contractsoldothers`, `contractsparentList`, `contractsSubseqList`, `contractspartsList` — arrive
as empty collections, so the four `@foreach` loops at lines 2261, 2304, 2360 and 2406 have a value
either way.

`$contractsSubseqList` was built at line 946, far from the rest. It moved into the method with the
code that feeds it.

**One duplicate query went with the move.** `$contractParties = ContractParties::select('*')->get();`
ran twice in `viewContract()`, at line 700 and again at line 767, with the same query and the same
result. The second call sat inside the moved block, so it is gone. That is the 2-query difference on
the Details tab.

### Proof the three queries are gone

`PerfTimingMiddleware` writes one record for each request to `storage/logs/perf-2026-08-22.log`. The
record's `db.slowest` list holds the ten slowest queries. The three scans cost 2,158 ms, 981 ms and
164 ms, and the fourth-slowest query on the page costs 42 ms, so a scan that runs cannot miss the
list.

Read after the change, one fetch for each tab, `DEBUGBAR_ENABLED=false` confirmed in `.env`:

| contract | tab | status | server `total_ms` | queries | child walk | `$contractsoldothers` | parent walk |
|---|---|---|---|---|---|---|---|
| 100479 | `details` | 200 | 4,103 | 360 | **yes** | **yes** | **yes** |
| 100479 | `pre-approval` | 200 | 375 | 86 | no | no | no |
| 100479 | `timeline` | 200 | 337 | 86 | no | no | no |
| 100479 | `edit` | 200 | 369 | 96 | no | no | no |
| 100479 | `flow` | 200 | 308 | 84 | no | no | no |
| 100479 | `history` | 200 | 321 | 83 | no | no | no |
| 100479 | `historical` | **500** | 6,676 | 255 | yes | yes | yes |
| 100479 | `attachment` | 200 | 2,638 | 91 | no | no | no |
| 100479 | `obligation` | 200 | 367 | 83 | no | no | no |
| 100479 | `e-stamp` | 200 | 516 | 83 | no | no | no |
| 100479 | none | 200 | 379 | 86 | no | no | no |
| 1 | `details` | 200 | 4,446 | 415 | **yes** | **yes** | **yes** |
| 1 | `pre-approval` | 200 | 505 | 87 | no | no | no |
| 1 | `timeline` | 200 | 343 | 87 | no | no | no |
| 1 | `edit` | 200 | 371 | 98 | no | no | no |
| 1 | `flow` | 200 | 333 | 86 | no | no | no |
| 1 | `history` | 200 | 369 | 85 | no | no | no |
| 1 | `historical` | **500** | 6,779 | 284 | yes | yes | yes |
| 1 | `attachment` | 200 | 2,185 | 93 | no | no | no |
| 1 | `obligation` | 200 | 338 | 93 | no | no | no |
| 1 | `e-stamp` | 200 | 345 | 85 | no | no | no |
| 1 | none | 200 | 343 | 87 | no | no | no |

`?tab=historical` **still runs the scans, and that is correct.** `historical` is not one of the nine
tabs with its own body block, so it falls into the same branch the Details tab falls into. The blade
throws first — `Undefined array key "history"` — so the region never renders, but the rule is right.
The break is unchanged and it belongs to [ticket 03](03-find-remaining-breaks.md).

`Log::debug('Contract detail page skips the Related Contracts queries', ...)` records the contract id
and the tab on every skip. `storage/logs/laravel.log` holds one line for each of the 20 loads above
that skipped, and no other new entry.

### The Details tab is unchanged

`100479?tab=details` fetched before the change and after, same session, warm, debug bar off:

- 239,076 characters both times, and the two documents match exactly when whitespace is ignored.
- Navigation Timing reports 239,091 document bytes, the same number [ticket 16](16-unreachable-blade-region.md) recorded.
- `Related Contracts` once, `accordionParentContract` twice, `Category Previous Contracts` once, in
  both documents.
- `1?tab=details` renders 261,223 bytes, again the number ticket 16 recorded.

Browser console on the Details tab holds the same three pre-existing warnings and one 403 for an
asset. No new message.

### Numbers

Rows 5 and 6 of [../measurements/report.md](../measurements/report.md).

- `100479?tab=edit`: **455–457 ms TTFB, 86 queries**, 326,254 document bytes, 61 requests. It was
  4,208–4,589 ms and 258 queries.
- `100479?tab=details`: 4,285 ms TTFB, 360 queries, 239,091 document bytes, 61 requests. Almost no
  change, which is the point.

### The surprise

The query count on the edit tab fell **258 to 86**, not 258 to 255. The three scans were not the only
thing in the block. The four `availableContracts()` decoration loops over the 58 related contracts sat
in there too, and [ticket 04](04-baseline-attribution.md) counted them as 158 repeated small lookups —
62% of the query count. So this one guard also collected most of what
[ticket 12](12-delete-waste.md) and [ticket 13](13-visible-to-scope.md) were aiming at, on every tab
but Details.

### Written down, not fixed

`?tab=attachment` takes **2,185–2,638 ms** with only 91–93 queries, so the time is not in the
database. Nothing else on the page behaves like that. Worth a look; it is not this ticket's.
