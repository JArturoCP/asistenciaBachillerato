<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // El proyecto usa Bootstrap 5; evita los SVG gigantes de la
        // paginación Tailwind predeterminada de Laravel.
        Paginator::useBootstrapFive();

        // Registrar todos los permisos funcionales definidos para el
        // módulo de roles y permisos.
        foreach (array_keys(config('access.permissions', [])) as $permission) {
            Gate::define($permission, function (User $user) use ($permission): bool {
                return $user->hasPermission($permission);
            });
        }
    }
}
