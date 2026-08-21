# Make the page smaller — what do we cut, and in what order?

Type: `wayfinder:grilling` · Status: **resolved 2026-08-21** · Blocked by:
[ticket 21](21-page-weight-measurement.md) · **Last in order — after
[ticket 17](17-plain-columns-experiment.md).**

## Question

The dev asked for this on 2026-08-21: the page is now **in scope**, and shrinking it is the
last item on this map.

This **redraws the destination**. [report.md](../measurements/report.md) "Where shrinking the 5 MB
belongs" said the opposite — "past this destination, no change on this map was ever going to move the
5 MB, and none should be added to try." That ruling stands as history and is now overridden by the
dev. Note it in the report rather than deleting it.

Decide **which cuts get made, in what order, and which are refused** — with a measured byte figure
against each. Not "make it smaller"; a numbered list someone can implement.

## What the research already found

Static attribution, done 2026-08-21 by resolving the dashboard's `@vite` entrypoints against
[public/build/manifest.json](../../../public/build/manifest.json) and reading the built CSS. No
browser needed for any of it, so it holds regardless of what ticket 21 measures.

**The dashboard pulls 30 built files, 1.81 MB uncompressed, before a single font or image.**

| file | KB | what it is |
|---|---|---|
| `core-7_a25xA8.css` | 534 | Vuexy core, mostly inline `data:image/svg+xml` icons |
| `apexcharts-ZYWXGMLC.js` | 475 | the milestones chart |
| `tabler-icons-XUw5yIc1.css` | 165 | icon font CSS, ~4,900 glyph rules |
| `fontawesome-BLLrrJAk.css` | 104 | **second** icon font |
| `jquery` + `bootstrap` + `select2` + `typeahead` | 281 | |
| `flag-icons-2QKiQDtI.css` | 33 | ~500 flag SVG rules, 6 flags used |
| `template-customizer` js+css | 37 | theme switcher widget |
| the other 20 files | ~180 | |

On top of that, per `@font-face`: `tabler-icons` woff2 **686 KB**, `fa-solid-900` woff2 153 KB,
`fa-brands-400` woff2, `fa-regular-400` woff2, and `OnTrackLogo.png` 45 KB.

### Four things worth deciding, cheapest first

**1. Compression is on, but only from the second request.** Measured against the live server:

```
hit 1  Content-Length: 485974   (no Content-Encoding)
hit 2  Content-Encoding: gzip   Content-Length: 126496
```

That is IIS `frequentHitThreshold` — the default is 2 hits in 10 seconds before a file enters the
compression cache. So a cold cache serves **full uncompressed bytes**, which is a large part of why
the dev sees ~5 MB. Apexcharts alone is 486 KB -> 126 KB, a **74 %** cut, for a config change and no
code. Across the 1.81 MB of CSS+JS this is roughly **1.3 MB**.

**2. The HTML document is not compressed at all.** IIS dynamic compression is off for PHP responses —
`dashboard-run0.json` records `encodedBytes: 71668` against `rawHtmlBytes: 70254`, encoded *larger*
than raw, and a live PHP response comes back with no `Content-Encoding`. The 61 KB document should be
~10 KB. This is also the honest frame for Change F: gzip would have saved more of that 10 KB of
`<option>` tags than moving them did.

**3. Static assets carry no `Cache-Control` and no `Expires`** — `ETag` and `Last-Modified` only. So a
returning user pays a 304 round-trip on each of 30 files instead of nothing. The filenames are
content-hashed (`core-7_a25xA8.css`), so `max-age=31536000, immutable` is safe by construction. This
is the whole warm-cache story and it moves no bytes on a cold load.

**4. Two icon fonts and a flag sprite, for a handful of glyphs.** Tabler is used 163 times and is
staying. FontAwesome is used for **12 classes** (`fa-file-lines`, `fa-spinner`, `fab fa-google`, …)
and costs 104 KB of CSS plus three font families. `flag-icons` is 33 KB of CSS listing ~500 flags to
serve the 6 in the language switcher. Whether either earns its place is a product question, not a
performance one — which is why this is a grilling ticket and not a task.

### Questions for the dev

