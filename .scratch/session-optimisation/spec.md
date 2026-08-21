# Spec: session and access overhead

**Status: seed. Not started. No map yet.**

This file holds what we know today. It is the starting point for a later effort. Nobody has
charted a [wayfinder map](../contracts-dashboard-perf/map.md) for this yet. Charting it is the
first job when the effort starts.

Written 2026-08-21. It came out of
[ticket 25](../contracts-dashboard-perf/issues/25-memo-per-request-lookups.md) on the dashboard
map. That ticket is closed, and the dashboard map now lists this work as out of scope.

## The problem

Every page in this app runs the same login and access queries. The code reads the same rows many
times in one request. The dashboard reading is 19 queries and about 45 ms.

The 19 are six groups. This is the measured dashboard request from
[perf-2026-08-21.log](../../storage/logs/perf-2026-08-21.log).

| what it reads | queries | ms | who calls it |
|---|---|---|---|
| the logged-in user's row | 9 | 60 | `Helpers::userInfo()` |
| the file storage setting | 5 | 4 | `fileStorageType()` |
| the login token row | 3 | 6 | the session check, and the two scopes |
| `SHOW TABLES` | 2 | 9 | `Controller::checkTablesConfiguration()` |

The user's branch list sits inside the two figures above. `Helpers::getEntityBranches()` reads the
token row and the user row every time it runs.

## What we know

These are facts. Someone read the code and checked them.

1. **No code in this project writes the user row.** The project only reads `ContractUsers`. Another
   app owns the writing. Two databases hold a `ContractUsers` table with 1,529 rows each:
   `apollo_contracts_expense` and `goalapp_apollo`. This project reads the first one.
2. **`Helpers::userInfo()` has 86 call sites.** The dashboard runs 9 of them. Other pages run more.
   [Helpers.php:242](../../app/Helpers/Helpers.php:242)
3. **`fileStorageType()` has 85 call sites.** [helpers.php:109](../../app/helpers.php:109). It reads
   one row: `file_storage` id 1. One page writes that setting,
   [ContractsetupController.php:55](../../Modules/Contractsetup/app/Http/Controllers/ContractsetupController.php:55).
   That page redirects at once. It never reads the value again in the same request.
4. **`ContractUsers` has no `updated_at` column.** So there is no cheap change stamp to read. A
   `MAX(id)` query finds a new user. It does not find an edit to an existing user. This rules out
   holding the user row across requests.
5. **`Controller::checkTablesConfiguration()` checks nothing.** Its list of required tables is
   empty, so it always answers "all good".
   [Controller.php:380](../../app/Http/Controllers/Controller.php:380). The session middleware calls
   it two times, at [line 54](../../app/Http/Middleware/ContractSessionMiddleware.php:54) and
   [line 85](../../app/Http/Middleware/ContractSessionMiddleware.php:85). It has no other callers.
6. **A normal user pays much more than a Super Admin.** `Helpers::getEntityBranches()`
   ([Helpers.php:301](../../app/Helpers/Helpers.php:301)) works in steps. It reads the token row,
   then the user row. Then it checks for Super Admin, and **a Super Admin stops there** with an empty
   list. A normal user goes on. The code then runs one raw SQL query for **each** place id in the
   user's `AccessLevel`. After that it reads `GeographicalHierarchy` once and `branch` once. So a
   Super Admin pays 2 queries. A normal user pays 4 plus the number of ids.
7. **The branch lookup runs more than once per request.** It sits inside a
   [global scope](../../CONTEXT.md). `BranchScope` is on the `BranchUser` model. `DepartmentScope` is
   on `Departments` and on `EntityBusiness`. A global scope fires on every query through those
   models. The dashboard fired them 2 times.
8. **Every number here comes from a Super Admin session.** Nobody has measured a normal user. This
   is the biggest gap in what we know.

## Decisions already taken

The dev took these in the ticket 25 grilling, 2026-08-21, before the work moved out of the
dashboard map. They stand. The new effort does not need to ask again.

1. **Memo `Helpers::userInfo()` inside the function.** All 86 call sites then get it, and no call
   site changes. A [memo](../../CONTEXT.md) lives for one request only. Requests take 0.7 s to
   1.1 s, so the row can be at most about 1 second old. The other app can write at any time, and the
   memo cannot be wrong for longer than one request. Today the same request reads the row 9 times up
   to 60 ms apart, so the page already mixes old and new. The memo does not add a new problem.
2. **A memo, not the [app cache](../../CONTEXT.md), for the user row.** `userInfo()` gives back the
   email, the name and the username in plain text. The app cache writes files to
   `storage/framework/cache/`. That is a bad trade for 45 ms.
3. **A hash of the response does not work.** To hash the row you must first read the row. That is
   the query we want to remove.
4. **Add `Helpers::userInfoFresh()` beside the memo.** It skips the memo and reads the database. A
   page that saves a user and reads it back calls this one. No page needs it today.
5. **The file storage setting goes in the app cache, not a memo.** The dev's point: it does not
   change after you set it once. The app cache gives 0 queries instead of 1. The one writer,
   `fileConfigStore()`, must clear the cache. The value is the word `Local`, `Microsoft` or `Google`,
   so a file on disk is fine. This takes 5 queries to 0 on every page in the app.
6. **Delete the second `checkTablesConfiguration()` call, and nothing else.** That call is a repeat,
   and repeats are what this work removes. The empty list of required tables is a different problem.
   It is about correctness, not speed. It gets written down, not fixed here. This follows the rule
   from round 4 of
   [ticket 23](../contracts-dashboard-perf/issues/23-per-request-query-decision.md): "finds nothing"
   is not the same as "dead".

## Open questions

1. **What does a normal user pay?** Somebody must log in as a user who is not Super Admin, then
   measure. Until then we do not know if this work is worth 45 ms or ten times that. See fact 6 and
   fact 7.
2. **How do we fix the two scopes?** Memo `getEntityBranches()`, or hand the user we already know
   into it. The second one changes the function signature. This question waits on question 1.
3. **What is the destination of this effort?** A spec, or a change applied in place. This is the
   first thing the new map must name.
4. **Does this work reach past the six groups?** The same login and access code runs on every page.
   Other pages may repeat other lookups. Nobody has looked.

## Found on the way, not this effort's work

1. **The empty required-tables list.** `checkTablesConfiguration()` cannot report a missing table,
   because nothing is on its list. Somebody should decide if the check is wanted. If it is, fill the
   list in. If it is not, delete the function and both calls.
2. **The [`whereIn` 1000-parameter bug](../../CONTEXT.md) in the branch lookup.**
   [Helpers.php:390](../../app/Helpers/Helpers.php:390) does
   `whereIn('id', $finalHierarchyList)`. That list holds every child place under the user's access
   level. At 1,000 entries the query gives back zero rows and no error. The user then sees an empty
   branch list with no warning. **This now has its own effort:**
   [.scratch/wherein-1000-bug/spec.md](../wherein-1000-bug/spec.md). Do not fix it here.
