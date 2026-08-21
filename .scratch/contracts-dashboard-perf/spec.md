# Contracts Dashboard Performance — Implementation Spec

Status: **agreed 2026-08-20**. Produced by the wayfinder map at [map.md](map.md); every claim below
traces to a closed ticket in [issues/](issues/), and every ticket traces to a measurement or a
`file:line`.

The page: `http://apollo.contracts.legality:8888/contracts/` — Laravel route `/` →
[`ContractDashboardController::dashDetails`](../../Modules/Contract/app/Http/Controllers/ContractDashboardController.php:35)
→ [`viewDashboard1.blade.php`](../../Modules/Contract/resources/views/dashboard/viewDashboard1.blade.php).
`/contracts` is an **IIS base path**, not a route segment.

Nothing in this spec has been applied. No migration has been run. No file has been deleted.

---

## 1. What was actually wrong

Measured with the local-only timing middleware from [ticket 02](issues/02-timing-middleware.md), at two
dataset sizes: the real local data (18 contracts) and a seeded set
([ticket 04](issues/04-seed-realistic-dataset.md)) of 3,018 contracts, 6,940 party rows, 13,867 approval
rows.

| Component | N=18 | N=3,018 |
|---|---|---|
| bootstrap | 1,072–1,133 ms | **1,255 ms** (flat) |
| routing | 34–68 ms | 59 ms |
| route middleware | 149–163 ms | 159 ms |
| **controller** | 252–429 ms | **12,583 ms** |
| view render | 461–887 ms | 379 ms |
| send + terminate | 1–5 ms | 1 ms |
| **total (document TTFB)** | **2,084–2,573 ms** | **14,437 ms** |
| queries | 164 | **5,654** |
| DB total | 507–1,109 ms | 8,336 ms |
| duplicate executions | 153 | 5,643 |
| **HTML size** | **67 KB** | **67 KB** |

The attribution reconciles with no unexplained gap at either size. Four separate problems fall out of
it, and they are independent of each other.

**a. Two N+1 query loops own the controller at scale.** 5,526 of the 5,654 queries are just two
statements repeated:

| times run | total | query |
|---|---|---|
| 3,018 | 4,236 ms | `select * from contract_categories where id = ? limit 1` |
| 2,508 | 3,460 ms | `select * from contract_type where contract_type_id = ? and applicable = ?` |

They come from [Controller.php:228](../../app/Http/Controllers/Controller.php:228) and
[Controller.php:321](../../app/Http/Controllers/Controller.php:321). The remaining ~4.2 s of controller
time is PHP hydrating 3,018 rows of 110 columns and running a counting loop over them.

The pattern is **2·N, not 4·N** as originally assumed, and the "duplicate lazy load" at
[Controller.php:219](../../app/Http/Controllers/Controller.php:219)/[:223](../../app/Http/Controllers/Controller.php:223)
**costs nothing** — `protected $with = ['contractPartyList']` already eager-loads it.

**b. The page computes 12 seconds of work that never reaches the response.** HTML stayed **67 KB at
both sizes**. The dashboard emits counters and dropdowns, not per-contract markup. So the whole
regression is server-side work on rows that are then thrown away.

**c. ~18 of the ~21 s wall clock is a dead asset server.** 28 of 31 browser requests fail; 26 are
refused by `[::1]:5173`, each burning 2–6 s and serialising
([ticket 01](issues/01-attach-chrome-devtools.md)). A stale `public/hot` file from September 2024 points
every asset URL at a Vite dev server that is not running, and the detection is a bare `is_file()` with
no probe and no fallback ([ticket 03](issues/03-vite-setup-research.md)).

**d. ~1.1 s of every request is spent before routing.** `opcache` is **not loaded**, so all 810 included
files recompile on every request; there is no config or route cache in `bootstrap/cache/`; 51 providers
boot per request. And `MenuServiceProvider`'s `View::composer('*')`
([MenuServiceProvider.php:25](../../app/Providers/MenuServiceProvider.php:25)) **alone accounts for all
92 overhead queries** — an uncached three-tier menu lookup re-run for each of 13 views, on every page in
the application ([ticket 11](issues/11-per-request-overhead.md)).

**And one correctness bug found along the way.** MariaDB 10.4.24 sets
`in_predicate_conversion_threshold = 1000`: at 1,000 or more **bound parameters** it rewrites `IN` into
a materialised subquery that **silently returns zero rows** — no error, no warning. Proven exactly:
999 ids → 6,684 rows; **1,000 ids → 0**; the same 2,508 values as literals → 11,506; the same values
bound with the conversion switched off → 11,506. `EMULATE_PREPARES` is off here, so **every Laravel
`whereIn` is on the broken path**. Any tenant with 1,000 or more visible contracts sees a **blank
approvals panel and silently zero task counters**
([ContractDashboardController.php:171](../../Modules/Contract/app/Http/Controllers/ContractDashboardController.php:171),
[:187](../../Modules/Contract/app/Http/Controllers/ContractDashboardController.php:187)) —
`goalapp_apollo` has 2,886. See [ticket 12](issues/12-approvals-empty.md).

Two consequences: the 14.4 s above is an **under**-estimate, because those paths were dormant while it
was measured; and the query rewrite in §3 is a **correctness** fix, not only a speed one.

---

## 2. Targets

- Under 2 s good. Around 2 s tolerable. Over 10 s unacceptable.
- **A query-count ceiling matters more than the millisecond figure** — it is what stops the regression
  coming back. Absolute milliseconds on the dev machine vary about **3×** between sessions (the same
  5,654 queries measured at both 8.3 s and 24.5 s of DB time), so trust counts and proportions.

Ceilings to enforce after this work:

| measure | today (N=3,018) | ceiling |
|---|---|---|
| queries issued by `dashDetails` | 5,654 | **10, and flat as N grows** |
| queries in the whole request | 5,746 | **120** (92 of those are the menu composer, §12) |
| document TTFB at N≈3,000 | 14.4 s | **under 2 s** |

The flat-as-N-grows clause is the real test. A count that rises with row count means an N+1 has
returned.

---

## 3. Change A — rewrite the dashboard query layer

The centrepiece. Full reasoning in [ticket 08](issues/08-query-layer-redesign.md).

**The dashboard stops calling `availableContracts()`** and builds its own query with
`DB::table('contracts')` — the query builder, not the Eloquent model. It never loads a contract row into
PHP.

