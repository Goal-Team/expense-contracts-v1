# Vite setup: what this repo actually does, from primary sources

Research ticket: `.scratch/contracts-dashboard-perf/issues/03-vite-setup-research.md`

All `file:line` references below are against the **live app dir** `D:\Contract-Expense\GOALv4\contracts`
(the worktree has no `node_modules`, `vendor`, `build`, `hot`).

## Versions actually installed (not what composer.json asks for)

| Thing | Version | Source |
| --- | --- | --- |
| `laravel/framework` | **v10.48.29** | `vendor/composer/installed.json:2087-2088` |
| `nwidart/laravel-modules` | **10.0.6** | `vendor/composer/installed.json:4736-4737` (note `composer.json:18` requires `^9.0`) |
| `laravel-vite-plugin` | **1.0.1** | `package.json` (`"laravel-vite-plugin": "1.0.1"`), `node_modules/laravel-vite-plugin/package.json:3` |
| `vite` | **5.1.3** | `node_modules/vite/package.json:3` (`package.json` asks `^5.0.12`) |
| `sass` (dart-sass, pure-JS build) | **1.71.0** | `node_modules/sass/package.json` (`"version":"1.71.0"`) |
| `sass-embedded` | **not installed** | `ls node_modules | grep -i sass` → only `sass`, `sass-loader` |

Side finding worth flagging: **`composer.lock` in the live dir contains unresolved git merge conflict
markers** — `composer.lock:7,9,11`, `:3040,3051,3062`, `:3068,3076,3087`, `:3136,…`. The HEAD side claims
`laravel/framework v10.48.22` + `nwidart/laravel-modules v9.0.6`, the other side `v11.1.4`. The lock file is
therefore not parseable as JSON and does not describe what is installed. (`laravel-vite-plugin` reads
`composer.lock` at `node_modules/laravel-vite-plugin/dist/index.js:163` purely to print the Laravel version
in its dev banner, wrapped in try/catch at `:165`, so this does not break `npm run dev` — but every
`composer install/update` will.)

---

## 1. Hot-file resolution

### Which path is read

`@vite(...)` compiles to `app(Illuminate\Foundation\Vite::class)(...)`:

- `vendor/laravel/framework/src/Illuminate/View/Compilers/Concerns/CompilesHelpers.php:58-64`
  → `return "<?php echo app('$class'){$arguments}; ?>";`

The hot-file path:

- `vendor/laravel/framework/src/Illuminate/Foundation/Vite.php:188-191`
  ```php
  public function hotFile()
  {
      return $this->hotFile ?? public_path('/hot');
  }
  ```
- `$this->hotFile` is only ever set by `useHotFile()` (`Vite.php:199-204`). **Nothing in this app calls it.**
  A grep for `usePublicPath|useHotFile|useBuildDirectory|useManifestFilename|createAssetPathsUsing|APP_BASE_PATH`
  across the whole repo excluding `vendor/` returns exactly one hit: `bootstrap/app.php:15`. So all defaults apply.

`public_path()` resolution for **this** flattened layout:

- `bootstrap/app.php:14-16` → `new Illuminate\Foundation\Application($_ENV['APP_BASE_PATH'] ?? dirname(__DIR__))`.
  `__DIR__` is `<app>\bootstrap`, so `basePath = D:\Contract-Expense\GOALv4\contracts`. `APP_BASE_PATH` is not
  set anywhere (`.env` has no such key).
- `vendor/laravel/framework/src/Illuminate/Foundation/helpers.php:627-630` → `public_path()` = `app()->publicPath($path)`.
- `vendor/laravel/framework/src/Illuminate/Foundation/Application.php:529-532`
  ```php
  public function publicPath($path = '')
  {
      return $this->joinPaths($this->publicPath ?: $this->basePath('public'), $path);
  }
  ```
  `$this->publicPath` (declared `Application.php:148`) is only set by `usePublicPath()` (`Application.php:540-547`),
  which is never called here.

**Definitive answer: the framework reads `<app>\public\hot`, i.e. `public/hot`. The root-level `hot` file is
never read by Laravel.** `index.php` sitting at the app root does not move `public_path()` — `public_path()` is
derived from the *base path*, which `bootstrap/app.php` computes from `dirname(__DIR__)`, not from the front
controller's location.

