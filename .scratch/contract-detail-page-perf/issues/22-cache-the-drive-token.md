# 22 — Cache the Google Drive access token

Type: `wayfinder:task` (AFK)
Blocked by: nothing. Ticket 19 measured it and recommended it first.
Status: OPEN

## Question

Nothing to decide. Half of every Google Drive call is an OAuth token refresh that depends on neither
the file nor the user, and it runs every single time.

## What ticket 19 measured

Inside `GoogleDriveController::changePermission()`, per call:

| step | ms |
|---|---|
| token refresh (`fetchAccessTokenWithRefreshToken`) | 494–582 |
| `files->get($fileid)` | 359–422 |

The refresh runs every time because `changePermission()` builds a **fresh `Google_Client`** and never
sets a stored token, so `isAccessTokenExpired()` is always true. On the attachment tab that is 915 ms of
one round trip, and it is now the biggest single cost left on this page.

A Google access token lasts an hour. Refreshing it on every page view is pure waste.

## Why this is in scope even though it is shared code

`changePermission()` has many callers, and the map's scope rule says leave other pages alone. This one
earns an exception on two grounds, and both matter:

- It is **on this page's critical path** — 915 ms of the attachment tab, measured.
- **A cached token changes no output anywhere.** Same token, same API calls, same answers. Nothing any
  page renders can differ.

So change it in place rather than adding a second function beside it — the "many callers, write a new
one" rule in [CLAUDE.md](../../../CLAUDE.md) exists for changes that could alter behaviour, and this one
cannot. **But hold yourself to that claim**: if you find you cannot cache the token without changing
what a caller gets back, stop and say so instead of pressing on.

## How

- Cache the access token, not the client. Key it so two different Google accounts or two different
  credential files cannot share an entry.
- **Expire it before Google does.** Store it with a lifetime shorter than the token's own — a minute or
  two of margin — so a request never picks up a token that dies mid-call.
- **Handle the expired-token path.** If an API call fails on authentication, drop the cached token,
  refresh once, and retry once. Not a loop.
- **Never log the token, the refresh token, or the credentials file contents.** `Log::debug` the fact of
  a refresh and the cache hit or miss, nothing more. See [CLAUDE.md](../../../CLAUDE.md).
- Check which cache driver this app actually uses before assuming one. A `file` driver is fine here; an
  `array` driver would make this a no-op and the measurement would look like a failure.

## Done when

- A page load that makes a Drive call refreshes the token **once**, and the next load refreshes it not
  at all. Prove it from `Log::debug` lines or the perf log, not from the time alone.
- Attachment-tab TTFB before and after, in the report, on `100479` (made-up file id, the failure path)
  **and** on contract `4` (a real Drive id, the success path). Ticket 19 found both cost the same, so
  both should improve the same.
- **Every caller of `changePermission()` still works.** `grep` for them, list them in the ticket, and
  load the ones reachable from this page. Say which ones you could not reach and why.
- Every tab on `100479` and `1` returns 200. Ticket 03 got all 13 to 200 — do not be the one who breaks
  that.

## What ticket 19 recommended after this, and did not do

Both are separate tickets, not this one:

1. **Move the permission grant off the page load.** `changePermission()` is not a read — with
   `$onlyView = true` it still runs `permissions->create`, so it is what grants the logged-in user
   access. Deleting it shows Google's 403 to anyone not already granted. Rendering the tab and fetching
   the link after paint is [ticket 10](10-esign-check-after-page-render.md)'s shape, and safe here
   because the attachment tab holds no form field.
2. **Skip the grant when the user already has access.** `files->get` already returns the permission list
   the code walks.

Also out of scope, and recorded so a later effort finds them: `viewExContractDocument.blade.php` lines 6
and 14, and `ContractController.php:145` in `documentViewer()`, both still hold the duplicate
`fileViewUrl()` + `get_google_drive_doc_link()` pair ticket 19 removed from this page. Each will cost the
same 915 ms.
