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
| [11 indexes](issues/11-missing-indexes.md) | after 04 | An index added before the baseline hides itself. |
| [12 delete the waste](issues/12-delete-waste.md) | after 04 | Cheapest and safest win. Goes first of the code changes. |
| [09 stop binding ids](issues/09-replace-wherein-with-joins.md) | after 04 | Four query shapes, nothing else. |
| [13 visibleTo scope](issues/13-visible-to-scope.md) | after 04, 12 | 138 of the 375 queries. The biggest single win. |
| [05 split viewContract](issues/05-query-layer-decision.md) | after 09, 12, 13 | Moving code last, so it is not moved twice. |
| [10 eSign off the page load](issues/10-esign-check-after-page-render.md) | after 04 | Independent of the query work. |
| [06 dropdowns on demand](issues/06-dropdown-decision.md) | after 12, 13 | New base class plus front-end work. Bigger than it looks. |
| [07 tabs on demand](issues/07-page-size-decision.md) | last | The one change that can silently wipe a column on save. Do it when everything else is proved. |

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
