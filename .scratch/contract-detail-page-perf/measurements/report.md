# Measurement report — contract detail page

One file for this effort. **No old-number column** — the row above holds the previous number, so
writing it twice adds nothing (the dev's call 2026-08-21, [CLAUDE.md](../../../CLAUDE.md)). Row 0 is
the baseline, so the first real row has something to sit under. Never start a second report file.

**How to fill a row.** Same page, same data, same session. Absolute milliseconds on this machine vary
about 3x between sessions, so a number taken hours after the row above it means nothing. Prefer query
counts; they do not drift.

**Every row records bytes too**, not only time: **document bytes** — what the server rendered;
**total transfer bytes** and **request count** — what the browser actually pulled down. They answer
different questions and must not be reported as if they were one number.

**Take every row with the debug bar OFF.** Set `DEBUGBAR_ENABLED=false` in [.env](../../../.env)
first. On the dashboard it inflated the document from 63,274 bytes to 359,490 — 5.7x — plus two extra
asset requests.

Dataset for every row unless the row says otherwise: the seeded local set — **3,018 contracts**,
6,940 party rows, 13,867 approval rows. Page under test:
`http://apollo.contracts.legality:8888/contracts/contracts/100479?tab=edit`.

## Results

| # | change | TTFB | queries | document bytes | transfer bytes | requests | measure | side effects / remarks |
|---|---|---|---|---|---|---|---|---|
| 0 | baseline | 4,208–4,589 ms warm (4 runs); server `total_ms` 4,137–4,774 over 21 runs | 253 | 326,254 | 326,854 warm / **2,979,504 cold** | 59–60 warm / 62 cold | `PerfTimingMiddleware` per-request record in `storage/logs/perf-2026-08-22.log`, plus the browser Navigation Timing API; debug bar off, opcache on | Warm full load 5,527–5,959 ms; cold full load 8,083 ms. **Almost all of it is one query.** DB time is 3,674–4,282 ms, which is 89–90% of the request. Three queries are 90% of that DB time: the `GROUP_CONCAT` child walk (`ContractController.php:784`) at 2,130–2,616 ms, `$contractsoldothers` (`:723`) at 969–1,045 ms, and the parent walk (`:748`) at 174–179 ms. Bootstrap 131–223 ms, routing 3–11 ms, route middleware 21–38 ms, blade render 31–110 ms over 21 views, send 1 ms. Peak memory 12–14 MB, 831 files included. **The document is not compressed** — `encodedBodySize` equals `decodedBodySize` at 326,254 both ways, on cold and warm runs alike, while 39 of the asset requests are compressed. IIS gzip never engages for the HTML. `SHOW GLOBAL STATUS LIKE 'Questions'` read 259 across the same load, 6 more than the middleware's 253; the extra 6 are the two `mysql` CLI calls that took the readings. Use 253. Times vary about 15% between runs today, not 3x, because one query dominates. Control contract `1?tab=edit`: TTFB 4,367 ms, document 321,453 bytes, **282 queries**. Same page on other tabs: `?tab=history` 61,452 bytes and 240 queries, no `?tab` 105,797 bytes and 243 queries. |
| 1 | ticket 02: seed all 60 columns the page reads | over 120 s, then HTTP 500 | — | 0, IIS error page | — | — | browser, `100479?tab=edit` and `1?tab=edit` | Not a regression the seed caused by being wrong — the seed made a page cost visible. Rows went from ~1.5 KB to 9,390 bytes, `contracts` from roughly buffer-pool size to 110 MB `DATA_LENGTH` against a 16 MB `innodb_buffer_pool_size`. The child-contract `GROUP_CONCAT` query (ContractController.php:780) scans the whole table once per row and now exceeds the IIS FastCGI request timeout. Same 3,018 `(id, parentcontract)` pairs in a two-column temp table: **3 s**. Pre-existing contract 1 fails the same way, so this is not about the seeded rows. |
| 2 | ticket 11 (part): covering index on `contracts(parentcontract, id)` | 4,422 ms | — | 326,554 | — | 58 | browser, `100479?tab=edit`, warm cache, debug bar off | **The page renders again.** From HTTP 500 to 4.4 s. Not the ticket 04 baseline: `ContractController.php` has uncommitted edits from the ticket 14 agent, and this was a warm run, so query count and transfer bytes are not taken here. Two things worth keeping: the document is **326 KB**, about 5x the dashboard's 63 KB, with the debug bar off. And the index took **474 s to build** on 3,018 rows - production needs a window for it, not a quiet deploy. |
| 3 | ticket 14: delete the dead subsequent-contracts query, run the approvals query once | — | 258 | — | — | — | `SHOW GLOBAL STATUS LIKE 'Questions'` around one browser load of `100479?tab=edit`, warm, debug bar off | Row 2 left the query column empty, so this row has nothing to sit under. The same measure on the commit before these two changes read **261**, so the two changes remove **3 queries**: 2 for the dead `$contractsSubseqList` line (the query plus the `contractPartyList` eager load `Contract::$with` adds) and 1 for the duplicate approvals query. The `$contractsoldothers` default in the same ticket adds and removes nothing. Server-wide counter, so another agent's traffic could land inside the window; each reading was taken twice and both agreed. |
| 3 | ticket 14: guard `$contractsoldothers`, delete the dead subseq query, one approvals query instead of two | — | 258 | — | — | — | `SHOW GLOBAL STATUS LIKE 'Questions'` around one warm browser load of `100479?tab=edit`, debug bar off, each reading taken twice | Three commits: `63a1db2`, `d1173aa`, `8858fce`. The dead query cost 2, not 1 - itself plus the `contractPartyList` eager load `Contract::$with` adds to every `Contract` query. The approvals dedupe also removes one full seven-column decrypt pass over every approval row, which the query count does not show. Chart View proved byte-identical: 115,939 bytes rendered before and after on contract 4, which has 21 approval rows. **258, not the 375 ticket 08 counted by reading** - the inventory counted query sites and loop shapes, this counts what the server ran on one contract. |