The builder is used deliberately, not by taste: `Contract::boot()` adds a global `select('*')`
([Contract.php:114](../../app/Models/Contract.php:114)) that **overwrites any narrow `select()`**, and
`protected $with = ['contractPartyList']` ([Contract.php:17](../../app/Models/Contract.php:17))
eager-loads party rows on every query made through the model, application-wide. The builder is not
subject to either, so the problem is sidestepped instead of fought. It also deletes the ~4.2 s of
hydration outright, because nothing is hydrated.

**All 15 stage counters become one `GROUP BY contract_status, substatus`** over the visible set,
returning about 20 rows, which PHP folds into the 15 counters using the existing `contractStatusKey()`
([helpers.php:116](../../app/helpers.php:116)).

The fold stays in PHP on purpose. `contractStatusKey()` and the `Terminated` case check work correctly
today, and MySQL's case-insensitive collation would silently change the answer if the logic moved into
SQL `CASE` arms. Twenty rows crossing the boundary is free.

**The visibility rule is written once as a reusable query scope** — department `IN` plus an `EXISTS` on
`contract_party_data` for an internal party in a reachable branch. Not inline SQL, so the follow-on
effort (§12) adopts it rather than re-deriving it. No decrypted value participates in any filter; every
column the counters branch on (`contract_status`, `substatus`, `status`, `contract_type`) is plaintext.

**`$contractIds` is deleted, not chunked.** The approvals and tasks queries `JOIN` against the
visibility scope instead of receiving a PHP array of ids. This is the correctness decision: chunking
into sub-1,000 batches was considered and rejected, because it treats a silently-wrong-answer bug as a
size limit to tiptoe around, and the next `whereIn` anyone writes reintroduces it with no warning. With
no id list, the bug **cannot happen**. `$contractStatus` becomes a column on the approvals result
instead of a PHP lookup table.

**`$contracts` stops being passed to the view.** Verified: `viewDashboard1.blade.php` references it on
exactly one line — [:330](../../Modules/Contract/resources/views/dashboard/viewDashboard1.blade.php:330),
a temporary perf probe. It is removed rather than passed as an empty placeholder.

### Measured cost of the replacement

Run against the seeded database with **no new indexes**, warm cache, timed with `SHOW PROFILES`
([ticket 09](issues/09-index-and-migrations.md)):

| new query shape | time |
|---|---|
| all 15 counters — `GROUP BY` + `EXISTS` visibility | **13–17 ms** |
| approvals joined to the visibility set | **64–72 ms** warm, 431 ms cold |

**12.6 s of controller time becomes about 15 ms**, with no index at all. `EXPLAIN` confirms both scans
are full table scans and still finish in 13 ms, because at this size the tables are small.

### Expected recovery

| what | recovers |
|---|---|
| kill the two N+1s | ~7.7 s |
| stop hydrating 3,018 × 110-column rows to count them | ~4.2 s |
| **together** | **~11.9 s of the 12.6 s controller time** |

---

## 4. Change B — "My Actionable Items" stays in PHP, and costs about 2 seconds

**Changed 2026-08-20 by the dev: no shadow columns.** The earlier plan added two plaintext copies of
encrypted columns; that is off the table. The counter is kept working the only way left — decrypting in
PHP — and the cost is stated here rather than hidden. Making it cheap is
[ticket 17](issues/17-plain-columns-experiment.md), which runs **last**.

There is **no approvals panel**. `$approvalsArr` is never displayed; it feeds a counting loop at
[viewDashboard1.blade.php:305-321](../../Modules/Contract/resources/views/dashboard/viewDashboard1.blade.php:305)
that produces six integers.

It cannot be done in SQL. `approval_status` and `username` are **encrypted in all 13,867 rows**,
AES-128-CBC with a random IV — the same value encrypts differently every time, so it is not matchable,
filterable, or indexable. **No index helps, so none is prescribed for it.** `original_username` is
**not** a plaintext fallback: it serves another purpose and must not be repurposed.

Doing nothing is not on the table either: the moment the id list becomes a `JOIN` (§3), this counter
turns back on by itself and brings the full decrypt with it.

**Decision: decrypt in PHP, on every dashboard load, over the narrowest row set that still gives the
right six numbers.**

### What "narrowest" means

The rows are cut down in SQL first, and only what survives is decrypted:

- `JOIN` the §3 visibility scope, so only approval rows for contracts this user can see are read. No id
  array — the §1 bug cannot happen.
- `row_status = 1` and `superseded = 0`, so rejected and replaced rows never load.
- Select **only** `id`, `contract_id`, `username`, `approval_status`, `contract_status` — five columns,
  not the whole 40-column row.
- Chunk the read. A single `get()` of 60,000 rows holds the lot in memory at once for no reason.

Then PHP decrypts `username` and `approval_status` per surviving row and folds the six integers.

### The honest cost

**Superseded 2026-08-21 by [ticket 17](issues/17-plain-columns-experiment.md), which has shipped.
`approval_status` is no longer encrypted, so this cost is gone. The table below is kept because it was
wrong in a way worth remembering.**

| rows decrypted | values | measured / expected |
|---|---|---|
| ~~13,867 (local seeded, all of them)~~ | ~~27,734~~ | ~~**0.49 s**, measured — 0.018 ms per value~~ |
| ~~60,000 (assumed production scale)~~ | ~~~120,000~~ | ~~**~2 s**, extrapolated~~ |

**Both figures were about double the truth.** They assumed two values decrypted for every row. The code
decrypts `approval_status` for every row but `username` only for rows that already came back `pending` -
it `continue`s otherwise. Measured over the same 13,861-row set: **13,861 + 2,127 = 15,988 values,
320-334 ms**, not 27,734 / 0.49 s.

That mattered, because it means the expense was **one column, and it was `approval_status`** - not the
pair. Ticket 17 therefore converted that column alone: plain `varchar(20)` with an index on
`(approval_status, row_status, superseded, contract_id)`, so the pending filter runs in SQL and PHP
never sees the other 11,734 rows. `username` **stays encrypted** - it holds JSON `{email,name}` whose
name is printed in 13 blade files, and at 2,127 decryptions it is not worth converting.

Measured after: the counter went from **~4.4-4.8 s to ~380 ms** of whole-request time at N=3,018, six
numbers identical. [report.md](measurements/report.md) rows 8 to 8c.

