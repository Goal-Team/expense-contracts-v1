# Deployment steps — dashboard performance work

Written 2026-08-21. Covers the changes from
[.scratch/contracts-dashboard-perf/spec.md](.scratch/contracts-dashboard-perf/spec.md), Changes A to G.

**Migrations are not listed here.** They run automatically. One of them has an order requirement, and
that requirement is step 2 below — read it before you deploy.

---

## 1. The order matters, and getting it wrong fails loudly

There is one hard rule. **The conversion command must run before the migrations.**

`database/migrations/2026_08_21_000001_narrow_approval_contracts_approval_status.php` narrows
`approval_contracts.approval_status` from `varchar(1000)` to `varchar(20)`. The column holds
200-character ciphertext until the conversion has run. Narrowing first would cut every value.

The migration checks for this and **throws** rather than damaging data:

```
approval_status still holds 13867 encrypted value(s). Narrowing the column now would cut every
one of them. Run "php artisan contract:convert-approval-status --apply" first.
```

So `php artisan migrate` **fails** if you deploy and migrate before converting. Nothing is damaged. You
run the conversion and migrate again.

**The safe order:**

1. Deploy the code.
2. Run the conversion (step 2 below).
3. Run the migrations.

**If your deploy runs migrations automatically and you cannot get between the two steps**, expect the
first deploy to report a failed migration. Then run the conversion and re-run the migration. During that
gap the dashboard shows near-zero counts in "My Actionable Items", because the counter reads plain text
and the rows are still ciphertext.

---

## 2. Convert the encrypted approval status to plain text

Check first. This changes nothing and tells you what it would do:

```bash
php artisan contract:convert-approval-status
```

Read the output, then apply it:

```bash
php artisan contract:convert-approval-status --apply
```

**Take a database backup first.** The command says so as well.

What it does: rewrites every `approval_contracts.approval_status` value from ciphertext to plain text —
`pending`, `approved`, and so on. Only that one column, only in the contracts database.

Why: the dashboard counter must filter on this column in SQL. The encryption is different every time
(AES-128-CBC with a random IV), so no query and no index can ever match a ciphertext value. This is
[ticket 17](.scratch/contracts-dashboard-perf/issues/17-plain-columns-experiment.md). It made the counter
about 12x cheaper.

Rows written **after** the deploy are already plain, because `config/app.php` lists
`approval_contracts.approval_status` in `PLAINTEXT_COLUMNS` and `encryptStringx()` reads that list. So
this command is a one-time fill of existing rows, not a job that repeats.

**It is reversible.** Run the migration's `down()` first so the column is wide enough to hold ciphertext
again, then:

```bash
php artisan contract:convert-approval-status --apply --down
```

---

## 3. Convert `contract_party_data` — needs a maintenance window

```bash
mysql -u<user> -p <database> < database/manual/001-contract-party-data-innodb-utf8mb4.sql
```

**Read the top of that file before you run it.** It is a **full table rebuild**: MyISAM to InnoDB, latin1
to utf8mb4, and the two join columns from `TEXT` to `varchar(32)`. MyISAM locks the whole table while it
rebuilds, so **nobody can use the app during this step**. On the dev database it is 6,940 rows and it is
quick. Size it against your own row count.

**Check the current state first.** If your table is already InnoDB and utf8mb4, skip this step.

**This is deliberately not a migration.** A migration cannot name the collation, because the right
collation depends on the server it runs against. Of the 8 client databases checked, one is
`utf8mb4_unicode_ci` and seven are `utf8mb4_general_ci`. A hardcoded value would have *created* the
mixed-collation problem it was meant to prevent
([ticket 20](.scratch/contracts-dashboard-perf/issues/20-migration-portability.md)). So a person runs it,
after checking the target database's own default.

Take a backup. The dev database's own pre-change dump is at
`.scratch/contracts-dashboard-perf/measurements/contract_party_data-before-convert.sql`, as an example of
what to keep.

---

## 4. `.env` on the production server

