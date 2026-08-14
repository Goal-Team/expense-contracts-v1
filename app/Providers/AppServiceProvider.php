<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Vite;

class AppServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   */
  public function register(): void
  {
    $this->app->singleton(\App\Services\AgreementTemplateStorageService::class);
    $this->app->singleton(\App\Services\AgreementTemplateVariableService::class);
    $this->app->singleton(\App\Services\AgreementTemplateValidationService::class);
    $this->app->singleton(\App\Services\AgreementTemplateSourceResolver::class);
    $this->app->singleton(\App\Services\AgreementTemplateRenderService::class);
  }

  /**
   * Bootstrap any application services.
   */
  public function boot(): void
  {
    // PHPWord defaults its temp directory to sys_get_temp_dir() (C:\Windows\TEMP on this
    // Windows host), which the web-server user cannot read/write -> "scandir(...PHPWordWriter_...)
    // Access is denied (code: 5)". Point it at Laravel's writable storage instead.
    try {
      $phpWordTemp = storage_path('app/phpword_temp');
      if (!is_dir($phpWordTemp)) {
        @mkdir($phpWordTemp, 0775, true);
      }
      if (is_dir($phpWordTemp) && class_exists(\PhpOffice\PhpWord\Settings::class)) {
        \PhpOffice\PhpWord\Settings::setTempDir($phpWordTemp);
      }
    } catch (\Throwable $e) {
      // Non-fatal: fall back to the library default.
    }

    // Dompdf's temp_dir also defaults to sys_get_temp_dir() (the non-writable
    // C:\Windows\TEMP under the web server). Override it at runtime so the
    // docx -> HTML -> PDF executed-contract flow can render.
    try {
      $dompdfTemp = storage_path('app/dompdf_temp');
      if (!is_dir($dompdfTemp)) {
        @mkdir($dompdfTemp, 0775, true);
      }
      if (is_dir($dompdfTemp)) {
        config(['dompdf.options.temp_dir' => $dompdfTemp]);
      }
    } catch (\Throwable $e) {
      // Non-fatal: fall back to the library default.
    }

    Vite::useStyleTagAttributes(function (?string $src, string $url, ?array $chunk, ?array $manifest) {
      if ($src !== null) {
        return [
          'class' => preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?core)-?.*/i", $src) ? 'template-customizer-core-css' :
                    (preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?theme)-?.*/i", $src) ? 'template-customizer-theme-css' : '')
        ];
      }
      return [];
    });
  }
}