The version of the counter described in this section is still in the code as
`actionableApprovalRows()` + `actionableItemCounts()`, beside `actionableApprovalRowsx()` +
`actionableItemCountsx()`, and is deleted at §10 step 11 like every other old half.

**No caching.** Rejected for the same reason as before: caching a number that is wrong for a different
reason is not a fix. Nothing is cached until the numbers are proven right.

---

## 5. Change C — withdrawn

There is no backfill, because there are no shadow columns to fill. The whole of the previous Change C —
the standalone script with the hardcoded key, the marker rows, the every-row verification — is
withdrawn.

**Nothing in it is wasted.** The rules it worked out still bind, and
[ticket 17](issues/17-plain-columns-experiment.md) inherits them in full when it converts the two columns
for real: seatbelt round-trip check before any write, never the `safeDecrypt` pattern (it returns
ciphertext on failure, which would look correctly filled), `chunkById(1000)` and stateless, **never
`whereIn`**, marker plus `Log::warning` with the row id only on failure, and verification that compares
**every** row rather than a sample. Full reasoning stays in
[ticket 15](issues/15-approval-backfill-plan.md).

**One fact from it is still load-bearing everywhere else** — how the encryption key works, because §4
decrypts and any CLI work touching these columns needs it:

- **PHP scheme** — these columns. [config/app.php:201](../../config/app.php:201) is
  `'APP_ENCRYPTION_KEY' => "c0n|r@(t$" . $linkarray[0] . "4"`, where `$linkarray[0]` is
  `$_SERVER['HTTP_HOST']` split on dots, first piece. From `apollo.contracts.legality:8888` that is
  **`apollo`**, giving the 16-byte key AES-128-CBC requires. The port and the rest of the host are
  ignored. **`encryptString($string, $key)` and `decryptString($string, $key)` throw their `$key`
  argument away** ([helpers.php:141](../../app/helpers.php:141),
  [:154](../../app/helpers.php:154)) — every call site passes one and none of them matter.
- **Legacy SQL scheme** — master tables only. [helpers.php:386](../../app/helpers.php:386) emits
  `AES_DECRYPT($column, '{APP_LEGACY_KEY}.$key')`, so `decrypt_data('BranchName','branch')` ends the key
  with `.branch`. **This** is the scheme where a table name is part of the key. Not these columns.

From a bare CLI the host is `localhost`, the key is the wrong length, and the Encrypter **refuses to
construct** — so a mistake crashes rather than writing rubbish. Any CLI work on these columns needs
`HTTP_HOST=apollo.contracts.legality:8888 php artisan ...`.

---

## 6. Change D — indexes and migrations

Full reasoning in [ticket 09](issues/09-index-and-migrations.md), **less the shadow columns**, which are
withdrawn (§4).

**The rewrite needs no indexes to hit its numbers.** The measured 13–17 ms in §3 was with zero new
indexes. So the index question is not "what makes the dashboard fast" — it is "what stops it getting slow
again as rows pile up", which is much smaller.

**One index, on `approval_contracts.contract_id`.** That table is the one place an index clearly earns
its keep today: 12,816 rows, 17.5 MB, `PRIMARY` only, so every approvals query reads the whole table
(`EXPLAIN`: `type=ALL`), and §4 now joins it on `contract_id` on every dashboard load.

**The composite index is gone.** `(approver_email, approval_status_plain)` indexed the two shadow
columns; with no shadow columns there is nothing to index, and the encrypted originals cannot be indexed
usefully at all. It comes back only if [ticket 17](issues/17-plain-columns-experiment.md) makes the
columns plain.

**Not prescribed:** nothing on `contracts` and nothing on `contract_party_data` *for speed*. They measure
fast unindexed, and every extra index costs write time on a 110-column table. Recorded as "measure again
if rows grow well past 10,000", not as work.

**Standing rule:** every table and column this effort creates or changes uses character set **utf8mb4**
and collation **utf8mb4_unicode_ci**, named explicitly. `utf8mb4_0900_ai_ci` was asked for first and
dropped — it is MySQL 8 only and **does not exist** on the local MariaDB 10.4.24, so a migration naming
it fails outright. `utf8mb4_unicode_ci` works on both, is case-insensitive, is already the collation of
`contracts` and `approval_contracts`, and is already the value in
[config/database.php:56](../../config/database.php:56) — so no mixed-collation comparison can appear in
the tables this work touches.

### Migration 1 — index `approval_contracts.contract_id`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_contracts', function (Blueprint $table) {
            $table->index('contract_id', 'idx_approval_contracts_contract_id');
        });
    }

    public function down(): void
    {
        Schema::table('approval_contracts', function (Blueprint $table) {
            $table->dropIndex('idx_approval_contracts_contract_id');
        });
    }
};
```


### Migration 2 — convert `contract_party_data` (separate, needs a window)

`contract_party_data` is **MyISAM, latin1_swedish_ci**, while `contracts` is InnoDB, utf8mb4. Its two
join columns are **`TEXT`**, which cannot be indexed without a prefix length and would not compare well
across a collation boundary anyway. The dev chose to fix the root problem rather than work around it.

Measured maximum lengths in 6,940 local rows: `contract_party_type` **10** characters,
`contract_party_location_id` **3**. `varchar(20)` on both is generous. `custom_field_group_id` is already
`int(11)` and needs no type change.

**This ships as its own migration, separate from the dashboard change**, so the dashboard fix is not
blocked on it and can be measured independently.

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE `contract_party_data` ENGINE = InnoDB');

        DB::statement(
            'ALTER TABLE `contract_party_data` '
            . 'CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );

        DB::statement(
            'ALTER TABLE `contract_party_data` '
            . 'MODIFY `contract_party_type` varchar(20) '
            . 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL, '
            . 'MODIFY `contract_party_location_id` varchar(20) '
            . 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL'
        );

        Schema::table('contract_party_data', function ($table) {
            $table->index('custom_field_group_id', 'idx_cpd_custom_field_group_id');
            $table->index(
                ['contract_party_type', 'contract_party_location_id'],
                'idx_cpd_type_location'
            );
        });
    }

    public function down(): void
    {
        Schema::table('contract_party_data', function ($table) {
            $table->dropIndex('idx_cpd_type_location');
            $table->dropIndex('idx_cpd_custom_field_group_id');
        });

        DB::statement(
            'ALTER TABLE `contract_party_data` '
            . 'MODIFY `contract_party_type` text NULL, '
            . 'MODIFY `contract_party_location_id` text NULL'
        );

        DB::statement(
            'ALTER TABLE `contract_party_data` '
            . 'CONVERT TO CHARACTER SET latin1 COLLATE latin1_swedish_ci'
        );

        DB::statement('ALTER TABLE `contract_party_data` ENGINE = MyISAM');
    }
};
```

