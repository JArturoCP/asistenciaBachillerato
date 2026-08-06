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
        $guardians = Tutor::with(['user', 'estudiantes.user', 'consentimientos'])->get();
        $students = Estudiante::with(['user', 'grupo'])->where('is_active', true)->get();

        AuditLog::log('READ', 'tutores', null, 'Consulta de padres/tutores y consentimientos LFPDPPP');

        return view('admin.guardians.index', compact('guardians', 'students'));
    }

    public function storeGuardian(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'relationship' => 'required|in:padre,madre,tutor_legal',
            'password' => 'required|string|min:8',
        ]);

        $nameParts = explode(' ', trim($validated['name']), 3);
        $nombre = $nameParts[0];
        $apellidoPaterno = $nameParts[1] ?? '';
        $apellidoMaterno = $nameParts[2] ?? null;

        DB::transaction(function () use ($validated, $nombre, $apellidoPaterno, $apellidoMaterno, &$guardian) {
            $user = User::create([
                'nombre' => $nombre,
                'apellido_paterno' => $apellidoPaterno,
                'apellido_materno' => $apellidoMaterno,
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'role' => 'parent',
            ]);

            $guardian = Tutor::create([
                'user_id' => $user->id,
                'parentesco' => $validated['relationship'],
                'telefono' => $validated['phone'],
                'correo_notificaciones' => $validated['email'],
                'alertas_correo_activadas' => true,
            ]);
        });

        AuditLog::log('WRITE', 'tutores', $guardian->id, "Tutor/Padre registrado: {$validated['name']}");

        return redirect()->route('admin.guardians.index')->with('success', "Padre/Tutor registrado exitosamente.");
    }

    public function linkStudent(Request $request)
    {
        $validated = $request->validate([
            'guardian_id' => 'required|exists:tutores,id',
            'student_id' => 'required|exists:estudiantes,id',
            'is_primary_contact' => 'required|boolean',
            'consent_accepted' => 'required|accepted',
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
