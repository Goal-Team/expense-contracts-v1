# 01 — Fix the crash on the edit tab when a reminder column is NULL

Type: `wayfinder:task` (AFK)
Blocked by: nothing
Status: OPEN

## Question

Nothing to decide. The edit tab throws and the page does not render. Make it render.

## What is known

`editRenew.blade.php` has **four** identical lines, not one:

- [:431](../../../Modules/Contract/resources/views/contract/editRenew.blade.php:431) `reminder_first_alertMeOn`
- [:481](../../../Modules/Contract/resources/views/contract/editRenew.blade.php:481) `reminder_second_alertMeOn`
- [:530](../../../Modules/Contract/resources/views/contract/editRenew.blade.php:530) `reminder_escalation_alertMeOn`
- [:578](../../../Modules/Contract/resources/views/contract/editRenew.blade.php:578) `reminder_escalation_alertMeOn_after`

Each does `explode(" ", decryptString($contract-><column>, '<column>'))` and then reads
`[0]`, `[1]` and `[2]` unguarded. The stored shape is three words — `30 days prior`. When the
column is `NULL`, `explode` returns one element and `[1]` throws `Undefined array key 1`.

3,000 of the 3,018 contracts have this column NULL. See ticket 02 for why.

## Done when

- The page renders for a contract with all four reminder columns NULL.
- The page still renders the stored values unchanged for a contract that has them — verify on one
  of the 18 pre-existing contracts.
- Verified in the browser on `contracts/100479?tab=edit`, not by a backend check.
- Committed on `claude/contract-edit-page-perf`.

## Notes

Do not paste the same guard four times. Pull it into one helper that takes the column value and
returns the three parts with sane defaults, and call it from all four places — CLAUDE.md, "one
function, one concern, do not copy blocks".