| key | value | why |
|---|---|---|
| `APP_DEBUG` | `false` | Hides error detail from users. It also switches the request timing instrumentation off, which is gated on `APP_DEBUG=true` **and** `APP_ENV=local`. |
| `APP_ENV` | `production` | The second half of that gate. |
| `LOG_LEVEL` | `warning` | Silences every `Log::debug()` line, including the menu cache-miss line. Leave the code alone. The setting does the work. |
| `DEBUGBAR_ENABLED` | `false` | The debug bar grows the dashboard document from 63 KB to 359 KB, and it would print query bindings on a public page. |
| `DEBUGBAR_OPEN_STORAGE` | `false` | Never expose stored requests over HTTP. |
| `DEBUGBAR_STORAGE_ENABLED` | `false` | Never write requests to disk. |
| `CACHE_DRIVER` | `file` is fine | Change G caches the menu here. Any driver works. With more than one web server, each server keeps its own copy — harmless, only a few more cache misses. |

The same values are written at the top of [.env.example](.env.example). Copy them from there.

---

## 5. `php.ini` — opcache

Check that these three lines exist and are not commented out:

```ini
zend_extension=opcache
opcache.enable=1
opcache.memory_consumption=256
```

Without opcache, PHP recompiles all 788 files of this application on **every request**. On the dev
machine that was the largest fixed cost in the response — more than a second, before any application code
ran. It is a one-line change and it does not affect behaviour.

Restart the web server after you change `php.ini`.

**Optional, production only:** `opcache.validate_timestamps=0` stops PHP checking file timestamps and is
faster again. The cost is that **you must restart PHP on every deploy**, or the old code keeps serving.
Only set it if your deploy already restarts the service.

---

## 6. IIS — one attribute, and it needs an administrator

**Already applied on the dev machine, and verified 2026-08-21.** Still to do on **production**.

How it was verified, and how to verify it anywhere — request a file the server has not served
recently and read the headers on the **first** request:

```bash
curl -s -o /dev/null -H "Accept-Encoding: gzip" -D - "http://<host>/contracts/build/assets/mapbox-gl-COawACmw.js"
```

`Content-Encoding: gzip` with `Content-Length: 343147` on attempt 1 means it is on. Before the change
that same file returned **1,252,656 bytes and no encoding** on the first request and only compressed from
the second. A second file picked at random also came back gzipped on attempt 1.

The biggest single win in the whole effort, and it is not application code.

**What is wrong on a server that has not had this change.** Static gzip is configured, but IIS only
compresses a file once it counts as frequently hit — 2 hits inside 10 seconds, by default. A visitor with
a cold cache asks for each file **exactly once**, so no file qualifies and the whole page goes out
uncompressed. Measured on one file: 1,252,656 bytes on the first request, 343,147 bytes from the second.
**3.65x.**

**The fix.** Set the threshold to 1 hit.

Run this in an **elevated** shell — Run as administrator. Without elevation it fails harmlessly with
`Cannot read configuration file due to insufficient permissions` and changes nothing.

Read it first:

```bash
C:\WINDOWS\system32\inetsrv\appcmd.exe list config -section:system.webServer/serverRuntime
```

Then set it:

```bash
C:\WINDOWS\system32\inetsrv\appcmd.exe set config -section:system.webServer/serverRuntime /frequentHitThreshold:1 /commit:apphost
```

Read it back. You want `frequentHitThreshold="1"` in the output.

**Three warnings, all learned the hard way on the dev machine:**

- The attribute belongs to `system.webServer/serverRuntime`. It does **not** exist on `httpCompression`.
  Putting it there returns **HTTP 500.19** and takes every URL under the application down. That happened.
- `applicationHost.config` is **machine-wide**. It governs every site on that server, not only this app.
- Do not add `frequentHitTimePeriod`. It is a timeSpan, its default is already `00:00:10`, and a plain
  number is invalid.

Worth about **1.39 MB** off a 2.9 MB first visit — the CSS and the JS. Fonts gain nothing, because woff2
is already compressed inside.

**Dynamic compression is deliberately not used.** It is a separate module, it is not installed here, and
the dev ruled it out. The cost is about 53 KB per request on the HTML document. Known and accepted.

