# Measurement report: contract create page

Effort: [map.md](../map.md). Branch `claude/contract-create-page-perf`.

Rules: one row per change, new numbers only (the row above holds the previous ones). Row 0 is the
baseline from [ticket 03](../issues/03-baseline-attribution.md). Warm, three runs,
`DEBUGBAR_ENABLED=false`, seeded master data from
[ticket 01](../issues/01-seed-master-data.md) (5,001 parties / 71 countries / 50 advisors).
`contracts/create-v3` and `contracts/create` measured separately.

Millisecond numbers on this page swing widely run to run, because the server is executing
15,000 tiny queries and the swing is the database, not the code. **The query count is the number
to read.** It does not drift.

| # | change (commit) | page | TTFB ms | queries | doc bytes decoded | requests / transfer | first paint / load ms | remarks |
|---|---|---|---|---|---|---|---|---|
| 0 | baseline | **create-v3** | 35,468 (34,994 / 49,428 / 21,983) | **15,094** | 8,899,081 (343,925 transfer) | 70 / 343,925 | 36,123 / 40,341 | Server split: controller 309–733 ms, **view render 21.3–47.7 s**, database 15.7–40.5 s. The work is in the blade, not the controller. |
| 0 | baseline | **create** (AI off) | 13,567 (15,025 / 12,350 / 13,327) | **10,094** | 8,011,289 (226,203 transfer) | 69 / 226,203 | 13,972 / 16,118 | Server split: controller 215–521 ms, view render 11.9–14.3 s, database 8.4–10.6 s. |
| 0 | baseline | **create** (AI on) | 1,428 | **93** | 2,303,630 | 73 / 109,038 | 2,432 / 6,185 | `contractCreateAi.blade.php`. One run only — the flag was turned on for the walk and put back. Recorded because it is the proof the N+1 is avoidable: same controller, same data, 93 queries. |
| 1 | fix the two page breaks (e9a1de4) | both | unchanged | unchanged | unchanged | **70 / 343,925** (create-v3), **69 / 226,203** (create) | unchanged | Ticket 02. The dead `jSignature.min.js` CDN request (403 Forbidden) is gone from all three create blades — one DNS lookup, one TLS handshake and one failed request off every load. No failed requests and no console errors remain. `contractCreate()`'s owner-lookup failure path no longer merges an undefined `$fileError` and no longer redirects to itself. Neither change touches the query count. |
| 2 | load the `state` table once per request (96fde96) | **create-v3** | **1,939** (1,858 / 1,769 / 2,189) | **95** | 8,899,081 | 70 / 343,925 | 2,517 / 4,069 | Ticket 10. `get_state()` now calls `State::nameFor()`, which loads the 32-row table once and answers from memory. **15,094 queries → 95.** Database time 15.7–40.5 s → **0.26–0.30 s**. View render 21.3–47.7 s → 1.03–1.22 s; controller unchanged at 344–389 ms. The document is **byte-identical** to row 0 (8,899,081 both), and the page renders 10,000 state names covering every state in the table — so nothing was lost, the queries were. |
| 2 | load the `state` table once per request (96fde96) | **create** (AI off) | **1,331** (1,798 / 1,227 / 968) | **95** | 8,011,289 | 69 / 226,203 | 1,900 / 3,382 | Same change. **10,094 queries → 95.** Database 8.4–10.6 s → 0.14–0.29 s. Document byte-identical to row 0. |
| 3 | delete the dead third `branch` query (c0df256) | both | unchanged | **94** | unchanged | unchanged | unchanged | Ticket 06. `contractCreate()` and `contractCreateV3()` each ran a third pass over the `branch` table and passed it to the view as `$branch`. No create blade reads it — every `$branch` in the party blades is a `foreach` variable bound from `$branchs` or `$branchsUser`. Document byte-identical on both pages, AI blade checked too. The two model pairs (`Branch`/`BranchUser`, `AddUsers`/`AddUsersSel`) were **not** folded: they carry different global scopes and return different rows. |
| 4 | one query for the geo hierarchy (3048c5c) | **create-v3** | **1,506** | **29** | 8,899,081 | 70 / 343,925 | — | Ticket 07. `getGeoGraphDropdowns()` walked a seven-level tree with one query per node — 67 queries for a 146-row table. Now one root query plus one grouped fetch. **94 → 29 queries**, database 126 ms. The walk itself is unchanged line for line; output proven identical for all six entity ids and a null session entity (same md5). Document byte-identical. |
| 4 | one query for the geo hierarchy (3048c5c) | **create** (AI off) | **981** | **29** | 8,011,289 | 69 / 226,203 | — | Same change. Database 223 ms. The four other pages that call the helper still render: `parties/`, `parties/individual`, `contract-setup/approval-rules`, `contract-setup/party-approval-rules`. |
| 5 | fetch one party address on pick (3ee4ed4) | **create-v3** | **883** (816 / 746 / 1,087) | 29 | **1,260,069** | 70 / **154,884** | 1,501 / 3,066 | Ticket 08. The hidden address list was 7,569,294 bytes — **85% of the document** — 10,032 `<li>` for 5,001 parties across two lists. Only the selected party is rendered now; the rest come from `contracts/create/party-address` when a party is picked. Document **8,899,081 → 1,260,069 bytes**, transfer 343,925 → 154,884. View render 1,020 ms → 57–141 ms. Verified in the browser: picking a party inserts the right address block — building, area, landmark, city, state, pincode, country. |
| 5 | fetch one party address on pick (3ee4ed4) | **create** (AI off) | **617** (492 / 813 / 545) | **27** | **372,277** | 69 / **34,550** | 1,415 / 2,896 | Same change. Document **8,011,289 → 372,277 bytes** — a 21× cut. Transfer 226,203 → 34,550. |

## Where the baseline time goes

From the perf log's duplicate groups, `create-v3`, one warm run:

| query | executions | time | ticket |
|---|---|---|---|
| `select * from state where id = ? limit 1` | **15,000** | **15,480 ms** | [10](../issues/10-get-state-n1.md) |
| `select * from GeographicalHierarchy where entityid = ? and parent = ?` | 66 | 73 ms | [07](../issues/07-geo-hierarchy-n1.md) |
| the `ContractUsers` five-column decrypt | 2 | 10 ms | [06](../issues/06-duplicate-model-queries.md) |
| `select admin_value from admin_settings` | 2 | 2 ms | — |
| everything else | 24 | — | — |

**One query shape is 99.4% of the query count and roughly 98% of the database time.**
`get_state()` fires one `State` lookup per party per address list, and `create-v3` renders that
list three times over 5,000 parties. `create` with the AI blade off renders it twice — 10,000.
`create` with the AI blade on renders it not at all — 93 queries total.

Browser cost is small next to the server: script 0.85–0.99 s, layout 0.15–0.21 s, style recalc
0.19–0.22 s. First paint lands within ~600 ms of TTFB on every run. **This page is not a
browser problem.** It is one query in a loop.
