<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\Grupo;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AdminStudentController extends Controller
{
    public function index(Request $request)
    {
        $query = Estudiante::with(['user', 'grupo', 'tutores.user'])
            ->join('users', 'estudiantes.user_id', '=', 'users.id')
            ->orderBy('users.apellido_paterno', 'asc')
            ->orderBy('users.apellido_materno', 'asc')
            ->orderBy('users.nombre', 'asc')
            ->select('estudiantes.*');

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
        $nextMatricula = Estudiante::generateNextMatricula();

        AuditLog::log('READ', 'estudiantes', null, 'Consulta de listado de estudiantes');

        return view('admin.students.index', compact('students', 'groups', 'nextMatricula'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido_paterno' => 'required|string|max:100',
            'apellido_materno' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
            'group_id' => 'required|exists:grupos,id',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ], [
            'nombre.required' => 'El nombre del estudiante es obligatorio.',
            'apellido_paterno.required' => 'El apellido paterno del estudiante es obligatorio.',
            'group_id.required' => 'Debe asignar un grupo académico al estudiante.',
            'group_id.exists' => 'El grupo seleccionado no existe.',
            'foto.image' => 'La fotografía debe ser una imagen en formato JPG, PNG o WEBP.',
            'foto.max' => 'La fotografía no debe superar los 2MB de peso.',
        ]);

        $matricula = Estudiante::generateNextMatricula();

        $photoPath = null;
        if ($request->hasFile('foto')) {
            $photoPath = $request->file('foto')->store('estudiantes/fotos', 'public');
        }

        DB::transaction(function () use ($validated, $matricula, $photoPath, &$student) {
            $user = User::create([
                'nombre' => $validated['nombre'],
                'apellido_paterno' => $validated['apellido_paterno'],
                'apellido_materno' => $validated['apellido_materno'] ?? null,
                'role' => 'student',
                'is_approved' => true,
            ]);

            $student = Estudiante::create([
                'user_id' => $user->id,
                'matricula' => $matricula,
                'foto' => $photoPath,
                'fecha_nacimiento' => $validated['birth_date'],
                'grupo_id' => $validated['group_id'],
            ]);
        });

        AuditLog::log('WRITE', 'estudiantes', $student->id, "Estudiante creado: {$student->nombre_completo} ({$student->matricula})");

        return redirect()->route('admin.students.index')->with('success', "Estudiante {$student->nombre_completo} registrado exitosamente con la matrícula {$student->matricula}.");
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
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ], [
            'matricula.required' => 'La matrícula estudiantil es obligatoria.',
            'matricula.unique' => 'La matrícula ingresada ya pertenece a otro estudiante.',
            'nombre.required' => 'El nombre del estudiante es obligatorio.',
            'apellido_paterno.required' => 'El apellido paterno del estudiante es obligatorio.',
            'group_id.required' => 'Debe asignar un grupo académico al estudiante.',
            'group_id.exists' => 'El grupo seleccionado no existe.',
            'foto.image' => 'La fotografía debe ser una imagen en formato JPG, PNG o WEBP.',
            'foto.max' => 'La fotografía no debe superar los 2MB de peso.',
        ]);

        $photoPath = $student->foto;
        if ($request->hasFile('foto')) {
            if ($student->foto && Storage::disk('public')->exists($student->foto)) {
                Storage::disk('public')->delete($student->foto);
            }
            $photoPath = $request->file('foto')->store('estudiantes/fotos', 'public');
        }

        DB::transaction(function () use ($student, $validated, $photoPath) {
            $student->user->update([
                'nombre' => $validated['nombre'],
                'apellido_paterno' => $validated['apellido_paterno'],
                'apellido_materno' => $validated['apellido_materno'] ?? null,
            ]);

            $student->update([
                'matricula' => $validated['matricula'],
                'foto' => $photoPath,
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
        
        $qrCodeSvg = QrCode::size(85)->margin(1)->generate($student->uuid);
        
        AuditLog::log('READ', 'estudiantes', $student->id, "Generación de credencial QR para: {$student->nombre_completo}");

        return view('admin.students.credential', compact('student', 'qrCodeSvg'));
    }
}
