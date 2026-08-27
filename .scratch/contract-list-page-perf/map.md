# Map: Contract List Page Performance

Branch: **`claude/contract-list-page-perf`**. One branch for this page, per
[CLAUDE.md](../../CLAUDE.md). Small commits as soon as a change works.

## Destination

The contract list page — `contracts/list` and every request it fires while a user works it,
above all the AJAX data call — **loads with no error and is fast on the seeded 3,018-contract
dataset**, and the work is **done, committed and measured on this branch**, not only specified.

Scope confirmed by the dev 2026-08-27: the GET page
([`ContractController::listContract`](../../Modules/Contract/app/Http/Controllers/ContractController.php:2438)),
its AJAX data method
([`listContractData`](../../Modules/Contract/app/Http/Controllers/ContractController.php:2156)),
and the list's own JS calls.

**Fast has no fixed number** — same as the earlier efforts. Take every safe win and report what
it came to. The **query count** is the number that must not regress. Bytes count too: the AJAX
call returns the whole list as JSON, so its size is a first-class number on this page.

The effort is finished when the page is correct, the numbers are in
[measurements/report.md](measurements/report.md), and no ticket is open.

## Notes

**Domain.** Same stack as the two earlier efforts: **Laravel 10.48.29** + nwidart/laravel-modules
+ Vuexy template, `/contracts` is the IIS base path, MariaDB 10.4.24. Read the earlier maps for
established facts before charting anything new:
[../contracts-dashboard-perf/map.md](../contracts-dashboard-perf/map.md) and
[../contract-detail-page-perf/map.md](../contract-detail-page-perf/map.md).

**The page.** One GET route [`contracts/list`](../../Modules/Contract/routes/web.php:164) →
`listContract` (84 lines, :2438–2521). The GET renders the frame only: filter dropdowns, zeroed
counters. The list itself arrives by AJAX from `listContractData` (:2156–2436), which returns
**every matching row as one JSON document** (`recordsTotal` = the full count; DataTables `draw`
protocol, filtering done in PHP, no LIMIT anywhere). View is
[contractList.blade.php](../../Modules/Contract/resources/views/contract/contractList.blade.php)
(661 lines) + [contractlist.js](../../Modules/Contract/resources/assets/js/contractlist.js)
(1,277 lines). Filters travel as cookies (`filterSet`, `filterStatus`, `myFilterStatus`,
`filterConType`) and POST fields.

**Skills to consult each session:** `diagnosing-bugs` (performance attribution), `grilling` +
`domain-modeling` (any decision ticket), `research` (AFK fact-finding).

**Standing rules for this effort** — carried whole from the detail-page map, confirmed by the dev
2026-08-27:

- **This effort does the work, not only the plan.** Every decision is followed by the change
  landing on this branch, in a small commit, with a report row.
- **Change functions in place. No `x` copies.** Many callers + bad name is the one side-by-side
  case. See [CLAUDE.md](../../CLAUDE.md).
- **The report has no old-number column.** Row 0 is the baseline; each row records the new
  numbers only.
- **Use fresh-context subagents wherever a ticket allows it.** The controller is 15,462 lines.
- **No id-list `whereIn`. Pass the query, not the values.** Repo rule; see CLAUDE.md and the
  [1000-binding bug](../wherein-1000-bug/spec.md). This page has live sites (ticket 05).
- **Scope is this page's own code.** Shared helpers and blades get written down, not changed,
  unless the change is safe for every caller and measured.
- **Dead code inside this page's scope can be deleted without asking**, once `grep` across the
  repo including blades proves nothing reads it.
- **Migrations: apply on the local dev database, then report.** Working `down()` always.
  Production stays the dev's to run.
- **Correctness bugs: fix what throws or costs time; write the rest down** in the ticket, per the
  dev's two-test rule.
- **No fixed millisecond target.** Query count must not regress.
- **Report only when the dev is needed.**
- **Measure with the debug bar OFF** (`DEBUGBAR_ENABLED=false`), warm, three runs, same seeded
  3,018-contract dataset. Bytes measured too: document bytes, AJAX JSON bytes, total transfer,
  request count, first and last render.
- **Only the `apollo_contracts_expense` database.**
- **Verify in the browser** — the CDP debug profile; ask the dev to log in once if the session is
  gone.
- **Plain words to the dev, caveman English for questions, no summary unless asked.**
- **Remove unnecessary cookies; prefer query parameters.** Dev rule 2026-08-27. Not for
  performance — for testing, security, state, and sharing. Ticket 07.
