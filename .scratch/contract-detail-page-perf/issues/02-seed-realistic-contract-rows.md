# 02 — Make the seeded rows look like real contracts

Type: `wayfinder:task` (AFK, subagent)
Blocked by: nothing
Status: CLOSED (seed done; page render blocked by a separate problem, see Resolution)

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

## Resolution

Status: DONE for the seed. The page still does not render, for a reason that is not the seed —
see [What this uncovered](#what-this-uncovered).

### The column table

The detail page reads **60** of the `contracts` table's **111** columns. `PerfDatasetSeeder`
wrote **40**. Every number below is measured, not read off the code.

Enumerated from `ContractController::viewContract` (lines 259-1085) and every partial reachable
from `viewDetailContract.blade.php`: `contractFlow`, `signApprovals`, `contractApprovalsView`,
`contractApprovalsViewParallel`, `approvalFlow`, `preApprovalFlow`, `contractApprovals`,
`viewDetailContractEdit` -> `editRenew` -> `editCustomField`, `viewContractDocument`,
`viewEstampProcess`, `contractObligation`, `viewDetailCustomField`, `partyDetailsView`.

**A. Read by the page, NULL on all 3,000 seeded rows before this ticket.** "18 real rows" says
what the pre-existing contracts hold — shape only, never a value.

| column | read where | encrypted | 18 real rows hold | now seeded with |
|---|---|---|---|---|
| `reminder_enable` | editRenew:397, viewDetailContract:1797 | yes | one word, on/off | 3 in 4 on, 1 in 4 off |
| `reminder_first_alert` | editRenew:423 | yes | three words, an end-date name | 2 values, rotated |
| `reminder_first_alertMeOn` | editRenew:431, viewDetailContract:1835 | yes | three words, "N unit direction" | 6 values, rotated |
| `reminder_first_alert_repeats` | editRenew:457 | yes | one word, a frequency | 6 values, rotated |
| `reminder_second_alert` / `_alertMeOn` / `_alert_repeats` | editRenew:474/481/506 | yes | same shapes | same, offset so rows differ |
| `reminder_escalation_alert` / `_alertMeOn` / `_alert_repeats` | editRenew:523/530/554 | yes | same shapes | same |
| `reminder_escalation_alert_after` / `_alertMeOn_after` / `_alert_repeats_after` | editRenew:571/578/603 | yes | same shapes, direction is "after" | same |
| `exclusivity` | editRenew:115 | yes | two words, one of four fixed options | all 4, rotated |
| `contract_tags` | editRenew:134 | no, JSON | one-element array of a type id | array of the row's own type id |
| `signatory` | approvalFlow:81, editRenew:856 | no, int | a ContractUsers id | 20 real ids, rotated |
| `rules_id` | contractFlow:6, contractApprovalsView:70, signApprovals:156 | no, JSON | ~1,235 bytes, one object, nested JSON strings | 4 variants, same shape |
| `payment_schedule` | editRenew:675 | yes | short free text | 5 values |
| `payment_terms` | editRenew:682 | yes | a sentence or two | 4 values |
| `taxes` | editRenew:689 | yes | short free text | 5 values |
| `escalation_clauses` | editRenew:698 | yes | short free text | 5 values |
| `discounts` | viewDetailContract:2151 | yes | short free text | 4 values |
| `retention` | editRenew:712 | yes | short free text | 4 values |
| `payment_escrow` | editRenew:716 | yes | short free text | 3 values |
| `financial_guarantees` | editRenew:724 | yes | short free text | 4 values |
| `currency_conversion` | editRenew:729 | yes | short free text | 3 values |
| `period_auto_renewal` | editRenew:326 | no, int | NULL on all 18 | 1-3, fixedTerm auto rows only |
| `period_auto_renewal_unit` | editRenew:329 | yes | one word, a time unit | 2 values, same rows |
| `auto_renewal_date` | editRenew:340, viewDetailContract:1734 | no, date | NULL on all 18 | the end date, same rows |
| `manual_renewal_date` | not read; set for symmetry | no, date | NULL on all 18 | the end date, manual rows |
| `evergreen_condition` | editRenew:355 | yes | one word | evergreen rows only |
| `termination_date` | viewDetailContract:455 | no, date | NULL on all 18 | the 90 Terminated rows |
| `termination_reason` | not read; set with the date | yes | one word | same 90 rows |
| `contract_attachment` | 25 sites, 7 partials | no | a storage file id | a SEEDPERF file id |
| `contract_attachment_filename` | 22 sites, 6 partials | no | a .docx name | contract_&lt;id&gt;_seedperf.docx |

**B. Read by the page, written by the seeder but with a value the page can never match.**

| column | what the seeder wrote | what the page needs | now |
|---|---|---|---|
| `commencement_type` | `fixedDate`, plain | encrypted `FixedDate`; the radio posts that spelling | encrypted `FixedDate` |
| `end_contract_type` | `autoRenewal` for 1,000 rows | one of onetimeContract / fixedTerm / evergreen / termination | `evergreen` for those 1,000 |
| `renewal_type` | `auto`, plain, on the autoRenewal rows | encrypted `automaticrenewal` or `manualRenewal`, on fixedTerm rows | both, half each, fixedTerm rows |
| `billing_frequency` | `monthly` etc, plain lowercase | encrypted and capitalised: Weekly / Monthly / Quarterly / Half Yearly / Annually / Onetime | all 6, rotated |
| `currency_contract` | `INR`, plain | encrypted | encrypted |
| `contract_description` | plain sentence | encrypted | encrypted, names the vendor |
| `contract_priority` | `critical` on 1 row in 4 | low / medium / high only | 3 values, rotated |

The `end_contract_type` swap does not move any dashboard number. `resolveContractEndDate()`
(ContractDashboardController.php:456) sends both `autoRenewal` and `evergreen` down the same
`else` branch, and that branch reads `fixed_date`, which every seeded row already had.

**C. Read by the page, still NULL, on purpose.** `legal_advisor_id`,
`legal_contact_comment`, `legal_requested_by_name`, `legal_requested_by_email`
(viewDetailContract:404-644). The local `legal_advisors` table has **0 rows**, so there is no
valid advisor id to point at. All 18 real contracts are NULL here too.

**D. Read on the page but not a `contracts` column at all.** `catgoery_identity`,
`department_identity`, `contract_type_id` — the controller grafts them on at
ContractController.php:599-611. `contract_value` is the model's only accessor and the page never
uses it. `contract_eauto_renewal_datend_date` (viewDetailContract:1732) exists nowhere in the
schema: a typo for `auto_renewal_date`, so that block is dead code.

### What changed

`database/seeders/PerfDatasetSeeder.php`, commit `79b7dd8`:

- `buildContracts` fills every column in table A and corrects every value in table B. Values
  come from small pools picked by row number, so no two neighbouring rows are identical.
- Each distinct string is encrypted once, not once per row. 3,000 rows times 25 encrypted
  columns is 75,000 AES calls otherwise.
- New `buildRulesId()` builds the approval-rule payload in four variants (sequential or
  parallel review, with or without a signatory group), shaped like the real rows.
- `loadPools()` gained a `signatories` pool from `ContractUsers`.
- Two bugs that stopped the seeder running at all:
  - `buildApprovals` encrypted `approval_status`, but migration
    `2026_08_21_000001_narrow_approval_contracts_approval_status` made that column a plain
    `varchar(20)`. It calls `encryptStringx($s, 'approval_contracts.approval_status')` now, the
    same as every write site in the app. Before this fix the seeder died with
    "Data too long for column 'approval_status'".
  - Contracts insert in batches of 40, not 200. A row carries 1.4 KB of `rules_id` now and 200
    of them overran MySQL's 1 MB `max_allowed_packet`.

Nothing outside the seeder changed. `editRenew.blade.php` and `app/helpers.php` were left alone.

### Verification

**Rollback and re-seed, not an update pass.** `PerfDatasetRollbackSeeder` still removes exactly
the seeded rows — it matches on the `SEEDPERF` marker **and** `id >= 100001`, and it reported
`Deleted 3000 contracts, 6900 contract_party_data, 13740 approval_contracts` with
`Remaining: contracts=18 contract_party_data=40 approval_contracts=127`, the documented pre-seed
counts. After re-seeding:

| table | total | seeded | map says |
|---|---|---|---|
| `contracts` | 3,018 | 3,000 | 3,018 |
| `contract_party_data` | 6,940 | 6,900 | 6,940 |
| `approval_contracts` | 13,867 | 13,740 | 13,867 |

All three match.

**Column check.** Of the columns the page reads, none is NULL on any seeded row except the four
legal-contact ones in table C.

**Browser, before the re-seed.** `contracts/100479?tab=edit` rendered, 415,649 bytes of body, no
error. Every text field the seeder skipped was **empty**, and every select fell back to its
**first option**, which is worse than empty because it looks populated: `billingFrequency` showed
Weekly, `alertMePrior` showed Days, `reminderEnable` showed on. None of those came from the row.

**Browser, after the re-seed. The page does not render.** `contracts/100479?tab=edit` and
`contracts/1?tab=edit` both return **HTTP 500, "The FastCGI process exceeded configured request
timeout"**. Contract 1 is a pre-existing row, so this is not about the seeded rows being
malformed. The cause is measured, not guessed — see below. The seeded field values themselves
could not be read off a rendered page, and that is the one part of this ticket left open.

### What this uncovered

Separate problems. Not fixed here, as the ticket asks.

1. **The child-contract query is what breaks the page.** `viewContract` runs this on every
   detail page view, for every contract (ContractController.php:780, and again at :10662):

       SELECT GROUP_CONCAT(lv SEPARATOR ',') FROM (
         SELECT @pv:=(SELECT GROUP_CONCAT(id SEPARATOR ',') FROM contracts
           WHERE FIND_IN_SET(parentcontract, @pv)) AS lv FROM contracts
         JOIN (SELECT @pv:=<id>) tmp) a

   The inner subquery reads the whole `contracts` table once for every row of the outer one:
   3,018 x 3,018 row reads. It gets slower with the square of the contract count. Found it in
   `information_schema.PROCESSLIST` holding "Sending data" for **859 seconds**. Killing it put
   `SQLSTATE[70100] 1317 Query execution was interrupted` in `laravel.log` against that exact
   SQL, which is what proves the page was sitting on it.

2. **The query reads the whole row when it needs two columns.** Measured 2026-08-21, same
   3,018 `(id, parentcontract)` pairs:

   | source | time |
   |---|---|
   | two-column temporary table | **3 s** |
   | the real `contracts` table | **over 120 s**, then the FastCGI timeout |

   Same rows, same SQL. The only difference is row width. A covering index on
   `(parentcontract, id)` closes that gap, and a `WITH RECURSIVE` walk (MariaDB 10.2+; this
   server is 10.4.24) removes the quadratic scan entirely. The index is written up as a
   migration in
   [proposed-migration-parentcontract-index.php](../proposed-migration-parentcontract-index.php)
   — **written for review, not run**, per CLAUDE.md.

3. **Realistic rows are 6x wider, and that is why the seed tipped the page over.** The seeded
   rows now hold **27 MB** of content, 9,390 bytes a row, against roughly 1.5 KB before.
   `contracts` reports **110 MB** of `DATA_LENGTH`, four times its content, because
   `ROW_FORMAT=Dynamic` pushes the long encrypted text columns off-page and each off-page value
   costs a whole 16 KB page. The local `innodb_buffer_pool_size` is **16 MB**. So the table went
   from roughly fitting in the buffer pool to 7x larger than it, and every one of the 3,018
   scans now reads from disk. `OPTIMIZE TABLE contracts` changed nothing: 109.6 MB after.
   The seed is not wrong. Real contracts are this wide. The query is wrong.

4. **Four blade sites still crash on a NULL reminder.** `viewDetailContract.blade.php:1835`,
   `:1883`, `:1932`, `:1980` do `explode(" ", decryptString($contract->reminder_*_alertMeOn))`
   with no guard. Ticket 01 fixed the `editRenew` side by routing it through
   `reminder_alert_parts()`; the view side was not changed. The seed hides this now, because no
   seeded row is NULL there any more, but any contract written by a path that skips those
   columns brings it straight back.

5. **`signApprovals.blade.php:156` does `json_decode($contract->rules_id)` with no guard**, and
   `viewDetailContract.blade.php:1117` does `json_decode(trim($contract->rules_id))`.
   `contractFlow.blade.php:6` guards the same read with `is_string()`. Same column, three
   different levels of care.

6. **`viewDetailContract.blade.php:1732` reads `contract_eauto_renewal_datend_date`.** No such
   column, no such accessor, one occurrence in the whole repo. The block it guards is
   unreachable. It is meant to be `auto_renewal_date`.

7. **`dataCustomFields($contract->id, $field_id)` runs one query per custom field**
   (editCustomField.blade.php:52). With 35 custom fields that is 35 queries for one panel, and
   the panel is included four times with different `categoryId`.

8. **The seeder had drifted from the app once already** — the `approval_status` encryption, under
   "What changed". Nothing tells anyone when a migration makes the seeder wrong. It surfaces as a
   seed that dies, or worse, as numbers measured against the wrong population.

## The last open piece, now closed — 2026-08-22

Ticket 02 could not read the seeded values off a rendered page, because the page was returning HTTP
500. With the covering index applied it renders, and the seeded reminder fields come through:
contract 100479 shows `90 days prior` and `30 days prior` in the edit tab. Real contract 1 still shows
`30 days prior` and `15 days prior`. So the seeded values reach the form and the shapes match.
