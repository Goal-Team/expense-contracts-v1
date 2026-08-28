# 08 — Server-side pagination for the list AJAX, as a reusable pattern

Type: `wayfinder:task` (AFK)
Blocked by: 02, 03
Assignee: claude subagent (session 2026-08-28)
Status: CLOSED 2026-08-28

## Question

Dev rule 2026-08-27: prefer pagination for the heavy AJAX calls, with a standard, well-known
Laravel pattern — and one reusable abstraction for paginated queries with filters and search,
not the same code pasted per endpoint.

The dev's qualifier, same day: **only convert the calls where it makes sense.** The earlier
efforts left the dropdown AJAX calls unpaginated because their data does not grow. The test is
business growth: a table that gains rows organically (contracts do) gets paginated; a small
stable list keeps the whole-list pattern.

This page's candidate is `listContractData`: 2,508 rows, 34.2 MB decoded JSON on `status=all`,
filtering done in PHP after fetching everything, DataTables running client-side
(`serverSide` not set, contractlist.js). Ticket 02's numbers and ticket 03's inventory decide
the exact shape; the counters block (`counts`) must survive — it needs whole-set numbers even
when the page shows 10 rows, so the fold moves to SQL or stays a separate query.

Decide and land:

1. The reusable pattern (Laravel's paginator on the query, DataTables `serverSide: true`
   protocol on the client is the industry-standard pairing here — confirm against what the
   codebase already has before writing anything new).
2. Which endpoints on this page convert (the list data call), which keep the old pattern.
3. The per-row work (`availableContracts()` decorate/decrypt) then runs on one page of rows,
   not 3,018 — that is where the time win is expected.

## Resolution

Landed 2026-08-28 in three commits: `bda494d` (the reusable class), `8eab3ba` (the
controller), `0f1e789` (the JS). Numbers in [measurements/report.md](../measurements/report.md)
row 4.

### The design, and why

**Full SQL, not the hybrid.** The ticket offered two shapes. The drops availableContracts()
made in PHP are already expressible in SQL for every role:
[`ContractVisibilityQuery`](../../../Modules/Contract/app/Services/ContractVisibilityQuery.php)
is the query form of the branch, department and role checks, and the dashboard effort proved
it returns the same rows. So the query paginates in SQL and no id-level PHP pass is needed —
except for search over the encrypted name column, below. Every tab count came back equal to
the ticket 02/06 truth in the browser, which is the parity proof for this page.

One transient dropped on purpose: availableContracts() also read the report page's
`filterByLocationReport` cookie. On this page that cookie only ever applied for one request
(the same call cleared it), and this endpoint no longer reads or clears it. Ticket 07 owns
cookies; written there in spirit by this note.

**The reusable class.** [`App\Support\ServerSideDataTable`](../../../app/Support/ServerSideDataTable.php).
It takes the filtered query plus three hooks — an order map, a search callable, a page
transform callable — and answers the DataTables `serverSide: true` protocol (draw / start /
length / search[value] / order). At most three queries: total COUNT (skippable), filtered
COUNT (only when searching), the page. Other endpoints that grow with the data can reuse it;
small stable dropdown lists keep the whole-list pattern, per the dev's qualifier.

**The controller.** `listContractData` builds one query-builder query: visibility +
`contype`/`concates` whereIns + `locations` as an internal-party EXISTS
(`applyPartyLocationFilter`) + `party_id` as an EXISTS on `contract_party_exe_id` + the
ticket-05 `myFilterStatus` id set. That id set stays a PHP-computed list (accessInfo() over
decrypted usernames cannot be SQL); it goes in with `whereIntegerInRaw`, which inlines
integers with zero bound values, so the 1,000-binding bug does not apply. The status tab is
plain `where()` calls (`applyListStatusFilter`); the collation compares case-insensitively,
which is what `contractStatusKey()` did. The query builder carries no `accessLevelSelect`
scope, so the 12-column select actually runs — the `select *` overwrite is gone from this
endpoint.

**Counters.** One GROUP BY over the filtered set (before the status tab), grouped on HEX()
so the collation cannot merge 'Terminated' with 'terminated'; folded in PHP arm for arm as
the old loop (`foldListStatusCounters`). Not shared with the dashboard's private fold: this
page counts `executed_amended`, `under_revision` and `initial_draft`, the dashboard does not,
and the old draft_initial/draft_under_revision = whole-draft-total quirk is kept.