1. **Compression config is IIS, not Laravel.** `applicationHost.config` is outside `contracts/`, which
   this map's standing scope rules out. Does the fix land here as a documented server change, or is it
   handed to whoever owns IIS? Same question for the cache headers — `web.config` in the app root
   *is* ours.
2. **Is the milestones chart worth 475 KB?** ApexCharts is the single biggest JS file and it draws one
   chart. Keep, lazy-load on scroll, or swap for something smaller?
3. **Does the theme customizer ship to users?** `hasCustomizer => true` at
   [config/custom.php:15](../../../config/custom.php:15). 37 KB, and it also means the blade resolves
   8 stylesheet paths (core, core-dark, and three themes light+dark) so the dark CSS is built and kept.
4. **FontAwesome: drop it, or keep it?** 12 glyphs. Dropping means finding 12 Tabler equivalents and
   editing the blades.
5. **`flag-icons` for 6 flags** — subset it, inline 6 SVGs, or leave it?
6. **Is a font subset acceptable?** Tabler woff2 is 686 KB for maybe 40 distinct glyphs on this page.
   Subsetting is a real win and a real maintenance burden: add an icon later and it is invisible until
   someone rebuilds the subset. Probably a no; ask.

## Also fix while here

`contracts/assets/fonts/font-main.css` **404s**, confirmed again 2026-08-21 — and the 404 is a 6,603
byte Laravel error page, so the broken asset costs bytes as well as time.
[Ticket 07](07-asset-pipeline-decision.md) item 4 already flagged it and nothing was applied.

## Constraints

- **Nothing is applied in this ticket.** It decides; the spec carries it. Same as every other ticket
  on this map.
- **Every cut gets a row in [report.md](../measurements/report.md)** with the three page-weight
  figures — document bytes, total transfer, request count — cold and warm, old and new in the same
  session. That column exists because of [ticket 21](21-page-weight-measurement.md).
- ~~**Blocked by [ticket 21](21-page-weight-measurement.md)**~~ — **unblocked 2026-08-21**, ticket 21
  closed the same day. It confirmed all three findings above independently and **corrected the total to
  2.9 MB, not 5 MB** (no run reached 5 MB; that was most likely the network panel keeping its log across
  more than one navigation). Attribution: CSS 966 KB, JS 940 KB, fonts 820 KB, images 90 KB.
- The asset pipeline is still unbuildable — no root `vite.config`, per
  [ticket 07](07-asset-pipeline-decision.md). Any cut that needs a rebuild (font subset, dropping
  FontAwesome, trimming flag-icons) **cannot be done until that config exists**. Say so in the
  ordering rather than discovering it during implementation.

## Answer

Decided with the dev 2026-08-21. **Six calls, nothing applied.** The page is **2.9 MB** (ticket 21's
corrected figure, not 5 MB) and none of it is contracts code — it is stock Vuexy template plus a chart
library.

Ordered cheapest-first. The first item is most of the win and touches no application code at all.

### 1. Config, not code — worth ~1.4 MB of the 2.9 MB

**The dev's call: write the files even where the change is above `contracts/`.** `contracts/` is served
under the parent **GOAL** application, so the map has no access to the IIS level where the biggest single
fix lives. Written anyway, each rule labelled with whether a folder-level `web.config` can set it or
whether it needs the parent. The dev applies what the environment allows — some IIS setups lock these
sections and a folder cannot override them.

Two files, in [config-proposals/](../config-proposals/):

- **[web.config.proposed](../config-proposals/web.config.proposed)** — the current `web.config` plus
  `<staticContent><clientCache>` (`Cache-Control: max-age=31536000, immutable`, safe because every
  `build/` filename is content-hashed) and `<urlCompression doDynamicCompression="true">` (the document
  and the `option-lists` JSON are uncompressed on every request). Carries a note on which block to delete
  first if it throws HTTP 500.19.
- **[applicationHost.config.notes.md](../config-proposals/applicationHost.config.notes.md)** — the one
  change a folder cannot make: `frequentHitThreshold="1"`. Static gzip is configured and **never helps a
  first visit**, because IIS only compresses a file once it is "frequently hit" and a cold visit asks for
  each asset exactly once. Measured: 1,252,656 bytes raw on request 1, **343,147 gzipped** from request 2
  on — 3.65x, on nothing. The attribute is `AppHostOnly`, so there is no in-app equivalent. Includes the
  `appcmd` one-liner, and a check that Dynamic Content Compression is even installed.

