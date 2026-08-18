# What is the correct Vite setup this repo is missing?

Type: research
Status: resolved
Blocked by: —

## Question

This repo has no root `vite.config.js`/`.mjs` — not in the worktree, not in its parent working dir,
never in git history — yet `package.json` declares `"dev": "vite"` and `"build": "vite build"`,
`.gitignore` bans `*.mjs`, and a stale `vite.config.mjs.timestamp-*` entry still sits in `.gitignore`.
Meanwhile `vite-module-loader.js` exists at the root and is imported by nothing, and
`Modules/Contract/vite.config.js` has its `export const paths` block commented out.

Establish from primary sources — `laravel-vite-plugin` and `nwidart/laravel-modules` docs, plus the
installed `vendor/laravel/framework` Vite helper source — the following:

1. **Hot-file resolution.** Exactly which path does Laravel's `Vite` helper check for the hot file, and
   how does that resolve in this *flattened* layout where `index.php` sits at the app root rather than
   in `public/`? Both `hot` and `public/hot` exist here. Read the actual `Illuminate\Foundation\Vite`
   source in `vendor/`, not the docs, and quote the decisive lines. Determine whether a stale hot file
   with no dev server running causes the emitted URLs to be dead (and whether the framework has any
   fallback to the manifest when it can't reach the dev server — verify, don't assume).
2. **Manifest resolution.** Which manifest path does `@vite` read in this layout, given both
   `build/manifest.json` and `public/build/manifest.json` exist? What determines the public prefix of
   the emitted URL, and does it interact correctly with the `/contracts` IIS base path?
3. **The canonical root config** for `laravel-vite-plugin` + `nwidart/laravel-modules`, including how
   `collectModuleAssetsPaths` is meant to be wired in, and what the module-level `vite.config.js`
   files are supposed to export.
4. **Whether `sass` compilation of the Vuexy `core.scss` tree is actually slow** in Vite dev, and what
   the documented mitigations are — this is the dev's original hypothesis for problem #2 and it should
   be either confirmed or refuted from sources rather than guessed at.

Capture findings as a Markdown file on a throwaway `research/vite-setup` branch and link it here. Do
not modify the app's build configuration — this ticket produces facts, not changes.

## Answer

Full findings: [research/vite-setup.md](../research/vite-setup.md). Summary:

**1. Hot file — only `public/hot` matters.** `Vite.php:190` reads `public_path('/hot')`.
`bootstrap/app.php:15` sets the base path to the app root via `dirname(__DIR__)`, and
`Application.php:529-532` appends `public`. Nothing anywhere outside `vendor/` calls `usePublicPath`,
`useHotFile`, or `useBuildDirectory`. So **`public/hot` (dated Sep 2024) is read and the root `hot` is
inert.**

Detection is a bare `is_file()` (`Vite.php:792-795`) — **no network probe, and no manifest fallback
anywhere in the 807-line class.** The hot branch at `:284-291` returns before `manifest()` is ever
reached at `:293`. So a stale hot file with no dev server running produces dead URLs with no recovery
path. Verified in the installed source, not assumed.

**2. Manifest — `public/build/manifest.json` is read** (`Vite.php:743-746`, a Jul 2024 copy with 1049
entries); the root `build/manifest.json` is never read. Emitted URLs come from `asset()` against the
request root (`ASSET_URL` unset, `config/app.php:67`, no `forceRootUrl`), giving
`/contracts/build/assets/...` — correct under IIS, but **served from the root `build/` directory while
the manifest that named those files came from `public/build/`.** Two independently-aged copies, one
read for names and the other read for bytes.

**3. Root config.** Reconstructed from the laravel-modules v10 docs. The decisive detail:
`collectModuleAssetsPaths` consumes **only** a module's `export const paths` — a module's
`defineConfig` default export is ignored entirely. All five enabled modules have `paths` commented
out, so the loader would contribute **zero** paths even if a root config existed.

**4. sass — the dev's original hypothesis was correct.** `core.scss` measured at **5.6–7.9s** (170 SCSS
files, 547 KB output). `sass@1.71.0` is the pure-JS implementation, `sass-embedded` is absent, and
**Vite 5.1.3 uses the legacy sass API** (`dep-stQc5rCc.js:32504` calls `sass.render(...)`; no
`modern-compiler` or `compileStringAsync` anywhere in its dist). The `api: 'modern-compiler'` option
only shipped in **Vite 5.4** (PR 17728), so this is a dependency upgrade, not a config flag.

**Against the map's working hypotheses:** (a) confirmed, narrowed to `public/hot` only. (b) confirmed —
all 441 blades' literal `@vite` entrypoints were checked against `public/build/manifest.json` with zero
misses, so deleting the hot file cannot break an entrypoint. (c) **partly wrong.** Correct about
today's bug, but wrong as a general claim: the scss tree genuinely costs ~6s and the installed
toolchain is the slow configuration. Both problems are real and independent.

**Side finding, unrelated to assets:** `composer.lock` contains **unresolved merge conflict markers**
(lines 7, 3040, 3068, …) and is therefore invalid JSON. Installed `nwidart/laravel-modules` is 10.0.6
against a `^9.0` constraint.
