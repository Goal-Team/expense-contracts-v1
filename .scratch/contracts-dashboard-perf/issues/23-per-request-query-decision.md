# 141 queries a request — what gets cut, and does it belong to this map?

Type: grilling
Status: resolved
Blocked by: [24-attribute-remaining-overhead.md](24-attribute-remaining-overhead.md)

## Question

Graduated from the map's **Not yet specified** on 2026-08-21, on the dev asking "we are running around
147 queries? why? are all those needed?". The fog line said the scope question could not be phrased
sharply until we knew what the ~1.1 s of per-request overhead was.
[Ticket 11](11-per-request-overhead.md) now says. So it can be phrased.

### The count, measured

Last `/dashboard-summary` request in `storage/logs/perf-2026-08-21.log`: **141 queries**, only **17
distinct shapes**, **135 of the executions duplicates** of a shape already run in the same request.
15 views composed.

| owner | queries | ms | needed? |
|---|---|---|---|
| **menu composer** (`View::composer('*')`, once per view) | **108** | ~391 | 3-4 would do |
| unattributed auth-shaped lookups ([ticket 24](24-attribute-remaining-overhead.md)) | 19 | 79 | unknown |
| **actionable-items chunked read** (2,000 rows a chunk) | 6 | **2,491** | yes, by design |
| dashboard's own counters and singletons | 8 | ~80 | yes |

The 108 breaks down exactly as ticket 11 predicted, scaled from 13 views to 15: `menu_configs` at
30 + 30 + 15 (three-tier fallback, run twice per composer for Vertical and Horizontal),
`information_schema.tables` 15 (`Schema::hasTable('menu_configs')`, 226 ms), `admin_settings` 18.
Same answer, recomputed 15 times, on **every page in the application**.

**Query count and response time do not point at the same thing here.** 108 of 141 queries are the menu
composer, but 2,491 of the 3,042 ms of DB time is the 6 approvals chunk reads. Cutting the 108 removes
**77 % of the queries and about 13 % of the DB time**. Both are worth saying out loud before deciding.

### The decision

1. **Is the menu composer fix in this map, or its own effort?** It is not the dashboard's code and it
   changes every page for every user. [Ticket 18](18-goalapp-apollo-note.md) already removed one objection:
   `menu_configs` and `admin_settings` are in our own database, so no "do not touch" rule blocks it.
   Against: this map's destination is the dashboard, and the spec is written and partly applied.
2. **If in scope, which fix?** Memoise `$getConfig` per request; or cache the whole composer result;
   or drop `Schema::hasTable()` from the hot path; or collapse the three-tier fallback into one query.
   Ticket 11 listed these with blast radius. They are not exclusive and they are not equal in risk.
3. **What is the query-count ceiling we are actually holding ourselves to?** The map's agreed target is a
   query ceiling ahead of a ms figure, and no number was ever named. 141 is the current reading.
4. **Does the actionable-items chunked read stay as it is?** 6 queries, 2,491 ms, and it is the single
   largest cost in the request. [Ticket 17](17-plain-columns-experiment.md) is the standing plan for it.
   Confirm that is still the answer rather than reopening it here.
5. **Nothing gets applied in this ticket.** New function beside old, names from
   [names.md](../names.md), a row in [report.md](../measurements/report.md), old and new in the same
   session.

## Decisions taken, round 1 (dev, 2026-08-21)

1. **The menu composer is this map's job, and both halves are done here** — the dead lookups *and* the
   caching. Not split into a follow-on effort. The dev's reason: the app cache is already enabled
   (`CACHE_DRIVER=file`, [.env:48](../../../.env)), and the codebase already has the pattern at
   [ContractOptionListController.php:78](../../../Modules/Contract/app/Http/Controllers/ContractOptionListController.php:78) —
   `cache()->remember()`. If the sidebar needs it turned on, turn it on.
2. **No query-count ceiling. Rejected.** Milliseconds matter more than counts. Counts get corrected where
   we can, but a count is only worth cutting when the query is real work. **The logic is not being
   rewritten** — this is optimising repeats, not redesigning the menu.
