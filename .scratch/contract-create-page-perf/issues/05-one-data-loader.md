# 05 — One data loader for create and create-v3

Type: `wayfinder:task` (AFK)
Blocked by: 03, 04
Assignee: unclaimed
Status: OPEN

## Question

`contractCreateV3()`
([:6706](../../../Modules/Contract/app/Http/Controllers/ContractController.php:6706)) is a
near-exact copy of `contractCreate()`
([:6588](../../../Modules/Contract/app/Http/Controllers/ContractController.php:6588)). The
difference is one extra query (`AnnexureMaster`), a different view name, a different redirect
target on the owner-lookup failure, and the `$fileError` bug that lives only in the older one.

Pull the shared part into one method both call. Two reasons, and only the second is speed:

1. The dev's rule 2026-08-28 — the two pages must show the same thing. One loader makes that true
   by construction instead of by care.
2. Every later ticket in this map — 06, 07, 08 — then has one place to change instead of two.

Follow [CLAUDE.md](../../../CLAUDE.md): change in place, no `x` copies. The shared loader is a new
method beside them; the two entry methods stay because two routes point at them, and each keeps
its own view, its own extra data and its own redirect.

No behaviour may change. Prove it: both pages render the same fields with the same values before
and after. Query count must not rise. Report row, one commit.
