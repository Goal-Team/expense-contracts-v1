# 13 — Replace `availableContracts()` with an Eloquent scope and eager loading

Type: `wayfinder:task` (AFK)
Blocked by: 04, 12
Status: CLOSED 2026-08-22 - the eager-loading half is done and the query count no longer grows with
the family tree. The `visibleTo()` half is **not** done, and the Resolution says why it stopped being
this effort's work.

## The decision, already made

**The dev's call 2026-08-21.** `availableContracts()` does two jobs and the dev does not like the name.
It becomes:

1. **`Contract::visibleTo($user)`** — a real Eloquent scope that picks the contracts a user may see.
   The dashboard effort already proved the visibility rule is expressible in SQL: department `IN` plus
   an `EXISTS` on `contract_party_data` for an internal party in an accessible branch. No decrypted
   value takes part in any filter.
2. **`->with(...)` eager loading** for the category and contract-type names, replacing the per-row
   loop.

**Avoiding the N+1 is the point of the ticket**, in the dev's words. A scope that still lazy-loads per
row has failed.

## Why it is written beside the old one

55 call sites. The new function goes in beside `availableContracts()`, callers move over a few at a
time, and the old one is deleted when nothing depends on it — [CLAUDE.md](../../../CLAUDE.md), "many
callers, and the name is bad". This is the one case in this repo where two functions live side by side,
and the reason is reviewability, not comparison.

**This ticket moves only this page's four call sites.** The other 51 are other pages, and the scope rule
says leave them. The old function stays until a later effort moves them.

## What it costs today

Ticket 08 measured all four calls together at **138 of the page's 375 queries**. The call at line 738
alone is 121: five fixed queries, plus a `ContractCategories::find` and a `contractTypeData` lazy load
for each of the 58 rows it decorates.

## Done when

- `Contract::visibleTo()` exists as an Eloquent scope and returns the same contract ids
  `availableContracts()` returned. **Prove it by comparing the id sets** on several contracts and
  several users, not by looking at the page.
- The category and type names come from eager loading, and the per-row loop is gone.
- This page's four call sites use it. The other 51 are untouched and `availableContracts()` still works
  for them.
- Query count before and after, in the report.
- Verified in the browser on a seeded contract and on contract 1: the Related Contracts, parent and
  child panels list the same entries as before.

## Watch out

- **`Contract::boot()` adds a global `select('*')` scope** that runs after your own `select()`. A
  one-column subquery comes back as all columns. Use
  `Contract::withoutGlobalScope('accessLevelSelect')` where that matters, and leave
  `ContractRoledBasedScope` alone — that one is the visibility rule and dropping it would show a user
  contracts they may not see.
- `Contract::$with` already eager-loads `contractPartyList` on every contract query. Check you are not
  loading party rows twice.
- `contract_party_type` is compared against lowercase `'internal'` in two places and only works because
  the collation ignores case. Do not tighten that into a case-sensitive comparison.

## Resolution - 2026-08-22

**The Details tab went from 368 queries to 82, and the count stopped growing with the size of the
contract family.** That growth was the whole complaint in this ticket.

| contract | before | after |
|---|---|---|
| `100479` (1 child) | 368 | **82** |
| `1` | 349 | **83** |
| `101101` (12-child fan-out) | 502 | **85** |
| `16` (few relations) | 130 | **79** |

Read the before column exactly as it is. **Only `100479` was measured at the start of the session**;
the other three were first measured one commit in, after the `admin_setting()` cache of row 19 had
already taken its 63 queries off. Their true starting numbers are higher - an earlier session recorded
426 on `1` and **619** on `101101` - but those came from a different session and are not mixed into
this table.

Contract `16` is the control. It has little family, so it had little to gain - it must simply not get
worse. It got better too.

Every tab gained, not only Details: `?tab=edit` 96 to **68**, `?tab=attachment` 91 to **56**,
`?tab=history` 80 to **56**.

**All 32 documents - four contracts across eight tab values - are byte-identical before and after**,
compared in one session with `git stash`. Report rows 19 to 23.

