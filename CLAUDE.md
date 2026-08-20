# CLAUDE.md

## Git workflow

**Never create git worktrees.** Work on branches in the primary working directory
(`D:\Contract-Expense\GOALv4\contracts`) only. If isolation is needed, create a branch and
check it out in place — do not use `git worktree add`, and do not pass `isolation: "worktree"`
to subagents or workflows.

Rationale: this repo ships `vendor/`, `node_modules/`, and a `.env` whose
`APP_ENCRYPTION_KEY` is derived from the serving hostname; a worktree copy is neither cheap
nor functional here.

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

## Adding improved functions

**Never rewrite a working function in place. Add a new one beside it.** The old one stays until the
new one is measured and proven, so old and new can be compared on the same page and the same data.
Deleting the old one is a separate, later step.

Naming follows PSR-1 / PSR-12, which is what this codebase already does:

- **Class names:** `StudlyCaps` — `ContractDashboardController`, not `contractDashboardController`.
- **Method names inside a class:** `camelCase`.
- **Class constants:** `UPPER_SNAKE_CASE`.
- **Plain procedural functions** (helpers.php and friends): `snake_case`.

Then, for the new function beside the old one:

- If the old name is good, the new one is the old name with **`x`** on the end
  (`buildCounters` -> `buildCountersx`).
- If the old name is bad — it does not say what the function does — **suggest a better name** that
  matches the instruction the function carries out, and get it approved before writing code.
- **If in doubt, ask.**

One function, one concern. But do not copy blocks of code to get there — pull the shared part out
into its own function and call it from both.

## Measurement report

**Every performance change gets a row in one file:
[.scratch/contracts-dashboard-perf/measurements/report.md](.scratch/contracts-dashboard-perf/measurements/report.md).**
One table, old number and new number side by side, plus a remark for any side effect. One file so the
biggest wins are obvious at a glance. Never start a second report file.

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
