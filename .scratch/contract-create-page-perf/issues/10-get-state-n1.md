# 10 — Kill the per-party `get_state()` N+1

Type: `wayfinder:task` (AFK)
Blocked by: 03, 04
Assignee: unclaimed
Status: OPEN

## Question

`partyDetailsCreate.blade.php` prints an address list with **one `<li>` per contract party**
([:244](../../../Modules/Contract/resources/views/contract/partyDetailsCreate.blade.php:244)),
and inside each one it calls `get_state($contractPartie->state)`.

[`get_state()`](../../../app/helpers.php:483) runs **one `State` query per call**:

```php
$state = State::where('id', $id)->first();
```

On the seeded 5,001-party set that is **5,000 queries on a single page load**, on top of
rendering 5,000 hidden `<li>` blocks the user never sees until a party is picked.

Ticket 04 gives the measured count. Then fix it. The `state` table holds **32 rows** — the whole
table fits in one query, so the fix is to load it once and look up in memory. Options, cheapest
first:

1. A request-lifetime cache inside `get_state()` itself. The detail-page effort added the same
   kind of cache for `admin_setting()`, `getEntityBranches()`, `userInfo()` and
   `fileStorageType()` — copy that shape, do not invent a new one.
2. Pass the state list into the blade and look up there.

Option 1 is the better one if it is safe: `get_state()` is a global helper and every other page
that calls it wins too. Grep every caller first, and say in the resolution how many there are.

Watch the interaction with ticket 08. If that ticket moves the party list out of the HTML
altogether, this N+1 goes with it — check which lands first and do not do the work twice.

Report row, one commit. Prove the rendered state names are identical before and after.
