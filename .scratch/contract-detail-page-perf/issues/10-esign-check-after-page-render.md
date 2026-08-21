# 10 — Take the eSign status check out of the page load

Type: `wayfinder:task` (AFK)
Blocked by: 04 — needs the baseline so the saving can be shown
Status: OPEN

## The decision, already made

**The dev's call 2026-08-21: show the page first, then check.** The page renders straight away. The
browser then asks for the signing status in the background and updates it on screen. Same behaviour
for the person using it; the page no longer waits on the signing company's server.

Nothing left to decide. This ticket builds it.

## What it looks like today

Inside
[`ContractController::viewContract`](../../../Modules/Contract/app/Http/Controllers/ContractController.php:275),
for any contract whose status is `signing` and substatus is `progress`:

1. Read the stored compose response from `esign_resposnses`.
2. Call `EsignApiController::getToken()` — an outbound HTTP call to get a token.
3. Call `EsignApiController::getEasySignLinks()` — a second outbound HTTP call.
4. If every signer shows `Signed`, download the signed PDF, update the contract attachment, and move
   the contract to Executed / Signed with `Contract::where('id', ...)->update(...)`.

So opening a page makes two outbound calls and can write four things. Two problems in one: the page
waits on a third party, and a GET changes data.

## Done when

- `viewContract` no longer makes the eSign calls. It renders the status it already has stored.
- A new endpoint does the check — token, links, and the Executed / Signed transition — and returns the
  result as JSON. It is a POST, not a GET, because it writes.
- The page calls it after render, only when the contract is in Signing / Progress, and updates the
  status area on screen when the answer comes back.
- **The transition still happens.** A contract that finished signing must still reach Executed /
  Signed, still get its signed PDF, and still get its attachment updated. Prove it on a contract in
  Signing / Progress, not only on one that is not.
- A report row: TTFB on a Signing / Progress contract, before and after.
- Committed on `claude/contract-edit-page-perf`.

## Watch out

- The write path must not run twice if the page is opened twice quickly. It updates the contract, so
  a second run on an already-Executed contract has to be a no-op, not a second attachment.
- Never log the token, and never log a decrypted contract field.
- Failure has to be quiet on screen. If the signing company's server is down, the page still works and
  shows the stored status. It must not show a broken page or an error the user cannot act on.
