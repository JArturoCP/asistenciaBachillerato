<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Grupo;
use App\Models\DocenteGrupo;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminTeacherController extends Controller
{
    public function index()
    {
        $teachers = User::whereIn('role', ['teacher', 'docente'])
            ->with(['docenteGrupos.grupo'])
            ->get();

        $groups = Grupo::orderBy('codigo_grupo')->get();

        AuditLog::log('READ', 'users', null, 'Consulta de docentes y asignaciones');

        return view('admin.teachers.index', compact('teachers', 'groups'));
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

    public function assignGroup(Request $request)
    {
        $validated = $request->validate([
            'teacher_id' => 'required|exists:users,id',
            'group_id' => 'required|exists:grupos,id',
            'subject_name' => 'required|string|max:100',
            'start_time' => 'nullable',
            'end_time' => 'nullable',
        ]);

        $assignment = DocenteGrupo::create([
            'docente_id' => $validated['teacher_id'],
            'grupo_id' => $validated['group_id'],
            'materia' => $validated['subject_name'],
            'hora_inicio' => $validated['start_time'] ?? null,
            'hora_fin' => $validated['end_time'] ?? null,
        ]);

        AuditLog::log('WRITE', 'docente_grupo', $assignment->id, "Asignación de materia {$assignment->materia} a docente");

        return redirect()->route('admin.teachers.index')->with('success', "Asignación registrada exitosamente.");
    }

    public function removeAssignment(DocenteGrupo $teacherGroup)
    {
        $subject = $teacherGroup->materia;
        $teacherGroup->delete();
        AuditLog::log('WRITE', 'docente_grupo', null, "Asignación eliminada: {$subject}");

        return redirect()->route('admin.teachers.index')->with('success', "Asignación de {$subject} eliminada.");
    }
}
