<?php

namespace App\Providers;

use Barryvdh\Debugbar\ServiceProvider as DebugbarServiceProvider;
use Illuminate\Support\ServiceProvider;

/**
 * Local-only registration of barryvdh/laravel-debugbar.
 *
 * Debugbar is deliberately NOT auto-discovered: it is listed in
 * extra.laravel.dont-discover in composer.json, so the only thing that can
 * register it is this file.
 *
 * Why the wrapper exists at all (ticket 16). Debugbar's own gate is
 * DEBUGBAR_ENABLED, and when that is unset it falls back to APP_DEBUG. There is
 * no environment check in it. So on a server where APP_DEBUG was left true,
 * Debugbar would paint query bindings - decrypted contract fields - onto a page
 * any visitor can load, and expose POST /_debugbar/queries/explain. This
 * provider demands APP_DEBUG *and* APP_ENV exactly 'local', the same pair
 * PerfTimingServiceProvider uses, so a wrong APP_DEBUG alone is not enough.
 *
 * Three independent locks, per ticket 16:
 *   1. this provider (holds even if the .env is wrong)
 *   2. DEBUGBAR_ENABLED=false in the production block of .env.example
 *   3. DEBUGBAR_STORAGE_ENABLED=false, so nothing is written to disk
 *
 * To remove Debugbar entirely: drop the registration line from config/app.php,
 * delete this file, and `composer remove --dev barryvdh/laravel-debugbar`.
 */
class LocalDebugbarServiceProvider extends ServiceProvider
{
    protected function enabled(): bool
    {
        // DEBUGBAR_ENABLED is checked HERE, not left to Debugbar's own gate.
        //
        // Debugbar's internal gate only stops it *rendering*. Its provider still
        // registers, so all 107 of its PHP files are loaded on every request. With
        // opcache off on this server that is recompiled every time, and it showed up
        // in the perf log as bootstrap cost on requests that never display a bar -
        // including the AJAX option-list endpoint. Checking the flag before
        // registering keeps those files off the boot path entirely.
        //
        // Default is FALSE: the measuring state is the resting state, so someone has
        // to opt in to slow the app down.
        //
        // env() rather than config(): Debugbar's own config file is not published, so
        // there is no config key to read. Caveat - if anyone ever runs
        // `php artisan config:cache`, Laravel stops loading .env and env() returns
        // null, which reads as false and leaves the bar off. That is the safe
        // direction to fail, and there is no config cache on this install.
        if (! filter_var(env('DEBUGBAR_ENABLED', false), FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        // trim(): the local .env has a trailing space after APP_ENV=local.
        return filter_var(config('app.debug'), FILTER_VALIDATE_BOOLEAN)
            && trim((string) config('app.env')) === 'local'
            && class_exists(DebugbarServiceProvider::class);
    }

    public function register(): void
    {
        if (! $this->enabled()) {
            return;
        }

        $this->app->register(DebugbarServiceProvider::class);
    }
}
