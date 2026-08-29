# 05 — One data loader for create and create-v3

Type: `wayfinder:task` (AFK)
Blocked by: 03, 04
Assignee: claude (session 2026-08-29)
Status: CLOSED 2026-08-29

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

## Resolution

Commit `280046d`. `contractCreateViewData()` now holds everything both pages hand to their view,
and both entry methods call it.

```php
private function contractCreateViewData(): ?array
```

It returns the shared data, or **null** when the session user is not a valid contract owner. Each
entry method keeps its own view name, its own extra data and its own redirect — the one thing the
two pages did differently — because two routes point at them.

`contractCreate()` keeps the view choice (`contractCreate` / `contractCreateAi` /
`contractCreateRep`) and the `ai/marketing` redirect. `contractCreateV3()` adds its one extra
query (`AnnexureMaster`) to the returned array.

Both methods went from about 100 lines each to under 30.

### Proof nothing changed

| shape | result |
|---|---|
| `create-v3` | 200, **1,260,069 bytes**, 49 selects, 1,142 options, **29 queries** |
| `create` (AI off) | 200, **372,277 bytes**, 47 selects, 1,135 options, **27 queries** |
| `create` (AI on) | 200, 2,303,523 bytes |
| `contracts/ai/marketing` | 200 after the correct redirect to the custom contract list |

Every number is identical to the measurement taken immediately before the fold. This ticket is
not a speed change and it did not become one — it is the guarantee that the two pages cannot
drift apart, which is what the dev asked for.
