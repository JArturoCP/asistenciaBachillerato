<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\Grupo;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AdminStudentController extends Controller
{
    public function index(Request $request)
    {
        $query = Estudiante::with(['user', 'grupo', 'tutores.user']);

        if ($request->filled('group_id')) {
            $query->where('grupo_id', $request->group_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('matricula', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('nombre', 'like', "%{$search}%")
                        ->orWhere('apellido_paterno', 'like', "%{$search}%")
                        ->orWhere('apellido_materno', 'like', "%{$search}%");
                  });
            });
        }

        $students = $query->paginate(15);
        $groups = Grupo::orderBy('codigo_grupo')->get();

        AuditLog::log('READ', 'estudiantes', null, 'Consulta de listado de estudiantes');

        return view('admin.students.index', compact('students', 'groups'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'matricula' => 'required|string|unique:estudiantes,matricula',
            'nombre' => 'required|string|max:100',
            'apellido_paterno' => 'required|string|max:100',
            'apellido_materno' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
            'group_id' => 'required|exists:grupos,id',
        ]);

        DB::transaction(function () use ($validated, &$student) {
            $user = User::create([
                'nombre' => $validated['nombre'],
                'apellido_paterno' => $validated['apellido_paterno'],
                'apellido_materno' => $validated['apellido_materno'] ?? null,
                'role' => 'student',
            ]);

            $student = Estudiante::create([
                'user_id' => $user->id,
                'matricula' => $validated['matricula'],
                'fecha_nacimiento' => $validated['birth_date'],
                'grupo_id' => $validated['group_id'],
            ]);
        });

        AuditLog::log('WRITE', 'estudiantes', $student->id, "Estudiante creado: {$student->nombre_completo} ({$student->matricula})");

        return redirect()->route('admin.students.index')->with('success', "Estudiante {$student->nombre_completo} registrado exitosamente.");
    }

    public function update(Request $request, Estudiante $student)
    {
        $validated = $request->validate([
            'matricula' => 'required|string|unique:estudiantes,matricula,' . $student->id,
            'nombre' => 'required|string|max:100',
            'apellido_paterno' => 'required|string|max:100',
            'apellido_materno' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
            'group_id' => 'required|exists:grupos,id',
            'is_active' => 'required|boolean',
        ]);

        DB::transaction(function () use ($student, $validated) {
            $student->user->update([
                'nombre' => $validated['nombre'],
                'apellido_paterno' => $validated['apellido_paterno'],
                'apellido_materno' => $validated['apellido_materno'] ?? null,
            ]);

            $student->update([
                'matricula' => $validated['matricula'],
                'fecha_nacimiento' => $validated['birth_date'],
                'grupo_id' => $validated['group_id'],
                'is_active' => $validated['is_active'],
            ]);
        });

        AuditLog::log('WRITE', 'estudiantes', $student->id, "Estudiante actualizado: {$student->nombre_completo}");

        return redirect()->route('admin.students.index')->with('success', "Estudiante {$student->nombre_completo} actualizado.");
    }

    public function destroy(Estudiante $student)
    {
        $name = $student->nombre_completo;
        $user = $student->user;
        $student->delete();
        if ($user) {
            $user->delete();
        }

        AuditLog::log('WRITE', 'estudiantes', null, "Estudiante eliminado (Soft Delete): {$name}");

        return redirect()->route('admin.students.index')->with('success', "Estudiante {$name} eliminado.");
    }

    public function showCredential(Estudiante $student)
    {
        $student->load(['user', 'grupo', 'tutores']);
        
        $qrCodeSvg = QrCode::size(180)->margin(1)->generate($student->uuid);
        
        AuditLog::log('READ', 'estudiantes', $student->id, "Generación de credencial QR para: {$student->nombre_completo}");

        return view('admin.students.credential', compact('student', 'qrCodeSvg'));
    }
}
