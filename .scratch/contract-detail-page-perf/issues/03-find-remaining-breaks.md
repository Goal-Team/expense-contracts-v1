# 03 — Walk every tab and collect what else is broken

Type: `wayfinder:task` (AFK, subagent)
Blocked by: 01, 02
Status: CLOSED 2026-08-22

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

## Resolution

Status: **CLOSED**, 2026-08-22. Nine commits, one for each break.

**Nine things threw, not four.** The ticket named four and the walk found five more. Every one of the
nine stopped the page from rendering, so every one is this ticket's under the CLAUDE.md rule. Nothing
else was touched.

| # | break | file | commit |
|---|---|---|---|
| 1 | `?tab=historical` read `$_GET['history']` with no guard | `viewDetailContract.blade.php` | `2f20da8` |
| 2 | four reminder columns exploded with no guard | `viewDetailContract.blade.php` | `9989237` |
| 3 | `rules_id` decoded and indexed with no guard, twice | `viewDetailContract.blade.php`, `signApprovals.blade.php` | `bab22dc` |
| 4 | `$ContractsFinal[0]` read before the empty-list redirect | `ContractController.php` | `d3be98d` |
| 5 | three name lookups read `->first()->name` | `ContractController.php` | `ddfc093` |
| 6 | the external party name read element 0 of an empty result | `ContractController.php` | `63a1c43` |
| 7 | `?attachment=` ran `die()` and blanked the page | `viewDetailContract.blade.php` | `77e0ecd` |
| 8 | `?tab=timelineedit` looped a variable nobody sets | `contractApprovals.blade.php` | `41483c6` |
| 9 | a missing history snapshot left `$contracts` null | `ContractController.php` | `74776e6` |

### 1. `?tab=historical`, and what was not obvious

The Historical tab shows one past version of the contract. The History tab links to it as
`?tab=historical&history=<history_id>`, the controller swaps the contract for that snapshot, and the
blade renders the Details body with the snapshot's values — `historical` has no body block of its
own. A 60-minute cookie called `historical` remembers the last version, and `?clearcokke=` forgets it.

One line built the tab's own nav link from `$_GET['history']` with no guard, so a load with no
`history` parameter threw. Twelve lines further down the same link was already built from the cookie.
So the rule now lives in one variable, `$historicalVersionId`: the parameter, else the cookie, else
empty. Both places read it. Empty draws no Historical nav item and the body shows the live contract,
which is what every unknown tab value already does.

**What was not obvious, and was left alone:** with the cookie set but no `?history=` in the URL, the
page draws the Historical nav item and shows the **live** contract, not the snapshot. The controller
only reads the parameter, never the cookie. Clicking the item fixes it, because the item's link
carries the id. Making the cookie load the snapshot would be a new feature, so it is written down
here instead. It does not throw.

### 2. The four reminder columns

`reminder_alert_parts()` from [ticket 01](01-fix-null-reminder-crash.md) does the work. No second
helper, no guard pasted four times. The variables are named now — `$firstAlertUnit` instead of
`$fristarl[1]` — and blocks three and four no longer share one `$escalationarl`.

The fourth block carried the same `?? '' == 'days'` precedence bug ticket 01 found on the edit side.
PHP reads `$x ?? ('' == 'days')`, so the stored value was truthy and the first branch always won: a
reminder of `14 days after` printed **Prior**. It prints **After** now.

### 3. `rules_id`

Both reads now test `is_string()` before the decode and `is_array()` after it, the way
`contractFlow.blade.php` already did. `signApprovals.blade.php` was the one that threw:
`$approvalsDetails[0]` on null gives "Trying to access array offset on null", which Laravel turns
into a 500.

### 4. `$ContractsFinal[0]`

`availableContracts()` returns an empty list for a contract the user may not see, **and** for a
contract whose `department_id` points at a row that is gone. `viewContract()` read element 0 of it and
threw "Undefined array key 0". The redirect that handles the empty list already existed — 122 lines
further down, after the eSign block had run. It is the first thing after the call now. Two things
follow: the page redirects to the contract list instead of throwing, and a contract the user may not
see no longer gets its eSign status polled and written.

### 5, 6. The four name lookups

