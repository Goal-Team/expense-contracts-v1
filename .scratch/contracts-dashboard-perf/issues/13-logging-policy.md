# Decide the logging and debug-output policy

Type: grilling
Status: resolved
Assignee: kader (2026-08-20)
Blocked by: —

## Question

The code debugs by printing to the page. Counted 2026-08-20:

| what | in PHP (`app/`, `Modules/`) | in blade pages |
|---|---|---|
| `print_r(` | 53 | 1 |
| `dd(` | 32 | 17 |
| `dump(` | 9 | — |
| `var_dump(` | 9 | — |

About **120 calls** that ship to production. None can be switched off from the outside — the only
way to quieten them is to delete them by hand before a release, which is why they keep coming back.

Meanwhile there are 96 proper `Log::` calls, so the habit exists; it is just not the default.

Nothing new needs installing. Laravel ships Monolog, `config/logging.php` already defines `stack`,
`single`, `daily` and `stderr` channels, and `.env` already carries `LOG_CHANNEL=stack` and
`LOG_LEVEL=debug`. Setting `LOG_LEVEL=warning` on the production server silences every
`Log::debug()` with no code change. This satisfies the no-new-vendor-package rule for free.

The rule is written in [CLAUDE.md](../../../CLAUDE.md). What is left is the clean-up decision.

## Settled 2026-08-20 — most of this ticket dissolved

Recounted properly. Of the 64 `dd()`/`dump()`/`var_dump()`/`print_r()` calls, **58 are already
commented out**. Only **6 are live**:

| file | what |
|---|---|
| [ContractPartyData.php:22](../../../app/Models/ContractPartyData.php:22) | dead — `whereInMultiple()` has **no callers** and would fatal on PHP 8 (`implode($value, ...)` args reversed) |
| [ContractPartyDataHistory.php:21](../../../app/Models/ContractPartyDataHistory.php:21) | dead — same method, same reason |
| [GraphHelper.php:514](../../../app/Helpers/GraphHelper.php:514) | live, rare path |
| [ContractController.php:11710](../../../Modules/Contract/app/Http/Controllers/ContractController.php:11710) | live, rare path |
| [ContractController.php:11792](../../../Modules/Contract/app/Http/Controllers/ContractController.php:11792) | live, rare path |
| [GoogleDriveController.php:742](../../../Modules/Contract/app/Http/Controllers/GoogleDriveController.php:742) | live, rare path |

**Dev's decision: leave them alone.** No clean-up sweep. The rule applies to new code only.

**Also done:** the rule is written in [CLAUDE.md](../../../CLAUDE.md), and
[.env.example](../../../.env.example) now carries the exact local and production values so the
production `.env` can be set without remembering them:

```
APP_ENV=production   APP_DEBUG=false   LOG_CHANNEL=daily   LOG_LEVEL=warning
```

Two facts worth carrying forward: `LOG_CHANNEL=stack` resolves to `single`, which writes one
`laravel.log` that **never rotates** — hence `daily` for production. And `APP_DEBUG=false` is the
same switch that hides the debug bar, so one production setting covers both.

## What is left

1. **Is a lint gate wanted** — something that fails a build on a new `dd(`/`dump(`/`var_dump(` —
   and does it belong here or with the wider tooling question in
   [ticket 14](14-debug-tooling-research.md)?
2. **What `LOG_LEVEL` does production actually run at today**, and who can change it? The rule only
   works if the production `.env` is reachable and set to `warning` or `error`. This needs someone
   with server access to check.
3. **Scope check.** This is hygiene, not speed. Decide whether the remainder belongs on this map or
   is spun out.

## Answer

**Resolved 2026-08-20. All three leftovers closed; nothing spun out.**

1. **No lint gate.** The dev chose option (a): the [CLAUDE.md](../../../CLAUDE.md) rule alone, no
   automatic check. Facts behind the choice, gathered before asking: **there is no build to fail** —
   no `.github/`, no CI config, no husky, no git hooks in this repo, and deploy is a file copy to
   IIS. `vendor/bin` does hold `pint` and `phpunit`, so a checker could be run by hand, but nothing
   runs one today. A Claude Code write-blocking hook was offered and declined. So the rule is
   enforced by review, not by tooling.

2. **Production `.env` is not our problem.** The dev's words: production environment variables are
   carefully mapped, and the production pipeline is not to be worried about now. So the four values
   in [.env.example](../../../.env.example) (`APP_ENV=production`, `APP_DEBUG=false`,
   `LOG_CHANNEL=daily`, `LOG_LEVEL=warning`) stand as the written record, and **no ticket on this map
   waits on someone reading the live file**. Carry-forward for
   [ticket 16](16-debug-tooling-decision.md): "`APP_DEBUG=false` hides it in production" can be taken
   as reliable — the dev vouches for the mapping. That removes the *reachability* worry from the
   debug-bar decision, but **not** the risk itself: the failure mode ticket 14 named is a wrong
   `APP_DEBUG` putting query bindings on a public page, and that risk is about what the setting does
   when wrong, not about who can reach it.

3. **Nothing left to spin out.** With (1) and (2) answered there is no remainder, so option (a): this
   ticket closes on this map. The spec ([ticket 10](10-assemble-spec.md)) carries **one line** in its
   deployment notes naming the four production values — not as a task for anyone here, just so the
   spec is readable by someone who was not in these sessions. No separate logging effort is created.

**Unchanged from the earlier settlement:** the 6 live `dd()`/`print_r()` calls are left alone (2 are
in a dead method with no callers), the 58 commented-out ones are left alone, and the rule applies to
new code only.