> **Honest note on that `down()`:** it is written and it works, but converting back is **not risk-free**
> — latin1 cannot hold every character utf8mb4 can, so the round trip is only safe if nothing wrote
> non-latin1 text in between.

### Expected build times

Extrapolated from local measurements at the assumed production scale — ~10,000 contracts, ~500
approvers, ~60,000 approval rows. Production data is off limits, so these are byte-per-row
extrapolations, **not measurements**. No index was built on the local database, because every schema
change goes through a reviewed migration.

| step | rows | expected | lock |
|---|---|---|---|
| index `contract_id` | ~60,000 (~82 MB) | seconds | online, no table lock |
| convert `contract_party_data` | ~23,000 | seconds | **full rebuild, table locked — needs a window** |

---

## 7. Change E — the asset pipeline

Full reasoning in [ticket 07](issues/07-asset-pipeline-decision.md) and
[ticket 03](issues/03-vite-setup-research.md).

**Delete `public/hot`, and turn RTL off in the same change.** This is the single cheapest win in the
whole spec: ~18 s of wall clock for one file deletion. Safe — all 441 blades' entrypoints were verified
present in `public/build/manifest.json`. Only `public/hot` is read; the root `hot` file is inert.

Set `'myRTLSupport' => false` at [config/custom.php:13](../../config/custom.php:13) **in the same
change**. Deleting `hot` while it is still `true` would serve precompiled **RTL** CSS for the first time
— right now nothing loads at all, so the setting has been invisible and the layout would visibly flip.
Also check the default at [Helpers.php:31](../../app/Helpers/Helpers.php:31), and note the value is
**cookie-overridable**, so a stale cookie can reintroduce RTL for one user after the config flips;
decide whether those cookies get cleared.

**A committed, working root `vite.config` is a required deliverable, not deferred work.** There is no
root `vite.config.js`/`.mjs` anywhere in the repo, its parent, or git history. Consequently
`npm run build` **cannot work today**: `laravel-vite-plugin` is installed but never loaded so no manifest
is emitted, the default `outDir` is `dist/` not `public/build/`, and the default entry is `index.html`
which does not exist here. The current `public/build/` output came from a machine that had the config.
**Without a config, nobody can change a stylesheet in this application at all.**

Two notes for whoever writes it: the config must be **committed** — `.gitignore`'s `*.mjs` rule plus the
stale `vite.config.mjs.timestamp-*` entries are how it went missing in the first place. And once building
resumes, `core.scss` genuinely costs **5.6–7.9 s** to compile, because `sass@1.71.0` runs pure-JS on Vite
5.1.3's *legacy* sass API; `api: 'modern-compiler'` needs Vite 5.4+, so the fix is `sass-embedded` or a
Vite upgrade, not a flag.

**Then consolidate the duplicated trees** — no measurable win, but it removes a live footgun: the
manifest is read from `public/build/manifest.json` while the bytes are served from the root `build/`, two
independently-aged copies, one supplying filenames and the other content. Consolidate
`hot`/`public/hot`, `build/`/`public/build/`, and the two scss trees (`resources/assets/vendor/scss/` vs
`Modules/Contract/resources/vendor/scss/`). The consolidation must reconcile the split, not just delete
one side.

**Separately:** `contracts/assets/fonts/font-main.css` fails with `ERR_ABORTED` (~0.9–1.3 s). Not a Vite
URL, survives everything above, needs its own fix.

---

## 8. Change F — the AJAX dropdown endpoints

Full reasoning in [ticket 06](issues/06-ajax-dropdown-design.md). This was the committed centrepiece when
the map was charted; measurement has since resized it, and the spec states the honest number.

**Dashboard only for now** — the two selects on `viewDashboard1`: `contracttype` (73 options) and the
branch `select2` (63 options). `contractList` and its five selects follow as separate work once the
pattern is proven.

**But the endpoints are built shared, not dashboard-private.** Both pages consume the identical two
lists; a dashboard-only endpoint under a dashboard-specific route prefix would guarantee duplication
later.

**One combined endpoint** returning all requested lists in a single JSON object, not one endpoint per
list. Four round trips on a page with known connection-queueing problems is the main way this change
could make the page *slower*. One request means one cache entry and one failure mode.

It **must respect the same `BranchScope` / `DepartmentScope` / `ContractRoledBasedScope` filtering the
page applies** — these lists expose branch and department names, and an endpoint that returns the full
branch list to a location-scoped user is a security regression, not a convenience.

**Fetch once and populate; `select2` stays static; no pagination.** At 73 and 63 options the lists are
small enough to send whole. `select2`'s `ajax:` mode re-queries on every keystroke, turning two queries
into dozens, and needs server-side search and paging that does not exist.

**Named fallback, with its trigger:** if measurement shows the single fetch is still too slow, move to
server-side option loading with a window of ~10 options and infinite scroll. Deferred on purpose — it is
the `select2` `ajax:` design plus server-side search and paging, and it should only be paid for if the
simple version measurably fails. Recorded here so it is not relitigated from scratch.

**Expected win, stated honestly: about a 15 % payload cut and two queries off the critical path — not
seconds.** The `<option>` markup is 136 tags, 10,761 bytes, 15.3 % of the dashboard HTML (20.4 % of the
contract list) — but it is a **fixed** 10.8 KB that does not grow with the dataset, since HTML stayed
67 KB at both 18 and 3,018 contracts. The dropdown queries are nowhere near the top costs.

**Implementation detail left to the implementer**, with one steer: selection state
(`$selcontype` / `$sellocal` at
[ContractDashboardController.php:241](../../Modules/Contract/app/Http/Controllers/ContractDashboardController.php:241)),
what renders before options arrive, failure behaviour, and cache invalidation. Reuse the existing cache
pattern at
[ContractController.php:6879-6896](../../Modules/Contract/app/Http/Controllers/ContractController.php:6879),
which keys a 10-minute cache on a `COUNT(*)`/`MAX(updated_at)` version stamp.

---

## 8b. Change G — cache the menu composer

Added 2026-08-21 from [ticket 23](issues/23-per-request-query-decision.md). Numbered `8b` so Changes A to F
keep the numbers they already have everywhere else in this file.

