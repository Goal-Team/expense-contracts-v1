# 24 — The approval gate that has never run

Type: `wayfinder:grilling` (HITL)
Blocked by: nothing. **Not this effort's work** — recorded here so it is not lost.
Status: OPEN, and deliberately not started

## What this is

`contractApprovals.blade.php` holds a hidden table of "these required fields are Missing".
[contract.js:1272](../../../Modules/Contract/resources/assets/js/contract.js:1272) and
[:1306](../../../Modules/Contract/resources/assets/js/contract.js:1306) count its rows and **disable
the Send button** on "Send For Approval" and "Send For Signing".

**The gate has never run for anybody.** The table looped `$reqfields`, which nothing in the repo sets —
not on this branch and not on `main` — so the tab holding it returned HTTP 500 on
`Undefined variable $reqfields`. Everyone who clicked that tab got an error page instead of a gate.

## What happened on 2026-08-22, and why it was undone

[Ticket 03](03-find-remaining-breaks.md) guarded the loop with `?? []`, which is what makes the tab
render. That is a breakage fix and it stands.

Then the dev confirmed `$reqfieldsText` was the variable it had been renamed to, and the loop was wired
to it. **That was reverted the same day**, because it does more than fill a table:

1. **It switches a dead gate on.** Rows appear, so the Send button starts being disabled. Nobody has
   ever seen that behaviour, on any version of this app.
2. **The test is wrong for part of the map, so the gate would block complete contracts.**
   `ContractController` adds a row to `$reqfieldsText` for every required custom field, keyed by
   `custom_field_id`. A custom field value lives in `custom_field_data`, not on the `contracts` row, so
   `@empty($contract->$key)` is always true for those keys and the field always reads "Missing". Proved
   on **contract 16**, whose custom field 57 holds `Related Party` and which still reported Missing.

So wiring it up would have shipped a new gate, wrongly blocking approval on any contract with a
required custom field, inside a performance branch. Reverted.

## What a correct gate would read

The controller **already computes the right answer.** `$reqfieldsVal` is a map of
`field => is it satisfied`, and it carries the special cases the blade's test cannot know:

- `contract_end_date` is removed from it in one branch.
- `termination_date` is forced false when the contract ends by termination and the date is empty.
- `signing_date` is only required once the contract reaches Signing.

A gate that reads `$reqfieldsVal[$key]` gets all of that for free. A gate that reads
`$contract->$key` re-derives it, badly.

## Why it is not this effort's

This effort is load time ([CLAUDE.md](../../../CLAUDE.md), "Staying on a performance task"). Switching
on a control that blocks the approval flow is functional work with real consequences for users, and it
needs the dev to decide what the gate should actually enforce — which is why this is a grilling ticket,
not a task.

**It is also worth doing.** The gate exists because somebody wanted to stop a contract going for
approval with mandatory fields empty. That intent has been broken for as long as the tab has been
broken.

## Questions for the dev

- Should the gate be switched on at all? It has never run; turning it on will stop people doing
  something they can do today.
- Which fields should block Send, and which should only warn? `$reqfieldsVal` currently mixes both
  kinds.
- Should a required **custom** field block Send? That is the case the current test gets wrong, and it
  is the one that decides how much work this is.
