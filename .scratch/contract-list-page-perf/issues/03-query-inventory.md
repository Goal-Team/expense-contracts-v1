# 03 — Inventory every query the page runs

Type: `wayfinder:task` (AFK)
Blocked by: 01
Assignee: claude subagent (session 2026-08-27)
Status: CLOSED 2026-08-27

## Question

Name every query site behind `listContract` and `listContractData` — including what the global
scopes and `Contract::$with` add, what `availableContracts()` runs per call at list scale, and
what the `myFilterStatus` path adds. For each: what it reads, how often it runs per request,
whether anything reads its result, duplicates, missing indexes, id-list `whereIn` sites.

**Trace, do not read.** The detail-page effort's rule: line numbers in tickets go stale after two
commits, and `DB::listen` with stack frames names each query's caller in one page load. Read the
detail-page [ticket 08](../../contract-detail-page-perf/issues/08-query-inventory.md) and
[ticket 12](../../contract-detail-page-perf/issues/12-delete-waste.md) for the method.

Read-only ticket. No code changes. Its output feeds tickets 04, 05 and whatever the fog
graduates into.

## Resolution

Traced 2026-08-27, not read. A temporary `DB::listen` in `PerfTimingServiceProvider` wrote every
query with its non-vendor stack frames to `storage/logs/qtrace.log` — the detail-page ticket 12
method. Three requests fired from the logged-in CDP browser with the filter cookies cleared first:

1. **GET `contracts/list`** — **11 queries** (was 14 before ticket 04).
2. **POST `contracts/data`**, default payload (`status=draft&contype=0&locations=0&party_id=0&userData=0`)
   — **13 queries**, 406 rows, 5,535,401 bytes.
3. **The same POST with `myFilterStatus` set** — **14 queries**, 0 rows (the 1,000-binding bug).

The trace and the listener were removed after the run. They are not on the branch.

Line numbers below are from the trace on this branch (commit `fa5d3ec` + tickets 04/06). They go
stale; the trace method does not.

### Shared middleware queries — first 4 of every request, GET and POST alike

| # | SQL (normalised) | Caller | Per request | Read by | Waste |
|---|---|---|---|---|---|
| M1 | `select id, AES_DECRYPT(username…, name, Salutation, issuper) from UserCredentials where …` | `Helpers::authTokenUser()` (Helpers.php:406) ← ContractSessionMiddleware:63 | 1 | session/user check | none. Request-cached since the detail effort. |
| M2 | `select id, AES_DECRYPT(AccessScope) from ContractUsers where AES_DECRYPT(UserName) = ? and Customer = ? and AccessScope LIKE …` | ContractSessionMiddleware:68 | 1 | access gate | decrypts `UserName` inside the `WHERE` — scans all 1,605 rows. Shared with every page. |
| M3 | `select type from file_storage where id = ? limit 1` | `fileStorageType()` (helpers.php:123) ← `storageAvailableCheck()` Controller.php:458 ← middleware:90 | 1 | storage gate | none. Request-cached. |
| M4 | `select * from contract_storage_config where storage_type = ? order by id desc limit 1` | Controller.php:459 | 1 | only `created_at` | `select *` for one date column. Tiny. |

### GET `contracts/list` — 7 more, 11 total

| # | SQL (normalised) | Caller | Per request | Read by | Waste |
|---|---|---|---|---|---|
| G5 | `select … AES_DECRYPT cols … from ContractUsers where …` | `BranchScope` → `Helpers::getEntityBranches()` (Helpers.php:383), fired by G6 | 1 | branch visibility | request-cached; one per request now. |
| G6 | `select id, AES_DECRYPT(BranchName, branchstatus, Doorno, StreetName, AreaName, Landmark, PinCode, ContactNumber, branchheadname, departments, LegalName) from branch where entityid = ?` | ContractController.php:2470–2485 | 1 | branch filter dropdown | **slowest GET query, 32.8 ms.** The blade (contractList.blade.php:591–592) reads only `id` and `LegalName`. 9 of 11 decrypted columns are never read. |
| G7 | `select * from contract_type where applicable = ?` | :2487 | 1 | type dropdown | none worth naming |
| G8 | `select * from contract_status_text` | :2489 | 1 | status tabs | none |
| G9 | `select * from contract_categories` | :2491 | 1 | category dropdown | none |
| G10 | `select admin_value from admin_settings where admin_key = ?` (`enable_ai_feature`) | contractList.blade.php:519 | 1 | blade `@if` | none. Request-cached after first call. |
| G11 | `select … AES_DECRYPT cols … from ContractUsers where AES_DECRYPT(UserName) = ?` | `Helpers::userInfo()` (Helpers.php:302) ← layout blades + contractList.blade.php:320 | 1 | header + "Show My Contracts" button email | decrypts in the `WHERE` — full scan of 1,605 rows, 12.3 ms. Request-cached, so once. Shared helper, not this page's to change. |

No `Contract` query runs on the GET any more, so `ContractRoledBasedScope` and `Contract::$with`
do not fire here at all. Ticket 04 removed the only one.

### POST `contracts/data`, default payload — 9 more after M1–M4, 13 total

