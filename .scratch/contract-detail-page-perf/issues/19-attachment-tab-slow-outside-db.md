# 19 — The attachment tab takes 2.2 s and the database is not the reason

Type: `wayfinder:task` (AFK)
Blocked by: nothing
Status: CLOSED

## Question

Where does the time go on `?tab=attachment`? It is the only tab on this page whose cost is not in the
database, so none of the map's other work will touch it.

## What ticket 18 measured

After the tab guard landed, every tab but Details and `historical` dropped to 300–520 ms. Except this
one:

| contract | tab | server `total_ms` | queries |
|---|---|---|---|
| 100479 | `attachment` | **2,638** | 91 |
| 1 | `attachment` | **2,185** | 93 |
| 100479 | `edit` | 369 | 96 |
| 100479 | `timeline` | 337 | 86 |

**91 queries and 2.6 s.** The edit tab runs more queries in a seventh of the time. So the time is
somewhere else.

## Where to look, in order

1. **The perf log first.** `storage/logs/perf-2026-08-22.log` already carries the phase breakdown per
   request — bootstrap, routing, controller body, blade render. One record for an attachment load says
   whether the time is in the controller or in the view before any guessing starts.
2. **An outbound call or a filesystem walk.** This tab shows contract documents. `fileStorageType()`
   decides local or remote storage, and the dashboard effort found it called five times per request. A
   remote storage check, a `Storage::exists()` per file, or a directory listing over a network path would
   all look exactly like this: slow, and invisible to the query count.
3. **File reads.** Reading a document to get its size or to build a preview costs wall-clock and no
   queries.

## Done when

- The 2.2 s is attributed to a named cause, with a measurement behind it, not a theory.
- If it is a fixable page cost, fix it and put the number in the report.
- If it is an outbound call or a storage round trip, say so plainly and say what it would take to move
  it off the page load — that becomes its own ticket rather than being guessed at here.

## Note

Do not fix this by making the tab load its documents on demand without measuring first. That is the
same shape as [ticket 07](07-page-size-decision.md) and it carries the same risk of a form field
leaving the document. Measure, then decide.

## Resolution

Closed 2026-08-22. Commit `1d5a5a1`. Report row 13.

### The phase

**The blade render, not the controller.** Four warm loads of `100479?tab=attachment`, read from
`storage/logs/perf-2026-08-22.log`:

| total_ms | bootstrap | controller | view render | queries | db ms |
|---|---|---|---|---|---|
| 3,250 | 879 | 211 | 1,940 | 91 | 176 |
| 2,306 | 250 | 150 | 1,869 | 91 | 154 |
| 2,171 | 155 | 158 | 1,827 | 91 | 156 |
| 2,420 | — | 255 | 1,898 | 91 | — |

The database is 154–176 ms of it. The view is 84–89% of the request.

### The cause

The tab's view is
[viewContractDocument.blade.php](../../../Modules/Contract/resources/views/contract/viewContractDocument.blade.php),
30 lines. It made **two** outbound calls to Google Drive, and both did the same work:

- `fileViewUrl($contract->contract_attachment, true)` -> `GoogleDriveController::getFileUrl()` ->
  `changePermission($fileid, Helpers::userInfo()->email ?? "", "", true)`
- `get_google_drive_doc_link(...)` -> `fileStorageTypeController()->changePermission($filepath,
  Helpers::userInfo()->email ?? '', '', true)`

Same file id, same email, same `$onlyView` flag. The second call repeats the first.

Temporary `Log::debug` timing, since removed:

| line | ms |
|---|---|
| `fileViewUrl()` | 914.9, 971.0, 957.9 |
| `get_google_drive_doc_link()` | 915.4, 862.6, 975.4 |

And inside each `changePermission()`:

| step | ms |
|---|---|
| token refresh (`fetchAccessTokenWithRefreshToken`) | 493.8, 529.4, 559.1, 581.8 |
| `files->get($fileid)` | 359.3, 379.6, 404.6, 422.4 |

