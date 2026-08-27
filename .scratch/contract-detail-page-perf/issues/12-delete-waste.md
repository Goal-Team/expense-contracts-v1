# 12 — Delete the queries nothing reads, and stop repeating the ones that do

Type: `wayfinder:task` (AFK)
Blocked by: 04
Status: CLOSED 2026-08-24 - all three items done. `100479?tab=details` is at **70 queries**, from 368 at the
start of the session. See Progress and Resolution at the end.

## Question

Nothing to decide. Ticket 08 found waste with no upside. Remove it. This is the cheapest and safest
part of the whole map, so it goes first.

Deleting dead code inside this page's scope needs no approval — the dev's call 2026-08-21 — as long as
`grep` across the repo, blade files included, proves nothing reads it.

## Three kinds of waste

**1. Six results that reach the view and no blade reads.** `$categorys` (664), `$signedHistory` (951,
a full scan of `user_action_log`), `$currentApproval` (from 955), `$isCurrentApprover`,
`$attachmentUrl`, `$waitingGateGroupIds` (1029 — only its `count()` is used, so keep the count and drop
the list).

**Careful here.** Ticket 08 checked the 18 blades this page really renders. Backup files
(`*.blade.php-bcck`, `-au11`, `-bc`) and other views (`viewnew`, `viewExDetailContract`) do read some
of these names, which is why a plain repo-wide grep looks clean. **A backup file is not a caller.** But
another live view is. Before deleting each one, confirm which of the two it is by checking whether any
route renders that view.

**2. Nine exact duplicate query pairs.** 405/663, 407/665, 456/711, 701/765, 667/682 (same `branch`
table, same 11 columns, one with a scope and one without — check they really return the same rows
before folding), 801/944, 955/980, and 262/455/715 which read the same contract row three times. Also
line 801 is fully dead: 944 overwrites its result with the same query.

**3. Two lookups that run uncached and cannot change inside one request.** `admin_setting()` fires once
per `Contract` query through `ContractRoledBasedScope`. `Helpers::getEntityBranches()` fires twice per
`BranchUser` query through `BranchScope`, and one of those scans all 1,605 `ContractUsers` rows because
it decrypts `UserName` inside the `WHERE`. Both give the same answer for the whole request. Memoise
them per request. Ticket 08 calls this the cheapest thing on the list.

## Watch the scope

`admin_setting()`, `BranchScope` and `ContractRoledBasedScope` are **shared with every page**. Memoising
for the length of one request does not change what any page gets back, so it is allowed — but say so in
the commit message, and check no page depends on reading a value that another part of the same request
just wrote.

## Done when

- Each deletion has a `grep` behind it, recorded in the commit message.
- Each duplicate pair is one query, and the second reader gets the first one's result.
- `admin_setting()` and `getEntityBranches()` run once per request.
- Query count before and after, in the report.
- Verified in the browser: every tab still shows what it showed. This is the ticket most likely to
  blank a section by deleting one thing too many.
- Small commits — one per kind of waste at least, not one big one.

## Progress - 2026-08-22

**`100479?tab=details` is at 76 queries and 93.6 ms in the database.** It was 368 at the start of the
session. This ticket did the last 8 of that; [ticket 13](13-visible-to-scope.md) did the rest.

### Item 3, the two uncached lookups - DONE, and three more like them

Both named lookups are done, and reading them exposed three more of the same shape. All five are
request-lifetime caches: work the answer out once, hold it in a static, key it on whatever the answer
depends on.

| What | Calls per page load, before | Report row |
|---|---|---|
| `admin_setting('enable_role_based_data')`, from `ContractRoledBasedScope` | **64** | 19 |
| `Helpers::getEntityBranches()`, from three scopes | 41 queries across its calls | 21 |
| The four access lists in `availableContracts()`, and `fileStorageType()` | 12 and 9 | 22 |
| `Helpers::userInfo()` | 6, each decrypting all 1,605 user rows | 23 |
| `Helpers::authTokenUser()`, new, shared with `ContractSessionMiddleware` | 3 | 24 |

