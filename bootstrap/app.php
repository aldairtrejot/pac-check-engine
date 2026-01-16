<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // ✅ Aliases para middlewares personalizados (se usan en routes/web.php)
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,

            // ✅ Si lo usas en rutas (recomendado)
            'require.state' => \App\Http\Middleware\RequireStateForOperatives::class,

            // ⚠️ OJO: EnsureRole actualmente NO hace nada (no lo recomiendo usar así)
            // 'ensure.role' => \App\Http\Middleware\EnsureRole::class,
        ]);

        // ✅ Cuando un usuario NO está autenticado y entra a rutas con auth,
        // Laravel lo redirige a esta ruta:
        $middleware->redirectGuestsTo(fn () => route('login'));

        // (Opcional) si quieres que un usuario ya logueado no vuelva al login,
        // lo puedes manejar en routes (guest middleware) o aquí:
        // $middleware->redirectUsersTo(fn () => route('pac'));

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
