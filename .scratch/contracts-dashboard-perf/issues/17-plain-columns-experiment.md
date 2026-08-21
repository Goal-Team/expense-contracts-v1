# What do we gain if `approval_status` and `username` are just plain columns?

Type: `wayfinder:grilling` · Status: **resolved 2026-08-21** · Assignee: kader (2026-08-21) · Blocked by: nothing · **Last in order — runs after
everything else in [spec.md](../spec.md) §10 has shipped and been measured.**

## Question

Shadow columns are off the table (dev's call, 2026-08-20). The question that replaces them: what if
`approval_contracts.approval_status` and `approval_contracts.username` **stop being encrypted at all**
and become ordinary plaintext columns?

Not shadow copies. The same two columns, holding readable values.

### Why this is worth asking

Both columns are AES-128-CBC with a random IV, so the same value encrypts differently every time.
That makes them unmatchable, unfilterable and unindexable. Everything expensive about "My Actionable
Items" follows from that one fact:

- The counter cannot be a `GROUP BY`. It has to pull rows into PHP and decrypt them.
- Measured: 27,734 values decrypted in **0.49 s** locally (13,867 rows, both columns). At the assumed
  production scale of ~60,000 rows that is roughly **2 seconds on every dashboard load** — see
  [spec.md](../spec.md) §4, which now ships that cost knowingly.
- No index can help, so [ticket 09](09-index-and-migrations.md)'s composite index
  `(approver_email, approval_status_plain)` had nothing to sit on and was dropped with the shadow
  columns.

If the columns were plain, the counter is one indexed `GROUP BY` and the 2 seconds goes to zero.

### Scope, fixed by the dev

**These two columns only.** `apollo_contracts_expense.approval_contracts.approval_status` and
`.username`. Nothing else on the table, nothing on `contracts`, nothing in any other database.
`goalapp_apollo` is not touched — see [ticket 18](18-goalapp-apollo-note.md).

`original_username` stays off limits. It serves another purpose and is not a plaintext fallback.

### What this ticket has to settle

1. **What is actually protected today.** `username` decrypts to an email in 13,866 of 13,867 rows;
   `approval_status` decrypts to one of five words (`Approved`, `Pending`, `approved`, `pending`,
   `rejected`, longest 8 characters). An approval status is not a secret. An approver's work email
   probably is not either — `approval_group_approvers.approver_email` and
   `financial_limit.approval_required_users` **already store approver emails in plaintext in this same
   database**. So the encryption may be protecting nothing that is not already readable one table over.
   Confirm that, name every place the same values already sit in plaintext, and say whether any
   compliance requirement (not just habit) puts them there.
2. **How many read and write sites there are.** 6 controllers, 43 `create` calls, 203
   `approval_status` assignments, and every write goes through the `ApprovalContracts` model — no
   `DB::table()`, no raw SQL ([ticket 08](08-query-layer-redesign.md) verified this). Count the *read*
   sites too: every `decryptString` on these two columns has to stop, or has to survive both formats.
3. **The migration and the one-time conversion.** Column type and length (`varchar(191)` covers the
   longest email at 106 characters and keeps an index inside the old key-length limit; `varchar(20)`
   covers the status), `utf8mb4` / `utf8mb4_unicode_ci`, a working `down()`, and a stateless
   re-runnable conversion script — the rules in [ticket 15](15-approval-backfill-plan.md) still apply
   in full (seatbelt check first, never the `safeDecrypt` pattern, `chunkById(1000)`, never `whereIn`,
   marker on failure, verify every row). The conversion is the same ~2 seconds of work, once.
4. **How mixed data is handled during the switch.** Old rows are ciphertext, new rows plain. Either the
   conversion runs inside the same release as the code change, or reads have to cope with both. Pick
   one and say why.
5. **The measured gain, written into [report.md](../measurements/report.md)** — old number and new
   number, same session, on the same seeded 3,018-contract set.

### What makes this last

It is not on the critical path. The dashboard rewrite ([spec.md](../spec.md) §3) hits its numbers
without it, and the actionable-items counter works today at a known cost. This ticket removes that cost
and nothing else, so it runs after the rest has shipped and been measured — otherwise its win cannot be
told apart from the rewrite's.

## Answer

**Resolved 2026-08-21. Scope cut to `approval_status` alone; `username` stays encrypted. Shipped and
measured — [report.md](../measurements/report.md) rows 8 to 8c.**

### The ticket's own numbers were wrong, and that changed the answer

This ticket and [spec.md](../spec.md) §4 both said **27,734 values decrypted in 0.49 s**, projecting
**~2 s at 60,000 rows**. That assumed both columns for every row. The code does not do that — it
`continue`s before touching `username` unless the status came back `pending`. Measured over the same
13,861-row set: **13,861 status decrypts + 2,127 username decrypts = 15,988 values, 320–334 ms**.

So the expense was **one column, not two**, and it was `approval_status`. That is what made
converting `username` unnecessary: with `approval_status` plain and indexed, SQL filters 13,861 rows
down to the 2,127 pending ones, and `username` is then decrypted for those only. The cost falls
without `username` being touched.

### 1. What is actually protected today — nothing that is not already readable

`approval_status` holds one of three lowercase words. Measured across all 13,867 rows:

| value | rows |
|---|---|
| `approved` | 11,736 |
| `pending` | 2,129 |
| `rejected` | 2 |

Longest is 8 characters. An approval status is not a secret.

`username` decrypts to JSON `{"email":...,"name":...}` — 19 distinct users, longest email 37
characters, longest whole string 106. **The same approver emails already sit in plaintext, in this
same database, in two other tables**: `approval_group_approvers.approver_email`, and
`financial_limit.approval_required_users` (a JSON blob carrying `email` per approver). So the
encryption on `username` protects nothing that is not readable one table over either.

**No compliance requirement.** The dev's call, 2026-08-21: habit, not a rule. Nothing in a client
contract or audit puts it there.

### 2. Read and write sites — and why the reads needed no change at all

**61 write sites** carry the name `approval_status`, across 4 files: ContractController (48),
ContractCustomController (6), ContractPartiesController (5), ContractImportController (2). But only
**56 of them write `approval_contracts`.**

**The 5 in ContractPartiesController write `approval_parties`, a different table** — lines 585, 794,
903, 1134 and 1367, all `ApprovalParties` (`protected $table = 'approval_parties'`). They stay on
`encryptString()` and stay encrypted. Four tables in this database have an `approval_status` column:
`approval_contracts`, `approval_parties`, `financial_limit` and `party_approval_rules`. So the config
key is written **`table.column`**, not a bare column name - `'approval_contracts.approval_status'` -
and a bare name would have quietly turned the other three plain as well. Each of the 56 was checked
back to its `ApprovalContracts::create/insert/update` or to the model the row was loaded from; the
six that could not be read off statically were read by hand.

**8 of those 61 were passing an email as the second argument** (`$usernameKey`, `$ownerKey`) instead
of the column name — at ContractController.php:13220, :13267, :13315, :14457, :14470, :15133 and
ContractCustomController.php:2681, :2707. It never mattered, because `encryptString()` ignores that
argument entirely. It matters now, because `encryptStringx()` reads it to decide whether the column
is plain. All 8 now pass `'approval_status'`. Two of them also guarded on
`function_exists('encryptString')`, now corrected to name the function they actually call.

**0 read sites needed changing.** `decryptString()` only decrypts a value starting with `ey` and
returns anything else untouched, so all 63 read sites already handle a plain value. None of
`approved`/`pending`/`rejected` starts with `ey`. There is deliberately no `decryptStringx()`.

The decision of which columns are plain is data, not code:
`config('app.PLAINTEXT_COLUMNS')` in [config/app.php](../../../config/app.php), currently
`['approval_contracts.approval_status']`.

### 3. The migration and the conversion — two pieces, in a fixed order

**Data first**, [`php artisan contract:convert-approval-status`](../../../app/Console/Commands/ConvertApprovalStatusPlain.php)
— checks-only unless `--apply`, `--down` to re-encrypt. Not a migration, by the same reasoning
[ticket 20](20-migration-portability.md) applied to `contract_party_data`: it has to decrypt in PHP
with the right key, which a migration cannot guarantee. It refuses to write until it is on
`apollo_contracts_expense`, a real row decrypts (which proves the key, rather than trusting a config
value), every value decrypts with **no `--skip-failures` escape hatch**, and every value is in the
expected word list, within the target width, and does not start with `ey`. `chunkById(1000)`, one
UPDATE per row on the primary key, **no `whereIn`** (CONTEXT.md: 1,000+ bindings silently returns
zero rows on this MariaDB build). Re-runnable — it only touches rows still starting with `ey`.

**Then the schema**,
[2026_08_21_000001_narrow_approval_contracts_approval_status.php](../../../database/migrations/2026_08_21_000001_narrow_approval_contracts_approval_status.php):

```
ALTER TABLE approval_contracts MODIFY approval_status varchar(20) NULL
ALTER TABLE approval_contracts ADD INDEX idx_approval_contracts_status_lookup
      (approval_status, row_status, superseded, contract_id)
```

`varchar(20)` because the longest value is 8 characters, and because narrowing is what makes the
index possible: `varchar(1000)` in utf8mb4 is 4,000 bytes. It **names no charset and no collation**
(ticket 20's rule), so the column inherits the table default — `utf8mb4_unicode_ci` here, whatever a
client server uses there. `MODIFY COLUMN` rather than `->change()` because `doctrine/dbal` is not
installed in this project. `up()` **throws if any ciphertext is still present**, so it cannot cut
values by being run out of order. `down()` drops the index and widens back to `varchar(1000)`.

Both applied to the dev database 2026-08-21 with the dev's go-ahead. 13,867 rows converted and
verified; then re-encrypted and converted back again while taking the measurement, which proved
`--down` works in both directions.

### 4. Mixed data during the switch — it needs no handling

`decryptString()`'s `ey` check means a half-converted table reads correctly, indefinitely. There is
no same-release requirement and no dual-format read path to write. The only ordering rule is that
`actionableItemCountsx()` returns zeros until the conversion has run — code before conversion is the
one order that does not work.

**Swapped in as the default the same day, on the dev's call once these numbers were in.**
`actionableItemCountsx()` is what the page runs; `?oldApprovalStatus=1` selects the old counter and is
the way back if a deployment runs the code before the conversion. The old pair is still in the file and
is deleted at [spec.md](../spec.md) §10 step 11, which now carries a checklist of what is swapped versus
what is deleted so nobody hunts for a half that has already gone.

### 5. The measured gain

[report.md](../measurements/report.md) rows 8–8c. Whole request, three runs each, one session, debug
bar off, document **63,267 bytes in every run**:

| | old | new |
|---|---|---|
| whole request | 5,043 / 5,402 / 8,820 ms | **999 / 1,059 / 1,184 ms** |
| counter-off control | 632 / 657 / 662 ms | same |
| **the counter itself** | **~4.4–4.8 s** | **~380 ms** |
| values decrypted | 15,988 | **2,127** |
| rows read (EXPLAIN) | 13,861, `type=ALL` | **2,127, `type=ref`** |

About **12× cheaper**, ~4.2 s off the page at N=3,018. The six numbers are **identical** — `Total (1)`,
Review 1, rest 0 — compared as text on both URLs in the browser.

Whole-request time fell much further than the 280 ms of saved decryption because the old path also
fetched and hydrated all 13,861 rows through the visibility join; the new one fetches 2,127 through
the index.

### A bug this ticket found in the seeder, not in the app

While confirming point 1, the two columns looked broken: 2,100 rows held `Pending` where the counter
compares `=== 'pending'`, and 13,740 held a bare email where the counter does
`json_decode($username)->email`. Both turned out to be
[PerfDatasetSeeder](../../../database/seeders/PerfDatasetSeeder.php)'s doing, not the application's —
the split was exactly seeded rows against real ones:

| | rows | `approval_status` | `username` |
|---|---|---|---|
| real (id 1–151) | 127 | all lowercase | all JSON `{email,name}` |
| seeded (id 100001+) | 13,740 | all capitalised | all bare email |

One `$encStatus` map was shared by `status` and `approval_status`. They are not the same vocabulary:
`status` really is capitalised in this database (`Approved`, `Draft`, `Signing`), while all 61
`approval_status` write sites pass a lowercase word. Fixed and re-seeded. **This invalidates the
decrypt figures in report row 3**, which were measured against data the app would never write. The
app code was never wrong here.

### What is left, and deliberately not done

`username` stays encrypted, so 2,127 decryptions per load remain — one per pending row. That is the
floor while it holds. Removing it means splitting the JSON `{email,name}` into two plain columns to
keep the name showing in the 13 blade files that print it, across 6 controllers. The dev's call,
2026-08-21: a much larger job for a few milliseconds, so not now.

`status` and `previous_status` on the same table are also encrypted `varchar(3000)`. Out of this
ticket's scope by the dev's fixing of it, and nothing on the dashboard reads them.
