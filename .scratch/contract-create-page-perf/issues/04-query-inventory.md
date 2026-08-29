# 04 — Query inventory

Type: `wayfinder:task` (AFK)
Blocked by: 02
Assignee: claude (session 2026-08-29)
Status: CLOSED 2026-08-29

## Question

Name every query the create page fires, with its caller, before any of them is rewritten.

Cover:

- `contractCreate()` and `contractCreateV3()`, line by line.
- Every query the blades fire — `contractCreate.blade.php`, `contractCreateV3.blade.php`, and the
  four `createCustomField` includes with category ids 1 to 4, plus `partyDetailsCreate` and
  `contractDuration`.
- The global scopes and model boot code that add queries behind each model.
- The AJAX calls the page fires from `contract.js`: `contracts/create/partylist`,
  `contracts/create/parties`, and anything else that runs on load.

For each: the SQL, the row count, the time, and whether any two are the same query. Say which
lists are printed into the HTML and how many bytes each one costs there.

Two duplicate pairs are known already and belong to ticket 06 — confirm them in the trace rather
than by reading: `Branch`/`BranchUser` (both table `branch`) and `AddUsers`/`AddUsersSel` (both
table `ContractUsers`).

## Resolution

Traced on `contracts/create-v3`, seeded set, after ticket 10. **95 queries, 28 distinct shapes,
257–304 ms of database time.** Sources: the perf log's duplicate groups and slowest list, plus the
controller read line by line.

### The controller, in order

`contractCreateV3()` fires these. `contractCreate()` fires the same list minus `AnnexureMaster`.

| # | query | rows | note |
|---|---|---|---|
| 1 | `CustomFields::where('status',1)->orderBy('order_id')->get()` | small | table `custom_field` |
| 2 | `Category::where('category_group','contract')->get()` | 31 | |
| 3 | `ContractType::get()` | 73 | |
| 4 | `getGeoGraphDropdowns()` | — | **67 queries**: one root + one per node at every level. Ticket 07 |
| 5 | `Branch::select(11 decrypts)->get()` | 99 | 11 `AES_DECRYPT` columns, 6.7 ms |
| 6 | `BranchUser::select(same 11)->get()` | 99 | **identical to #5** — same table `branch`, 4.6 ms. Ticket 06 |
| 7 | `EntityMain::select(3 decrypts)->get()` | 6 | |
| 8 | `AddUsers::select(5 decrypts)->get()` | 1,605 | 4.0 ms |
| 9 | `AddUsersSel::select(same 5)->get()` | 1,605 | **identical to #8** — same table `ContractUsers`, 3.9 ms. Ticket 06 |
| 10 | `LegalAdvisor::where('status',1)->orderBy('name')->get()` | 50 | |
| 11 | `ContractParties::select('*')->get()` | **5,001** | **99.5 ms — the slowest query on the page.** Every column of every party, encrypted blobs included. Ticket 08 |
| 12 | `ContractPartiesLabel::selectRaw(...)->leftJoin('regex')` | 5 | |
| 13 | `Branch::select('id', BranchName)->get()` | 99 | **a third pass over `branch`**, a subset of #5's columns. Ticket 06 |
| 14 | `Country::select('id','name')->get()` | 71 | |
| 15 | `ContractCategories::select('*')->get()` | 3 | |
| 16 | `EntityBusiness::select('*')->get()` | 214 | |
| 17 | `admin_setting('enable_new_contracts')` | 1 | request-cached already; the log shows 2 executions of `admin_settings` across the whole request |
| 18 | `AddUsers::select(...)->where(UserName)->first()` | 1 | the owner/initiator check |
| 19 | `AnnexureMaster::where('status',1)->orderBy(...)->get()` | small | **V3 only** |

The rest of the 95 are session and middleware work — the `UserCredential` decrypt, the access
level lookups. They are not this page's code.

### Duplicate groups the log reports

| query | executions | time |
|---|---|---|
| `select * from GeographicalHierarchy where entityid = ? and parent = ?` | **66** | 54 ms |
| the `ContractUsers` five-column decrypt (#8 and #9) | 2 | 8 ms |
| `select admin_value from admin_settings` | 2 | 1 ms |

The two `branch` decrypts (#5 and #6) do not group because #13 makes the third pass a different
shape; both appear in the slowest list at 6.7 ms and 4.6 ms.

### Where the remaining database time goes

Of ~260–300 ms: **`contract_parties` alone is 99.5 ms**, the geo hierarchy is 54 ms, and the four
duplicate-pair queries are about 19 ms. Everything else is under 4 ms each.

### The bytes, which are now the page's biggest number

The document is **8,899,081 bytes decoded** on `create-v3` and 8,011,289 on `create`. It gzips to
343,925 and 226,203, so the wire cost is modest and the parse cost is not. Almost all of it is the
party section: `partyDetailsCreateV3.blade.php` renders the 5,001-row list as a `<select>` **and**
as a hidden `<li>` address block, more than once. The measured proof is
`contractCreateAi.blade.php`, which loads the same 5,001 parties into 11,217 `<option>` elements
and produces a **2.3 MB** page. Ticket 08 owns this.

### Blade and AJAX

The four `createCustomField` includes (category ids 1–4) fire **no queries** — they filter the
already-loaded `$customFields` collection. `partyDetailsCreateV3`, `partyDetailsCreate` and
`contractDuration` fire no queries of their own either, now that `get_state()` is cached.

One AJAX call runs on load: `POST /contracts/getSignatory` — **6 queries, 13 ms, 1,395 bytes**.
It is small and it is not worth changing.

### Written down, not fixed

`ContractCategories::select('*')` and `EntityBusiness::select('*')` take every column of small
tables (3 and 214 rows). Real waste, but a few kilobytes and under 4 ms each. Ticket 08 decides
whether they are worth trimming with the rest.
