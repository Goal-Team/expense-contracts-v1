# 03 — Baseline and attribution

Type: `wayfinder:task` (AFK)
Blocked by: 01, 02
Assignee: unclaimed
Status: OPEN

## Question

Row 0 of [measurements/report.md](../measurements/report.md). Measure both pages after the seed
(ticket 01) and after the breaks are fixed (ticket 02).

Per page — `contracts/create-v3` and `contracts/create` — measure warm, three runs,
`DEBUGBAR_ENABLED=false`, cookies cleared:

- server time to first byte
- query count and database time
- document bytes, raw and gzipped
- full page: request count and total transfer
- first render and last render

Then attribute the server time. Which of the twelve master lists costs what, how long
`getGeoGraphDropdowns()` takes, how long the SQL decrypt passes take, how much is blade render.
Use the perf log the earlier efforts wrote to `storage/logs/`.

Attribute the browser time as well: script parse, script run, layout. `contract.js` is 109 KB and
runs whole — say how much of the last render it owns.
