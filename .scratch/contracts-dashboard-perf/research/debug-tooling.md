# Debug bar vs Laravel MCP server: what is actually available, and what it costs

Research ticket: `.scratch/contracts-dashboard-perf/issues/14-debug-tooling-research.md`

Nothing was installed. No app file was changed. All `file:line` references are against the live app dir
`D:\Contract-Expense\GOALv4\contracts`.

## Two premises in the ticket are wrong — read this first

**1. This app is not Laravel 11. It is Laravel 10.48.29.**

- `vendor/laravel/framework/src/Illuminate/Foundation/Application.php:43` → `const VERSION = '10.48.29';`
- `composer.json:14` asks for `"laravel/framework": "^10.0"`.

This changes the answer to question 6 completely. Several of the packages below need Laravel 11 as a
minimum and simply cannot be installed here.

**2. The repo's own timing middleware does not use `DB::enableQueryLog()`.** It uses `DB::listen`, and
the code says why in a comment:

- `app/Providers/PerfTimingServiceProvider.php:85-90`
  ```php
  // Query aggregates. DB::listen is used rather than
  // DB::enableQueryLog()/getQueryLog() because the query log retains every
  // statement and its bindings for the whole request; at ~12k queries that
  // is tens of MB of avoidable allocation inside the thing we are
  // measuring. The recorder folds each event into bounded aggregates.
  DB::listen(fn (QueryExecuted $q) => $perf->recordQuery($q));
  ```
- A grep of `app/` for `enableQueryLog|getQueryLog|DB::listen` returns only that line plus two comments
  (`app/Perf/PerfRecorder.php:83`). **Nothing in `app/` calls `enableQueryLog` or `getQueryLog`.**

Other facts about this machine, for the version work later:

| Thing | Version | Source |
| --- | --- | --- |
| PHP (XAMPP) | **8.3.8** | `C:\xampp\php\php.exe -r "echo PHP_VERSION;"` |
| `laravel/framework` | **v10.48.29** | `vendor/composer/installed.json` |
| `symfony/finder` | v6.4.24 | `vendor/composer/installed.json` |
| `laravel/tinker` / `psy/psysh` | v2.10.1 / v0.12.9 | `vendor/composer/installed.json` |
| `barryvdh/*` already in vendor | only `laravel-dompdf` | `ls vendor/barryvdh` |
| `php-debugbar/php-debugbar` | **not installed** | `vendor/php-debugbar` and `vendor/maximebf` both absent |

---

## 1. Is there an official Laravel MCP server?

**The dev's recollection is right, and there are in fact two separate first-party things.** They do
different jobs and it is worth keeping them apart.

### `laravel/mcp` — a toolkit, not a server

- Official, maintained by the Laravel organisation, MIT: <https://github.com/laravel/mcp>
- Docs: <https://laravel.com/docs/12.x/mcp>
- It is a **kit for writing your own MCP server**. It ships no tools that read queries, schema or logs.
  You write your own tool classes, then register them:
  `Mcp::web('/mcp/weather', WeatherServer::class)` or `Mcp::local('weather', WeatherServer::class)`.
- So on its own it answers none of the dev's need. It is the plumbing underneath the next one.

### `laravel/boost` — this is the actual server

- Official, under the Laravel organisation: <https://github.com/laravel/boost>, docs
  <https://laravel.com/docs/13.x/boost>.
- README describes it as a "Laravel-focused MCP server for augmenting your AI powered local
  development experience."
- Newest version **v2.5.5** (`https://repo.packagist.org/p2/laravel/boost.json`).
- Installed as a dev dependency and run over stdio as an artisan command:
  ```
  composer require laravel/boost --dev
  php artisan boost:install
  claude mcp add -s local -t stdio laravel-boost php artisan boost:mcp
  ```
