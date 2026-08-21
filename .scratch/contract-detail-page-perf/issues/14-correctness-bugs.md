# 14 — The correctness bugs found while reading, fix the safe ones

Type: `wayfinder:task` (AFK)
Blocked by: 01 — no reason to wait for anything else; these are not speed changes
Status: OPEN

## The decision, already made

**Fix the safe ones, list the rest.** The dev's call 2026-08-21. A bug with one right answer gets fixed
here. A bug where the fix needs the dev's intent gets written down, not guessed.

## Fix these — one right answer each

1. **`Controller.php:295` can never be true.** `if (isset($contractParties->company_name))` names a
   variable that does not exist in that scope. The effect: `$partysName` never picks up an external
   party name, so every Related Contracts row shows internal parties only. Find the variable that was
   meant and use it.
2. **`X == !null` used as a null check**, at `ContractController.php:553`, `:573`, `:583`,
   `Controller.php:250`, `:293`, and `viewDetailContract.blade.php:2322` and `:2422`. PHP reads it as
   `X == true`. It happens to work on positive ids and is wrong for id `0`. Write the check it meant.
3. **`$contractsoldothers` can be undefined.** It is assigned inside `if ($contractsold)` at line 718
   and the blade loops it with no guard at `viewDetailContract:2261`. Give it an empty default so a
   missing contract row does not throw the page.
4. **Line 801 is dead** — line 944 overwrites it with the same query. Delete it. (Ticket 12 may get
   there first; whoever does it, do it once.)

## List these — they need the dev

5. **`$chartApprovals` is identical to `$approvals`.** The one clause that made them differ is
   commented out, so Chart View no longer shows the pre-approval stages the comment above it promises.
   Restoring the clause is a guess about what Chart View is meant to show. **Ask, do not guess.**
6. **`$approvalsArrExternal` has no `flag` filter**, unlike every other approvals read, and the
   `$partySigned` array it fills is indexed by `$externalSigned`, a counter that three separate
   branches increment. So which signature lands against which party depends on party order. This is a
   real bug with a real consequence on a signed contract, and fixing it means deciding what the
   correct mapping is. **Ask.**
7. **A GET request writes to the database.** Handled separately and already decided —
   [ticket 10](10-esign-check-after-page-render.md) moves the eSign block out of the page load. **The
   `user_action_log` insert at line 535 is the same problem and is not yet covered by any ticket.** It
   writes a row on every page load of a Signing or Approved contract, so opening a page ten times
   writes ten rows. Add it to ticket 10's scope, or say why it should stay.

## Done when

- Items 1 to 4 are fixed, each in its own small commit, each with what changed and why.
- Items 5 to 7 are written up here for the dev, with enough detail to decide without re-reading the
  code.
- Verified in the browser: external party names now appear in the Related Contracts party column, and
  nothing else on the page changed.

## Note

These are not speed changes, so they get no report row. Say so in the commit message rather than
inventing a measurement.