**The finding.** `MenuServiceProvider::boot()` registers `View::composer('*')`
([MenuServiceProvider.php:25](../../app/Providers/MenuServiceProvider.php:25)). Laravel runs it one time for
each view. The dashboard composes 15 views, and the closure caches nothing, so it recomputes an identical
answer 15 times. Measured on `/dashboard-summary`: **108 queries, 391 ms** — 77 % of the request's 141
queries and about 13 % of its database time. It is not dashboard code. It runs on **every page in the
application**.

Per run it is 7 queries:

| query | n per run | n per request |
|---|---|---|
| `Schema::hasTable('menu_configs')` → `information_schema.tables` | 1 | 15, and **226 ms of the 391** |
| `admin_setting('enable_admin_level_menu_config')` | 1 | 15 of the 18 seen |
| side menu: by role (finds nothing), then by role `Default` (finds it) | 2 | 30 |
| top menu: by role, by `Default`, by empty role — all find nothing | 3 | 45 |

**Why two of those lookups find nothing, and why that is correct.** The three-step fallback is the design:
use a menu made for your role, else the `Default` row, else a row with an empty role. `menu_configs` holds 7
rows, all of them side-menu rows, for `User`, `Manager`, `Admin`, `Marketing Manager`, `User1`, `Default`
and `Legal`. There is **no `Super Admin` row** and **no top-menu row at all**. So a Super Admin session
falls through to `Default`, and the top menu falls through to nothing. Both are the fallback doing its job,
not dead code. An earlier draft of the ticket called the top-menu lookup dead and proposed deleting it; the
dev reversed that, and the reversal is recorded in the ticket rather than edited away.

**The change: caching, plus registering the composer on the two views that need it. No deletions and no
logic change.**

- **`View::composer` names the two menu views instead of `'*'`.** Added 2026-08-21 after the dev asked why
  the composer runs once per view at all. It does not need to: only
  [verticalMenu.blade.php:59](../../resources/views/layouts/sections/menu/verticalMenu.blade.php:59) and
  [horizontalMenu.blade.php:8](../../resources/views/layouts/sections/menu/horizontalMenu.blade.php:8) read
  `$menuData`, and those are the only two menu view names in the codebase — every layout, root and all
  five modules, includes the same two
  ([contentNavbarLayout.blade.php:39](../../resources/views/layouts/contentNavbarLayout.blade.php:39),
  [horizontalLayout.blade.php:54](../../resources/views/layouts/horizontalLayout.blade.php:54)). Measured
  with a temporary log line: **16 runs a request became 1.** The horizontal view never renders on a vertical
  layout, so its composer never fires. **This is not interchangeable with the cache** — narrowing alone
  leaves 40 queries (1 run x 7), caching alone leaves 33 but keeps 16 runs. Together: 33 queries, 1 run.
- New class `App\Menu\MenuDataResolver` — names and reasons in [names.md](names.md) section 7. The
  existing closure body becomes the body of the cache closure inside `resolveForRole(?string $role)`, so
  there is no second copy of the logic to keep in step.
- **Cache key is the role only.** The `enable_admin_level_menu_config` flag becomes part of the cached
  value. Flipping that flag therefore needs a cache clear — accepted, because it is an install-time
  switch, not a daily toggle.
- **`Schema::hasTable()` goes inside the cache.** It is more than half the cost, and whether a table exists
  is deployment state, not request state. The safety-net time limit covers the case where the table is
  created later.
- **Cleared on write, not on a version stamp.** An admin edit shows at once and the request pays **no**
  stamp query. A long time limit sits behind it as a safety net.

  **Two corrections made while building this, 2026-08-21.** First, the clear is **all roles, not one**:
  `forgetForRole()` cannot work, because a role with no row of its own resolves through the `Default` row,
  so editing `Default` changes the answer for roles the write never names. `MenuDataResolver::flush()` bumps
  a generation number in the cache key instead — one write, nothing missed. Second, the flush is a
  **`saved`/`deleted` hook on the `MenuConfig` model**, not calls inside
  [MenuConfigController](../../Modules/Contractsetup/app/Http/Controllers/MenuConfigController.php): the
  hook catches tinker, a seeder, or a screen added later, and it is one place to read instead of four. That
  controller has **four** write methods, not the five stated in the ticket.
- Cache mechanics copy the one existing precedent in this codebase,
  [ContractOptionListController.php:78](../../Modules/Contract/app/Http/Controllers/ContractOptionListController.php:78)
  — `cache()->remember()`, with `CACHE_MINUTES` as a class constant. The driver is already enabled:
  `CACHE_DRIVER=file`.

**Result — built and measured 2026-08-21, not an estimate.**

| | before | after |
|---|---|---|
| menu composer, cache miss | 108 queries | **7** |
| menu composer, cache hit | 108 queries | **0** |
| whole request, cache miss | 141 queries | **40** |
| whole request, cache hit | 141 queries | **33** |

One cache entry per generation per role, so each role pays its own 7 the first time. Verified in the
browser: the sidebar renders with 22 links and every counter is unchanged. Invalidation checked end to end
— flush bumps the version, the model hook fires on a save, the next request misses and the one after
hits. Row 8 of [report.md](measurements/report.md).

**No `.env` switch, and no fresh "before" measurement.** The dev's call: the old figures are already in
[report.md](measurements/report.md) and `storage/logs/perf-2026-08-21.log`, so they are copied across rather
than measured again. Caveat that the report row must carry: absolute milliseconds drift about 3x between
sessions on this machine, so the ms comparison is indicative and each number names its session. **The query
count does not drift**, so 108 → 0 stands on its own.

**Applied already, and separately from the caching:**
[horizontalMenu.blade.php:8](../../resources/views/layouts/sections/menu/horizontalMenu.blade.php:8) read
`$menuData[1]->menu` with no guard, so switching the layout to horizontal would have failed every page.
It now has the same `@if($menuData[1]->menu ?? false)` guard that
[verticalMenu.blade.php:59](../../resources/views/layouts/sections/menu/verticalMenu.blade.php:59) already
had. One line in, one `@endif` out, compile-checked. Not performance work — a latent break found while
reading.

**Left for a later effort, deliberately.** Removing the horizontal layout altogether is 9 items — 6
layout files, the top-menu view and its static JSON, three config settings, the `menu_type` enum, and the
menu admin screen. None of it is performance work, and the layout files are stock template files a template
update would restore. Not this map's.

