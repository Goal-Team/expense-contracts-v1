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

## Order of work

This tracker is markdown, so there is no query to find the frontier. The order is written down instead.
A ticket is takeable when every ticket in its "Blocked by" line is closed.

| Ticket | Takeable when | Why this order |
|---|---|---|
| ~~[01 reminder crash](issues/01-fix-null-reminder-crash.md)~~ | **CLOSED** | The page did not render at all. |
| ~~[08 query inventory](issues/08-query-inventory.md)~~ | **CLOSED** | Read-only. Everything below leans on it. |
| [02 realistic seeded rows](issues/02-seed-realistic-contract-rows.md) | now | A baseline on rows that are 60 columns of NULL measures the wrong page. |
| [14 breakage and one duplicate](issues/14-correctness-bugs.md) | now | Narrowed 2026-08-21 to what throws or costs time. The rest is out of scope. |
| [03 find remaining breaks](issues/03-find-remaining-breaks.md) | after 02 | Fix what is broken before measuring how slow it is. |
| [04 baseline](issues/04-baseline-attribution.md) | after 03 | Every row in the report sits under this one. |
| ~~[16 is Related Contracts dead?](issues/16-unreachable-blade-region.md)~~ | **CLOSED** | It is not dead. `?tab=details` renders it. Nothing deleted. But it found ticket 18. |
| ~~[18 guard the scans by tab](issues/18-guard-the-scans-by-tab.md)~~ | **CLOSED** | **Edit tab 4,208-4,589 ms to 455 ms, 258 queries to 86.** The biggest win on this map. |
| ~~[20 `$contractsoldothers` scan](issues/20-contractsoldothers-scan.md)~~ | **CLOSED** | Details tab **4,088-5,233 ms to 2,997-3,576 ms**. The query itself 928-1,823 ms to under 5 ms. |
| [19 attachment tab, 2.2 s outside the database](issues/19-attachment-tab-slow-outside-db.md) | now, runs beside anything | The only tab whose cost is not queries: 91 queries, 2,638 ms. No other ticket on this map will touch it. |
| ~~[15 recursive child walk](issues/15-recursive-child-walk.md)~~ | **CLOSED** | **Details tab 3,235-7,109 ms to 1,198-2,207 ms.** `WITH RECURSIVE` in place of the session-variable walk. The seed got parent-child chains first, so the number is real. |
| [11 indexes](issues/11-missing-indexes.md) | four left | Ticket 20 took one and **found a better column order than this ticket guessed** - order by selectivity, not by the order they appear in the `where`. Read its Resolution before adding the rest. |
| [12 delete the waste](issues/12-delete-waste.md) | now, but re-scope it first | **Ticket 18 already collected most of this on every tab but Details.** The six unread results and the duplicate pairs still stand; the 158 repeated lookups mostly do not. Re-read before starting. |
| [09 stop binding ids](issues/09-replace-wherein-with-joins.md) | after 04 | Four query shapes, nothing else. |
| [13 visibleTo scope](issues/13-visible-to-scope.md) | after 12 | **Now a Details-tab-only ticket.** Ticket 18 removed its callers everywhere else. On Details it is still about 274 of the 360 queries, so it is the count win there and nowhere else. |
| [05 split viewContract](issues/05-query-layer-decision.md) | after 09, 12, 13 | Moving code last, so it is not moved twice. |
| [10 eSign off the page load](issues/10-esign-check-after-page-render.md) | after 04 | Independent of the query work. **Unmeasured** — the block only fires on a Signing contract and the test set has none, so it needs a copy of one set to Signing first. |
| [17 gzip the HTML](issues/17-gzip-the-html-document.md) | now, runs beside anything | 326 KB sent uncompressed while 39 assets are compressed. A config line for the biggest byte win on the page. |
| [06 dropdowns on demand](issues/06-dropdown-decision.md) | after 12, 13 | **Demoted by the baseline: the dropdown data costs about 60 ms, 1.4%.** This is a page-weight change, not a speed change. Still worth doing, no longer urgent. |
| [07 tabs on demand](issues/07-page-size-decision.md) | last | Still last — it is the one change that can silently wipe a column on save. **Ticket 18 takes most of what ticket 16 credited to this one, at a fraction of the risk.** Weigh whether the rest is worth it once 18 lands. |

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

- **The Details tab is still the slowest tab, but the seconds are gone.** 1,198-2,207 ms and 369
  queries on `100479`, against 306-514 ms and 83-98 on every tab but `attachment`. Ticket 15 closed the
  child walk. What is left: the **parent walk** at 222-255 ms, the same session-variable shape the
  child walk had, which the same `ancestry` CTE can replace; and the `availableContracts()` loops,
  about 280 of the 369 queries ([ticket 13](issues/13-visible-to-scope.md)).
- **The query count on the Details tab now grows with the family tree.** 369 on `100479` (one child),
  426 on contract `1`, **619 on `101101`** (a 12-child fan-out). The blade lazy-loads
  `select * from contracts where parentcontract = ? limit 1` for every related contract, and the four
  `availableContracts()` loops walk every row. Ticket 13's, and the seeded chains make it visible.
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
