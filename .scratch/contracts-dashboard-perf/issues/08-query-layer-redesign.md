# Redesign the dashboard query layer

Type: grilling
Status: resolved
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

Settled with the dev 2026-08-20, over three rounds. Every one of the 8 items above is decided.

### The shape

The dashboard **stops calling `availableContracts()`** and gets its own query, built with
`DB::table('contracts')` — the query builder, not the model. It never loads a contract row into PHP.

Reason for the builder rather than Eloquent: `Contract::boot()`'s `select('*')`
([Contract.php:114](../../../app/Models/Contract.php:114)) and
`$with = ['contractPartyList']` ([Contract.php:17](../../../app/Models/Contract.php:17)) rewrite every
query made through the model, app-wide. The builder is not subject to them, so the problem is
sidestepped rather than fought. It also deletes the ~4.2 s of PHP hydration outright, because nothing
is hydrated.

**The counters become one `GROUP BY contract_status, substatus`** over the visible set, returning
~20 rows. PHP folds those ~20 rows into the 15 counters, applying `contractStatusKey()` as it does
today. The `CASE` fold is deliberately **not** pushed into SQL: `contractStatusKey()` and the
`Terminated` case-sensitivity already work correctly in PHP, and MySQL's case-insensitive collation
would silently change the answer if the logic moved into SQL `CASE` arms. ~20 rows crossing the
boundary is free.

**The visibility rule is written once as a reusable query scope** (department `IN` plus an `EXISTS`
on `contract_party_data` for an internal party in a reachable branch), not inline SQL — so the
follow-on effort adopts it rather than re-deriving it.

### The id list is deleted, not chunked (item 4)

`$contractIds` and `$contractStatus` both disappear. The approvals and tasks queries `JOIN` against
the visibility scope instead of receiving a PHP array of 2,508 ids.

This is a **correctness** decision, not a tuning one. Ticket 12 proved a `whereIn` of ≥1000 bound
parameters silently returns zero rows on this server. Chunking into sub-1000 batches was considered
and **rejected**: it treats a silently-wrong-answer bug as a size limit to tiptoe around, and the
next `whereIn` written anywhere in this app reintroduces it with no warning. With no id list, the bug
is not possible. `$contractStatus` becomes a column on the approvals result instead of a PHP lookup
table.

**`$contracts` stops being passed to the view.** Verified: `viewDashboard1.blade.php` references it
on exactly one line — [:330](../../../Modules/Contract/resources/views/dashboard/viewDashboard1.blade.php:330),
the temporary perf probe. Not passed as an empty placeholder either; the dev's call is to remove it
and see whether anything breaks.

### "My Actionable Items" — the schema is the bottleneck (item 6)

**There is no approvals panel.** `$approvalsArr` is never displayed. It feeds a pure counting loop at
[viewDashboard1.blade.php:305-321](../../../Modules/Contract/resources/views/dashboard/viewDashboard1.blade.php:305)
that produces six integers for the "My Actionable Items" card. 13,867 rows are fetched and six fields
decrypted per row to compute six numbers that could be one query.

Except they cannot, today. Verified against the database:

- `approval_status` and `username` are **encrypted in all 13,867 rows**, AES-128-CBC with a random
  IV — the same value encrypts differently every time. Not matchable, not filterable, not indexable
  in SQL. No index helps.
- `original_username` is **empty in all 13,867 rows**, but it is **not** a plaintext fallback — the
  dev confirmed it serves another purpose and must not be repurposed.
- `Helper::accessInfo($email, false)` reduces to plain `strtolower($email) === strtolower(session
  user)` — role is irrelevant on that branch. The *logic* is trivial; the *data* is opaque.
- `approval_contracts` has **`PRIMARY` only** — no index on `contract_id` or `unique_id`. Feed to
  [ticket 09](09-index-and-migrations.md).

Doing nothing is not available: the moment the id list is replaced with a `JOIN`, this counter turns
back on by itself and brings the full decrypt with it.

**Decision: plain-text shadow columns on `approval_contracts`** — `approval_status_plain` and
`approver_email` — added by Laravel migration, backfilled, and indexed. The counter then becomes one
indexed `GROUP BY`. A bounded PHP decrypt ships first as the interim so the correctness fix does not
regress the page. Caching the counter was rejected: caching a number that is wrong for a different
reason is not a fix.

**They are filled by a `saving` hook on the `ApprovalContracts` model**, not by patching call sites.
Verified this works: `approval_contracts` is written from 6 controllers with 43 `create` calls and
203 `approval_status` assignments, and **every write goes through the model** — no `DB::table()`, no
`DB::statement`, no raw SQL. So one hook covers all of them, including any new write path added
later. A database trigger cannot work: the value arrives already encrypted, so the database cannot
read it.

