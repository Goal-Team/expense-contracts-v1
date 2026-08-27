# 01 — Walk the page on the seeded set, find and fix what breaks

Type: `wayfinder:task` (AFK)
Blocked by: nothing
Assignee: claude (session 2026-08-27)
Status: OPEN

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

_Open._