Both files exist and are identical in the live dir:

```
hot         17 bytes  Aug 10 15:35  ->  http://[::1]:5173
public/hot  17 bytes  Aug 10 15:35  ->  http://[::1]:5173
```

Both carry the same mtime as every other file that came in with the copy (`.gitignore`, `index.php`, etc. are
all `Aug 10 15:35`), and both are gitignored (`.gitignore:5` `/public/hot`, `.gitignore:7` `/hot`). So they were
shipped in with the source copy; they are not the product of a dev server that ran on this machine.

### What a stale hot file does

- `Vite.php:792-795`
  ```php
  public function isRunningHot()
  {
      return is_file($this->hotFile());
  }
  ```
  **That is the entire dev-server detection: a filesystem `is_file()` check. No socket connect, no HTTP probe,
  no timeout, no port check.**
- `Vite.php:284-291` — when `isRunningHot()` is true, `__invoke()` returns immediately with
  `@vite/client` prepended and every entrypoint mapped through `hotAsset()`; `$this->manifest()` on line 293 is
  never reached.
- `Vite.php:653-656`
  ```php
  protected function hotAsset($asset)
  {
      return rtrim(file_get_contents($this->hotFile())).'/'.$asset;
  }
  ```

So with `public/hot` present containing `http://[::1]:5173`, `@vite('resources/assets/vendor/scss/core.scss')`
emits literally `http://[::1]:5173/resources/assets/vendor/scss/core.scss` — a hard-coded absolute URL to a
port that nothing is listening on. Same for `Vite::asset()` (`Vite.php:665-676`, hot branch at `:669-671`),
which `resources/views/layouts/sections/scriptsIncludes.blade.php:27-34` uses.

### Fallback to the manifest — verified absent

**There is no fallback.** I read `Vite.php` end to end (807 lines). The complete set of places the hot file
influences behaviour is `__invoke()` (`:284`), `asset()` (`:669`), `reactRefresh()` (`:623`), and
`manifestHash()` (`:758`). In every one of them the branch is taken purely on `is_file()`, and there is no
`try`/`catch`, `@`-suppression, `curl`/`fsockopen`/`stream_socket_client`, `Http::` call, or retry anywhere in
the class. `manifest()` (`:722-735`) is only ever called from the non-hot branches. The framework cannot know
the dev server is down, and cannot recover if it is.

