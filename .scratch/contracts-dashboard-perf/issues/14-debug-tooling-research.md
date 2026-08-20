# What is the safe way to add a debug bar, and is a Laravel MCP server the better fit?

Type: research
Status: resolved
Assignee: kader (2026-08-20)
Blocked by: —

## Question

The dev decided on 2026-08-20 that a debug bar is worth having — useful to humans and to the agent —
reversing the earlier no-vendor-packages rule. The reasoning: `APP_DEBUG=false` in the production
`.env` hides it, the same discipline the `LOG_LEVEL` rule already relies on.

The dev also recalls reading about **an MCP server for Laravel projects** — possibly the better fit,
since the agent's need is not a rendered HTML panel but structured access to queries, logs and
schema. That recollection has not been checked and must not be assumed correct.

This ticket is fact-finding only. It decides nothing; it feeds a later decision.

Find out, against primary sources (official Laravel docs, the packages' own repos and READMEs, not
blog posts):

1. **Is there an official or widely-used Laravel MCP server**, and what does it actually expose —
   query logs, schema, tinker, application logs, browser console? Name it precisely, give its
   version, its maintainer, and whether it is first-party Laravel or community.
2. **How does it authenticate and what does it expose to the network?** An MCP server that can run
   tinker against `apollo_contracts_expense` is a shell. Report exactly what the access model is.
3. **`barryvdh/laravel-debugbar`: what actually gates it.** Confirm from the source, not the README,
   which conditions suppress it — `APP_DEBUG`, `DEBUGBAR_ENABLED`, `app()->environment()`, or a
   combination. State what happens if `APP_DEBUG=true` is left on in production by mistake, since
   `vendor/` is committed in this repo and would carry the package to the server.
4. **What it costs when enabled.** Debugbar collects on every request. Quantify the overhead it adds,
   because this effort is measuring milliseconds and an instrument that changes the measurement is
   worse than none.
5. **Whether either can coexist with the ticket 02 middleware** without double-counting queries
   (both call `DB::listen` / `enableQueryLog`).
6. **PHP and Laravel version fit.** This app is Laravel 11 on the XAMPP PHP in `C:\xampp\php`.
   Confirm the version constraints resolve — noting that `composer.lock` in this repo is
   **conflicted and invalid JSON**, so dependency state cannot be trusted and any install plan must
   deal with that first.

## Constraints

- Findings go to `.scratch/contracts-dashboard-perf/research/debug-tooling.md`.
- Do **not** install anything. This ticket reports; the dev decides.
- Nothing may touch `goalapp_apollo`.

## Answer

Full findings: [research/debug-tooling.md](../research/debug-tooling.md) (492 lines, sources cited as
URLs and `file:line`). Gist below. **Decides nothing** — feeds a decision ticket.

### Two premises in this ticket were wrong

- **This app is Laravel 10.48.29, not 11.** Verified twice:
  `vendor/laravel/framework/.../Application.php:43` is `const VERSION = '10.48.29'` and
  `composer.json:14` asks `^10.0`. This decides question 6 on its own — several packages need 11+ and
  simply cannot be installed here. The map said Laravel 11; corrected.
- **The ticket-02 middleware does not use `enableQueryLog`.** It uses `DB::listen`, and
  [PerfTimingServiceProvider.php:85](../../../app/Providers/PerfTimingServiceProvider.php:85) carries a
  comment saying why — the query log keeps every statement and its bindings for the whole request,
  which at ~12k queries is tens of MB allocated inside the thing being measured. Nothing in `app/`
  calls `enableQueryLog` or `getQueryLog`.

### 1. The MCP server exists — the dev's recollection was right, with a catch

Two **first-party** things, and they are not the same:

- **`laravel/mcp`** — a kit for writing your own server. Ships no query, log, or schema tools.
- **`laravel/boost`** (official Laravel, v2.5.5 current) — the actual MCP server, and what the dev was
  thinking of.

**The catch: Boost has no query-log tool.** It can run a query you hand it; it cannot show you what a
page just ran. That is precisely the thing this effort most wants, so Boost does not replace the
timing middleware.

### 2. Access model — and a real problem with the only installable version

Boost is **stdio only** (`php artisan boost:mcp`) — no port, no token, no network listener. Access is
"whoever can run the process". Its `DatabaseQuery` tool is genuinely read-only (allowlist, plus a
write-keyword blocklist, plus a read-only transaction).

