# Map: Contract Detail Page Performance

Branch: **`claude/contract-edit-page-perf`**. One branch for this page, per
[CLAUDE.md](../../CLAUDE.md). Small commits as soon as a change works.

## Destination

The contract detail page — `contracts/{id}`, every tab, one controller method
[`ContractController::viewContract`](../../Modules/Contract/app/Http/Controllers/ContractController.php:259)
— **loads with no error and is fast on the seeded 3,018-contract dataset**, and the work is
**done, committed and measured on this branch**, not only specified.

Fast means the same targets the dashboard effort used: under 2 s is good, around 2 s is
tolerable, over 10 s is unacceptable. A query-count ceiling matters more than the millisecond
figure, because it is what stops the regression coming back.

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
- Four `whereIn` calls take a plucked list whose size grows with the dataset —
  `ContractPartyData::whereIn('contract_party_location_id', ...)`,
  `whereIn('contract_party_exe_id', ...)`, `Contract::whereIn('id', $FinalContractList)`,
  `Contract::whereIn('id', $finalListChild)`. This stack silently returns **zero rows** at 1,000 or
  more bound values ([mariadb whereIn bug](../wherein-1000-bug/spec.md)), so these are suspect at
  N=3,018.

## Decisions so far

<!-- one line per closed ticket, newest last -->

## Not yet specified

- What `viewContract` should become. It is ~820 lines doing contract load, eSign polling and status
  update, history, parties, approvals, obligations and four `availableContracts()` passes. Splitting
  it is clearly coming, but the seams only become visible once the baseline says where the time goes.
- Whether the eSign compose/poll block — an outbound HTTP call and a `Contract::update` — belongs in a
  GET request at all. It is a correctness and a latency question at once, and it needs the dev.
- Whether the `availableContracts()` extraction the dashboard effort sized (55 call sites) has to
  happen for this page, or whether this page's four passes can be folded without touching it.
- Whether any index or column change is needed here. Cannot be phrased until the baseline names the
  slow queries.

## Out of scope

- **Every other page.** One page per effort, one branch per page, done one at a time — the dev's call
  2026-08-21. The contract list, reports and create pages get their own maps later.
- The legacy Angular app at the IIS document root, and `/login/`.
- **Changing** `goalapp_apollo` or any other tenant database on the local server.
- The `composer.lock` and `nwidart/laravel-modules` version mismatch, and the
  `APP_ENCRYPTION_KEY`-from-hostname design. Both real, both unrelated to this page's speed; both
  already recorded in the dashboard effort's map.
