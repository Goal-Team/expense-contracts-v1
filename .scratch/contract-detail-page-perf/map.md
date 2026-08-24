# Map: Contract Detail Page Performance

Branch: **`claude/contract-edit-page-perf`**. One branch for this page, per
[CLAUDE.md](../../CLAUDE.md). Small commits as soon as a change works.

## Destination

The contract detail page — `contracts/{id}`, every tab, one controller method
[`ContractController::viewContract`](../../Modules/Contract/app/Http/Controllers/ContractController.php:259)
— **loads with no error and is fast on the seeded 3,018-contract dataset**, and the work is
**done, committed and measured on this branch**, not only specified.

**Fast has no fixed number here** — the dev's call 2026-08-21, "just make it much better". Take every
safe win and report what it came to. The **query count** is the number that must not regress, because
it does not drift between sessions the way milliseconds do. The starting point is 375 queries per
request (ticket 08).

The effort is finished when the page is correct, the numbers are in
[measurements/report.md](measurements/report.md), and no ticket is open.

## Notes

**Domain.** Same stack as the dashboard effort: **Laravel 10.48.29** + nwidart/laravel-modules +
Vuexy template, `/contracts` is the IIS base path. Read the dashboard effort's map for the
established facts before charting anything new:
[../contracts-dashboard-perf/map.md](../contracts-dashboard-perf/map.md) and its
[spec.md](../contracts-dashboard-perf/spec.md).

**The page.** One route,
[`contracts/{id}`](../../Modules/Contract/routes/web.php:285), one method `viewContract`
(~820 lines, lines 259–1085), one view `contract::contract.viewDetailContract` with the edit tab in
[editRenew.blade.php](../../Modules/Contract/resources/views/contract/editRenew.blade.php) (894 lines).
`?tab=edit` only chooses which tab opens; the request builds every tab either way. **Scope is the
whole page, all tabs** — the dev's call 2026-08-21 — because the tabs share one request, so the
cost is shared too. The edit tab is what we measure and verify on.

**Skills to consult each session:** `diagnosing-bugs` (performance attribution), `grilling` +
`domain-modeling` (any decision ticket), `research` (AFK fact-finding).

**Standing rules for this effort:**

- **This effort does the work, not only the plan.** Every ticket that decides something is followed
  by the change landing on this branch, in a small commit, with a report row. The dev's call
  2026-08-21: "every effort is not only gives the map but also the actual result in that branch".
- **Change functions in place. No `x` copies.** Reversed by the dev 2026-08-21; see
  [CLAUDE.md](../../CLAUDE.md). Bad name -> rename it. Bad logic -> rewrite the body. Function does
  too much -> pull the extra concerns into new functions and delete the old one once nothing calls it,
  checked with `grep` including blade files. Git holds the old version, and every migration has a
  working `down()`, so nothing needs a side-by-side copy.
- **The report has no old-number column.** Also 2026-08-21. Row 0 is the baseline; each row records
  the new numbers only, because the row above already holds the previous ones.
- **Use fresh-context subagents wherever a ticket allows it.** The dev's call 2026-08-21. Reading a
  15,203-line controller and a 894-line blade eats a context window; hand that reading to an agent and
  keep the conclusion.
- **No id-list `whereIn`. Use a join.** The dev's call 2026-08-21, now a repo rule in
  [CLAUDE.md](../../CLAUDE.md) under "Query rules". A `pluck()` feeding a `whereIn()` is two queries
  doing one join's work, and at 1,000 or more bound values it silently returns zero rows on this
  stack. [Ticket 09](issues/09-replace-wherein-with-joins.md) applies it to this page.
- **Scope is this page's own code. Set by the dev 2026-08-21.** The contract detail page, its tabs and
  its own blade files. **Do not change other pages or unrelated blades.** If a blade or a helper is
  shared with another page, leave it and write it down instead. Repo-wide rules in
  [CLAUDE.md](../../CLAUDE.md) are the exception - a rule is allowed to be recorded there.
- **Dead code inside this page's scope can be deleted without asking**, once `grep` across the repo
  including blade files proves nothing else reads it. The commit message says what went. Anything
  shared with another page gets listed, not deleted.
- **Migrations: apply on the local dev database, then report.** The dev's call 2026-08-21, so index
  work does not stall while they are away. Every migration still needs a working `down()`, and it is
  still committed as a file. Production stays the dev's to run.
- **`availableContracts()` becomes an Eloquent scope. The dev's call 2026-08-21.** The dev does not
  like the name and it does two jobs at once. The replacement is `Contract::visibleTo($user)` - a real
  Eloquent scope for picking the contracts a user may see - plus `->with(...)` eager loading for the
  category and type names, instead of the per-row loop. **Avoid the N+1**; that is the point of it.
  55 call sites, so the new one is written beside the old and callers move over a few at a time
  ([CLAUDE.md](../../CLAUDE.md), "many callers, and the name is bad").
- **Correctness bugs found while optimising: fix the safe ones, list the rest.** The dev's call
  2026-08-21. A bug with one right answer gets fixed here. A bug where the fix needs the dev's intent
  gets written down, not guessed. [Ticket 14](issues/14-correctness-bugs.md).
- **Dropdowns: one reusable pattern, not one endpoint each. The dev's call 2026-08-21.** Every dropdown
  on the page shows the first 20 alphabetically and then searches on demand. Built as an **abstract
  base class over Eloquent models**, so a new dropdown is a subclass and a config line, never a new
  hand-written endpoint. [Ticket 06](issues/06-dropdown-decision.md).
- **Save testing: copy a real contract and save on the copy.** The dev's call 2026-08-21. Real field
  shapes, no real row touched. Copy, save, compare the row, delete the copy.
- **`viewContract` gets split, one concern each.** The dev's call 2026-08-21, knowing the diff is
  large. Contract load, parties, approvals, history and obligations become separate methods.
- **No fixed millisecond target. The dev's call 2026-08-21.** "Just make it much better." Take every
  safe win, report what it came to, and the dev judges. The **query count is still the number that
  must not regress**, because it is the one that does not drift between sessions.
- **Report only when the dev is needed.** Also 2026-08-21. Work the whole map. Come back for the dev's
  intent, or for a bug big enough that they should know now. Otherwise they read the branch.
- **Bytes are measured too**, same as the dashboard: document bytes, total transfer bytes, request
  count. Time and query count alone hide a 3 MB page.
- **Measure with the debug bar OFF.** `DEBUGBAR_ENABLED=false` in [.env](../../.env) before any number
  is taken; it inflated the dashboard document 5.7x.
- **Only the `apollo_contracts_expense` database.** Every schema change is a Laravel migration with a
  working `down()`, shown for review before it runs.
- **Verify in the browser.** A backend check is never a substitute for loading the page and looking at
  it. See [CLAUDE.md](../../CLAUDE.md) for the CDP profile launch.