---

## 8c. Step 11 done — the old dashboard is deleted and the new one is on the live URL

Applied 2026-08-21. Report rows 10, 12 and 13.

**Routes.** `GET ''` and `POST 'filterDash'` now reach `dashboardSummary()`. The temporary
`dashboard-summary` pair is deleted. The URLs are the originals, so bookmarks and the `menu_configs`
`"url": ""` entry keep working. The POST is renamed `contractDashboard.filter`, which fixes a latent bug:
both old routes carried the name `contractDashboard`, so `route('contractDashboard')` resolved to whichever
Laravel registered last. **A cosmetic gap closed for free** — the sidebar highlights by route name and
the menu JSON's slug is `contractDashboard`, so `Dashboard` is the active item again.

**Deleted.** `dashDetails()` (210 lines), `viewDashboard1.blade.php`,
`app/Console/Commands/CompareDashboardCounters.php` and its `dashboard:compare-counters` signature, three
unused imports, the old `actionableItemCounts()`/`actionableApprovalRows()` pair and the
`?oldApprovalStatus=1` flag. The `x` suffixes are gone: the plain-text versions now hold the plain names.
The controller is **639 lines, down from 948**.

**The one judgement call in it.** The plain-text counter returns **zeros** against ciphertext, so
`?oldApprovalStatus=1` was the only way back if a deployment ran the code before
`contract:convert-approval-status --apply`. The dev chose to delete it anyway. The protection is now
procedural: [DEPLOYMENT.md](../../DEPLOYMENT.md) section 1, plus the narrow migration **throwing** rather
than letting the order slip quietly.

**`encryptStringx()` keeps its `x`**, the dev's call. `encryptString()` has 525 call sites and ignores its
second argument; `encryptStringx()` has 58 and the second argument names the `table.column`. Merging them
would silently convert three unrelated tables.

**`?withoutActionableItems=1` stays.** It is the only way to reproduce report row 3, and it costs nothing.

---

## 8d. Change H — the two page-size cuts taken from ticket 22

Applied 2026-08-21, dev's call: **customizer off and ApexCharts lazy-loaded**. The other three cuts in
[ticket 22](issues/22-reduce-page-size.md) — fa-brands/fa-regular, the language switcher, the Tabler
font subset — were not chosen and are not done. **Neither of these needed a rebuild.**

| | before | after |
|---|---|---|
| requests before the load event | 56 | **36** |
| bytes before the load event | 2,908,591 | **2,360,704** |
| bytes after the load event | 0 | 491,404 |

**548 KB off the critical path**, 20 fewer requests.

**Customizer off** — `hasCustomizer => false` in [config/custom.php](../../config/custom.php). Zero
customizer files fetched, and the layout stops resolving 8 stylesheet paths. **Users lose light/dark and
theme switching**, and a saved `localStorage` choice stops applying. Accepted in ticket 22.

**ApexCharts lazy-loaded** — removed from the eager `vendor-script` bundle in
`viewDashboardSummary.blade.php` and fetched by an `IntersectionObserver` with 200px of lead time, through
`Vite::asset()` so it follows the manifest and needs no hardcoded hash. It now starts at **4,825 ms**,
after the load event at 4,566 ms, and both charts still render identically. The `.scss` stays eager: it is
small and it styles the container before the chart arrives.

Safe to defer past `cards-statistics.js`, which is also loaded on this page: all 12 of its chart elements
are absent from this view and every call is null-guarded. **Which also means it parses about 1,300 lines to
do nothing here** — not fixed, and worth its own look.

**One trap, written down because it cost a broken page.** The first attempt put the text `@vite` inside a
JavaScript comment in the blade. Blade compiles directives inside `<script>` as well, and a bare `@vite`
with no parentheses becomes a zero-argument call — the page died with
`Too few arguments to function Illuminate\Foundation\Vite::__invoke()`. Never write an `@`-directive name
in a comment in a blade file.

---

## 9. Behaviour: what is preserved, and the two things that change

### Preserved exactly

- **Contracts with no internal party stay invisible.** Today they never reach
  `$contract->applicable = true` ([Controller.php:250](../../app/Http/Controllers/Controller.php:250))
  and are excluded from every counter; 300 such contracts were seeded specifically to test this. Whether
  that is intended or a bug is **not decided** — it is recorded as a known divergence for a separate
  decision, and this work must not change what the numbers say.
- **The `Terminated` casing.** The PHP switch is case-sensitive on `'Terminated'`; MySQL's collation is
  not. Keeping the fold in PHP (§3) preserves today's count byte for byte. Every `_ci` collation treats
  `Terminated` and `terminated` as the same value — which is exactly why the fold stays in PHP, and
  choosing a case-insensitive collation in §6 does not reopen it.
- **Super Admin's empty branch set means "everything".**
  [Helpers.php:323](../../app/Helpers/Helpers.php:323) reads an empty reachable-branch list as "see
  all"; SQL's `IN ()` means the opposite. **The role is checked in PHP before the query is built** — a
  Super Admin simply gets no branch condition added. "No filter" and "filter by every value" stay
  different code paths, so the dangerous case cannot be written by accident.

### Deliberately changed

- **The `filterByLocationReport` cookie is dropped from the dashboard.**
  [Controller.php:167](../../app/Http/Controllers/Controller.php:167) clears it on any non-reports
  controller, but `setcookie()` only takes effect next request while `$_COOKIE` at
  [:280](../../app/Http/Controllers/Controller.php:280) still holds the old value — so the dashboard
  inherits the reports page's location filter on exactly one arbitrary request after leaving a report,
  then never again. Nobody designed that. The dashboard has its own filter (`$request->contractlocs`).
  Dropping it also keeps the new visibility scope free of superglobal state, which is what makes it
  reusable.
- **"My Actionable Items" numbers will change.** They are silently zero today because of the 1,000-id
  bug. This is the one expected difference.

### How it is proved correct

No test suite exists, and the preserved behaviours above rest on the new numbers matching the old ones
exactly.

**A throwaway artisan command** runs the old counting loop and the new query side by side over the seeded
3,018 contracts, across several users and roles, and prints any of the 15 counters that differ. Deleted
once the change ships. Expect exactly one deliberate difference — the "My Actionable Items" numbers.
Everything else must match, including the 300 no-party contracts and the `Terminated` casing.

