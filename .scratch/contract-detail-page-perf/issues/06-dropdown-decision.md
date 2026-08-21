# 06 — Decide whether this page's dropdowns move to the AJAX endpoints

Type: `wayfinder:task` (AFK)
Blocked by: 04, 12, 13
Status: OPEN

## The decision, already made

**The dev's call 2026-08-21. Every dropdown on this page loads on demand: the first 20 alphabetically,
then a search.** Not one hand-written endpoint per dropdown — **one abstract base class over Eloquent
models**, so adding a dropdown is a subclass and a config line.

The dashboard's `ContractOptionListController` is the precedent to read, and it may become the first
subclass, but it is not the answer on its own: it serves fixed lists, not paged search.

What the base class has to take: the model, the column to show, the column to return, the order, and
any `where` the dropdown needs. What it has to give back: a page of options and a total, in one shape,
so one piece of front-end code drives every dropdown on the page.

## Original question, kept for the history

This page loads every contract party, every contract type, all users and all branches to fill
dropdowns. The dashboard effort built AJAX endpoints for exactly this
([`ContractOptionListController`](../../../Modules/Contract/app/Http/Controllers/ContractOptionListController.php)).
Do this page's dropdowns move to them?

The dev's call 2026-08-21: **decide it in a ticket, after measuring.** Not a given, unlike on the
dashboard where it was decided up front.

## What is known

`ContractParties::select('*')->get()` appears twice in `viewContract` with no `where` at all — every
party row in the database, loaded twice, on every page view. Whether that actually costs anything at
this row count is ticket 04's job to say.

## Done when

- One abstract base class, and every dropdown on this page a subclass of it.
- **The saved value shows immediately, before any search runs.** This is the real design problem on an
  edit form: the dashboard's filters started empty, this page's already hold a value. The saved option
  has to be in the first payload whether or not it falls in the first 20 alphabetically.
- Selecting nothing must not clear the field. A dropdown that has not loaded yet must still submit the
  value it was saved with — otherwise saving the contract wipes it. Same risk as ticket 07.
- Query count and byte count before and after, in the report. Ticket 08 measured what this removes:
  three full reads of 1,605 users with `AES_DECRYPT`, all 99 branches read twice, and 214 departments.
- Verified in the browser: open the edit tab, change nothing, save, and compare the row. Nothing may
  change.