- **Plain words to the dev, caveman English for questions, no summary unless asked.**

**Facts established while charting, 2026-08-21:**

- **The break is the seeded data, not the page code.** `editRenew.blade.php:440` throws
  `Undefined array key 1` because
  [line 431](../../Modules/Contract/resources/views/contract/editRenew.blade.php:431) does
  `explode(" ", decryptString($contract->reminder_first_alertMeOn, ...))` and that column is `NULL`.
  Measured: **3,000 of 3,018 contracts have `reminder_first_alertMeOn` NULL** — exactly the seeded
  rows. The 18 pre-existing contracts all have a value. `editRenew.blade.php` was **not touched** by
  the dashboard commit `d00f187`, and `PerfDatasetSeeder` only writes the columns the dashboard reads.
- Contract 100479 is a seeded row (`created_at` 2023-03-03 is synthetic; ids 100001+ are the seed
  range, `ID_BASE` in [PerfDatasetSeeder](../../database/seeders/PerfDatasetSeeder.php)).
- `viewContract` was changed by `d00f187` in **one way only**: 14 `encryptString(...)` calls became
  `encryptStringx('...', 'approval_contracts.approval_status')`. None of them is on the read path.
- `viewContract` runs at least **60 separate queries** by eye, including
  `ContractParties::select('*')->get()` twice (every party row in the database) and four separate
  `availableContracts()` decoration loops.
- **The `whereIn` lists are safe today and dangerous in production.** Ticket 08 measured them: at
  3,018 local contracts `$FinalContractList` holds **58** and `$finalListChild` holds **0**, because
  the seeder made only one `contract_parties` row. The two `ContractPartyData` calls bind **one** value
  each and grow with parties per contract, not with the dataset — the charting note below was wrong
  about those two. But `$FinalContractList` is bounded by contracts per branch, the busiest seeded
  branch already holds 72, and one busy production branch can hold 1,000 or more. Then the Related
  Contracts panel goes empty with no error. So the rewrite still happens; it is just not reproducible
  locally yet.
- **A GET request writes to the database in two places, not one.** The eSign block
  ([:281-384](../../Modules/Contract/app/Http/Controllers/ContractController.php:281)) and a
  `user_action_log` insert at
  [:535](../../Modules/Contract/app/Http/Controllers/ContractController.php:535) on every load of a
  Signing or Approved contract. A refresh, a browser prefetch or a crawler changes contract status.
- ~~Four `whereIn` calls take a plucked list whose size grows with the dataset —
  `ContractPartyData::whereIn('contract_party_location_id', ...)`,
  `whereIn('contract_party_exe_id', ...)`, `Contract::whereIn('id', $FinalContractList)`,
  `Contract::whereIn('id', $finalListChild)`. This stack silently returns **zero rows** at 1,000 or
  more bound values ([mariadb whereIn bug](../wherein-1000-bug/spec.md)), so these are suspect at
  N=3,018.~~ Corrected by ticket 08, above. **The dev ruled 2026-08-21: they all stop binding ids.**
  [Ticket 09](issues/09-replace-wherein-with-joins.md).

## Decisions so far

<!-- one line per closed ticket, newest last -->

- **Ticket 02, 2026-08-21.** The seed now fills all 60 `contracts` columns the detail page reads, not
  40, and corrects 7 values the page could never match. Row counts unchanged: 3,018 / 6,940 / 13,867.
  **The page stopped rendering as a result**: realistic rows are 6x wider, `contracts` grew to 110 MB
  against a 16 MB buffer pool, and the child-contract `GROUP_CONCAT` query at
  [ContractController.php:780](../../Modules/Contract/app/Http/Controllers/ContractController.php:780)
  now takes over 120 s, so IIS returns HTTP 500 on a FastCGI timeout. Same 3,018 rows in a
  two-column temp table: 3 s. That query is the next thing to fix.

- [01 — Fix the crash on the edit tab when a reminder column is NULL](issues/01-fix-null-reminder-crash.md)
  — one helper `reminder_alert_parts()` replaces four unguarded `explode()` calls; the page renders on a
  NULL reminder and still shows stored values unchanged. Fixed a precedence bug in the same blocks that
  made every unit dropdown show Years. Commit `37ddd2e`.

- [08 — Inventory every query the page runs](issues/08-query-inventory.md) — **375 queries per
  request**, 72 query sites. 234 of them come from two loops over 58 related contracts. Three hidden
  sources inflate every count: `ContractRoledBasedScope` calls `admin_setting()` once per `Contract`
  query, `Contract::$with` eager-loads party rows, and `BranchScope` adds two queries of which one
  decrypts in the `WHERE` and scans all 1,605 users. Nine exact duplicate query pairs. Seven results
  no blade on this page reads. Six missing indexes. Seven correctness bugs. Commit `1f5ec54`.

- [02 — Make the seeded rows look like real contracts](issues/02-seed-realistic-contract-rows.md) — the
  page reads **60 of the 111 `contracts` columns** and the seeder wrote 40. All 20 gaps filled and 7
  values corrected that the page could never match. Filling them took rows from ~1.5 KB to **9,390
  bytes**, `contracts` to **110 MB against a 16 MB buffer pool**, and the page to **HTTP 500** — which
  exposed a quadratic query nobody could see at 18 contracts. Commit `79b7dd8`.
- [14 — breakage and one duplicate](issues/14-correctness-bugs.md) — three commits, **261 to 258
  queries**. Guarded `$contractsoldothers` (it throws for a contract the user may not see, because the
  global scopes make it `null`), deleted a dead query that cost 2, and made Chart View reuse the
  timeline's approvals instead of repeating the query and its decrypt pass. Commits `63a1db2`,
  `d1173aa`, `8858fce`.
- [11 — indexes](issues/11-missing-indexes.md), part done — the `contracts(parentcontract, id)`
  covering index took the page from **HTTP 500 to 4,422 ms**. It took **474 s to build** on 3,018 rows,
  so production needs a window. Five indexes left. Commit `378ba21`.

- [04 — Baseline the page and say where the time goes](issues/04-baseline-attribution.md) — **4,208-4,589
  ms TTFB, 253 queries, 326 KB document, 2.98 MB cold transfer.** **89-90% of the request is spent
  waiting on the database**; blade rendering 326 KB across 21 views costs under 110 ms. **Three
  whole-table scans hold 3,840 ms** — the child walk at `:784` (2,616 ms), `$contractsoldothers` at
  `:723` (1,045 ms), the parent walk at `:748` (179 ms). Separately, **158 repeated small lookups cost
  only 218 ms but are 62% of the query count**. So the seconds and the count need different fixes.
  Commit `ff88b2a`.

