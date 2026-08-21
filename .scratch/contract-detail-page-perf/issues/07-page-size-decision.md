# 07 — Decide which page-size cuts this page gets

Type: `wayfinder:task` (AFK)
Blocked by: 04
Status: OPEN

## The big decision is already made

**Load tabs on demand. The dev's call 2026-08-21.** Only the open tab renders. The others fetch when
clicked. This was the one question on this page that needed the dev, and it is answered.

The dev knows the cost: browser find-in-page no longer searches across closed tabs, and any script
that reads a hidden tab's fields stops working. **Finding every such script is part of this ticket** -
the edit form posts one big form, so check what the submit handler reads before you move anything out
of the document.

## Remaining question

Which of the byte cuts apply to this page, and which are already handled globally by the dashboard
effort?

## What is known

The dashboard effort decided six cuts and found three facts that are server-wide, not page-specific:
IIS gzip only engages from the second request for a file, the HTML document is not compressed at all,
and content-hashed build assets carry no `Cache-Control`. Those are the same server, so they either
already landed or they are still pending for every page. See
[../../contracts-dashboard-perf/issues/22-reduce-page-size.md](../../contracts-dashboard-perf/issues/22-reduce-page-size.md).

What is page-specific here: this page renders far more markup than the dashboard — a 894-line edit
form plus every other tab, all in one document, whether the user opens those tabs or not.

## Done when

- Say which dashboard cuts already cover this page and need nothing.
- Tabs load on demand, and the edit form still saves everything it saved before - proved by saving a
  contract and comparing the row before and after, not by looking at the page.
- Landed and committed, with a report row carrying all three byte numbers.

## Watch out

The edit form is one form spanning tabs. If a field lives in a tab that is no longer in the document,
it is no longer submitted, and saving the contract silently wipes that column. This is the whole risk
of this ticket. Either the fetched tab is inserted into the same form before submit, or each tab saves
on its own. Decide which, write down which, and prove the save.
