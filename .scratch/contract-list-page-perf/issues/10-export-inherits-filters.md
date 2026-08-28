# 10 — The export inherits the list's filters, cookies die

Type: `wayfinder:task` (AFK)
Blocked by: 09
Assignee: —
Status: OPEN

## Question

Dev's idea 2026-08-28: the green Contract Export button carries the list's whole filter state
on its URL, the export page loses its own contract-type dropdown, and the two remaining
write-only cookies (`filterSet`, `filterStatus`) die. The export respects every list filter —
status, contype, concates, locations, my, and the search box value (search rides the POST, not
the URL — dev's call: search stays out of the query string).

The shape:

1. The green button's `href` = `/contracts/builk-export` + the list's current query string,
   plus the current search value if any.
2. The export blade drops the ContractType multiselect and every cookie read; the form keeps
   export columns + all-in-one-page; the filters ride the form action's query string.
3. One shared filter-assembly method, extracted from `listContractData`, used by both the list
   and `bulkDownload` — the same query, the same visibility, list and export cannot drift.
   `bulkDownload`'s own status branches, its `availableContracts()` call and the dead
   `filterSearch` loop (shape never matched, proven 2026-08-28) go.
4. Sheet-per-type stays: loop the types in `contype`, or all types when absent.
5. Delete: `_syncExportCookies()` in contractlist.js, the export blade's cookie JS, both
   cookie writes. After this no filter cookie exists anywhere on the page.

Verify in the browser: filter the list several ways (tab, type, category, location, my,
search), click export each time, the XLSX row counts must equal the list's `recordsFiltered`
per type sheet. No filters = full export. Stale cookies planted → ignored. The export of a
searched list matches the searched rows.

## Resolution

_Open._
