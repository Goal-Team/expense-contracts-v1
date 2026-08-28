# CLAUDE.md

## Git workflow

**Never create git worktrees.** Work on branches in the primary working directory
(`D:\Contract-Expense\GOALv4\contracts`) only. If isolation is needed, create a branch and
check it out in place — do not use `git worktree add`, and do not pass `isolation: "worktree"`
to subagents or workflows.

Rationale: this repo ships `vendor/`, `node_modules/`, and a `.env` whose
`APP_ENCRYPTION_KEY` is derived from the serving hostname; a worktree copy is neither cheap
nor functional here.

**One branch per page for the optimisation work.** Set by the dev 2026-08-21. Each page gets its own
branch off `main`. Commit as soon as a change works, in small commits — not one big commit at the end.
Pages are done one at a time, never in parallel.

## Request and state rules

**Remove unnecessary cookies. Prefer query parameters.** Set by the dev 2026-08-27. Not a
performance rule — it is for testing, security, state, and sharing. A filter that lives in the
URL can be sent to a colleague; a cookie cannot. A cookie earns its place only when state must
cross pages.

**Prefer server-side pagination for heavy AJAX calls.** Set by the dev 2026-08-27. Use a
standard, well-known Laravel pattern, and one reusable abstraction for paginated queries with
filters and search — not the same code pasted per endpoint. Qualifier from the dev, same day:
**only convert the calls where it makes sense.** The test is business growth — a table that
gains rows organically gets paginated; a small stable list (the dropdown AJAX calls of the
earlier efforts) keeps the whole-list pattern.

## Database rules

**Only touch the `apollo_contracts_expense` database.** It is the contracts database and the
only one this project may read or write.

**Never change `goalapp_apollo`.** Not schema, not data, not seeds. Same for every other
database on the local MySQL instance. Read-only at most, and only when asked.

**Every schema change goes through a Laravel migration.** No raw `ALTER TABLE`, no manual
edits in a DB client, no ad-hoc SQL scripts. Write the migration file, show it for review,
and only run it after the dev approves.

Columns may be added to `apollo_contracts_expense` tables when the gain is worth it — the bar
is a proper migration with a working `down()`, plus a plan for backfilling existing rows.

## Query rules

**Write Eloquent, not raw SQL.** Set by the dev 2026-08-21. Reach for the tools in this order, and
only move down the list when the one above genuinely cannot do the job:

1. **Eloquent relationships** — `whereHas`, `withCount`, `with`. Reads like the domain, and it is the
   first choice. If the relationship does not exist on the model yet, **add it** rather than dropping
   a level.
2. **Eloquent on a subquery** — `whereIn('id', Model::select('id')->where(...))`. Still Eloquent, and
   it keeps the ids inside the database.
3. **Query builder `join`** — named columns, no SQL string.
4. **`DB::raw` / `whereRaw` / `selectRaw`** — last resort. If you use one, say in a comment on the line
   why Eloquent could not express it.

**Never pass a list of ids into `whereIn`.** The pattern to delete on sight is a `pluck()` feeding a
`whereIn()`:

```php
// wrong - two queries, and it breaks silently
$ids  = ContractPartyData::where('custom_field_group_id', $id)->pluck('contract_party_location_id');
$rows = ContractPartyData::whereIn('contract_party_location_id', $ids)->pluck('custom_field_group_id');
```

Three things are wrong with it. It runs two queries where one does the work. It carries every id
across the wire as a bound parameter. And **on this stack a `whereIn` with 1,000 or more bound values
silently returns zero rows** — no error, no warning, just an empty result and a blank section of the
page. See [.scratch/wherein-1000-bug/spec.md](.scratch/wherein-1000-bug/spec.md).

Write it as one query, passing the **query** to `whereIn` instead of the values. Nothing is bound and
nothing crosses the wire:

```php
$rows = ContractPartyData::whereIn(
        'contract_party_location_id',
        ContractPartyData::select('contract_party_location_id')->where('custom_field_group_id', $id)
    )
    ->distinct()
    ->pluck('custom_field_group_id');
```

`whereIn` with a short, fixed list of literal values — `whereIn('contract_status', ['Draft', 'Review'])`
— is fine. The rule is about lists of ids that come out of another query and grow with the data.

