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
| 0 | baseline | — | — | — | — | — | — | Ticket 04 fills this. Page does not render at all until ticket 01 lands, so there is no baseline yet. |
| 1 | ticket 02: seed all 60 columns the page reads | over 120 s, then HTTP 500 | — | 0, IIS error page | — | — | browser, `100479?tab=edit` and `1?tab=edit` | Not a regression the seed caused by being wrong — the seed made a page cost visible. Rows went from ~1.5 KB to 9,390 bytes, `contracts` from roughly buffer-pool size to 110 MB `DATA_LENGTH` against a 16 MB `innodb_buffer_pool_size`. The child-contract `GROUP_CONCAT` query (ContractController.php:780) scans the whole table once per row and now exceeds the IIS FastCGI request timeout. Same 3,018 `(id, parentcontract)` pairs in a two-column temp table: **3 s**. Pre-existing contract 1 fails the same way, so this is not about the seeded rows. |
