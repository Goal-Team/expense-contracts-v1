# 07 — Flatten the geo hierarchy N+1

Type: `wayfinder:task` (AFK)
Blocked by: 03, 04
Assignee: unclaimed
Status: OPEN

## Question

`getGeoGraphDropdowns()`
([app/Http/Controllers/Controller.php:35](../../../app/Http/Controllers/Controller.php:35))
walks the geographical hierarchy one level at a time and fires a `GeographicalHierarchy` query
**per node at every level** — head office, then region per office, then state per region, and so
on down. Every create page GET pays for it.

Read the whole method first: the ticket 04 trace says how many queries it really runs on the
seeded data and how deep the tree goes.

Then load the tree in one query and build the same nested array in PHP. The shape the view
receives must not change by one key.

**It is a shared helper on the base `Controller`.** Grep every caller before touching it. The
standing rule: change it only if the change is safe for every caller and measured. If any caller
depends on a different shape, say so and stop.

Report row, one commit. Prove the array is identical before and after — compare the serialised
structure, not the look of the dropdown.