**Watch the row count when you fold two queries into one.** A join can return duplicates where a
`pluck()` collapsed them. Add `distinct()` where the old code relied on that, and compare the id sets
before and after — not the look of the page.

**A `Contract` subquery needs one extra step.** `Contract::boot()`
([app/Models/Contract.php:114](app/Models/Contract.php:114)) adds a global scope that calls
`select('*')`, and it runs **after** your own `select()`, so it overwrites it. A one-column subquery
becomes an all-columns subquery and MySQL answers `Operand should contain 1 column`. Drop that one
scope by name:

```php
Contract::withoutGlobalScope('accessLevelSelect')->select('id')->where(...)
```

Drop only that scope. `ContractRoledBasedScope` is the visibility rule and must stay.

## Logging and debug output

**Never use `dd()`, `dump()`, `var_dump()`, `print_r()`, or `echo` to debug.** They ship to
production, they cannot be switched off, and they can print session data or decrypted
contract fields straight onto the page.

**Use `Log::debug()` / `Log::info()` / `Log::warning()` / `Log::error()` instead.** Laravel
already ships Monolog and already reads `LOG_LEVEL` from `.env`. No new package is needed.

**Quieten production with `.env`, never by deleting log lines.** Set `LOG_LEVEL=warning` (or
`error`) on the production server and every `Log::debug()` call goes silent on its own. Leave
the lines in the code — they are the diagnosis tool for next time. Removing them by hand
before a release is how they come back a week later.

The exact values for local and for production are written at the top of
[.env.example](.env.example). Copy them from there rather than remembering them.

**Existing commented-out debug lines are left alone.** 58 of the 64 `dd()`/`print_r()` calls
in this codebase are already commented out and harmless. Do not spend time deleting them.
This rule is about new code.

Pick the level so this works:

- `Log::debug()` — detail only useful while chasing a problem. Off in production.
- `Log::info()` — normal events worth a record.
- `Log::warning()` — something is wrong but the request survived.
- `Log::error()` — the request failed.

**Never log a decrypted contract field, a password, a token, or a full session.** Log the id
and enough to find the row, not the contents.

## How to talk to the dev

**Use plain, simple words.** Short sentences. Say the thing directly.

**Write in Simplified Technical English (ASD-STE100).** This is the rule for every reply, not
only for questions. Keep to it:

- One idea per sentence. 20 words or less.
- Active voice. Say who does the thing. "The scope reads the user table", not "the user table is read".
- Present tense. Use the same word for the same thing every time.
- No long noun strings. "The count of queries for each request" beats "per-request query count".
- Cut every word that carries no meaning.

Skip jargon. If a plain word works, use the plain word. Do not say "blast radius", "O(n)",
"structurally impossible", "non-deterministic" when "how much else breaks", "gets slower with
more rows", "cannot happen", "different every time" say the same thing.

Some terms are worth keeping because they are exact and there is no short plain version.
Those live in [CONTEXT.md](CONTEXT.md) with a plain-words meaning next to each. Add a term
there the first time you use it. If a term is not worth an entry, it is not worth using.

## Asking questions, and summaries

**Ask questions in caveman English.** Short. Blunt. No filler. Cut every word that does not
carry meaning. "Old counter slow. Keep it or drop it?" beats "I wanted to check whether you would
prefer that we retain the existing counter behaviour."

