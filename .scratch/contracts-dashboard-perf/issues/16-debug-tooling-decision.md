# Decide what debug tooling we actually add

Type: grilling
Status: resolved
Assignee: kader (2026-08-20)
Blocked by: 14

## Question

[Ticket 14](14-debug-tooling-research.md) has the facts. This ticket makes the call. The dev's
position going in (2026-08-20) was "a debug bar is worth having, because `APP_DEBUG=false` in
production hides it — the same discipline as the `LOG_LEVEL` rule".

The research changes the ground under three parts of that:

1. **The analogy does not hold.** A wrong `LOG_LEVEL` writes noise into a file nobody outside the
   server reads. A wrong `APP_DEBUG` paints query bindings — decrypted contract fields — onto a page
   any visitor can load, plus opens `POST /_debugbar/queries/explain`. And Debugbar has **no
   local-only guard**, unlike this repo's own provider which demands `APP_ENV === 'local'` as well.
2. **The MCP server is real but does not do the wanted thing.** `laravel/boost` is official, but it has
   **no query-log tool** — it can run a query you hand it, not show what a page ran. And only Boost
   **v1.1.5** installs on Laravel 10.48.29, which ships a `Tinker` tool running a bare `eval()` with
   the real `.env` — reach into `goalapp_apollo` included.
3. **Debugbar's default caps are 100/500 queries**, so on a 12,000-query page it stops collecting
   before it reaches the interesting part. The tool that would answer this effort's questions is the
   `DB::listen` recorder that already exists.

Decide, with the dev:

1. **Do we add Debugbar at all**, given the middleware already answers the performance questions and
   the risk is a public page leaking contract fields rather than a noisy log file? If yes, what makes
   it safe here — `DEBUGBAR_ENABLED=false` committed in the production `.env`, a local-only wrapper
   provider like `PerfTimingServiceProvider` uses, raised query caps, or all three?
2. **Boost: no, v1.1.5 with the `eval()` tool, or bump Laravel to 10.49+ first** so Boost v1.8.13
   becomes available? The Laravel 10 line is at **v10.50.3**, so the bump is a patch move, not a major
   upgrade. Note that even then Boost does not give query logs.
3. **What the agent actually needs**, as opposed to what a human needs. The `DB::listen` recorder plus
   the log files have answered every question in this effort so far. Is structured access worth any
   new package at all, or is the honest answer "extend the recorder"?
4. **Whether this is in scope for this effort.** The dashboard spec does not need it. It may belong to
   a separate tooling effort — decide that explicitly rather than letting it ride along.

## Hard prerequisite

**Nothing can be installed until `composer.lock` is repaired.** It has 15 conflict markers and fails
`json_decode`, so every composer command refuses to run. Repairing it exposes a second trap:
`composer.json:18` asks `nwidart/laravel-modules ^9.0` while **10.0.6 is installed**, so a naive
`composer update` downgrades and breaks all five modules. `composer validate` also blocks on a y/n
prompt for `tbachert/spi`, an installed plugin missing from `allow-plugins`.

The map currently has the lock file in **Out of scope**. If the answer to question 1 or 2 is "yes,
install something", that ruling has to be revisited — the lock file becomes a prerequisite, not
adjacent debt.

## Dev input, 2026-08-20 (ticket still open)

Given verbatim: **"Yes debugbar and fix the composer if it needs so, but do not upgrade anything
existing ones for now. You can add new packages if needed but do not upgrade [existing] ones."**

What this settles:

- **Debugbar: yes.** Question 1 is answered in principle. *How* it is made safe here is still open.
- **`composer.lock` comes out of Out of scope and becomes a prerequisite.** The map's ruling said it
  stays out only for as long as we install nothing. We are installing something.
- **Boost: effectively no.** Boost v1.8.13 needs Laravel 10.49+, and upgrading is refused. That leaves
  only v1.1.5 with the bare `eval()` Tinker tool, which nobody argued for. Question 2 closes as "no".

What "do not upgrade existing packages" runs into, and must be resolved before this ticket closes:

1. **`composer.json:18` asks `nwidart/laravel-modules ^9.0` while 10.0.6 is installed.** Any resolve
   wants to *downgrade* it and break all five modules. The fix that obeys the instruction is to correct
   the constraint to match what is actually installed — recording reality, not upgrading — and then
   repair the lock.
2. **`composer require` is not surgical.** Adding a package can bump others to satisfy its
   requirements. Whether Debugbar can go in with genuinely nothing else moving has to be checked with a
   dry run before anything is written.
3. **`composer validate` blocks on a y/n prompt** for `tbachert/spi`, an installed plugin missing from
   `allow-plugins`.

Still open: the local-only guard, the 100/500 query caps (useless on a 12,000-query page as-is), and
whether this belongs on this map at all or is its own tooling effort.

## Answer

**Resolved 2026-08-20.**

