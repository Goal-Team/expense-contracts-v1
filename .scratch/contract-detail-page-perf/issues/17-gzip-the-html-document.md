# 17 — Compress the HTML document

Type: `wayfinder:task` (AFK, then the dev applies it)
Blocked by: nothing
Status: CLOSED

## Question

Nothing to decide. The page's HTML is sent uncompressed and it is 326 KB. Compress it.

## What the baseline measured

`encodedBodySize` equals `decodedBodySize` at **326,254 bytes** on every run, cold and warm. So the
document is never gzipped. Meanwhile **39 of the asset requests are** compressed on a cold run. IIS
gzip engages for static files and skips the HTML response.

326 KB of markup, most of it repeated table and form structure, compresses to well under 40 KB. That
is the single biggest byte win available on this page, and it is a config line, not page code.

The dashboard effort found the same thing and has the config work already drafted:
[../contracts-dashboard-perf/config-proposals/web.config.proposed](../contracts-dashboard-perf/config-proposals/web.config.proposed)
and
[applicationHost.config.notes.md](../contracts-dashboard-perf/config-proposals/applicationHost.config.notes.md).
**Read those first.** If the dashboard effort already decided the change and it was never applied, this
ticket applies it rather than deciding it again.

## Why it is not simply "turn gzip on"

Two things the dashboard effort found, and both apply here:

- **Dynamic compression is a different switch from static compression.** HTML from PHP is dynamic
  content. `httpCompression` needs the `dynamicTypes` entry for the PHP handler's MIME type, and
  `urlCompression` needs `doDynamicCompression="true"`.
- **IIS gzip only engages from the second request for a static file.** That is `frequentHitThreshold`,
  and it applies to static compression. Check whether it also delays dynamic compression before
  reporting a number, or the first measurement will look like a failure.

## Done when

- The document is compressed. Prove it with `encodedBodySize` well below `decodedBodySize` on the real
  page in the browser, and with the `Content-Encoding` response header.
- Cold and warm transfer bytes both recorded in the report. Cold is currently **2,979,504 bytes**; the
  document is 11% of that, so say what the whole-page number becomes, not only the document one.
- **Say what CPU it costs.** Compressing 326 KB on every page view is not free, and the page is already
  spending 4 s. Measure TTFB before and after — if compression adds to the wait, that is the trade and
  the dev should see it.
- If the change is in `applicationHost.config` rather than the site's `web.config`, **the dev applies
  it** — that file is outside the repo and needs an admin. Write the exact change and hand it over.

## Note

The real fix for 326 KB is sending less of it, and that is [ticket 07](07-page-size-decision.md), tabs
on demand. This ticket makes what we do send cheap. Do both; they do not overlap.

## Resolution

Status: **CLOSED**. Report row 15. Commit `00d6219`.

### The short version

The document is compressed. `100479?tab=edit` goes from **326,254 bytes to 35,432**, 9.2x, for
**6-9 ms of CPU**. Cold whole page goes 2,979,504 to **2,688,682**. Warm whole page goes 326,854 to
**35,732**.

**Nothing is left for the dev to apply.** The change is one new middleware and one line in
`Kernel.php`, both in the repo, both committed. `web.config` was not touched.

### The thing the ticket got wrong: this is not a config change

The ticket says "config rather than page code". It is not. **IIS dynamic compression is not installed
on this server**, so no line in any config file can switch it on.

Read out of the live server, not from a note:

```
C:\WINDOWS\System32\inetsrv\compdyn.dll                     -> DOES NOT EXIST
C:\WINDOWS\System32\inetsrv\compstat.dll                    -> exists (static compression)
applicationHost.config <httpCompression> <dynamicTypes>     -> absent
applicationHost.config gzip scheme dynamicCompressionLevel  -> absent
applicationHost.config any "DynamicCompression" string      -> 0 matches
applicationHost.config <urlCompression />                   -> empty, so doDynamicCompression
                                                               keeps its default of true
applicationHost.config <serverRuntime frequentHitThreshold="1" />  -> APPLIED and live
```

