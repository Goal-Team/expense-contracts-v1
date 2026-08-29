# 03 — Baseline and attribution

Type: `wayfinder:task` (AFK)
Blocked by: 01, 02
Assignee: claude (session 2026-08-29)
Status: CLOSED 2026-08-29

## Question

Row 0 of [measurements/report.md](../measurements/report.md). Measure both pages after the seed
(ticket 01) and after the breaks are fixed (ticket 02).

Per page — `contracts/create-v3` and `contracts/create` — measure warm, three runs,
`DEBUGBAR_ENABLED=false`, cookies cleared:

- server time to first byte
- query count and database time
- document bytes, raw and gzipped
- full page: request count and total transfer
- first render and last render

Then attribute the server time. Which of the twelve master lists costs what, how long
`getGeoGraphDropdowns()` takes, how long the SQL decrypt passes take, how much is blade render.
Use the perf log the earlier efforts wrote to `storage/logs/`.

Attribute the browser time as well: script parse, script run, layout. `contract.js` is 109 KB and
runs whole — say how much of the last render it owns.

## Resolution

Row 0 is written: [measurements/report.md](../measurements/report.md). Warm, three runs per page,
`DEBUGBAR_ENABLED=false`, seeded set from ticket 01. Driven over CDP in the logged-in debug
profile.

| page | TTFB | queries | doc decoded | requests / transfer | first paint / load |
|---|---|---|---|---|---|
| `create-v3` | 35,468 ms (34,994 / 49,428 / 21,983) | **15,094** | 8,899,081 | 70 / 343,925 | 36,123 / 40,341 ms |
| `create` (AI off) | 13,567 ms (15,025 / 12,350 / 13,327) | **10,094** | 8,011,289 | 69 / 226,203 | 13,972 / 16,118 ms |
| `create` (AI on) | 1,428 ms | **93** | 2,303,630 | 73 / 109,038 | 2,432 / 6,185 ms |

**Milliseconds swing hard on this page and the query count does not.** The swing is the database
executing 15,000 tiny statements, not the code. Read the query count.

### Attribution

Server side, `create-v3`: controller **309–733 ms**, view render **21.3–47.7 s**, database
15.7–40.5 s. The work is in the blade, not the controller.

Duplicate query groups from the perf log:

| query | executions | time |
|---|---|---|
| `select * from state where id = ? limit 1` | **15,000** | **15,480 ms** |
| `select * from GeographicalHierarchy where entityid = ? and parent = ?` | 66 | 73 ms |
| the `ContractUsers` five-column decrypt | 2 | 10 ms |
| `select admin_value from admin_settings` | 2 | 2 ms |

**One query shape is 99.4% of the count and about 98% of the database time.** Ticket 10 owns it.

Two other tickets are now sized, and both are small next to it:

- **Ticket 07** (geo hierarchy N+1) is real but costs 66 queries and 73 ms. It is worth taking,
  but it is not the page's problem.
- **Ticket 06** (duplicate model queries) shows as 2 executions of the `ContractUsers` decrypt,
  10 ms. Also real, also small.

### Browser side

Script 0.85–0.99 s, layout 0.15–0.21 s, style recalc 0.19–0.22 s. First paint lands within about
600 ms of TTFB on every run. **This page is not a browser problem** — `contract.js` at 109 KB
costs under a second against a 35-second server wait. Ticket 09 is therefore low value until the
server side is fixed; leave it open but take it last.

### Note on ticket 08

The dev named the dropdowns as the main problem. The measurement says the dropdown *rows* are not
the cost — the *address list* rendered beside them is. `create` with the AI blade on loads the
same 5,001 parties into 11,217 `<option>` elements in **93 queries and 1.4 s**. So ticket 08
should be re-read after ticket 10 lands: much of what it proposes may already be paid for.
