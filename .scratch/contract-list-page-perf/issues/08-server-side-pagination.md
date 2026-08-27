# 08 — Server-side pagination for the list AJAX, as a reusable pattern

Type: `wayfinder:task` (AFK)
Blocked by: 02, 03
Assignee: —
Status: OPEN

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

_Open._
