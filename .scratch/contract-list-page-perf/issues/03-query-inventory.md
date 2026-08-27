# 03 — Inventory every query the page runs

Type: `wayfinder:task` (AFK)
Blocked by: 01
Assignee: —
Status: OPEN

## Question

Name every query site behind `listContract` and `listContractData` — including what the global
scopes and `Contract::$with` add, what `availableContracts()` runs per call at list scale, and
what the `myFilterStatus` path adds. For each: what it reads, how often it runs per request,
whether anything reads its result, duplicates, missing indexes, id-list `whereIn` sites.

**Trace, do not read.** The detail-page effort's rule: line numbers in tickets go stale after two
commits, and `DB::listen` with stack frames names each query's caller in one page load. Read the
detail-page [ticket 08](../../contract-detail-page-perf/issues/08-query-inventory.md) and
[ticket 12](../../contract-detail-page-perf/issues/12-delete-waste.md) for the method.

Read-only ticket. No code changes. Its output feeds tickets 04, 05 and whatever the fog
graduates into.

## Resolution

_Open._
