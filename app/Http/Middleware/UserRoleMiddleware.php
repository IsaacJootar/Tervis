<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class UserRoleMiddleware
{
  /**
   * Handle an incoming request.
   */
  public function handle(Request $request, Closure $next): Response
  {
    $user = Auth::user();

    // Safety fallback (auth middleware should already handle this)
    if (!$user) {
      return redirect()->route('login');
    }

    $role = $user->role;

    /**
     * Role → Allowed URL prefixes → Default dashboard route
     */
    $roleAccess = [

      'Central Admin' => [
        'prefixes' => ['central', 'core'],
        'index'     => 'central-admin-dashboard',
      ],

      'Central Administrator' => [
        'prefixes' => ['central', 'core'],
        'index'     => 'central-admin-dashboard',
      ],

      'State Data Administrator' => [
        'prefixes' => ['core', 'analytics'],
        'index'     => 'state-officer-dashboard',
      ],

      'LGA Officer' => [
        'prefixes' => ['core', 'analytics'],
        'index'     => 'lga-officer-dashboard',
      ],

      'Facility Administrator' => [
        'prefixes' => ['core', 'analytics'],
        'index'     => 'facility-admin-dashboard',
      ],

      'Data Officer' => [
        'prefixes' => ['registers', 'workspaces'],
        'index'     => 'patient-workspace',
      ],

      'Verification Officer' => [
        'prefixes' => ['avo'],
        'index'     => 'din-activations',
      ],

      'Patient' => [
        'prefixes' => ['patient'],
        'index'     => 'patient-dashboard',
      ],
    ];

    // Unknown role → logout
    if (!isset($roleAccess[$role])) {
      Auth::logout();
      return redirect()->route('login');
    }

    // Check if request URL matches any allowed prefix
    foreach ($roleAccess[$role]['prefixes'] as $prefix) {
      if (
        $request->is($prefix) ||
        $request->is($prefix . '/*')
      ) {
        return $next($request);
      }
    }

    // Not allowed → redirect to role index
    return redirect()->route($roleAccess[$role]['index']);
  }
}
