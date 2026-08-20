# Names for every new function, class and route

One file, one table per area. **Change any name here and nowhere else** — the spec and the tickets
point at this file instead of repeating the names.

Rules applied (from [CLAUDE.md](../../CLAUDE.md) and [spec.md](spec.md) §15): classes `StudlyCaps`,
methods `camelCase`, constants `UPPER_SNAKE_CASE`, plain functions `snake_case`. Old name good ->
old name plus **`x`**. Old name bad -> a name that says what the function does. Nothing is rewritten
in place; every new thing sits beside the old one until the old one is proven dead.

Decided 2026-08-20. The dev delegated the naming and asked only that the reasons be written down.

## 1. The dashboard method and its routes

| old | new | rule | why |
|---|---|---|---|
| `ContractDashboardController::dashDetails()` [:35](../../Modules/Contract/app/Http/Controllers/ContractDashboardController.php:35) | `dashboardSummary()` | new name | Dev approved 2026-08-20. Old name says nothing; the method builds the dashboard's summary counters. |
| `GET ''` -> `dashDetails`, name `contractDashboard` [web.php:166](../../Modules/Contract/routes/web.php:166) | `GET 'dashboard-summary'` -> `dashboardSummary`, name **`contractDashboardSummary`** | new route | The new method needs its own reachable URL so both versions render on the same data. Path is a plain kebab-case noun, matching `contracts/list`. |
| `POST 'filterDash'` -> `dashDetails`, name `contractDashboard` [web.php:168](../../Modules/Contract/routes/web.php:168) | `POST 'dashboard-summary/filter'` -> `dashboardSummary`, name **`contractDashboardSummary.filter`** | new route | The filter post needs its own name, because the two old routes share one name and `route('contractDashboard')` silently resolves to one of them. Dotted suffix follows `dashboard.locationStatus.filter`, already in this file. |

**The duplicate `contractDashboard` name is left alone.** It is pre-existing, the old page depends on
whichever one currently wins, and fixing it inside a performance change hides a behaviour change in a
speed change. The new pair simply does not repeat the mistake. Worth its own small ticket later.

**How the new path is reached while both exist: a separate route. No request flag, no config switch.**
A flag on the existing route is one typo away from serving new behaviour on the production URL. Two
routes cannot do that: the old URL keeps the old code, byte for byte, until step 11 deletes it.

## 2. The shared visibility rule

New work, no old name. Lives in `Modules/Contract/app/Services/`, next to
`ApprovalEntriesBackfillService`.

| new | kind | why |
|---|---|---|
| `ContractVisibilityQuery` | class | Says exactly what it is: the query fragment that decides which contracts a user may see. **Not** named `...Scope` on purpose — the nine classes in [app/Models/Scopes](../../app/Models/Scopes) are Eloquent global scopes that attach themselves to a model, and this is the opposite: a plain query-builder fragment applied by hand. Reusing the word would mislead every reader. |
| `ContractVisibilityQuery::visibleContracts()` | method | Returns a fresh `DB::table('contracts')` builder with the rule already applied. What the dashboard calls. |
| `ContractVisibilityQuery::applyTo($query, $alias)` | method | Adds the same rule to a builder someone else owns — the approvals and tasks joins. One rule, two callers, no copy. |
| `ContractVisibilityQuery::reachableBranches()` | method | Wraps `Helpers::getEntityBranches()` [:301](../../app/Helpers/Helpers.php:301) and holds the Super Admin case: returns `null` for "no branch condition at all", an array otherwise. `null` and `[]` mean opposite things here ([spec.md](spec.md) §9) and the name has to make that the callers' first question. |

The follow-on `availableContracts()` effort adopts this class rather than re-deriving the rule, which
is why it is a service and not a private controller method.

## 3. The counter fold

| new | kind | why |
|---|---|---|
| `ContractDashboardController::statusCountRows()` | private method | Runs the one `GROUP BY contract_status, substatus` and returns the ~20 raw rows. Names its output, not its mechanism. |
| `ContractDashboardController::foldStatusCounters($rows)` | private method | Turns those rows into the 15 counters. "Fold" is the word the spec already uses. One concern each: one queries, one counts. |

`contractStatusKey()` [helpers.php:116](../../app/helpers.php:116) is **unchanged and keeps its name.**
It is correct today and the fold stays in PHP because of it.

## 4. The actionable-items counter

| new | kind | why |
|---|---|---|
| `ContractDashboardController::actionableApprovalRows()` | private method | The SQL half: joins the visibility rule, filters `row_status`/`superseded`, selects the five columns, chunks. Returns rows, nothing decrypted. |
| `ContractDashboardController::actionableItemCounts()` | private method | The PHP half: decrypts `username` and `approval_status` per row and folds the six integers. Named after the panel heading the dev sees, "My Actionable Items". |

Split in two because the decrypt cost is the thing being measured ([spec.md](spec.md) §4) and
[ticket 17](issues/17-plain-columns-experiment.md) replaces exactly one of these halves later. The old
version of this counter is the loop in
[viewDashboard1.blade.php:305-321](../../Modules/Contract/resources/views/dashboard/viewDashboard1.blade.php:305)
— inline blade, no name, so nothing sits beside it. The blade loop stays until step 11.

## 5. The AJAX dropdown endpoint

Shared, not dashboard-private, so it does not live on the dashboard controller.

| new | kind | why |
|---|---|---|
| `ContractOptionListController` | class | `Modules/Contract/app/Http/Controllers/`. One combined endpoint serving option lists to any page — dashboard now, `contractList` later. |
| `ContractOptionListController::optionLists(Request $request)` | method | Returns the requested lists in one JSON object. Plural because one call returns several lists; that is the whole point of the design. |
| `ContractOptionListController::optionListsVersion()` | private method | The `COUNT(*)`/`MAX(updated_at)` cache stamp, copying the pattern at [ContractController.php:6879](../../Modules/Contract/app/Http/Controllers/ContractController.php:6879). |
| `GET 'contracts/option-lists'`, name **`contractOptionLists`** | route | `GET`, not `POST`: it reads, it is cacheable, and it needs no CSRF token from a `select2` fetch. Under `contracts/`, not under a dashboard prefix, because a dashboard-prefixed path would guarantee a duplicate later. |

## 6. The old-vs-new comparison command

| new | kind | why |
|---|---|---|
| `CompareDashboardCounters` | class | `app/Console/Commands/`, beside `CheckMigrations`. |
| signature `dashboard:compare-counters` | artisan signature | Matches the existing `migrations:check` shape: area, then verb-noun. |

Throwaway. Runs `dashDetails()` and `dashboardSummary()` over the seeded 3,018 contracts across
several users and roles and prints any of the 15 counters that differ. Deleted with the old method.

## 7. When the old one gets deleted

Step 11 in [spec.md](spec.md) §10. The trigger is both of these, not either:

1. A row in [measurements/report.md](measurements/report.md) with the old number and the new number,
   same page, same data, same session.
2. `dashboard:compare-counters` reporting **no unexpected difference** — the only allowed difference
   is the "My Actionable Items" numbers, which are wrong today because of the 1,000-id bug.

Until both hold, the old function stays exactly as it is.
