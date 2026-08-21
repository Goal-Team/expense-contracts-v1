# Decide the approval_contracts backfill plan

Type: grilling
Status: resolved
Assignee: kader (2026-08-20)
Blocked by: 09

## Question

[Ticket 08](08-query-layer-redesign.md) decided that `approval_contracts` gets two plain-text shadow
columns — `approval_status_plain` and `approver_email` — filled by a `saving` hook on the
`ApprovalContracts` model. The hook covers **new** writes. [Ticket 09](09-index-and-migrations.md)
has now settled the index and migration set and named this **the long pole**: it is PHP decrypt work
per row, not DDL, so it is the one step that takes real time.

Existing rows still need filling, and that is the open question.

The facts already established:

- **All 13,867 local rows** have `approval_status` and `username` encrypted, AES-128-CBC with a random
  IV. Assumed production scale is **~60,000 rows** ([map](../map.md) notes).
- **`original_username` is off limits.** It has another purpose and is not a plaintext fallback.
- **The encryption key is derived from the serving hostname**
  ([config/app.php:7](../../../config/app.php:7)). From a bare CLI it resolves to `localhost` and the
  Encrypter will not construct — seeding needed
  `HTTP_HOST=apollo.contracts.legality:8888 php artisan db:seed`. So the backfill has to run somewhere
  the key resolves, with the right hostname.
- `ApprovalEntriesBackfillService` exists in this codebase as a precedent and **has not been read**.

Decide, with the dev:

1. **What runs the backfill** — an artisan command, a queued job, or the existing
   `ApprovalEntriesBackfillService` pattern. Read that service first before choosing.
2. **How the hostname problem is handled in production.** The key depends on `HTTP_HOST`, which a cron
   or CLI run does not have. Is the command run with the variable set by hand, or triggered from a
   request, or something else? Getting this wrong writes garbage into the shadow columns.
3. **Batching and restartability.** ~60,000 rows of PHP decrypt. Chunk size, and what happens if it
   dies halfway — is a half-filled table safe, and can it be re-run without redoing finished rows?
4. **What the counter does while the backfill is incomplete.** Ticket 08 committed to shipping a
   bounded PHP decrypt as the interim so the correctness fix does not regress the page. Does that
   interim stay until the backfill finishes, and how does the code know which one to use?
5. **Rows that fail to decrypt.** Some may have been written under a different hostname or key. What
   is written to the shadow column then — null, a marker, or the row skipped and logged?
6. **Verification.** How anyone confirms the backfill is correct and complete, given the source column
   cannot be compared in SQL.

## Constraints

- Nothing runs against production. This ticket produces the plan; the spec carries it.
- Never log a decrypted value. Log the row id and enough to find it. See [CLAUDE.md](../../../CLAUDE.md).
- Only `apollo_contracts_expense`.

## Answer

**Resolved 2026-08-20.** The premise of this ticket was wrong, and measuring it first changed every
answer below.

### The backfill is not the long pole. It takes about two seconds.

[Ticket 09](09-index-and-migrations.md) named it the long pole because it is PHP decrypt per row.
Measured on the seeded local data: **all 13,867 rows, both columns, 27,734 values, decrypted in
0.49 s** — 0.018 ms per value. At the assumed production scale of ~60,000 rows that is roughly
**2 seconds**, one time. No queue, no overnight window, no progress table.

### How the encryption key is actually built

Two schemes exist and only one is relevant here.

