# What changes in `goalapp_apollo` would help the contracts dashboard?

Note only. Nothing was changed. Read-only work for [ticket 18](../issues/18-goalapp-apollo-note.md),
measured 2026-08-21 on the local MariaDB 10.4.24.

---

## The headline

**The dashboard never touches `goalapp_apollo`.** Not one query. So no change there makes today's
page faster.

But `goalapp_apollo` is the same application's data on an older shape of the same schema — it is what
a real client database looks like. So the note is still worth having, for a different reason: **it
shows what the same page will cost when it runs against a real client database instead of our
seeded local one.** That answer is "much more than we measured", and the list below says why.

---

## 1. Does this page's SQL touch `goalapp_apollo`? No.

Three things checked, all negative:

| checked | result |
|---|---|
| [config/database.php](../../../config/database.php) | **one** MySQL connection only, `mysql`. `sqlite`, `pgsql`, `sqlsrv` are the stock Laravel stubs, unused. There is no second connection to add a database name. |
| `$connection` on any model | **none**. No `protected $connection` anywhere in `app/`, `Modules/`, `config/`, `database/`, `routes/`. |
| `DB::connection(...)` / `->connection(...)` anywhere | **none** in application code. |
| a database name written into raw SQL | **none**. The only raw SQL on this page is the recursive `GeographicalHierarchy` query in [Helpers.php:323](../../../app/Helpers/Helpers.php:323) and it names no database. The only files with `goalapp_` in them are notes, a CLI safety list, and one stray `.sql` file in a views folder that nothing runs. |

`.env` has `DB_DATABASE=apollo_contracts_expense`. Every query on the page goes there and nowhere
else. `goalapp_apollo` is reachable from the same MySQL login, which is why the "do not touch" rule
exists — but the application never crosses over on its own.

**So: nothing in `goalapp_apollo` is on the dashboard's path today.** Points 2 and 3 of the ticket
were asking whether the cheap fixes sit on the wrong side of the line. They do not.

## 2. The user, role and branch lookups — all local, none blocked

[Helpers.php:299-393](../../../app/Helpers/Helpers.php:299) reads four tables to build the
reachable-branch list. All four live in `apollo_contracts_expense` on the default connection:

| table | rows | engine | index today | plan for its lookup |
|---|---|---|---|---|
| `usercredential` | 1,736 | MyISAM | `PRIMARY` only | `WHERE authtoken = ?` → **full scan, 1,736 rows** |
| `addusers` | 1,607 | InnoDB | `PRIMARY` only | `WHERE Customer = ? AND <decrypted UserName> = ?` → **full scan, 1,607 rows** |
| `GeographicalHierarchy` | 146 | MyISAM | `PRIMARY` only | `id IN (...)` uses `PRIMARY`; the recursive `FIND_IN_SET(parent, @pv)` walk cannot use any index |
| `branch` | 86 | InnoDB | `PRIMARY` only | `city IN (...) OR Cluster IN (...)` → **full scan, 86 rows** |

Copies of all four exist in `goalapp_apollo` with the **same missing indexes** and near-identical row
counts. So the same scans happen for a real client. But because these tables are in *our* database,
indexing them is ordinary in-scope work — no rule stands in the way.

Worth saying plainly: **the `addusers` lookup can never be indexed as written**, because the `WHERE`
decrypts the column on every row (`decrypt_datas('UserName', 'AddUsers')`). An index on `UserName`
would be ignored. Only `Customer` is indexable, and it narrows 1,607 rows to a few hundred at best.

## 3. The menu composer — also all local

`menu_configs` and `admin_settings` are both in `apollo_contracts_expense` (and, again, copied into
`goalapp_apollo` with the same shape). So the 92 overhead queries from
[ticket 11](../issues/11-per-request-overhead.md) are entirely ours to fix. **The cheapest fix in the
whole application is not behind the "do not touch" line.** That was the ticket's worry and it is
unfounded.

