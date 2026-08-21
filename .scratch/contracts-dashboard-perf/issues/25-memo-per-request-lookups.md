# The 19 once-per-request lookups — which get a memo?

Type: grilling
Status: closed 2026-08-21 — **out of scope**. Moved to its own effort:
[.scratch/session-optimisation/spec.md](../../session-optimisation/spec.md)
Blocked by: [23-per-request-query-decision.md](23-per-request-query-decision.md) (ordering only, set by the dev)

## Question

Split out of [ticket 23](23-per-request-query-decision.md) on 2026-08-21, the dev's call: the menu composer
and these 19 are different problems and should not share a ticket. Grill this one **after** the menu
grilling finishes.

[Ticket 24](24-attribute-remaining-overhead.md) attributed all 19 and found **every one is
once-per-request** — none scale with the 15 views, so per-view caching buys nothing. They cost about 45 ms
together, so this is a query-count tidy-up, not a speed fix. The dev has said milliseconds matter more than
counts, so this ticket has to justify itself on something other than the number 19.

| shape | n | caller | fix on the table |
|---|---|---|---|
| `ContractUsers`, 6 cols | 9 | `Helpers::userInfo()` ([Helpers.php:254](../../../app/Helpers/Helpers.php:254)), 9 hand-written sites, no memo | request-scoped memo |
| `ContractUsers`, 7 cols | 2 | `Helpers::getEntityBranches()` via `BranchScope` and `DepartmentScope` | memo, or stop the scopes repeating it |
| `ContractUsers`, 2 cols | 1 | [ContractSessionMiddleware.php:65](../../../app/Http/Middleware/ContractSessionMiddleware.php:65) | leave alone |
| `UserCredential` | 3 | 1 real auth lookup; 2 are the same two scopes re-reading the token just to get a username | stop the repeat |
| `file_storage` | 5 | `fileStorageType()` ([helpers.php:112](../../../app/helpers.php:112)), hits the DB every call | memo |
| `SHOW TABLES` | 2 | `Controller::checkTablesConfiguration()` ([Controller.php:381](../../../app/Http/Controllers/Controller.php:381)), called **twice** from the session middleware | delete the duplicate call |

Open questions for the grilling:

1. **Which of the six get a memo, and which are left alone?** `userInfo()` at 9 calls is the obvious one.
   The single middleware reads are not worth touching.
2. **The duplicate `checkTablesConfiguration()` call.** It runs twice from
   `ContractSessionMiddleware.php:54` and `:85`, and **its required-tables list is empty, so both runs
   check nothing.** Delete one call, delete both, or fill the list in? This is a correctness question
   wearing a performance question's clothes.
3. **The two global scopes re-reading the user.** `BranchScope` and `DepartmentScope` each call
   `getEntityBranches()`, which re-reads `UserCredential` and `ContractUsers`. Memo it, or pass the
   already-known user in?
4. **Super Admin caveat.** Ticket 24 measured a Super Admin session, which takes an early return in
   `getEntityBranches()`. A normal user issues more queries per call, and nobody has measured how many.
   Measure before deciding, or decide without?
5. **Does this earn its place at all**, given 45 ms? The honest case for it is not speed — it is that
   `userInfo()` is called 9 times for one answer, and the next page that adds a call site pays again.


## Answer

**Ruled out of scope 2026-08-21, on the dev's call. The work is real. It is not this map's work.**

The dev's words: make a separate spec for this effort, start the work later.

**Why it is out of scope.** This map's destination is the dashboard. These six lookups are not the
dashboard's code. They are the login and access code, and they run on every page in the app. The
dashboard only shows them. Ticket 23 already found the same thing about the menu composer, and the dev
chose to keep that one, because the app cache pattern was already in the codebase and the win was 391 ms.
This one is 45 ms on the only session anybody has measured. So the same argument does not carry it.

**Nothing is lost.** Four rounds of facts and six decisions are written in
[.scratch/session-optimisation/spec.md](../../session-optimisation/spec.md). The new effort starts from
those, not from nothing. Its open questions are listed there too. The first one is the one that matters:
nobody has measured a normal user.

**No map for the new effort yet.** Charting one needs a grilling to name its destination. That happens
when the dev starts the work, not now.

**Two things found on the way, and neither is performance work.** Both are written in the new spec.
`checkTablesConfiguration()` cannot report a missing table, because its list is empty. And the branch
lookup carries the [`whereIn` 1000-parameter bug](../../../CONTEXT.md) — a third place it lives, after
[ticket 12](12-approvals-empty.md) and `ApprovalEntriesBackfillService`.
