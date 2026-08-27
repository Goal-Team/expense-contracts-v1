# Measurement report: contract list page

Effort: [map.md](../map.md). Branch `claude/contract-list-page-perf`.

Rules: one row per change, new numbers only (the row above holds the previous ones). Row 0 is the
baseline from [ticket 02](../issues/02-baseline-attribution.md). Warm, three runs,
`DEBUGBAR_ENABLED=false`, seeded 3,018-contract set. GET and AJAX measured separately.

| # | change (commit) | GET TTFB ms | GET queries | GET doc bytes | AJAX TTFB ms | AJAX queries | AJAX JSON bytes | first/last render ms | remarks |
|---|---|---|---|---|---|---|---|---|---|
| 0 | baseline | — | — | — | — | — | — | — | pending ticket 02 |
