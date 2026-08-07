<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'No tiene permisos autorizados para acceder a este módulo.');
        }

        // Superadmin has full privileges on all modules
        if ($user->role === 'superadmin' || in_array($user->role, $roles, true)) {
            return $next($request);
        }

        abort(403, 'No tiene permisos autorizados para acceder a este módulo.');
    }
}
