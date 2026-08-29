# 02 — Walk both pages, find and fix breaks

Type: `wayfinder:task` (AFK)
Blocked by: nothing
Assignee: claude (session 2026-08-29)
Status: CLOSED 2026-08-29

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

## Resolution

Fixed in commit `e9a1de4`. Every shape walked in the logged-in Chrome debug profile, driven over
CDP.

**The `chrome-devtools` MCP server wedged** on a stale page handle part-way through and every
call answered "The selected page has been closed". The walk was finished by driving the same
browser over the same protocol from a small script, so this is the real page in the real session
— not a backend substitute. Script kept at
`<scratchpad>/cdp.js`.

### What each shape does

| shape | blade | result |
|---|---|---|
| `contracts/create-v3` | `contractCreateV3` | 200, renders |
| `contracts/create`, AI off | `contractCreate` | 200, renders |
| `contracts/create`, AI on | `contractCreateAi` | 200, renders |
| `contracts/ai/marketing` | — | 200 after redirect to `contracts/list/contract-custom`, which is correct: `custom_contracts_type_id` is not set locally |

The AI flag was turned on in `admin_settings` for the walk and **put back to `false`** afterwards.
Confirmed by reading the row back.

### Breaks fixed

**1. `contractCreate()` threw on its owner-lookup failure path.**
[:6670](../../../Modules/Contract/app/Http/Controllers/ContractController.php:6670) called
`array_merge($fileError, $invalid_owner_error)` and the method never sets `$fileError` — a fatal
on the one path the branch exists to handle. It also redirected to `contracts/create`, which is
the method itself, so a real miss would have looped. It now redirects to `contracts/list` with
the message, matching `contractCreateV3()`, which never had the bug.

**2. All three create blades loaded a dead external script.**
`https://s3-us-west-2.amazonaws.com/s.cdpn.io/25686/jSignature.min.js` answers **403 Forbidden**.
Every load paid a DNS lookup, a TLS handshake and a failed request for it. Nothing in the repo
calls `jSignature` — grep across every `.js` and every blade finds only the `<script>` tags
themselves. Removed from `contractCreate.blade.php`, `contractCreateV3.blade.php` and
`contractCreateAi.blade.php`.

Verified after: `contracts/create-v3` returns 200 with **no failed requests and no console
errors**. Six other blades still carry the same dead tag — `viewDetailContract`,
`viewExDetailContract`, `contractCreateSimple`, `contractCreateCopy`, `contractCreateAiV2`,
`admin_settings/index`. They are other pages, so they are left alone and written down here.

### Written down, not fixed

- `Tagify: input element not found` warning on every create shape. It is a warning, the page
  renders, and it costs nothing measurable. Two-test rule: not this effort's.

### Numbers seen while walking — these feed ticket 03

Seeded set, debug bar off. The AI blade is the outlier and it is the important one:

| shape | queries | TTFB | HTML decoded |
|---|---|---|---|
| `create-v3` | **15,094** | 19.4 s | 8.9 MB |
| `create`, AI off | **10,094** | 13.1 s | 8.0 MB |
| `create`, AI on | **93** | **1.4 s** | 2.3 MB |

**15,000 of `create-v3`'s 15,094 queries are the same statement**: `select * from state where
id = ?`. The perf log names it as one duplicate group of 15,000 executions taking 13.4–22.4 s.
That is `get_state()` called once per party per address list, and the address list renders three
times. `create` with the AI blade off runs 10,000 of them — two address lists instead of three.

**The AI blade already avoids it** — 93 queries, 1.4 s. So the fix in ticket 10 is not a new
design: one of the three blades in this same page family already renders the party section
without the per-row `State` query. Read it before writing anything.

Server time is almost entirely view render, not the controller: on `create-v3` the controller is
258–560 ms and the view render is 18.8–28.9 s.