### 1. Debugbar: yes, behind three separate locks

The dev's call. What makes it safe here — all three, not one:

- **A local-only wrapper provider.** Debugbar's own gate is `DEBUGBAR_ENABLED`, falling back to
  `APP_DEBUG` when unset — **there is no environment check in it at all**. This repo already has the
  better pattern in [PerfTimingServiceProvider](../../../app/Providers/PerfTimingServiceProvider.php),
  which demands `APP_DEBUG` **and** `trim(APP_ENV) === 'local'`. Debugbar gets the same treatment: add
  it to `extra.laravel.dont-discover` in `composer.json` so auto-discovery does not register it, and
  register it from a small local-only provider instead. This is the lock that holds even if someone
  gets the `.env` wrong.
- **`DEBUGBAR_ENABLED=false` written into the production block of
  [.env.example](../../../.env.example)**, next to the `APP_DEBUG=false` / `LOG_LEVEL=warning` values
  already there. Independent of the provider, and it beats `APP_DEBUG` when set.
- **Request storage off** (`DEBUGBAR_STORAGE_ENABLED=false`). On by default, it writes every request to
  `storage/debugbar/` on disk.

The risk this defends against is specific and worth restating: a wrong `APP_DEBUG` does not make a
noisy log file, it paints **query bindings — decrypted contract fields — onto a page any visitor can
load**, and opens `POST /_debugbar/queries/explain`.

### 2. The query caps stay at their defaults, and Debugbar is not the tool for this effort

`config/debugbar.php` ships `'soft_limit' => 100` and `'hard_limit' => 500`: past 100 queries bindings
and backtraces are dropped, past 500 the query is not collected at all. Raising them was considered and
**rejected** — the caps are what keep Debugbar's own cost small, and a bar that lists 12,000 queries is
not readable anyway. Debugbar is for humans looking at normal pages. **The tool for this effort remains
the `DB::listen` recorder from [ticket 02](02-timing-middleware.md)**, which has answered every
question asked so far, including the ones a debug bar could not have (the 1000-id bug).

So the honest framing: Debugbar is being added because the dev wants it available day to day, not
because this performance work needs it.

### 3. Boost: no

Boost v1.8.13 needs Laravel 10.49+, and the dev has ruled out upgrading anything already installed.
That leaves only **v1.1.5**, which ships a `Tinker` tool running a bare `eval()` with the real `.env` —
`goalapp_apollo` included if a connection to it exists. Nobody argued for that. And Boost has **no
query-log tool**, so it could not do the wanted thing even if it installed.

### 4. What the agent needs: extend the recorder, add no package

Structured access to what a page ran is the one genuinely useful thing, and the `DB::listen` recorder
already provides it in `storage/logs/perf-Y-m-d.log`. Anything more is an extension of that file's
format, not a dependency.

### 5. composer.lock — prerequisite, and worse than recorded

The map had this in **Out of scope**, "for as long as we install nothing". We are installing something,
so it moves to **prerequisite**.

Two facts found while resolving this, one of them new:

- The lock has **15 conflict markers** and fails `json_decode`, so every composer command refuses to run.
- **Neither side of the conflict is what is installed.** The `nwidart/laravel-modules` hunk pits
  **v9.0.6** (HEAD) against **v11.1.4** (theirs), while `vendor/composer/installed.json` says
  **10.0.6** — against a `composer.json` constraint of `^9.0`. So the lock cannot be repaired by
  picking a side; **no side describes reality**. `vendor/composer/installed.json` (161 packages,
  `laravel/framework v10.48.29`) is the only truthful record of this install.

**The plan, in this order, with a stop-gate:**

1. Rebuild `composer.lock` from `vendor/composer/installed.json` so it describes exactly what is on
   disk. Verify with `composer install --dry-run` — it must report nothing to do. If it wants to change
   a single package, stop.
2. Correct `composer.json` where it contradicts the install (`nwidart/laravel-modules` `^9.0` →
   `^10.0`) and add `tbachert/spi` to `allow-plugins` so `composer validate` stops blocking on a y/n
   prompt. This records reality; it is not an upgrade.
3. `composer require barryvdh/laravel-debugbar --dev --no-update`, then a **dry run** with
   `--with-dependencies` to see what would move. **If anything other than Debugbar and its own new
   dependencies moves, stop and report** rather than proceeding.

**Standing instruction from the dev, recorded:** new packages may be added; **nothing already installed
gets upgraded**. If a serious security hole turns up in something already installed, bring it to the
dev — do not bump it.

### 6. Scope: adjacent, ships separately

This does not belong in the dashboard spec and the spec does not depend on it. It ships as its own
small change — lock repair, then Debugbar behind the local-only provider — and the spec simply notes
that the lock file must be valid before any deployment step that runs composer. Not spun out as a
separate effort; it is one change, not a body of work.

