# CONTEXT.md

Words we keep, and what they mean in plain language.

Rule: if a plain word works, use the plain word. A term earns a place here only when it is
exact and there is no short plain version. Add the term the first time you use it.

## Database

**Contracts database** — `apollo_contracts_expense`. The only database this project touches.

**`goalapp_apollo`** — a different tenant's database on the same MySQL server. Off limits.
Never read, never write, never seed.

**Migration** — a PHP file in `database/migrations/` that changes the database shape. Has an
`up()` that makes the change and a `down()` that undoes it. The only allowed way to change
the database.

**Backfill** — filling in a new column for rows that already exist. A new column starts
empty; old rows need it filled in by a script before anything can rely on it.

**Index** — a lookup table the database keeps on the side so it can find rows without reading
every row. No index means the database reads the whole table every time.

**Full scan** — the database reading every row in a table to answer one question. Slow, and
gets slower as rows pile up.

**Bound value** — a value PHP sends to the database with a query. `where id = ?` sends one bound
value. `whereIn('id', [1,2,3])` sends three. On this stack a query with 1,000 or more bound values
silently returns no rows, so a list of ids is never sent as bound values.

**Subquery** — a query written inside another query. The database runs both and the ids never leave
it, so nothing is bound and the 1,000-value fault cannot happen.

**Collation** — the rule the database uses for sorting text and for deciding whether two bits
of text count as the same. It is why `Terminated` and `terminated` match in SQL but not in PHP.
This project uses character set `utf8mb4` and collation `utf8mb4_unicode_ci` — it works on both
MySQL 8 and MariaDB, ignores upper and lower case, and is already what `contracts` and
`approval_contracts` use.

**Mixed collations** — two text columns with different collations cannot be compared cleanly. The
database either refuses with an error or stops using the index. This is why new columns use the same
collation as the table they sit in.

**Engine** — how a table stores its rows. `InnoDB` is the modern one. `MyISAM` is the old one
and locks the whole table while it is being changed. `contract_party_data` is still MyISAM.

**Online change** — a table change the database can make while the app keeps using the table.
The opposite needs a window where nobody uses the app. InnoDB can add an index online; MyISAM
cannot.

## The dashboard problem

**N+1** — one query to get a list, then one more query for every single item in that list. If
the list has 3,000 items, that is 3,001 queries. This is the main thing making the dashboard
slow.

**`whereIn` 1000-parameter bug** — on this MariaDB, asking `WHERE id IN (...)` with 1,000 or
more values gives back **zero rows**, with no error. Under 1,000 works fine. Live bug. Found in
[ticket 12](.scratch/contracts-dashboard-perf/issues/12-approvals-empty.md). It has its own effort
now: [.scratch/wherein-1000-bug/spec.md](.scratch/wherein-1000-bug/spec.md). Four known places, and
110 `whereIn` calls nobody has checked.

**Menu composer** — a piece of code in `app/Providers/MenuServiceProvider.php`. It fills in the
side menu and the top menu. Laravel runs it one time for each view on the page. The dashboard
has 15 views, so it runs 15 times, and it reads the same menu rows every time.

**View** — one Blade template file. One page is made of many views: the layout, the menu, the
footer, and each panel on the page.

**Aggregate query** — one query that asks the database to count things and hand back the
totals, instead of handing back every row for PHP to count.

**`GROUP BY`** — the SQL for "give me one row per distinct value, with a count".

**Hydrating** — PHP turning a database row into a full object in memory. Costs time and
memory per row. The dashboard hydrates 3,000 objects and then reads none of them.

**Global scope** — a rule bolted onto the `Contract` model that silently rewrites every query
made through it. `Contract` has one that forces `select('*')`, so asking for 5 columns still
loads all 110.

**Query builder** — `DB::table('contracts')`. Talks to the database directly, skipping the
model. Global scopes do not apply to it.

**TTFB** — time to first byte. How long the browser waits after asking before the server
sends anything back. Our main number.

## Encryption

**Encrypted at rest** — the value stored in the database is scrambled. You must load it into
PHP and unscramble it before you can read it.

