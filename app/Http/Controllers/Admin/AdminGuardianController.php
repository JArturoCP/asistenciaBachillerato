<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tutor;
use App\Models\Estudiante;
use App\Models\Consentimiento;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminGuardianController extends Controller
{
    public function index()
    {
        $guardians = Tutor::whereHas('user', function ($q) {
            $q->where('is_approved', true);
        })->with(['user', 'estudiantes.user', 'consentimientos'])->get();
        $students = Estudiante::with(['user', 'grupo'])->where('is_active', true)->get();

        AuditLog::log('READ', 'tutores', null, 'Consulta de padres/tutores y consentimientos LFPDPPP');

        return view('admin.guardians.index', compact('guardians', 'students'));
    }

    public function storeGuardian(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido_paterno' => 'required|string|max:100',
            'apellido_materno' => 'nullable|string|max:100',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|digits:10',
            'relationship' => 'required|in:padre,madre,tutor_legal',
            'password' => 'required|string|min:8',
        ], [
            'nombre.required' => 'El nombre del padre o tutor es obligatorio.',
            'apellido_paterno.required' => 'El apellido paterno es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingrese una dirección de correo válida.',
            'email.unique' => 'El correo electrónico ya pertenece a otro usuario.',
            'relationship.required' => 'Debe seleccionar el parentesco.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe contener al menos 8 caracteres.',
            'phone.digits' => 'El número de teléfono debe contener 10 dígitos.',
        ]);

        DB::transaction(function () use ($validated, &$guardian) {
            $user = User::create([
                'nombre' => $validated['nombre'],
                'apellido_paterno' => $validated['apellido_paterno'],
                'apellido_materno' => $validated['apellido_materno'] ?? null,
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'role' => 'parent',
                'is_approved' => true,
            ]);

            $guardian = Tutor::create([
                'user_id' => $user->id,
                'parentesco' => $validated['relationship'],
                'telefono' => $validated['phone'],
                'correo_notificaciones' => $validated['email'],
                'alertas_correo_activadas' => true,
            ]);
        });

        AuditLog::log('WRITE', 'tutores', $guardian->id, "Tutor/Padre registrado: {$guardian->user->nombre_completo}");

        return redirect()->route('admin.guardians.index')->with('success', "Padre/Tutor registrado exitosamente.");
    }

    public function updateGuardian(Request $request, Tutor $guardian)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido_paterno' => 'required|string|max:100',
            'apellido_materno' => 'nullable|string|max:100',
            'email' => 'required|email|unique:users,email,' . $guardian->user_id,
            'phone' => 'nullable|string|digits:10',
            'relationship' => 'required|in:padre,madre,tutor_legal',
            'password' => 'nullable|string|min:8',
        ], [
            'nombre.required' => 'El nombre del padre o tutor es obligatorio.',
            'apellido_paterno.required' => 'El apellido paterno es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingrese una dirección de correo válida.',
            'email.unique' => 'El correo electrónico ya pertenece a otro usuario.',
            'relationship.required' => 'Debe seleccionar el parentesco.',
            'password.min' => 'La contraseña debe contener al menos 8 caracteres.',
            'phone.digits' => 'El número de teléfono debe contener 10 dígitos.',
        ]);

        DB::transaction(function () use ($guardian, $validated) {
            $userData = [
                'nombre' => $validated['nombre'],
                'apellido_paterno' => $validated['apellido_paterno'],
                'apellido_materno' => $validated['apellido_materno'] ?? null,
                'email' => $validated['email'],
                'phone' => $validated['phone'],
            ];

            if (!empty($validated['password'])) {
                $userData['password'] = Hash::make($validated['password']);
            }

            $guardian->user->update($userData);

            $guardian->update([
                'parentesco' => $validated['relationship'],
                'telefono' => $validated['phone'],
                'correo_notificaciones' => $validated['email'],
            ]);
        });

        AuditLog::log('WRITE', 'tutores', $guardian->id, "Tutor/Padre actualizado: {$guardian->user->nombre_completo}");

        return redirect()->route('admin.guardians.index')->with('success', "Datos del Padre/Tutor actualizados correctamente.");
    }

    public function destroyGuardian(Tutor $guardian)
    {
        $name = $guardian->user->nombre_completo;
        $user = $guardian->user;
        $guardian->delete();
        if ($user) {
            $user->delete();
        }

        AuditLog::log('WRITE', 'tutores', null, "Tutor eliminado (Soft Delete): {$name}");

        return redirect()->route('admin.guardians.index')->with('success', "Padre/Tutor {$name} eliminado del sistema.");
    }

    public function linkStudent(Request $request)
    {
        $validated = $request->validate([
            'guardian_id' => 'required|exists:tutores,id',
            'student_id' => 'required|exists:estudiantes,id',
            'is_primary_contact' => 'required|boolean',
            'consent_accepted' => 'required|accepted',
        ], [
            'guardian_id.required' => 'Debe seleccionar un padre o tutor.',
            'student_id.required' => 'Debe seleccionar un estudiante.',
            'consent_accepted.required' => 'Debe marcar la aceptación del aviso de privacidad LFPDPPP.',
            'consent_accepted.accepted' => 'Es obligatorio aceptar el consentimiento LFPDPPP para registrar la vinculación.',
        ]);

        $guardian = Tutor::findOrFail($validated['guardian_id']);
        $student = Estudiante::findOrFail($validated['student_id']);

        // Attach student to guardian
        $guardian->estudiantes()->syncWithoutDetaching([
            $student->id => [
                'es_contacto_principal' => $validated['is_primary_contact'],
                'fecha_verificacion' => now(),
            ]
        ]);

        // Register LFPDPPP Consent record
        Consentimiento::create([
            'tutor_id' => $guardian->id,
            'estudiante_id' => $student->id,
            'version_consentimiento' => 'v1.0-LFPDPPP',
            'aceptado' => true,
            'ip_address' => $request->ip(),
            'fecha_otorgado' => now(),
        ]);

        AuditLog::log('WRITE', 'consentimientos', null, "Consentimiento LFPDPPP registrado para tutor ID {$guardian->id} y estudiante {$student->nombre_completo}");

        return redirect()->route('admin.guardians.index')->with('success', "Vinculación y Consentimiento LFPDPPP registrados correctamente.");
    }
}