- [16 — is Related Contracts dead?](issues/16-unreachable-blade-region.md) — **not dead.** `?tab=details`
  renders it, and the nav bar links to that tab on every load. The two tab chains test **different**
  values: `details` is in the button chain and not in the body chain, so it lands in the body chain's
  `@else` at [line 1383](../../Modules/Contract/resources/views/contract/viewDetailContract.blade.php:1383),
  and the region sits inside that block at lines 2240–2450. Earlier agents tried `edit`, `history` and no
  `?tab`; all three hit a named branch. **Nothing deleted.** The Details tab runs **362 queries** against
  the edit tab's 253. Two further facts: **no contract in the set has a parent**, so the two recursive
  walks cost 2,496 ms to build empty tables; and **the three scans run on every tab**, so the edit tab
  pays for a region it never renders. Report row 4. Commit `3409cc4`.

- [18 - only run the Details-tab queries on the Details tab](issues/18-guard-the-scans-by-tab.md) - the
  three whole-table scans now run only on the tab that renders their results. One rule in two new
  helpers in [app/helpers.php](../../app/helpers.php), read by the controller and by the blade, so it is
  not written twice. The data build left `viewContract` and became `relatedContractLists()`. Every tab
  loads and the Details tab is **byte-identical**. `100479?tab=edit` went **4,208-4,589 ms and 258
  queries to 455 ms and 86 queries**. The count fell far more than expected: the four
  `availableContracts()` loops over 58 related contracts sat in the same block, so this guard also took
  most of what tickets 12 and 13 were aiming at, on every tab but Details. Rows 5 and 6. Commit
  `47b4932`.

- [20 - the `$contractsoldothers` scan](issues/20-contractsoldothers-scan.md) - **the index is the whole
  win.** `contracts(contract_type, department_id, catgoery_id)`, most selective column first, took the
  query from **928-1,823 ms to under 5 ms** and the Details tab from 4,088-5,233 ms to **3,295-3,433
  ms**. `EXPLAIN` goes from `type ALL, rows 1509` to `type ref, rows 1`. It **built in 208 ms**, not the
  474 s the `(parentcontract, id)` index took, so this one needs no window. Then the select narrowed
  from `*` to the seven columns the blade reads, plus `without('contractPartyList')`: **359 queries**,
  and 415 to 413 on contract 1. The narrow select wins one query, not time, because the index already
  stopped the scan. Rows identical on both contracts. Rows 7 and 8. Commits `5ffd9c1`, `8ae50df`.
  **Two things to remember:** `catgoery_id` and `contract_type` are `TEXT`, so the index needs a prefix
  length and a **numeric** literal loses the index altogether; and the Details tab's remaining 3 s is
  the child walk, [ticket 15](issues/15-recursive-child-walk.md).

- [20 — `$contractsoldothers` scans the whole table](issues/20-contractsoldothers-scan.md) — **Details tab
  4,088-5,233 ms to 2,997-3,576 ms.** The query went from 928-1,823 ms to under 5 ms, and the optimiser
  really uses the index (`type ALL, rows 1509` becomes `type ref, rows 1`). Two commits, one per half:
  the index `contracts(contract_type, department_id, catgoery_id)` won all the time, and narrowing
  `select *` to the seven columns the blade reads won one query and no time, because the index had
  already stopped the scan. Four things worth carrying forward: **the build took 208 ms, not 474 s** —
  build time follows the index's columns, not the table's size, so no window is needed for this one;
  `catgoery_id` and `contract_type` are **`TEXT`**, so the index needs a prefix and `$table->index()`
  cannot write one; **a numeric literal loses the index silently** — `contract_type=41` scans, `='41'`
  does not, and Eloquent binds strings so only hand-written filters are at risk; and contract 100479
  renders **0** rows in that table while contract 1 renders 11, so the control contract is the one that
  proves it. Commits `5ffd9c1`, `8ae50df`.

- [15 - the recursive child walk](issues/15-recursive-child-walk.md) - **one `WITH RECURSIVE` query
  in place of the `@pv` / `FIND_IN_SET` walk.** Step 0 came first: the seeder now links **684 of the
  3,000 seeded rows** to a parent - 300 pairs, 100 chains three deep, 50 chains four deep, two wide
  fan-outs of 12 and 20, and one branch two rows deeper. No cycle: every parent id sits below its
  child id, and a walk from the 2,334 roots reaches all 3,018 rows once, max depth 3. Report row 9
  re-measures the page on that data first - **3,235-7,109 ms and 369 queries**, the walk holding
  1,996-5,365 ms. Then the rewrite: `100479?tab=details` goes to **1,198-2,207 ms**, same 369
  queries, and the walk leaves the ten-slowest list. Rows 9 and 10. Commits `a4aa1bc`, `05831d1`.
  **Three things to remember:** the old code glued ids together with no comma when a contract had
  two or more ancestors, so **202 of 3,018 contracts got one bogus id and lost one real one** (the
  page looked the same only because the lost id was always an ancestor the Parent Contracts table
  had already printed, and the blade shares one `$prevContracts` list); `GROUP_CONCAT` also
  truncated the old result at 1,024 bytes with no error; and **the parent walk is now the slowest
  query on the page** at 222-255 ms, same session-variable shape, and the same `ancestry` CTE fixes
  it.

- [15 — Replace the quadratic child-contract walk](issues/15-recursive-child-walk.md) — **Details tab
  3,235-7,109 ms to 1,198-2,207 ms; TTFB 3,436 ms to 1,237 ms.** One recursive CTE in place of a
  session-variable walk that read the whole table once per row. The query count does not move: it was one
  query and it is one query.
  **The old query was also wrong, and nobody could have seen it from the page.** With two or more
  ancestors it joined the walks with `.=` and **no comma**, gluing the last id of one to the first id of
  the next — so `100904` got `100904100902`, a number matching nothing, and lost a real id.
  **202 of 3,018 contracts hit it.** The page never showed it because the blade shares one
  `$prevContracts` list across all four family tables, and the lost id was always an ancestor the Parent
  table had already printed. `GROUP_CONCAT` also capped the old result at `group_concat_max_len`, 1,024
  bytes, so a tree over about 145 members lost its tail with no error.
  Step 0 seeded the chains this needed: **684 of 3,000 rows now have a parent** — 300 pairs, 100 chains
  three deep, 50 four deep, fan-outs of 12 and 20 — with the cycle check run twice. Row 0 of the report
  is untouched; row 9 is the honest starting number on the new data. Four commits.

- [21 - the recursive parent walk](issues/21-parent-walk.md) - **the upward walk is now one shared
  method.** `ancestryCte()` returns the `WITH RECURSIVE` fragment; `ancestorContractIds()` reads the
  ancestors from it and `subsequentContractIds()` uses the same fragment to find the top of the tree.
  Nothing copied. `100479?tab=details` goes **1,198-2,207 ms to 686-785 ms**, same 369 queries, and
  **neither walk is in the ten slowest any more** - the slowest query on the page is now 7-11 ms.
  Id sets match on all 20 contracts checked and every document is byte-identical. Report row 11.
  Commit `c480b03`. **One thing to remember:** this query had **neither** of the child walk's faults -
  no `.=` gluing, no `GROUP_CONCAT`, so no 1,024-byte cap - but it was correct only because Laravel
  uses **native** prepared statements. With emulated prepares the `FIND_IN_SET` branch reads `@idlist`
  as it stood when the statement began, so the walk stops after the immediate parent and the 202
  contracts with two or more ancestors silently lose the rest.