- Tools it exposes (from the docs table, <https://laravel.com/docs/13.x/boost#available-mcp-tools>):

  | Tool | What it does |
  | --- | --- |
  | Application Info | PHP + Laravel versions, DB engine, package list, Eloquent models |
  | Browser Logs | reads logs and errors from the browser |
  | Database Connections | lists connections, shows the default |
  | Database Query | **runs a query against the database** |
  | Database Schema | reads the schema |
  | Get Absolute URL | turns relative paths into real URLs |
  | Last Error | last error from the log files |
  | Read Log Entries | last N log entries |
  | Record Rule | writes a project rule into `.ai/rules` |
  | Search Docs | Laravel's hosted docs API |

- **What it does NOT expose: a query log.** There is no tool that hands over the queries a page just
  ran. It can run a query you give it; it cannot tell you what the dashboard ran. For this effort — which
  is about which queries a page fires and how long they take — **that is the important gap.** The repo's
  own `perf-Y-m-d.log` gives more of what is wanted than Boost does.
- Community alternatives exist but I did not find one that is both maintained and clearly better than the
  first-party pair, so I did not chase them. **Unverified** — I checked the first-party options and
  stopped there, because the first-party ones settle the question.

---

## 2. What is the access model? Treating "can run tinker" as a shell

### Boost: stdio only, no network, no auth — but arbitrary PHP on the older versions

- Boost runs as `php artisan boost:mcp` over **stdio**. It opens no port and listens on no socket. The
  access model is therefore "whoever can run the process": your own user account on this machine, plus
  whatever agent you wired into `.mcp.json`. There is no token, no login, and nothing to authenticate,
  because there is nothing on the network.
- **The version that fits this app has a Tinker tool that runs `eval()` on whatever the agent sends.**
  `https://github.com/laravel/boost/tree/v1.1.5/src/Mcp/Tools` contains `Tinker.php`, and that file runs
  `$result = eval($code);` with a 256MB memory limit and a default 180s timeout and **no other guard**.
  Its own description to the agent is "Execute PHP code in the Laravel application context, like artisan
  tinker."

  So yes — the dev's framing is exactly right. On Boost v1.x this is a shell, running as the web user,
  inside the app, with the real `.env` and the real DB credentials loaded. `apollo_contracts_expense`
  **and `goalapp_apollo`** are both reachable from it if a connection to either is configured, because
  `eval()` does not care what the CLAUDE.md rules say.
- **Newer Boost dropped the Tinker tool.** It is not in the v2.5.5 docs tool table. So the newest Boost
  is much less of a shell than the one this app could install — the opposite of what you would hope.
- The `Database Query` tool is genuinely read-only, and carefully so. From
  `https://raw.githubusercontent.com/laravel/boost/main/src/Mcp/Tools/DatabaseQuery.php`: an allowlist of
  `SELECT, SHOW, EXPLAIN, DESCRIBE, DESC, WITH, VALUES, TABLE`; a `WRITE_KEYWORDS` blocklist of
  `DELETE|UPDATE|DROP|ALTER|TRUNCATE|RENAME|CREATE|MERGE`; stacked statements, `INTO`/`OUTFILE` and
  version-gated MySQL comments all rejected; and it wraps the query in a transaction the DB itself
  enforces as read-only. **Unverified for v1.1.5** — I read the `main` version of this file. Older Boost
  may be looser, and it hardly matters when `Tinker.php` sits next to it with `eval()`.

### `laravel/mcp` web servers: the auth model, if you ever wrote your own

From <https://laravel.com/docs/12.x/mcp#authentication>: "Just like routes, you can authenticate web MCP
servers with middleware." Three documented options:

- **OAuth 2.1 via Laravel Passport** — `Mcp::oauthRoutes()` plus `->middleware('auth:api')`. The docs
  recommend this "when possible" because OAuth 2.1 is what the MCP spec documents and what most clients
  support. Needs Passport installed, an `OAuthenticatable` model and Passport keys.
- **Sanctum** — `->middleware('auth:sanctum')` and an `Authorization: Bearer <token>` header. This app
  already has `laravel/sanctum` (`composer.json:15`).
- **Your own middleware** reading the `Authorization` header by hand.

And plainly: **a web MCP server with no middleware has no authentication at all.** The default is open.
For this effort none of that applies, because Boost is stdio-only — but it is the thing to remember if
anyone ever proposes exposing an MCP server over HTTP from this app.

---

## 3. What actually gates `barryvdh/laravel-debugbar`

The version that fits Laravel 10 is the **3.x** line, so that is the source I read (v3.16.5). Note the
`master` branch is 4.x and is arranged differently — it added a `canBeEnabled()` early return in the
service provider that 3.x does not have.

### The one decision that matters

`https://raw.githubusercontent.com/barryvdh/laravel-debugbar/3.x/src/LaravelDebugbar.php`:

```php
public function isEnabled()
{
    if ($this->enabled === null) {
        $config = $this->app['config'];
        $configEnabled = value($config->get('debugbar.enabled'));

        if ($configEnabled === null) {
            $configEnabled = $config->get('app.debug');
        }

        $this->enabled = $configEnabled &&
            !$this->app->runningInConsole() &&
            !$this->app->environment('testing');
    }

    return $this->enabled;
}
```

In plain words, it shows up when **all** of these hold:

1. `DEBUGBAR_ENABLED` is true, **or** it is unset/null and `APP_DEBUG` is true. `config/debugbar.php` is
   `'enabled' => env('DEBUGBAR_ENABLED')` with the comment "Debugbar is enabled by default, when debug is
   set to true in app.php. You can override the value by setting enable to true or false instead of
   null."
2. not running in the console;
3. `APP_ENV` is not `testing`.

**`app()->environment()` is not otherwise consulted.** There is no "only in local" rule. This is the
important difference from the repo's own instrumentation, which requires `APP_DEBUG` **and**
`APP_ENV === 'local'` (`app/Providers/PerfTimingServiceProvider.php:38-39`). Debugbar has no such second
gate. `APP_DEBUG=true` alone is enough.

### Where that check is applied

- `src/ServiceProvider.php:18-45` `register()` and `:51-63` `boot()` are **unconditional** in 3.x. They
  merge the config, register the singletons, `loadRoutesFrom(__DIR__ . '/debugbar-routes.php')`, register
  the `InjectDebugbar` middleware and register the `debugbar:clear` command — regardless of whether
  debugbar is enabled. The gate is *downstream*, not at boot.
- `src/Middleware/InjectDebugbar.php:50-68`:
  ```php
  public function handle($request, Closure $next)
  {
      if (!$this->debugbar->isEnabled() || $this->inExceptArray($request)) {
          return $next($request);
      }
      ...
  }
  ```
  When disabled, this is a single method call and a pass-through. That part is genuinely free.
- The `_debugbar/*` routes are always registered, but every one of them carries the
  `DebugbarEnabled::class` middleware, which calls `abort(404)` when debugbar is disabled. The routes are
  `GET /open`, `GET /clockwork/{id}`, `GET /telescope/{id}`, `GET /assets/stylesheets`,
  `GET /assets/javascript`, `DELETE /cache/{key}/{tags?}`, `POST /queries/explain`.

### What happens if `APP_DEBUG=true` is left on in production

Since `vendor/` is committed in this repo, the package **will** be on the server. So, honestly:

- **The debug bar renders on every HTML page, for everyone.** Not just the dev. Any visitor.
- It leaks. The README says it outright: "Do not use Debugbar on publicly accessible websites, as it will
  leak information from stored requests (by design)." Default collectors include `db` (every query and,
  with `with_params` defaulting to true, **every binding**), `session` off but `symfony_request` on,
  `auth` off, `models`, `cache`, `mail`. On this app "every binding" means contract field values.
