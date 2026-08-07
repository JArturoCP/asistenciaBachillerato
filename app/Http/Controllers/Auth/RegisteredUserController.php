<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tutor;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'nombre' => ['nullable', 'string', 'max:100'],
            'apellido_paterno' => ['nullable', 'string', 'max:100'],
            'apellido_materno' => ['nullable', 'string', 'max:100'],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['nullable', 'in:parent,teacher,admin'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        if ($request->filled('nombre') && $request->filled('apellido_paterno')) {
            $nombre = $request->nombre;
            $apellidoPaterno = $request->apellido_paterno;
            $apellidoMaterno = $request->apellido_materno;
        } else {
            $nameParts = explode(' ', trim($request->name ?? 'Usuario'), 3);
            $nombre = $nameParts[0];
            $apellidoPaterno = $nameParts[1] ?? '';
            $apellidoMaterno = $nameParts[2] ?? null;
        }

        $role = $request->role ?? 'parent';

        $user = User::create([
            'nombre' => $nombre,
            'apellido_paterno' => $apellidoPaterno,
            'apellido_materno' => $apellidoMaterno,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $role,
            'phone' => $request->phone,
        ]);

        // If registering as a parent/tutor, automatically create Tutor profile
        if ($role === 'parent') {
            Tutor::create([
                'user_id' => $user->id,
                'parentesco' => 'padre',
                'telefono' => $request->phone,
                'correo_notificaciones' => $user->email,
                'alertas_correo_activadas' => true,
            ]);
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
