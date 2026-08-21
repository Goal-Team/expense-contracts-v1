/**
 * Root Vite config for the contracts app.
 *
 * This file MUST stay committed. It went missing once because .gitignore held a
 * blanket `*.mjs` rule, so the old config was never in git and `npm run build`
 * stopped working on every machine that did not already have a copy. That rule
 * has been narrowed to `/vite.config.*.timestamp-*.mjs`, which is the only
 * `.mjs` anyone actually wanted ignored (Vite's throwaway loader file).
 * `git check-ignore -v vite.config.mjs` now comes back clean.
 *
 * The `.mjs` extension is required, not a preference: laravel-vite-plugin 1.0.1
 * is ESM-only and package.json has no `"type": "module"`, so a `vite.config.js`
 * is loaded as CommonJS and the plugin import fails with
 * "resolved to an ESM file. ESM file cannot be loaded by `require`".
 *
 * What it has to do:
 *  - load laravel-vite-plugin, so a Laravel manifest is emitted at all;
 *  - write that manifest to `public/build/manifest.json`, which is the only
 *    path Laravel's Vite helper reads here (`public_path('/hot')` / `public/build`);
 *  - declare every entrypoint the blades ask for through `@vite(...)` and
 *    `Vite::asset(...)`.
 *
 * Known cost, not fixed here: `resources/assets/vendor/scss/core.scss` takes
 * 5.6-7.9 s on its own, because sass 1.71.0 is the pure-JS build and Vite 5.1.3
 * drives it through the legacy sass API. `css.preprocessorOptions.sass.api =
 * 'modern-compiler'` needs Vite 5.4+, so it is a dependency upgrade, not a flag.
 * Deliberately not changed here.
 */

import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import { globSync } from 'glob';

/** Glob, always returning forward-slashed paths relative to this file. */
const files = (pattern) =>
    globSync(pattern, { cwd: __dirname })
        .map((file) => file.split(path.sep).join('/'))
        .sort();

/** Sass partials (`_name.scss`) are imported by other files; they are not entrypoints. */
const notAPartial = (file) => !path.basename(file).startsWith('_');

const input = [
    // Template runtime: helpers.js, template-customizer.js, ...
    ...files('resources/assets/vendor/js/*.js'),

    // Icon and flag fonts, requested by layouts/sections/styles.blade.php
    ...files('resources/assets/vendor/fonts/*.scss'),

    // Third-party libs shipped inside the template (select2, datatables,
    // formvalidation, flatpickr, ...). Each blade pulls in only the ones it uses.
    ...files('resources/assets/vendor/libs/**/*.js').filter(notAPartial),
    ...files('resources/assets/vendor/libs/**/*.css').filter(notAPartial),
    ...files('resources/assets/vendor/libs/**/*.scss').filter(notAPartial),

    // Theme core + the theme/style variants. styles.blade.php builds these names
    // at runtime from $configData['rtlSupport'], ['theme'] and ['style'], and
    // scriptsIncludes.blade.php resolves every theme through Vite::asset(), so the
    // whole tree has to be built, not just the currently selected combination.
    ...files('resources/assets/vendor/scss/**/*.scss').filter(notAPartial),

    // Per-page scripts, one per blade.
    ...files('resources/assets/js/*.js'),
    'resources/assets/css/demo.css',

    // Laravel's own default entrypoints.
    ...files('resources/css/*.css'),
    ...files('resources/js/*.js'),

    // Module scripts that the previous build also emitted.
    'Modules/Contract/resources/assets/js/contract.js',
    'Modules/Contract/resources/assets/js/contractlist.js',
];

export default defineConfig({
    build: {
        // Laravel reads public/build/manifest.json. Vite's default is dist/.
        outDir: 'public/build',
        // Named on purpose. Plain `true` would make Vite 5 write
        // public/build/.vite/manifest.json, which Laravel never reads.
        manifest: 'manifest.json',
        emptyOutDir: true,
    },

    plugins: [
        laravel({
            publicDirectory: 'public',
            buildDirectory: 'build',
            input: [...new Set(input)],
            refresh: true,
        }),
    ],

    server: {
        // Explicit, because the stale public/hot pointed the browser at
        // http://[::1]:5173 and every asset request was refused. If dev is ever
        // run again it must advertise a host the browser can actually reach.
        host: '127.0.0.1',
        port: 5173,
        strictPort: true,
    },
});