- [21 — The parent walk](issues/21-parent-walk.md) — **Details tab 1,198-2,207 ms to 686-785 ms; TTFB
  824 ms.** Both recursive walks are now out of the ten-slowest list on every contract measured, and the
  slowest query on that tab is **7-11 ms**. The two walks share one method, `ancestryCte()`, with the
  32-level cap in one place; nothing is copied. Query count does not move — one query became one query.
  **This old query was right**, unlike its sibling: one query, no `.=` gluing, no `GROUP_CONCAT`, so
  neither of ticket 15's faults applied. **But its answer depended on a PDO flag.** It reads `@idlist` in
  one branch while writing it in the branch above, and with *emulated* prepared statements MariaDB reads
  the value from when the statement began — NULL — so the walk stopped at the immediate parent. Five runs
  of five: native gives the whole chain, emulated gives one row. Laravel uses native, so it was not
  broken; it was one config line from losing every ancestor above the first on the 202 contracts that
  have more than one. Recorded, not fixed. Commits `c480b03`, `89087ae`, `d05d016`.

- [03 - walk every tab and collect what else is broken](issues/03-find-remaining-breaks.md) - **nine
  breaks, not four.** `?tab=historical` returned HTTP 500 on every contract; it renders now, and it
  costs what the Details tab costs because it falls into the same branch. The other eight: the four
  reminder columns on the Details side now read `reminder_alert_parts()`; both unguarded `rules_id`
  decodes match `contractFlow`'s `is_string()` test; the three id-to-name lookups and the external
  party name no longer read `->first()->name`; `$ContractsFinal[0]` no longer runs before the
  empty-list redirect, which sat 122 lines too late; `?attachment=` no longer hits a `die;` that
  returned a 70-character page; `?tab=timelineedit` no longer throws on `$reqfields`, a variable
  nothing in the repo sets; and a missing history snapshot falls back to the live contract instead of
  leaving `$contracts` null. **52 loads, 13 tab values on four contracts, all 200, no error and no
  warning in `laravel.log`, and every document ticket 21 recorded is unchanged character for
  character.** Report row 12 - the only fix with a number; the other eight move nothing.
  **Three things to remember:** the seeder now fills all four reminder columns, so proving a NULL
  needs a row edited by hand and put back; a warning is a 500 here, because Laravel turns every PHP
  error into an `ErrorException`, so `$x[0]` on null throws just as hard as a missing key; and
  `?tab=<anything unknown>` renders the Details body, so a typo in a URL silently gives the slowest
  tab. Commits `2f20da8`, `9989237`, `bab22dc`, `d3be98d`, `ddfc093`, `63a1c43`, `77e0ecd`,
  `41483c6`, `74776e6`.

- [03 — Walk every tab and collect what else is broken](issues/03-find-remaining-breaks.md) — **nine
  breaks fixed, one commit each, and every tab now returns 200** across 52 loads on five contracts. Four
  were known; five were not, and two of those were URLs nobody had tried: `?attachment=` ran a `die()`
  and returned a 70-character page, and `?tab=timelineedit` looped a variable nothing sets. Also fixed:
  `?tab=historical` (`$_GET['history']` unguarded — four earlier agents hit this), the four unguarded
  reminder `explode()` calls on the view side that ticket 01 fixed only on the edit side,
  `json_decode($contract->rules_id)` twice, `$ContractsFinal[0]` read 122 lines before the empty-list
  redirect, three id-to-name lookups reading `->first()->name`, and a missing history snapshot leaving
  `$contracts` null.
  **Two facts worth keeping.** A PHP *warning* is a 500 on this stack — Laravel turns every error into an
  `ErrorException`, so `$x[0]` on null throws as hard as a missing key. And **`?tab=<anything unknown>`
  renders the Details body**, so a URL typo silently serves the most expensive tab.
  `?tab=historical` went from HTTP 500 to **200 at 760-777 ms and 369 queries** — exactly what the Details
  tab costs, because it falls into the same branch. The other eight fixes move no number and the agent
  said so rather than inventing one.

- [19 - the attachment tab is slow outside the database](issues/19-attachment-tab-slow-outside-db.md) -
  **the blade holds the time, not the controller.** 89 queries cost 154-176 ms while the view render
  held 1,827-1,940 ms. The tab called `fileViewUrl()` and `get_google_drive_doc_link()` one after the
  other, and both end in `GoogleDriveController::changePermission()` with the same file id, the same
  email and the same `$onlyView` flag. Each of those makes **two** outbound requests to Google - an
  OAuth token refresh of 494-582 ms and a Drive `files.get` of 359-422 ms - so one page load made four
  round trips for one link. `fileViewUrl()`'s answer is read only by the `Local` branch, so it moved
  inside that branch: `100479?tab=attachment` goes **2,171-2,428 ms and 91 queries to 1,327-1,384 ms
  and 89**, and contract `4` - a real Drive file - goes 2,428 ms and 94 queries to 1,365 ms and 92.
  Output unchanged on both. Report row 13. Commit `1d5a5a1`.
  **Three things to remember:** the cost is the same whether the file is real or not, because contract
  4's `files.get` **succeeded** in 422 ms and cost what the seeded contracts' 404 cost - so the 2.1 s
  was never a timeout or a failure path; `changePermission()` is **not a read**, it runs
  `permissions->create` even with `$onlyView = true`, so it is what grants the logged-in user access
  and the remaining 915 ms cannot simply be deleted; and the token refresh is **half of every Drive
  call in the repo**, because `changePermission()` builds a fresh `Google_Client` and never sets a
  stored token, so `isAccessTokenExpired()` is always true. Caching that token is the recommendation,
  and it is the one fix that needs no change to what any page renders.

- [19 — The attachment tab takes 2.2 s and the database is not the reason](issues/19-attachment-tab-slow-outside-db.md)
  — **2,171-2,428 ms to 1,327-1,384 ms.** The time was **84-89% in the blade render**, not the controller
  and not the database — 154-176 ms of that request is queries. A 30-line view made **two** outbound
  Google Drive calls that did the same work with the same file id, the same email and the same flag:
  `fileViewUrl()` at 915-971 ms beside `get_google_drive_doc_link()` at 863-975 ms. Four round trips to
  Google for one file link. The first only feeds the `Local` branch, so it moved inside that branch.
  **The failure path and the success path cost the same** — seeded rows hold a made-up Drive id and throw,
  contract 4 holds a real one and succeeds in 422 ms, and both pages cost 2,428 ms before the fix. So the
  2.1 s was never a timeout. **915 ms stays and cannot be deleted**: `changePermission()` still runs
  `permissions->create` even with `$onlyView = true`, so it is what grants the logged-in user access to
  the file. Removing it shows Google's 403 to anyone not already granted. Commit `1d5a5a1`.