**Rows.** `listContractRows()` returns the 14 fields contractlist.js reads, instead of
119-key models with party rows twice. Dropped keys nothing read: `currency`, `catgoery_identity`,
`contract_type_id`, `contractPartyNames`, `onetime_end_date`, the doubled party arrays.
Response keys the JS reads are unchanged: `data`, `draw`, `recordsTotal`, `recordsFiltered`,
`counts`.

**Search.** The box was client-side over rendered columns. Server-side now covers: contract
name (PHP-encrypted with a random IV, so SQL LIKE cannot see it — one id+name fetch over the
current tab, decrypt, match, ids inlined into the WHERE), status, substatus, priority,
attachment filename, both dates in the rendered d-m-Y format (DATE_FORMAT), type label and
category label (subqueries), branch label (SQL AES_DECRYPT LIKE through BranchScope).
Trade-offs, written down: the formatted currency value is no longer searched (it would
decrypt every contract's value on every keystroke) and neither is the S.No column (a row
number). Search cost: draft 690 ms, all 1,536 ms — the decrypt pass over ~2,508 names is
~1 s and runs only while searching.

**Sorting.** End Date (column 6) sorts in SQL; everything else falls back to id desc, which
matches the old default. The old client sort compared d-m-Y strings lexically, so SQL order
by the stored Y-m-d text is chronological now — different from before, and correct. The
hidden Value column (orderData 12) has no clickable header, so it maps to nothing.

**The JS.** `serverSide: true`, the `deferLoading: 57` leftover removed (with serverSide on
it would have blocked the first request), `ajax.data` is a function so the filter cookies are
read per draw, and the S.No render adds `_iDisplayStart` so numbering continues across pages.
The request contract is unchanged POST fields, so ticket 07 can move them to query
parameters in one place (`ajax.data` + `getFilterSetData`).

### Verification (logged-in CDP browser, filter cookies cleared first)

| shape | expected (tickets 02/05/06) | got |
|---|---|---|
| default load (`status=draft`) | 406 | recordsTotal 406, "Showing 1 to 10 of 406 entries" |
| counts in response | draft 406, all 2,508, executed 747 | equal, plus splits below |
| `executed_*` splits | 332 / 128 / 83 / 74 / 74 / 56, amended 0 | equal, each as tab recordsTotal and as counts |
| all other tabs | — | review 399, negotiation 205, finalization 148, approval 223, approved 175, signing 205, draft_initial 406, draft_under_revision 0 |
| page 2 / page 3 | rows 11–20 / 21–30 | "Showing 11 to 20 / 21 to 30 of 406", S.No continues (11, 21) |
| page size 50 | 50 rows | "Showing 1 to 50 of 406" |
| sort End Date asc/desc | chronological | asc: '-' rows then 01-01-2023; desc: 30-07-2030 first |
| search "Meridian Radiology" (draft) | real name from the seed | 31 rows, counts stay whole-set |
| search "Terminated" (executed tab) | 74 | recordsFiltered 74, all rows substatus Terminated |
| search category / branch label | — | "Taken on Lease" 845; "Indraprastha" 56, rows match |
| `contype` [7] / [7,6] | SQL whereIn same as old | 34 / 69, equals counts.all |
| `locations` [1] | visible ∩ internal party in branch 1 | 6 |
| `party_id` 1 | every visible contract with an external party | 2,507 (contract 16 has no external party — old code excluded it too) |
| `myFilterStatus` | 1 row for this login | 1 row, the same contract as ticket 05 |

### Written down, not fixed (costs nothing)

- The blade's Terminated badge id is `status_executed_Terminated` (capital T) while the
  counts key is `executed_terminated`, so drawCallback never updates it and it shows the GET's
  zero. True before this change too.
- The export buttons (CSV/Excel/PDF/print) now export the current page only — with
  serverSide the client never holds more than one page. Same for the column-visibility
  filter. The column-search inputs were already dead (the thead has no second row).
- The old PHP else-branch compared `contractStatusKey()` output case-sensitively against the
  raw POST value; SQL compares case-insensitively. The JS only ever sends lowercase tokens,
  so no shape changes.
