# 03 — Walk every tab and collect what else is broken

Type: `wayfinder:task` (AFK, subagent)
Blocked by: 01, 02
Status: OPEN

## Question

The reminder crash is the one the dev hit first. What else is broken on this page at N=3,018 that
was not broken at N=18?

## What is known

Four `whereIn` calls on this page take a list whose length grows with the dataset:

- `ContractPartyData::whereIn('contract_party_location_id', $contract_party_locations)`
- `ContractPartyData::whereIn('contract_party_exe_id', $contract_party_id)`
- `Contract::whereIn('id', $FinalContractList)`
- `Contract::whereIn('id', $finalListChild)`

On this stack a `whereIn` with 1,000 or more bound values silently returns **zero rows** — no
error, just empty. See [../../wherein-1000-bug/spec.md](../../wherein-1000-bug/spec.md). At 3,018
contracts these are the prime suspects: the symptom is a section of the page quietly going blank,
which is easy to miss and easy to blame on data.

## Done when

- Every tab loaded in the browser on a seeded contract and on a pre-existing contract, with the
  Laravel log and the browser console checked after each.
- Each break written down with the file, line and cause. Fixes that are one line land in this
  ticket's commit; anything that needs a decision becomes its own ticket.
- Committed on `claude/contract-edit-page-perf`.

## Note from ticket 02, 2026-08-21

**The page does not render at all on the re-seeded data.** `contracts/100479?tab=edit` and
`contracts/1?tab=edit` both return HTTP 500, "The FastCGI process exceeded configured request
timeout". Contract 1 is a pre-existing row, so this is not about the seeded rows.

One query causes it: the child-contract `GROUP_CONCAT` at
[ContractController.php:780](../../../Modules/Contract/app/Http/Controllers/ContractController.php:780)
(the same shape again at `:10662`). It reads the whole `contracts` table once for every row of the
table, so 3,018 x 3,018 row reads, and it gets slower with the square of the contract count. Seen
in `information_schema.PROCESSLIST` at "Sending data" for 859 s; killing it wrote
`SQLSTATE[70100] 1317 Query execution was interrupted` into `laravel.log` against that exact SQL.

It needs two columns, `id` and `parentcontract`. Two ways out, and they stack:

- the covering index in [ticket 11](11-missing-indexes.md) — 3 s instead of over 120 s;
- replace the session-variable walk with `WITH RECURSIVE` (MariaDB 10.2+, this server is
  10.4.24), which drops the quadratic scan altogether.

Four more things that break or throw, found while enumerating the page's reads:

1. `viewDetailContract.blade.php:1835`, `:1883`, `:1932`, `:1980` — `explode(" ",
   decryptString($contract->reminder_*_alertMeOn))` with no guard. Ticket 01 fixed the `editRenew`
   side through `reminder_alert_parts()`; these four were not changed. NULL there is a TypeError.
2. `signApprovals.blade.php:156` — `json_decode($contract->rules_id)` unguarded, and
   `viewDetailContract.blade.php:1117` — `json_decode(trim($contract->rules_id))`.
   `contractFlow.blade.php:6` guards the same read with `is_string()`.
3. `ContractController.php:613` — `->first()->contract_type` with no null check. An orphaned
   `contract_type` id fatals before the view renders.
4. `viewDetailContract.blade.php:1732` reads `contract_eauto_renewal_datend_date`. No such column
   and no such accessor anywhere in the repo, so the block is unreachable. Meant to be
   `auto_renewal_date`. Harmless, so out of scope by the CLAUDE.md rule — recorded, not fixed.