---

## 7. Add the asset cache rule to the production `web.config` by hand

**`web.config` is git-ignored** (`.gitignore` line 34), so the version on the production server is its
own file and a deploy never touches it. You have to add this block there yourself.

Paste it as the **last child of `<configuration>`**, after the closing `</system.webServer>`:

```xml
<location path="build/assets">
    <system.webServer>
        <staticContent>
            <clientCache cacheControlMode="UseMaxAge"
                         cacheControlMaxAge="365.00:00:00"
                         cacheControlCustom="immutable" />
        </staticContent>
    </system.webServer>
</location>
```

What it does: sets `Cache-Control: immutable, max-age=1 year` on the built CSS, JS, fonts and images. A
returning visitor stops paying 54 conditional round-trips. It saves **nothing** on a first visit.

Why it is safe: every one of the 1,046 files in that folder carries a content hash in its name, so the
name changes whenever the bytes change and a stale cache cannot happen.

**Why it is scoped to that one folder.** `assets/` and `images/` hold files whose names never change. An
unscoped rule at the top level of the file would pin a changed logo or stylesheet in every existing
browser for a year. Use the `<location>` form, not a bare `<staticContent>` block.

Check it after you add it. The response headers for any file under `build/assets/` should carry
`Cache-Control: immutable,max-age=31536000`, and a file like `favicon.ico` should carry **no**
`Cache-Control` at all.

**If it causes HTTP 500.19**, the parent GOAL application has locked
`system.webServer/staticContent`. Delete the `<location>` block and the file is back to what it was.
Nothing depends on it.

The same block is already applied to the dev machine's `web.config` and can be copied from there.

---

## 7a. The menu cache needs nothing from you

Any write to `menu_configs` fires a model hook that retires every cached menu, whatever did the writing —
the admin screen, a seeder, or tinker. You never clear it by hand after an admin edits a menu.

---

## 7b. The built assets — one folder now, and `npm run build` is enough

**Fixed 2026-08-21.** This used to be the most dangerous thing in this list. It is not any more.

### What was wrong

There were **two separate 33 MB copies** of the built assets, and nothing kept them in step:

| folder | what used it |
|---|---|
| `public/build/` | Laravel read `manifest.json` from here — this is what turns `@vite(...)` into a URL |
| `build/` | IIS served the actual files from here — this is what the browser downloaded |

`vite.config.mjs` writes to `public/build`. So a build updated the index Laravel reads and left the files
the browser downloads alone. Measured: a real build changes the filenames of **four** files —
`jkanban`, `template-customizer`, `datatables-bootstrap5`, `charts-apex`. `datatables-bootstrap5` is
needed by **56** blades including `contractList.blade.php`. So rebuilding used to mean **every list page
loses its datatable**, while the dashboard looked perfectly fine.

They had already drifted before any of this work: different entry counts, crossed `app.css`/`app.js`
entries, and one hand-typed path with a spelling error (`resource/sassets`).

### The fix

One rewrite rule in `web.config`, pointing the URL at the folder Laravel already reads:

```xml
<rule name="build assets served from public/build" stopProcessing="true">
    <match url="^build/(.*)$" ignoreCase="true" />
    <action type="Rewrite" url="public/build/{R:1}" />
</rule>
```

**It must sit above the "static file 404 without booting PHP" rule.** That rule has `stopProcessing` and
fires on any missing `.css`/`.js` path, so it would answer 404 before the rewrite could run.

**And the cache rule's path had to change with it.** `<location>` matches the path **after** the rewrite,
not the URL the browser asked for. It is now `<location path="public/build/assets">`. With the old
`build/assets` the `Cache-Control` header disappeared completely — that was measured, not guessed.

Both rules are in the tracked `web.config`, so they travel with the repo. Remember section 7: the
production server has its own `web.config` and a deploy does not create these for you.

### So now

`npm run build` on its own is correct and sufficient. It writes `public/build`, Laravel reads
`public/build`, IIS serves `public/build`. One folder.

