# The one IIS change a folder web.config cannot make

**Nothing here is applied.** From [ticket 22](../issues/22-reduce-page-size.md). The dev asked for the
files to be written even where the change is outside `contracts/`, because `contracts/` is served under
the parent **GOAL** application and this map has no access to that level.

## The problem, measured

Static gzip **is** configured on this server, and it **never helps a first visit**.

```
request 1   1,252,656 bytes   no Content-Encoding
request 2     343,147 bytes   Content-Encoding: gzip
request 3     343,147 bytes   Content-Encoding: gzip
request 4     343,147 bytes   Content-Encoding: gzip
```

3.65x, from request 2 onward. IIS only compresses a file once it counts as **frequently hit**, and a
cold-cache visit asks for each asset exactly once — so on a first visit not one file qualifies. That is
1.9 MB of CSS+JS going out raw. It is the single biggest page-weight fact in this whole effort, and it is
one attribute.

Confirmed twice, independently: [ticket 21](../issues/21-page-weight-measurement.md) and ticket 22's own
research.

## Correction, 2026-08-21 — the first version of this file was wrong and took the site down

**This file originally said `frequentHitThreshold` goes on `<httpCompression>`. It does not.** The dev
applied it as written and every URL under the app returned **HTTP 500.19 — "Unrecognized attribute
'frequentHitThreshold'"**, module `StaticCompressionModule`. IIS rejects the whole config file, so the
failure is total, not partial.

Checked against IIS's own schema, `%windir%\System32\inetsrv\config\schema\IIS_schema.xml`, which is
the authority:

```
1409:  <sectionSchema name="system.webServer/serverRuntime">
1416:    <attribute name="frequentHitThreshold" type="uint" defaultValue="2" ... />
1417:    <attribute name="frequentHitTimePeriod" type="timeSpan" defaultValue="00:00:10" />
```

Both attributes belong to **`system.webServer/serverRuntime`**. Neither exists on `httpCompression`.

A second error in the same block: `frequentHitTimePeriod` is a **timeSpan**, so `"10000"` is invalid
whichever element it sits on. It would have to be `"00:00:10"` — which is already the default, so leave it
out entirely.

## The change

`frequentHitThreshold` defaults to **2 hits within 10 seconds**. Set it to **1** and the first request for
a file compresses it.

**Rollback first if the broken version was applied.** In
`%windir%\System32\inetsrv\config\applicationHost.config`, delete the two bad attributes so the element
reads as it did before. This alone brings the site back:

```xml
<httpCompression directory="%SystemDrive%\inetpub\temp\IIS Temporary Compressed Files">
```

Then set it on the right section.

**Two things this file got wrong the first time, both found by the dev trying to run it 2026-08-21.**

**1. The shell matters.** The line below was written in **cmd.exe** syntax. The dev runs **PowerShell**,
where `%windir%` is not a variable — PowerShell tried to resolve `%windir%` as a module and failed with
`The module '%windir%' could not be loaded`. Nothing to do with IIS.

**2. It needs an elevated shell.** Even the read-only `list config` fails without it:
`Cannot read configuration file due to insufficient permissions` (`redirection.config`, exit code 5).
So "run this and see" is not possible from an ordinary prompt — it must be **Run as administrator**.

**3. The `&` call operator is a trap in a terminal that duplicates input.** The `& "$env:windir\..."`
form is valid PowerShell, but the dev's terminal pasted the line **twice on one line**, so the second
copy's leading `&` landed mid-command and PowerShell refused it:
`The ampersand (&) character is not allowed`. The same terminal had already produced `ppowershell` and
`%%windir%` from doubled input, so this is a paste artifact, not a syntax problem.

**Use the bare literal path. No `&`, no quotes, no `%windir%`.** Checked on this machine:
`$env:windir` is `C:\WINDOWS`, so the full path is `C:\WINDOWS\system32\inetsrv\appcmd.exe` — **it
contains no spaces**, which is exactly why PowerShell will run it directly with no call operator and no
quoting. This form works in **both** PowerShell and cmd, and if the terminal duplicates it the error is
obvious instead of a confusing parser message.

**Run these in an ELEVATED shell** (Run as administrator). One line at a time.

Read it first:

```
C:\WINDOWS\system32\inetsrv\appcmd.exe list config -section:system.webServer/serverRuntime
```

Then set it — note the value is unquoted, which removes the last piece of quoting that could go wrong:

```
C:\WINDOWS\system32\inetsrv\appcmd.exe set config -section:system.webServer/serverRuntime /frequentHitThreshold:1 /commit:apphost
```

Then read it back. Expected in the output: `frequentHitThreshold="1"`.

**If it is not elevated** the failure is unmistakable and harmless — exit code 5,
`Cannot read configuration file due to insufficient permissions`, naming `redirection.config`. Nothing is
changed.

### Why the agent does not run this itself

