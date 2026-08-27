# 22 — Cache the Google Drive access token

Type: `wayfinder:task` (AFK)
Blocked by: nothing. Ticket 19 measured it and recommended it first.
Status: CLOSED

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

## Resolution

Closed 2026-08-22. Commit `5396884`. Report row 14.

### The condition to stop on: it did not fire

The ticket's claim holds. **A cached token cannot change what a caller gets back.** `changePermission()`
returns `null` on success and the string `'Error permission file changes: ' . $e->getMessage()` on
failure, and it still does exactly that. The token is the same token Google hands back on a refresh, so
`files.get`, `permissions.update` and `permissions.create` see the same credentials and give the same
answers. So the method changed in place, as the ticket allowed, and no second function sits beside it.

### What was cached, and how it is keyed

`GoogleDriveController::authorizedClient($forceRefresh = false)` is new. It builds the `Google_Client`,
reads the access token from the cache, and refreshes only when the cache holds nothing usable.

- **Key:** `google_drive_access_token:` plus a `sha1` of the client id, the client secret, the token
  endpoint and the refresh token, joined with `|`. Two Google accounts, or two credential files, give two
  different keys, so one account can never read the other account's token. The key holds a hash, so the
  cache file name carries no secret.
- **Life:** `expires_in` minus `DRIVE_TOKEN_SAFETY_MARGIN`, which is 120 seconds. Google returned
  `expires_in` 3,599, so the entry lives **3,479 seconds**. A request cannot pick up a token that dies
  mid-call.
- **Belt as well as braces:** the cached token also goes through `isAccessTokenExpired()` before use. If
  the clock and the cache disagree, the token is dropped and refreshed.
- **Cache driver:** `CACHE_DRIVER=file` in [.env](../../../.env), confirmed live with
  `config('cache.default')`. A `file` driver keeps the token between requests, which is the whole point.
  An `array` driver would have made this a no-op.

The old method body moved to a private `applyFilePermissions($client, ...)`. It throws instead of
catching, so `changePermission()` owns both the error string and the one retry. Nothing was copied.

### The retry path, and what does not trigger it

On an exception, `isDriveAuthFailure()` decides. Code 401 or 403 **and** a message naming an
authentication fault drops the cached token, refreshes once and retries once. Not a loop: the retry runs
`applyFilePermissions()` inside its own `try`, and a second failure returns the error string.

A **404 for a missing file does not touch the cache.** That matters here: the seeded contracts hold a
made-up Drive id, so their `files.get` always 404s, and a page view of `100479` must not throw the good
token away.

**Proved, not assumed.** A bogus access token was written into the cache by hand, with a valid
`expires_in`, so the client believed it. Then `4?tab=attachment` was loaded:

```
02:30:01 DEBUG   Google Drive token: cache hit, no refresh.
02:30:01 WARNING Google Drive rejected the access token. Dropping the cached token and retrying once.
                 {"file_id":"1f4s0npgp0wIMs6BZRNAntsj6G2kwtr5K","code":401}
02:30:01 DEBUG   Google Drive token: cache miss, refresh runs. {"forced":true}
02:30:02 DEBUG   Google Drive token: refreshed and cached. {"seconds":3479}
```

The page rendered its `docs.google.com/document/d/1f4s0npgp0wIMs6BZRNAntsj6G2kwtr5K/view` iframe, the
same iframe ticket 19 recorded, and printed no "Invalid User/File Access".

### The refresh count

`php artisan cache:clear` first, then 12 Drive calls across the loads below. `laravel.log` holds:

| line | count |
|---|---|
| `cache miss, refresh runs` with `forced` false | **1** |
| `cache hit, no refresh` | **10** |
| `cache miss, refresh runs` with `forced` true | 1 (the retry test above) |

So the first load refreshes once and every load after it refreshes not at all. That is the ticket's
"done when", read from the log lines and not from the time.

Nothing in the log names the token, the refresh token, the cache key or the credentials file. The
`Log::debug` lines carry the words hit, miss and forced, and the lifetime in seconds. The `Log::warning`
carries the file id and the HTTP code.

### The numbers

Warm, `DEBUGBAR_ENABLED=false` confirmed in [.env](../../../.env) and left as it was, read from
`storage/logs/perf-2026-08-22.log`.

| page | before (row 13) | after |
|---|---|---|
| `100479?tab=attachment` (made-up Drive id, the 404 path) | 1,327-1,384 ms, 89 queries | **1,066-1,212 ms, 89 queries** |
| `4?tab=attachment` (real Drive id, the success path) | 1,365 ms, 92 queries | **1,194-1,256 ms, 92 queries** |

**Both improved, and by about the same amount**, which is what ticket 19 predicted: the two paths cost
the same because the failure is a 404 from a live call, not a timeout.

**The saving is smaller than ticket 19 measured, and the reason is the network, not the cache.** Ticket
19 timed the refresh at 494-582 ms. Today the same refresh costs about **230 ms**: the one miss load ran
1,392 ms while the hit loads in the same minute ran 1,066-1,212 ms. So the cache removes the whole
refresh, and the whole refresh is simply cheaper today than it was yesterday. The refresh count above is
the honest proof; the milliseconds are the day's round trip to `oauth2.googleapis.com`.

