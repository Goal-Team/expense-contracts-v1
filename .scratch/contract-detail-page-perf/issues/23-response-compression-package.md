# 23 — Use a composer package for response compression, if one fits

Type: research, then task
Blocked by: nothing
Status: CLOSED
Date: 2026-08-22

## Question

The dev asked, verbatim:

> "Make the middleware more generic, but I think there are already packages for it. Use some standard
> package through composer so that we have the vendor files to ship. Always use a package which has an
> MIT or equivalent license that approves commercial usage and shipping (even with credits is fine). If
> not, we have to use our own code for the problem people have solved through years."

So the job is: find out whether a package fits. If one does, use it and drop
[`CompressResponse`](../../../app/Http/Middleware/CompressResponse.php) from
[ticket 17](17-gzip-the-html-document.md). If none does, keep our code and make it generic instead.

## Answer

**No package fits. We keep our own code and made it tunable through config.**

Nothing was installed. `composer.json` and `composer.lock` are **byte-identical** to before this
ticket. Every install was a `--dry-run`.

The licence bar was never the problem. **Every candidate is MIT.** They failed on other things: two
will not run on Laravel 10, one needs a PHP extension this server does not have, and the one that
installs cleanly refuses to compress `text/html` — which is the whole job.

## The candidates

Checked 2026-08-22 against **PHP 8.3.8** and **Laravel 10.48.29** on this machine.

| package | licence | last release | Laravel 10? | does the job? | verdict |
|---|---|---|---|---|---|
| `open-southeners/laravel-response-compression` **3.3.0** | MIT | 2026-04-11 | **no** — needs `illuminate/http ^12 \|\| ^13` | yes | **rejected** — will not install |
| `open-southeners/laravel-response-compression` **2.2.0** | MIT | 2025-02-17 | yes, no constraint at all | **badly** | **rejected** — see below, worse than our code |
| `chr15k/laravel-response-compression` 0.3.0 | MIT | 2026-06-16 | **no** — needs `illuminate/http ^11 \|\| ^12 \|\| ^13` | yes | **rejected** — will not install |
| `erlandmuchasaj/laravel-gzip` 1.3.0 | MIT | 2026-05-08 | yes, `^8`-`^13` | yes | **rejected** — needs `ext-exif`, missing here |
| `renatomarinho/laravel-page-speed` 4.4.4 | MIT | 2026-06-04 | yes | **no** — refuses `text/html` | **rejected** |
| `middlewares/encoder` 2.2.0 | MIT | 2025-03-23 | PSR-15 only, no Laravel | yes, but needs a bridge | **rejected** — bridge costs more than the code it replaces |
| `aurynx/http-compression` 0.2.0 | MIT | 2025-10-11 | no Laravel integration | maybe | **rejected** — needs PHP 8.4, **4 installs**, 0 stars |
| `highperapp/compression` | MIT | `dev-main` only | no | maybe | **rejected** — `dev-main` only, **2 installs**, drags the whole AMPHP async server stack |
| `graham-campbell/*` | — | — | — | — | **no compression package exists.** `htmlmin/htmlmin` is a minifier, not a compressor |

Note on `laravel-page-speed`: the dev's guess was right. Its headline job **is** HTML minification —
`CollapseWhitespace`, `RemoveComments`, `ElideAttributes`, `RemoveQuotes`, `TrimUrls`. Details below.

### Why each one failed, with the proof

**`open-southeners` 3.3.0 and `chr15k` — will not install.** Composer said so:

```
- open-southeners/laravel-response-compression 3.3.0 requires illuminate/http ^12.0 || ^13.0 ...
  but these were not loaded
- chr15k/laravel-response-compression 0.3.0 requires illuminate/http ^11.0|^12.0|^13.0 ...
  but these were not loaded
```

Both are the well-kept, purpose-built packages. Both skipped the Laravel 10 line. `open-southeners`
never supported it — the earliest version that names an `illuminate` constraint asks for `^11`.

**`erlandmuchasaj/laravel-gzip` — needs a PHP extension we do not have.** It is the only package that
supports Laravel 10 *and* is properly maintained. It fails on this:

```
Package erlandmuchasaj/laravel-gzip has requirements incompatible with your PHP version,
PHP extensions and Composer version:
  - erlandmuchasaj/laravel-gzip 1.3.0 requires ext-exif but it is not present.
```