- [22 - cache the Drive access token](issues/22-cache-the-drive-token.md) - **the refresh is the proof,
  not the time.** `changePermission()` built a fresh `Google_Client` and never set a token, so every
  call paid an OAuth refresh. `authorizedClient()` now holds the token in the `file` cache, keyed by a
  `sha1` of the client id, the client secret, the token endpoint and the refresh token, and the entry
  lives **3,479 s** - 120 short of the token's own 3,599. Twelve Drive calls logged **one** real refresh
  and **10** cache hits. `100479?tab=attachment` goes **1,327-1,384 ms to 1,066-1,212 ms**, contract `4`
  1,365 ms to **1,194-1,256 ms**, same query counts and byte-identical documents. The condition to stop
  on did not fire: callers still get `null` on success and the same error string on failure, so the
  method changed in place. Report row 14. Commit `5396884`.
  **Three things to remember:** the refresh cost **230 ms today against ticket 19's 494-582 ms**, so
  the round trip to Google varies by the day and the count is the honest number; a **404 for a missing
  file must not drop the cached token**, and `isDriveAuthFailure()` only accepts 401 or 403 with an
  authentication message, so the seeded made-up Drive ids are safe; and **ten more methods in
  `GoogleDriveController` still refresh their own token**, one line each to move over, all on POST paths
  and so out of this page's scope.

- [22 — Cache the Google Drive access token](issues/22-cache-the-drive-token.md) — **attachment tab
  1,327-1,384 ms to 1,066-1,212 ms**, and contract 4 improved by the same amount, as predicted. New
  `authorizedClient()` holds the token in the `file` cache, keyed on a `sha1` of the client id, secret,
  token endpoint and refresh token, so two accounts can never share an entry. It lives `expires_in` minus
  120 s — 3,479 s in practice. A 401 or 403 naming an authentication fault drops it, refreshes once and
  retries once; a 404 for a missing file does **not** touch the cache, which matters because every seeded
  contract holds a made-up Drive id and 404s on every load. The retry was proved by writing a bogus token
  into the cache by hand, not assumed.
  **Honest caveat: the refresh cost about 230 ms on the day it was measured, not the 494-582 ms ticket 19
  saw.** The cache removes the whole refresh either way; the milliseconds are that day's round trip to
  Google. The refresh **count** is the proof — one miss and ten hits over 12 calls — and the report row
  says so rather than claiming the bigger number.
  **Cheapest follow-up on the board:** ten more methods in the same class still refresh their own token,
  six identical lines each, and `authorizedClient()` is public so each is a one-line change. All ten sit
  on POST paths, so the scope rule leaves them here. Commit `5396884`.

- [17 - gzip the HTML document](issues/17-gzip-the-html-document.md) - **the document is compressed, and
  it took PHP, not config.** `100479?tab=edit` goes **326,254 bytes to 35,432**, 9.2x, for **6-9 ms of
  CPU**. Cold whole page 2,979,504 to **2,688,682** (9.8% off); warm whole page 326,854 to **35,732**
  (89% off), because on a repeat visit every asset comes from cache and the document is the page. Proved
  both ways at once: `content-encoding: gzip` with `content-length: 35432` and `vary: Accept-Encoding`,
  and `encodedBodySize` 35,432 against `decodedBodySize` 326,254. All 13 tab values on `100479` and `1`
  return 200 and compress, 4.8x to 11.9x. Report row 15. Commit `00d6219`.
  **Three things to remember:** **IIS dynamic compression is not installed here** - `compdyn.dll` is
  absent from `C:\WINDOWS\System32\inetsrv`, `applicationHost.config` holds no `<dynamicTypes>` and no
  `dynamicCompressionLevel`, so `urlCompression doDynamicCompression="true"` would have been inert and no
  `web.config` line could ever have worked; `applicationHost.config` is **readable without elevation** on
  this machine, so its facts can be checked rather than guessed, and `Test-Path` on `compdyn.dll` answers
  the whole question in one line; and **`frequentHitThreshold` never delayed the document** - it is
  applied and live at 1, but PHP has no hit counter, so the first request already comes back gzipped.
  **The dev may want to revert this.** They ruled out dynamic compression 2026-08-21 on maintenance
  grounds. That ruling was about the IIS feature, which needs an admin and a machine-wide file; this is
  one middleware in the repo. `git revert 00d6219` undoes it whole and touches nothing else.

- [17 — Compress the HTML document](issues/17-gzip-the-html-document.md) — **326,254 bytes to 35,432,
  9.2x, for 6-9 ms of CPU.** Cold whole page 2,979,504 to 2,688,682 (9.8%); **warm whole page 326,854 to
  35,732 (89%)** — warm is the bigger relative win because every asset comes from cache and the document
  *is* the page.
  **The ticket's premise was wrong and the agent checked rather than trusting it. This is not a config
  change.** `compdyn.dll` — the IIS Dynamic Content Compression module — **is not on this server's disk**,
  `applicationHost.config` holds no `dynamicTypes` list, and the string `DynamicCompression` appears zero
  times in it. So the `web.config` lines the ticket asked for would have been valid attributes with no
  module behind them. **No config line could ever have worked.** `web.config` was not touched.
  The fix is one middleware, `app/Http/Middleware/CompressResponse.php`, registered first in the global
  stack so it is last to touch the response. `gzencode` level 6, measured as the knee: level 1 gives
  42,790 bytes in 2 ms, level 9 gives 34,648 in 17 ms. It refuses streamed and binary responses so an
  attachment download is never pulled into memory, skips already-compressed types, and sets
  `Vary: Accept-Encoding` so no proxy hands a gzipped body to a client that did not ask.
  Also answered the `frequentHitThreshold` warning: it cannot delay this, because PHP has no hit counter,
  so the **first** request comes back gzipped. Commit `00d6219`.


