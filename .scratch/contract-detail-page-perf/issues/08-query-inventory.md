# 08 — Inventory every query the page runs, by reading the code

Type: `wayfinder:research` (AFK, subagent)
Blocked by: nothing — static reading only, no browser and no database writes
Status: DONE

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

## Findings

Status: DONE, 2026-08-21. Read-only. No code changed.

Counted on contract **100479** (Draft / Initial Draft, `parentcontract` 0, one Internal party at
branch 3, one External party) against the seeded database: **3,018 contracts, 6,940 party rows,
13,867 approval rows, 1,605 users, 99 branches, 1 external party company, 6 active custom fields in
categories 1-4**.

**One request runs about 375 queries.** 234 of those come from two loops over the 58 related
contracts. The rest is a long flat list, with many exact duplicates.

### How to read the "how often" column

- **once** - one query per request.
- **per party row** - one per row of `$contractParty` (2 rows for 100479).
- **per related contract** - one per row of `$contractspartsList` (58 rows for 100479).
- **hidden** - a query the line does not show. Two scopes and one eager load fire on their own:
  - every `Contract` query runs `ContractRoledBasedScope`, which calls `admin_setting()`. That is an
    uncached `select admin_value from admin_settings` **per Contract query**.
  - every `Contract` query with rows also loads `contractPartyList`, because `Contract::$with` names
    it. One extra `select * from contract_party_data where custom_field_group_id in (...)`.
  - every `BranchUser` query runs `BranchScope`, which calls `Helpers::getEntityBranches()`. That is
    **two** more queries (`UserCredentials`, then `ContractUsers`), and the `ContractUsers` one
    decrypts `UserName` in the `WHERE`, so it scans all 1,605 rows.

### The table

