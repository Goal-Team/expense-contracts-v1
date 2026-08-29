# 07 — Flatten the geo hierarchy N+1

Type: `wayfinder:task` (AFK)
Blocked by: 03, 04
Assignee: claude (session 2026-08-29)
Status: CLOSED 2026-08-29

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

## Resolution

Fixed in commit `3048c5c`. **94 queries → 29 on both create pages.**

### The tree

Seven levels: head office → region → state → zone → district → city → cluster. The table holds
**146 rows across 6 entities** — 6 head offices, 10 regions, 39 states, 26 zones, 59 city/town, 6
country. The create page ran **67 queries** to read it: one root query plus one per node at every
level.

### The change

The walk is unchanged, line for line — every `foreach`, every `tname` indent, every `ticon` write,
the final `array_reverse`. Only the queries are gone. One query loads every descendant row for the
entity and groups it by parent:

```php
$childrenByParent = GeographicalHierarchy::where('entityid', $entityid)->get()->groupBy('parent');
$childrenOf = fn ($parent) => $childrenByParent->get($parent, new EloquentCollection());
```

Each of the six `->where('parent', $parent)->get()` calls became `$childrenOf($parent)`.

The walk was left alone on purpose. It is odd code — `$rowDistrict["ticon"] = 'check'` is written
from inside the city, cluster and zone loops, which mutates a variable belonging to an outer
level. Rewriting the walk would have to reproduce that, and the two-test rule says a wrong result
that costs nothing is not this effort's. Every node still has exactly one parent and is still
visited exactly once, so the shared row objects are mutated in the same order as the freshly
fetched ones were.

### Proof

Captured the old output for **every `entityid` in the table (1–6) and for a null session entity**,
then ran the new code over the same seven cases:

| entity | rows |
|---|---|
| 1 | 4 |
| 2 | 66 |
| 3 | 2 |
| 4 | 33 |
| 5 | 12 |
| 6 | 29 |
| null | 4 |

`identical: YES`, and the md5 of the whole structure is unchanged at
`e45c9ab80e4b14b2325c086aed57374a`. That is the serialised array compared, not the look of a
dropdown.

End to end: both create pages return 200 and the document is **byte-identical** — 8,899,081 on
`create-v3`, 8,011,289 on `create`.

### Every caller checked

Ten call sites in three controllers. The create pages are measured above; the other four pages all
render 200:

| page | title |
|---|---|
| `parties/` | ContractParties |
| `parties/individual` | ContractParties |
| `contract-setup/approval-rules` | ApproverRules-List |
| `contract-setup/party-approval-rules` | PartyApproverRules-List |

### Written down, not fixed

`$responseGeo` is never initialised. If the root query returns no rows, `array_reverse($responseGeo)`
throws on an undefined variable. It cannot happen on any entity in this database — every one has at
least one head office — so under the two-test rule it is recorded, not fixed.