**Every one of these is shared with the whole application**, as this ticket warned. Holding a value for
the length of one request cannot change what another page gets back, and the one writer of each was
checked: `AdminSettings` clears its entry on `setValue()` and on the model's `saved` and `deleted`
events, and `ContractsetupController` redirects after writing the storage type.

### Item 1, the six unread results - DONE

All six are gone from the view payload. Report row 25, and the check behind each is in the commit
message: every blade in every module, every PHP file, every JS file, with backup files and the
compiled cache ruled out as callers.

**Only two of the six cost a query** - the `Category` read and `getSignedHistory()`. The other four
were PHP work and payload. `getSignedHistory()` itself stays, because it has other callers.

**One more dead query went with them**, found while reading: `checkTablesConfiguration()` builds an
**empty** required-table list, so it always returns `true` - after running `SHOW TABLES` over the whole
schema, twice per request through `ContractSessionMiddleware`. At 9.1 ms it was the slowest single
query left on the page. Report row 24.

### Item 2, the duplicate pairs - DONE 2026-08-24

**Found by tracing, not by reading.** The ticket's line numbers came from ticket 08 and five tickets had
moved that file since, so instead a temporary `DB::listen` wrote the SQL and four stack frames for every
query on the page, into `storage/logs/qtrace.log`. One page load named every repeat exactly. The trace
was removed again; it is not in the branch.

Four sites repeated, and all four now read once. Report rows 26 and 27.

| What | Calls before | Why it repeated |
|---|---|---|
| `get_country()` | 3 | one call per party address, same country each time |
| The `AddUsers` row in `getEntityBranches()` | 2 | once per argument pair, and the query decrypts `UserName` in the `WHERE` |
| The entity name in the party loop | 1 per party row | `EntityMain` read inside a `foreach` |
| **The subject contract row** | **2, each pulling `Contract::$with`** | `viewContract` reads it at the top and again 200 lines down |

The contract row is the interesting one. It could not simply be reused, because
`availableContracts()` **writes decrypted names, formatted dates and label text back onto the model it
is given**, so the row cannot be read raw again afterwards. The method now takes an untouched `clone`
before that pass and the later branch reads the clone. `clone` copies the attribute array by value, so
the decrypt pass cannot reach it.

### What is left, and why it is left

`100479?tab=details` now runs **70 queries**. What still repeats:

| n | shape | why it is left |
|---|---|---|
| 8 | `custom_field_data` by group, field and group name | **not repeats** - eight different fields. Folding them into one query is a real change to `dataCustomFields()`, which 48 call sites use |
| 4 | `contract_party_data ... in (100479)` | `Contract::$with` behind three list queries in `relatedContractLists()` that each happen to include this contract. Folding needs those lists restructured |
| 2 | `custom_field where status = ?` | two readers, not yet traced |
| 2 | `contract_type where applicable = ?` | two readers, not yet traced |
| 2 | `contract_party_data where custom_field_group_id = ?` | two readers, not yet traced |
| 2 | the entity name list | two readers, not yet traced |

That is about 10 queries for a day's careful work on an 800-line method, against a page that now spends
under 150 ms in the database. **Stop here.** If a later effort wants them, the way to find them is the
`DB::listen` trace above, not reading.

## Resolution - 2026-08-24

All three items done. `100479?tab=details` went **368 queries to 70** across this ticket and
[ticket 13](13-visible-to-scope.md), and every tab gained: edit 96 to 56, attachment 91 to 46, history
80 to 46.

Report rows 24, 25, 26 and 27. **Every change was checked the same way**: 32 documents - four contracts
across eight tab values - fetched before and after with `git stash`, and every hash equal.

**Three things to remember.**

- **Trace, do not read.** Four tickets had moved this file, so every line number in this ticket was
  wrong. A 20-line `DB::listen` that logs the SQL and four stack frames answered in one page load what
  reading could not answer in an hour.
- **A method that mutates what you pass it stops you reusing the row.** `availableContracts()` decrypts
  and reformats in place. That is why the contract was read twice, and it will block the next person
  the same way. A `clone` taken before the call is the cheap way round it.
- **The dead ones were the cheap ones.** `checkTablesConfiguration()` ran a `SHOW TABLES` over the whole
  schema, twice per request, to check an **empty** list. It was the slowest single query on the page.
