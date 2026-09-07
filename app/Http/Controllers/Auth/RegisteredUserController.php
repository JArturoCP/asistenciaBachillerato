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
use Illuminate\Validation\Rule;
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
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')->whereNull('deleted_at')],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['nullable', 'in:parent,teacher,admin,supervisor,director,subdirector,orientador,pedagogo,secretario_escolar'],
            'phone' => ['nullable', 'string', 'digits:10'],
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingrese una dirección de correo electrónico válida.',
            'email.unique' => 'Este correo electrónico ya se encuentra registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'password.min' => 'La contraseña debe contener al menos :min caracteres.',
            'role.in' => 'El rol seleccionado no es válido.',
            'phone.digits' => 'El número de teléfono debe contener 10 dígitos.',
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

        // Public registration requires admin approval (is_approved = false)
        $user = User::create([
            'nombre' => $nombre,
            'apellido_paterno' => $apellidoPaterno,
            'apellido_materno' => $apellidoMaterno,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $role,
            'phone' => $request->phone,
            'is_approved' => false,
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

        // Public registration stays pending approval, so DO NOT log in automatically
        return redirect()->route('login')->with('status', 'Solicitud de registro enviada correctamente. Su cuenta se encuentra pendiente de aprobación por un administrador.');
    }
}