**Different every time** — the same text encrypted twice gives two different scrambled
results (AES-128-CBC with a random IV). So you cannot search or match on the scrambled value
in SQL. Ever. No index helps.

**Shadow column** — a second, plain-text column sitting next to an encrypted one, holding
just the part we need to search on. Lets SQL do the filtering.

## Built assets

**Vite** — the tool that prepares the CSS and JavaScript for the browser. It reads the source files
and writes finished files.

**Content hash** — a short code in a built filename, like `core-7_a25xA8.css`. Vite works it out
from the bytes inside the file. Change one byte and you get a new code, so you get a new filename.

**`manifest.json`** — the index Vite writes. It maps a source name to the built filename with its
hash. `@vite('.../datatables-bootstrap5.js')` in a Blade file has no hash in it, so Laravel must
read the manifest to learn which real file to ask for.

**`build/` and `public/build/`** — two separate 33 MB copies of the built files on this project.
**Laravel reads `manifest.json` from `public/build/`. IIS serves the real files from `build/`.**
They must agree. Nothing keeps them in step automatically.

## Caching

**App cache** — a store the app writes an answer into, so it does not have to work the answer out
again. This app uses `CACHE_DRIVER=file`, so the answers are files in `storage/framework/cache/`.

**Cache hit** — the answer was in the store. No database query.

**Cache miss** — the answer was not in the store. The app works it out, runs the queries, and
saves the answer for next time.

**Cache key** — the name the answer is filed under. Two users who must see different answers need
different keys. The menu is filed by role.

**Memo** — the app keeps an answer in memory for the rest of one request. It is not the app cache:
it disappears when the request ends, and it never touches a file or the database. Use it when the
same answer is asked for many times in one request.

**View composer** — a piece of code Laravel attaches to a view. Laravel runs it each time that view
is built. `View::composer('*')` attaches it to **every** view, so it runs one time for each view on
the page.

**Clear the cache** — throw the saved answer away, so the next request works it out again. Needed
when the data behind the answer changes.

**Memo** — the app keeps an answer in memory for one request only. The second call gets the
answer from memory. Nothing is written to disk. The memory goes away when the request ends.

**Call site** — one place in the code that calls a function. `userInfo()` has 86 call sites.
Each one runs its own query today.

**Role** — the user's job type, held in the session as `contractSessionUserRole`. It comes from the
legacy login app as `$_SESSION['logrole']`. Values seen in the code: `User`, `Manager`, `Admin`,
`Super Admin`, `Legal`, `Marketing Manager`.

## Logging

**`LOG_LEVEL`** — a setting in `.env` saying how noisy the logs are. Anything below the level
you set is thrown away. Order, quietest last: `debug`, `info`, `notice`, `warning`, `error`,
`critical`, `alert`, `emergency`. Set it to `warning` in production and all the `Log::debug()`
lines go quiet on their own.

**Monolog** — the logging engine Laravel already ships with. Nothing to install.

**Log channel** — where the log goes. `single` = one file that grows forever. `daily` = a new
file each day, keeping 14 days. Use `daily` on a server.

**`APP_DEBUG`** — must be `false` in production. It hides error details from users and also
hides the debug bar.

## Model hooks

**`saving` hook** — code on a model that runs automatically every time a row is saved,
whichever part of the app is doing the saving. Used here to keep a shadow column filled
without patching 43 places.

## This effort

**Wayfinder map** — the plan for this whole effort, at
[.scratch/contracts-dashboard-perf/map.md](.scratch/contracts-dashboard-perf/map.md). Each
open question is a ticket in `issues/`.

**Ticket** — one question to settle, small enough for one work session.

**Spec** — the end goal here. A written plan someone else can build from. This effort writes
the plan; it does not build it.

**Visibility rule** — who is allowed to see which contract. Today it is department access
plus "has an internal party in a branch you can reach". Buried in
`availableContracts()`.

**`availableContracts()`** — the shared function in `app/Http/Controllers/Controller.php` that
applies the visibility rule. Called from 55 places. Also does a lot of extra dressing-up work
the dashboard throws away.

**My Actionable Items** — the card on the dashboard showing how many contracts are waiting on
you. Its numbers come from `$stusMy`.