- [09 - stop binding lists of ids](issues/09-replace-wherein-with-joins.md), part done - **the Parent
  Contracts list passes the query, not the ids.** `ancestorContractIds()` returns the recursive walk as
  a subquery and `whereIn` reads it, so one binding crosses the wire instead of one per ancestor. Two
  queries became one on every contract: **369 to 368** on `100479`, 426 to 425 on `1`, 400 to 399 on
  `101143`, 619 to 618 on `101101`. Id sets match on 22 of 22 contracts, ids and order both, and all 30
  documents are unchanged to the character. Report row 16. Commit `81f2581`.
  **Four things to remember:** the ticket's "known sites" list was **wrong** - tickets 15 and 21
  replaced the two family-tree *walks*, not the `whereIn` calls that read their ids, so **four sites
  still bind**, the biggest being `$finalListChild` at 111 ids and 92.62 ms on `101101`; **MariaDB
  accepts a `WITH` clause inside a derived table**, which is the only reason `whereIn` can hold a
  recursive walk at all; `withoutGlobalScope('accessLevelSelect')` was **not** needed here, because the
  `select('*')` trap only fires when the subquery is a `Contract` query and this one is a plain
  `DB::query()`; and **the merged query is 2-5 ms slower than the two it replaced** - the win is that
  the table cannot silently go blank past 1,000 ids, not speed.
  **The columns stay at `select('*')` and that is deliberate.** Ticket 20's narrow select is not safe
  on any query that feeds `availableContracts()`: that method reads at least ten columns and branches
  on `isset()`, so a column left out changes the rows and the query count with no error.

- [03 - break 8, `$reqfields`](issues/03-find-remaining-breaks.md) - **the dev confirmed it:
  `$reqfieldsText`.** `?tab=timelineedit` fills its missing-fields table now instead of rendering an
  empty one. The table is `display:none` but `contract.js` counts its rows and **disables the Send
  button** on the Approval and Signing popups, so the rows gate the flow. Only the four `timelineedit`
  documents change, by 618-858 characters; the other 26 are unchanged to the character. Report row 17.
  Commit `6515eb5`.
  **One thing is left for the dev:** the controller adds a row to `$reqfieldsText` for every required
  custom field, keyed by `custom_field_id`, and a custom field value lives in `custom_field_data`, not
  on the `contracts` row - so `@empty($contract->$key)` is always true and the field always prints
  "Missing". Contract **16** proves it. There are three candidate arrays with three different meanings
  of missing, so the fix is the dev's call, not a guess.

- [11 - the last two indexes](issues/11-missing-indexes.md) - **four of ticket 08's six are applied, two
  are dropped on measurement.** `contracts_history(id, created_at)` and
  `custom_field_data(custom_field_group_id, custom_field_id, custom_field_group(20), id)` are in, both
  used by the optimiser - full scan to `type ref, rows 1` - and **neither moves a number**, because those
  tables hold 17 and 6 rows on the seeded set. They stay because both grow with use on a client database.
  Dropped: `contract_party_data(contract_party_exe_id)`, where the column holds **one** distinct value
  across 6,940 rows, and `user_action_log(group_id)`, which waits on ticket 12. Report row 18.
  **One thing to remember:** read the row count before writing an index migration. Ticket 08 named the
  six by reading the code, which finds a missing index and cannot size it.

- [13 - the loops](issues/13-visible-to-scope.md) - **the Details tab goes 368 queries to 82, and the
  count stops growing with the contract family:** 502 to 85 on the 12-child fan-out, 130 to 79 on the
  contract with almost no family. Every tab gains - edit 96 to 68, attachment 91 to 56. **All 32
  documents, four contracts across eight tab values, are byte-identical**, compared in one session with
  `git stash`. Five commits, report rows 19 to 23. Three N+1 loops went (`ContractCategories::find()`,
  `contractTypeData`, `contractParent`) and four request-lifetime caches went in (`admin_setting()`,
  `getEntityBranches()`, the four access lists with `fileStorageType()`, and `userInfo()`).
  **`Contract::visibleTo()` is not built**, and after the measurement it should not be: the N+1 was the
  reason for it, and with the N+1 gone the scope saves zero queries while being the largest logic change
  on the map. It is in Out of scope now, as an architecture change for a later effort.
  **Three things to remember:** the request-lifetime cache was worth more than the query rewrites - four
  five-line changes took 115 queries off, and every other page gets the same saving; **guard an eager
  load with `count() > 1`**, because on a one-row collection it costs the query it saves and contract
  `16` went **up** by 3 without it; and `contractParent` returns a **child**, not a parent - the
  `belongsTo` keys are the other way round.

- **The `historical` cookie stays.** The dev asked to delete it and keep the deletion if nothing broke.
  Something breaks: it is the only thing that keeps the Historical nav item on screen while the user
  moves between tabs. Recorded in Not yet specified. No code changed.

- [12 - the waste](issues/12-delete-waste.md) - **all three items done, `100479?tab=details` is at 70 queries.**
  Six results that reached the view and no blade read are gone, and only two of them cost a query. A
  seventh dead query went with them: `checkTablesConfiguration()` builds an **empty** required-table
  list, so it always returns `true` - after a `SHOW TABLES` over the whole schema, twice per request.
  At 9.1 ms that was the slowest single query left on the page. Item 3's two uncached lookups turned
  into five request-lifetime caches, listed in the ticket. Report rows 24 and 25.
  Item 2, the duplicate pairs, is done too - rows 26 and 27. **The ticket's line numbers were all wrong**,
  because five tickets had moved that file, so a temporary `DB::listen` logged the SQL and four stack
  frames for every query instead and named each repeat in one page load. Four sites repeated:
  `get_country()`, the `AddUsers` row inside `getEntityBranches()`, the entity name inside the party
  loop, and **the subject contract row, read twice, each read pulling `Contract::$with` behind it**.
  The contract row needed a `clone`: `availableContracts()` decrypts and reformats **in place**, so the
  row cannot be read raw again after it runs.
  **One thing to remember:** trace, do not read. Every line number in a ticket older than two commits is
  a guess on this file.

## Order of work

This tracker is markdown, so there is no query to find the frontier. The order is written down instead.
A ticket is takeable when every ticket in its "Blocked by" line is closed.

