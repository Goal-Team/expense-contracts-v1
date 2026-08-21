# 08 — Inventory every query the page runs, by reading the code

Type: `wayfinder:research` (AFK, subagent)
Blocked by: nothing — static reading only, no browser and no database writes
Status: OPEN

## Question

What queries does one request to `contracts/{id}` actually run, and which of them grow with the
number of contracts in the database?

Ticket 04 measures. This ticket reads. Both are needed: a measurement says which query is slow, the
inventory says why it exists and what it feeds. Doing the reading separately keeps a 15,203-line
controller out of the session that has to decide something.

## Done when

A table in this ticket lists, for every query in `viewContract` and in the views it renders:

- the file and line,
- the model and roughly the SQL,
- whether it runs once, once per row, or once per contract in the database,
- which blade file consumes the result — and **whether any blade file consumes it at all**. The
  dashboard effort found the page handing the view data it never used.

Plus, called out separately:

- every `whereIn` whose bound list grows with the dataset (the 1,000-value silent-empty bug),
- every `select('*')->get()` with no `where`,
- every place `availableContracts()` is called and what each call is for.

## Notes

No code changes in this ticket. Reading and reporting only.
