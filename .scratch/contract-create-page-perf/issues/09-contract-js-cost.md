# 09 — Trim contract.js on the create page

Type: `wayfinder:task` (AFK)
Blocked by: 03
Assignee: unclaimed
Status: CLOSED 2026-08-29 — out of scope

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

## Resolution

**Ruled out of scope by the dev, 2026-08-29.** Not resolved on the route — closed as scope.

The ticket 03 attribution priced it: `contract.js` costs **0.85–0.99 s of script time** against a
page whose server time is now about 0.6–0.9 s. It is real, and it is the smallest thing left.
The dev chose ticket 05 over it and said to stop there.

One change did land in this file, from ticket 08: the `.partyExternal` change handler fetches the
picked party's address instead of relying on 7.6 MB of pre-rendered markup. The show/hide path
was left exactly as it was so the nine other pages that load the file behave as before.

If this is picked up later, the order in the question above still holds, and the numbers to beat
are in [measurements/report.md](../measurements/report.md).