- **Request storage is on by default and writes to disk.** `'storage' => ['enabled' => env('DEBUGBAR_STORAGE_ENABLED', true), 'path' => storage_path('debugbar')]`.
  So past requests pile up in `storage/debugbar/` and stay readable.
- **The `_debugbar/*` routes open up.** `POST /_debugbar/queries/explain` runs `EXPLAIN`.
  `DELETE /_debugbar/cache/{key}/{tags?}` clears cache entries. `GET /_debugbar/open` serves the stored
  requests. All of them stop 404-ing the moment `isEnabled()` flips true.
- **`DEBUGBAR_ENABLED=false` in the production `.env` is a second, independent lock** and it beats
  `APP_DEBUG`, because the null-check means an explicit false wins. That is worth using. Relying on
  `APP_DEBUG=false` alone gives you one switch; setting both gives you two.

The comparison with the existing `LOG_LEVEL` discipline does not fully hold, and this is the point the
dev should weigh. Getting `LOG_LEVEL` wrong writes noise into a log file nobody outside the server can
read. Getting `APP_DEBUG` wrong paints query bindings onto the page for the public. Same shape of switch,
very different cost when it slips.

---

## 4. What it costs when enabled

**There is no published per-request millisecond figure.** The README is qualitative only: "It can also
slow the application down (because it has to gather and render data). So when experiencing slowness, try
disabling some of the collectors." I found no benchmark in the repo, the docs, or the issue tracker. So
below is the mechanism plus what I could measure directly, not a quoted number.

### The per-query cost, measured on this machine

The expensive default is the backtrace. `config/debugbar.php` has
`'backtrace' => env('DEBUGBAR_OPTIONS_DB_BACKTRACE', true)` — **on by default** — and
`src/DataCollector/QueryCollector.php:307-323`:

