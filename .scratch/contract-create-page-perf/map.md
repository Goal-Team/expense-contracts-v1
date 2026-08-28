# Map: Contract Create Page Performance

Branch: **`claude/contract-create-page-perf`**. One branch for this page, per
[CLAUDE.md](../../CLAUDE.md). Small commits as soon as a change works.

## Destination

The contract create page — **`contracts/create-v3`** first, and **`contracts/create`** with it —
**loads with no error and is fast**, and the work is **done, committed and measured on this
branch**, not only specified.

Scope set by the dev 2026-08-28:

- **`contracts/create-v3` is the page.** It is the newest. It wins on any design conflict.
- **`contracts/create` matters too**, above all when the AI create feature is on. The two pages
  must show the same thing. They already share the same twelve dropdown queries, so most server
  wins land on both.
- **Merging the two into one page is not this effort.** The dev's call: a second effort does the
  AI bridge, with its own map and its own grilling. This effort does not change what the pages do.

**Fast has no fixed number** — same as the three earlier efforts. Take every safe win and report
what it came to. The **query count** is the number that must not regress.

The dev named the main problem 2026-08-28: **the dropdown fields**. The page loads twelve master
lists on the GET and prints them into the HTML. The local database has almost no rows in the two
lists that grow in production, so the seed comes first (ticket 01) — a dropdown with one row
cannot be measured.

The effort is finished when both pages are correct, the numbers are in
[measurements/report.md](measurements/report.md), and no ticket is open.

## Notes

**Domain.** Same stack as the three earlier efforts: **Laravel 10.48.29** + nwidart/laravel-modules
+ Vuexy template, `/contracts` is the IIS base path, MariaDB 10.4.24. Read the earlier maps for
established facts before charting anything new:
[../contracts-dashboard-perf/map.md](../contracts-dashboard-perf/map.md),
[../contract-detail-page-perf/map.md](../contract-detail-page-perf/map.md) and
[../contract-list-page-perf/map.md](../contract-list-page-perf/map.md).

**The pages.**

- [`contracts/create`](../../Modules/Contract/routes/web.php:133) → `contractCreate()`
  ([ContractController.php:6588–6705](../../Modules/Contract/app/Http/Controllers/ContractController.php:6588)).
  Also serves `contracts/ai/{aiparam}` (:145). Picks its view at runtime:
  `contractCreate.blade.php` (697 lines) normally, `contractCreateAi.blade.php` (908 lines) when
  `admin_setting('enable_ai_feature')` is on, `contractCreateRep.blade.php` (651 lines) for
  `aiparam=marketing`.
- [`contracts/create-v3`](../../Modules/Contract/routes/web.php:141) → `contractCreateV3()`
  ([:6706–6801](../../Modules/Contract/app/Http/Controllers/ContractController.php:6706)).
  Renders `contractCreateV3.blade.php`. Its body is a **near-exact copy** of `contractCreate()`
  plus one extra query (`AnnexureMaster`). Ticket 05.
- Both load [`contract.js`](../../Modules/Contract/resources/assets/js/contract.js) — 109 KB,
  2,931 lines, 48 top-level functions, two `$(document).ready` blocks and **no page guard**.
  Nine other pages load the same file. The dev's call 2026-08-28: **change it in place, prove each
  change safe for every caller.**
- `contracts/create-v2` ([:137](../../Modules/Contract/routes/web.php:137)) is a dead route —
  nothing links to it. Out of scope, and it stays.

**Skills to consult each session:** `diagnosing-bugs` (performance attribution), `grilling` +
`domain-modeling` (any decision ticket), `research` (AFK fact-finding).

**Standing rules for this effort** — carried whole from the list-page map, plus the dev's calls of
2026-08-28:

- **This effort does the work, not only the plan.** Every decision is followed by the change
  landing on this branch, in a small commit, with a report row.
- **Do not change what the pages do.** No field moves, no merge of create and create-v3, no AI
  bridge. Speed only. The dev's reason: a behaviour change hides a speed win.
- **Change functions in place. No `x` copies.** Many callers + bad name is the one side-by-side
  case. See [CLAUDE.md](../../CLAUDE.md).
- **The report has no old-number column.** Row 0 is the baseline; each row records the new
  numbers only.
- **Use fresh-context subagents wherever a ticket allows it.** The controller is 15,462 lines.
- **No id-list `whereIn`. Pass the query, not the values.** Repo rule; see CLAUDE.md and the
  [1000-binding bug](../wherein-1000-bug/spec.md).
- **`contract.js` is this page's code now**, and every change to it must be checked on all ten
  pages that load it. Dev's call 2026-08-28.
- **Dead code inside this page's scope can be deleted without asking**, once `grep` across the
  repo including blades proves nothing reads it.
- **Migrations and seeders: apply on the local dev database, then report.** Working `down()`
  always. Production stays the dev's to run.
- **Correctness bugs: fix what throws or costs time; write the rest down** in the ticket, per the
  dev's two-test rule.
- **No fixed millisecond target.** Query count must not regress.
- **Report only when the dev is needed.**
- **Measure with the debug bar OFF** (`DEBUGBAR_ENABLED=false`), warm, three runs, same seeded
  data set. Bytes measured too: document bytes, AJAX bytes, total transfer, request count, first
  and last render.
- **Only the `apollo_contracts_expense` database.**
- **Verify in the browser** — the CDP debug profile; ask the dev to log in once if the session is
  gone. Both pages, and the AI flag both on and off.
- **Plain words to the dev, caveman English for questions, no summary unless asked.**
- **Prefer query parameters over cookies**, and **server-side pagination only where it makes
  sense** — data that grows organically. Dev rules 2026-08-27.

**Facts established while charting, 2026-08-28:**