Three lookups swap an id for a name — category, business unit, contract type. The fourth reads the
external party's company name. All four threw when the row was missing. `ContractParties` also
carries the `PartiesRoleBasedScope` global scope, so its result is empty for a party the current user
may not see, not only for a deleted row.

Each one falls back to an empty name and writes a `Log::warning` with the contract id and the id that
missed. The id itself still sits in the column beside the name, so nothing is lost. No decrypted field
is logged.

### 7. `?attachment=`

`contracts/{id}?attachment=1` returned a **70-character** document with HTTP 200. A `die;` sat on the
first line of the block that reads that parameter. Nothing else in the block has ever run: the
`cookie()->queue('attachment', ...)` below the `die` is unreachable, nothing else in the repo sets
that cookie, and the one line that reads it can never have been true. The whole block is gone, so the
URL now renders the same page as no parameter at all.

### 8. `?tab=timelineedit`

`contractApprovals.blade.php` loops `$reqfields` and nothing in the repo passes it, so the tab
returned 500 on "Undefined variable $reqfields". The tab is in no nav bar, so only a typed URL reaches
it. The loop fills a `display:none` table of the contract fields that are missing.
`ContractController` holds `$reqfieldsText`, a `key => label` map of exactly that shape, which looks
like the name this was renamed to — **but that is a guess, so the fix guards the loop and renders an
empty table.** The dev decides whether `$reqfieldsText` belongs there.

### 9. A missing history snapshot

`?tab=historical&history=999999` returned 500: `ContractHistory::...->first()` came back null and the
page read `contract_status` off it. The id reaches the page from a link a user kept and from the
60-minute cookie, so a deleted snapshot is enough. It falls back to the live contract now, the same as
a load with no `?history=`. A second read of the party rows, 300 lines further down, made the same
decision on its own from `$_GET`; it reads the one variable now, so the page cannot show the live
contract with a snapshot's parties.

### Every tab, four contracts

52 loads, one fetch each, warm, `DEBUGBAR_ENABLED=false`, cookie cleared with `?clearcokke=` first.
`100479` a plain root, `101101` a 20-child fan-out, `101143` three ancestors, `1` a real pre-existing
contract. `zzz` stands for any unknown tab value; `timelineedit` is in the table because it was one of
the breaks.

| tab | 100479 | 101101 | 101143 | 1 |
|---|---|---|---|---|
| `details` | 200, 239,076 | 200, 313,381 | 200, 247,844 | 200, 261,223 |
| `pre-approval` | 200, 105,779 | 200, 125,430 | 200, 125,361 | 200, 100,708 |
| `timeline` | 200, 105,779 | 200, 125,430 | 200, 125,361 | 200, 100,708 |
| `timelineedit` | 200, 80,873 | 200, 100,765 | 200, 100,696 | 200, 109,462 |
| `edit` | 200, 326,231 | 200, 349,476 | 200, 322,022 | 200, 321,430 |
| `flow` | 200, 72,388 | 200, 80,855 | 200, 80,786 | 200, 89,080 |
| `history` | 200, 61,437 | 200, 62,805 | 200, 62,735 | 200, 62,752 |
| `historical` | **200**, 239,117 | **200**, 313,422 | **200**, 247,885 | **200**, 261,264 |
| `attachment` | 200, 61,350 | 200, 57,654 | 200, 57,584 | 200, 57,345 |
| `obligation` | 200, 85,534 | 200, 82,901 | 200, 81,731 | 200, 81,584 |
| `e-stamp` | 200, 67,003 | 200, 63,307 | 200, 63,237 | 200, 62,993 |
| `zzz` | 200, 239,076 | 200, 313,381 | 200, 247,844 | 200, 261,223 |
| none | 200, 105,779 | 200, 125,430 | 200, 125,361 | 200, 100,708 |

**Every document ticket 21 recorded is unchanged, character for character** — `details` 313,381 on
`101101`, 261,223 on `1`, 247,844 on `101143`, and 326,231 / 349,476 / 322,022 / 321,430 on `edit`.
`?tab=historical` renders 41 characters more than `?tab=details` on every contract; the difference is
the tab bar, which lists a different set of links on that branch.

Contract `4` was checked too, because it is a Signing contract and the one that renders
`signApprovals.blade.php`: all eleven tab values return 200.

