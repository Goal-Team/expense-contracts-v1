# Measurement report: contract list page

Effort: [map.md](../map.md). Branch `claude/contract-list-page-perf`.

Rules: one row per change, new numbers only (the row above holds the previous ones). Row 0 is the
baseline from [ticket 02](../issues/02-baseline-attribution.md). Warm, three runs,
`DEBUGBAR_ENABLED=false`, seeded 3,018-contract set. GET and AJAX measured separately.

| # | change (commit) | GET TTFB ms | GET queries | GET doc bytes | AJAX TTFB ms | AJAX queries | AJAX JSON bytes | first/last render ms | remarks |
|---|---|---|---|---|---|---|---|---|---|
| 0 | baseline | 2,780 (2,241 / 2,970 / 2,784) | 14 | 69,572 (12,504 gz) | 5,600 (5,637 / 5,600 / 4,997) | 13 | 5,535,399 (1,142,051 gz), 406 rows | 3,460 / 14,700 | AJAX is the default shape: `status=draft`, all other fields 0. `status=all`: 2,508 rows, 34,248,287 bytes (7,012,310 gz), TTFB ~5,800 ms, 13 queries. `myFilterStatus` set: 0 rows (1,000-binding bug, ticket 05), 14 queries, TTFB ~4,700 ms. Full page: 47 requests, 1,154,555 bytes transfer warm (the data call is 99% of it). Server side of the AJAX call: ~1.0 s contract fetch, ~3.1 s `availableContracts()`, ~0.2 s filter+counter loop, ~0.4 s JSON encode (1.4 s at `status=all`). Browser spends ~3 s more on parse + DataTables draw after the response ends. |
| 1 | delete the dead all-contracts query on GET (c27eea8) | 336 (378 / 346 / 285) | 11 | 70,544 (12,674 gz) | unchanged | 13 | unchanged | unchanged | Ticket 04. Three GET queries gone: the dead `select * from contracts` (1.0–1.7 s baseline) plus its two eager party-row loads. GET database time is now 29–47 ms. AJAX untouched, still 13 queries, 200 OK. Doc bytes read 70,544 here vs 69,572 at baseline — the GET body varies a little run to run (tokens); the change removes no markup. |
