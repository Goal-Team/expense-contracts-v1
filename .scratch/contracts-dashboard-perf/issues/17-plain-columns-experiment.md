# What do we gain if `approval_status` and `username` are just plain columns?

Type: `wayfinder:grilling` · Status: **open** · Blocked by: nothing · **Last in order — runs after
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

<!-- filled on resolution -->
