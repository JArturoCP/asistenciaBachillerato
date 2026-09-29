<?php

namespace App\Providers;

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
        foreach (array_keys(config('access.permissions', [])) as $permission) {
            \Illuminate\Support\Facades\Gate::define($permission, function (\App\Models\User $user) use ($permission): bool {
                return $user->hasPermission($permission);
            });
        }

        //
    }
}