After a rebuild, still **load a list page**, not just the dashboard — the dashboard uses almost none of the
files whose names change.

### The root `build/` folder is gone

Deleted 2026-08-21, 78 MB freed, once the rewrite above had been confirmed working. It held `assets`
(33 MB), `assets-bc` (33 MB), `assets.zip` (13 MB), `index.html` and `modules`, and nothing in the code
referenced any of the last four.

`assets-bc` was checked before it went, not after: it held **exactly the same 1,046 filenames** as
`assets`, none unique, and the files spot-checked by md5 were byte-identical across all three copies. Since
the filenames are content hashes, matching names mean matching bytes. Nothing existed only there.

**On a production server, do the same check before removing it.** Confirm one build asset returns
`Cache-Control: immutable` and, on a second request, `Content-Encoding: gzip` — that proves requests are
being served through the rewrite and not out of the old folder. Then move the folder aside, load a
dashboard **and** a list page, and only then delete. That is the order used here.

### Still true, and still not written down anywhere else

`/build` **and** `/public/build` are both git-ignored, and no file under either is tracked. A checkout
gives a server **no CSS or JS at all**. Confirm how the built assets reach production before you deploy.
The one thing this effort changed is that `npm run build` works at all now, because `vite.config.mjs` is
committed — it was impossible before
([ticket 07](.scratch/contracts-dashboard-perf/issues/07-asset-pipeline-decision.md)).

**The module JavaScript does not go through Vite.** The blades load it with raw URLs —
`<script src="{{url('/')}}/Modules/Contract/resources/assets/js/contract.js">` — so the manifest's module
entries are not used, and a `Modules/` against `modules/` case difference between builds is harmless.

---

## 8. Do not do these

- **Do not run `php artisan config:cache`.** [config/app.php:7](config/app.php:7) derives
  `APP_ENCRYPTION_KEY` from `$_SERVER['HTTP_HOST']`. From a command line there is no HTTP host, so the
  value becomes `localhost` and a **wrong key is baked into the cached config**. Stored contract data then
  stops decrypting. This is a real trap, not a theoretical one.
- **Do not create `storage/perf-boot-timing.enabled`** on a production server. That file switches on the
  bootstrap timing instrumentation. It is git-ignored, so it will not arrive by itself.
- **Do not delete the `Log::debug()` lines** to quieten the logs. Set `LOG_LEVEL=warning` and they go
  silent on their own.

---

## 9. After the deploy

```bash
php artisan cache:clear
```

```bash
php artisan view:clear
```

`cache:clear` matters because the menu cache can hold entries written by the old code. `view:clear` drops
compiled Blade templates so the changed views recompile.

**Do not** run `config:cache`. See step 8.

---

## 10. How to check it worked

| check | what you should see |
|---|---|
| Open the dashboard at `/contracts/` | It loads. The left menu shows, and **Dashboard is highlighted**. |
| "My Actionable Items" | A real number, not `Total (0)`. If it is zero for everybody, step 2 has not run. |
| "All Contracts", "Expired", "Pending Activation" | The same numbers as before the deploy. |
| Both filter dropdowns | They fill in shortly after the page appears. They are fetched separately now, on purpose. |
| A built asset, on a **second** request | `Content-Encoding: gzip` in the response headers. Missing means step 6 has not been applied. |
| Any file under `build/assets/` | `Cache-Control: immutable,max-age=31536000`. |

---

## 11. Rolling back

| change | how to undo it |
|---|---|
| Code | Deploy the previous release. The old dashboard method is deleted, so rolling back the code rolls back the whole change. |
| `approval_status` conversion | `php artisan migrate:rollback` for the narrow migration, then `php artisan contract:convert-approval-status --apply --down`. In that order — the column must be wide before the values grow back. |
| `contract_party_data` conversion | Restore the backup you took in step 3. There is no reverse script. |
| `frequentHitThreshold` | Set it back to `2`, or delete the attribute. Same elevated command. |
| `build/assets/web.config` | Delete the file. A browser holding a one-year entry keeps it until the filename changes — and the name changes on every rebuild, so this heals itself. |
