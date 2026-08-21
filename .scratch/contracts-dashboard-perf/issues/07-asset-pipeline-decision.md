# Decide the asset pipeline

Type: grilling
Status: resolved
Blocked by: —

## Question

The asset setup is in an undefined state and nobody knew how it was meant to work — the dev's own words
on `vite-module-loader.js` and the manifest were "I don't know how it is used, figure it out".
[Ticket 03](03-vite-setup-research.md) has now established how it works. This ticket decides what to do
about it.

**What 03 settled.** `public/hot` (dated Sep 2024) is the file Laravel reads; the root `hot` is inert.
Detection is a bare `is_file()` with no dev-server probe and **no manifest fallback**, so with nothing
listening on 5173 every asset URL is dead and unrecoverable. Deleting `public/hot` is safe — all 441
blades' `@vite` entrypoints resolve in `public/build/manifest.json` with zero misses. Separately,
`core.scss` genuinely takes **5.6–7.9s** to compile: `sass@1.71.0` is pure-JS, `sass-embedded` is
absent, and Vite 5.1.3 uses the *legacy* sass API. `api: 'modern-compiler'` requires Vite 5.4+.

So problem #2 as originally reported is **two** problems, and they have different fixes.

Decide, with the dev:

1. **Is Vite dev meant to be used here at all?** Deleting `public/hot` restores the precompiled CSS and
   makes the page fast immediately — but it also means no HMR and no way to change styles without a
   full build. If nobody is editing scss, that is a clean win. If they are, it trades one pain for
   another.
2. **If Vite dev is wanted, is the ~6s compile acceptable, or does the toolchain get upgraded?** The
   options are: live with it; add `sass-embedded`; or upgrade Vite to 5.4+ and set
   `css.preprocessorOptions.sass.api = 'modern-compiler'`. Upgrading Vite on a Vuexy template with 1049
   manifest entries is not free — scope that cost before choosing it.
3. **Does the missing root config get reconstructed?** A build config that exists only on individual
   machines, gitignored, means nobody else can build this app's CSS — and 03 found the module `paths`
   exports are all commented out, so module assets are not being collected either way. This is a
   standing fragility that belongs in the spec regardless of which way 1 and 2 go.
4. **Which duplicated copy is authoritative.** 03 found the manifest is read from `public/build/` while
   the bytes are served from the root `build/` — two independently-aged copies, one supplying filenames
   and the other supplying content. That is a live footgun. Also decide between the two scss trees
   (`resources/assets/vendor/scss/` vs `Modules/Contract/resources/vendor/scss/`) and delete the loser.
5. **Is `[::1]` ever a workable dev-server host** for a browser hitting `apollo.contracts.legality:8888`?
   If a config is reconstructed it needs an explicit `server.host`.

**Now measured** — [ticket 01](01-attach-chrome-devtools.md) confirms it against the live page:
`hasDeadViteHost: true`, **26 requests to `http://[::1]:5173/...` all `ERR_CONNECTION_REFUSED`**, each
burning 2,058–6,116 ms and serialising. **Only 3 of 31 requests succeed** on the dashboard (3 of 37 on
the contract list) — the page runs essentially unstyled. Roughly **18 s of the ~21 s wall clock** is
this. Deleting `public/hot` is the highest-value single change in the entire map, and it is one file
deletion.

Two further findings for this ticket to absorb:

6. **The RTL variant is being served.** The refused entrypoints include
   `resources/assets/vendor/scss/rtl/core.scss` and `rtl/theme-default.scss`, so
   `$configData['rtlSupport']` resolves to `rtl` ([Helpers.php:115-123](../../../app/Helpers/Helpers.php:115),
   cookie-overridable). Is that intended? If not, the app has been serving right-to-left stylesheets to
   everyone, and it also means the *non*-RTL build artifacts are the ones going unused.
7. **`contracts/assets/fonts/font-main.css` fails with `ERR_ABORTED`** (~0.9–1.3 s) and is **not** a
   Vite URL — a separately broken local asset that survives whatever happens to the hot file.

## Answer

Decided with the dev 2026-08-14.