- **Prefer server-side pagination for the heavy AJAX calls**, via a standard Laravel pattern and
  one reusable abstraction (filters + search + paging), not per-endpoint copies. Dev rule
  2026-08-27, with a qualifier the same day: **only convert the calls where it makes sense** —
  data that grows organically. Small stable lists (the earlier efforts' dropdowns) keep the
  whole-list pattern. Ticket 08.

**Facts established while charting, 2026-08-27:**

- **The GET runs a dead full-table query.** `listContract` loads all contracts at
  [:2443–2447](../../Modules/Contract/app/Http/Controllers/ContractController.php:2443) into
  `$contracts`, then the only consumer — `availableContracts($contracts, true)` at :2493 — is
  commented out, and the view's `compact()` does not include it. ~3,018 rows fetched and dropped
  on every page view. Ticket 04.
- **The AJAX call decrypts six columns of every approval row when `myFilterStatus` is set.**
  [:2231–2245](../../Modules/Contract/app/Http/Controllers/ContractController.php:2231) reads
  `ApprovalContracts::select('*')` filtered by a **plucked id list** (`whereIn('contract_id',
  $contractIds)`), then decrypts `username`, `status`, `previous_status`, `next_action_item`,
  `next_action_description`, `approval_status` per row. The dashboard effort measured that decrypt
  pass at 320–334 ms over 13,861 rows — and `approval_status` is **plain now**, so most of this
  pass may be deletable. And with 3,018 visible contracts the binding count crosses 1,000, so on
  the seeded set this `whereIn` **silently returns zero rows** — "My contracts" goes empty.
- **`availableContracts($contracts, true)` runs over every visible contract on every AJAX call**
  (:2201). The detail-page effort already removed its N+1 loops, but its per-row decrypt/format
  work remains and here it runs on thousands of rows, not 58.
- **The status counters are computed in PHP** over every row on every AJAX call (:2263–2404),
  after the rows are already decrypted and decorated. The dashboard effort moved the same kind of
  fold into SQL (`statusCountRows()` + `foldStatusCounters()` in helpers) — check reuse before
  writing anything new.
- **The branch dropdown decrypts 11 columns in SQL** via `decrypt_data()` (:2451–2466) for every
  `branch_users` row, on every GET.
- **Known global-scope costs apply to every `Contract` query here**: `Contract::$with` eager-loads
  party rows, `ContractRoledBasedScope` calls `admin_setting()`, `BranchScope` adds two queries.
  The detail-page effort added request-lifetime caches for `admin_setting()`,
  `getEntityBranches()`, `userInfo()`, `fileStorageType()` — those savings apply here already.
- Correctness oddity, costs nothing, written down not fixed: the location filter tests
  `$contractPart->contract_party_location_id == !null` (:2293) — the `== !null` pattern the
  detail effort also recorded.

## Decisions so far

<!-- one line per closed ticket, newest last -->

- [01 walk the page, find and fix breaks](issues/01-walk-page-find-breaks.md) — page renders on
  every filter shape after four throw fixes (malformed `filterConType`, scalar `contype`/`concates`,
  missing `status`, malformed `filterSet`). Wrong results written down: all `executed_*` tabs
  empty (case bug → ticket 06), `myFilterStatus` empty on the seeded set (1,000-binding bug →
  ticket 05). `status=all` AJAX is 2,508 rows / 34.2 MB decoded JSON.
- [02 baseline and attribution](issues/02-baseline-attribution.md) — GET ~2.8 s TTFB / 14
  queries; default AJAX ~5.6 s TTFB / 13 queries / 5.5 MB (34.2 MB at `status=all`); table
  last render ~14.7 s. 58% of the AJAX time is `availableContracts()` PHP loops, DB only
  ~0.7 s; the `accessLevelSelect` scope turns both list queries into `select *`.
- [04 delete the dead all-contracts query on GET](issues/04-dead-get-query.md) — dead query,
  its `filterConType` guard and the commented consumer deleted. GET is now ~340 ms / 11
  queries / ~30-47 ms database (was ~2.8 s / 14 / 1.2-1.8 s). AJAX unchanged at 13 queries.
- [06 fix the `executed_*` filter case](issues/06-executed-substatus-filter-case.md) — the status
  compare now goes through `contractStatusKey()` and the substatus token is lowercased. The six
  tabs return rows again (332/128/83/74/74/56; amended 0, no seed rows). `executed` and `draft`
  unchanged. Still 13 AJAX queries.
- [03 query inventory](issues/03-query-inventory.md) — every query traced with callers: GET 11,
  default AJAX 13, +1 with `myFilterStatus` (the 2,508-binding `ApprovalContracts` whereIn, ticket
  05). No duplicates — the detail-effort request caches absorb them all. New: the JSON rows carry
  all 119 contract columns (the `accessLevelSelect` `select *`) and the party rows twice — most of
  the 5.5/34.2 MB body; feeds ticket 08.
- [05 stop binding id lists](issues/05-stop-binding-id-lists.md) — the `myFilterStatus` approvals
  `whereIn` now takes queries (pending unique_ids + live-contract subquery), zero bound ids, and
  only `username` is decrypted, once per pending row. "My contracts" returns rows again on the
  seeded set; id sets proven equal to the old logic in tinker (302/302, threshold off). Still 13
  AJAX queries default, 14 with the cookie.
- [08 server-side pagination, reusable pattern](issues/08-server-side-pagination.md) — the list
  AJAX pages in SQL now: `ContractVisibilityQuery` + status/filters in the query, counters from
  one GROUP BY, one page of 14-field rows through the new `App\Support\ServerSideDataTable`
  (DataTables `serverSide: true`). Default AJAX 474 ms / 5.8 KB (was 5.6 s / 5.5 MB);
  `status=all` 555 ms / 5.8 KB (was 5.8 s / 34.2 MB); table draw ~75 ms after the response.
  15 queries default (13 before: one giant fetch became counters + count + page). Search covers
  the same columns, name via one decrypt pass per request. Every tab count equals the 02/06 truth.

## Order of work

This tracker is markdown, so there is no query to find the frontier. The order is written down
instead. A ticket is takeable when every ticket in its "Blocked by" line is closed and no one has
claimed it (Assignee line).

| Ticket | Blocked by | Why this order |
|---|---|---|
| [01 walk the page, find and fix breaks](issues/01-walk-page-find-breaks.md) | nothing | A page that does not render cannot be measured. Every filter shape, every cookie shape. |
| [02 baseline and attribution](issues/02-baseline-attribution.md) | 01 | Row 0 of the report. GET and AJAX both, server and browser both. |
| [03 query inventory](issues/03-query-inventory.md) | 01 | Name every query site in `listContract` + `listContractData` before rewriting any. |
| [04 delete the dead all-contracts query on GET](issues/04-dead-get-query.md) | 02 | Known win, measured against row 0. |
| [05 stop binding id lists](issues/05-stop-binding-id-lists.md) | 02, 03 | The approvals `whereIn` takes 2,508 bound ids on the seeded set — over the 1,000 silent-zero line. Confirmed live by ticket 01. |
| [06 fix the executed_* filter case bug](issues/06-executed-substatus-filter-case.md) | 02 | All six executed tabs show an empty list; row counts change, so baseline first. |
| [08 server-side pagination, reusable pattern](issues/08-server-side-pagination.md) | 02, 03 | Dev rule 2026-08-27. The 34.2 MB list call is the candidate; numbers and inventory decide the shape. |
| [07 move filters off cookies](issues/07-filters-off-cookies.md) | 03, 08 | Dev rule 2026-08-27. Lands in the same AJAX-contract rewrite as 08, not twice. |

## Not yet specified

- **`availableContracts()` per-row work at list scale.** At 58 rows it stopped mattering on the
  detail page; at 3,018 it may be the whole request. Attribution first (ticket 02), then decide
  what of the decorate/decrypt pass the list actually reads.
- **The counter fold in PHP.** The dashboard solved the same problem in SQL. Whether its helpers
  (`statusCountRows()` / `foldStatusCounters()`) already fit this page's grouping (substatus
  splits, `contractStatusKey()` mapping) is checkable once the inventory exists.