`exif` reads image metadata. It has nothing to do with gzip, and the package requires it anyway —
along with `ext-fileinfo`, `nesbot/carbon` and `illuminate/filesystem`. That is a grab-bag, not a
compressor. It could be forced in with `--ignore-platform-req=ext-exif`, and it was not, for two more
reasons found by reading its source:

- **It sets `Cache-Control: public, max-age=...`** on responses that have no `Cache-Control`. On an
  authenticated contract page that is how a shared cache hands one user's contract to another. Ours
  sets no `Cache-Control` at all, and the app's own `no-cache, private` survives.
- **It skips compression when `app()->isLocal()`**, and `APP_ENV=local` here. The default behaviour on
  this machine would be no compression.

It does get `Vary` right — it appends. Credit where due.

**`renatomarinho/laravel-page-speed` — cannot do the job.** It installs cleanly with zero extra
packages, so it was read closely. Its `ApiResponseCompression` middleware refuses `text/html`:

```php
$isApiResponse = str_contains($contentType, 'application/json')
    || str_contains($contentType, 'application/xml')
    || str_contains($contentType, 'application/vnd.api+json')
    || str_contains($contentType, 'text/json');

if (! $isApiResponse) {
    return false;
}
```

The 326 KB we care about is `text/html; charset=UTF-8`. This package would never touch it. Its other
middlewares **rewrite the markup** — collapse whitespace, strip comments, drop quotes — which is a
different, riskier change nobody asked for. Its namespace is also `VinkiusLabs\LaravelPageSpeed`
while the package name is `renatomarinho/...`, so it has changed hands.

**`open-southeners` 2.2.0 — installs, but is worse than what we have.** This is the only real
temptation: `composer require open-southeners/laravel-response-compression:*` resolves to 2.2.0 and
reports **"1 install, 0 updates, 0 removals"** — nothing else moves, no new dependencies. Then its
source (69 lines) shows why not:

| what it does | why that is a problem here |
|---|---|
| **Sets no `Vary` header at all** | This is the exact bug we fixed in ticket 17's follow-up, but worse — not a wrong `Vary`, no `Vary`. A shared cache can hand a gzipped body to a client that never asked for one |
| **Sets no `Content-Length`** | It replaces the body and leaves the old length behind. Behind IIS/FastCGI a wrong `Content-Length` truncates or hangs the response |
| **Adds `X-Vapor-Base64-Encode: True`** | Unconditional. Meaningless outside AWS Lambda, and leaked on every page |
| **Ignores `Content-Type` entirely** | It will gzip a woff2, a JPEG, a PDF — anything that is not a streamed or binary response. Burns CPU and can make the body bigger |
| **Never checks the result is smaller** | So an incompressible body gets sent larger than it started |
| **Defaults to level 9** | Ticket 17 measured level 9 at 17.2 ms against level 6's 4.6 ms, to save 784 bytes. Wrong end of the curve |
| **Threshold 10,000 bytes** | Tuned for the AWS API Gateway 10 MB limit, not for saving a round trip |

It is also off the maintained line: 2.2.0 is dated 2025-02-17 and 3.0.0 landed two weeks later. It
will never get a fix. It is a **Laravel Vapor** package — the class is
`OpenSoutheners\LaravelVaporResponseCompression\ResponseCompression` — solving the AWS API Gateway
10 MB response limit, not the "IIS will not compress PHP output" problem we have.

**`middlewares/encoder` — good code, wrong shape.** The best-written candidate. It appends `Vary`
correctly, filters on content type, and skips an already-encoded response. But it is **PSR-15**, and
Laravel middleware is not PSR-15. Using it needs a PSR-7 factory plus
`symfony/psr-http-message-bridge` plus a PSR-15 adapter, and every request would convert the whole
326 KB Symfony response into a PSR-7 stream and back. It also sets no `Content-Length`, has no minimum
size check, and — because PSR-7 has no `BinaryFileResponse` — the bridge would read a whole contract
attachment into memory, which is the one thing our version refuses hardest. Three packages and two
body copies to replace 30 lines is a bad trade.

## Composer safety

[Ticket 16](../../contracts-dashboard-perf/issues/16-debug-tooling-decision.md) and
[ticket 14](../../contracts-dashboard-perf/issues/14-debug-tooling-research.md) warn that
`composer.json` and `composer.lock` here have broken the app before. Everything below was honoured:

- `composer.json` and `composer.lock` were copied to the scratchpad first.
- **Every install was `--dry-run`.** `composer update` with no arguments was never run.
- The md5 of both files is the same before and after this ticket. `git status` shows neither as
  modified.