### 1. Vite dev is not reinstated. Delete `public/hot`.

Serve the precompiled manifest. Recovers ~18 s of the ~21 s wall clock for one file deletion.

**But the dev's follow-up question — "locally if I have to run, I should always run `vite build`?" — has an
uncomfortable answer: no, you cannot run `vite build` today, and that changes what this decision costs.**

With no root `vite.config`, `npm run build` invokes Vite on its defaults: `laravel-vite-plugin` is
installed but never loaded, so **no `public/build/manifest.json` is emitted**; the default `outDir` is
`dist/`, not `public/build/`; and Vite's default entry is `index.html`, which does not exist in this repo
(only `index.php`). The build would fail outright, and even if it ran it would not produce what `@vite`
reads.

The existing `public/build/` output was produced on a machine that had the config. So deleting
`public/hot` leaves the app serving **frozen** precompiled CSS with no supported way to regenerate it.

**Consequence, and a correction to the recommendation originally put to the dev:** reconstructing a
working root `vite.config` is **not** deferrable "later work" — it is a required deliverable of the spec.
Without it, nobody can change a stylesheet in this application at all. The sequencing is: delete
`public/hot` now for the immediate 18 s (safe — all 441 blades' entrypoints verified present in the
manifest), and treat "a committed, working root `vite.config` that builds `core.scss` into
`public/build/manifest.json`" as a required spec item, not an optional one.

The ~6 s `core.scss` compile is therefore moot for now — nothing is compiling. It becomes live again when
the config is reconstructed, and at that point `sass-embedded` or Vite ≥5.4 with
`css.preprocessorOptions.sass.api = 'modern-compiler'` is the mitigation. Note for whoever does it: the
config must be **committed**, and `.gitignore`'s `*.mjs` rule plus the stale
`vite.config.mjs.timestamp-*` entries are what let it go missing in the first place.

### 2. RTL is not needed by any user. Turn it off.

Set `'myRTLSupport' => false` at [config/custom.php:13](../../../config/custom.php:13) (currently `true`).
That flows through [Helpers.php:123](../../../app/Helpers/Helpers.php:123) to `rtlSupport`, which
[Helpers.php:168-169](../../../app/Helpers/Helpers.php:168) turns into the `/rtl` path segment injected
into the `@vite` entrypoint at
[styles.blade.php:10](../../../resources/views/layouts/sections/styles.blade.php:10).

Also check the default at [Helpers.php:31](../../../app/Helpers/Helpers.php:31) (`'myRTLSupport' => true`),
and note the value is **cookie-overridable**, so a stale cookie can reintroduce RTL for an individual user
even after the config flips — the spec should say whether those cookies get cleared.

**Sequencing matters:** flip this *before or with* the `public/hot` deletion. Deleting `hot` while
`rtlSupport` is still `true` means serving *precompiled RTL* CSS for the first time — currently nothing
loads at all, so the RTL setting has been invisible. Do them together or the layout may visibly flip.

### 3. Consolidate the duplicated trees.

Sequenced after the deletion, since it has no measurable perf win but removes a live footgun: the manifest
is read from `public/build/manifest.json` while the bytes are served from the root `build/` — two
independently-aged copies, one supplying filenames and the other content. Any future build that updates
one and not the other yields filenames pointing at absent bytes.

Consolidate: `hot` / `public/hot`, `build/` / `public/build/`, and the two scss trees
(`resources/assets/vendor/scss/` vs `Modules/Contract/resources/vendor/scss/`). The authoritative copies
per [ticket 03](03-vite-setup-research.md) are the ones under `public/` for hot and manifest — but the
*bytes* are served from root `build/`, so the consolidation must reconcile that, not just delete one side.

### 4. Also fix

`contracts/assets/fonts/font-main.css` fails with `ERR_ABORTED` (~0.9–1.3 s) and is not a Vite URL — it
survives everything above and needs its own fix.

### Nothing here was applied

This ticket produced decisions only. `public/hot` still exists, `myRTLSupport` is still `true`. All of it
flows into [Assemble and agree the spec](10-assemble-spec.md).