No index would help there anyway: `menu_configs` has **7 rows** and already has
`UNIQUE(menu_type, role)`. Scanning 7 rows 65 times is not an index problem, it is a caching problem.
Same for `admin_settings` — 12 rows, already unique on `admin_key`.

## 4. The 1,000-bound-parameter bug applies to `goalapp_apollo` too

`in_predicate_conversion_threshold` is **1000** both globally and per session. It is a **server**
setting, so it applies to every database on this MySQL instance, `goalapp_apollo` included. No schema
change anywhere can fix it.

`goalapp_apollo` holds **2,886 contracts, 2,783 of them `status = 1`**. Any user who can see a
thousand or more of those is on the broken path. So a client on that data is almost certainly seeing
the silent zero-rows today, exactly as [ticket 12](../issues/12-approvals-empty.md) described.

The fixes stay the same and none of them touch a database: restructure to `JOIN`/`EXISTS`, or set
`in_predicate_conversion_threshold = 0` on connect, or chunk. The new
[ContractVisibilityQuery](../../../Modules/Contract/app/Services/ContractVisibilityQuery.php) already
builds no id list, so the page is out of reach of the bug once it is the only path.

---

## 5. The list — biggest expected win first

Each item: what it buys, what it costs, and how much to trust the number.

### 1. Index `contract_party_data` and move it to InnoDB — **7× on the visibility query, and it stops the contracts scan**

`goalapp_apollo.contract_party_data` is **MyISAM, latin1_swedish_ci, 5,970 rows, `PRIMARY` only**, and
its two join columns are `TEXT`. Ours is now InnoDB / utf8mb4_unicode_ci / `varchar(32)` with two
indexes (report row 7).

Same query, `EXPLAIN` side by side:

| | `goalapp_apollo` (no index) | `apollo_contracts_expense` (2 indexes) |
|---|---|---|
| party rows read | **5,970** (full scan, materialised) | **140** (range scan on `idx_cpd_type_location`) |
| contracts rows read | **all of them** (`type: ALL`) | **1 per match** (`eq_ref` on `PRIMARY`) |

Timing, warm cache, same session, same query, `IGNORE INDEX` standing in for "before" so the 3×
between-session drift does not apply:

| | ms |
|---|---|
| without the party indexes | **33 ms** |
| with them | **4.8 ms** |

**About 7× on this query shape.** The important half is not the milliseconds — it is that the index
flips the plan so the **contracts table stops being scanned at all**. Without it every dashboard load
reads the whole contracts table; with it, contracts is reached one row at a time by primary key.

- **Cost:** a full table rebuild (MyISAM → InnoDB, charset conversion, two indexes). Needs a
  maintenance window; the table cannot be written during it. Index write cost is negligible —
  ~6,000 rows, ~290 KB of index.
- **Confidence: high.** Measured on both databases, and the plan change is visible in `EXPLAIN`.
- **Note:** the local number was only **1.3×** in report row 7 because that measurement used a
  different, narrower query. The 7× here is the visibility `EXISTS` the new dashboard actually runs.
- **Schema difference to watch:** `goalapp_apollo.contract_party_data` has **7 columns**; ours has
  **10** (`vendor_code`, `party_address`, `contact_details` are extra). The two schemas are not the
  same version, so the conversion script from
  [database/manual/001-...sql](../../../database/manual/001-contract-party-data-innodb-utf8mb4.sql)
  must be re-checked against the client's real column list before it is run.

### 2. Something is wrong with the size of `goalapp_apollo.contracts` — **9× on any scan of it**

| | rows (`status = 1`) | table on disk | free space | warm `COUNT(*)` |
|---|---|---|---|---|
| `apollo_contracts_expense.contracts` | 3,018 | **6.5 MB** | 4 MB | **54 ms** |
| `goalapp_apollo.contracts` | 2,783 | **71.6 MB** | 5 MB | **480 ms** |

Nearly the same number of rows, **11× the disk, 9× the time**. And the actual text content is small —
summing the eight biggest text columns over all 2,886 rows gives **2.7 MB**. So roughly 69 of the
71.6 MB is not content.

