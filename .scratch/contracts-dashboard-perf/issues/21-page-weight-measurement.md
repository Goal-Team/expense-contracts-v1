# Measure the page weight, old against new

Type: `wayfinder:task` (HITL — needs a logged-in browser) · Status: **closed 2026-08-21** · Blocked by: nothing

## Question

Dev's instruction, 2026-08-20: **every measurement row also records the size of the page.** And an
observation that needs explaining rather than arguing with:

> I do not see much down size in the page. It is around 5MB in both old and new. Although the new
> loads faster.

## Why the observation and the report do not disagree

[report.md](../measurements/report.md) row 5 measures **61,064 bytes against 71,294** — a 14.3 % cut.
That row is the **HTML document only**, the thing Change F set out to shrink: 136 `<option>` tags
lifted out of the markup.

The ~5 MB the dev is looking at is the **whole page** — document plus every stylesheet, script, font
and image, 53–54 requests of them (rows 1 and 2c). The 10 KB saved on the document is **0.2 % of
5 MB**, so of course it does not show. Both numbers are correct; they are measuring different things.

This is a gap in the report, not in the change. The report has a byte figure for one row and no byte
figure anywhere else, so there is no way to see that the 5 MB is assets and that this spec never
touched them.

## What to do

1. **Add page weight to [report.md](../measurements/report.md) as a standing column**, alongside
   milliseconds and query counts. Three numbers, because they answer different questions:
   - **document bytes** — what the server rendered. This is what Change F moves.
   - **total transfer bytes** and **request count** — what the browser actually pulled down. This is
     the ~5 MB.
   - **uncompressed vs over-the-wire**, if they differ — worth knowing whether compression is even on.
2. **Backfill the rows already measured.** Rows 0, 2b, 2c and 5 were all taken in a browser session
   and the numbers exist in [measurements/](../measurements/); they were just not written down as
   sizes.
3. **Attribute the 5 MB.** Group the transfer by type — JS, CSS, fonts, images — and name the biggest
   single files. Until that breakdown exists, "the page is 5 MB" cannot be turned into a decision.
4. Decide whether shrinking it belongs on **this** map or past the destination. Likely past: this
   effort's destination is dashboard response time, and asset weight is an
   [asset-pipeline](07-asset-pipeline-decision.md) matter — the same duplicated `hot`/`build`/scss
   trees that spec §10 step 10 leaves for later. Say so explicitly rather than leaving it implied.

## How

DevTools over the debug Chrome profile, same as rows 1 and 2c
([ticket 01](01-attach-chrome-devtools.md)). Needs the dev to log in once —
`--user-data-dir=C:\Users\karun\.claude\chrome-debug-profile`, see
[CLAUDE.md](../../../CLAUDE.md). Old and new measured in the **same session**, as every report row
must be.

Cache disabled for the transfer numbers, and a second warm-cache pass, because a returning user pays
almost none of the 5 MB.

## Answer

Measured 2026-08-21 in one browser session, old `/` and new `/dashboard-summary` alive together, cold
cache and warm. Full numbers and breakdown in [report.md](../measurements/report.md), section
"Page weight", rows 21a-21d.

**The dev's observation was correct and the report was misleading.** The whole page moved by **754 bytes
out of 2.9 MB** — 0.03 %. The document did shrink, 71,644 -> 63,274 bytes, but the option data did not
disappear: it came back as a **7,616-byte** `contracts/option-lists` request. 8,370 out, 7,616 back in.
**Change F is not a weight change at all.** It moves bytes off the document's critical path, and that is
the whole of its value. Row 5's "14.3 % smaller" was always document bytes only and should never have
been read as a whole-page figure.

The four things this ticket asked for:

1. **Page weight is now a standing part of the report** — document bytes, resource bytes, whole page,
   request count, cold and warm.
2. **Backfilled** as far as the archive allows. Rows 0 and 2b were taken in the `public/hot` era with
   **28 of 31 requests failing**, so their transfer totals are the weight of 404s and are marked not
   valid. Document bytes from those runs are sound and are carried forward.
3. **The 2.9 MB is attributed.** CSS 966 KB (33 %), JS 940 KB (32 %), fonts 820 KB (28 %), images 90 KB.
   Five files are 73 % of the page — `tabler-icons.woff2` 702 KB, `core.css` 547 KB, `apexcharts.js`
   486 KB, `tabler-icons.css` 169 KB, `fa-brands-400.woff2` 118 KB. **All of it is stock Vuexy template
   or a chart library. None of it is contracts code.** The figure is **2.9 MB, not 5 MB** — no run in the
   session reached 5 MB; the 5 MB was most likely the network panel preserving its log across more than
   one navigation. It changes nothing: 2.9 MB is still about 46x the document.
4. **Answered "past the destination" — and the dev then overrode it, the same day.** This ticket and the
   report both ruled that shrinking the page was a follow-on effort, on the grounds that the destination
   covered response time only. The dev widened the destination to cover response **size** as well, so the
   work is on this map after all and belongs to [ticket 22](22-reduce-page-size.md). The old ruling stays
   written down in both files as history.

**And a returning user pays almost none of the 2.9 MB.** Warm cache is 71,944 bytes old and 71,190 new;
all 54-55 assets come from cache. It is a first-visit cost.

### The compression finding, and one correction

Not asked for, and the biggest page-weight fact in the effort: **compression barely works.** Probed
directly with the response headers read back.

- **Static gzip is configured but never helps a first visit.** A never-fetched 1,252,656-byte JS file came
  back uncompressed on request 1 and gzipped to **343,147** on requests 2, 3 and 4 — **3.65x**. IIS only
  compresses a file once it counts as frequently hit, and a cold-cache visit asks for each asset exactly
  once, so it qualifies for nothing.
- **The HTML document is not compressed at all**, on any request. Three back-to-back requests, 63,274
  bytes every time. Dynamic compression is a separate setting from static, and it is off. The
  `option-lists` JSON is uncompressed too.
- **Build assets carry no `Cache-Control`**, only an `ETag`, so a returning user pays a 304 round-trip on
  each of 54 files — even though the filenames are already content-hashed.

This independently confirms all three findings already written into
[ticket 22](22-reduce-page-size.md).

**One correction.** An earlier pass of this work read the single gzipped `apexcharts.js` measurement
(126,796 transferred against 485,974 decoded) as a Resource Timing artifact and concluded "compression is
off". That was wrong on both counts — the reading was real gzip, and static compression is on. The direct
probe above settled it. The wrong conclusion is left visible in the report's history rather than quietly
removed.

Status: **resolved.**
