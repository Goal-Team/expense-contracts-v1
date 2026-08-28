# 09 — Comma-separated query parameters, no JSON in the URL

Type: `wayfinder:task` (AFK)
Blocked by: 07
Assignee: claude subagent (session 2026-08-28)
Status: CLOSED 2026-08-28

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

Landed 2026-08-28 in commit `6f2c26e`. The URL and the AJAX POST carry comma-separated
ints now: `?status=all&contype=6,7&locations=2`. No JSON anywhere on the page.

The pieces:

- **contractlist.js** — `_setJsonParam` became `_setCommaParam` (`val.join(',')`).
  `_listUrl` and dashboard.js's `listFilterQuery` put the literal comma back after
  `URLSearchParams.toString()` encodes it as `%2C` (a comma is legal unencoded in a query
  string, RFC 3986). The DataTables `ajax.data` function was already reading the params
  with `params.get()`, which decodes — it now sends the comma strings as the POST fields
  unchanged. `_syncExportCookies` splits on comma instead of `JSON.parse`; the `filterSet`
  cookie for the export page still carries arrays, verified.
- **ContractController** — new private `parseIdList($raw): array` replaces every
  `json_decode` of these fields: explode on comma, trim, keep `ctype_digit` tokens > 0,
  cast int. Absent, empty and `'0'` return `[]` (no filter). Used three times in
  `listContractData` (contype, concates, locations) and three times in `listContract`
  (the dropdown preselects — the old `$queryList` closure is gone).
- **dashboard.js** — the tile handoff builds `contype=7&locations=2` with `join(',')`.

Verified in the CDP browser, cookies cleared first:

- Pick type 7 → URL `?status=all&contype=7`, 34 rows. Types 6+7 → `contype=6,7`, 69 rows.
  Location 2 → `locations=2`, 70 rows. All equal the report truth. No brackets, quotes or
  percent-encoding in the address bar.
- Fresh navigation to `?status=all&contype=6,7&locations=2` → same rows (1), both
  dropdowns preselected, tab highlighted, Clear Filters shown.
- Malformed: `contype=abc` → page renders, 2,508 rows (filter ignored);
  `contype=7,,9999,abc` → 34 rows (junk dropped, valid ints kept). Nothing throws.
- Dashboard: type 7 + location 2 picked, "all" tile clicked → lands on
  `?status=all&contype=7&locations=2`, filtered and preselected.
- `?status=all&my=1` → 1 row, the ticket-05 truth.
- Default AJAX: **15 queries** (perf log `db.query_count`), **5,767 bytes** — equal to
  report row 5. No report row needed; no number moved.

Written down, not fixed (dev's two-test rule): `my=0` in a hand-edited URL acts like
`my=1` — `params.get('my')` returns the string `"0"`, which is truthy in JS
(contractlist.js, the `d.userData` line). No page writes `my=0`; costs nothing.
