<?php

namespace App\Providers;

use App\Services\DashboardMetricsService;
use App\Services\PredictiveAnalyticsService;
use Illuminate\Support\Facades\Vite;
use App\Services\RiskAssessmentService;
use Illuminate\Support\ServiceProvider;
use App\Services\DiagnosticAssistantService;
use App\Services\EnhancedRiskAssessmentService;

class AppServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   */
  public function register(): void
  {
    // register
  }

  /**
   * Bootstrap any application services.
   */
  public function boot(): void
  {
    Vite::useStyleTagAttributes(function (?string $src, string $url, ?array $chunk, ?array $manifest) {
      if ($src !== null) {
        return [
          'class' => preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?core)-?.*/i", $src) ? 'template-customizer-core-css' : (preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?theme)-?.*/i", $src) ? 'template-customizer-theme-css' : '')
        ];
      }
      return [];
    });
  }
}