```php
$stack = debug_backtrace(
    DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT,
    app('config')->get('debugbar.debug_backtrace_limit', 50)
);
```

I timed those exact two operations with the XAMPP PHP, 100,000 iterations each, at a call depth of about
40 frames (script in the scratch dir, nothing in the repo touched):

```
debug_backtrace(IGNORE_ARGS|PROVIDE_OBJECT, 50) at depth ~40:  3.28 us each
preg_replace over one bound SQL string:                        0.16 us each
```

So the raw per-query overhead is roughly **3.5 microseconds**, and that is the floor — it excludes the
array building, the `$request->is()` path checks, and the object retention.

### The limits change the picture for this dashboard

`config/debugbar.php` also has `'soft_limit' => 100` and `'hard_limit' => 500`, and
`QueryCollector::addQuery()` (lines 155-201) applies them:

```php
$this->queryCount++;
if ($this->hardLimit && $this->queryCount > $this->hardLimit) {
    return;
}
$limited = $this->softLimit && $this->queryCount > $this->softLimit;
```

- past 100 queries, bindings are dropped and the backtrace is skipped;
- past 500 queries, the query is **not collected at all**.

The dashboard reportedly runs about **12,000 queries per request**. Under those defaults debugbar would
do full work on 100 of them, cheap work on 400, and nothing on the remaining ~11,500. So the collector's
per-query cost is capped and small — under a millisecond in total for the query side.

**But the same limits make it useless for this job.** A tool that shows you 500 of 12,000 queries and
drops the bindings after the first 100 cannot tell you where the 12,000 come from.

### The costs that are not capped

- **Memory.** This is the recurring complaint upstream, and there are numbers. On a *clean* Laravel 8/9/10
  install showing only the welcome page, debugbar reports 19-21MB
  (<https://github.com/barryvdh/laravel-debugbar/issues/1434>). Other reports:
  <https://github.com/barryvdh/laravel-debugbar/issues/1287> traces memory exhaustion to `preg_replace`
  in `QueryCollector.php`, and <https://github.com/barryvdh/laravel-debugbar/issues/726> and
  <https://github.com/barryvdh/laravel-debugbar/issues/813> are both memory-exhausted reports.
- **The other collectors are uncapped.** `views` (on by default) keeps every rendered view and its data.
  `models`, `cache`, `events`, `gate`, `mail`, `http_client` all default on. On a page composing hundreds
  of views these grow without a limit.
- **Storage write and HTML injection, once per request** — serialising the whole collected payload to
  JSON in `storage/debugbar/` and then rewriting the response body.
- **`DEBUG_BACKTRACE_PROVIDE_OBJECT` holds object references**, so collected frames keep objects alive
  that would otherwise be freed. That inflates the peak memory the perf recorder is trying to measure.

### The honest answer to the ticket's actual worry

The ticket's worry — "an instrument that changes the measurement is worse than none" — is justified, but
not for the reason assumed. The query-collection cost is capped and small. The problems are that
**memory readings become meaningless** (debugbar's own ~20MB baseline plus retained objects sit inside
the number `PerfRecorder` reports), and that **the response body gets rewritten and a payload written to
disk after the timing marks are taken**. Wall-clock per-request overhead is **not quantified by any
primary source, and I did not measure it, because measuring it means installing the package.** Marked
unverified.

---

## 5. Can either coexist with the timing middleware without double counting?

**Yes for both, and there is no double counting of anything.** The ticket's premise (that both call
`enableQueryLog`) is wrong on both sides.

### They subscribe to the same event, separately

`DB::listen` is not a separate mechanism — it *is* an event listener.
`vendor/laravel/framework/src/Illuminate/Database/Connection.php:1063-1066`:

```php
public function listen(Closure $callback)
{
    $this->events?->listen(Events\QueryExecuted::class, $callback);
}
```

And debugbar attaches to the very same event (`src/LaravelDebugbar.php:370-387`):

```php
$events->listen(
    function (\Illuminate\Database\Events\QueryExecuted $query) {
        if (!app(static::class)->shouldCollect('db', true)) {
            return;
        }
        ...
        $this['queries']->addQuery($query);
    }
);
```

So on a request there would be two independent listeners on `QueryExecuted`. Each gets its own copy of
the event and counts on its own. Neither can inflate the other's count, and `$query->time` is measured by
the connection before either listener runs, so neither can inflate the other's timings either.
**Nobody calls `DB::enableQueryLog()`, so there is no shared log to fight over.**