Two reasons, both worth writing down. It needs **elevation**, which the agent's shell does not have. And
`applicationHost.config` is **machine-wide** — it governs every site on this box, including the parent
GOAL application and phpMyAdmin, not just `contracts/`. A wrong value here already took the whole server
down once (see the correction above). So the agent verifies the command form and the attribute name
against the schema, and the dev applies it.

### Rule for every command handed to the dev from now on

**Say which shell, check the command runs there, and prefer the form with the fewest moving parts.**
This effort lost time three times to the same shape of error, all of them one check away from being
caught:

1. an attribute name that was not in `IIS_schema.xml` — took the whole site down with HTTP 500.19;
2. `%windir%`, which is cmd syntax, handed to a dev working in PowerShell;
3. the `&` call operator, which turns a duplicated paste into a parser error.

The lesson that covers all three: **hand over the plainest thing that works.** A bare literal path with no
variables, no call operator and no quotes runs in both shells and fails legibly when something else goes
wrong. Check the attribute against the schema, and say out loud that it needs elevation.

### Why this is not settable from `contracts/web.config`

`system.webServer/serverRuntime` has `allowDefinition="AppHostOnly"`. A folder-level `web.config` that
names it returns **HTTP 500.19**. There is no folder-level equivalent and no workaround inside the app.

### Lesson for the next config proposal in this effort

**Every attribute in a proposed IIS change gets checked against `IIS_schema.xml` before the dev is asked
to apply it.** One `grep` would have caught this. A wrong attribute name is not a partial failure — IIS
refuses the file and the whole application 500s.

### Dynamic compression: ruled out by the dev, and not installed anyway

**The dev's call, 2026-08-21: no dynamic compression.** Their words — it is a pain to maintain, and they
will bear the cost of the uncompressed HTML document. **Nothing in this effort should propose it again.**

That decision costs about **53 KB per request** on the document, which is never cacheable and always on
the critical path. Recorded so the number is known, not to reopen the argument.

It would also have needed an install, not a setting. Read out of the live config:

```
DynamicCompressionModule -> NOT PRESENT
StaticCompressionModule  -> <add name="StaticCompressionModule" image="%windir%\System32\inetsrv\compstat.dll" />
<dynamicTypes> present   -> False
```

So `urlCompression doDynamicCompression="true"` would have been **inert** — a valid attribute with no
module behind it. The schema also shows it already **defaults to true**, so that block was never the
thing that would have switched it on. It has been removed from
[web.config.proposed](web.config.proposed).

### Static compression only — which is where nearly all the win was anyway

| fix | what it needs | worth |
|---|---|---|
| `serverRuntime/frequentHitThreshold="1"` | one attribute, module already installed | **~1.39 MB** — CSS 966 KB + JS 940 KB at the measured 3.65x |
| `staticContent/clientCache` in `web.config` | folder-level, safe, schema-checked | 0 bytes cold; removes 54 conditional round-trips warm |
| ~~install `IIS-HttpCompressionDynamic`~~ | ~~role feature~~ | **ruled out by the dev** |

**The one attribute is worth ~1.39 MB of the 2.9 MB on its own.** Static compression is a different
module from dynamic, it is already installed, and its `staticTypes` list already covers `text/*` and
`application/javascript`. Nothing needs installing.

The fonts (820 KB) gain nothing either way — `staticTypes` has `*/*` disabled and woff2 is already
compressed internally, so gzipping it would be wasted CPU. Correct as configured, not an oversight.

### While you are in there — check dynamic compression is even installed

`urlCompression doDynamicCompression="true"` in the proposed `web.config` does nothing unless the
**Dynamic Content Compression** role feature is installed. Check:

```
%windir%\system32\inetsrv\appcmd.exe list config -section:system.webServer/httpCompression
```

If the `<scheme name="gzip">` entry has no `dynamicCompressionLevel`, or `dynamicTypes` does not list
`text/html`, dynamic compression is not set up. `text/html` and `application/json` both want to be in
`dynamicTypes` — the document and the `option-lists` response.

## What it is worth

| what | now | after |
|---|---|---|
| CSS + JS on a first visit | ~1.9 MB raw | ~500 KB gzipped |
| the HTML document | 63,274 bytes, never compressed | ~10 KB |
| `option-lists` JSON | uncompressed | ~2 KB |
| returning visitor | 304 round-trip on 54 files | nothing (from the `web.config` half) |

Roughly **1.4 MB off a 2.9 MB page**, for config only. No application code changes, no rebuild, nothing
this map has to write.

## Risk

Low, and reversible by putting the old value back. `frequentHitThreshold="1"` costs a little CPU the first
time each file is asked for, then the compressed copy is cached on disk and served from there. With 54
static assets that is a one-off cost, not a per-request one. It is server-wide, so it also affects the
GOAL app and every other site on the instance — in the same direction.