### 2. Theme customizer off

`hasCustomizer => false` at [config/custom.php:15](../../../config/custom.php:15). Saves 37 KB of JS+CSS,
and the blade stops resolving 8 stylesheet paths (core, core-dark, and three themes light+dark). **Users
lose light/dark and theme switching, and a saved localStorage choice stops applying** — the dev accepted
that. No rebuild needed.

### 3. ApexCharts stays, but lazy-loads

**Changed mid-discussion.** First answered "keep as is", then "swap for Chart.js", finally **lazy-load** —
that is the call. The chart stays ApexCharts and looks identical; the 486 KB stops blocking first paint and
loads when the chart scrolls into view.

The push that moved it: ticket 21 found gzip never helps a first visit, so "keep as is" meant paying the
full **486 KB uncompressed**, not the 126 KB gzipped figure. Third heaviest file on the page.

Chart.js was on the table (199 KB, already in the manifest at
`resources/assets/vendor/libs/chartjs/chartjs.js`) and was rejected — redrawing and re-styling the chart is
real work and needs a build. Lazy-loading needs **no rebuild**, which is why it wins.

### 4. FontAwesome stays; fa-brands and fa-regular go

**Also changed mid-discussion** — first "keep it", then "drop fa-brands and fa-regular" once the 118 KB
figure landed. `fa-brands-400.woff2` is in ticket 21's top five heaviest files, 4 % of the whole page.
`fa-solid` and the CSS stay.

**Checked before deciding, and it is cheaper than it looked.** `far fa-` and `fab fa-` appear in exactly
**one** blade — [icons-font-awesome.blade.php](../../../resources/views/content/icons/icons-font-awesome.blade.php),
the stock Vuexy icon-gallery demo, routed at [routes/web.php:320](../../../routes/web.php:320). Not real
app code.

**One real exception:** `fab fa-google` at
[verticalMenu.blade.php:138](../../../resources/views/layouts/sections/menu/verticalMenu.blade.php:138) is a
live menu icon and needs a Tabler replacement. So the whole cost of this cut is one menu icon plus a demo
page nobody should be routing. Needs a rebuild.

### 5. Language switcher removed

Not "subset the flags" — **the switcher goes**. Removes 33 KB of `flag-icons` CSS listing ~500 flags to
serve the 6 in use (`us in fr cn br au`), and the flag SVG requests with it. A product change the dev
chose deliberately over the two cheaper options. Needs a rebuild.

### 6. Font subsetting — deferred, not refused

Tabler's woff2 is **702 KB**, the single heaviest file on the page, for maybe 40 glyphs. Deferred rather
than decided, and the reason is item 7: **the decision is not yet real**, because without a build config
nobody could produce a subset or rebuild it when an icon is added.

### 7. The gate on items 4, 5 and 6

**There is no committed root `vite.config`** — [ticket 07](07-asset-pipeline-decision.md). Any cut that
needs a rebuild cannot happen until that exists. So the running order is:

| # | cut | rebuild? | worth |
|---|---|---|---|
| 1 | IIS + `web.config` | no | **~1.4 MB** |
| 2 | customizer off | no | 37 KB |
| 3 | ApexCharts lazy-load | no | 486 KB off first paint |
| — | *root `vite.config` must land here* | — | ticket 07 |
| 4 | drop fa-brands + fa-regular | yes | 118 KB + fa-regular |
| 5 | remove language switcher | yes | 33 KB + flag SVGs |
| 6 | Tabler font subset | yes | up to ~680 KB, if ever |

Items 1-3 are ~1.9 MB of the 2.9 MB and need no build at all.

### Also carried forward

`contracts/assets/fonts/font-main.css` still **404s** — and the 404 body is a 6,603-byte Laravel error
page, so it costs bytes as well as a request. Flagged by [ticket 07](07-asset-pipeline-decision.md) item 4
and still not applied.

### Nothing here was applied

Two config files written for review. `hasCustomizer` is still `true`, the chart still loads eagerly, both
extra FontAwesome families still ship, the switcher is still there.

Status: **resolved.**