`compdyn.dll` is the Dynamic Content Compression module's own file. It is not on the disk. So the
`httpCompression/dynamicTypes` entry and `urlCompression doDynamicCompression="true"` that this
ticket asks for would both be **inert** - valid attributes with no module behind them.

This is exactly what the dashboard effort found and wrote down in
[applicationHost.config.notes.md](../../contracts-dashboard-perf/config-proposals/applicationHost.config.notes.md).
Re-checked against the live server today, and it still holds.

`web.config.bak.20260821` in the repo root is the pre-dashboard-effort file, 1,430 bytes against the
live file's 8,315. It holds no compression block of any kind, so it says the same thing: compression
was never configured for this application at the folder level. It was not touched.

### The two warnings the ticket carried, both answered

**`frequentHitThreshold` does not delay dynamic compression here.** The dashboard effort's fix,
`serverRuntime frequentHitThreshold="1"`, is applied and live. But the question does not arise: there
is no dynamic compression on this server to delay. The compression now happens inside PHP, which has
no hit counter. The **first** request for the document comes back gzipped. No phantom to chase.

**Static and dynamic compression really are two different switches**, and the reason the 39 assets
compress while the document does not is that only the static module exists.

### What was done instead

`app/Http/Middleware/CompressResponse.php`, registered **first** in the global stack in
`app/Http/Kernel.php`. The Laravel pipeline sends the request down the list and the response back up
it, so the first entry is the last to touch the response - which is what compressing a finished
document needs. `PerfTimingServiceProvider` prepends its own middleware, so the perf log still counts
the compression inside the request.

It uses `gzencode` at **level 6**, measured as the knee of the curve on the real document:

| level | bytes | ratio | best of 5 |
|---|---|---|---|
| 1 | 42,790 | 7.6x | 1.99 ms |
| 4 | 37,741 | 8.6x | 3.34 ms |
| 5 | 36,658 | 8.9x | 3.72 ms |
| **6** | **35,432** | **9.2x** | **4.58 ms** |
| 9 | 34,648 | 9.4x | 17.16 ms |

Level 9 pays 12 ms more for 784 bytes.

It sets `Content-Encoding: gzip`, the corrected `Content-Length`, and **`Vary: Accept-Encoding`** so no
proxy can hand a gzipped body to a client that did not ask for one.

**It refuses to touch, on purpose:** `StreamedResponse` and `BinaryFileResponse`, so a contract
attachment download is never pulled into memory; any response that already carries a
`Content-Encoding`; empty responses (204, 304); bodies under 1 KB, where the gzip header costs more
than it saves; content types that are already compressed, so the 820 KB of woff2 fonts are left alone;
and clients that do not send `gzip` in `Accept-Encoding`.

### Header and byte proof, both together

Response headers on `100479?tab=edit`:

```
content-encoding: gzip
content-length: 35432
content-type: text/html; charset=UTF-8
vary: Accept-Encoding
```

Navigation Timing on the same load: `encodedBodySize` **35,432**, `decodedBodySize` **326,254**.

Before, on the same page: no `content-encoding` at all, `content-length: 326254`, and
`encodedBodySize` equal to `decodedBodySize` at 326,254. The browser was already sending
`accept-encoding: gzip, deflate` and being ignored.

### Cold and warm, whole page

| | document | whole page | requests |
|---|---|---|---|
| before, cold | 326,254 | 2,979,504 | 62 |
| **after, cold** | **35,432** | **2,688,682** | 62 |
| before, warm | 326,254 | 326,854 | 62 |
| **after, warm** | **35,432** | **35,732** | 62 |

Cold saves 290,822 bytes, **9.8%** of the page. Warm saves 291,122 bytes, **89%** of the page -
warm is the bigger relative win, because every asset comes from cache and the document *is* the page.

### The CPU cost

**6-9 ms on the edit tab.** The perf record isolates it cleanly, because compressing a finished
response lands in one phase:

| | `send_terminate_ms`, edit tab, 6 fetches |
|---|---|
| middleware off | 0.70 - 0.87 ms |
| middleware on | 7.31 - 9.38 ms |

