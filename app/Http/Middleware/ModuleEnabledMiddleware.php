<?php

namespace App\Http\Middleware;

use App\Models\FacilityModuleAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
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
      abort(403, 'Facility scope is required for module access.');
    }

    $access = FacilityModuleAccess::query()
      ->where('facility_id', $facilityId)
      ->where('module_key', $moduleKey)
      ->first();

    if ($access && !$access->is_enabled) {
      abort(403, 'This module is disabled for your facility.');
    }

    return $next($request);
  }

  private function shouldEnforceForRole(string $role): bool
  {
    return in_array($role, ['Facility Administrator', 'Data Officer'], true);
  }
}