| # | File:line | Model / SQL | How often | Blade that uses it |
|---|---|---|---|---|
| 1 | ContractController.php:262 | `Contract` where id, status=1 (+2 hidden) | once | via `availableContracts` -> nothing in the tree; only builds `$contract` for the eSign block and `$preApprovalSteps` |
| 2 | Controller.php:186 | `BranchUser::pluck(BranchName)` (+2 hidden) | 4x, one per `availableContracts` call | none directly |
| 3 | Controller.php:187 | `EntityBusiness::pluck(id)` | 4x | none directly |
| 4 | Controller.php:189 | `EntityMain::pluck(Nameoftheentity)` | 4x | none directly |
| 5 | Controller.php:228 | `ContractCategories::find(catgoery_id)` | **per contract in the passed list** (58+1) | feeds `$contractsoldother->catgoery_id` |
| 6 | Controller.php:321 | `$contract->contractTypeData` lazy load | **per contract in the passed list** (58+1) | `viewDetailContract` party tables |
| 7 | ContractController.php:281 | `EsignResposnse` where contract_id, status=1, latest | once, only when status Signing/Progress | none - drives a write |
| 8 | ContractController.php:356 | `Contract::find(parentcontract)` | only in the eSign path | none |
| 9 | ContractController.php:376 | `Contract::where(id)->update(...)` **WRITE** | only in the eSign path | none |
| 10 | ContractController.php:405 | `CustomFields` where status=1 order by order_id | once | `viewDetailCustomField`, `editCustomField` |
| 11 | ContractController.php:407 | `ContractType::get()` | once | overwritten at 665 - **wasted** |
| 12 | ContractController.php:410 | `ContractHistory` where id (no index on `id`, full scan) | once | `viewDetailContract:1209` |
| 13 | ContractController.php:415 | `AddUsers::select(...)` all 1,605 rows, 4 `AES_DECRYPT` columns, no `where` | once | `viewDetailContract`, `contractObligation`, `editRenew` |
| 14 | ContractController.php:423 | `AddUsersSel::select(...)` - **same table, same rows**, one extra column | once | `editRenew` only |
| 15 | ContractController.php:431 | `LegalAdvisor` where status=1 | once | `viewDetailContract` |
| 16 | ContractController.php:452 | `ContractHistory` where history_id | only with `?history=` | `$contract` |
| 17 | ContractController.php:453 | `ContractPartyDataHistory` where history_id | only with `?history=` | `$contractPartyData` |
| 18 | ContractController.php:455 | `Contract` where id first (+2 hidden) | once | `$contract` - used by 20+ blades |
| 19 | ContractController.php:456 | `ContractPartyData` where custom_field_group_id | once | `$contractPartyData` in `viewDetailContract` |
| 20 | ContractController.php:459 | `ApprovalContracts` where contract_id, flag<>-1, superseded=0, not preapproval | once | `activitytimeline`, `contractApprovals*`, `viewDetailContract` |
| 21 | ContractController.php:487 | `ApprovalContracts` where contract_id - **no flag filter** | once | **no blade at all.** `$approvalsArrExternal` never reaches the view. It only builds `$partySigned` / `$partyMails`, which stay empty unless the contract is Executed or Signing. |
| 22 | ContractController.php:535 | `crudUserActionLog` -> `AddUsers` lookup + **WRITE** to `user_action_log` | once, only when Signing/Approved | none |
| 23 | ContractController.php:541 | `EntityMain` where id first | **per party row** | `$contractPartyData` |
| 24 | ContractController.php:558 | `BranchUser` 11 `AES_DECRYPT` columns, where id first (+2 hidden) | once, guarded by `empty($branchFirst)` | `viewDetailContract` |
| 25 | ContractController.php:589 | `ContractParties` where id | per External party row | `$contractPartyData` |
| 26 | ContractController.php:604 | `ContractCategories` where id first | once | `$contract->catgoery_id` |
| 27 | ContractController.php:610 | `EntityBusiness` where id first | once | `$contract->department_id` |
| 28 | ContractController.php:617 | `ContractType` where contract_type_id first | once | `$contract->contract_type` |
| 29 | ContractController.php:620 | `ApprovalContracts` where contract_id, flag=1 | once | `viewDetailContract:2474` |
| 30 | ContractController.php:638 | `ApprovalContracts` where contract_id, flag=0 | once | `viewDetailContract` |
| 31 | ContractController.php:663 | `CustomFields` where status=1 - **exact repeat of 405** | once | same |
| 32 | ContractController.php:664 | `Category` where category_group='contract' | once | **no blade in the tree.** `$categorys` is read only by `createfield`, `createfieldlist`, `partyCustomField*` - none of them is included here. |
| 33 | ContractController.php:665 | `ContractType::get()` - **exact repeat of 407** | once | `viewDetailContract`, `editRenew` |
| 34 | ContractController.php:667 | `BranchUser` 11 `AES_DECRYPT` columns, all 99 rows, no `where` (+2 hidden) | once | `contractObligation`, `partyDetailsEdit` |
| 35 | ContractController.php:682 | `Branch` - **same `branch` table, same 11 columns, no `where`, no scope** | once | `partyDetailsView`, `partyDetailsEdit` |
| 36 | ContractController.php:697 | `EntityMain::get()` all rows, no `where` | once | `partyDetailsView`, `partyDetailsEdit` |
| 37 | ContractController.php:701 | `ContractParties::select('*')->get()` no `where` | once | overwritten at 765 - **wasted** |
| 38 | ContractController.php:703 | `ContractCategories::select('*')->get()` no `where` | once | `viewDetailContract`, `editRenew` |
| 39 | ContractController.php:705 | `EntityBusiness::select('*')->get()` no `where` (214 rows) | once | `viewDetailContract`, `editRenew` |
| 40 | ContractController.php:711 | `ContractPartyData` where custom_field_group_id - **exact repeat of 456** | once | `$contractPartys` in 5 blades |
| 41 | ContractController.php:715 | `Contract` where id first - **third read of the same row** (+2 hidden) | once | `$contractsold`, used only for its 3 ids |
| 42 | ContractController.php:718 | `Contract` where catgoery_id, department_id, contract_type, id<>, get (+2 hidden). **No index on any of the three, so a full scan of 3,018 rows** | once | `viewDetailContract:2261` |
| 43 | ContractController.php:725 | `ContractPartyData` pluck contract_party_location_id (internal) | once | none directly |
| 44 | ContractController.php:727 | `ContractPartyData` pluck contract_party_exe_id (External) | once | none directly |
| 45 | ContractController.php:729 | `ContractPartyData::whereIn('contract_party_location_id', ...)` pluck | once | none directly |
| 46 | ContractController.php:731 | `ContractPartyData::whereIn('contract_party_exe_id', ...)` pluck. **No index on `contract_party_exe_id`, so a full scan of 6,940 rows**, and it returns all 3,018 contracts | once | none directly |
| 47 | ContractController.php:736 | `Contract::whereIn('id', $FinalContractList)` (+2 hidden) | once | `$contractspartsList` -> `viewDetailContract:2402` |
| 48 | ContractController.php:751 | raw `DB::select` parent-chain query. Derived table over **all 3,018 contracts** with `ORDER BY id DESC` and session variables, so a full scan plus a filesort | once | none directly |
| 49 | ContractController.php:760 | `Contract::whereIn('id', $parentContractArr)` (+1 hidden) | once | `$contractsparentList` -> `viewDetailContract:2304` |
| 50 | ContractController.php:765 | `ContractParties::select('*')->get()` - **exact repeat of 701** | once | `partyDetailsView`, `partyDetailsEdit` |
| 51 | ContractController.php:787 | raw `DB::select` child-chain query. Two full scans of `contracts` per call | **once per parent in the chain** | none directly |
| 52 | ContractController.php:801 | `Contract::whereIn('id', $finalListChild)` (+1 hidden) | once | **nothing.** Line 944 runs the same query again and overwrites `$contractsSubseqList` before any blade sees it. Pure waste. |
| 53 | helpers.php:395, called from ContractController.php:919 and :925 | `CustomFieldsData` where group+field+group, latest, first. **Table `custom_field_data` has no index but the primary key, so a full scan** | 2x per required custom field of this contract type | feeds `$reqfieldsVals` |
| 54 | ContractController.php:944 | `Contract::whereIn('id', $finalListChild)` - **exact repeat of 801** (+1 hidden) | once | `viewDetailContract:2360` |
| 55 | ContractController.php:948 | `ContractObligations` where contract_id, flag=1 | once | `contractObligation`, `viewDetailContract` |
| 56 | ContractController.php:12133, called from :951 | `UserActionLog` where group_id. **No index on `group_id`, so a full scan** | once | **no blade at all.** `$signedHistory` reaches the view and nothing in the tree reads it. |
| 57 | ContractController.php:955 | `ApprovalContracts` where contract_id, flag<>-1, superseded=0 | once | `contractFlow`, `approvalFlow` |
| 58 | ContractController.php:980 | `ApprovalContracts` - **identical to 955**, because the one clause that differed is commented out | once | `contractFlow` |
| 59 | ContractController.php:1015 | `ApprovalContracts` where contract_id, all rows | once | `approvalFlow` (`$lockedGroups`) |
| 60 | ContractController.php:12404, called from :1028 | `AddUsers::find(created_by)` | once | none - sets `$userCanGate` |
| 61 | Helpers.php:254 | `Helpers::userInfo()` -> `AddUsers` with `AES_DECRYPT` in the `WHERE`, so a **full scan of 1,605 rows** | 4x: line 1001, inside 1028, `approvalFlow:2`, `preApprovalFlow:2` | `approvalFlow`, `preApprovalFlow` |
| 62 | ContractController.php:1029 | `ApprovalContracts` where contract_id, awaiting_owner_trigger=1, pluck | once | **no blade at all.** `$waitingGateGroupIds` reaches the view and nothing reads it. Only its `count()` sets `$canAdvanceNext`. |
| 63 | ContractController.php:12578, called from :1036 | `ContractPartiesRepresentative` join `contract_parties` join `contract_party_data`, distinct | once, only when `$userCanGate` | `approvalFlow:45` |
| 64 | ContractController.php:1038 | `AddUsers::select(...)` all 1,605 rows again, no `where` | once, only when `$userCanGate` | `contractFlow`, `preApprovalFlow` |
| 65 | ContractController.php:1053 | `ApprovalContracts` where contract_id, flow_type='preapproval', order by orderval | once | `preApprovalFlow` |
| 66 | viewDetailContract.blade.php:2431 | `$contractsoldother->contractParent` lazy `belongsTo` (+1 hidden `admin_setting`). The keys are reversed, so the SQL is `where parentcontract = <row id> limit 1`. **No index on `parentcontract`, so a full scan of 3,018 rows, for every row.** | **per related contract (58)** | `viewDetailContract` Link button |
| 67 | viewDetailCustomField.blade.php:50, 54, 61, 64, 70, 82 | `dataCustomFields()` -> `CustomFieldsData`, full scan | once per rendered field, and **once per option** inside a `select` or `currency` field. Four includes cover categories 1-4. | `viewDetailCustomField` |
| 68 | editCustomField.blade.php:54, 58, 65, 68, 74, 86 | same helper, same fields, second pass. Line 68 calls it **twice on one input**. | same, four more includes | `editCustomField` |
| 69 | editRenew.blade.php:5 and :26 | `admin_setting('enable_new_contracts')` -> `AdminSettings`, uncached | 2x | `editRenew` |
| 70 | approvalFlow.blade.php:68 | `AddUsers` where id first | once, only Signing/Approved | `approvalFlow` |
| 71 | approvalFlow.blade.php:84 | `AddUsers` where id first | once, only Signing/Approved | `approvalFlow` |
| 72 | approvalFlow.blade.php:307 | `AddUsers` where id first - **the same `created_by` row, read again every time** | **per approval row shown**, up to 22 | `approvalFlow` |