| # | SQL (normalised) | Caller | Per request | Read by | Waste |
|---|---|---|---|---|---|
| P5 | `select admin_value from admin_settings where admin_key = ?` (`enable_role_based_data`) | `ContractRoledBasedScope`:19, fired by P6 | 1 | the scope | request-cached. |
| P6 | `select * from contracts where status = ? order by id desc` | ContractController.php:2167–2205 | 1 | `availableContracts()`, then the JSON | **the page's one big query: 289–636 ms, ~2,508 rows.** The code names 12 columns; the `accessLevelSelect` global scope's `select('*')` runs after and overwrites them (ticket 02's finding, confirmed in the trace — the SQL is `select *`). Every column crosses the wire, and because the models are `json_encode`d whole, every column also ships to the browser: **each JSON row carries 119 keys** (`rules_id` alone 1,545 bytes, `payment_terms` 314, a dozen more encrypted blobs at 200–300 bytes each) where the table reads perhaps 12. ~13.3 KB per row against well under 1 KB read. Both status branches build the identical query — the status filter runs in PHP afterwards. |
| P7 | `select * from contract_party_data where custom_field_group_id in (2,508 inline integer ids)` | `Contract::$with` (`contractPartyList`) eager load, behind P6 | 1 | `availableContracts()` party loop, `party_id`/`locations` filters | 25–32 ms. Laravel inlines integer keys (`whereIntegerInRaw`), 0 bindings — so this one **dodges** the 1,000-binding bug, but the SQL text grows with the dataset. The party rows are then serialised into the JSON **twice per contract** (`contractParty` and `contract_party_list`, 589 bytes each on the sample row). |
| P8 | ContractUsers via `BranchScope` → `getEntityBranches()` | fired by P9 | 1 | branch visibility | request-cached. |
| P9 | `select AES_DECRYPT(BranchName), id from branch where entityid = ?` | Controller.php:200 (`availableContracts()` static lists) | 1 | branch-access check + `location_branch` label | request-cached across the method's calls. |
| P10 | `select id from entitybusiness where applicable = ? and entityid = ?` | Controller.php:201 | 1 | department-access check | request-cached. |
| P11 | `select AES_DECRYPT(Nameoftheentity), id from entity where id = ?` | Controller.php:202 | 1 | internal party names | request-cached. |
| P12 | `select name, id from contract_categories` | Controller.php:203 | 1 | category label per row | request-cached. |
| P13 | `select * from contract_type where contract_type_id in (73 inline ids)` | Controller.php:213 `loadMissing('contractTypeData')` | 1 | `contract_type` label per row | inline ids, safe. |

### POST with `myFilterStatus` — the 14th query

| # | SQL (normalised) | Caller | Per request | Read by | Waste |
|---|---|---|---|---|---|
| P14 | `select * from approval_contracts where contract_id in (?, … 2,508 bound ?s …) order by id desc` | ContractController.php:2246–2249 | 1 | the "my pending approvals" filter | **the page's one id-list `whereIn` with bound values** — a `pluck`-shaped id list from `$ContractsFinal`. 2,508 bindings is over the 1,000-binding line, so it silently returns 0 rows and "My contracts" goes empty. Confirmed again in this trace (17.3 ms, 0 rows through to the response). Ticket 05. When it does return rows (under 1,000 contracts) it then decrypts 6 columns per row in PHP — and `approval_status` is plain now, so part of that pass is dead. |

### Answers to the ticket's specific questions

- **Duplicates:** none. Every query runs exactly once per request. The detail-page effort's
  request-lifetime caches (`admin_setting()`, `getEntityBranches()`, `userInfo()`,
  `fileStorageType()`, `authTokenUser()`, the four `availableContracts()` lists) absorb every
  repeat this page used to pay.
- **Id-list `whereIn` sites:** one — P14, the `ApprovalContracts` filter (:2246). It is live-broken
  on the seeded set. P7 and P13 are framework eager loads with inline integer ids: no binding
  limit, but P7's SQL text grows with the dataset.
- **`accessLevelSelect` `select *` overwrite:** affects exactly one traced query on this page —
  P6, the contracts fetch. (The GET has no Contract query left.) It is also the direct cause of
  the 119-key JSON rows, so it is a bytes problem as much as a wire problem.
- **Global scopes:** `ContractRoledBasedScope` costs P5 (one cached `admin_settings` read).
  `BranchScope` costs G5/P8 (one cached `ContractUsers` read). `Contract::$with` costs P7 —
  ~25–32 ms and roughly 1.2 MB of doubled party JSON.
- **Queries whose result nobody fully reads:** P6 (119 columns, ~12 read), G6 (11 decrypted
  columns, 2 read), P7 (`select *`, ~6 columns read — but all serialised), M4 (one column read).

### Feeds later tickets

- **The JSON carries every contract column, encrypted blobs included.** Fixing the
  `accessLevelSelect` overwrite on P6 (or listing the response fields explicitly) shrinks the
  5.5 MB default response and the 34.2 MB `status=all` response by most of their weight —
  `rules_id` alone is ~3.9 MB of the `status=all` body. This belongs to ticket 08's response
  rewrite; do not fix it twice.
- **Party rows ship twice per contract** (`contractParty` + `contract_party_list`). One of the
  two is pure duplication in the response body. Ticket 08.
- **G6 decrypts 9 branch columns nobody reads** on every GET — the map already noted the decrypt;
  the trace adds that only `id` + `LegalName` are read. Cheap trim for whichever ticket touches
  the GET next.
- **No missing-index finding at list scale**: P6 is a full fetch by design (no WHERE beyond
  `status`), so no index helps until ticket 08 pushes the filters into SQL. Once it does,
  `contracts.contract_status` / `substatus` become the columns to look at.