- `nwidart/laravel-modules` is still **10.0.6**, and `composer.json` still asks `^10.0` — that
  mismatch from ticket 16 is already fixed. `php artisan module:list` shows **all five modules
  Enabled**.
- No `config.platform` override exists in `composer.json`, so composer's platform check reads the real
  PHP. That is why the `ext-exif` failure above is real and not a config artefact.

## What was done instead

The dev's second instruction — "make the middleware more generic" — is what shipped.

**Three files, following CLAUDE.md's "add a new one beside it" rule.** That rule also says not to copy
blocks of code to get there, but to pull the shared part out and call it from both. So:

| file | what it is |
|---|---|
| `config/compression.php` | **new.** The tunable values, every one with an env key |
| `app/Support/ResponseCompressor.php` | **new.** The gzip work, pulled out of the old middleware. Takes level, minimum size and content types as arguments |
| `app/Http/Middleware/CompressResponsex.php` | **new.** Reads the config, calls the compressor |
| `app/Http/Middleware/CompressResponse.php` | **kept.** Same class constants as before, now calling the shared compressor instead of holding its own copy of the code |
| `app/Http/Kernel.php` | points at `CompressResponsex`. Swap the one line to compare the two |

The old middleware stays until the new one is proven, which is what the rule is for. Nothing is
duplicated, so the two cannot drift apart.

**Naming:** `CompressResponse` is a good name, so the rule gives the new one an `x` on the end —
`CompressResponsex`. It reads odd for a class. **Rename it whenever you like**; it is referenced in
exactly one place, `app/Http/Kernel.php`.

### What a client can now tune without editing code

| env key | default | what it does |
|---|---|---|
| `RESPONSE_COMPRESSION_ENABLED` | `true` | Master switch. Set `false` if the web server ever starts compressing PHP output itself, so the work is not done twice |
| `RESPONSE_COMPRESSION_LEVEL` | `6` | zlib level. Clamped to 1-9, so a bad value cannot break a page |
| `RESPONSE_COMPRESSION_MIN_BYTES` | `1024` | Bodies smaller than this are left alone |
| `RESPONSE_COMPRESSION_TYPES` | the 7 text types | Comma-separated content types, for a client who can only edit `.env` |

**The defaults are exactly the numbers `CompressResponse` hardcodes.** With no env keys set, behaviour
is unchanged. Proved: `config('compression')` returns
`{"enabled":true,"level":6,"min_bytes":1024,"types":[...the same 7...]}`.

### No brotli, and why

`brotli_compress()` **does not exist** on this PHP:

```
php -r 'var_dump(function_exists("brotli_compress"));'   ->   bool(false)
```

Brotli beats gzip by 15-20%, but it needs the `brotli` PHP extension, which is not on this PHP 8.3.8
build. Adding a brotli branch would be dead code. `config/compression.php` says so, so the next person
does not re-check.

## Proof

### The document, in the browser

`http://apollo.contracts.legality:8888/contracts/contracts/100479?tab=edit`:

```
content-encoding: gzip
content-length: 35434
content-type: text/html; charset=UTF-8
vary: Accept-Encoding
cache-control: no-cache, private     <- the app's own, not touched
```

Navigation Timing on that load: `encodedBodySize` **35,434**, `decodedBodySize` **326,254**. Both match
ticket 17's follow-up exactly.

### Every tab, both contracts

All 13 tab values on `100479` and `1`, plus `?attachment=1` on both and
`?tab=historical&history=999999`. **29 loads. All 200. All gzip. All `Vary: Accept-Encoding`.**

**Every decoded size is an exact match for ticket 17's table** — 239,091 / 105,797 / 80,888 / 326,254 /
72,403 / 61,452 / 239,132 / 61,365 / 85,549 / 67,018 on `100479`, and 261,238 / 100,723 / 109,477 /
321,453 / 89,095 / 62,767 / 261,279 / 57,360 / 81,599 / 63,008 on `1`. Nothing about the page changed.

Encoded sizes move by 1 or 2 bytes against ticket 17 (35,434 against 35,432; 20,273 against 20,274).
That is the CSRF token, which is a different string on every request, shifting the gzip output. Not a
behaviour change.

### The refusals still hold

Each case run against `App\Support\ResponseCompressor` on a 66 KB compressible body:

