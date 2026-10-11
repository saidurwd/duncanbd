<?php

use App\Domain\Cms\RedirectManager;
use App\Http\Middleware\EnsureAdminAccess;
use App\Http\Middleware\ProtectFormsFromSpam;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Csp\AddCspHeaders;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\ResponseCache\Middlewares\CacheResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->prefix(config('admin.path'))
                ->name('admin.')
                ->group(base_path('routes/admin.php'));

            // Catch-all page resolver must be registered last so it never shadows real routes.
            Route::group([], base_path('routes/site.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [SecurityHeaders::class, AddCspHeaders::class]);

        // Public pages: no session or cookies, so full responses are safe to cache for all guests.
        // Headers are added outside the cache layer so they are never served stale.
        $middleware->group('site', [SecurityHeaders::class, AddCspHeaders::class, CacheResponse::class]);

        $middleware->alias([
            'admin.access' => EnsureAdminAccess::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'spam' => ProtectFormsFromSpam::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Legacy-URL redirects and 404 logging for the public site.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
                return null;
            }

            if ($request->is(config('admin.path'), config('admin.path').'/*')) {
                return null;
            }

            $manager = app(RedirectManager::class);

            if ($redirect = $manager->find($request->path())) {
                $manager->recordHit($redirect);

                return redirect($redirect->to_url, $redirect->status_code);
            }

            $manager->recordNotFound($request->path(), $request->headers->get('referer'));

            return null;
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
