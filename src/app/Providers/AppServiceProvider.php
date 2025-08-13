<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Spatie\Permission\Middleware\RoleMiddleware;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot()
    {
        // Middleware alias (ya lo tenías)
        Route::aliasMiddleware('role', RoleMiddleware::class);

        // 👇 Macro escalable para tus recursos de contenido
        Route::macro('contentRoutes', function (string $prefix, string $controller) {
            Route::prefix($prefix)->group(function () use ($controller) {
                Route::get('/', [$controller, 'index']);
                Route::middleware('jwt.auth')->get('/own', [$controller, 'own']);
            });

            Route::prefix(Str::singular($prefix))->group(function () use ($controller) {
                Route::middleware('jwt.auth')->group(function () use ($controller) {
                    Route::post('/{id}/upload-cover-image', [$controller, 'uploadCoverImage']);
                    Route::post('/{id}/upload-file', [$controller, 'uploadFile']);

                    Route::post('/', [$controller, 'store']);
                    Route::put('/{id}/toggle-status', [$controller, 'toggleStatus']);
                    Route::put('/{id}', [$controller, 'update']);
                    Route::delete('/{id}', [$controller, 'destroy']);
                });

                Route::get('/{id}', [$controller, 'show']);
            });
        });
    }
}
