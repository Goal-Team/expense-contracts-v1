# Spec: the `whereIn` 1000-parameter bug

**Status: seed. Not started. No map yet.**

This file holds what we know today. It is the starting point for a later effort. Nobody has charted
a [wayfinder map](../contracts-dashboard-perf/map.md) for this. Charting it is the first job when the
effort starts.

Written 2026-08-21. The bug was found in
[ticket 12](../contracts-dashboard-perf/issues/12-approvals-empty.md) on the dashboard map. That
ticket fixed nothing. It only found the bug and named the dashboard's two victims. Two more turned up
later, in other tickets. The dev's call, 2026-08-21: give it its own effort.

This is not a performance problem. **The app shows users wrong data and reports no error.**

## The problem

The database server is **MariaDB 10.4.24**. It has a setting called
`in_predicate_conversion_threshold`, and its value is **1000**.

When a query asks `WHERE id IN (...)` with **1000 or more** values, the server gives back **zero
rows**. It does not fail. It does not warn. It answers "nothing matched".

With 999 values the same query works.

The measured proof from ticket 12, on the same data:

| what we asked | rows back |
|---|---|
| `whereIn` with the first **999** ids | 6,684 correct |
| `whereIn` with the first **1000** ids | **0 wrong** |
| all 2,508 ids, with the setting turned off | 11,506 correct |

**The threshold is exact.** 999 works. 1000 fails.

## Who this hits

**Any tenant with 1,000 or more rows in the list.** `goalapp_apollo` holds 2,886 contracts. So this
is almost certainly happening in production today.

The user sees a page that loads, with an HTTP 200, showing an empty panel or a count of zero. Nothing
anywhere reports a problem.

## The four places we know about

Nobody has swept the codebase. These four came up while working on other things.

1. **The dashboard approvals panel.**
   `ApprovalContracts::whereIn('contract_id', $contractIds)` at
   [ContractDashboardController.php:171](../../Modules/Contract/app/Http/Controllers/ContractDashboardController.php:171).
   The panel goes blank.
2. **The dashboard task counters.**
   `Tasks::whereIn('contract_id', $contractIds)` at
   [ContractDashboardController.php:187](../../Modules/Contract/app/Http/Controllers/ContractDashboardController.php:187).
   All four counts read zero.
3. **The approval entries backfill.**
   `buildLocationMap()` does `whereIn('custom_field_group_id', $contractIds)` at
   [ApprovalEntriesBackfillService.php:1073](../../Modules/Contract/app/Services/ApprovalEntriesBackfillService.php:1073).
   "Insert all" feeds it every missing contract id. At 1,000 or more, every contract gets the location
   `-`. **This one writes the wrong answer into the database.** It is the worst of the four.
4. **The user's branch list.**
   `getEntityBranches()` does `whereIn('id', $finalHierarchyList)` at
   [Helpers.php:390](../../app/Helpers/Helpers.php:390). That list holds every child place under the
   user's access level. The user then sees an empty branch list. Found while working
   [ticket 25](../contracts-dashboard-perf/issues/25-memo-per-request-lookups.md).

**The codebase holds 110 `whereIn` and `whereNotIn` calls** in `app/` and `Modules/`. Forty of them
are in `ContractController.php`. Nobody has checked which ones can grow with row count.

## Fix options, from ticket 12

None of these are applied.

| fix | what it does | risk |
|---|---|---|
| `SET SESSION in_predicate_conversion_threshold=0` when the app connects. One line in the PDO options of the `mysql` connection in [config/database.php](../../config/database.php) | fixes every `whereIn` in the app at once | The whole app. It gives up a speed trick that MariaDB means to use on very long lists. **The right first step.** It stops the app losing data today. |
| Rewrite the query as a `JOIN` or an `EXISTS`, so there is no id list | removes the whole class of bug, and it is faster | Every place you rewrite. **The right end state.** It is already the direction of [ticket 08](../contracts-dashboard-perf/issues/08-query-layer-redesign.md). |
| `PDO::ATTR_EMULATE_PREPARES => true` | the values go to the server as text, so the bad path never runs | The whole app. It changes how every query is sent. Wider effects than the first option. |
| Cut the list into batches under 1,000 | works | You must do it at every call site. One missed site stays broken, and it stays silent. |
| Upgrade MariaDB | the real fix | Server work. Somebody else's job. |

Ticket 12's recommended order: turn the setting off first, so the app stops losing data. Then remove
the id lists as the query work happens. Then ask if the setting is still wanted.

## Open questions

1. **Do we turn the setting off now, before anything else?** It is one line and it stops silent data
   loss. Against it: it changes every query in the app, and nobody has measured what that costs.
2. **Which of the 110 `whereIn` calls can reach 1,000?** Somebody must read them. The ones fed by a
   contract id list are the first to check.
3. **What does the destination of this effort look like?** A written plan, or a change applied in
   place. The new map must name it first.
4. **Is production on the same MariaDB version, with the same setting?** We cannot look. Production
   is off limits for this work. So the effort must decide how it gets that answer.
5. **How do we stop the next one?** The failure is silent, so nobody finds it by using the app. A
   check that shouts when a `whereIn` list grows past 999 would catch the next one. Nobody has
   designed that.

## What this effort must not do

**Never change `goalapp_apollo` or any other tenant database.** Read-only, and only when asked. The
rule is in [CLAUDE.md](../../CLAUDE.md).