3. **[Ticket 17](17-plain-columns-experiment.md) stands unchanged.** My Actionable Items keeps its 6
   chunked reads and 2,491 ms until ticket 17 runs. Not reopened here.
4. **The 19 once-per-request lookups move out of this ticket** into
   [ticket 25](25-memo-per-request-lookups.md), to be grilled after this one.

This ticket is therefore now **only** the menu composer's 108.

## Decisions taken, round 2 (dev, 2026-08-21)

5. **Clear the cache on write.** [MenuConfigController](../../../Modules/Contractsetup/app/Http/Controllers/MenuConfigController.php)
   is the only writer, so its five write points call `forget`. A long time limit sits behind it as a safety
   net. **No version-stamp query** — that would be one query per request we do not need to pay.
6. **The Horizontal lookup is deleted.** 45 queries a request feeding a menu that no rendered view reads.
   `$menuData[1]` is null today on every page, and
   [horizontalMenu.blade.php:8](../../../resources/views/layouts/sections/menu/horizontalMenu.blade.php:8)
   reads it with no guard, so a horizontal layout fails identically before and after. Not a logic change.
7. **No `.env` switch, and no fresh "before" measurement.** The dev's call: the old numbers are already
   recorded, so copy them from [report.md](../measurements/report.md) and the perf log rather than
   measuring the old path again.

   **Caveat, stated because the report's own rule says so:** absolute milliseconds drift about 3x between
   sessions on this machine, so an old ms figure from an earlier session against a new one taken today is
   not a fair comparison. The **query count does not drift**, so 108 -> 0 is solid either way. The row will
   record the ms as indicative and say which session each number came from.

   **One thing this leaves open, and the answer taken:** with no switch, nothing in normal flow can reach
   the old path, which sits against the rule that the old code stays until the new one is proven. So the
   old closure body is **moved whole** into `MenuDataResolver::resolveUncached()` — kept, callable, not
   rewritten, and it is the thing the cache wraps. Nothing is deleted.
8. **Names approved.** `App\Menu\MenuDataResolver`, in [names.md](../names.md) section 7.

## Decisions taken, round 3 (dev, 2026-08-21)

7 **(revised).** **No `resolveUncached()`, and no scaffolding to reach the old path.** The dev's words:
   do not do too much engineering just to get the old number, we already have it. Keep the old function
   only if it is easy to keep; otherwise write the new one and copy the old figures across.

   How it lands: the old closure body becomes **the body of the cache closure inside
   `resolveForRole()`**. So the logic is not rewritten and there is no second dead copy to maintain. This
   is a deliberate, dev-approved step away from CLAUDE.md's "old one stays until the new one is measured" —
   allowed here because the old numbers are already in [report.md](../measurements/report.md) and the perf
   log, so there is nothing left to measure the old path for.
9. **Cache key is the role only.** The `enable_admin_level_menu_config` flag becomes part of the cached
   value, not part of the key. Flipping that flag needs a cache clear, and that is accepted: it is an
   install-time switch reading `true`, not a daily toggle. Zero queries on a cache hit is worth more.
10. **`Schema::hasTable('menu_configs')` goes inside the cache.** It is 226 ms of the 391 — more than half
    the whole cost. Whether a table exists is deployment state, not request state. If the table is ever
    missing, the safety-net time limit covers the gap after someone runs the migration.
11. **The spec gets a new numbered change**, beside Changes A to F, so the biggest query cut on the page is
    visible to whoever implements from [spec.md](../spec.md) and not buried in a ticket.

### Expected result

| | now | after |
|---|---|---|
| menu composer queries | 108 | **0 on a cache hit** |
| menu composer DB time | 391 ms | ~0 on a cache hit |
| whole request | 141 queries | **33** |

The 33 is 141 minus 108. The 19 once-per-request lookups are untouched here — they are
[ticket 25](25-memo-per-request-lookups.md).

## Decisions taken, round 4 (dev, 2026-08-21)