### Call-out 1 - `whereIn` lists that grow with the dataset

The bug fires on the **number of bound values**, not the number of rows the query returns. Measured
sizes at 3,018 contracts:

| Line | Bound list | Size at 3,018 contracts | Verdict |
|---|---|---|---|
| 729 | `$contract_party_locations`, the internal party rows of **this** contract | 1 | safe. It grows with parties per contract, not with the dataset. |
| 731 | `$contract_party_id`, the External party rows of **this** contract | 1 | safe, same reason. |
| 736 | `$FinalContractList`, contracts that share a branch **and** share the external party company | **58** | at risk. It is bounded by contracts per branch. The seed spreads 3,018 contracts over 99 branches, so about 59 each, and the busiest branch holds 72. In `goalapp_apollo` (2,886 contracts) one busy branch can hold 1,000 or more, and then the Related Contracts panel goes empty with no error. |
| 801 and 944 | `$finalListChild`, the whole descendant chain | **0** here, because no seeded contract has a parent | at risk on real data. `$childsList` concatenates every generation, so a wide tree passes 1,000. |
| 760 | `$parentContractArr` | 1, the value `0` | safe. It is bounded by chain depth. |
| hidden, after 736 | eager load `contractPartyList` -> `whereIn('custom_field_group_id', <58 ids>)` | 58 | at risk, and it tracks line 736 exactly. |
| hidden, after 718 | eager load `contractPartyList` for `$contractsoldothers` | 0 here. The largest category+department+type group in the seed holds 12. | at risk on real data. |
| Scopes/ContractRoledBasedScope.php:33 | `whereIn('created_by', $users)` for the Marketing Manager role | 0 today, because `enable_role_based_data` is off | at risk once the role is used and one manager has 1,000 or more reports. |

