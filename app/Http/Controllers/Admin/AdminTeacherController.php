<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\DocenteGrupo;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminTeacherController extends Controller
{
    public function index()
    {
        $teachers = User::whereIn('role', ['teacher', 'docente'])
            ->with(['docenteGrupos.grupo', 'docenteGrupos.materia'])
            ->get();

        $groups = Grupo::orderBy('codigo_grupo')->get();
        $materias = Materia::orderBy('nombre')->get();

        AuditLog::log('READ', 'users', null, 'Consulta de plantilla docente, materias y horarios');

        return view('admin.teachers.index', compact('teachers', 'groups', 'materias'));
    }

    public function storeTeacher(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido_paterno' => 'required|string|max:100',
            'apellido_materno' => 'nullable|string|max:100',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|digits:10',
            'password' => 'required|string|min:8',
        ], [
            'nombre.required' => 'El nombre del docente es obligatorio.',
            'apellido_paterno.required' => 'El apellido paterno del docente es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingrese una dirección de correo válida.',
            'email.unique' => 'El correo electrónico ya pertenece a otro usuario.',
            'password.required' => 'La contraseña inicial es obligatoria.',
            'password.min' => 'La contraseña debe contener al menos 8 caracteres.',
            'phone.digits' => 'El número de teléfono debe contener 10 dígitos.',
        ]);

        $teacher = User::create([
            'nombre' => $validated['nombre'],
            'apellido_paterno' => $validated['apellido_paterno'],
            'apellido_materno' => $validated['apellido_materno'] ?? null,
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'role' => 'teacher',
        ]);

        AuditLog::log('WRITE', 'users', $teacher->id, "Docente registrado: {$teacher->nombre_completo}");

        return redirect()->route('admin.teachers.index')->with('success', "Docente {$teacher->nombre_completo} registrado exitosamente.");
    }

    public function updateTeacher(Request $request, User $teacher)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido_paterno' => 'required|string|max:100',
            'apellido_materno' => 'nullable|string|max:100',
            'email' => 'required|email|unique:users,email,' . $teacher->id,
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8',
        ], [
            'nombre.required' => 'El nombre del docente es obligatorio.',
            'apellido_paterno.required' => 'El apellido paterno del docente es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingrese una dirección de correo válida.',
            'email.unique' => 'El correo electrónico ya pertenece a otro usuario.',
            'password.min' => 'La nueva contraseña debe contener al menos 8 caracteres.',
        ]);

        $updateData = [
            'nombre' => $validated['nombre'],
            'apellido_paterno' => $validated['apellido_paterno'],
            'apellido_materno' => $validated['apellido_materno'] ?? null,
            'email' => $validated['email'],
            'phone' => $validated['phone'],
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $teacher->update($updateData);

        AuditLog::log('WRITE', 'users', $teacher->id, "Docente actualizado: {$teacher->nombre_completo}");

        return redirect()->route('admin.teachers.index')->with('success', "Docente {$teacher->nombre_completo} actualizado correctamente.");
    }

    public function destroyTeacher(User $teacher)
    {
        $name = $teacher->nombre_completo;
        $teacher->delete();

        AuditLog::log('WRITE', 'users', null, "Docente eliminado (Soft Delete): {$name}");

        return redirect()->route('admin.teachers.index')->with('success', "Docente {$name} eliminado del sistema.");
    }

    public function storeMateria(Request $request)
    {
        $validated = $request->validate([
            'clave' => 'required|string|max:20|unique:materias,clave',
            'nombre' => 'required|string|max:150',
            'semestre' => 'required|integer|min:1|max:6',
        ], [
            'clave.required' => 'La clave de la materia es obligatoria.',
            'clave.unique' => 'La clave de materia ya existe en el catálogo.',
            'nombre.required' => 'El nombre de la asignatura es obligatorio.',
            'semestre.required' => 'El semestre es obligatorio.',
            'semestre.min' => 'El semestre debe ser al menos 1.',
            'semestre.max' => 'El semestre máximo es 6.',
        ]);

        $materia = Materia::create($validated);

        AuditLog::log('WRITE', 'materias', $materia->id, "Asignatura creada: {$materia->nombre} ({$materia->clave})");

        return redirect()->route('admin.teachers.index')->with('success', "Asignatura {$materia->nombre} registrada en el catálogo.");
    }

    public function assignGroup(Request $request)
    {
        $validated = $request->validate([
            'teacher_id' => 'required|exists:users,id',
            'materia_id' => 'required|exists:materias,id',
            'group_id' => 'required|exists:grupos,id',
            'dia_semana' => 'required|in:lunes,martes,miercoles,jueves,viernes,sabado',
            'start_time' => 'required',
            'end_time' => 'required',
            'aula' => 'nullable|string|max:50',
        ], [
            'teacher_id.required' => 'Debe seleccionar un docente.',
            'materia_id.required' => 'Debe seleccionar una asignatura.',
            'group_id.required' => 'Debe seleccionar un grupo.',
            'dia_semana.required' => 'El día de la semana es obligatorio.',
            'start_time.required' => 'La hora de inicio es obligatoria.',
            'end_time.required' => 'La hora de fin es obligatoria.',
        ]);

        $assignment = DocenteGrupo::create([
            'docente_id' => $validated['teacher_id'],
            'materia_id' => $validated['materia_id'],
            'grupo_id' => $validated['group_id'],
            'dia_semana' => $validated['dia_semana'],
            'hora_inicio' => $validated['start_time'],
            'hora_fin' => $validated['end_time'],
            'aula' => $validated['aula'] ?? null,
        ]);

        $materia = Materia::find($validated['materia_id']);
        AuditLog::log('WRITE', 'docente_grupo', $assignment->id, "Asignación de clase {$materia->nombre} a docente en horario {$validated['dia_semana']} {$validated['start_time']}-{$validated['end_time']}");

        return redirect()->route('admin.teachers.index')->with('success', "Horario y materia asignados exitosamente al docente.");
    }

    public function updateAssignment(Request $request, DocenteGrupo $teacherGroup)
    {
        $validated = $request->validate([
            'materia_id' => 'required|exists:materias,id',
            'group_id' => 'required|exists:grupos,id',
            'dia_semana' => 'required|in:lunes,martes,miercoles,jueves,viernes,sabado',
            'start_time' => 'required',
            'end_time' => 'required',
            'aula' => 'nullable|string|max:50',
        ], [
            'materia_id.required' => 'Debe seleccionar una asignatura.',
            'group_id.required' => 'Debe seleccionar un grupo.',
            'dia_semana.required' => 'El día de la semana es obligatorio.',
            'start_time.required' => 'La hora de inicio es obligatoria.',
            'end_time.required' => 'La hora de fin es obligatoria.',
        ]);

        $teacherGroup->update([
            'materia_id' => $validated['materia_id'],
            'grupo_id' => $validated['group_id'],
            'dia_semana' => $validated['dia_semana'],
            'hora_inicio' => $validated['start_time'],
            'hora_fin' => $validated['end_time'],
            'aula' => $validated['aula'] ?? null,
        ]);

        AuditLog::log('WRITE', 'docente_grupo', $teacherGroup->id, "Horario/materia asignado actualizado");

        return redirect()->route('admin.teachers.index')->with('success', "Horario de clase actualizado correctamente.");
    }

    public function removeAssignment(DocenteGrupo $teacherGroup)
    {
        $materiaNombre = $teacherGroup->materia?->nombre ?? 'Clase';
        $teacherGroup->delete();
        AuditLog::log('WRITE', 'docente_grupo', null, "Asignación eliminada (Soft Delete): {$materiaNombre}");

        return redirect()->route('admin.teachers.index')->with('success', "Asignación de {$materiaNombre} eliminada.");
    }
}
