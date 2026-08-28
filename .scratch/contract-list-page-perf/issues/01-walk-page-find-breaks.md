# 01 — Walk the page on the seeded set, find and fix what breaks

Type: `wayfinder:task` (AFK)
Blocked by: nothing
Assignee: claude (session 2026-08-27)
Status: CLOSED 2026-08-27

## Question

Does `contracts/list` render, and does every request it fires return 200, on the seeded
3,018-contract dataset — across the filter shapes the page can be in?

The detail-page effort found nine breaks this way, five of them on shapes nobody had tried. The
shapes here are cookie- and POST-driven:

- no cookies at all (fresh browser)
- `filterSet` cookie present (it overwrites `$_POST` in `getFilterSetData()`)
- `filterStatus` = each status key, including the `executed_*` and `draft_*` compounds
- `myFilterStatus` set — this is the path that plucks ids and decrypts approvals, and on 3,018
  visible contracts its `whereIn` binds over 1,000 values, so expect an empty (wrong) result,
  not an error ([map](../map.md), charting facts)
- `filterConType` set (GET-side `whereIn` on a cookie value — also check what a malformed cookie
  does, `json_decode` returns null)
- `party_id` on the AJAX call
- `locations` POST value, 0 and a real branch id and `[]`

Fix what throws (breakage is in scope even on a perf task). What returns a wrong result without
throwing gets written down here and stays for its own ticket. Verify in the browser, logged-in
CDP session.

## Resolution

Walked every shape in the logged-in CDP browser on the seeded set, 2026-08-27. The page
renders on every shape after four throw fixes. All 18 `filterStatus` keys, `filterSet`
valid, `myFilterStatus`, `filterConType` valid, `party_id`, and all three `locations`
values return HTTP 200.

**Four throws found and fixed** (commits `9c9b1d5`, `bc1abeb`):

1. Malformed `filterConType` cookie → 500 on the GET. `json_decode` gave null, `whereIn`
   threw `count() on null`. Guarded at
   [ContractController.php:2444](../../../Modules/Contract/app/Http/Controllers/ContractController.php:2444).
2. `contype` POST as a JSON scalar (e.g. `5`) → 500 on the AJAX. Same null/scalar-to-`whereIn`
   path at :2188. Now requires a non-empty array.
3. `concates` POST as a scalar → 500. Same fix at :2191.
4. Missing `status` POST key → 500 (`Undefined array key` at the `setcookie` line, :2199).
   Now guarded. A malformed `filterSet` cookie also 500'd (`foreach` on null in
   `getFilterSetData`, :2543) — guarded with an early return.

**Wrong results found, written down, not fixed** (each stays for its own ticket):

- **All six `executed_*` filters return 0 rows.** :2311 compares
  `$contract->contract_status == 'executed'` but the DB stores `Executed`. The seed has 900
  Executed rows across all six substatuses. Case bug, no throw. → new ticket 06.
- **`myFilterStatus` returns 0 rows on the seeded set.** Confirmed live: the approvals
  `whereIn` binds 2,508 ids, over the 1,000 silent-zero line. "My contracts" is empty. →
  existing ticket 05.
- **Missing `status` behaves like `all`, not like empty.** `availableContracts([])` treats an
  empty array as "no argument" ([Controller.php:174](../../../app/Http/Controllers/Controller.php:174),
  `if (!$contracts)`) and fetches every contract itself. Unreachable from the real JS (it
  always sends `status`); noted only.
- **A malformed `locations` value silently disables the filter** (the `else` at :2296 marks
  every party applicable). No throw; noted.
- `draft_under_revision` = 0 rows is correct for this seed — it has no Draft/Under Revision rows.

**Numbers seen in passing** (ticket 02 measures properly): AJAX with `status=all` returns
2,508 rows, **34.2 MB decoded JSON**, ~5–9 s server time. Default page (`draft`) is 5.5 MB
decoded, 1.1 MB over the wire, ~4–6 s. The full shape matrix (which cookie/POST key is read
where, with line numbers) is preserved in the effort's notes and fed tickets 05–08.
