# 04 — Query inventory

Type: `wayfinder:task` (AFK)
Blocked by: 02
Assignee: unclaimed
Status: OPEN

## Question

Name every query the create page fires, with its caller, before any of them is rewritten.

Cover:

- `contractCreate()` and `contractCreateV3()`, line by line.
- Every query the blades fire — `contractCreate.blade.php`, `contractCreateV3.blade.php`, and the
  four `createCustomField` includes with category ids 1 to 4, plus `partyDetailsCreate` and
  `contractDuration`.
- The global scopes and model boot code that add queries behind each model.
- The AJAX calls the page fires from `contract.js`: `contracts/create/partylist`,
  `contracts/create/parties`, and anything else that runs on load.

For each: the SQL, the row count, the time, and whether any two are the same query. Say which
lists are printed into the HTML and how many bytes each one costs there.

Two duplicate pairs are known already and belong to ticket 06 — confirm them in the trace rather
than by reading: `Branch`/`BranchUser` (both table `branch`) and `AddUsers`/`AddUsersSel` (both
table `ContractUsers`).
