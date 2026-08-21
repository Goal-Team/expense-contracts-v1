# 19 — The attachment tab takes 2.2 s and the database is not the reason

Type: `wayfinder:task` (AFK)
Blocked by: nothing
Status: OPEN

## Question

Where does the time go on `?tab=attachment`? It is the only tab on this page whose cost is not in the
database, so none of the map's other work will touch it.

## What ticket 18 measured

After the tab guard landed, every tab but Details and `historical` dropped to 300–520 ms. Except this
one:

| contract | tab | server `total_ms` | queries |
|---|---|---|---|
| 100479 | `attachment` | **2,638** | 91 |
| 1 | `attachment` | **2,185** | 93 |
| 100479 | `edit` | 369 | 96 |
| 100479 | `timeline` | 337 | 86 |

**91 queries and 2.6 s.** The edit tab runs more queries in a seventh of the time. So the time is
somewhere else.

## Where to look, in order

1. **The perf log first.** `storage/logs/perf-2026-08-22.log` already carries the phase breakdown per
   request — bootstrap, routing, controller body, blade render. One record for an attachment load says
   whether the time is in the controller or in the view before any guessing starts.
2. **An outbound call or a filesystem walk.** This tab shows contract documents. `fileStorageType()`
   decides local or remote storage, and the dashboard effort found it called five times per request. A
   remote storage check, a `Storage::exists()` per file, or a directory listing over a network path would
   all look exactly like this: slow, and invisible to the query count.
3. **File reads.** Reading a document to get its size or to build a preview costs wall-clock and no
   queries.

## Done when

- The 2.2 s is attributed to a named cause, with a measurement behind it, not a theory.
- If it is a fixable page cost, fix it and put the number in the report.
- If it is an outbound call or a storage round trip, say so plainly and say what it would take to move
  it off the page load — that becomes its own ticket rather than being guessed at here.

## Note

Do not fix this by making the tab load its documents on demand without measuring first. That is the
same shape as [ticket 07](07-page-size-decision.md) and it carries the same risk of a form field
leaving the document. Measure, then decide.
