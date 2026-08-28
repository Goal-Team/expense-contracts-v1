# 09 — Comma-separated query parameters, no JSON in the URL

Type: `wayfinder:task` (AFK)
Blocked by: 07
Assignee: —
Status: OPEN

## Question

Dev call 2026-08-28: `?contype=["1"]` is ugly and non-standard. The URL format becomes
comma-separated ints: `?status=executed&contype=1,2&concates=3&locations=2&my=1`. Commas are
legal unencoded in a query string (RFC 3986) and browsers pass them through.

Change, in one move (the JSON shape is two days old, on this branch only, nothing tolerates it):

1. contractlist.js writes commas into the URL and sends the same comma strings as the POST
   fields (`contype`, `concates`, `locations`).
2. `listContractData` splits on comma and casts to int instead of `json_decode`, one place per
   field.
3. dashboard.js handoff links use the same format.
4. Multiselect preselects on the GET read the comma form.

Verify in the browser: set filters, URL shows commas, copy URL into a fresh tab → same state;
dashboard tile → list still filters; no query-count or bytes regression vs report row 5.

## Resolution

_Open._
