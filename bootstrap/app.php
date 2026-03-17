<?php

use App\Http\Middleware\UserRoleMiddleware;
use App\Http\Middleware\ModuleEnabledMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
  ->withRouting(
    web: __DIR__ . '/../routes/web.php',
    commands: __DIR__ . '/../routes/console.php',
    health: '/up',
  )
  ->withMiddleware(function (Middleware $middleware) {
    // MaternalRecordPro route middleware
    $middleware->alias([
      'role.redirect' => UserRoleMiddleware::class,
      'module.enabled' => ModuleEnabledMiddleware::class,
    ]);
  })
  ->withExceptions(function (Exceptions $exceptions) {
    //
  })->create();