The sizes stay small **because the seed made only one `contract_parties` row**. Line 731 therefore
matches every contract, and the `intersect` at line 733 falls back to the branch list. A real party
table changes these numbers. Do not read "58" as safe forever.

### Call-out 2 - `select('*')->get()` with no `where`

| Line | Model | Table | Rows now |
|---|---|---|---|
| 701 | `ContractParties` | `contract_parties` | 1, and line 765 throws the result away |
| 765 | `ContractParties` | `contract_parties` | 1 |
| 703 | `ContractCategories` | `contract_categories` | 3 |
| 705 | `EntityBusiness` | `entitybusiness` | 214 |

Seven more have the same shape without the literal `select('*')`:

| Line | Model | Rows now | Note |
|---|---|---|---|
| 415 | `AddUsers` | 1,605 | 4 `AES_DECRYPT` columns on every row |
| 423 | `AddUsersSel` | 1,605 | same table, same rows, 5 `AES_DECRYPT` columns |
| 1038 | `AddUsers` | 1,605 | a third read of the same table |
| 667 | `BranchUser` | 99 | 11 `AES_DECRYPT` columns |
| 682 | `Branch` | 99 | same table, same 11 columns, no scope |
| 697 | `EntityMain` | 6 | |
| 407 and 665 | `ContractType` | 73 | runs twice, and the first result is unused |

### Call-out 3 - every `availableContracts()` call

