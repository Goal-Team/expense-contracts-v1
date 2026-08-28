# 08 — Cut the dropdown payload

Type: `wayfinder:task` (HITL)
Blocked by: 03, 04, 06
Assignee: unclaimed
Status: OPEN

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
