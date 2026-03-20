<?php

namespace App\Providers;

use App\Models\Activity;
use App\Models\Registrations\DinActivation;
use App\Observers\ActivityObserver;
use App\Observers\DinActivationObserver;
use App\Services\DashboardMetricsService;
use App\Services\PredictiveAnalyticsService;
use App\Services\Ui\NavbarContextService;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
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
    Activity::observe(ActivityObserver::class);
    DinActivation::observe(DinActivationObserver::class);

    Vite::useStyleTagAttributes(function (?string $src, string $url, ?array $chunk, ?array $manifest) {
      if ($src !== null) {
        return [
          'class' => preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?core)-?.*/i", $src) ? 'template-customizer-core-css' : (preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?theme)-?.*/i", $src) ? 'template-customizer-theme-css' : '')
        ];
      }
      return [];
    });

    View::composer('layouts.sections.navbar.*Navbar-partial', function ($view): void {
      $user = Auth::user();
      $context = app(NavbarContextService::class)->build($user);
      $view->with('navbarContext', $context);
    });
  }
}