The view render phase shows the same shape: 951-971 ms on a refresh load, 717-807 ms on a cache hit.

### Every caller of `changePermission()`

`grep -rn "changePermission" --include=*.php --include=*.blade.php`, vendor and node_modules excluded.

**Straight onto `GoogleDriveController`, so they get the cache:**

| caller | reachable from this page? |
|---|---|
| `GoogleDriveController.php:507` in `getFileUrl()` | **yes.** `fileViewUrl()` reaches it, on the `Local` branch of the attachment tab. |
| `app/helpers.php:502` in `get_google_drive_doc_link()` | **yes.** This is the attachment tab's own call, and the one measured above. |

**Through `fileStorageTypeController()`, which picks the driver by `file_storage.type`:**

| caller | reachable from this page? |
|---|---|
| `ContractController.php:3589` | no - contract create / save |
| `ContractController.php:3706` | no - contract create / save |
| `ContractController.php:4316` | no - approval action |
| `ContractController.php:5577` | no - contract update |
| `ContractController.php:5692` | no - contract update |
| `ContractController.php:6195` | no - approval action |
| `ContractController.php:7883` | no - signing action |
| `ContractController.php:8583` | no - signing action |
| `ContractController.php:9516` | no - signing action |
| `ContractImportController.php:1332` | no - bulk import |
| `ContractController.php:1876` | commented out |

**Eleven callers could not be loaded, and the reason is the same for all of them: every one sits behind a
POST that writes a contract** - create, update, approve, sign or import. This is a GET page's speed
ticket, and the map's rule on save testing says to copy a real contract first and save on the copy. That
is more work than this change needs, because the two GET callers run the identical code:
`changePermission()` has one body, and both a real Drive id and a made-up one went through it. What the
eleven pass differently is `$prev_email`, `$current_email` and `$onlyView = false`, and none of those
three touches the token.

`LocalDriveController::changePermission()` and `MicrosoftDriveController::changePermission()` are
different classes with the same method name. Neither was touched.

### Every tab still returns 200

26 loads, one `fetch` each, warm, `100479` and `1`, all 13 tab values including `zzz` for an unknown tab
and no `?tab` at all.

| tab | 100479 | 1 |
|---|---|---|
| `details` | 200, 239,076 | 200, 261,223 |
| `pre-approval` | 200, 105,779 | 200, 100,708 |
| `timeline` | 200, 105,779 | 200, 100,708 |
| `timelineedit` | 200, 80,873 | 200, 109,462 |
| `edit` | 200, 326,231 | 200, 321,430 |
| `flow` | 200, 72,388 | 200, 89,080 |
| `history` | 200, 61,437 | 200, 62,752 |
| `historical` | 200, 239,117 | 200, 261,264 |
| `attachment` | 200, 61,350 | 200, 57,345 |
| `obligation` | 200, 85,534 | 200, 81,584 |
| `e-stamp` | 200, 67,003 | 200, 62,993 |
| `zzz` | 200, 239,076 | 200, 261,223 |
| none | 200, 105,779 | 200, 100,708 |

**Every count matches ticket 03's table character for character.** The document is 61,365 bytes, the warm
transfer 61,965 bytes over 62 requests - row 13's numbers exactly. `laravel.log` holds no error and no
warning across the 26 loads, apart from the one warning the retry test made on purpose. The browser
console on the attachment tab holds the same seven entries ticket 03 recorded: three logs, one
deprecation, one 403 for an asset, one form-field issue, one Tagify warning.

### Left alone, written down

- **Ten other methods in `GoogleDriveController` still build their own client and refresh their own
  token**: `storeFile` :63, `get_file_path` :176, `storeContent` :256, `updateFileContent` :303,
  `copyFile` :341, `checkParentFolder` :442, `downloadUrl` :475, `setFilePermission` :640, `getComments`
  :722, `storeFileBypath` :760. Each holds the same six lines. `authorizedClient()` is public, so moving
  them over is one line each - but every one of them is on a POST path, none is on this page, and the
  map's scope rule keeps this ticket to the page. **Whoever takes them gets the same 230-500 ms per call
  for almost no work.**
- The two duplicate `fileViewUrl()` + `get_google_drive_doc_link()` pairs ticket 19 recorded still stand:
  `viewExContractDocument.blade.php` lines 6 and 14, and `ContractController.php:145` in
  `documentViewer()`. Each now pays one refresh less, but each still makes two round trips where one
  would do.
- Ticket 19's other two recommendations are untouched and still worth doing: move the permission grant
  off the page load, and skip the grant when `files.get` already shows the user has access.

### Could not verify

- **A real expired token.** The 401 path was proved with a token the code believed and Google did not,
  which is the same code path an hour-old token takes. Waiting an hour for a genuinely expired token was
  not done.
- **The eleven POST callers**, for the reason in the caller table above.
