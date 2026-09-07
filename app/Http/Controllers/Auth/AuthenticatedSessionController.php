<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::user();

        // Check if account approval is pending
        if (! $user->is_approved) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', 'Acceso bloqueado: Su solicitud de registro se encuentra pendiente de aprobación por un administrador.');
        }

        $request->session()->regenerate();

        // Redirect based on role:
        // Superadmin & Admin -> /dashboard
        // Institutional Roles (Director, Subdirector, Orientador, Pedagogo, Secretario, Supervisor) -> /attendance/overview
        // Teacher -> /teacher/attendance
        // Parent -> /parent/dashboard
        if ($user->isParent()) {
            return redirect()->route('parent.dashboard');
        }

        if ($user->isTeacher() && !$user->isAdmin()) {
            return redirect()->route('teacher.attendance.index');
        }

        if ($user->canViewAllStudentAttendance() && !$user->isAdmin()) {
            return redirect()->route('attendance.overview.index');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