This is for questions only. Explanations and specs stay in normal plain words (see
[How to talk to the dev](#how-to-talk-to-the-dev)).

**Never write a summary unless the dev asks for one.** No "here is what I did" wrap-up at the end
of a reply, no recap of work already shown above. Answer, or report the one thing that changed, and
stop.

## Changing functions

**Change the function in place. Do not leave a copy of the old one beside it.** Set by the dev
2026-08-21, reversing the earlier rule. Two near-identical functions make the code harder to read and
the git diff harder to follow. Git holds the old version, so nothing is lost: if a change makes things
worse, revert the commit. Every migration has a working `down()` for the same reason.

Naming follows PSR-1 / PSR-12, which is what this codebase already does:

- **Class names:** `StudlyCaps` — `ContractDashboardController`, not `contractDashboardController`.
- **Method names inside a class:** `camelCase`.
- **Class constants:** `UPPER_SNAKE_CASE`.
- **Plain procedural functions** (helpers.php and friends): `snake_case`.

Then, for a function you change, pick by what is wrong with it:

- **Name is good** — keep it. No `x` suffix any more; that was for the side-by-side rule and it is gone.
- **Name is bad** — it does not say what the function does. **Rename it** to a name that matches the
  instruction the function carries out, following PSR-1 / PSR-12 above. Suggest the name and get it
  approved before you rename.
- **Logic is bad** — change the logic in place. Same function, better body.
- **Function does too much** — pull the extra concerns out into new functions beside it. The old
  function stays only while something still calls it. **Delete it once nothing depends on it**, and
  check with `grep` before you delete, including blade files.
- **Many callers, and the name is bad** — do not change it in place. **Write the new function beside
  it, move the callers over one at a time, and delete the old one when nothing depends on it.** The
  dev's call 2026-08-21. This is the one case where two functions live side by side, and the reason is
  not comparison — it is that changing a function 55 pages rely on, in one commit, cannot be reviewed.
  A single-caller function is still changed in place.
- **If in doubt, ask.**

One function, one concern. But do not copy blocks of code to get there — pull the shared part out
into its own function and call it from both.

## Staying on a performance task

**On a performance task, leave wrong code alone unless it throws or it costs time.** Set by the dev
2026-08-21. Two tests decide whether a thing is yours to touch:

1. **Does it break the page?** Then fix it. A page that does not render cannot be measured.
2. **Does it cost queries, time or bytes?** Then fix it. Duplicate queries, dead queries, per-row
   lookups, a second decrypt pass over the same rows.

Neither of those? **Write it down and move on.** A wrong result, a null check that works by accident, a
comparison that reads the wrong variable — real bugs, and none of them is the task. The dev's reason:
logic gets fixed on its own later, and the performance is measured again then. Fixing it now buys
nothing and it makes the diff harder to read.

Write each one you leave into the effort's ticket, with the file and line, so a later effort picks it up
instead of finding it again.

**Performance is every number the user waits on, not only the server time.** The dev's list, 2026-08-22:

- the size of the page
- the time to load it — the server response time **and** the browser's own work
- the first render and the last render
- the time spent in the database
- the count of queries

A change that improves one of those is on the task. A change that improves none of them is not, however
wrong the code looks. **The query count is the number that must not regress**, because it does not drift
between sessions the way milliseconds do.

## Measurement report

**Every performance change gets a row in the report file of the effort it belongs to** — one file per
effort, named `measurements/report.md` under that effort's `.scratch/` folder. One table, one row per
change, plus a remark for any side effect. Never start a second report file inside one effort.

**A row records the new numbers only. There is no old-number column.** Set by the dev 2026-08-21. The
row above already holds the previous number, so writing it twice adds nothing. Row 0 of every table is
the baseline, so the first row has something to sit under.

## Verifying UI changes in the browser

The app is served at `http://apollo.contracts.legality:8888/contracts/` and almost every page
requires a logged-in session.

**Verify in the browser. Do not substitute a backend check.** Rendering through `artisan
tinker`, resolving a manifest by hand, or `curl`-ing an asset are supporting evidence at best
— they are not a substitute for loading the real page and looking at it. Never skip or
downgrade browser verification because auth is in the way.

**Getting a driveable session:**

1. Chrome 136+ silently ignores `--remote-debugging-port` on the default profile, so the
   everyday logged-in profile cannot be driven over CDP at all. Launch a fresh dedicated
   profile instead (see below).
2. **Ask the user to log in.** They will do it — do not attempt it yourself, and never enter
   credentials. The debug profile keeps its cookies, so this is a once-per-profile step.
3. Then drive the session and verify.

```powershell
Start-Process "C:\Program Files\Google\Chrome\Application\chrome.exe" -ArgumentList `
  "--remote-debugging-port=9222", `
  "--user-data-dir=C:\Users\karun\.claude\chrome-debug-profile", `
  "--no-first-run","--no-default-browser-check"
```

Confirm CDP is up at `http://127.0.0.1:9222/json/version`. `chrome-devtools-mcp` is already
declared in `.mcp.json` pointing at that port.

The legacy Angular 1.8 app at the IIS document root owns `/login/` and is **not to be worked
on** — treat auth as someone else's problem.
