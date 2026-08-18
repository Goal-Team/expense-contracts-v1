# Design the AJAX dropdown endpoints

Type: grilling
Status: resolved
Blocked by: —

## Question

Moving dropdown option data out of the HTML response into AJAX calls is a **decided outcome**, not an
open question. What remains open is how.

Today the dashboard inlines two option lists — contract types
([viewDashboard1.blade.php:314](../../../Modules/Contract/resources/views/dashboard/viewDashboard1.blade.php:314),
73 rows) and branches
([:323](../../../Modules/Contract/resources/views/dashboard/viewDashboard1.blade.php:323), 99 rows,
fed by a `BranchUser` query that `AES_DECRYPT`s 11 columns). `contractList` inlines four: types,
categories, branches, statuses
([contractList.blade.php:573-602](../../../Modules/Contract/resources/views/contract/contractList.blade.php:573)).

Decide, with the dev:

1. **Which lists convert**, and on which pages. Dashboard only, or `contractList` too — it has twice
   as many and is the likelier beneficiary.
2. **Endpoint shape.** One endpoint per list, or one options endpoint returning several lists in a
   single round trip? Several small requests can be slower than one inline render; the whole change
   backfires if it trades one blocking cost for four.
3. **Selection state.** The blade currently marks the selected option from `$selcontype` and
   `$sellocal` ([ContractDashboardController.php:241-242](../../../Modules/Contract/app/Http/Controllers/ContractDashboardController.php:241)).
   With options arriving later, where does the current selection live and how is it re-applied without
   a flash of empty select?
4. **What renders before the options arrive**, and what happens if a request fails. A filter control
   that silently stays empty is worse than a slow page.
5. **Caching and invalidation.** Branches and contract types change rarely. There is a precedent in the
   codebase — the party list is cached for 10 minutes keyed by a `COUNT(*)`/`MAX(updated_at)` version
   stamp ([ContractController.php:6879-6896](../../../Modules/Contract/app/Http/Controllers/ContractController.php:6879)).
   Reuse that pattern, or something simpler?
6. **Client mechanism.** `select2` is already a dependency; is it used here, and does it change the
   answer (it supports remote data sources and paging natively)?
7. **Authorisation.** These endpoints expose branch and department lists. They must respect the same
   `BranchScope` / `DepartmentScope` / `ContractRoledBasedScope` filtering the page does — a
   convenience endpoint that leaks the full branch list to a scoped user is a real regression.

**Measured sizes** from [ticket 01](01-attach-chrome-devtools.md), which sizes the win:

| Page | `<option>` tags | option bytes | % of HTML |
|---|---|---|---|
| Dashboard | 136 | 10,761 | **15.3 %** |
| Contract list | 163 | 13,306 | **20.4 %** |

Dashboard: `contracttype` 73 options / 9,239 B, plus a second unnamed `select2` 63 options / 6,080 B.
Contract list carries five selects (73 / 63 / 14 / 10 / 3 options).

So the payload cut is real but modest, and document TTFB is **1.9–2.7 s** — three orders of magnitude
more than 13 KB can explain. Design this for the right reason: the win is removing the `BranchUser`
11-column `AES_DECRYPT` select and `ContractType::get()` from the critical path, and only secondarily
the bytes. If the endpoints are designed as four extra blocking round trips, this change makes the page
*slower*, which is why question 2 above matters most.

Note both pages share the same two large lists (73 contract types, 63 branches) — one shared, cached
endpoint pair likely serves both, which also settles question 1.

## Answer

Decided with the dev 2026-08-14.

### Scope: dashboard first, other pages after

Only `viewDashboard1` converts in this spec — the two selects, `contracttype` (73 options) and the branch
`select2` (63 options). `contractList` and its five selects follow as separate work once the pattern is
proven.

**Design implication:** the endpoints must still be built as *shared*, not dashboard-private, because both
pages consume the identical two lists. Name and place them so `contractList` can adopt them unchanged
rather than growing a parallel implementation. A dashboard-only endpoint under a dashboard-specific route
prefix would guarantee duplication later.

### Endpoint: one combined endpoint

A single request returning all requested lists in one JSON object, rather than one endpoint per list.
Four round trips on a page with known connection-queueing problems is the main way this change could make
the page *slower*; one request means one cache entry and one failure mode.

Must respect the same `BranchScope` / `DepartmentScope` / `ContractRoledBasedScope` filtering the page
does — these lists expose branch and department names, and an endpoint that returns the full branch list
to a location-scoped user is a security regression, not a convenience.

### Client: fetch once, populate, keep `select2` static

No `select2` `ajax:` mode. **No pagination for now.** At 73 and 63 options the lists are small enough to
send whole; the goal is getting the queries off the critical path, not paginating them.

`select2`'s `ajax:` mode re-queries on every keystroke, which would turn two queries into dozens and needs
server-side search and paging that does not exist yet.

**Agreed fallback, if measurement shows the single fetch is still too slow:** move to server-side option
loading with a window of ~10 options and infinite scroll. That is explicitly deferred — it is the
`select2` `ajax:` design plus server-side search and paging, and it should only be paid for if the simple
version measurably fails. The spec should record it as a named fallback with the trigger condition, so the
decision is not relitigated from scratch.

### Sizing — and an honest note on the expected win

Measured in [ticket 01](01-attach-chrome-devtools.md): 136 `<option>` tags, 10,761 bytes, **15.3 % of the
dashboard HTML**.

But [ticket 02](02-timing-middleware.md) then measured where the 2.1 s actually goes, and **the dropdown
queries are not in the top costs**. `ContractType::get()` and the `BranchUser` select do not appear among
the duplicate-query offenders; bootstrap (~1.1 s), `menu_configs` (65 queries) and
`information_schema.tables` (13 queries, 198 ms) dominate.

So the realistic expected win from this change is **a 15 % payload cut and two queries off the critical
path — not seconds.** It remains worth doing as the dev has decided, and the architecture is better for
it, but the spec must state the expected gain honestly rather than implying this is what fixes the
dashboard. What fixes the dashboard is bootstrap, the menu queries, the schema introspection, and — at
scale — the `4·N` pattern.

### Still open, deliberately

Selection-state handling (`$selcontype` / `$sellocal` at
[ContractDashboardController.php:241](../../../Modules/Contract/app/Http/Controllers/ContractDashboardController.php:241)),
what renders before options arrive, failure behaviour, and cache invalidation were not settled here. They
are implementation detail for the spec rather than decisions needing the dev — with one steer: reuse the
existing cache pattern from
[ContractController.php:6879-6896](../../../Modules/Contract/app/Http/Controllers/ContractController.php:6879),
which keys a 10-minute cache on a `COUNT(*)`/`MAX(updated_at)` version stamp.
