# 07 — Move the list filters off cookies onto request parameters

Type: `wayfinder:task` (AFK)
Blocked by: 03, 08
Assignee: —
Status: OPEN

## Question

Dev rule 2026-08-27: remove unnecessary cookies; prefer query parameters where possible. Not a
performance change — the dev wants it for testing, security, state, and sharing (a URL with the
filters in it can be sent to a colleague; a cookie cannot).

The cookie surface, charted by ticket 01's walk:

| Cookie | Read server-side | Written by |
|---|---|---|
| `filterSet` | `getFilterSetData()` — injects `$_POST` keys | contractlist.js:735-747 |
| `filterStatus` | echoed in the view; also written by the server on every AJAX (:2199) | JS + server |
| `myFilterStatus` | existence only, :2225 | dashboard.js / contractlist.js |
| `filterConType` | GET-side `whereIn`, :2444 | dashboard.js |
| `filterConLoc` | never read server-side | dashboard.js |
| `filterApplied` | never read server-side | contractlist.js |
| `filterByLocationReport` | inside `availableContracts()`, Controller.php:299-307 | reports page |

Decide per cookie: move to a query/POST parameter, keep (cross-page state genuinely needed,
e.g. dashboard → list handoff), or delete (never read). Then land the change. This waits on
ticket 08 because server-side paging rewrites the AJAX request contract anyway — move the
filters in the same rewrite, not twice.

## Resolution

_Open._