### Behaviours preserved, and the two deliberately changed (items 2, 3, 7)

**Preserved exactly:**

- **Contracts with no internal party stay invisible.** Today they never reach
  `$contract->applicable = true` ([Controller.php:250](../../../app/Http/Controllers/Controller.php:250))
  and are excluded from every counter; ticket 04 seeded 300 of them. Recorded in the spec as a known
  divergence for a separate decision — the dev has not ruled it intended or a bug, and this ticket
  must not change what the numbers say.
- **The `Terminated` casing.** The PHP switch is case-sensitive on `'Terminated'`; MySQL's collation
  is not. Keeping the fold in PHP (see above) preserves today's count byte-for-byte. Whether to fold
  case properly is a separate data question.
- **Super Admin's empty-branch-set means "everything".** [Helpers.php:323](../../../app/Helpers/Helpers.php:323)
  reads an empty reachable-branch list as "see all"; SQL's `IN ()` means the opposite. **The role is
  checked in PHP before the query is built** — a Super Admin simply gets no branch condition added.
  "No filter" and "filter by every value" are kept as different code paths so the dangerous case
  cannot be written by accident.

**Deliberately changed:**

- **The `filterByLocationReport` cookie is dropped from the dashboard.**
  [Controller.php:167](../../../app/Http/Controllers/Controller.php:167) clears it on any non-reports
  controller, but `setcookie()` only takes effect next request while `$_COOKIE` at
  [:280](../../../app/Http/Controllers/Controller.php:280) still holds the old value — so the
  dashboard inherits the reports page's location filter on exactly one arbitrary request after
  leaving a report, then never again. Nobody designed that. The dashboard has its own filter
  (`$request->contractlocs`). Dropping it also keeps the new visibility scope free of superglobal
  state, which is what makes it reusable.
- **"My Actionable Items" numbers will change** — they are silently zero today because of the 1000-id
  bug. This is the one expected difference.

### Not this ticket

`availableContracts()` itself is **not** rewritten here. 55 call sites — 52 of them the identical
shape `availableContracts($x, true)`, plus 3 count-by-key variants and 1 `partyData` variant — spread
over ContractController (28), ContractReportsController (23), Tasks (2), Dashboard (2), Import (1),
Export (1).

The function change would be small; the **verification is the project**. Those call sites consume the
decorated objects the loop builds — `contract_name`/`currency`/`currency_value`/`end_contract_type`
decrypted in place, `catgoery_id` overwritten with the category name, `contract_type` overwritten
with the type name, plus `contractPartyNames`, `location_branch`, `catgoery_identity`,
`contract_type_id`, `currency_value_converted`, and `fixed_date` reformatted. Narrowing any of it
breaks a view or an export silently, and there is no test suite. 23 of the 52 are report and export
paths, where a wrong number is worse than a slow page.

Scoped as a follow-on effort, framed as **extract the visibility predicate into a reusable scope,
leave the decoration loop alone** — not "rewrite the helper".

### How this is proved correct

No test suite exists, and Q2/Q3 both rest on the new numbers matching the old ones exactly.

**A throwaway artisan command** runs the old counting loop and the new query side by side over the
seeded 3,018 contracts, across several users and roles, and prints any of the 15 counters that
differ. Deleted once the change ships. Expect exactly one deliberate difference — the "My Actionable
Items" numbers. Everything else must match, including the 300 no-party contracts and the `Terminated`
casing.

Real tests are the right long-term answer but need a test setup this repo does not have. Separate
effort.

### Expected result

| what | recovers |
|---|---|
| Kill the two N+1s (`contract_categories` 3,018×, `contract_type` 2,508×) | ~7.7 s |
| Stop hydrating 3,018 × 110-column rows to count them | ~4.2 s |
| **Together** | **~11.9 s of the 12.6 s controller time** |

Against a 14.4 s baseline that is itself an under-estimate (ticket 12: the approvals and tasks paths
were dormant when it was measured). The ~1.8 s outside the controller is bootstrap and view render —
[ticket 11](11-per-request-overhead.md)'s territory, not this one.

Measure after killing the N+1s, before committing to the full aggregate — it is possible the cheaper
half suffices, and [ticket 09](09-index-and-migrations.md) has to know which query shapes are real
before it can pick indexes.

## Correction, 2026-08-20

The **interim bounded PHP-decrypt counter promised here is dropped.** [Ticket 15](15-approval-backfill-plan.md)
measured the backfill at **~2 seconds one time** (27,734 values decrypted in 0.49 s locally), not the hours
this ticket assumed. The gap the interim covered is two seconds inside a single deploy, so the release
simply orders itself: add columns -> fill -> switch the page over. No dual code path ships.