- **PHP scheme** (this table's `username` and `approval_status`):
  [config/app.php:201](../../../config/app.php:201) is
  `'APP_ENCRYPTION_KEY' => "c0n|r@(t$" . $linkarray[0] . "4"`, where `$linkarray[0]` is
  `$_SERVER['HTTP_HOST']` split on dots, first piece. From `apollo.contracts.legality:8888` that is
  **`apollo`**, giving `c0n|r@(t$apollo4` — 16 bytes, which is what AES-128-CBC requires. The port and
  the rest of the host are ignored. **`encryptString($string, $key)` and `decryptString($string, $key)`
  discard their `$key` argument entirely** ([helpers.php:141](../../../app/helpers.php:141),
  [:154](../../../app/helpers.php:154)); every call site passes one and none of them do anything.
- **Legacy SQL scheme** (master tables only): [helpers.php:386](../../../app/helpers.php:386) builds
  `AES_DECRYPT($column, '{APP_LEGACY_KEY}.$key')`, so `decrypt_data('BranchName','branch')` ends the key
  with `.branch`. **This** is the scheme where a table/module name is part of the key. It is not used by
  `approval_contracts`.

Consequence worth carrying: from a bare CLI the host is `localhost`, the key is 20 bytes, and the
Encrypter **refuses to construct** — a mistake crashes rather than writing rubbish. And rows written
while the app was served on a host with a different first word cannot be read now. That is the most
likely real cause of an undecryptable row.

### 1. What runs it — a script with the key written into it

**Dev's call: a standalone script with the key hardcoded, no manual step, not going through the
helpers.** So the backfill builds its own `Encrypter` with the literal key rather than reading
`config('app.APP_ENCRYPTION_KEY')` or calling `decryptString()`. That removes the host dependency
completely: no `HTTP_HOST=` prefix to remember, no admin page needed, and no chance of a silently
different key. The dev will remove or replace the hardcoded key afterwards.

The admin-page route (the `ApprovalEntriesBackfillController` pattern, seven routes at
[web.php:303](../../../Modules/Contract/routes/web.php:303)) was offered and **not** taken. It stays
documented here only because it is the precedent for how such work is normally driven in this codebase.

**Open point the spec must carry:** the hardcoded key is `apollo`-derived. If production serves this app
on a host whose first word is not `apollo`, the key in the script has to be changed before it runs
there. This is a one-line note to whoever deploys, not a decision.

### 2. Seatbelt before writing — yes

Agreed. Before a single write: encrypt a throwaway string with the hardcoded key, decrypt it, confirm
it round-trips; then decrypt one real row and confirm the result looks like an email address. If either
fails, stop and write nothing.

**Explicitly banned pattern:** `ApprovalEntriesBackfillService::safeDecrypt()`
([:1106](../../../Modules/Contract/app/Services/ApprovalEntriesBackfillService.php:1106)) catches a
failed decrypt and **returns the ciphertext**. Copied into a shadow column that writes ciphertext into a
plaintext column, where it looks correctly filled. The backfill must never do this.

### 3. Batching and re-running — 1,000 at a time, stateless

`chunkById(1000)`, selecting only rows not yet filled. No progress table, no resume flag: a crash
halfway loses nothing and a second run finishes the job. Running it repeatedly is harmless — after the
first pass it finds nothing to do.

**Hard rule:** never collect ids and pass them to `whereIn`. That is
[ticket 12](12-approvals-empty.md)'s bug — at 1,000 or more bound parameters MariaDB 10.4.24 silently
returns zero rows. `chunkById` uses a keyset walk and is not affected.

### 4. No temporary slow counter

[Ticket 08](08-query-layer-redesign.md) promised a bounded PHP-decrypt path to keep the numbers right
while the shadow columns were empty. **Dropped.** That promise was written when the fill looked like
hours; it is two seconds, one time, in one deploy. Building it would mean two pieces of code computing
the same six numbers, a check on every page load to choose between them, and both kept correct forever.

Release order instead: **add the columns → run the backfill → switch the page to the new columns.**

(Recorded because it caused confusion in the session: the two seconds is the **one-time fill**, not a
per-request cost. After the fill the page reads plain columns and does no decryption at all — that is
the entire point of the columns.)

### 5. Rows that will not decrypt — a marker

**Dev's call: a marker, not NULL.** Failed rows get a marker value written into the shadow column, are
counted in the summary, and are logged at `Log::warning` with the **row id only** — never the value,
per [CLAUDE.md](../../../CLAUDE.md). The re-run filter picks up both unfilled rows and marked rows, so
if the cause was a fixable key problem a later run retries them. Anything reading the shadow columns
must treat the marker as "no match", which matches today's behaviour — those rows already count as zero.

Local data: **zero rows failed to decrypt.** One row of 13,867 has a `username` with no `@` in it.

### 6. Verification — every row, not a sample

Full compare, because it is affordable: a throwaway command decrypts every row, compares against the
shadow column, and reports matched / mismatched / marked with the ids of any mismatch. Run it
immediately after the fill, and again about a week later to prove the `saving` hook is holding for new
writes. [Ticket 08](08-query-layer-redesign.md)'s old-vs-new diff command covers the counter numbers
separately.

### The columns themselves

`approver_email varchar(191)` and `approval_status_plain varchar(20)`, both **utf8mb4 /
utf8mb4_unicode_ci** per the map's standing rule, storing the decrypted value **exactly as found, with
no case change**. Measured values: `approval_status` is only `Approved` / `Pending` / `approved` /
`pending` / `rejected`, longest 8 characters — and the **lowercase 127 are the real pre-seed rows while
the capitalised ones are all seeded**, so real-world casing is mixed and unknown. `utf8mb4_unicode_ci`
is case-insensitive, so `Approved` and `approved` already match in a `WHERE` and normalising would
destroy real data for no gain. `username` is an email in 13,866 of 13,867 rows, longest 106 characters;
191 covers it and keeps the index inside the old key-length limit.