So one attachment load makes **four** outbound requests to Google for one file link. The token
refresh runs every time because `changePermission()` builds a fresh `Google_Client` and never sets a
stored token, so `isAccessTokenExpired()` is always true.

`file_storage.id = 1` holds `type = 'Google'`, so this is the live path on this machine.

### Do the seeded contracts have real files?

**No, and it does not matter.** Seeded rows hold a made-up Drive id — `contract_attachment` is
`SEEDPERF000000100479`, `storage_type` is `Local` — so `files->get` throws and the tab prints
"Invalid User/File Access". But **contract 4 holds a real Google Drive id**
(`1f4s0npgp0wIMs6BZRNAntsj6G2kwtr5K`, `storage_type` Google, contract in Signing), its `files->get`
**succeeded** in 404–422 ms, and its page cost the same 2,428 ms. The failure path and the success
path cost the same. So the 2.1 s is not a timeout and not an error path.

### What changed

`fileViewUrl()` moved inside the blade's `Local` branch, which is the only branch that reads its
answer. On Google or Microsoft storage the tab now makes one round trip instead of two. On Local
storage nothing changes: there `fileViewUrl()` is a `Storage::exists()` on disk.

| page | before | after |
|---|---|---|
| `100479?tab=attachment` | 2,171–2,428 ms, 91 queries | **1,327–1,384 ms, 89 queries** |
| `4?tab=attachment` (real Drive file) | 2,428 ms, 94 queries | **1,365 ms, 92 queries** |

Output is unchanged. `100479` still prints the storage-mismatch notice; `4` still renders its
`docs.google.com/document/d/1f4s0npgp0wIMs6BZRNAntsj6G2kwtr5K/view` iframe. All 13 tab values on
`100479` and on `1` return 200 — 26 loads, no error in `laravel.log`.

### What is left, and the recommendation

**915 ms of one Google Drive round trip stays, and it cannot be removed without changing what the
user sees.** `changePermission()` is not a read: with `$onlyView = true` it skips
`permissions->update` but it still runs `permissions->create`, so the call is what **grants the
logged-in user access to the file**. Drop it and the iframe shows Google's 403 to anyone who has not
been granted the file before.

Moving it off the page load takes three things, and it is its own ticket — the same shape as
[ticket 10](10-esign-check-after-page-render.md):

1. **Render the tab without the link, then fetch it.** The tab already renders an iframe pointing at
   a Drive URL built from the file id alone; the permission grant is the only reason to wait. A small
   endpoint that grants and returns the URL, called from the page after it paints, moves the whole
   915 ms out of TTFB. This is not ticket 07's shape — the attachment tab holds no form field, so
   nothing can leave the document on save.
2. **Cache the OAuth token.** 494–582 ms of every call is a token refresh that does not depend on the
   file or the user. One access token, held in the cache until it expires, removes that half from
   every Drive call in the repo, not only this tab. `changePermission()` has many callers, so this is
   a change to `GoogleDriveController` and needs its own measurement.
3. **Skip the grant when the user already has it.** `files->get` already returns the permission list
   the code walks. A short-lived cache of "this user may see this file" would skip the round trip on
   a refresh.

Recommend 2 first: it is one place, it halves every Drive call on the site, and it needs no change to
what the page renders.

### Left alone, written down

- [viewExContractDocument.blade.php](../../../Modules/Contract/resources/views/contract/viewExContractDocument.blade.php)
  lines 6 and 14 hold the **same duplicate pair** — `fileViewUrl()` beside
  `get_google_drive_doc_link()`. It is the external-signer view, not this page, so the map's scope
  rule leaves it. It will cost the same 915 ms.
- [ContractController.php:145](../../../Modules/Contract/app/Http/Controllers/ContractController.php:145)
  in `documentViewer()` holds the same duplicate pair, and that method is what the iframe's
  `/showDocument/{id}` route serves on Local storage. Not this page's request, so not touched here.
