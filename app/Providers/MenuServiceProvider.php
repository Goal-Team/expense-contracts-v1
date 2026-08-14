<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\MenuConfig;
use Illuminate\Support\Facades\Schema;
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
        View::composer('*', function ($view) {
            // Load defaults from the static JSON files so we always have a fallback
            $verticalMenuData = null;
            $horizontalMenuData = null;
            // Try to override with DB-backed menu for the current user's role (if present)
            //try {
              $currentRole = session()->get('contractSessionUserRole') ?? null;

              if (Schema::hasTable('menu_configs')) {
                try {
                  // Check admin flag whether to use admin-level (per role) menu configuration
                  $useAdminLevel = (bool) admin_setting('enable_admin_level_menu_config', false);

                  // Helper to fetch menu config by type with fallbacks:
                  // If admin-level enabled: try current role first
                  // Otherwise: skip role lookup and use 'default' role from table
                  $getConfig = function($type) use ($currentRole, $useAdminLevel) {
                    $config = null;

                    if ($useAdminLevel && $currentRole) {
                      $config = MenuConfig::where('menu_type', $type)
                        ->where('role', $currentRole)
                        ->where('active', 1)
                        ->first();
                    }

                    // Fallback to explicit default role (case-insensitive)
                    if (! $config) {
                      $config = MenuConfig::where('menu_type', $type)
                        ->whereRaw('LOWER(role) = ?', [strtolower('default')])
                        ->where('active', 1)
                        ->first();
                    }

                    // Last fallback: any active entry with NULL role
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

                  if ($verticalConfig && !empty($verticalConfig->menu_json)) {
                    $verticalMenuData = json_decode($verticalConfig->menu_json);
                  }
                  if ($horizontalConfig && !empty($horizontalConfig->menu_json)) {
                    $horizontalMenuData = json_decode($horizontalConfig->menu_json);
                  }
                } catch (\Exception $e) {
                  // Ignore errors and fall back to static defaults
                }
              }
            //} catch (\Exception $e) {
              // Ignore errors and fall back to static defaults
            //}
    
            $view->with('menuData', [
                $verticalMenuData,
                $horizontalMenuData
            ]);
        });    
  }
    
}