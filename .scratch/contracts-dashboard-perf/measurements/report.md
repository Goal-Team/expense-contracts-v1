# Measurement report — contracts dashboard

One file for every change. Old number, new number, side by side. Never start a second report file.

**How to fill a row.** Measure the old function, then the new one, on the **same page, same data,
same session** — absolute milliseconds on this machine vary about 3× between sessions, so an old and
a new number measured hours apart mean nothing. Prefer query counts; they do not drift.

Dataset for every row unless the row says otherwise: seeded local set — **3,018 contracts**, 6,940
party rows, 13,867 approval rows ([ticket 04](../issues/04-seed-realistic-dataset.md)).

## Results

| # | change | old function / state | new function / state | old | new | measure | side effects / remarks |
|---|---|---|---|---|---|---|---|
| 0 | baseline (no change) | `dashDetails` | — | 14,437 ms TTFB / 5,654 queries | — | timing middleware | Reference row. N=18 was 2,084–2,573 ms / 164 queries. |
| | | | | | | | |

## Rows still to fill

One row per change in [spec.md](../spec.md) §10, in that order:

1. Change E part 1 — delete `public/hot` + RTL off
2. Change A — `dashboardSummary()` beside `dashDetails()`
3. Actionable-items counter (PHP decrypt, bounded)
4. Migration — indexes on `approval_contracts`
5. Change F — AJAX dropdown endpoints
6. Change E part 2 — committed root `vite.config`
7. Migration — convert `contract_party_data`
8. Plain-column experiment ([ticket 17](../issues/17-plain-columns-experiment.md))

## Notes on the columns

- **old / new** — put the unit in the cell (`ms`, `queries`, `KB`, `rows read`). One measure per row;
  add a second row for the same change if it needs a second measure.
- **measure** — how it was taken: `timing middleware`, `SHOW PROFILES`, `DevTools`, `debug bar`.
- **side effects / remarks** — anything that got worse, any behaviour that changed, any number that
  moved for a reason other than speed. Blank means "checked, nothing".