| Ticket | Takeable when | Why this order |
|---|---|---|
| ~~[01 reminder crash](issues/01-fix-null-reminder-crash.md)~~ | **CLOSED** | The page did not render at all. |
| ~~[08 query inventory](issues/08-query-inventory.md)~~ | **CLOSED** | Read-only. Everything below leans on it. |
| [02 realistic seeded rows](issues/02-seed-realistic-contract-rows.md) | now | A baseline on rows that are 60 columns of NULL measures the wrong page. |
| [14 breakage and one duplicate](issues/14-correctness-bugs.md) | now | Narrowed 2026-08-21 to what throws or costs time. The rest is out of scope. |
| ~~[03 find remaining breaks](issues/03-find-remaining-breaks.md)~~ | **CLOSED** | **Nine breaks, not four. Every tab returns 200.** Two of them nobody had tried: `?attachment=` and `?tab=timelineedit`. |
| [04 baseline](issues/04-baseline-attribution.md) | after 03 | Every row in the report sits under this one. |
| ~~[16 is Related Contracts dead?](issues/16-unreachable-blade-region.md)~~ | **CLOSED** | It is not dead. `?tab=details` renders it. Nothing deleted. But it found ticket 18. |
| ~~[18 guard the scans by tab](issues/18-guard-the-scans-by-tab.md)~~ | **CLOSED** | **Edit tab 4,208-4,589 ms to 455 ms, 258 queries to 86.** The biggest win on this map. |
| ~~[20 `$contractsoldothers` scan](issues/20-contractsoldothers-scan.md)~~ | **CLOSED** | Details tab **4,088-5,233 ms to 2,997-3,576 ms**. The query itself 928-1,823 ms to under 5 ms. |
| ~~[19 attachment tab, 2.1-2.2 s outside the database](issues/19-attachment-tab-slow-outside-db.md)~~ | **CLOSED** | **2,171-2,428 ms to 1,327-1,384 ms.** The time was in the blade, not the database: two identical Google Drive calls. |
| ~~[22 cache the Drive token](issues/22-cache-the-drive-token.md)~~ | **CLOSED** | Attachment tab **1,327-1,384 ms to 1,066-1,212 ms**. One refresh per hour instead of one per call. |
| ~~[22 cache the Drive access token](issues/22-cache-the-drive-token.md)~~ | **CLOSED** | **One refresh, then none.** `100479?tab=attachment` 1,327-1,384 ms to **1,066-1,212 ms**; contract `4` to 1,194-1,256 ms. The refresh cost 230 ms today, not the 494-582 ms ticket 19 saw. |
| ~~[15 recursive child walk](issues/15-recursive-child-walk.md)~~ | **CLOSED** | Details tab **3,235-7,109 ms to 1,198-2,207 ms**. The old query was also **wrong**, on 202 of 3,018 contracts. |
| ~~[21 parent walk](issues/21-parent-walk.md)~~ | **CLOSED** | Details tab **1,198-2,207 ms to 686-785 ms**. The slowest query on that tab is now **7-11 ms**. |
| ~~[11 indexes](issues/11-missing-indexes.md)~~ | **CLOSED** | Four of the six applied, two dropped on measurement: three of ticket 08's six tables hold 6, 17 and 49 rows, and a fourth column has one distinct value. |
| ~~[12 delete the waste](issues/12-delete-waste.md)~~ | **CLOSED** | **Ticket 18 already collected most of this on every tab but Details.** The six unread results and the duplicate pairs still stand; the 158 repeated lookups mostly do not. Re-read before starting. |
| [09 stop binding ids](issues/09-replace-wherein-with-joins.md) | **NOW, four sites left** | **The "one site left" line was wrong.** Tickets 15 and 21 replaced the two family-tree *walks*, not the `whereIn` calls that read their ids. `$parentContractArr` is fixed (commit `81f2581`); the two `ContractPartyData` plucks, `$FinalContractList` and `$finalListChild` still bind. `$finalListChild` binds **111 ids on `101101`** and is the slowest query on that page at **92.62 ms** — take it next. |
| ~~[13 visibleTo scope](issues/13-visible-to-scope.md)~~ | **CLOSED** | **Details tab 368 to 82 queries, and the count stops growing with the family tree** - 502 to 85 on the 12-child contract. The `visibleTo()` scope itself is **not** built and is now out of scope; the eager loading was the part that carried the numbers. |
| [10 eSign off the page load](issues/10-esign-check-after-page-render.md) | **NOT THIS EFFORT** | The dev's call 2026-08-22: do it later. Ruled out of scope, see the section below. |
| ~~[17 gzip the HTML](issues/17-gzip-the-html-document.md)~~ | **CLOSED** | Document **326,254 to 35,432 bytes**, 9.2x, for 6-9 ms of CPU. Not config — IIS dynamic compression is not installed on this server. |

- [14 - Correctness bugs found while reading](issues/14-correctness-bugs.md) - **the dev narrowed
  it to speed only 2026-08-21**: bad logic that neither breaks the page nor costs time stays. Three
  commits landed - an empty default for `$contractsoldothers` so a contract the user may not see
  cannot throw the page (`63a1db2`), the dead `$contractsSubseqList` query gone (`d1173aa`), and one
  approvals query instead of two (`8858fce`). Queries on `100479?tab=edit` went **261 to 258**.
  Items 1, 2, 5, 6 and 7 are written up in the ticket as out of scope. Two findings: **nothing in
  the repo reads `$contract->contractPartyNames`**, so item 1's fix would show nothing and would add
  one lazy query per external party across 58 call sites; and the **Related Contracts panel did not
  render on any contract we could load**, so a large block of `viewDetailContract.blade.php` may be
  dead.

## Not yet specified

- **The Details tab is still the slowest tab, and no single query holds it any more.** 686-785 ms and
  369 queries on `100479`, against 306-514 ms and 83-98 on every tab but `attachment`. Tickets 15 and
  21 closed both walks, and the slowest query on the page is now 7-11 ms. What is left is the **count**:
  the `availableContracts()` loops, about 280 of the 369 queries
  ([ticket 13](issues/13-visible-to-scope.md)).
- **The query count on the Details tab now grows with the family tree.** 369 on `100479` (one child),
  426 on contract `1`, **619 on `101101`** (a 12-child fan-out). The blade lazy-loads
  `select * from contracts where parentcontract = ? limit 1` for every related contract, and the four
  `availableContracts()` loops walk every row. Ticket 13's, and the seeded chains make it visible.
- ~~**Should the Historical tab load a snapshot from its cookie?**~~ **SETTLED 2026-08-22. The cookie
  stays, and nothing changed.** The dev asked to try deleting it - keep the deletion if everything still
  works, put it back if not. It does not work: the cookie is the only thing that keeps the **Historical
  nav item on screen while the user moves between tabs**. Every other tab's link carries no `?history=`,
  so `$historicalVersionId`
  ([viewDetailContract.blade.php:98](../../Modules/Contract/resources/views/contract/viewDetailContract.blade.php:98))
  would be empty and the nav item would vanish; the only way back to a snapshot would be to pick it again
  from the History tab. That is a feature loss, not a tidy-up, so by the dev's own test the cookie is left
  alone. The **body** of the page never depended on it: the controller reads `$_GET['history']` and never
  the cookie, which is the odd behaviour ticket 03 recorded and is unchanged. Two dead things were found
  next to it and left alone, because they cost nothing: the `.navstascokie` click handlers in
  `contract.js:2396` and `contractflow.js:1424` write the cookie, and **no blade emits that class**, so
  neither has ever run.
- ~~**What `$reqfields` was renamed to.**~~ **ANSWERED by the dev 2026-08-22: `$reqfieldsText`.** The
  loop reads it now and `?tab=timelineedit` fills its table. Commit `6515eb5`, report row 17. **One
  thing is still the dev's**: the controller adds a row to `$reqfieldsText` for every required custom
  field, keyed by `custom_field_id`, and those values live in `custom_field_data`, so
  `@empty($contract->$key)` is always true and the field always prints "Missing" — contract 16 proves
  it. Three arrays hold three different meanings of missing (`$reqfieldsText`, `$reqfieldsVals`,
  `$reqfieldsVal`), so the choice of test needs the dev. Written up in
  [ticket 03](issues/03-find-remaining-breaks.md).