- **`Branch` and `BranchUser` are the same table.** `BranchUser`
  ([app/Models/BranchUser.php:13](../../app/Models/BranchUser.php:13)) sets
  `protected $table = 'branch'`, the same as `Branch`. `$branchs` and `$branchsUser` run the
  identical query twice, each decrypting **11 columns over 99 rows** in SQL. Ticket 06.
- **`AddUsers` and `AddUsersSel` are the same table.** Both set
  `protected $table = 'ContractUsers'`. `$users` and `$usersSel` run the identical query twice,
  each decrypting **5 columns over 1,605 rows**. Ticket 06.
- **`getGeoGraphDropdowns()` is a nested N+1 loop.**
  [Controller.php:35](../../app/Http/Controllers/Controller.php:35) walks the geographical
  hierarchy level by level and fires one `GeographicalHierarchy` query **per node at every
  level**. Ticket 07.
- **Twelve master lists load on every GET** and are printed into the HTML: custom fields,
  categories, contract types, geo hierarchy, branches (twice), entities, users (twice), legal
  advisors, contract parties, party labels + regex, branch names again, countries, contract
  categories, entity businesses. `$contractParties` and `$catego` and `$ent` use `select('*')`.
- **Local master-data row counts, 2026-08-28**: `branch` 99, `ContractUsers` 1,605,
  `entitybusiness` 214, `contract_type` 73, `category` 31, `entity` 6, `contract_parties_label` 5,
  `contract_categories` 3, **`country` 1**, **`contract_parties` 1**, **`legal_advisors` 0**.
  The last three are the dev's point: the page cannot be measured on them. Ticket 01.
- **`contractCreate()` has an undefined variable on its error path.**
  [:6670](../../Modules/Contract/app/Http/Controllers/ContractController.php:6670) merges
  `$fileError`, which the method never sets. It throws when the owner lookup fails. The V3 copy
  does not have the bug. Ticket 02.
- **`enable_ai_feature` is `false` on the local database**, so `contracts/create` renders
  `contractCreate.blade.php` today. The dev wants the AI path tested too — turn the flag on and
  walk it (ticket 02), then put it back.
- **`contract.js` runs whole on every page that loads it.** No page guard. The create page runs
  approval flow, OTP send and check, obligations add and delete, send-for-review and contract
  link code it never uses. Ticket 09.

## Decisions so far

<!-- one line per closed ticket, newest last -->

_None yet._

## Order of work

This tracker is markdown, so there is no query to find the frontier. The order is written down
instead. A ticket is takeable when every ticket in its "Blocked by" line is closed and no one has
claimed it (Assignee line).

| Ticket | Blocked by | Why this order |
|---|---|---|
| [01 seed the create page master data](issues/01-seed-master-data.md) | nothing | `country` has 1 row, `contract_parties` 1, `legal_advisors` 0. The dev's call: without a real seed there is no dropdown cost to see. |
| [02 walk both pages, find and fix breaks](issues/02-walk-pages-find-breaks.md) | nothing | A page that does not render cannot be measured. create, create-v3, AI flag on and off. |
| [03 baseline and attribution](issues/03-baseline-attribution.md) | 01, 02 | Row 0 of the report. Both pages, server and browser both. Needs the seed in place. |
| [04 query inventory](issues/04-query-inventory.md) | 02 | Name every query site in both methods and their blades before rewriting any. |
| [05 one data loader for create and create-v3](issues/05-one-data-loader.md) | 03, 04 | The two methods are near-identical copies. One loader means one place to make fast, and it keeps the two pages showing the same thing. |
| [06 kill the duplicate model queries](issues/06-duplicate-model-queries.md) | 03, 04 | `Branch`/`BranchUser` and `AddUsers`/`AddUsersSel` are the same tables. Four queries and four decrypt passes where two do the work. |
| [07 flatten the geo hierarchy N+1](issues/07-geo-hierarchy-n1.md) | 03, 04 | One query per node at every level, on every GET. Shared helper — measure and prove safe for every caller. |
| [08 cut the dropdown payload](issues/08-dropdown-payload.md) | 03, 04, 06 | The dev's named main problem. Twelve master lists printed into the HTML. Decide per list: trim the columns, or move it to a lookup call. |
| [09 trim contract.js on the create page](issues/09-contract-js-cost.md) | 03 | 109 KB, no page guard, most of it belongs to other pages. Browser time counts. Ten pages must stay working. |

## Not yet specified

- **The POST side.** `storeContract()` and the V3 wrapper are the other half of the page, and the
  dev has not named them. The GET is the measured page today. Once the GET is fast, ask whether the
  save is in scope.
- **The create page's own AJAX calls.** `contracts/create/partylist` and
  `contracts/create/parties` fire from `contract.js`. Ticket 04 names them; whether any of them
  needs the server-side pagination pattern waits on their measured size.
- **The AI blade.** `contractCreateAi.blade.php` is 908 lines against `contractCreate.blade.php`'s
  697. Whether its extra 211 lines cost anything waits on ticket 02 walking it with the flag on.
- **Custom fields.** `createCustomField` is included four times with four category ids. Whether
  that is four passes over one collection or four queries waits on ticket 04.

## Out of scope

- **Merging create and create-v3 into one page, and the AI upload bridge.** The dev's call
  2026-08-28: a second effort, its own map, its own branch. This effort does not change behaviour.
- **`contracts/create-v2`.** A dead route — nothing links to it. It stays as it is.
- **Every other page.** One page per effort, one branch per page — the dev's standing call.
- The legacy Angular app at the IIS document root, and `/login/`.
- **Changing** `goalapp_apollo` or any other database than `apollo_contracts_expense`.
- **Bad logic that neither breaks the page nor costs time** — the dev's two-test rule.