- **The `myFilterStatus` path re-implements "my pending approvals" in PHP** — plucked ids, per-row
  `accessInfo()` calls, JSON-decoding `username` per approval row. The dashboard's
  `actionableItemCounts()` answers nearly the same question in SQL now that `approval_status` is
  plain. Possible big win, needs the inventory first.
- **Client-side cost.** 1,277 lines of list JS rendering thousands of DataTables rows; first and
  last render on the seeded set are unmeasured. May also expose asset-weight work (the dashboard
  effort's ticket 22 took page weight down; check what carried over to this layout).
- **The seeded data may be too thin for the party filter.** `contract_parties` has few rows per
  contract on the seed; the party filter path (:2264–2276) and location filter loop over
  `contract->contractParty`. Check whether the seed exercises them before trusting the numbers.

## Out of scope

- **Every other page.** One page per effort, one branch per page — the dev's standing call.
- The legacy Angular app at the IIS document root, and `/login/`.
- **Changing** `goalapp_apollo` or any other database than `apollo_contracts_expense`.
- **Bad logic that neither breaks the page nor costs time** — the dev's two-test rule. Found so
  far and left alone: the `== !null` null test at :2293.
- `Contract::visibleTo()` as an architecture change — already ruled out once on the detail-page
  map; the same ruling holds unless this page's numbers argue otherwise.