- **This page reacts to URL shapes nobody has mapped.** `?tab=edit` runs 86 queries;
  `?tab=edit&_n=<anything>` runs **96**. Same with compression on and off, so it is not that change.
  Ticket 03 already found `?tab=<unknown>` serves the Details body. Something reads the query string in a
  way no ticket has accounted for. Cheap to find, nobody has looked.
- Why `SHOW TABLES` runs twice on every page view. Something asks the schema on a page load. Noticed by
  the baseline, cheap, unexplained.

<!-- The unreachable-blade question is ANSWERED 2026-08-22 by ticket 16: the region renders on
     ?tab=details. Nothing was deleted. -->
- What `viewContract` should become. It is ~820 lines doing contract load, eSign polling and status
  update, history, parties, approvals, obligations and four `availableContracts()` passes. Splitting
  it is clearly coming, but the seams only become visible once the baseline says where the time goes.
<!-- The index question GRADUATED 2026-08-21: ticket 08 named the six missing indexes, so it is now
     [ticket 11](issues/11-missing-indexes.md). -->
<!-- The availableContracts question GRADUATED 2026-08-21: the dev chose the Eloquent scope, so it is
     now [ticket 13](issues/13-visible-to-scope.md). -->

<!-- All three of these went OUT OF SCOPE 2026-08-21 on the dev's ruling: bad logic that neither
     breaks the page nor costs time is not this effort's. See the Out of scope section. -->

## Out of scope

- **Switching on the approval gate.** [Ticket 24](issues/24-approval-gate.md). The hidden "required
  fields Missing" table gates the Send button on Send For Approval and Send For Signing, and **it has
  never run for anybody** — the tab holding it returned HTTP 500 on `main` too. Wiring the renamed
  variable in on 2026-08-22 was **reverted the same day**: it switches a dead gate on, and the blade's
  test is wrong for required custom fields, so it would block contracts that are complete (proved on
  contract 16). **Why:** turning on a control that blocks the approval flow is functional work needing
  the dev's intent, not load-time work. Ticket 03's `?? []` guard stays — that is what makes the tab
  render, and it is breakage, which is in scope. The blade carries the whole analysis in a comment so
  nobody wires it up by accident.


- **Paginated dropdowns through an abstract base class.** The dev asked for this on 2026-08-21 and ruled
  it out of scope on 2026-08-22, after measurement contradicted the plan. The dropdown data costs **60 ms,
  1.4%** ([ticket 04](issues/04-baseline-attribution.md)); ticket 18 then removed most of it from every
  tab but Details and ticket 17 took the document to 35 KB, so the byte argument went as well. What was
  left was real work with a real risk — a dropdown that has not loaded must still submit its saved value,
  or saving wipes the field — for 60 ms. **Why:** it is an architecture and UX improvement, not a
  load-time one, and this effort is load time. [Ticket 06](issues/06-dropdown-decision.md) holds the
  measurement so a later effort starts from it.

- **Finishing the `viewContract` split.** The dev asked for it on 2026-08-21 and closed it on
  2026-08-22: "enough for now." The expensive parts came out on their own while optimising — four methods
  left the function — and the rest is pure readability with no load-time effect and a large diff.
  [Ticket 05](issues/05-query-layer-decision.md).

- **Tabs on demand.** [Ticket 07](issues/07-page-size-decision.md). Ticket 18 took most of its win with a
  controller guard instead, and ticket 17 took the document from 326 KB to 35 KB, so what is left is a
  small byte saving bought with the one change on this map that can **silently wipe a column on save** —
  the edit form is one form spanning tabs, so a field in a tab that left the document stops being
  submitted. Bad trade now. Ruled out 2026-08-22.


- **Bad logic that neither breaks the page nor costs time. The dev's ruling 2026-08-21**, when asked
  about four bugs ticket 08 found: *"even bad logic, as long as it is not breaking the page, is okay to
  have. just focus on the performance of the page."* Two tests decide it now: **does it throw, or does
  it cost queries or time?** If neither, write it down and move on.

  Ruled out under it, all written up in [ticket 14](issues/14-correctness-bugs.md):
  - External party names never appear in the Related Contracts column (`Controller.php:295` tests a
    variable that does not exist in that scope). Wrong output, costs nothing.
  - `X == !null` used as a null check in 7 places. Works by accident on positive ids.
  - Chart View lost its pre-approval stages to a commented-out clause. **The duplicate query itself is
    still fixed**, because that is speed — one query now feeds both lists, with the rows unchanged.
  - External party signatures matched to parties by position, so an internal party listed first shifts
    every signature one place along.
  - The signature list counts cancelled and superseded approval rows, unlike every other read here.
  - The `user_action_log` row written on every page load of a Signing or Approved contract.

  These are real bugs. They are simply not this effort's. **Ticket 10 still stands** — the eSign block
  leaves the page load because two outbound HTTP calls in a page view is a speed problem, not because a
  GET should not write.


- **Every other page.** One page per effort, one branch per page, done one at a time — the dev's call
  2026-08-21. The contract list, reports and create pages get their own maps later.
- The legacy Angular app at the IIS document root, and `/login/`.
- **Changing** `goalapp_apollo` or any other tenant database on the local server.
- The `composer.lock` and `nwidart/laravel-modules` version mismatch, and the
  `APP_ENCRYPTION_KEY`-from-hostname design. Both real, both unrelated to this page's speed; both
  already recorded in the dashboard effort's map.

- **`Contract::visibleTo()`, the visibility rule in SQL.** Asked for by the dev 2026-08-21 as half of
  [ticket 13](issues/13-visible-to-scope.md); ruled out of scope 2026-08-22 after the other half was
  measured. The reason to build it was the N+1 inside `availableContracts()`, and the N+1 is gone: the
  method now runs **no query per row**, so moving the same rule into SQL saves zero queries, zero bytes
  and no measurable time. Against that it is the largest logic change on the map - it decides which
  contracts a user may see. **Why:** it is an architecture improvement, not a load-time one, and the
  dev's rule of 2026-08-22 is that performance means page size, load time, render time, database time
  and query count. The case for it survives for a later effort: 55 pages call `availableContracts()`,
  the name does not say what it does, and the rule is written in PHP where SQL would express it.

- **Taking the eSign check off the page load.** [Ticket 10](issues/10-esign-check-after-page-render.md).
  The dev's call 2026-08-22: "we will have to do it later, not this exercise." It stays unmeasured -
  the block only fires on a Signing contract and the test set has none, so it needs a copy of one set to
  Signing first. Nothing is wrong with the ticket; it is simply a later effort's.