6 **(reversed).** **Nothing is deleted. The top menu lookup stays.** The dev's point, and it is right: the
   three-step fallback is how the menu is *supposed* to work. The role lookup finds no row because nobody
   made a `Super Admin` row; the top menu lookup finds no row because nobody made a `Horizontal` row. Same
   design, same reason. Calling one correct and the other dead was inconsistent.

   The number settles it too: **after the cache, the top menu lookup costs 3 queries per cache miss instead
   of 45 per request.** Deleting it buys almost nothing, and it would be a logic change the dev already
   ruled out in round 1.

   **The old ruling stays written down above rather than being edited away.** What it got wrong: it treated
   "no row exists" as "dead code", when no-row-exists is the exact case the fallback was built for.

   **Q12 is withdrawn.** It asked how far to delete the top menu — the 9 items, from 3 lines up to a
   migration and the menu admin screen. Nothing is deleted, so the question has no subject. The 9-item list
   is left in the transcript for whoever later decides the horizontal layout should go; it is not this
   map's work.

12. **Still open, one line, not performance work:**
    [horizontalMenu.blade.php:8](../../../resources/views/layouts/sections/menu/horizontalMenu.blade.php:8)
    reads `$menuData[1]->menu` with no guard, so a horizontal layout would fail on every page.
    [verticalMenu.blade.php:59](../../../resources/views/layouts/sections/menu/verticalMenu.blade.php:59)
    already does `?? false`. Asked; awaiting the dev.

### Expected result, revised

| | now | after |
|---|---|---|
| menu composer, cache miss | 108 | **7** |
| menu composer, cache hit | 108 | **0** |
| menu composer DB time, cache hit | 391 ms | ~0 |
| whole request, cache hit | 141 queries | **33** |

The 7 is 1 table check + 1 flag read + 2 side menu lookups + 3 top menu lookups. The change is now
**caching and nothing else** — no deletions, no logic change.

## Answer

**Resolved 2026-08-21, after four rounds of grilling. The decision: cache the menu composer, change nothing
else.**

**Is it this map's job?** Yes, both halves, in this map. Not split into a follow-on effort. The app cache is
already enabled (`CACHE_DRIVER=file`) and the codebase already holds the pattern at
[ContractOptionListController.php:78](../../../Modules/Contract/app/Http/Controllers/ContractOptionListController.php:78).

**What gets cut.** 108 queries and 391 ms, down to 7 queries on a cache miss and **0 on a cache hit**. The
whole request goes 141 -> 33. One cache entry per role. Details and the mechanism are now **Change G in
[spec.md](../spec.md) section 8b** — that is the handoff artifact, so it is written there rather than
repeated here.

**What does not get cut, and this is the important part.** Nothing is deleted and no logic changes. The
three-step fallback — by role, then by `Default`, then by empty role — is how the menu is meant to
work. It finds nothing for a Super Admin because nobody made a `Super Admin` row, and nothing for the top
menu because nobody made a `Horizontal` row. Those are the fallback working, not dead code. Round 2 ruled
the top-menu lookup dead and proposed deleting 45 queries a request; **round 4 reversed it on the dev's
challenge**, and the reversal stands as the answer. After caching, that lookup costs 3 queries per cache
miss, so deleting it would buy almost nothing and would change logic the dev had already ruled out.

**No query-count ceiling.** Rejected in round 1: milliseconds decide, and a count is only worth cutting
when the query is not real work. This change survives on the 391 ms, not on the 108.

**Applied here, separately:** the missing guard at
[horizontalMenu.blade.php:8](../../../resources/views/layouts/sections/menu/horizontalMenu.blade.php:8) —
one `@if($menuData[1]->menu ?? false)` and its `@endif`, mirroring
[verticalMenu.blade.php:59](../../../resources/views/layouts/sections/menu/verticalMenu.blade.php:59).
Compile-checked with `BladeCompiler::compileString()` + `php -l`. A latent break, not performance work.

**No code written for the caching itself.** This map is planning; Change G is now in the spec for whoever
implements it. No report row yet either — there is no measurement until it is built.