Two real interactions to keep in mind, neither of them double counting:

- **Ordering.** `PerfTimingServiceProvider::boot()` registers its listener during boot
  (`app/Providers/PerfTimingServiceProvider.php:90`), and debugbar registers its own inside
  `LaravelDebugbar::boot()`, called from `InjectDebugbar::handle()` — that is, from inside the middleware
  stack, later. Listeners fire in registration order, so the perf recorder would run first and its
  own overhead would not be counted inside debugbar's numbers, but debugbar's ~3.5us per query **would**
  land inside the wall-clock time the perf recorder reports. On 12k queries that is a few
  milliseconds at most, and it is capped after 500 queries.
- **`PerfTimingMiddleware` is prepended to the global stack** (`PerfTimingServiceProvider.php:82`) while
  `InjectDebugbar` is registered normally, so the perf middleware sits outside debugbar. Debugbar's
  response rewriting therefore happens *inside* the perf timing window, and its storage write happens
  around `terminate()`. Both would show up as unexplained time at the end of a request.

### Boost does not interact at all

Boost is a separate stdio process. It runs no web request and attaches no listener to the app's
`QueryExecuted` event. There is nothing to double count. The only overlap is `Read Log Entries` and
`Last Error`, which read `storage/logs/*` — and those could read `perf-Y-m-d.log` too, which is
mildly useful.

---

## 6. Version fit — and the lock file has to be fixed first

### The lock file blocks everything

`composer.lock` is **342,427 bytes with 15 unresolved merge conflict markers** at lines 7, 9, 11, 3040,
3051, 3062, 3068, 3076, 3087, 3136, 3138, 3140, 3148, 3150, 3152. `json_decode` on it returns
`Syntax error`. Lines 7-11:

```
<<<<<<< HEAD
    "content-hash": "6b5cd507259ac69ad5abede596d04b94",
=======
    "content-hash": "93b7c8395f4687c91bae194eb7473337",
>>>>>>> f2d681924bff5c934b81f4df48094f6b16f68323
```

No `composer require`, `install` or `update` can run until this is sorted. That is the first job in any
install plan, before any version question.

**Two traps once it is sorted.** Both are worse than the lock file itself.

1. **`composer update` would downgrade and break the app.** `composer.json:18` asks for
   `"nwidart/laravel-modules": "^9.0"`, but **10.0.6 is what is installed**. An update would pull
   modules back to 9.x. Every one of the five modules would be resolved against the wrong major.
2. **`composer validate` cannot even complete unattended.** Running it here stops on
   `tbachert/spi contains a Composer plugin which is currently not in your allow-plugins config` and
   waits for a y/n answer. `composer.json:76-80` lists three allowed plugins and `tbachert/spi` is not
   one of them, yet it is installed in `vendor/`. Any scripted composer step needs that decided first.

So the composer state here is not just "a conflicted lock". `composer.json` and `vendor/` disagree about
what is installed, and the lock that would have settled it is unreadable.

### Does each package fit?

**`barryvdh/laravel-debugbar` v3.16.5 — fits as-is.** This is the newest 3.x, and 3.x is the last line
that supports Laravel 10. From `https://raw.githubusercontent.com/barryvdh/laravel-debugbar/v3.16.5/composer.json`:

```json
"require": {
    "php": "^8.1",
    "php-debugbar/php-debugbar": "^2.2.4",
    "illuminate/routing": "^10|^11|^12",
    "illuminate/session": "^10|^11|^12",
    "illuminate/support": "^10|^11|^12",
    "symfony/finder": "^6|^7|^8"
}
```

Checked against this machine: PHP `^8.1` vs 8.3.8 ✓. `illuminate/* ^10` vs 10.48.29 ✓.
`symfony/finder ^6` vs v6.4.24 ✓. One new package would be pulled in:
**`php-debugbar/php-debugbar ^2.2.4` is not currently in `vendor/`**, so `vendor/` grows by two
directories, and both get committed. Auto-discovery registers
`Barryvdh\Debugbar\ServiceProvider` via `extra.laravel.providers`, so no manual registration.

Newer debugbar lines are out: **v4.0.9 needs `illuminate ^11|^12`**, and **v4.4.1 (newest) needs
`illuminate ^11|^12|^13.0` and PHP `^8.2`** (`https://repo.packagist.org/p2/barryvdh/laravel-debugbar.json`).

**`laravel/boost` — does not fit at the current framework patch, and this is the sharp bit.**

