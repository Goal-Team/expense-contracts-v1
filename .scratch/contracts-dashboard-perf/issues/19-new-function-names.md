# Name every new function and route, old beside new

Type: `wayfinder:grilling` · Status: **resolved** · Assignee: kader (2026-08-20) · Blocked by: nothing · **Do this before any code is
written.**

## Question

Standing rule from the dev, 2026-08-20: **nothing is rewritten in place.** Every improvement is a new
function next to the old one, so old and new can be run on the same page and the same data and
compared, and the old one deleted only once the new one is proven. Rule written into
[CLAUDE.md](../../../CLAUDE.md).

Naming, from the same call:

- PSR-1 / PSR-12, as this codebase already does it: classes `StudlyCaps`, **methods `camelCase`**,
  constants `UPPER_SNAKE_CASE`, plain procedural functions `snake_case`. `camelCase` is for method
  names only — never for a class or a controller.
- Old name good -> new name is the old name plus **`x`**.
- Old name bad -> **suggest a better name** that says what the function does, and get it approved.
- One function, one concern — but pull shared code out rather than copying it.
- If in doubt, ask.

This ticket produces the table, all of it approved in one go, before anyone writes code.

### Already decided

| old | new | why |
|---|---|---|
| `ContractDashboardController::dashDetails()` | **`dashboardSummary()`** | Old name says nothing. The method builds the dashboard's summary counters. Dev approved the new name 2026-08-20. |

### Still to name

Work through [spec.md](../spec.md) §10 and name one row per thing that changes. At least these:

1. **The route.** `dashDetails` is bound twice —
   [routes/web.php:166](../../../Modules/Contract/routes/web.php:166) (`GET ''`, name
   `contractDashboard`) and [:168](../../../Modules/Contract/routes/web.php:168) (`POST filterDash`,
   the **same** route name, so one silently shadows the other in `route()` lookups). The new method
   needs its own route and its own name to be reachable side by side. Decide the path and the route
   name, and decide whether the duplicate name gets fixed at the same time or is left alone as
   pre-existing.
2. **The visibility scope** — the department `IN` plus `EXISTS` on `contract_party_data`
   ([spec.md](../spec.md) §3). New, shared, and the follow-on `availableContracts()` effort is meant to
   adopt it, so the name matters more than the others. Where does it live — a query scope, a builder
   macro, a service?
3. **The counter fold** — the ~20 `GROUP BY` rows folded into 15 counters in PHP. Keeps calling
   `contractStatusKey()` ([helpers.php:116](../../../app/helpers.php:116)), which is **unchanged** and
   keeps its name.
4. **The actionable-items counter** — the bounded PHP-decrypt version ([spec.md](../spec.md) §4).
5. **The AJAX dropdown endpoint** ([spec.md](../spec.md) §8) — controller method plus route. New work,
   no old name to sit beside, but it still has to follow the naming rules and it must be **shared**, not
   dashboard-private.
6. **The comparison command** — the throwaway artisan command that diffs old counters against new
   across roles ([spec.md](../spec.md) §9). Names the pair it compares.

### What each row needs

Old name (with `file:line`), proposed new name, one line on why, and `x`-suffix or new name — say
which rule applied. Where a name is a judgement call, say so and ask rather than picking quietly.

### Also settle

- **When the old one gets deleted.** The rule says later. Name the trigger: a row in
  [report.md](../measurements/report.md) showing old and new, plus the comparison command reporting no
  unexpected difference.
- **How the new path is reached while both exist.** A separate route, a query flag, a config switch —
  pick one. A flag read from the request is the cheapest way to A/B the same page, and it must not be
  able to reach production behaviour by accident.

## Answer

Resolved 2026-08-20. The dev delegated the naming — "choose any good name, do not ask me questions
about naming" — and asked only that every name be written down with its reason, in one place, so a
name can be changed later without hunting.

**The record is [names.md](../names.md).** One file, seven sections: the dashboard method and its two
new routes, the shared visibility class, the counter fold, the actionable-items counter, the dropdown
endpoint, the comparison command, and the deletion trigger. Every row carries the rule that applied
(`x`-suffix or new name) and one line on why. The spec and this ticket point at that file rather than
repeating the names, so there is exactly one place to edit.

The names, in short:

- `dashDetails()` -> `dashboardSummary()` (already approved), reached by its **own** new routes
  `contractDashboardSummary` and `contractDashboardSummary.filter`.
- Visibility rule -> `ContractVisibilityQuery` service, with `visibleContracts()`, `applyTo()` and
  `reachableBranches()`. Deliberately **not** called a `Scope` — that word already means an Eloquent
  global scope in this codebase, and this is a hand-applied query-builder fragment.
- Counter fold -> `statusCountRows()` + `foldStatusCounters()`. `contractStatusKey()` unchanged.
- Actionable items -> `actionableApprovalRows()` (SQL) + `actionableItemCounts()` (decrypt and fold),
  split because ticket 17 later replaces only the second half.
- Dropdowns -> `ContractOptionListController::optionLists()`, route `GET contracts/option-lists`.
- Comparison -> `CompareDashboardCounters`, artisan `dashboard:compare-counters`.

Two judgement calls, decided rather than asked:

1. **Both versions are reached by separate routes, not a request flag or a config switch.** A flag on
   the existing route is one typo away from serving new behaviour on the production URL. Two routes
   cannot do that.
2. **The duplicate `contractDashboard` route name is left alone.** Pre-existing, and fixing it inside
   a performance change hides a behaviour change in a speed change. The new pair does not repeat it.
   Worth its own small ticket later.

Deletion of an old function needs both a report.md row (old and new, same session) and
`dashboard:compare-counters` showing no unexpected difference — the only allowed difference being the
"My Actionable Items" numbers.