`storage/logs/laravel.log` across the 52 loads holds **no error and no warning** — only the expected
40 `skips the Related Contracts queries` and 12 of each family-tree walk. Twelve, not four, because
`details`, `historical` and `zzz` all fall into the same branch.

Browser console on `101101?tab=details`: the same **nine** entries ticket 21 recorded — three
accessibility issues, one deprecation, one 403 for an asset, one Tagify warning, three logs. Nothing
new. The two font preload warnings ticket 21 saw did not appear this time; they are load timing.

### How each break was proved, not assumed

Every fix was checked both ways in the browser: the page loads with the guard, and the same URL on the
same data throws without it. The data for the NULL and orphan cases was made, then put back.

| break | data used | before the fix | after |
|---|---|---|---|
| reminder columns | **all four columns on `100479` set to NULL**, then restored from a dump | ticket 01 measured `Undefined array key 1` | renders; empty day, Days selected, no direction |
| `rules_id` | `rules_id` on contract 4 set to NULL, then restored | `Trying to access array offset on null`, HTTP 500 | 200, and 170,885 characters again on the real value |
| the three name lookups | `catgoery_id` and `contract_type` on `100479` set to `99999` | `Attempt to read property "name" on null`, HTTP 500 | 200, two `Log::warning` lines |
| `$ContractsFinal[0]` | `department_id` on `100479` set to `99999` | `Undefined array key 0`, HTTP 500 | redirects to the contract list |
| external party | `contract_party_exe_id` on party row 101103 set to `999999` | `Undefined array key 0`, HTTP 500 | 200, one `Log::warning` |
| `?attachment=` | none needed | 70-character document, HTTP 200 | 200, 105,779 characters |
| `?tab=timelineedit` | none needed | `Undefined variable $reqfields`, HTTP 500 | 200, 81,421 characters |
| missing snapshot | `?history=999999` | `Attempt to read property "contract_status" on null` | 200, one `Log::warning` |

**The seeder fills all four reminder columns now, so a contract with NULLs had to be made.** All 3,018
rows carry a value. `100479`'s four columns were set to NULL, the Details tab was loaded, and the
encrypted values were then written back from a dump taken first. The tab renders **248,378 characters
of DOM** before and after the round trip, so the restore is exact. The same round trip was done for
contract 4's `rules_id`, for `100479`'s three lookup ids, and for party row 101103. Every one is back
to its seeded value.

### Numbers

One row, [row 12](../measurements/report.md). `?tab=historical` goes from HTTP 500 to **200 at 760–777
ms and 369 queries**, which is what the Details tab costs on the same contract (row 11 reads 686–785
ms and 369 queries). Nothing was made faster or slower: `historical` falls into the Details branch and
runs the same work, and the 3,700–4,100 ms this tab used to burn before failing was the two
family-tree walks that tickets 15 and 21 have since replaced.

**The other eight fixes move no number.** Each is a null check, an `is_string()` test, one moved `if`,
or a deleted `die`, on a path that runs once per request. There is nothing to measure, and no number
is invented here.

### Wrong, and left alone

Under the CLAUDE.md rule: it does not throw and it does not cost time, so it is written down here for
a later effort.

- **`viewDetailContract.blade.php:1732` reads `$contract->contract_eauto_renewal_datend_date`.** No
  such column and no such accessor exist anywhere in the repo, so the block never renders. It looks
  like it means `auto_renewal_date`. Found by ticket 02; still true.
- **`preApprovalFlow.blade.php:133` does `$steps->first()->approval_type_row` with no guard.** It
  cannot throw today because `$steps` is a group from a `groupBy` and a group is never empty. Line 214
  guards the identical read with `?? 0`, so the two lines disagree about whether a guard is needed.
- **The Historical tab shows the live contract when only the cookie carries a version.** Written up
  under break 1. The controller reads the parameter and never the cookie.
- **`$reqfields` in `contractApprovals.blade.php` is probably `$reqfieldsText`.** Written up under
  break 8. The guard renders an empty table until the dev says.
- **`?tab=<anything unknown>` renders the Details body.** `zzz` returns the Details tab byte for byte.
  That is how the blade's last branch works, and `contract_detail_shows_related_contracts()` records
  it, but a typo in a URL silently gives a different tab than the one asked for.