Real tests are the right long-term answer but need a test setup this repo does not have.

---

## 10. Order of work

Set by the dev: **fix the N+1s and the throwaway page payload first. Measure. Only then index. Load
testing at bigger row counts comes last.** Growing the dataset before those two are fixed measures the
wrong thing.

| # | step | depends on | can ship alone |
|---|---|---|---|
| 0 | ~~**Agree the names**~~ — **done 2026-08-20, see [names.md](names.md)** | nothing | — |
| 1 | ~~**Change E, part 1**~~ **done** — `hot` already gone, `myRTLSupport => false` | nothing | **yes** |
| 2 | ~~**Change A**~~ **done** — `dashboardSummary()` + `ContractVisibilityQuery` + `viewDashboardSummary` | 0 | **yes** |
| 3 | ~~**Measure**~~ **done** — 4,827-4,974 ms controller -> **36 ms**, 5,654 -> **127** queries | 2 | — |
| 4 | ~~**Change B**~~ **done** — `actionableApprovalRows()` + `actionableItemCounts()` | 2 | no |
| 5 | ~~**Measure again**~~ **done** — counter costs **~336 ms** and 7 queries | 4 | — |
| 6 | Migration 1 — index `approval_contracts.contract_id` — **file written, awaiting the dev's approval to run** | 4 | no |
| 7 | ~~**Change F**~~ **done** — `contracts/option-lists`, HTML 71,294 -> 61,064 bytes | 0 | **yes** |
| 8 | ~~**Change E, part 2**~~ **done** — `vite.config.mjs`, build verified to a throwaway outDir | nothing | **yes** |
| 9 | Migration 2 — convert `contract_party_data` — **file written, awaiting the dev's approval to run** | nothing | **yes**, needs a window |
| 10 | Consolidate the duplicated asset trees | 8 | yes |
| 11 | Delete each old function, once its new one is proven (§15) — **see the checklist below the table; ticket 17's pair is already swapped, only the delete is left** | 3, 5 | yes |
| 12 | ~~**[Ticket 17](issues/17-plain-columns-experiment.md)** — plain columns instead of encrypted~~ **done 2026-08-21** — `approval_status` only; `actionableApprovalRowsx()` + `actionableItemCountsx()` + `leadingStatusByGroup()`, `encryptStringx()`, one command, one migration | 5 | **yes**, ran last |

**Step 0 is not optional.** Nothing is rewritten in place, so every step below it needs its new name
agreed first — see §15 and [ticket 19](issues/19-new-function-names.md).

### What step 11 still has to delete, and what it must not go looking for

Swapping a new function in as the default and **deleting** the old one are two separate acts. A pair
that has been swapped is not done — the old half is still in the file. This list says which is which,
so step 11 does not hunt for something already gone.

| old half | state | what step 11 does |
|---|---|---|
| `dashDetails()` + `viewDashboard1.blade.php` | still the live `GET ''` route | move the route across, then delete |
| the `$approvalsArr` blade loop, [viewDashboard1.blade.php:305](../../Modules/Contract/resources/views/dashboard/viewDashboard1.blade.php:305) | still there, inside the old view | goes when the old view goes |
| `actionableApprovalRows()` + `actionableItemCounts()` | **swapped 2026-08-21 — no longer the default.** `actionableItemCountsx()` runs unless `?oldApprovalStatus=1` is passed | delete both, and delete the `?oldApprovalStatus=1` branch in `dashboardSummary()` with them |

**`?plainApprovalStatus=1` no longer exists.** It was the flag that opted *in* to the new counter while
it was being proved. Now that the new one is the default, the flag that remains is
**`?oldApprovalStatus=1`**, which opts back *out*. Anything referring to `plainApprovalStatus` is
describing the 2026-08-21 measuring session, not the code.

**Keep `?oldApprovalStatus=1` until the conversion has run in production.** `actionableItemCountsx()`
returns zeros against a table that still holds ciphertext, so until
`contract:convert-approval-status --apply` has run on a database, that flag is the way back. Delete it
in the same step as the functions.

**Every step from 1 to 10 puts a row in [report.md](measurements/report.md)** — old number, new number,
same session, plus a remark for any side effect. That is what makes the biggest win visible.

Steps 1, 7, 8 and 9 are independent of each other and of everything else. Step 12 runs last on purpose:
its win cannot be told apart from the rewrite's if it runs earlier.

---

## 11. Expected outcome

| change | expected effect |
|---|---|
| Delete `public/hot` + RTL off | **~18 s** off wall clock. No effect on TTFB. |
| Query-layer rewrite | **~11.9 s** of the 12.6 s controller time. Queries 5,654 → single digits. |
| Actionable-items counter in PHP (§4) | Keeps the six numbers correct. ~~**Costs ~0.5 s local / ~2 s expected at 60,000 rows, every load.**~~ **Removed 2026-08-21 by [ticket 17](issues/17-plain-columns-experiment.md)** — `approval_status` is plain and indexed, the counter costs ~380 ms, and the ~0.5 s / ~2 s figures were about double the truth. See §4. |
| Index on `approval_contracts.contract_id` | Stops the approvals join reading the whole table as rows pile up. |
| AJAX dropdowns | ~15 % payload cut, two queries off the critical path. **Not seconds.** |
| `vite.config` | No performance effect. Without it nobody can change a stylesheet. |
| `contract_party_data` conversion | No measurable dashboard effect today. Removes an engine/charset mismatch and makes the join indexable. |

Against the §2 targets: TTFB at N≈3,000 should land **under 2 s** — 14.4 s minus ~11.9 s of controller
time leaves ~2.5 s, of which ~1.25 s is bootstrap that this spec does **not** address.

**Reaching "good" therefore needs the per-request overhead handled too.** That is honest, not a caveat
buried at the end: this spec takes the dashboard from unacceptable to roughly tolerable, and the
remaining second is a separate piece of work.

---

## 12. What was deliberately not done

- **`availableContracts()` is not rewritten.** 55 call sites — 52 the identical shape
  `availableContracts($x, true)`, plus 3 count-by-key variants and 1 `partyData` variant — across
  ContractController (28), ContractReportsController (23), Tasks (2), Dashboard (2), Import (1),
  Export (1). The function change would be small; **the verification is the project**. Those call sites
  consume decorated objects (fields decrypted in place, `catgoery_id` overwritten with a name,
  `contract_type` overwritten with a name, plus seven more added properties), 23 of them report and
  export paths where a wrong number is worse than a slow page, with no test suite to catch it. Scoped as
  a follow-on effort framed as **extract the visibility predicate, leave the decoration loop alone**.
