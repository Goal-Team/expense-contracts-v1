<?php

namespace App\Providers;

use App\Menu\MenuDataResolver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class MenuServiceProvider extends ServiceProvider
{
  /**
   * Register services.
   */
  public function register(): void
  {
    //
  }

  /**
   * Bootstrap services.
   */
  public function boot(): void
  {
        // Named views, not '*'. Only these two read $menuData, and they are the only two menu view
        // names in the codebase - every layout, root and all five modules, includes these same two
        // (contentNavbarLayout.blade.php:39 and horizontalLayout.blade.php:54). With '*' this closure
        // ran once for each of the 16 views the dashboard composes, and 14 of those runs handed a
        // value to a view that never reads it.
        //
        // The lookup itself lives in MenuDataResolver and is cached, so only the first run of a cache
        // generation touches the database. Change G, .scratch/contracts-dashboard-perf/spec.md 8b.
        View::composer([
            'layouts.sections.menu.verticalMenu',
            'layouts.sections.menu.horizontalMenu',
        ], function ($view) {
            $currentRole = session()->get('contractSessionUserRole') ?? null;

            $view->with('menuData', MenuDataResolver::resolveForRole($currentRole));
        });
  }

}
