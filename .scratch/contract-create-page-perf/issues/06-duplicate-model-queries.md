# 06 — Kill the duplicate model queries

Type: `wayfinder:task` (AFK)
Blocked by: 03, 04
Assignee: unclaimed
Status: OPEN

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