| Boost | php | illuminate | laravel/mcp | Works on 10.48.29? |
| --- | --- | --- | --- | --- |
| v2.5.5 (newest) | ^8.2 | `^11.45.3\|^12.41.1\|^13.0` | ^0.7.1\|^0.8.0\|^0.9.0 | **No** — needs Laravel 11+ |
| v1.3.1 – v1.8.13 | ^8.1 | `^10.49.0\|^11.45.3\|…` | varies | **No** — needs 10.49.0+ |
| v1.2.0 – v1.3.0 | ^8.1 | `^10.49.0\|^11.45.3\|^12.28.1` | ^0.2.0 | **No** — needs 10.49.0+ |
| v1.1.5 and below | ^8.1 | `^10.0\|^11.0\|^12.0` | ^0.1.1 | **Yes** |

Source: `https://repo.packagist.org/p2/laravel/boost.json` and
`https://repo.packagist.org/p2/laravel/mcp.json`. `laravel/mcp` v0.1.1 and v0.1.2-beta take
`illuminate ^10.0|^11.0|^12.0`; everything from v0.2 onward needs `^11.45.3` or higher.

The awkward part: **v1.1.5 is the only version that installs here, and v1.1.5 is the one with
`Tinker.php` and its bare `eval()`.** The newer Boost that dropped the Tinker tool is exactly the one
this app cannot have. Choosing Boost today means choosing the `eval()` version.

There is a way out, and it is smaller than it sounds. **The Laravel 10 line did not stop at 10.48.x — it
went to v10.50.3** (<https://packagist.org/packages/laravel/framework>). Bumping `laravel/framework`
from 10.48.29 to 10.49.0 or later is a *patch-level* move inside the same major, and it opens up Boost up
to **v1.8.13** — which is far newer, and would need checking for whether the Tinker tool is still there.
That is a much cheaper path than going to Laravel 11. **Unverified**: I did not check whether v1.8.13
still ships `Tinker.php`, nor what else a 10.48→10.50 bump would move, and given the lock-file state
above no such bump should be attempted before the composer mess is cleaned up.

---

## Summary for the decision

- **Debugbar fits this app today** at v3.16.5, needs no version bumps, and its gate is
  `DEBUGBAR_ENABLED ?? APP_DEBUG`, and not in console, and not `testing`. **No local-only guard** — one
  wrong `APP_DEBUG` and it renders for the public with query bindings in it, on a server that has
  `vendor/` committed. Set `DEBUGBAR_ENABLED=false` in production as a second lock.
- **Debugbar's query cost is capped and small (~3.5us per query, then nothing after 500), but the same
  caps make it near-useless for a 12,000-query page**, and its ~20MB memory baseline plus retained
  objects would corrupt the memory figures `PerfRecorder` reports.
- **Boost is the real official MCP server, but it has no query-log tool** — the one thing this effort
  most wants. And the only version installable at 10.48.29 is v1.1.5, whose Tinker tool is a bare
  `eval()` shell.
- **Neither double counts with the existing middleware.** Both use `QueryExecuted` listeners, nobody uses
  `enableQueryLog`, and timings are measured by the connection before any listener runs.
- **Nothing can be installed at all until `composer.lock` is repaired**, and repairing it exposes a
  second problem: `composer.json` asks for `nwidart/laravel-modules ^9.0` while 10.0.6 is installed, so a
  naive `composer update` downgrades and breaks the modules.

## Open questions / could not determine

- **Debugbar's real wall-clock overhead per request on this app.** No primary source publishes a figure,
  and measuring it means installing the package. My 3.28us/0.16us numbers are for the two hot operations
  in isolation, not the whole thing.
- **Whether Boost v1.8.13 still ships `Tinker.php`.** I confirmed v1.1.5 has it and that the v2.5.5 docs
  no longer list it. I did not find where in between it was removed.
- **`DatabaseQuery.php` read-only guards in v1.1.5 specifically.** I read the `main` version.
- **What the two sides of the `composer.lock` conflict each intend**, and whether the
  `nwidart/laravel-modules` `^9.0` vs 10.0.6 mismatch is the residue of the same bad merge. Same open
  question as in `vite-setup.md`.
- **Community Laravel MCP servers.** I did not survey them. The first-party pair answered the question,
  so I stopped.
- **Whether the existing `.mcp.json` chrome-devtools server already covers Boost's `Browser Logs` tool.**
  Probably, but I did not compare them.
