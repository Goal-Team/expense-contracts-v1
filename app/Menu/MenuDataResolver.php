<?php

namespace App\Menu;

use App\Models\MenuConfig;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Resolves the vertical and horizontal menu data for one role, and caches the answer.
 *
 * Change G of the dashboard performance spec
 * (.scratch/contracts-dashboard-perf/spec.md section 8b, ticket 23).
 *
 * Why this class exists: the menu was resolved inside a View::composer('*') closure, which Laravel
 * runs once for every view. The dashboard composes 15 views, so an identical answer was computed 15
 * times - 108 queries and 391 ms per request, on every page in the application. The lookup logic
 * below is the old closure body, unchanged; the only new thing is the cache around it.
 */
class MenuDataResolver
{
    /** Safety net behind the flush-on-write. Not the primary invalidation. */
    public const CACHE_MINUTES = 1440;

    /** Cache key holding the current generation number. Bumping it retires every role's entry. */
    private const VERSION_KEY = 'menu_data:version';

    /**
     * The two menu structures for this role, as the view composer needs them.
     *
     * @return array{0: mixed, 1: mixed} [vertical, horizontal] - either may be null
     */
    public static function resolveForRole(?string $role): array
    {
        $key = self::cacheKey($role);

        return cache()->remember($key, now()->addMinutes(self::CACHE_MINUTES), function () use ($role) {
            Log::debug('MenuDataResolver cache miss', ['role' => $role]);

            return self::lookUp($role);
        });
    }

    /**
     * Retire every cached menu, for every role.
     *
     * Deliberately all roles, not one: a role with no row of its own falls back to the `Default`
     * row, so editing `Default` changes the answer for roles whose names appear nowhere in the
     * write. Bumping one number is cheaper than working out which roles were affected, and it
     * cannot miss one.
     */
    public static function flush(): void
    {
        // forever() rather than increment(): increment behaves differently across cache drivers when
        // the key is absent, and a version that fails to move would leave every stale entry live.
        $next = self::version() + 1;
        cache()->forever(self::VERSION_KEY, $next);

        Log::info('MenuDataResolver flushed', ['version' => $next]);
    }

    private static function cacheKey(?string $role): string
    {
        return 'menu_data:v' . self::version() . ':role:' . ($role ?? '_none_');
    }

    private static function version(): int
    {
        return (int) cache()->get(self::VERSION_KEY, 1);
    }

    /**
     * The old closure body from MenuServiceProvider, moved whole.
     *
     * Three-step fallback per menu type: a row for this role, else the `Default` row, else a row
     * with a null role. Finding nothing is a normal outcome, not an error - there is no
     * `Super Admin` row and no `Horizontal` row in a stock install.
     */
    private static function lookUp(?string $role): array
    {
        $verticalMenuData = null;
        $horizontalMenuData = null;

        if (Schema::hasTable('menu_configs')) {
            try {
                $useAdminLevel = (bool) admin_setting('enable_admin_level_menu_config', false);

                $getConfig = function ($type) use ($role, $useAdminLevel) {
                    $config = null;

                    if ($useAdminLevel && $role) {
                        $config = MenuConfig::where('menu_type', $type)
                            ->where('role', $role)
                            ->where('active', 1)
                            ->first();
                    }

                    if (! $config) {
                        $config = MenuConfig::where('menu_type', $type)
                            ->whereRaw('LOWER(role) = ?', [strtolower('default')])
                            ->where('active', 1)
                            ->first();
                    }

                    if (! $config) {
                        $config = MenuConfig::where('menu_type', $type)
                            ->whereNull('role')
                            ->where('active', 1)
                            ->first();
                    }

                    return $config;
                };

                $verticalConfig = $getConfig('Vertical');
                $horizontalConfig = $getConfig('Horizontal');

                if ($verticalConfig && ! empty($verticalConfig->menu_json)) {
                    $verticalMenuData = json_decode($verticalConfig->menu_json);
                }
                if ($horizontalConfig && ! empty($horizontalConfig->menu_json)) {
                    $horizontalMenuData = json_decode($horizontalConfig->menu_json);
                }
            } catch (\Exception $e) {
                // Same as before: fall back to the static defaults rather than break the page.
                Log::warning('MenuDataResolver lookup failed', ['role' => $role, 'error' => $e->getMessage()]);
            }
        }

        return [$verticalMenuData, $horizontalMenuData];
    }
}