Four calls. Each one costs **5 fixed queries** - one `BranchUser::pluck`, two from `BranchScope`,
one `EntityBusiness::pluck`, one `EntityMain::pluck` - plus **2 queries for every contract in the
list**, which are `ContractCategories::find` and the `contractTypeData` lazy load.

| Line | List it decorates | Rows for 100479 | Cost | Why it is there |
|---|---|---|---|---|
| 264 | the one contract being viewed | 1 | 7 | to build `$ContractsFinal[0]` -> `$contract`. Only the eSign block and `$preApprovalSteps` read it. The blades use `$contracts` from line 455 instead. This call is really an **access check**: if the user may not see the contract, the array is empty and line 391 redirects. |
| 738 | `$contractspartsList`, the related contracts | 58 | **121** | fills the Related Contracts table with decrypted names, dates and party names. |
| 762 | `$contractsparentList`, the parent chain | 0 | 5 | fills the Previous Contracts table. |
| 946 | `$contractsSubseqList`, the child chain | 0 | 5 | fills the Subsequent Contracts table. |

`availableContracts` accounts for **138 of the roughly 375 queries**, and 116 of those 138 are the
two per-row queries running 58 times.

### Call-out 4 - correctness problems, not speed problems

Listed, not fixed.

1. **A GET request writes to the database.** Lines 281 to 384 call an outbound eSign API, then run
   `Contract::where(id)->update()`, `EsignResposnse::update()`, a parent `Contract::update()` and
   `createContractSnapshot()`. Line 535 writes a `user_action_log` row on every page load of a
   Signing/Approved contract. A browser prefetch, a refresh or a crawler changes contract status.
2. **`$contractsoldothers` can be undefined.** Line 718 sits inside `if ($contractsold)`. The blade
   at `viewDetailContract:2261` loops it with no guard. If the contract row is gone, the page throws.
3. **Line 801 is dead.** Line 944 overwrites its result with the same query. Two queries and one
   eager load are wasted, and the reader has to prove the line does nothing.
4. **`$chartApprovals` (980) is identical to `$approvals` (955).** The one clause that made them
   differ is commented out. The Chart View no longer shows the pre-approval stages that the comment
   above it promises.
5. **`X == !null` is used as a null check** at lines 553, 573, 583, at `Controller.php:250` and
   `:293`, and in `viewDetailContract.blade.php:2322` and `:2422`. PHP reads it as `X == true`. It
   works by accident on positive ids and is wrong for id `0`.
6. **`Controller.php:295` can never be true.** `if (isset($contractParties->company_name))` names a
   variable that does not exist in that scope. So `$partysName` never gets an external party name,
   and every Related Contracts row shows internal parties only.
7. **`$approvalsArrExternal` (487) has no `flag` filter**, unlike every other approvals read. It goes
   to no blade, and the `$partySigned` array it fills is indexed by `$externalSigned`, a counter that
   lines 566, 576 and 586 all increment. So the party-to-signature mapping depends on party order.
8. **`admin_setting()` and `Helpers::getEntityBranches()` run uncached.** Every `Contract` query pays
   one `admin_settings` read. Every `BranchUser` query pays two reads, and one of them scans all
   1,605 `ContractUsers` rows because it decrypts `UserName` inside the `WHERE`. Both answers are the
   same for the whole request. This is the cheapest thing to cache.
9. **Six results reach the view and no blade in the render tree reads them:** `$categorys` (664),
   `$signedHistory` (951, a full scan of `user_action_log`), `$currentApproval` (built from 955),
   `$isCurrentApprover`, `$attachmentUrl`, and `$waitingGateGroupIds` (1029). Checked by grepping
   each name across all 18 blades the page really renders. Backup files (`*.blade.php-bcck`,
   `-au11`, `-bc`) and other views (`viewnew`, `viewExDetailContract`) do use some of them, which is
   why a plain repo-wide grep looks clean.
10. **Indexes the page needs and does not have:** `contracts.parentcontract` (row 66 scans 3,018
    rows 58 times), `contracts (catgoery_id, department_id, contract_type)` (row 42),
    `contracts_history.id` (row 12), `user_action_log.group_id` (row 56),
    `contract_party_data.contract_party_exe_id` (row 46), and
    `custom_field_data (custom_field_group_id, custom_field_id, custom_field_group)` (rows 53, 67,
    68). `custom_field_data` has no index at all beyond its primary key.