**But only Boost v1.1.5 and below accept Laravel 10.48.29, and v1.1.5 ships `Tinker.php` running a
bare `eval()`** — no sandbox, full `.env`, and therefore reach into `goalapp_apollo` as well. Newer
Boost dropped that tool, and this app cannot install newer Boost as it stands.

For a `laravel/mcp` web server, auth is **middleware-only** (Passport OAuth 2.1, Sanctum, or your
own) — no middleware means no authentication at all.

### 3. What gates Debugbar (read from source, v3.16.5)

`LaravelDebugbar::isEnabled()`: `DEBUGBAR_ENABLED` if set, otherwise `APP_DEBUG`, **and** not running
in console, **and** `APP_ENV !== 'testing'`.

**There is no local-only guard** — unlike this repo's own provider, which additionally demands
`APP_ENV === 'local'`. `register()`/`boot()` are unconditional in 3.x; the gate sits in
`InjectDebugbar::handle()` plus a `DebugbarEnabled` middleware that `abort(404)`s the `_debugbar/*`
routes.

**If `APP_DEBUG=true` is left on in production** (and `vendor/` is committed, so the package would be
on the server): it renders for **every visitor**, with `with_params` — query bindings — on by default,
writes stored requests into `storage/debugbar/`, and opens `POST /_debugbar/queries/explain` and
`DELETE /_debugbar/cache/...`. On a contracts system that is decrypted field values painted onto the
page. `DEBUGBAR_ENABLED=false` is a second, independent lock that beats `APP_DEBUG`.

### 4. What it costs — and why the caps make it useless here

**No primary source publishes a millisecond figure**; the README is qualitative only. Measured on this
machine's PHP 8.3.8 instead: `debug_backtrace(limit 50)` **3.28 µs**, the SQL `preg_replace`
**0.16 µs**. Defaults cap collection at `soft_limit 100` / `hard_limit 500` queries — so the per-query
cost is small, **but those same caps make it useless on a 12,000-query page**.

The uncapped cost is memory: ~19–21 MB baseline on a clean install (upstream issue #1434), plus
`DEBUG_BACKTRACE_PROVIDE_OBJECT` retaining objects — landing inside the very numbers `PerfRecorder`
reports.

### 5. Coexistence — no double counting, either direction

`DB::listen` **is** an event listener (`Connection.php:1063-1066` is literally
`$this->events?->listen(QueryExecuted::class, $callback)`), and Debugbar attaches its own separate
listener to the same event. Each gets its own copy, and `$query->time` is measured by the connection
before any listener runs. Boost does not interact at all.

### 6. Version fit — the lock file blocks everything first

- **Debugbar v3.16.5 fits as-is** (php ^8.1 vs 8.3.8, illuminate ^10, symfony/finder ^6 vs 6.4.24). It
  pulls in `php-debugbar/php-debugbar ^2.2.4`, currently absent from `vendor/`. Debugbar **4.x needs
  Laravel 11+**.
- **Boost: only v1.1.5 and below** accept 10.48.29 — v1.2.0+ require `^10.49.0`.

### Three things worth acting on

1. **`composer.lock` has 15 conflict markers and fails `json_decode`, so no composer command can
   run at all.** Repairing it exposes a second trap: `composer.json:18` asks
   `nwidart/laravel-modules ^9.0` while **10.0.6 is installed**, so a naive `composer update`
   downgrades and breaks all five modules. And `composer validate` cannot finish unattended — it
   blocks on a y/n prompt for `tbachert/spi`, an installed plugin missing from `allow-plugins`.
   **This is now a prerequisite, not adjacent debt** — nothing can be installed until it is fixed.
2. **The Laravel 10 line went to v10.50.3, not 10.48.x.** A patch bump to 10.49+ opens Boost up to
   v1.8.13 — a far cheaper escape from the `eval()` version than moving to Laravel 11.
3. **The `APP_DEBUG` / `LOG_LEVEL` analogy this ticket leans on does not hold.** A wrong `LOG_LEVEL`
   writes noise to a file nobody outside the server reads. A wrong `APP_DEBUG` paints contract-field
   bindings onto a public page. The dev's reasoning for allowing a debug bar rests on that analogy, so
   the decision ticket has to face this.

### Could not determine

Listed at the end of the findings file. Nothing material to the decision was left unverified.
