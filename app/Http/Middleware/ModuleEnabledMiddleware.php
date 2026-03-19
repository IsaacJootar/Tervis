<?php

namespace App\Http\Middleware;

use App\Models\FacilityModuleAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ModuleEnabledMiddleware
{
  public function handle(Request $request, Closure $next, string $moduleKey): Response
  {
    $user = Auth::user();
    if (!$user) {
      return redirect()->route('login');
    }

    $moduleKey = trim($moduleKey);
    if ($moduleKey === '') {
      return $next($request);
    }

    if (!Schema::hasTable('facility_module_accesses')) {
      return $next($request);
    }

    if (!$this->shouldEnforceForRole((string) ($user->role ?? ''))) {
      return $next($request);
    }

    $facilityId = (int) ($user->facility_id ?? 0);
    if ($facilityId <= 0) {
      return $this->moduleDisabledResponse(
        $request,
        'Facility scope is required to open this module.',
        $moduleKey,
        null
      );
    }

    $access = FacilityModuleAccess::query()
      ->where('facility_id', $facilityId)
      ->where('module_key', $moduleKey)
      ->first();

    if ($access && !$access->is_enabled) {
      return $this->moduleDisabledResponse(
        $request,
        'This module is currently disabled for your facility.',
        $moduleKey,
        $access->module_label
      );
    }

    return $next($request);
  }

  private function shouldEnforceForRole(string $role): bool
  {
    return in_array($role, ['Facility Administrator', 'Data Officer'], true);
  }

  private function moduleDisabledResponse(Request $request, string $message, string $moduleKey, ?string $moduleLabel): Response
  {
    $resolvedLabel = trim((string) $moduleLabel) !== ''
      ? trim((string) $moduleLabel)
      : Str::of($moduleKey)->replace(['_', '-'], ' ')->title()->value();

    if ($request->expectsJson() || $request->wantsJson()) {
      return response()->json([
        'message' => $message,
        'module_key' => $moduleKey,
        'module_label' => $resolvedLabel,
      ], 403);
    }

    $user = Auth::user();
    $facilityName = trim((string) optional($user?->facility)->name);
    $fallbackUrl = $this->resolveFallbackUrlForRole((string) ($user->role ?? ''));

    return response()->view('errors.module-disabled', [
      'title' => 'Module Unavailable',
      'message' => $message,
      'moduleLabel' => $resolvedLabel,
      'facilityName' => $facilityName !== '' ? $facilityName : 'Your Facility',
      'fallbackUrl' => $fallbackUrl,
    ], 403);
  }

  private function resolveFallbackUrlForRole(string $role): string
  {
    $roleRoutes = [
      'Facility Administrator' => 'facility-admin-dashboard',
      'Data Officer' => 'patient-workspace',
    ];

    $routeName = $roleRoutes[$role] ?? null;
    if ($routeName && Route::has($routeName)) {
      try {
        return route($routeName);
      } catch (\Throwable $e) {
        // Fall through to previous url.
      }
    }

    return url()->previous();
  }
}
