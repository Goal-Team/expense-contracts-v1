# 07 — Move the list filters off cookies onto request parameters

Type: `wayfinder:task` (AFK)
Blocked by: 03, 08
Assignee: claude subagent (session 2026-08-28)
Status: CLOSED 2026-08-28

## Question

Dev rule 2026-08-27: remove unnecessary cookies; prefer query parameters where possible. Not a
performance change — the dev wants it for testing, security, state, and sharing (a URL with the
filters in it can be sent to a colleague; a cookie cannot).

The cookie surface, charted by ticket 01's walk:

| Cookie | Read server-side | Written by |
|---|---|---|
| `filterSet` | `getFilterSetData()` — injects `$_POST` keys | contractlist.js:735-747 |
| `filterStatus` | echoed in the view; also written by the server on every AJAX (:2199) | JS + server |
| `myFilterStatus` | existence only, :2225 | dashboard.js / contractlist.js |
| `filterConType` | GET-side `whereIn`, :2444 | dashboard.js |
| `filterConLoc` | never read server-side | dashboard.js |
| `filterApplied` | never read server-side | contractlist.js |
| `filterByLocationReport` | inside `availableContracts()`, Controller.php:299-307 | reports page |

Decide per cookie: move to a query/POST parameter, keep (cross-page state genuinely needed,
e.g. dashboard → list handoff), or delete (never read). Then land the change. This waits on
ticket 08 because server-side paging rewrites the AJAX request contract anyway — move the
filters in the same rewrite, not twice.

## Resolution

Landed 2026-08-28 in two commits: `e903793` (the list page: controller + blade +
contractlist.js) and `549f11b` (the dashboard handoff). Numbers in
[measurements/report.md](../measurements/report.md) row 5 — no regression: 15 queries default,
5,767 bytes, TTFB inside row 4's spread.

### The new contract

The list's filter state is the URL query string:

    /contracts/list?status=executed&contype=["7"]&concates=["2"]&locations=["2"]&my=1

The GET reads the parameters for the tab strips, the hidden `#status` field and the dropdown
preselects. The DataTables `ajax.data` function reads them again on every draw and sends them
as the same POST fields as before (`status`, `contype`, `concates`, `locations`, `userData`,
`party_id`). Multi-select changes update the URL with `history.replaceState` and redraw in
place — no full reload where there was none. Tab clicks, the status dropdown and the clear
buttons navigate with rewritten parameters. The URL can be copied and sent to a colleague;
verified by pasting a three-filter URL into a cookie-free navigation and getting the same
state back (rows, dropdowns, tab highlight, Clear Filters button).

### Per-cookie verdict

| Cookie | Verdict | Why |
|---|---|---|
| `filterSet` | **kept, write-only** (list side deleted) | The bulk-export page ([contractBuilkExport.blade.php:33](../../../Modules/Contract/resources/views/contractimport/contractBuilkExport.blade.php), [ContractExportController.php:473](../../../Modules/Contract/app/Http/Controllers/ContractExportController.php)) prefills its download form from it — genuine cross-page state. The list no longer reads it (`getFilterSetData()` and its `$_POST` injection are deleted); `_syncExportCookies()` in contractlist.js mirrors the URL into it on every draw, and deletes it when no filter is set. Verified: export form prefills correctly. |
| `filterStatus` | **kept, write-only** (same reason) | Same export page reads it into the form's `status` field. The server `setcookie` in `listContractData` is deleted; the JS write in `_syncExportCookies()` mirrors the drawn status, which is what the old server write did. Every list-side read is now the `status` URL parameter. |
| `myFilterStatus` | **moved to URL** (`my=1`) | Existence-only flag. Blade button logic and the server check (now the `userData` POST field) behave exactly as before; the ticket-05 1-row result verified on `status=all&my=1`. |
| `filterConType` | **moved to URL** (`contype`) | Dashboard → list handoff travels on the navigation URL now. Bonus: the list's type dropdown preselects from it, which the cookie flow never did. |
| `filterConLoc` | **moved to URL** (`locations`) | Same handoff. Was never read server-side; the AJAX field carries it now. |
| `filterApplied` | **deleted** | Only fed the Clear Filters button visibility; that derives from the URL now (`_toggleClearFilters()`). |
| `filterByLocationReport` | **left alone** | Belongs to the reports page. This endpoint stopped reading it in ticket 08; not touched here. |

### Stale-cookie transition

The server reads no filter cookie, so stale values change nothing. contractlist.js deletes
`myFilterStatus`, `filterConType`, `filterConLoc` and `filterApplied` once on load, and
overwrites `filterStatus`/`filterSet` from the URL, so a stale filter no longer leaks into the
export form either. Verified: all six cookies planted with hostile values (including
`filterSet=not-json-at-all`) → default draft page, 406 rows, cookies gone after load.

### UX changes, written down

- **Cross-visit persistence is gone on purpose.** A plain `/contracts/list` link (menu,
  bookmark) always opens the default draft view now; before, a leftover `filterStatus` cookie
  reopened the last tab. The dev's rule: cookies only when state must cross pages, and
  cross-visit memory is not that.
- The "Show My Contracts" button shows once a `status` parameter exists, same as it showed
  once the cookie existed — a user on the bare default URL does not see it until they pick a
  tab. Same shape as before, different carrier.
- `clearAllFilters` keeps `my=1` and `clearMyActions` keeps nothing but `status=all` — both
  match the old cookie behaviour exactly.

### Left for a later effort

- The export handoff could become URL parameters on the export link itself, killing the last
  two cookies — that means touching the export page (out of scope, shared surface).
- `recount_status_()` and its `search.dt` hook are deleted (the visibility toggle no longer
  depends on the drawn rows). Nothing else called them (grep, blades included).
- dashboard.js still deletes `filterStatus`/`filterSet`/`filterApplied`/`myFilterStatus` on
  dashboard load (pre-existing lines, left alone) and still writes `myFilterTasks` for the
  tasks page — the tasks page is not this effort. The dead `getContTypeLocs()` call on the
  tasks tile is gone with the function; its two cookies had no reader left.
