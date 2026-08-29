# 02 — Walk both pages, find and fix breaks

Type: `wayfinder:task` (AFK)
Blocked by: nothing
Assignee: claude (session 2026-08-29)
Status: OPEN

## Question

A page that does not render cannot be measured. Load every shape of the create page in the
browser and fix what throws:

- `contracts/create-v3`.
- `contracts/create` with `enable_ai_feature` **off** — renders `contractCreate.blade.php`.
- `contracts/create` with `enable_ai_feature` **on** — renders `contractCreateAi.blade.php`.
  Turn the flag on in `admin_settings`, walk it, then put it back to `false`.
- `contracts/ai/marketing` — renders `contractCreateRep.blade.php` when
  `custom_contracts_type_id` is set, and redirects when it is not. The setting is absent locally,
  so check the redirect works and do not chase the blade further.

One break is known already: `contractCreate()` merges `$fileError` at
[ContractController.php:6670](../../../Modules/Contract/app/Http/Controllers/ContractController.php:6670)
and never sets it. The owner-lookup failure path throws instead of redirecting. `contractCreateV3()`
does not have the bug — it was fixed in the copy. Fix it in `contractCreate()`.

Fix what throws. Write down what is merely wrong, per the dev's two-test rule. Record the console
errors and failed requests on each page too — they cost browser time even when the page renders.