- **The ~1.1 s of per-request overhead.** Attributed but not fixed: `opcache` not loaded (810 files
  recompiled per request), no config or route cache, 51 providers booting per request, and
  `MenuServiceProvider`'s `View::composer('*')` owning **all 92 overhead queries**. Every candidate fix
  affects the whole application, not the dashboard, so the scope question — fix here or hand off — is
  itself undecided. See [ticket 11](issues/11-per-request-overhead.md).
- **Caching the 15 stage counters.** Nearly closed by measurement: they run in **13–17 ms** with no
  indexes at N=3,018, so there is nothing left for a cache to save. Recorded so the decision is stated
  rather than silently skipped.
- **The missing `create_contracts_table` migration.** Accepted debt, written down: nobody can build a
  fresh environment from migrations alone, because `contracts` has no create migration and every
  contracts migration is a `Schema::table` guarded by `Schema::hasTable`. A new environment needs a
  schema dump.
- **Debug tooling.** Debugbar is being added behind three locks, but it ships separately and this spec
  does not depend on it. See [ticket 16](issues/16-debug-tooling-decision.md). The local `.env` now
  carries `DEBUGBAR_ENABLED=true` and `DEBUGBAR_OPEN_STORAGE=false`, both documented with their
  production values in [.env.example](../../.env.example).
- **Plaintext shadow columns.** Ruled out by the dev 2026-08-20. The two columns may instead stop being
  encrypted altogether, which is [ticket 17](issues/17-plain-columns-experiment.md) and runs last.
- **Anything in `goalapp_apollo`.** Never changed. What changes there *would* help is written up as a
  note for a later effort — [ticket 18](issues/18-goalapp-apollo-note.md), read-only.
- **Whether the 110-column `contracts` table and its two coexisting encryption schemes need addressing.**
  Adjacent debt; not shown to affect this page.
- **How to confirm production has the same symptom**, given production data is off limits.

---

## 13. Deployment notes

- Production `.env` must carry `APP_ENV=production`, `APP_DEBUG=false`, `LOG_CHANNEL=daily`,
  `LOG_LEVEL=warning` — the values in [.env.example](../../.env.example). Not a task for this work; the
  dev has confirmed production variables are mapped. `LOG_CHANNEL=stack` resolves to `single`, which
  writes one `laravel.log` that **never rotates** — hence `daily`.
- Migration 2 locks `contract_party_data` for a full rebuild. It needs a window.
- **Debug bar variables must be set on the production server:** `DEBUGBAR_ENABLED=false` and
  `DEBUGBAR_OPEN_STORAGE=false`. The local values and the production values sit side by side in
  [.env.example](../../.env.example). The dev copies the keys across and sets them.
- Any CLI work touching the encrypted approval columns is host-dependent: the key is derived from the
  first word of `HTTP_HOST` (§5).
- **`composer.lock` is invalid JSON** — 15 conflict markers — so every composer command refuses to run.
  Any deployment step that invokes composer will fail until it is repaired. Worse than recorded
  elsewhere: **neither side of the conflict matches what is installed.** The `nwidart/laravel-modules`
  hunk pits v9.0.6 against v11.1.4 while **10.0.6 is installed**, against a `composer.json` constraint of
  `^9.0`. `vendor/composer/installed.json` (161 packages, `laravel/framework v10.48.29`) is the only
  truthful record of this install. Standing rule from the dev: **new packages may be added; nothing
  already installed gets upgraded.** See [ticket 16](issues/16-debug-tooling-decision.md).
- Seeding or any CLI work touching encrypted data needs the hostname:
  `HTTP_HOST=apollo.contracts.legality:8888 php artisan ...`.

---

## 14. Sanity check

Every claim above traces to a measurement or a `file:line`. Where a number is extrapolated rather than
measured (§6 build times), it says so. Where the expected win is smaller than originally assumed
(§8 dropdowns), it says so. Where this spec does not reach the target on its own (§11), it says so.

The one thing a reader must not take on trust: **absolute milliseconds on the dev machine vary about 3×
between sessions.** Judge this work by query counts and proportions, not by the clock.

---

## 15. New functions beside old ones, never in place

Standing rule from the dev, 2026-08-20, written into [CLAUDE.md](../../CLAUDE.md).

**Nothing here is rewritten in place.** Every improvement is a new function next to the old one, so both
can run on the same page against the same data and be compared, and the old one is deleted only once the
new one is proven. Deletion is step 11 in §10, not part of the change that replaces it.

Names follow PSR-1 / PSR-12 as this repo already does — **class names `StudlyCaps`, method names
`camelCase`, constants `UPPER_SNAKE_CASE`, plain procedural functions `snake_case`**. Then:

- Old name good -> the new one is the old name plus **`x`**.
- Old name bad — it does not say what the function does -> **suggest a better name** and get it approved
  before writing code.
- One function, one concern. Shared code is pulled out into its own function, not copied.
- In doubt, ask.

**Agreed so far:** `ContractDashboardController::dashDetails()` -> **`dashboardSummary()`**. The old name
says nothing; the method builds the dashboard's summary counters.

**All the names are agreed — 2026-08-20, [ticket 19](issues/19-new-function-names.md) — and the record
is [names.md](names.md).** One file: the new method's own routes, the shared visibility class
`ContractVisibilityQuery`, the counter fold, the actionable-items counter, the dropdown endpoint, the
comparison command `dashboard:compare-counters`, and the trigger for deleting an old function. Each row
says which rule applied and why. **Change a name there and nowhere else** — this spec does not repeat
them.

Two calls made in that ticket that the rest of the spec depends on: both versions are reached by
**separate routes** (no request flag, which could serve new behaviour on the production URL by
accident), and the pre-existing duplicate `contractDashboard` route name is **left alone**.

---

## 16. One measurement report

**Every change in §10 puts a row in
[measurements/report.md](measurements/report.md).** One file, one table: old number, new number, how it
was measured, and a remark for any side effect. One file so the change that bought the most is obvious at
a glance. Never a second report file.

Old and new are measured **on the same page, the same data and in the same session** — absolute
milliseconds on this machine vary about 3× between sessions (§2, §14), so an old and a new number taken
hours apart mean nothing. Query counts do not drift and are the preferred measure.
