<?php

namespace App\Http\Middleware;

use App\Services\Security\RolePermissionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RolePermissionMiddleware
{
  public function handle(Request $request, Closure $next, string $permissionKey): Response
  {
    $user = Auth::user();
    if (!$user) {
      return redirect()->route('login');
    }

    $permissionKey = trim($permissionKey);
    if ($permissionKey === '') {
      return $next($request);
    }

    if (!RolePermissionService::can($user, $permissionKey)) {
      abort(403, 'Unauthorized: permission denied.');
    }

    return $next($request);
  }
}