### What was actually done

Five commits, each with its own report row:

1. **`AdminSettings::getValue()` holds its answer for the request.** `ContractRoledBasedScope` asks for
   `enable_role_based_data` once per `Contract` query, so one settings row came back **64 times** in one
   page load. Row 19, -63 queries.
2. **The three N+1 loops.** `ContractCategories::find()` per contract became one `pluck()`;
   `$contract->contractTypeData` became `loadMissing()` on the collection; and
   `$contractsoldother->contractParent`, read once per row of the Other Contracts With Parties table,
   became `with('contractParent')` on the query that builds the list. Row 20, -171 on `100479`.
3. **`Helpers::getEntityBranches()` holds its answer for the request.** Three scopes call it on every
   query they build, and each call re-reads two user rows and walks the branch hierarchy - 41 identical
   queries. Row 21, -30 on every tab.
4. **The four access lists and the storage-type row.** `availableContracts()` read four lists at the top
   of each of its four calls, and `fileStorageType()` read its single row nine times. Row 22, -17.
5. **`Helpers::userInfo()` holds its answer for the request.** 76 call sites, and the query decrypts
   `UserName` in the `WHERE`, so it reads and decrypts all 1,605 user rows each time. Row 23, -5
   queries but the most expensive shape left on the page.

Two of those - `admin_setting()` and `getEntityBranches()` - are item 3 of
[ticket 12](12-delete-waste.md). They are done, and ticket 12 records it.

### `Contract::visibleTo()` is not built, and after the measurement it should not be

The ticket asked for two things. The eager loading is done. The scope is not, and here is the honest
reason rather than a quiet omission.

`visibleTo()` was going to move the visibility rule into SQL - department `IN` plus an `EXISTS` on
`contract_party_data`. **The reason it was worth doing was the N+1, and the N+1 is gone.** With the loops
fixed, `availableContracts()` runs **no query per row at all**: it reads four cached lists and then walks
rows in PHP. Moving the same rule into SQL would now save **zero queries**, zero bytes and no measurable
time on this page.

Against that it is the largest logic change on the map. It decides which contracts a user may see. Get
it wrong in the safe direction and a table goes empty; get it wrong in the other and a user sees a
contract they may not. The dev's rule of 2026-08-22 - *do not change logic unless the page breaks, and
performance means page size, load time, render time, database time and query count* - rules it out on
its own terms: it improves none of those five numbers.

**It is still worth doing as an architecture change**, and the case for it survives: 55 pages call
`availableContracts()`, the name does not say what it does, and the rule is written in PHP where SQL
would express it. That is a later effort's, with its own destination. Written into the map's
**Out of scope** section, not lost.

`availableContracts()` is **not deleted and not renamed**. The dev's call, restated 2026-08-22: nothing
gets deleted while 55 pages depend on it, and this effort cannot prove that nothing breaks. Both changes
above are inside the existing function, so all 55 callers get them for free.

### Four things to remember

- **A request-lifetime cache was worth more than the query rewrites.** Four of the five commits are the
  same five-line shape: work the answer out once, hold it in a static, key it on whatever the answer
  depends on. Together they took 115 queries off the page. Nothing about them is specific to this page,
  and every other page in the application gets the same saving.
- **Guard an eager load with a row count.** `loadMissing()` on a one-row collection costs exactly the
  query it saves. Without `count() > 1`, contract `16` went **up** by 3 queries. Always measure a
  contract with little data next to one with a lot.
- **`contractParent` does not fetch the parent.** `belongsTo(Contract::class, 'id', 'parentcontract')`
  has its keys the other way round, so it returns *a child*. The blade only tests it for truth, which is
  why the eager load is safe - a `belongsTo` eager load keeps one row per key, and a contract with 12
  children has 12 candidates.
- **A `whereIn` with bound ids is still on this page.** `BranchScope` passes a collection of branch ids
  into `whereIn('id', ...)`. It is small today, and it is the 1,000-binding bug waiting to happen. Not
  this ticket's - written down for [ticket 09](09-replace-wherein-with-joins.md).
