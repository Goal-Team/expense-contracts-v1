# 10 — Kill the per-party `get_state()` N+1

Type: `wayfinder:task` (AFK)
Blocked by: 03, 04
Assignee: claude (session 2026-08-29)
Status: CLOSED 2026-08-29

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

## Resolution

Fixed in commit `96fde96`. Option 1 — a request-lifetime cache inside the helper — and it was
safe.

`get_state()` keeps its name, its signature and its return values. Its body is now one line:

```php
function get_state($id)
{
    return State::nameFor($id);
}
```

`State::nameFor()` loads the whole 32-row table once per request with
`pluck('name', 'id')` and answers from memory after that. Same shape as
`AdminSettings::$valueCache`, which the detail-page effort added for the same reason —
`State::forgetCachedNames()` matches `AdminSettings::forgetCachedValues()`.

### Numbers

| page | queries | TTFB | database time |
|---|---|---|---|
| `create-v3` | **15,094 → 95** | 35,468 ms → **1,939 ms** | 15.7–40.5 s → **0.26–0.30 s** |
| `create` (AI off) | **10,094 → 95** | 13,567 ms → **1,331 ms** | 8.4–10.6 s → **0.14–0.29 s** |

View render on `create-v3` went 21.3–47.7 s → 1.03–1.22 s. The controller did not move
(344–389 ms), which is the expected shape: the cost was in the blade loop, not the controller.

### Proof nothing changed

1. **Value-for-value.** Ran the old logic and the new one over the same inputs in tinker:
   `0`, `-1`, `1`, `5`, `32`, `33`, `999`, the string `'7'`, and `null`. Identical, including
   the `0` returned for a missing id and the string id that still resolves (`'7'` →
   `Gujarat`). Then over **all 32 real ids** — identical.
2. **Byte-for-byte.** The rendered document is **8,899,081 bytes on `create-v3` and 8,011,289 on
   `create`, exactly as at row 0**. A helper that had started returning `0` would have shrunk the
   page.
3. **The names are real.** `create-v3` renders **10,000** `State: <name>` strings covering every
   state in the table — Andhra Pradesh 314, Assam 314, Karnataka 312, and so on.
4. **No console errors, no failed requests** on either page after the change.

### Every caller checked

Eleven call sites in seven blades, all `get_state($x->state)` inside a loop:

`partyDetails` (×2), `partyDetailsCreate`, `partyDetailsCreateV3` (×2), `partyDetailsEdit` (×2),
`partyDetailsV3` (×2), `partyDetailsView` (×2).

The create-page blades are verified above. The others reach the contract detail/edit page: loaded
contracts 1, 2, 3 and 5 — all **200, titled "Edit/View Contract", no errors**. Those pages emit no
`State:` block at all for these contracts, before or after; the literal is blade markup and this
change cannot remove markup, it only changes the value printed after it.

Nothing outside `get_state()` and the `State` model was touched, so no other page can be affected.

### What this changes for the rest of the map

The page is no longer a database problem. At 95 queries and ~1.3–1.9 s, the remaining tickets are
much smaller than they looked when they were written:

- **Ticket 07** (geo hierarchy) — 66 queries, 73 ms. Still worth taking.
- **Ticket 06** (duplicate model queries) — a handful of queries, ~10 ms.
- **Ticket 08** (dropdown payload) — the document is still **8.9 MB decoded**. That is now the
  biggest number on the page and it is bytes, not queries. Re-read the ticket against these
  numbers before proposing shapes.
- **Ticket 09** (`contract.js`) — under a second of script against a ~1.9 s page. Take it last.