The Laravel 10 docs match: "The `@vite` directive will automatically detect the Vite development server and
inject the Vite client" — detection being the hot file — and the plugin's job is to remove it on shutdown
(<https://laravel.com/docs/10.x/vite#loading-your-scripts-and-styles>). `laravel-vite-plugin` deletes the hot
file in its exit handlers, `node_modules/laravel-vite-plugin/dist/index.js:120-131`:

```js
const clean = () => { if (fs.existsSync(pluginConfig.hotFile)) { fs.rmSync(pluginConfig.hotFile); } };
process.on("exit", clean); process.on("SIGINT", …); process.on("SIGTERM", …); process.on("SIGHUP", …);
```

A hard kill (Windows task-kill, machine reset, or — as here — the file being copied in with the source tree)
leaves the file behind and there is nothing in Laravel that notices.

### Where `http://[::1]:5173` comes from

`node_modules/laravel-vite-plugin/dist/index.js:251-267`: `resolveDevServerUrl()` takes the Node
`AddressInfo`; `:259` `const serverAddress = isIpv6(address) ? \`[${address.address}]\` : address.address;`
and `:263` returns `${protocol}://${host}:${port}`. With no `server.host` configured, Node binds `::1` and the
literal IPv6 form is written to the hot file at `:103` `fs.writeFileSync(pluginConfig.hotFile, viteDevServerUrl)`.
Laravel's own docs use the same `http://[::1]:5173` as their worked example
(<https://laravel.com/docs/10.x/vite#correcting-dev-server-urls>). The plugin's default hot-file *write* path
is `path.join(config.publicDirectory ?? "public", "hot")` (`dist/index.js:211`) — i.e. `public/hot`, relative
to Vite's CWD — which agrees with what Laravel reads.

---

## 2. Manifest resolution

### Which manifest is read

- `Vite.php:743-746`
  ```php
  protected function manifestPath($buildDirectory)
  {
      return public_path($buildDirectory.'/'.$this->manifestFilename);
  }
  ```
- `$buildDirectory` defaults to `'build'` (`Vite.php:49`), `$manifestFilename` to `'manifest.json'`
  (`Vite.php:56`); neither is overridden anywhere in the app (see the grep in §1).

**Definitive answer: `<app>\public\build\manifest.json`.** The root `build/manifest.json` is never read by
Laravel. (`Vite.php:722-735` throws `ViteManifestNotFoundException` if that exact path is absent, and caches
per-path in the static `$manifests` array.)

Both exist:

```
build/manifest.json          192061 bytes  Aug 10 15:35   1048 entries
public/build/manifest.json   192233 bytes  Jul  1  2024   1049 entries
```

Differences between the two:

- Only in root `build/`: `modules/contract/resources/assets/js/script.js`.
- Only in `public/build/`: `modules/contract/resources/assets/js/contract.js`,
  `modules/contract/resources/assets/js/contractlist.js`.
- Two shared keys point at swapped files: `resources/css/app.css` and `resources/js/app.js` have each other's
  hashed output (`assets/app-l0sNRNKZ.js` ↔ `assets/app-DP2rzg_V.js`). Both output files exist in both
  directories, so this is cosmetic here.
- `resources/assets/vendor/scss/core.scss → assets/core-7_a25xA8.css` is **identical in both manifests**, and
  `build/assets/core-7_a25xA8.css` and `public/build/assets/core-7_a25xA8.css` are both present at 546 735 bytes.

I scanned all 441 blade templates under `resources/views/**` and `Modules/*/resources/views/**` for literal
`@vite([...])` arguments (4 non-literal/dynamic fragments skipped — the config-driven ones in
`resources/views/layouts/sections/styles.blade.php:10-11` and `stylesFront.blade.php:9-10`) and checked every
literal entrypoint against both manifests: **0 entrypoints are missing from `public/build/manifest.json`, and
0 from `build/manifest.json`.** I also resolved the specific entrypoint list used by the contracts dashboard
(`Modules/Contract/resources/views/dashboard/viewDashboard.blade.php:5,9,16,17`, plus `core.scss`,
`theme-default.scss`, `resources/assets/vendor/js/bootstrap.js`, `resources/assets/js/main.js`) through
`public/build/manifest.json` and confirmed every mapped output file exists on disk under the root `build/`
directory that the URLs actually resolve to.

### What determines the emitted public prefix, and the `/contracts` IIS base path

- `Vite.php:335-340` builds the tag from `$this->assetPath("{$buildDirectory}/{$chunk['file']}")`.
- `Vite.php:709-712` → `return ($this->assetPathResolver ?? asset(...))($path, $secure);` — `assetPathResolver`
  is null (never set), so plain `asset()`.
- `vendor/laravel/framework/src/Illuminate/Routing/UrlGenerator.php:252-264`
  ```php
  $root = $this->assetRoot ?: $this->formatRoot($this->formatScheme($secure));
  return Str::finish($this->removeIndex($root), '/').trim($path, '/');
  ```
- `assetRoot` comes from `config('app.asset_url')` (`vendor/laravel/framework/src/Illuminate/Routing/RoutingServiceProvider.php:67`),
  which is `env('ASSET_URL')` (`config/app.php:67`). **`ASSET_URL` is not set in `.env`** (only `APP_URL=http://localhost`
  at `.env:8` and two `VITE_PUSHER_*` keys). No `forceRootUrl`/`forceScheme` call exists in `config/` or `app/Providers/`.

So `assetRoot` is null and the prefix is `$request->root()`, which under IIS with the app at `/contracts`
(script `/contracts/index.php`) is `http(s)://host/contracts`. Emitted URL:
`http://host/contracts/build/assets/core-7_a25xA8.css`. `removeIndex()` (`UrlGenerator.php:295+`) strips
`index.php` from that root.

**This interacts correctly with the IIS base path** — and it explains the split: the manifest is read from
`public/build/` on disk, but the URL is `/contracts/build/...`, which IIS serves from the **root** `build/`
directory (`web.config` rewrites only when `{REQUEST_FILENAME}` is neither a file nor a directory, so real
files under `build/` are served straight off disk). The two directories happen to be near-identical copies,
so this works today by coincidence, not by design. `Vite::content()` (`Vite.php:687-700`) would read
`public_path('build/…')`, i.e. the `public/` copy — that method is not used in this app.

---

## 3. The canonical root config for `laravel-vite-plugin` + `nwidart/laravel-modules`

Per the official Laravel Modules docs (<https://laravelmodules.com/docs/10/basic-usage/compiling-assets>,
matching installed v10.0.6), the root `vite.config.js` is:

```js
import {defineConfig} from 'vite';
import laravel from 'laravel-vite-plugin';
import collectModuleAssetsPaths from './vite-module-loader.js';

async function getConfig() {
    const paths = [
        'resources/css/app.css',
        'resources/js/app.js',
    ];
    const allPaths = await collectModuleAssetsPaths(paths, 'Modules');

    return defineConfig({
        plugins: [
            laravel({
                input: allPaths,
                refresh: true,
            })
        ]
    });
}

export default getConfig();
```

Key points from that page:

- `collectModuleAssetsPaths` "loads all enabled modules, reads their `vite.config.js` files and compiles them
  into one collection"; the second argument is the modules folder and must be changed if the folder is renamed.
- The loader is published to the project root with
  `php artisan vendor:publish --provider="Nwidart\Modules\LaravelModulesServiceProvider" --tag="vite"`.
  The shipped source is `vendor/nwidart/laravel-modules/scripts/vite-module-loader.js`.
- **A module's `vite.config.js` must export a `paths` array** — that is the only thing the loader consumes:
  ```js
  export const paths = [
      'Modules/Blog/resources/assets/sass/app.scss',
      'Modules/Blog/resources/assets/js/app.js',
  ];
  ```

Confirmed against the installed loader source, `vendor/nwidart/laravel-modules/scripts/vite-module-loader.js`:
it joins `__dirname` + `modulesPath`, reads `modules_statuses.json`, and for each module whose status is `true`
imports `Modules/<Name>/vite.config.js` and does `if (moduleConfig.paths && Array.isArray(moduleConfig.paths))
paths.push(...moduleConfig.paths)`. **The module config's `default` export (`defineConfig({...})`) is ignored
entirely by the loader.**

### Applied to this repo

- Root `vite-module-loader.js` exists and is a **hand-modified** copy of the vendor file: it replaces
  `fs.stat`/`stat.isFile()` with a `try { await fs.access(...) } catch { /* skip */ }` guard (diff verified
  against `vendor/nwidart/laravel-modules/scripts/vite-module-loader.js`). Functionally equivalent, more
  tolerant of a missing module config.
- It is imported by nothing, because **no root `vite.config.js`/`.mjs` exists** — so `npm run dev` /
  `npm run build` (`package.json` `"dev": "vite"`, `"build": "vite build"`) would run Vite with no
  `laravel()` plugin at all: no `input`, no hot file written, no manifest keys, `publicDir` not disabled.
- All five modules are enabled (`modules_statuses.json`: Contract, ContractParties, ApprovalRules,
  Contractsetup, Tasks), and **every one of `Modules/*/vite.config.js` has its `export const paths` block
  commented out** — verified in all five files; they are byte-for-byte the vendor stub
  (`vendor/nwidart/laravel-modules/src/Commands/stubs/vite.stub`, also copied to
  `stubs/nwidart-stubs/vite.stub`) with the name substituted. So even if a root config existed and called
  the loader, `collectModuleAssetsPaths` would contribute **zero** module paths.
- The stubs' *own* `defineConfig` blocks target per-module output dirs `public/build-<module>` with
  `buildDirectory: 'build-<module>'`. Those directories do **not** exist under `public/` (`ls public` →
  `assets build hot storage`), and Laravel would need `@vite($entry, 'build-contract')` to read them
  (`Vite.php:279-282`, second arg). Nothing in the app does that. Meanwhile the manifests contain
  `modules/contract/resources/assets/js/*.js` keys inside the single `build/manifest.json` — i.e. whoever
  produced the current build did it with a **single root config** whose `input` included module paths
  directly, not with the per-module `defineConfig` route.

So the missing root config for this repo, reconstructed, would need `input` to include the app-level Vuexy
entrypoints **plus** the module paths, and the module configs' `paths` exports would need to be uncommented.
The `.gitignore:41` entry `vite.config.mjs.timestamp-1727949545676-a6c5910f065c.mjs` (repeated three times)
plus `.gitignore:44` `*.mjs` is Vite's own esbuild side-car artifact naming — evidence a `vite.config.mjs`
existed on 2024-10-03 (that epoch ms) and that `*.mjs` in `.gitignore` is now actively suppressing it from
ever being committed again.

---

## 4. Is compiling the Vuexy `core.scss` tree actually slow?

### Measured on this machine (primary evidence)

Using the installed compiler directly, output written to the scratch temp dir (no app file touched):

```
node node_modules/sass/sass.js --no-source-map --load-path=node_modules resources/assets/vendor/scss/core.scss
  run 1: real 7.90s   (output 609,674 bytes)
  run 2: real 5.57s
theme-default.scss:  real 2.64s
```

`resources/assets/vendor/scss/core.scss` is a 4-line file (`@import 'bootstrap'; 'bootstrap-extended';
'components'; 'colors';`) fanning out over a tree of **170 `.scss` files** under
`resources/assets/vendor/scss`. The production output is 546,735 bytes
(`build/assets/core-7_a25xA8.css`). So: **yes, this tree genuinely costs ~5–8 seconds per full compile.**
Caveat: the `sass` CLI uses the modern compiler internally, so this is a *floor*, not the cost Vite 5.1.3
would incur.

### The installed compiler and API path

- `sass@1.71.0` is dart-sass **transpiled to pure JavaScript** — `node_modules/sass/package.json`:
  `"description":"A pure JavaScript implementation of Sass"`. `sass-embedded` (native Dart binary) is **not
  installed**.
- **Vite 5.1.3 uses the legacy JS API.** `node_modules/vite/dist/node/chunks/dep-stQc5rCc.js:32504`
  → `sass.render(finalOptions, (err, res) => {` — the legacy callback API, invoked from the sass processor at
  `:32453-32565`, inside a `WorkerWithFallback` (`:32471`). Grepping the whole `node_modules/vite/dist/node/chunks/`
  tree for `modern-compiler`, `'modern'`, `sass-embedded`, and `compileStringAsync` returns **zero hits**.
  There is no `css.preprocessorOptions.scss.api` option in this Vite version to set.
- Dart Sass deprecated the legacy JS API in **1.45.0**, started emitting warnings in **1.79.0**, and will
  remove it in 2.0.0 (<https://sass-lang.com/documentation/breaking-changes/legacy-js-api/>). 1.71.0 is
  therefore in the "deprecated, no warning yet" window.

### Documented mitigations

- `css.preprocessorOptions.scss.api = 'modern-compiler'` — accepted values `'legacy'` (`sass.render`),
  `'modern'` (`sass.compileStringAsync`), `'modern-compiler'` (Compiler API, uses `sass-embedded`). Shipped in
  **Vite 5.4** (<https://github.com/vitejs/vite/pull/17728>, <https://github.com/vitejs/vite/pull/17754>);
  default from Vite 6; legacy support removed in Vite 7. **Not available in the installed 5.1.3 — this
  requires a Vite upgrade, it is not a config-only fix.**
- Install `sass-embedded` alongside/instead of `sass`: native Dart executable behind the same API, "in many
  situations faster"; Vite prefers it when present. Reported gains: ~4.7s→~3.9s (small project),
  ~5.9s→~3.8s (larger), with some reports of up to 8x
  (<https://www.oddbird.net/2024/08/14/sass-compiler/>).
- Why the legacy path is specifically bad under Vite: "For every Sass import, a new instance of Sass would
  spin up, compile, and spin down"; the modern Compiler API reuses one instance across compilations
  (<https://www.oddbird.net/2024/08/14/sass-compiler/>).

Note `postcss.config.js` exists at the root, so Vite would also run PostCSS/autoprefixer over that 546 KB of
output on every dev compile.

---

## Verdict on the three working hypotheses in the map

**(a) `@vite` is emitting dead `http://[::1]:5173/...` URLs because stale hot files exist with no dev server
running — CONFIRMED, with one correction.** Only **`public/hot`** matters. `Vite.php:190` reads
`public_path('/hot')`, and `public_path()` resolves off the *base path* (`bootstrap/app.php:15` →
`Application.php:529-532`), not off `index.php`'s location, so it is `<app>\public\hot`. The root `hot` file
is inert as far as Laravel is concerned. Detection is a bare `is_file()` (`Vite.php:792-795`) with **no
network check and no manifest fallback anywhere in the class** — verified by reading all 807 lines.

**(b) Deleting both hot files would fall back to the precompiled `build/assets/core-7_a25xA8.css` —
CONFIRMED.** With no hot file, `__invoke()` reads `public/build/manifest.json` (`Vite.php:743-746`), which
maps `resources/assets/vendor/scss/core.scss → assets/core-7_a25xA8.css`, and the emitted URL
`/contracts/build/assets/core-7_a25xA8.css` resolves to the root `build/` copy, which exists (546,735 bytes).
I verified all literal `@vite` entrypoints across all 441 blades resolve in `public/build/manifest.json` with
0 misses, so no page should 500 on `ViteManifestNotFoundException`/`Unable to locate file in Vite manifest`.
Nuances worth carrying forward: (i) strictly only `public/hot` needs deleting, though deleting both is
harmless and less confusing; (ii) the manifest that is *read* (`public/build/`) is a Jul 2024 copy while the
files that are *served* come from the Aug 2025 root `build/` — they are near-identical today but this is an
accident waiting to break; (iii) `modules/contract/resources/assets/js/script.js` exists in the root manifest
but not the one Laravel reads — currently unreferenced by any blade, but it would 500 if a template ever
requests it.

**(c) "core.css takes too long to compile" is therefore not a compilation cost at all — PARTLY CONTRADICTED.**
For the *observed* symptom it is correct: with a stale hot file nothing compiles at all, the browser just
fails to reach `[::1]:5173`, and the page renders unstyled/slow. But the underlying premise the dev started
from is not wrong — the tree really is expensive (measured 5.6–7.9s for a single `core.scss` compile,
170 SCSS files, 547 KB output), and the installed toolchain is the slow configuration: pure-JS `sass@1.71.0`
driven through Vite 5.1.3's **legacy** `sass.render` API (`dep-stQc5rCc.js:32504`), with no
`api: 'modern-compiler'` option available at that Vite version. So if the dev server is ever actually
started, first-paint cost on a cold cache will be real and multi-second. Framing (c) as "not a compilation
cost" is right about today's bug and wrong about tomorrow's dev experience.

---

## Open questions / could not determine

- **I did not verify empirically that the dev server is unreachable**, because starting one and probing
  `[::1]:5173` were both out of scope for this ticket. The code path proves *what happens if* it is
  unreachable; the "no dev server is running" premise comes from the ticket and from the hot files' mtimes
  matching the rest of the copied tree, not from a live port check.
- **How slow `core.scss` is inside Vite 5.1.3 dev specifically** is not measured. My 5.6–7.9s numbers come
  from the `sass` CLI (modern compiler, no Vite, no PostCSS, no importer indirection). Vite's legacy-API path
  plus the custom `internalImporter` (`dep-stQc5rCc.js:32455-32487`) and PostCSS would plausibly be slower,
  but I cannot quantify it without running the dev server.
- **Whether Vite dev recompiles the whole `core.scss` tree on each edit or serves from its cache** in this
  version — I did not trace Vite's dev CSS caching, so I cannot say what the *warm* HMR cost is versus the
  ~6s cold cost.
- **What the original `vite.config.mjs` contained.** The `.gitignore` timestamp artifact
  (`vite.config.mjs.timestamp-1727949545676-…`) proves one existed around 2024-10-03, but it is not in git
  history and not on disk. My §3 reconstruction is from the vendor docs plus what the existing
  `build/manifest.json` keys imply, not from the actual lost file.
- **Which of `build/` vs `public/build/` was produced by which run, and whether `public/build/` is meant to
  be the live one.** Their mtimes (Jul 2024 vs Aug 2025) and differing module keys say they are separate
  builds, but I found no script, deploy step, or `.gitignore`/`web.config` rule that explains the duplication
  or which is authoritative.
- **The `composer.lock` merge conflict** — I did not determine which side is intended, nor whether
  `nwidart/laravel-modules` 10.0.6 being installed against a `^9.0` constraint (`composer.json:18`) is
  deliberate or the residue of the same botched merge.
