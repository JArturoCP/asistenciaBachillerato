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
            'name' => 'required|string|max:150',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8',
        ]);

        $nameParts = explode(' ', trim($validated['name']), 3);
        $nombre = $nameParts[0];
        $apellidoPaterno = $nameParts[1] ?? '';
        $apellidoMaterno = $nameParts[2] ?? null;

        $teacher = User::create([
            'nombre' => $nombre,
            'apellido_paterno' => $apellidoPaterno,
            'apellido_materno' => $apellidoMaterno,
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'role' => 'teacher',
        ]);

        AuditLog::log('WRITE', 'users', $teacher->id, "Docente registrado: {$teacher->nombre_completo}");

        return redirect()->route('admin.teachers.index')->with('success', "Docente {$teacher->nombre_completo} registrado exitosamente.");
    }

    public function storeMateria(Request $request)
    {
        $validated = $request->validate([
            'clave' => 'required|string|max:20|unique:materias,clave',
            'nombre' => 'required|string|max:150',
            'semestre' => 'required|integer|min:1|max:6',
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

    public function removeAssignment(DocenteGrupo $teacherGroup)
    {
        $materiaNombre = $teacherGroup->materia?->nombre ?? 'Clase';
        $teacherGroup->delete();
        AuditLog::log('WRITE', 'docente_grupo', null, "Asignación eliminada: {$materiaNombre}");

        return redirect()->route('admin.teachers.index')->with('success', "Asignación de {$materiaNombre} eliminada.");
    }
}
