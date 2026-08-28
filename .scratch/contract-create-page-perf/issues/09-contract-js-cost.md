# 09 — Trim contract.js on the create page

Type: `wayfinder:task` (AFK)
Blocked by: 03
Assignee: unclaimed
Status: OPEN

## Question

[`contract.js`](../../../Modules/Contract/resources/assets/js/contract.js) is 109 KB, 2,931 lines,
48 top-level functions, with two `$(document).ready` blocks and **no page guard**. The whole file
runs on every page that loads it.

Most of it is not create-page code. It holds approval flow, OTP send and check, obligations add and
delete, send-for-review, and contract link. The create page downloads all of it and runs all of it.

Ten pages load it: `contractCreate`, `contractCreateV3`, `contractCreateAi`, `contractCreateAiV2`,
`contractCreateCopy`, `contractCreateSimple`, `contractCreateType`, `contractImport`,
`renewDetailContract`, and `admin_settings/index`.

The dev's call 2026-08-28: **change it in place, and prove each change safe for every caller.**

Use the ticket 03 attribution to say what the file actually costs the create page in parse time and
run time first. Then take the wins in this order, stopping when the numbers stop moving:

1. **Dead code.** Functions and blocks nothing calls. Grep the repo including every blade before
   deleting one.
2. **Work that runs on load and should not.** A ready block that binds handlers for elements the
   create page does not have, or fires a request the create page does not need.
3. **Requests fired on load.** Name each one and say whether the create page needs it.

Every one of the ten pages must still work. Say in the resolution how each was checked. Report row
per change, small commits.
