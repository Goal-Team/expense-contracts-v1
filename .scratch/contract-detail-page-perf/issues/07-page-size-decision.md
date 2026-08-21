# 07 — Decide which page-size cuts this page gets

Type: `wayfinder:grilling` (HITL)
Blocked by: 04
Status: OPEN

## Question

Which of the byte cuts apply to this page, and which are already handled globally by the dashboard
effort?

## What is known

The dashboard effort decided six cuts and found three facts that are server-wide, not page-specific:
IIS gzip only engages from the second request for a file, the HTML document is not compressed at all,
and content-hashed build assets carry no `Cache-Control`. Those are the same server, so they either
already landed or they are still pending for every page. See
[../../contracts-dashboard-perf/issues/22-reduce-page-size.md](../../contracts-dashboard-perf/issues/22-reduce-page-size.md).

What is page-specific here: this page renders far more markup than the dashboard — a 894-line edit
form plus every other tab, all in one document, whether the user opens those tabs or not.

## Done when

- Say which dashboard cuts already cover this page and need nothing.
- Decide on the page-specific ones, above all whether the non-open tabs should render at all.
- Landed and committed, with a report row carrying all three byte numbers.
