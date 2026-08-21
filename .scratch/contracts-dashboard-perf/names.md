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
[ticket 17](issues/17-plain-columns-experiment.md) replaces exactly one of these halves later.

**Ticket 17 landed 2026-08-21. Three names, and one plain function:**

| new | kind | why |
|---|---|---|
| `ContractDashboardController::actionableApprovalRowsx()` | private method | Old name is good, so it takes an `x`. Same query as `actionableApprovalRows()` plus `where('approval_status', 'pending')`, and it stops selecting `approval_status` - there is nothing left to test in PHP. |
| `ContractDashboardController::actionableItemCountsx()` | private method | Same, with an `x`. Decrypts `username` for pending rows only. |
| `ContractDashboardController::leadingStatusByGroup()` | private method | **New concern, so a new name rather than an `x`.** The old walk got the group leader for free by reading every row id DESC; once the query returns only pending rows, the leader has to be asked for separately. Named after what it returns - the leading row's status, per `unique_id` group. Not `getLeaders()`: it returns statuses, not rows. |
| `encryptStringx()` | plain function | Beside `encryptString()` in [app/helpers.php](../../app/helpers.php). Writes plain text for any `table.column` named in `config('app.PLAINTEXT_COLUMNS')`, otherwise defers to `encryptString()`. `x` because the old name is good. Plain procedural functions are `snake_case` in this codebase by rule, but `encryptString` is not, and matching its neighbour beats matching the rule here. **Its second argument stopped being decorative**: `encryptString()` ignores it, which is how 8 sites had drifted into passing an email there, and how a bare `'approval_status'` label would have converted three unrelated tables. |
| `?oldApprovalStatus=1` | request flag | Local-only switch on the new route only, beside `?withoutActionableItems=1`. **Was `?plainApprovalStatus=1` for one day.** It opted *in* to `actionableItemCountsx()` while that was being proved; the new counter became the default on 2026-08-21, so the flag inverted and renamed to opt back *out* to `actionableItemCounts()`. Named for what it selects, not for what it is not. It is also the way back if a deployment runs the code before the conversion — `actionableItemCountsx()` returns zeros against ciphertext. Deleted at spec §10 step 11 along with the old functions. |
| `config('app.PLAINTEXT_COLUMNS')` | config key | `UPPER_SNAKE_CASE`, matching `APP_ENCRYPTION_KEY` and `APPROVAL_TYPES` beside it in [config/app.php](../../config/app.php). Says what the list is, not what it is for. **Entries are `table.column`** - `'approval_contracts.approval_status'` - because four tables here have an `approval_status` column and only one is meant to be plain. |
| `ConvertApprovalStatusPlain` / `contract:convert-approval-status` | command | `StudlyCaps` class, kebab-case signature under the `contract:` namespace, matching `ConvertPartyDataCollation` / `contract:convert-party-data`. |
| `idx_approval_contracts_status_lookup` | index | Matches `idx_approval_contracts_contract_id`. Named for the lookup it serves rather than listing four columns. |

**There is deliberately no `decryptStringx()`.** `decryptString()` only decrypts a value starting
with `ey` and returns anything else untouched, so all 63 read sites already handle a plain value and
a half-converted table. That is also the answer to the ticket's "how is mixed data handled" question:
it needs no handling. The old
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
| `ConvertPartyDataCollation`, command `contract:convert-party-data` | console command | Says what it converts, not how. Named for the table and the thing that varies (the collation), because the collation is the whole reason it is a command and not a migration — [ticket 20](issues/20-migration-portability.md). Not `Migrate...`: calling it that would invite someone to move it back into `database/migrations`. |
| `dashboard/partials/option-lists-head.blade.php` | blade partial | Starts the option-list fetch from `<head>`, before jQuery loads. No jQuery, no DOM work. Leaves the **promise** on `window.contractOptionListsPromise`. Named `-head` beside `-js` because the pair is one change split by where it runs, not two features. |
| `window.contractOptionListsPromise` | browser global | A promise, deliberately, not a result. `.then()` on a finished promise fires at once, so the DOM-ready code can never miss a response that landed early. Added 2026-08-20 on the dev's condition that there be no race. |
| `@yield('head-prefetch')` in `commonMaster.blade.php` | blade section | The one hook in the shared layout, added rather than reworked. Empty on all 440 other blades, so no other page changes. |
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

## 8. Added while building (2026-08-20), same rules

These were not on the list when it was agreed. They are recorded here because they exist in the
code now, and because a name is only cheap to change while nothing calls it.

