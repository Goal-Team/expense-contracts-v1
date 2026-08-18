# Redesign the dashboard query layer

Type: grilling
Status: open
Blocked by: 05, 11, 12

## Question

Treated as **one unit** at the dev's request — the N+1 fixes, the `$approvalsArr` blade walk, and the
counting loop are not to be split into separate tickets.

Today `dashDetails` runs ~10 fixed queries plus ~4·N, loads every active contract with all 110 columns
(the `select('*')` global scope overrides the narrow `select`), and computes 15 stage counters in a PHP
`foreach`. The charting research established the counting **can** collapse to roughly one aggregate
query: all grouping columns are plaintext, `contractStatusKey()` is a pure map expressible as a SQL
`CASE`, and `availableContracts()` visibility is a department `IN` plus an `EXISTS` on
`contract_party_data`. **Reporting tables are not required** — which was the dev's main effort concern.

Decide, with the dev:

1. **The aggregate query design** — one `GROUP BY` producing all 15 counters, or a small number of
   queries? How are the `contract_status` `CASE` fold and the nested `substatus` switch expressed?
2. **The two behaviours that must survive the rewrite**: contracts with no internal party are currently
   excluded from every counter ([Controller.php:221](../../../app/Http/Controllers/Controller.php:221)),
   and an empty accessible-branch set means "all branches" for Super Admin, not "none"
   ([Helpers.php:323](../../../app/Helpers/Helpers.php:323)) — inverting that would leak or hide
   everything.
3. **The `Terminated` case-sensitivity divergence.** The PHP switch is case-sensitive with `'Terminated'`
   capitalised while the other substatus values are lowercase; MySQL's default collation is
   case-insensitive. A `GROUP BY` will therefore be *more* forgiving than the current code. Decide
   deliberately whether to preserve the existing (arguably buggy) counts or fix them, because the
   numbers on the dashboard will change either way.
4. **`$contractIds`.** Collected at
   [ContractDashboardController.php:104](../../../Modules/Contract/app/Http/Controllers/ContractDashboardController.php:104)
   and consumed downstream at `:172` and `:188` for the approvals and tasks queries. An aggregate
   rewrite must either still produce that id list or rework both consumers.
5. **The global scopes.** `Contract::boot()`'s `select('*')` and `$with = ['contractPartyList']` defeat
   any narrow query. Removing or overriding them touches every other page using the model — is that in
   scope here, or does the dashboard bypass the model?
6. **`$approvalsArr` in the blade** — the full approval history for every visible contract, walked in the
   view with a `Helper::accessInfo()` call and `json_decode` per row. Moved to the controller, folded
   into the aggregate, or loaded separately?
7. **The `$_COOKIE['filterByLocationReport']` filter** — cookie-sourced input feeding a location filter.
   Where does it belong in the new query?
8. ~~**The duplicate lazy load** at `Controller.php:219`/`:223` — the cheapest win available.~~
   **Measured and withdrawn.** [Ticket 05](05-baseline-attribution.md) shows `contractPartyList` does not
   appear among the duplicate queries at all, because `protected $with = ['contractPartyList']`
   ([Contract.php:17](../../../app/Models/Contract.php:17)) eager-loads it in a single query. The
   "duplicate lazy load" **costs nothing**. Do not spend time on it.

## Measured targets for this redesign

[Ticket 05](05-baseline-attribution.md) attributed the cost precisely. **The pattern is 2·N, not the 4·N
this ticket originally assumed.** At N=3,018, controller time is 12,583 ms of a 14,437 ms response, and
two queries are essentially all of it:

| n | total ms | query | site |
|---|---|---|---|
| **3,018** | **4,236** | `contract_categories where id = ?` | [Controller.php:228](../../../app/Http/Controllers/Controller.php:228) |
| **2,508** | **3,460** | `contract_type where contract_type_id = ? and applicable = ?` | [Controller.php:321](../../../app/Http/Controllers/Controller.php:321) |

5,526 of 5,654 queries; 7.7 s of the 8.3 s DB time. The remaining ~4.2 s of controller time is PHP —
hydrating 3,018 rows × 110 columns (the `select('*')` global scope) plus the counting loop.

So this redesign has two distinct halves worth roughly equally: **eliminating the two N+1s** (~7.7 s) and
**not hydrating 3,018 full rows to count them** (~4.2 s). An aggregate `GROUP BY` addresses both at once;
eager-loading the two relations addresses only the first. Decide whether both are needed or whether the
cheaper fix suffices — measure after the first.

## Established 2026-08-17: the blade never uses `$contracts` at all

Counting real uses in `viewDashboard1.blade.php` (excluding the temporary probe lines):

| variable | real uses in the view |
|---|---|
| `$counts` | 28 |
| `$stusMy` | 10 |
| `$stusMyTask` | 4 |
| `$branchs` / `$contractTypes` | 3 each |
| `$approvalsArr` | 2 |
| `$contractStatus` | **2** — both `$stusMy[$contractStatus[$appr[0]->contract_id]]++` at [:308](../../../Modules/Contract/resources/views/dashboard/viewDashboard1.blade.php:308) and [:315](../../../Modules/Contract/resources/views/dashboard/viewDashboard1.blade.php:315), i.e. only for contracts present in `$approvalsArr` |
| **`$contracts`** | **0** |

**The collection of 2,508 fully-hydrated 110-column models is passed to the view and never read.** It
exists only to be iterated in the controller's counting loop and to build `$contractStatus`, which is in
turn consulted only for contracts appearing in the approvals list.

That means `availableContracts()` — where *both* N+1s live
([Controller.php:228](../../../app/Http/Controllers/Controller.php:228) and
[:321](../../../app/Http/Controllers/Controller.php:321)) — spends its entire per-contract budget
decrypting `contract_name`, `currency`, `currency_value`, `end_contract_type`, resolving category names,
type names, location labels and party names **for display fields this page never renders**.

So essentially the whole ~7.7 s of N+1 queries and most of the ~4.2 s of PHP is producing data that is
thrown away. The page genuinely needs only: the counters, the approvals panel, and a status map for the
contracts in that panel.

## The `$approvalsArr` walk is dormant because of a bug — design for it anyway

View render *decreased* from 461–887 ms at N=18 to 379 ms at N=3,018. [Ticket 12](12-approvals-empty.md)
found why: **MariaDB silently returns zero rows for `IN` with ≥1000 bound parameters**
(`in_predicate_conversion_threshold = 1000`, `EMULATE_PREPARES = 0`). So at scale `$approvalsArr` is
*empty*, and so is the `Tasks` result feeding `$stusMyTask`.

Two consequences for this redesign:

1. **Do not treat the `$approvalsArr` walk as free.** It is dormant only because the query is broken. Once
   fixed it decrypts six fields across ~11,500 rows and walks them in the blade with a
   `Helper::accessInfo()` + `json_decode` per row. Item 6 above is a real cost — design for it.
2. **Item 4 changes character.** Passing 2,508 ids from PHP back into SQL is not just slow, on this server
   it is *incorrect*. An `EXISTS`/`JOIN` formulation has no id list and cannot hit the bug. **That makes
   this redesign a correctness fix, not only an optimisation** — and it is the strongest argument for
   doing it properly rather than settling for eager-loading the two N+1 relations.

Also note ticket 05's N=3,018 measurements were taken with both paths dormant, so **14.4 s is an
under-estimate** of true load at that scale.

## Answer

<!-- filled on resolution -->
