# 12 — Delete the queries nothing reads, and stop repeating the ones that do

Type: `wayfinder:task` (AFK)
Blocked by: 04
Status: OPEN

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
