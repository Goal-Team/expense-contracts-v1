# 12 — Delete the queries nothing reads, and stop repeating the ones that do

Type: `wayfinder:task` (AFK)
Blocked by: 04
Status: PART DONE 2026-08-22 - items 1 and 3 are finished. Item 2, the nine duplicate query pairs, is
still open and its line numbers need re-basing. See Progress at the end.

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

### Item 2, the nine duplicate pairs - STILL OPEN

**Do not work from the line numbers in this ticket.** They come from ticket 08, and tickets 15, 18, 20,
21 and 13 have all moved that file since. Re-find each pair before touching it.

What the measurement says is left, on `100479?tab=details` - **10 duplicate groups, 30 executions, about
20 ms in total**:

| n | shape | worth |
|---|---|---|
| 8 | `custom_field_data` by group, field and group name | **not a duplicate** - eight different fields |
| 5 | `contract_party_data where custom_field_group_id in (100479)` | real. `Contract::$with` fires this every time the subject contract is loaded, and it is loaded five times |
| 3 | `country where id = ?` | real |
| 2 | `contract_type where applicable = ?` | real |
| 2 | `custom_field where status = ?` | real |
| 2 | `approval_contracts where contract_id = ? and flag = ?` | real |
| 2 | the `AddUsers` list | real |

The five subject-contract loads are the biggest of them and the one worth doing first:
[line 507](../../../Modules/Contract/app/Http/Controllers/ContractController.php:507) and
[line 724](../../../Modules/Contract/app/Http/Controllers/ContractController.php:724) both read it, and
`relatedContractLists()` reads it again.

**Judge it against the numbers before starting.** The whole page now spends 93.6 ms in the database and
these 30 executions are about 20 ms of it. That is worth having, and it is no longer the biggest thing
on the page.