The `Log::debug` line agrees from the other side. Over **65** compressed responses: median 3.29 ms,
median 6.35 ms on an edit-size document, 8.36 ms worst, **234 ms of CPU in total to save 9,715,902
bytes**.

**TTFB moved by more than the compression costs, and the ranges overlap**, so the 6-9 ms above is the
number to trust rather than the medians below. Six fetches each, same session, middleware switched off
and back on:

| tab | off | on |
|---|---|---|
| `edit` | 360-469 ms, median 404 | 374-481 ms, median 435 |
| `details` | 760-847 ms, median 787 | 760-946 ms, median 869 |

Navigation Timing on real page loads after the change: 471 ms cold, 571 ms warm.

### Every tab loaded

All 13 tab values on `100479` and on `1` - **26 loads** - plus `?attachment=1` on both and
`?tab=historical&history=999999`. **All 200. All compressed.**

| tab | 100479 decoded | 100479 encoded | ratio | 1 decoded | 1 encoded | ratio |
|---|---|---|---|---|---|---|
| `details` | 239,091 | 22,010 | 10.9x | 261,238 | 21,969 | 11.9x |
| `pre-approval` | 105,797 | 20,274 | 5.2x | 100,723 | 15,128 | 6.7x |
| `timeline` | 105,797 | 20,274 | 5.2x | 100,723 | 15,128 | 6.7x |
| `timelineedit` | 80,888 | 15,062 | 5.4x | 109,477 | 16,224 | 6.7x |
| `edit` | 326,254 | 35,432 | 9.2x | 321,453 | 34,712 | 9.3x |
| `flow` | 72,403 | 13,403 | 5.4x | 89,095 | 13,538 | 6.6x |
| `history` | 61,452 | 12,373 | 5.0x | 62,767 | 12,452 | 5.0x |
| `historical` | 239,132 | 22,020 | 10.9x | 261,279 | 21,978 | 11.9x |
| `attachment` | 61,365 | 12,411 | 4.9x | 57,360 | 11,943 | 4.8x |
| `obligation` | 85,549 | 14,954 | 5.7x | 81,599 | 14,502 | 5.6x |
| `e-stamp` | 67,018 | 13,357 | 5.0x | 63,008 | 12,961 | 4.9x |
| `zzz` | 239,091 | 22,010 | 10.9x | 261,238 | 21,969 | 11.9x |
| none | 105,797 | 20,274 | 5.2x | 100,723 | 15,128 | 6.7x |

**The decoded sizes were proved unchanged the strict way, not against an older record.** The same 26
loads were taken with `CompressResponse` commented out of `Kernel.php` and then back in. On the off
run, `encodedBodySize` equals `decodedBodySize` on all 26. On the on run, every decoded size matches
the off run **exactly - delta 0 on all 26**. The decoded edit-tab body is also byte-identical to the
pre-change body once the CSRF token is normalised.

Ticket 03's table reads a uniform 15 bytes smaller on 24 rows and 23 smaller on the two `edit` rows.
That drift is **pre-existing and not this change's**: the baseline taken at the start of this ticket,
before any code was written, already read 326,254 on `100479?tab=edit` against ticket 03's 326,231.
The off/on comparison above is why the drift does not matter.

`laravel.log` holds **no error** across the 55 loads, and one `Contract detail page found no history
snapshot` warning, which is the deliberate `history=999999` probe. The browser console holds the same
entries as report row 14, including the pre-existing 403 on the external
`s3-us-west-2.amazonaws.com` copy of `jSignature.min.js`.

### What the dev may want to change

Nothing is required. Two things are worth their attention.

**1. This does the thing the dev ruled out on 2026-08-21, by a different route.** Their words, recorded
in the dashboard effort's notes: no dynamic compression, it is a pain to maintain, and they will bear
the cost of the uncompressed document. That ruling was about **IIS** dynamic compression, which needs
an admin, a role feature install and a machine-wide config file that governs every site on the box.
This is one middleware in the repo with no server change and no admin. It is on one branch and
`git revert 00d6219` undoes it whole. **If the dev holds to the ruling, revert that one commit** - it
touches nothing else.