Split out of this ticket: the 19 once-per-request lookups, [ticket 25](25-memo-per-request-lookups.md).
Withdrawn from this ticket: Q12, how far to delete the horizontal layout — nothing is deleted, so the
question has no subject; the 9-item list is in the ticket body for a later effort.

## Built and verified, 2026-08-21

The dev asked why there was no code, and answered his own question: this map has built Changes A, D, E, F
and ticket 17, so stopping at a spec for G was not consistent with any of it. **Change G is applied.**

| file | what |
|---|---|
| [app/Menu/MenuDataResolver.php](../../../app/Menu/MenuDataResolver.php) | New. The old closure body, moved whole into `lookUp()`, wrapped by `resolveForRole()` in `cache()->remember()`. |
| [app/Providers/MenuServiceProvider.php](../../../app/Providers/MenuServiceProvider.php) | The composer now reads the session role and calls the resolver. Nothing else left in it. |
| [app/Models/MenuConfig.php](../../../app/Models/MenuConfig.php) | Added a `booted()` hook: `saved` and `deleted` both call `MenuDataResolver::flush()`. |
| [resources/views/layouts/sections/menu/horizontalMenu.blade.php](../../../resources/views/layouts/sections/menu/horizontalMenu.blade.php) | The missing `@if($menuData[1]->menu ?? false)` guard, from decision 12. |

**Measured:** cache hit **33 queries**, cache miss **40**, against **141** before. Zero `menu_configs` and
zero `information_schema.tables` left on a hit. `admin_settings` 18 -> 4. Sidebar verified on screen at
1440px, 22 links, all counters unchanged. Invalidation proved end to end: flush bumps the version, the
model hook fires on a clean `save()` without writing a row, the next request misses at 40 and the one after
hits at 33. Full row in [report.md](../measurements/report.md) row 8.

**Two deviations from the decisions above, both found while building, both deliberate:**

1. **Flush is all roles, not one.** `forgetForRole()` cannot work: a role with no row of its own resolves
   through the `Default` row, so editing `Default` changes the answer for roles the write never mentions.
   `flush()` bumps a generation number instead — one write, nothing missed.
2. **The flush lives on the model, not in the controller.** `saved` and `deleted` hooks on `MenuConfig`
   rather than calls in `MenuConfigController`'s write methods. It catches tinker, a seeder, or a screen
   added later, and it is one place to read instead of four. Also: that controller has **four** write
   methods, not the five this ticket claimed.

Both are recorded in [names.md](../names.md) §7 and in [spec.md](../spec.md) §8b.

## Added after the build, 2026-08-21 — the composer's view list

The dev asked the question this ticket never did: **why does the composer run once per view at all?** It
does not need to. `View::composer('*')` attaches to every view, and the dashboard composes 16. Only two
views read `$menuData`, and they are the only two menu view names in the codebase — every layout, root
and all five modules, includes the same two. The other 14 runs handed a value to a view that never reads it.

Now registered on the two names. **Measured with a temporary `Log::debug` in the closure: 1 run, where the
same probe would have logged 16.** Only the vertical view fires; the horizontal one never renders on a
vertical layout. The log line was removed after measuring. Report row 9.

**Honest note on what each half buys.** The query count is 33 either way, because the cache had already
taken the 15 extra runs to 0 queries. Narrowing buys the 15 redundant closure runs and their ~30 app-cache
file reads — real work the query counter cannot see. They are not interchangeable: narrowing alone
leaves 40 queries, caching alone leaves 33 with 16 runs, both give 33 with 1 run.

**Also confirmed while checking, because the dev asked:** the shared-layout arrangement he described is
**already how this app is built.** Every page extends `layoutMaster` -> `contentNavbarLayout` ->
`commonMaster`, and the menu is included once at
[contentNavbarLayout.blade.php:39](../../../resources/views/layouts/contentNavbarLayout.blade.php:39). All
five module layouts include that same root view, not copies. So the menu view renders **once** per request.
The 16 was never 16 menus — it is the layout chain plus the panels, of which 1 is the menu. Blade also
caches its **compiled** views, but it does not cache their **output**, so no rendered HTML is being reused.