| new | kind | why |
|---|---|---|
| `ContractVisibilityQuery::reachableDepartments()` | method | Sibling of `reachableBranches()`. The department list is the other half of the visibility rule and comes from `EntityBusiness` so `DepartmentScope` applies. |
| `ContractVisibilityQuery::applyPartyLocationFilter()` | method | The dashboard's own location filter (`$request->contractlocs`). Split out of `applyTo()` on purpose: it is a filter the user picked, not part of who may see what, and the two must not be confused. |
| `ContractVisibilityQuery::whereHasInternalPartyIn()` | private method | The one `EXISTS` on `contract_party_data`, used by both the visibility rule and the location filter. Pulled out rather than written twice. |
| `ContractVisibilityQuery::applyRoleRule()` | private method | The conditions `ContractRoledBasedScope` adds through Eloquent. The query builder does not carry a model's global scopes, so they are repeated here — the one place a copy was unavoidable. |
| `ContractDashboardController::myTaskCounts()` | private method | The four task numbers, as conditional counts. Old code had no name for it; it was a loop over every task row inside `dashDetails()`. |
| `ContractDashboardController::emptyActionableItemCounts()` | private method | The six numbers at zero. Used both as the starting value and as the answer when the counter is skipped for a measurement. |
| `viewDashboardSummary.blade.php` | view | New view beside `viewDashboard1.blade.php`. Needed because `$approvalsArr`, `$contracts` and `$contractStatus` are no longer passed and `$stusMy` now arrives ready made. The old view is untouched. |
| `?withoutActionableItems=1` | request flag | Local-only measurement switch on the new route only, so the same page can be measured with the decrypt counter on and off in one session (spec §10 steps 3 and 5). It cannot reach the live URL, which never enters this method. |
| `ContractOptionListController::contractTypeOptions()`, `branchOptions()`, `requestedLists()` | private methods | One list each, plus the parser for the `lists` parameter. One function, one concern. |

Two implementation choices worth knowing, since they are not obvious from the names:

- **The counters group on `HEX(contract_status)`, not on the column.** The table collation is
  case-insensitive, so a plain `GROUP BY` would merge `Terminated` and `terminated` before PHP saw
  them, and the PHP fold is case-sensitive on `Terminated`. `COLLATE utf8mb4_bin` was tried first and
  the server rejected it — `ONLY_FULL_GROUP_BY` does not accept a `COLLATE` expression in the select
  as matching the same expression in the `GROUP BY`. `HEX()` satisfies both.
- **`vite.config.mjs`, not `.js`.** `laravel-vite-plugin@1.0.1` is ESM-only and `package.json` has no
  `"type": "module"`, so Vite loads a `.js` config as CommonJS and the build dies. `.gitignore`'s
  blanket `*.mjs` was narrowed to `/vite.config.*.timestamp-*.mjs` so the config can be committed.

## 7. The menu composer

Decided 2026-08-21, dev approved. [Ticket 23](issues/23-per-request-query-decision.md).

| new | kind | why |
|---|---|---|
| `App\Menu\MenuDataResolver` | class | The current code is an anonymous closure inside `MenuServiceProvider::boot()`, so there is no old name to add `x` to. Named for what it does: it resolves the menu data for the current role. Sits in `app/Menu/`, not in `app/Providers/`, because it is no longer a provider concern once it is a class. |
| `MenuDataResolver::resolveForRole(?string $role): array` | method | Takes the session role, hands back the two menu structures. `?string` because the session role can be absent, which is exactly the case that falls through to the `Default` row. **The old closure body becomes the body of the cache closure inside this method** — so there is no second, dead copy of it and nothing extra to maintain. Dev's call, round 3: no scaffolding just to reach the old path, because the old numbers are already recorded.
| `MenuDataResolver::CACHE_MINUTES` | class constant | Safety-net time limit behind the clear-on-write. Copies the constant name already used in `ContractOptionListController`. |
| `MenuDataResolver::flush(): void` | method | **Replaced the approved `forgetForRole()` while building, 2026-08-21.** Per-role forgetting is wrong: a role with no row of its own falls back to the `Default` row, so editing `Default` changes the answer for roles that appear nowhere in the write. `flush()` bumps one generation number and retires every role's entry at once - cheaper than working out which roles were affected, and it cannot miss one. Called from a `saved`/`deleted` hook on the `MenuConfig` model, **not** from `MenuConfigController` (which has four write methods, not the five the spec said): the hook catches tinker, seeders and any screen added later, and it is one place to read instead of four. |
| `MenuDataResolver::VERSION_KEY`, `cacheKey()`, `version()`, `lookUp()` | private | The generation number and the key it builds (`menu_data:v{n}:role:{role}`), plus the moved closure body. Private because nothing outside the class has any business with them. |
