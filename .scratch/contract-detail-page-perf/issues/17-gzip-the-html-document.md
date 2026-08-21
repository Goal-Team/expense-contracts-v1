# 17 — Compress the HTML document

Type: `wayfinder:task` (AFK, then the dev applies it)
Blocked by: nothing
Status: OPEN

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
