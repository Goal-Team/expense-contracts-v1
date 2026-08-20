# Assemble and agree the spec

Type: grilling
Status: resolved
Assignee: kader (2026-08-20)
Blocked by: 08, 09, 11, 12, 15

## Question

The destination. Turn every closed decision into one implementable spec document covering:

- **Measured baseline** at N=18 and N≈3,000, with the attribution table from
  `05-baseline-attribution` — so a reader knows what was actually wrong, not what was assumed.
- **The AJAX dropdown conversion** — endpoints, caching, selection state, failure behaviour,
  authorisation scoping, and which pages.
- **The query-layer changes** as one unit, with the behaviours that must be preserved called out
  explicitly (no-internal-party exclusion, Super Admin empty-branch-set semantics, the `Terminated`
  case decision).
- **The asset-pipeline resolution**, including whatever needs deleting.
- **Migration files**, written out, with expected build times and rollback — shown for review, not
  applied.
- **Ordering and independence** — what ships first, what can ship alone, what depends on what.
- **Expected outcome per change**, against the agreed targets: under 2s good, around 2s tolerable, over
  10s unacceptable, plus a query-count ceiling.
- **What was deliberately not done**, and why.
- **Deployment notes** — one line naming the production `.env` values the spec depends on
  (`APP_ENV=production`, `APP_DEBUG=false`, `LOG_CHANNEL=daily`, `LOG_LEVEL=warning`), so a reader who
  was not in these sessions knows what they are. Not a task for anyone here — the dev has confirmed
  production variables are carefully mapped. See [ticket 13](13-logging-policy.md).

Sanity checks before calling it agreed: does every claim trace to a measurement or a file:line, and does
the spec still make sense to someone who was not in any of these sessions?

## Answer

**Written 2026-08-20: [spec.md](../spec.md).** The destination of this map.

14 sections: the measured baseline at both scales, the four independent problems plus the correctness
bug, the targets and the query-count ceilings, six changes (A query layer, B shadow columns, C backfill,
D indexes with all three migration files written out, E asset pipeline, F AJAX dropdowns), what is
preserved and the two things that deliberately change, a 12-step order of work with dependencies, the
expected outcome per change, what was deliberately not done, and deployment notes.

Three things the spec says out loud rather than burying:

- **It does not reach "under 2 s" on its own.** 14.4 s minus ~11.9 s of controller time leaves ~2.5 s,
  and ~1.25 s of that is bootstrap this spec does not touch. It takes the page from unacceptable to
  roughly tolerable; the last second is [ticket 11](11-per-request-overhead.md)'s territory.
- **The AJAX dropdown conversion — the map's original centrepiece — is worth ~15 % of payload and two
  queries, not seconds.** Measurement resized it and the spec states the honest number.
- **Absolute milliseconds on this machine vary ~3× between sessions.** The spec tells the reader to
  judge the work by query counts and proportions.

Sanity checks from the question, both met: every claim traces to a measurement or a `file:line`, and
extrapolated numbers are labelled as extrapolations rather than passed off as measurements.

