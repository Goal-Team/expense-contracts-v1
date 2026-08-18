# Assemble and agree the spec

Type: grilling
Status: open
Blocked by: 08, 09, 11, 12

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

Sanity checks before calling it agreed: does every claim trace to a measurement or a file:line, and does
the spec still make sense to someone who was not in any of these sessions?

## Answer

<!-- filled on resolution -->
