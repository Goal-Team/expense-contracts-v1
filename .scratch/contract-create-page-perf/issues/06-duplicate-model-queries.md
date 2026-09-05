# 06 — Kill the duplicate model queries

Type: `wayfinder:task` (AFK)
Blocked by: 03, 04
Assignee: claude (session 2026-08-29)
Status: CLOSED 2026-08-29

## Question

Two pairs of models point at the same table, and the create page queries each pair twice:

- `Branch` and `BranchUser` both set `protected $table = 'branch'`
  ([app/Models/BranchUser.php:13](../../../app/Models/BranchUser.php:13)). `$branchs` and
  `$branchsUser` are the identical query, each decrypting **11 columns over 99 rows** in SQL.
- `AddUsers` and `AddUsersSel` both set `protected $table = 'ContractUsers'`. `$users` and
  `$usersSel` are the identical query, each decrypting **5 columns over 1,605 rows** in SQL.

There is a third: `$branch = Branch::select('id', BranchName)` runs a **third** query over the
same table for a subset of the same columns.

Confirm with the ticket 04 trace that the pairs really are the same rows and the same columns.
Then run each once and hand the same result to both view variables. Keep both variable names —
the blades use them, and this ticket does not touch the blades.

Watch for a difference the reading missed: a global scope on one model and not the other, a
different connection, a different ordering. If one exists, say so and fold only what is safe.

Report row, one commit. Query count must fall. Browser-verify every dropdown that reads
`$branchs`, `$branchsUser`, `$branch`, `$users` and `$usersSel` still fills, on both pages.

## Resolution

Commit `c0df256`. **The ticket's premise was wrong on both pairs, and right on the third query.**

### The two pairs are not duplicates. They stay.

Both pairs share a table, which is why they produce near-identical SQL. But each model carries a
**different global scope**, so each returns a different set of rows:

| model | table | global scope | what it means |
|---|---|---|---|
| `Branch` | `branch` | none | every branch |
| `BranchUser` | `branch` | `BranchScope` | only the branches the user's entity can see |
| `AddUsers` | `ContractUsers` | `UserContractScope` | Contracts access scope, Active, entity match |
| `AddUsersSel` | `ContractUsers` | `UserBranchScope` | the above **plus** a `branchhead` filter from `getEntityBranches()` |

Folding either pair would hand one dropdown the other's rows. On the logged-in test user the two
scopes happen to return the same rows, which is exactly why the perf log grouped them as one
duplicate shape — the trace agrees with the SQL text and disagrees with the meaning. Read the
scopes, not the log.

**Cost of leaving them: about 19 ms and two queries.** Correct behaviour is worth more.

### The third `branch` query was dead, and it is gone

`$branch = Branch::select('id', decrypt_data('BranchName','branch'))->get()` ran in both
`contractCreate()` and `contractCreateV3()` and went into the view as `branch`. **No create blade
reads it.** `partyDetailsCreate` and `partyDetailsCreateV3` each mention `$branch` 26 times, and
every one of them sits inside one of six `@foreach ($branchs as $branch)` /
`@foreach ($branchsUser as $branch)` blocks — the loop variable shadows the collection.

Removed the query and dropped `'branch'` from both `compact()` calls.

### Proof

- **94 queries**, down from 95, on both pages.
- The document is **byte-identical**: 8,899,081 on `create-v3`, 8,011,289 on `create` — the same
  numbers as after ticket 10. A blade reading a now-missing variable would have thrown, and a
  missing dropdown would have shrunk the page.
- The **AI blade** was checked too, since `contractCreate()` also renders it: 200, 2,303,523 bytes.
  That is 107 bytes under its row-0 figure, which is the jSignature `<script>` tag removed in
  ticket 02 — not this change.
- Only the two create methods were touched, so no other page can be affected.

### Written down, not fixed

`BranchScope` calls `whereIn('id', $availableBranches)` with a plucked id list
([app/Models/Scopes/BranchScope.php](../../../app/Models/Scopes/BranchScope.php)). That is the
pattern [CLAUDE.md](../../../CLAUDE.md) bans, and on this stack a list of 1,000 or more ids
silently returns zero rows. It is harmless today — this install has 99 branches — and it is not
this page's code, so it is written down rather than changed. A customer with 1,000+ branches would
see every branch dropdown in the app go empty with no error.
