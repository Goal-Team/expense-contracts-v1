# 06 — Decide whether this page's dropdowns move to the AJAX endpoints

Type: `wayfinder:grilling` (HITL)
Blocked by: 04
Status: OPEN

## Question

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

- A yes or no, with the measured cost of the dropdown data behind it.
- If yes: which dropdowns, whether the existing endpoints serve them unchanged, and how the selected
  value is shown before the AJAX call returns. That last one is the real design question on an **edit**
  form — the dashboard's filters started empty, this page's do not.
- The change landed and committed if the answer is yes.