**2. The IIS route is still available and is better if they want it.** Compression in the web server
costs PHP nothing and covers static files in the same engine. It needs an administrator.

Check whether the feature is present. **Elevated PowerShell**, bare literal path, no variables and no
call operator, per the dashboard effort's rule:

```
C:\WINDOWS\system32\inetsrv\appcmd.exe list config -section:system.webServer/httpCompression
```

If the output has no `<dynamicTypes>` list and the `<scheme name="gzip">` line carries no
`dynamicCompressionLevel`, the feature is **not installed** - which is what it reads today. Then, in
the same elevated shell:

```
DISM /Online /Enable-Feature /FeatureName:IIS-HttpCompressionDynamic /All
```

That installs the role feature and puts `compdyn.dll` on the disk. After it, `text/html` and
`application/json` need adding to `dynamicTypes`, and that edit is machine-wide. **If the dev takes
that route, remove `CompressResponse` from `Kernel.php` at the same time**, or the work is done twice.
IIS skips a response that already carries a `Content-Encoding`, so the likely outcome is no harm and
wasted effort - but there is no reason to run both.

### Left alone, per "Staying on a performance task"

**An unknown query parameter on the edit tab costs 10 extra queries.**
`100479?tab=edit` runs **86** queries. `100479?tab=edit&_n=<anything>` runs **96**, and the count is
the same with compression on and off, so it is not this change. Both numbers come out of
`storage/logs/perf-2026-08-22.log` on 2026-08-22 between 02:42 and 02:44. This is worth a look -
ticket 03 already found that `?tab=<anything unknown>` silently serves the Details body, so this page
reacts to URL shapes in ways nobody has mapped. Not this ticket's, and written down instead of chased.

### One thing to remember

**`compdyn.dll` on disk is the fastest way to answer "is dynamic compression installed".** It needs no
elevation and no `appcmd`. `Test-Path C:\WINDOWS\System32\inetsrv\compdyn.dll` returns `False` here and
`compstat.dll` returns `True`, which names both halves of the puzzle in one line: static compression
exists, dynamic does not. `applicationHost.config` itself is **readable without elevation** on this
machine - only writing it needs an admin - so every fact in this Resolution was read from the live
server rather than trusted from a note.

## Follow-up 2026-08-22 — `Vary` is added, not replaced

The dev asked whether this middleware has the cache-invalidation problem they distrust in IIS dynamic
compression. **It does not, and reading the code is the proof: it stores nothing.** No cache read, no
cache write, no key, no file. It gzips the body of the response it was just handed and returns it. There
is nothing to invalidate because nothing is kept.

The features are worth keeping apart, because three different IIS things get called "dynamic":

| feature | keeps a copy? | invalidation risk |
|---|---|---|
| IIS **output caching** | yes, whole responses | real - this is the one that serves a stale page |
| IIS **static** compression | yes, compressed files on disk | real, but IIS keys them on file changes |
| IIS **dynamic** compression | no | none |
| this middleware | no | none |

**But the question found a real bug in exactly that area.** The middleware did
`$response->headers->set('Vary', 'Accept-Encoding')`, and `set()` **replaces**. Any response already
carrying a `Vary` — `Cookie`, `Accept-Language` — would have lost it, and dropping one of those is how a
shared cache hands one user's page to another. Nothing in this app sets `Vary` today (`grep` over `app/`,
`Modules/` and `config/` finds no other writer), so nothing was broken in practice. It was one added
header away from being a serious bug.

`addVaryAcceptEncoding()` now reads what is there, returns early if `Accept-Encoding` or `*` is already
covered, and appends otherwise. Case-insensitive, because header field names are.

Verified in the browser on `100479?tab=edit`: `vary: Accept-Encoding`, `content-encoding: gzip`,
`content-length: 35434` against a decoded 326,254. Four warm fetches: 767, 586, 505, 469 ms.
