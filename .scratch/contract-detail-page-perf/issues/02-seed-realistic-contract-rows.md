# 02 — Make the seeded rows look like real contracts

Type: `wayfinder:task` (AFK, subagent)
Blocked by: nothing
Status: OPEN

## Question

`PerfDatasetSeeder` writes only the columns the **dashboard** reads. The detail page reads far
more, and every column the seeder skips is NULL on 3,000 rows. Which columns does the detail
page read that the seeder leaves NULL or empty, and what realistic value should each get?

The dev's instruction 2026-08-21: after the NULL fix, either measure against data that existed
before the seed, or correct the seed values. Correcting the seed is the durable answer — the 18
pre-existing contracts cannot show a performance problem at all.

## What is known

The seeded contract row is built in
[`PerfDatasetSeeder::buildContracts`](../../../database/seeders/PerfDatasetSeeder.php:193), around
40 columns out of the table's ~110. `reminder_first_alertMeOn` is one of the gaps, and it is what
crashes the page (ticket 01).

## Done when

- Every column `viewDetailContract` and its partials read is listed, with whether the seeder writes
  it and what the 18 real contracts hold in it.
- `PerfDatasetSeeder` writes a realistic value for each gap, using the same encryption the real
  write path uses for that column.
- The 3,000 existing seeded rows are backfilled — a re-seed is acceptable if the rollback seeder
  still works, otherwise an update pass.
- Committed on `claude/contract-edit-page-perf`.

## Notes

Hand the enumeration to a fresh-context subagent. Reading a 894-line blade plus its partials plus a
498-line seeder does not need to happen in the session that decides anything.

Never log or print a decrypted contract field while doing this.
