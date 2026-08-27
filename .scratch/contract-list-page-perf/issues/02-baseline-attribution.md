# 02 — Baseline the page and say where the time goes

Type: `wayfinder:task` (AFK)
Blocked by: 01
Assignee: —
Status: OPEN

## Question

What does the page cost today, and which parts hold the cost? Row 0 of
[measurements/report.md](../measurements/report.md).

Measure warm, three runs, `DEBUGBAR_ENABLED=false`, seeded 3,018-contract set, logged-in browser
session. Two requests, measured separately:

1. **GET `contracts/list`** — TTFB, query count, document bytes, total transfer, request count.
2. **AJAX `listContractData`** — the shape the page actually fires on load (read
   [contractlist.js](../../../Modules/Contract/resources/assets/js/contractlist.js) for the real
   payload), TTFB, query count, **JSON bytes**, and the same with `myFilterStatus` set.

Browser numbers too: first render and last render of the table on the seeded set — the browser's
own work is performance per the dev's list, and 3,018 client-side DataTables rows are the suspect.

Attribution the way ticket 04 did it on the detail page: where do the milliseconds sit — database,
decrypt/PHP loops, blade/JSON serialisation, browser render? The timing middleware and
`DB::listen` tooling from the earlier efforts are already in the repo.

## Resolution

_Open._