- **Four `explode(',', $reqFieldsOptions['value'][$key])` reads** in `contractApprovalsView`,
  `contractApprovalsViewParallel` and `signApprovals` index a second array by the same `$key`. None
  threw on any of the 52 loads, and the two arrays are built together, so the keys line up.
- **`viewExDetailContract.blade.php:63` holds the same `die;` block** that break 7 deleted. That is
  the external portal, another page, so the map's scope leaves it alone.
- **`ContractController.php` has a second copy of the history swap** at about line 10597, with the
  same null break that break 9 fixed, and a second copy of the three name lookups at about line 4435.
  Another page each time. A later effort on those pages can take the same fix.

### Follow-up to break 8: `$reqfields` is `$reqfieldsText`, 2026-08-22

**The dev confirmed the guess.** The loop in `contractApprovals.blade.php:24` reads `$reqfieldsText`
now, so `?tab=timelineedit` fills its table instead of rendering an empty one. Commit `6515eb5`.

**The loop's shape fits.** `$reqfieldsText` is `key => label`
([ContractController.php:1023](../../../Modules/Contract/app/Http/Controllers/ContractController.php:1023)),
the eight keys are `contracts` columns and the eight labels are plain strings, so
`@empty($contract->$key)` works and no row prints a blank or an array-to-string notice.
`contractApprovalsView.blade.php:140` already reads the same map for its labels, so the two blades
agree on what it is for.

**The table matters even though nobody sees it.** It is `display:none`, but
[contract.js:1271](../../../Modules/Contract/resources/assets/js/contract.js:1271) and
[:1305](../../../Modules/Contract/resources/assets/js/contract.js:1305) count its rows and **disable
the popup's Send button** when it has any, on "Send For Approval" and "Send For Signing". So the rows
gate the approval flow.

What each contract prints:

| contract | rows in the table |
|---|---|
| 100479 | Termination - Date, Signing Date |
| 101143 | Termination - Date, Signing Date |
| 1 | Termination - Date, Signing Date, Relationship with Apollo Group |
| 16 | Contract End Date, Termination - Date, Signing Date, Relationship with Apollo Group |

**The guard stays.** `($reqfieldsText ?? [])` costs nothing while the variable is always set, and it
stops the next rename taking the page down.

**One key shape does not fit, and it is written down rather than forced.** 111 lines below the map,
[ContractController.php:1142](../../../Modules/Contract/app/Http/Controllers/ContractController.php:1142)
adds a row to `$reqfieldsText` for every **required custom field**, keyed by `custom_field_id`. A custom
field value lives in `custom_field_data`, not on the `contracts` row, so `$contract->$key` is `null` for
those keys and the field **always** prints "Missing".

Contract **16** proves it: `custom_field` 57, "Relationship with Apollo Group", is the one row in this
database with `required = 1`, its `contract_type` is 1, contract 16's `contract_type` is 1, and
`custom_field_data` holds `Related Party` for it. The table still says Missing.

`$reqfieldsVals[$key]` holds the real value for both the plain columns and the custom fields, and
`contractApprovalsView.blade.php` already pairs `$reqfieldsVals[$key]` with `$reqfieldsText[$key]` that
way. Two things stopped this ticket changing the test:

- It is a **wrong result, not a break**, and it costs no query and no time, so the
  performance rule in [CLAUDE.md](../../../CLAUDE.md) leaves it.
- `$reqfieldsVals['signing_date']` holds the **literal string `signing_date`** on a Signing contract
  ([:1126](../../../Modules/Contract/app/Http/Controllers/ContractController.php:1126)), which the other
  three blades treat as empty by testing `$inpVal == $key`. So swapping the test needs that rule copied
  in, and there is a third candidate array, `$reqfieldsVal`, whose `true` means missing for a custom
  field and `false` means missing for a plain column. Three arrays, three meanings of missing. **The
  dev picks.**

**Verified**: 30 loads, 13 tab values on `100479` and `1`, plus `16?tab=timelineedit` and
`101143?tab=timelineedit`, all **200**. Only the four `timelineedit` documents change, by 618-858
characters each; the other 26 match the pre-change document to the character, whitespace ignored.
`storage/logs/laravel.log` holds no error and no warning across the run. Browser console on
`1?tab=timelineedit`: the same two known entries, a 403 for an asset and the Tagify warning. Report
row 17.