The table is InnoDB, `ROW_FORMAT=Dynamic`, **105 columns**, most of them `TEXT` or `LONGTEXT`. The
likely cause is InnoDB pushing long columns onto their own 16 KB pages — 2,886 rows × one off-page
page each would be about 46 MB, which is the right order. If that is what it is, an `OPTIMIZE TABLE`
will **not** shrink it; only narrowing the columns would.

- **What it buys:** if the space really is reclaimable, up to **9×** on every query that scans
  contracts. Once item 1 is in place the dashboard stops scanning contracts, so most of this win goes
  away — which is another reason to do item 1 first.
- **Cost:** unknown until the cause is known.
- **Confidence: the measurement is high, the cause and the fix are low.** Do not act on this. Test it
  on a **copy** of the table first and see whether a rebuild actually reclaims anything.

### 3. Index `approval_contracts.contract_id` — **big for us, worth almost nothing there**

Ours is 12,816 rows and the index made the approvals join **114 ms → 23 ms, 5×** (report row 4).

`goalapp_apollo.approval_contracts` has **21 rows.** An index on 21 rows changes nothing measurable.

- **Cost:** trivial either way.
- **Confidence: high**, and the honest reading is *this index is a local-dataset win, not a
  production one*. Real client data has almost no approval rows. Worth adding anyway, because it
  costs nothing and the table will grow — but do not expect the 5× to appear for a client.

### 4. Index `contract_tasks.contract_id` — **nothing to measure yet**

`contract_tasks` is **empty (0 rows) in both databases** and has `PRIMARY` only. The dashboard's four
task counters therefore count nothing, in both.

- **What it buys:** nothing today. It becomes item-1-sized once the table has real rows.
- **Confidence: high on the emptiness, none on any future number.** This is also the honest gap
  already recorded in the measurement report — the task query shape has never been tested against
  real rows.

### 5. Index `usercredential.authtoken` — small and steady

Read once per request. Turns a 1,736-row scan into one row.

- **What it buys:** a few milliseconds, every request, every page. Not just the dashboard.
- **Cost:** MyISAM table, so a rebuild. One small index. `authtoken` is not unique-safe to assume, so
  a plain index, not a unique one.
- **Confidence: medium.** The plan change is certain (`type: ALL` → `ref`); the milliseconds saved are
  too small for this machine to measure reliably at 1,736 rows.

### 6. Index `addusers.Customer` and `branch.city` / `branch.Cluster` — smallest of the lot

86 and 1,607 rows. Both currently scan.

- **What it buys:** almost nothing at these row counts. Listed so the list is complete, not because it
  is worth doing.
- **Confidence: high that it is not worth doing.** `addusers` also cannot be indexed on the column
  that matters, because the `WHERE` decrypts it (see point 2 above).

### 7. Not on the list, and why

- **An index on `contracts` for the visibility filter.** Not needed. Once
  `contract_party_data` is indexed the optimiser drives from the party table and reaches contracts by
  primary key. Confirmed in `EXPLAIN` on our database.
- **An index on `menu_configs` or `admin_settings`.** 7 and 12 rows, already unique-indexed. The 92
  queries are a caching problem, not an index one.
- **Anything for the 1,000-parameter bug.** It is a server variable and a code shape. No schema change
  reaches it.

---

## What this note is really saying

Two things the dev should take away, neither of which is an index:

1. **No cheap fix is stuck behind the "do not touch" rule.** Everything on the dashboard's path —
   the menu composer, the branch lookup, the party join, the approvals index — lives in
   `apollo_contracts_expense`. The rule costs this effort nothing.

2. **Our local numbers flatter us.** The same page against real client data reads a contracts table
   that is 11× the size and takes 9× as long to scan, joins a party table with no indexes at all, and
   is on the wrong side of the 1,000-parameter bug with 2,783 visible contracts. Where the measurement
   report says a change was worth 1.3×, against a real client database it is worth more; where it says
   5× (the approvals index), against a real client database it is worth nothing, because that table
   has 21 rows.

Nothing here was applied. Nothing here belongs in [spec.md](../spec.md).
