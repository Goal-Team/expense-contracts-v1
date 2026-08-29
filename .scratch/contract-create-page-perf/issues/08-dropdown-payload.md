# 08 — Cut the dropdown payload

Type: `wayfinder:task` (HITL)
Blocked by: 03, 04, 06
Assignee: claude (session 2026-08-29)
Status: CLOSED 2026-08-29

## Question

The dev named this the main problem 2026-08-28: **the loading of the dropdown fields.**

Twelve master lists load on every GET and are printed into the HTML. Three of them use
`select('*')` and take every column: `$contractParties`, `$catego`, `$ent`. The rest name their
columns but decrypt many of them in SQL — eleven per branch row, five per user row.

Decide **per list**, using the ticket 03 and 04 numbers, which of three shapes it takes:

1. **Trim the columns.** The list stays in the HTML but carries only what the dropdown shows.
   The cheapest change and the first thing to try on every list.
2. **Move it to a lookup call.** A dropdown that grows organically — contract parties above all —
   becomes a search-as-you-type call instead of a full list in the page. The dev's rule
   2026-08-27: server-side paging **only where it makes sense**, and one reusable abstraction, not
   a copy per endpoint. `App\Support\ServerSideDataTable` exists from the list-page effort — check
   whether it fits before writing anything new. `contracts/create/partylist-v2` already exists as
   a cached party lookup; read it before adding another.
3. **Leave it.** A small stable list — countries, categories, party labels — keeps the whole-list
   pattern. The dev's qualifier, same day.

This is HITL: bring the dev the list-by-list table with its bytes and its time, and the shape
proposed for each, before changing the ones that move to a lookup call. Trimming columns needs no
approval.

Both pages, and every dropdown verified filling in the browser. Report row per change, small
commits.

## Resolution

Fixed in commit `3ee4ed4`. The dev's call, 2026-08-29: **fetch the one address on pick.**

### The measurement changed the ticket

The dev named the dropdowns as the main problem. The page anatomy says otherwise. On
`contracts/create-v3`:

| part | bytes | share |
|---|---|---|
| hidden party address list — 10,032 `<li>` across two lists | **7,569,294** | **85%** |
| inline `<script>` blocks | 880,408 | 10% |
| all 49 `<select>` and their 1,142 options | 252,728 | 3% |
| everything else | ~199,000 | 2% |

**The dropdowns are 253 KB.** They were never the cost. Beside them the page pre-rendered the
full address of **every** party — building number, area, landmark, city, state, pincode, country
— as a hidden `<li>`, twice over. [contract.js:1963](../../../Modules/Contract/resources/assets/js/contract.js:1963)
hid them all and showed the one whose `id` matched the picked party. 7.6 MB shipped so one address
could be shown.

So shape 2 of this ticket was taken, and only for the address list. Shape 1 (trim the dropdown
columns) and the lookup-call rewrite of the party `<select>` were not needed — at 253 KB they buy
nothing worth the risk.

### The change

- New partial `partyAddressItem.blade.php` holds the `<li>` markup once.
- New route `GET contracts/create/party-address?party=<id>` → `contractPartyAddress()`, which
  renders that partial for one party. Unknown or missing id returns 204.
- `partyDetailsCreate.blade.php` and `partyDetailsCreateV3.blade.php` render **only the selected
  party**, so a validation bounce still shows the right address with no JavaScript.
- `contract.js` keeps the show/hide path exactly as it was and only fetches when the picked party
  has no `<li>` in the page yet.

That last point is why the shared file was safe to change. `partyDetails`, `partyDetailsEdit` and
`partyDetailsView` still pre-render the whole list, so their `<li>` is always found and the fetch
never runs. `contractflow.js` has its own copy of the handler and was not touched.

### Numbers

| page | document bytes | transfer | queries | TTFB |
|---|---|---|---|---|
| `create-v3` | **8,899,081 → 1,260,069** | 343,925 → 154,884 | 29 (unchanged) | 883 ms |
| `create` | **8,011,289 → 372,277** | 226,203 → **34,550** | 29 → **27** | 617 ms |

View render on `create` fell from about 1,020 ms to **57–141 ms**.

### Proof

Driven in the browser on both pages. Picking a party fires exactly one
`GET /contracts/create/party-address?party=…` and inserts one `<li>`:

```
Building no : 7   Area name: Sector 7   Landmark : Near Landmark 7
City: Delhi   State: Gujarat   Pincode: 600007   Country: India
```

The inserted `<li>` computes to `display: list-item`. Its only hidden ancestor is
`div.clearfix.External`, the External-party block that the page hides until the user picks that
party type — pre-existing behaviour, not this change.

The endpoint alone returns **200 and 508 bytes** for a real id.

`contracts/1` and `contracts/2` — pages whose party blades still pre-render — return 200 with no
new console errors.

### Written down, not fixed

The inline `<script>` blocks are 880,408 bytes, now **70% of the remaining `create-v3` document**.
Not looked at in this ticket. It is the next byte win if the dev wants one.
