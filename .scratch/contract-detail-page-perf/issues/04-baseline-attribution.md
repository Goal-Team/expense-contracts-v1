# 04 — Baseline the page and say where the time goes

Type: `wayfinder:task` (AFK)
Blocked by: 01, 02, 03
Status: OPEN

## Question

How slow is this page, and which part is slow? No optimisation decision can be made before this
answers.

## What is known

The timing middleware from the dashboard effort is already installed and already reports per-request
query count and time. `viewContract` runs at least 60 queries by eye, including
`ContractParties::select('*')->get()` twice and four `availableContracts()` decoration passes.

## Done when

- Row 0 of [../measurements/report.md](../measurements/report.md) holds the baseline: TTFB, query
  count, document bytes, total transfer bytes, request count. Debug bar OFF.
- The time is attributed: how much is queries, how much is PHP, how much is the view, how much is
  bootstrap. Name the top five queries by total time and the top five by count.
- Say plainly which of the suspects — the two `select('*')->get()` party loads, the four
  `availableContracts()` passes, the eSign block, the dropdown data — actually costs anything.