| case | result |
|---|---|
| html, client asks for gzip | **gzip**, 221 bytes |
| client sends `Accept-Encoding: identity` | plain, 66,000 bytes |
| no `Accept-Encoding` header at all | plain |
| body under `min_bytes` | plain |
| `Content-Type: font/woff2` | plain |
| already carries `Content-Encoding: br` | untouched, still `br`, body unchanged |
| empty 204 | plain |
| empty 304 | plain |
| `StreamedResponse` | plain, class unchanged |
| `BinaryFileResponse` | plain, class unchanged — a contract attachment is never read into memory |
| not a `Response` object at all | passed through unchanged |
| incompressible 60 KB of random bytes | **left plain** — the body is never made bigger |

### `Vary` is still appended, not replaced

The bug from ticket 17's follow-up cannot come back:

| response already carries | result |
|---|---|
| `Vary: Cookie, Accept-Language` | `Cookie, Accept-Language, Accept-Encoding` — **nothing lost** |
| `Vary: accept-encoding` (lower case) | `accept-encoding` — left alone, matching is case-insensitive |
| `Vary: *` | `*` — left alone, the wildcard already covers it |

**None of the rejected packages gets all three of these right.** `open-southeners` 2.2.0 sets no `Vary`
at all.

### Bad config values cannot break a page

`level` is clamped to 1-9 and `min_bytes` to 0 or more. A `ResponseCompressor(99, -5, ...)` still
returns a valid gzip body of the same 221 bytes as level 9. The knobs work: level 1 gives 449 bytes,
level 6 gives 221, `min_bytes: 999999` blocks compression, and a `types` list without `text/html`
leaves an HTML page plain.

### Round trip

`gzdecode()` of the compressed body equals the original body exactly, and `Content-Length` equals
`strlen()` of the body that is actually sent.

### The CPU cost, old against new

Same page, same session, `100479?tab=edit`, Kernel swapped between the two middlewares:

| | `send_terminate_ms` | median | gzip `cost_ms` | document bytes |
|---|---|---|---|---|
| `CompressResponse` (old), 6 fetches | 5.27 - 11.88 | 6.28 | 4.45 - 10.75 | 35,486 |
| **`CompressResponsex` (new), 10 fetches** | 5.27 - 13.70 | 7.46 | 4.52 - 12.31 | **35,486** |

**The output is byte-identical — 35,486 on all 16 fetches.** Both middlewares call the same `gzencode`
in the same shared class, so it could not be otherwise. The two ranges overlap at both ends and the
`cost_ms` ranges overlap too, so the median gap is this machine's noise, not the code. Both sit inside
ticket 17's recorded 6-9 ms band.

**There is no speed gain here and none was wanted.** The gain is that the numbers are now tunable.

### Logs and console

`storage/logs/laravel.log` holds **no error from 13:06 onward**, when this work started. Every
`local.ERROR` in today's file is from 00:36 to 02:06, before it. The only entry from these runs is one
`Contract detail page found no history snapshot` warning, which is the deliberate `history=999999`
probe — the same one ticket 17 recorded.

The browser console holds the same entries as report row 14: the pre-existing 403 on the external
`s3-us-west-2.amazonaws.com` copy of `jSignature.min.js`, and the woff2 preload warnings. Nothing new.

## What the dev may want to change

Nothing is required. Three things are worth their attention.

1. **`CompressResponsex` is an ugly name.** CLAUDE.md's rule produced it. Rename it freely — one
   reference, in `app/Http/Kernel.php`.
2. **`CompressResponse` can be deleted** once the new one has run for a while. That is the rule's
   separate, later step. It costs one file and no runtime.
3. **The dev's instruction was to ship vendor files, and no vendor files shipped.** That is the honest
   outcome, not a shortcut. HTTP response compression is normally the web server's job, so the Laravel
   package ecosystem for it is thin, young and aimed at AWS Lambda rather than IIS. Every package that
   is well kept has moved past Laravel 10; every package that still supports Laravel 10 is either
   missing a needed extension or does not compress HTML. **If the app moves to Laravel 11 or 12, revisit
   this** — `open-southeners/laravel-response-compression` 3.x is MIT, well maintained, zero open
   issues and 222,000 installs, and would then be a fair swap. Our `ResponseCompressor` would need
   its `Vary` and `Content-Length` handling checked against it first.

## One thing to remember

**A package that installs is not a package that works.** `renatomarinho/laravel-page-speed` and
`open-southeners` 2.2.0 both install cleanly on this app with zero dependency movement — the greenest
possible `composer require`. One refuses to compress HTML at all; the other drops `Content-Length` and
`Vary`. Both were caught by **reading the middleware source**, not by the install. Sixty lines of
someone else's code is quicker to read than to debug in production.
