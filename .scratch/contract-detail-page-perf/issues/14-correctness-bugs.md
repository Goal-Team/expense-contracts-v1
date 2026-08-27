# 14 — The correctness bugs found while reading, fix the safe ones

Type: `wayfinder:task` (AFK)
Blocked by: 01 — no reason to wait for anything else; these are not speed changes
Status: CLOSED — see [Resolution](#resolution). Items 1, 2, 5, 6 and 7 are out of scope on the dev's
speed-only ruling and are written up at the end for a later effort.

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

## Scope change, 2026-08-21

The dev ruled during this ticket: **this effort is about page speed only. Bad logic that does not
break the page and does not cost time is fine to leave.** Their words: "even bad logic, as long as it
is not breaking the page, is okay to have. just focus on the performance of the page."

So the ticket shrank to three things:

- Item 3 — it throws the page, so it stays.
- Item 4 — pure waste, so it stays.
- One new item — `$chartApprovals` repeats the `$approvals` query. Speed, and it needs no decision.

Items 1, 2, 5, 6 and 7 are out of scope on that ruling. They are written up below for a later effort.

## Resolution

Branch `claude/contract-edit-page-perf`. Three commits, one change each.

| commit | change |
|---|---|
| `63a1db2` | Item 3. `$contractsoldothers` gets `collect()` before the `if ($contractsold)` block. |
| `d1173aa` | Item 4. The dead `$contractsSubseqList` query is gone. |
| `8858fce` | New item. `$chartApprovals = $approvals;` replaces the repeated query and decrypt loop. |

### Item 3 — `$contractsoldothers` can be undefined

`viewContract` only assigned the variable inside `if ($contractsold)`.
`viewDetailContract.blade.php:2261` loops it with no guard, so an undefined variable throws the page.

The trigger is not "the contract row is gone". It is the global scopes on `Contract`.
`$contractsold = Contract::select('*')->where('id', $id)->first()` runs through
`ContractRoledBasedScope` and `BranchScope`, so for a contract the signed-in user may not see,
`$contractsold` is `null` while the route still resolves and the blade still renders. The fix gives
the variable an empty collection, so the loop runs zero times instead of throwing.

**Query count: unchanged.** `collect()` touches no database.

### Item 4 — the dead `$contractsSubseqList` query

Line 801 ran `Contract::whereIn('id', $finalListChild)->where('id','<>',$id)->where('status',1)->get()`.
Line 944 ran the same query with `select('*')` added and overwrote the result. Nothing between the two
lines read the variable; checked with `grep` over the controller and all blades.

**Query count: 2 fewer.** The query itself, plus the `contractPartyList` eager load that
`Contract::$with` adds to every `Contract` query.

### New item — one approvals query instead of two

`$approvals` (line 955) and `$chartApprovals` (line 980) were the same query written twice: same
table, same `contract_id`, same `flag <> -1`, same `superseded = 0`, same `orderBy('id','DESC')`, and
the same seven-column `decryptString` loop over every row. `$chartApprovals` now reuses the
`$approvals` result.

Behaviour is byte-identical, on purpose:

- The commented-out `flow_type` clause on `$approvals` stays commented out. Neither list changes which
  rows it holds.
- `contractFlow.blade.php` is the only reader (lines 64 and 97). It wraps both in `collect()` and only
  reads the rows, so one shared collection is safe. Nothing writes to a row.

**Query count: 1 fewer, plus one full decrypt pass over every approval row.** Contract 4 has 21
approval rows; a busy production contract has more.

### Measured

`SHOW GLOBAL STATUS LIKE 'Questions'` around one browser load of `100479?tab=edit`, warm, debug bar
off, same session:

| state | queries |
|---|---|
| commit before these three | 261 |
| after item 4 | 259 |
| after the approvals dedupe | 258 |

Each reading was taken twice and both agreed. The counter is server-wide, so another agent's traffic
could land in the window; the repeat is the guard against that. Row 3 of
[measurements/report.md](../measurements/report.md) carries the number.

### Verified in the browser

Chrome over CDP, logged-in session, debug bar off. `storage/logs/laravel.log` did not grow by a single
byte across every load below, and the only console output is a pre-existing 403 on one asset and a
`Tagify: input element not found` warning.

| page | result |
|---|---|
| `100479?tab=edit` | renders, 396,492 bytes of DOM, no error text |
| `1?tab=edit` (real contract, control) | renders, 391,231 bytes of DOM, no error text |
| `100479` default tab | renders, Chart View tab present |
| `100479?tab=history` | renders |
| `4?tab=history` | renders |
| `4?tab=flow` (Chart View, 21 approval rows) | renders |

**The Chart View proof for the dedupe.** Contract 4 holds 21 approval rows with `flag <> -1` and
`superseded = 0`, the most of any contract in the seeded set. `4?tab=flow` was captured with the new
code, then the controller was put back to `d1173aa` and the same page captured again. The rendered
HTML is **115,939 bytes both times**, and the chart text is the same. The only difference in the two
text captures is a "Test Environment" banner at the top of the page, which is not part of the chart.

### Two things found on the way

1. **The party column the ticket asked for does not exist.** The ticket's "done when" asked for
   external party names to appear in the Related Contracts party column. The three Related Contracts
   tables in `viewDetailContract.blade.php` (lines 2304, 2360, 2406) have six columns — Contract Name,
   Signing Date, Contract Value, Effective Date, End date, Actions — and no party column. There is
   nothing on that panel for item 1 to make visible. See item 1 below.
2. **Related Contracts did not render on any contract we could load.** It sits inside
   `@elseif ($currentTab == 'history')` in the second tab chain (`viewDetailContract.blade.php:1197`),
   and `?tab=history` on both contract 100479 and contract 4 rendered without it. `Category Previous
   Contracts` (the `$contractsoldothers` table, line 2244, and it carries `d-none`) is in the same
   region and also did not render. So a large block of that blade is unreachable through the tab
   parameter, at least for these contracts. Worth its own ticket: either a condition nobody intends,
   or a whole panel that is dead.

## Out of scope on the dev's ruling — for a later effort

### Item 1 — `Controller.php:295` can never be true

`app/Http/Controllers/Controller.php:295` reads `if (isset($contractParties->company_name))` inside
`availableContracts()`. `$contractParties` does not exist in that scope. The variable meant is
`$contractPart->partyDetailsEx`, the `BelongsTo` on `ContractPartyData`
(`app/Models/ContractPartyData.php:43`) that the very next line already uses.

The effect: `$partysName` never picks up an external party name, so `$contract->contractPartyNames`
holds internal names only.

**Why leaving it costs nothing, and why fixing it costs something.** `grep -ri contractpartynames`
over the whole repo, blades included, finds exactly two live hits: the assignment at
`Controller.php:326`, and one backup file `ContractController.php-bcc`. **No blade and no JavaScript
reads `contractPartyNames`.** So the wrong value reaches no screen today.

Fixing it makes the page slower. `partyDetailsEx` is not eager loaded — `Contract::$with` is only
`['contractPartyList']` — so `$contractPart->partyDetailsEx->company_name` fires one lazy query for
every external party row. Every seeded contract has one, so the three Related Contracts lists on this
page would pay up to one query per related contract. `availableContracts()` has **58 call sites**
across `ContractController`, `ContractDashboardController`, `ContractExportController`,
`ContractImportController`, `ContractReportsController`, `TasksController` and
`ContractVisibilityQuery`, so every one of them pays it, for a value none of them shows.

Whoever picks this up should fix the variable **and** add the eager load in the same commit, or the
query count goes up for nothing. There is also a second, smaller bug in the same line: it appends
`',' . $name . ','`, and `preg_replace('/^,|,$/', ...)` at line 324 only strips one comma from each
end, so two external parties would render as `A,,B`. That has never been seen because the branch has
never run.

The right home for this is the `Contract::visibleTo($user)` work in
[ticket 13](13-visible-to-scope.md), which already replaces this loop.

### Item 2 — `X == !null` used as a null check

PHP reads `$x == !null` as `$x == true`. The seven places the ticket named:

- `Modules/Contract/app/Http/Controllers/ContractController.php:551`, `:579`, `:587`
- `app/Http/Controllers/Controller.php:250`, `:293`
- `Modules/Contract/resources/views/contract/viewDetailContract.blade.php:2322`, `:2422`

`grep -rn "== !null"` finds **39** of these across the repo, so the seven are a small part of one
habit.

**Measured against the live data, this is safe today.** In `contract_party_data`:

| party type | rows | `contract_party_location_id` | `contract_party_exe_id` |
|---|---|---|---|
| Internal | 3,920 | all set, none 0, none empty | all NULL |
| Intergroup | 2 | all set, none 0, none empty | all NULL |
| External | 3,018 | all NULL | all set, none 0 |

`contract_party_location_id` is `varchar(32) NULL` and `contract_party_exe_id` is `int(11) NULL`.
Neither ever holds `0` or an empty string, so `== !null` and `!== null` pick the same rows. Both
spellings cost one comparison. Nothing to gain on speed.

It becomes wrong the moment a branch id or an external party id of `0` exists, and it is wrong in a
quiet way: the party is skipped, no error is logged, and a name or a signature tick goes missing.
Whoever fixes it should write `!== null`, not `!= null` — loose `!=` treats `0` and the empty string as
null and brings the same bug back.

### Item 5 — `$chartApprovals` no longer differs from `$approvals`

Now visible in one line at `ContractController.php:983`. The comment above `$approvals` (line 953)
says the timeline excludes pre-approval flow rows, and the clause that did it is commented out:

```php
// ->where(function ($q) {
//     $q->where('flow_type', '<>', 'preapproval')->orWhereNull('flow_type');
// })
```

The comment above the old `$chartApprovals` promised Chart View shows the **full** flow including the
review, negotiation and finalization stages. With the clause off, both lists hold every non-superseded
row, so Chart View and the timeline show the same thing and the promise in the comment is not kept.

**The question for the dev, unchanged:** should the timeline hide pre-approval rows again? If yes,
uncomment the clause on `$approvals` only, and `$chartApprovals` goes back to its own query — the
dedupe in `8858fce` must be reverted at the same time. If no, delete the commented-out clause and both
comments, because they describe behaviour the code does not have.

Restoring the clause is a guess about what Chart View is for, so it was not guessed.

### Item 6 — `$approvalsArrExternal` and the party-to-signature mapping

`ContractController.php:487` builds `$approvalsArrExternal` with **no `flag` filter**, unlike every
other approvals read on the page, which use `flag <> -1` or `flag = 1`. So it includes superseded and
withdrawn approval rows.

It fills `$partySigned` and `$partyMails`, indexed by `$externalSigned`. `$externalSigned` starts at 0
and is incremented in **three** separate branches of the party loop: line 551 (Internal), line 579
(Intergroup) and line 587 (External). Each branch also reads `$partySigned[$externalSigned]` to decide
whether to draw a party as signed and whether to show its mail icon.

The consequence: **which signature lands against which party depends on the order the party rows come
back in**, and the counter advances for internal parties too, which have no external signature. On a
signed contract with a mixed party list, the tick can sit on the wrong party.

Fixing it means deciding what the correct mapping is — most likely a key on the party row rather than
a running counter. That is the dev's decision, so it was not guessed. This is the one item here that
shows a wrong thing on screen, so it is worth ranking above items 1 and 2 when the dev picks it up.

### Item 7 — a GET request writes to the database

Two writes on a page load:

1. The eSign block, `ContractController.php:281-384`. Already owned by
   [ticket 10](10-esign-check-after-page-render.md).
2. `ContractController.php:535`, `$this->crudUserActionLog(...)` with `signing-email`. It runs whenever
   `contract_status` is `signing` and `substatus` is `approved`. **No ticket covers it.**

The second one writes a `user_action_log` row on every page load of such a contract, so opening the
page ten times writes ten rows. A refresh, a browser prefetch or a crawler each add one. 250 contracts
in the seeded set are in `Signing` status.

Two costs, not one. Each load pays an `INSERT`, and `user_action_log` grows without limit, which makes
the `$signedHistory` full scan (ticket 08, call-out 9) slower every day.

The recommendation stands: add it to ticket 10's scope, so both writes leave the render path together.
It was left alone here because moving a write is a behaviour change and ticket 10 owns that decision.
