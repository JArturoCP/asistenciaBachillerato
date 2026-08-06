<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grupo;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AdminGroupController extends Controller
{
    public function index()
    {
        $groups = Grupo::withCount(['estudiantes', 'docenteGrupos'])->orderBy('codigo_grupo')->get();
        AuditLog::log('READ', 'grupos', null, 'Consulta de lista de grupos');

        return view('admin.groups.index', compact('groups'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'group_code' => 'required|string|unique:grupos,codigo_grupo',
            'grade' => 'required|string',
            'shift' => 'required|in:matutino,vespertino',
            'school_year' => 'required|string',
        ]);

        $group = Grupo::create([
            'codigo_grupo' => $validated['group_code'],
            'grado' => $validated['grade'],
            'turno' => $validated['shift'],
            'ciclo_escolar' => $validated['school_year'],
        ]);

        AuditLog::log('WRITE', 'grupos', $group->id, "Grupo creado: {$group->codigo_grupo}");

        return redirect()->route('admin.groups.index')->with('success', "Grupo {$group->codigo_grupo} creado exitosamente.");
    }

    public function update(Request $request, Grupo $group)
    {
        $validated = $request->validate([
            'group_code' => 'required|string|unique:grupos,codigo_grupo,' . $group->id,
            'grade' => 'required|string',
            'shift' => 'required|in:matutino,vespertino',
            'school_year' => 'required|string',
        ]);

        $group->update([
            'codigo_grupo' => $validated['group_code'],
            'grado' => $validated['grade'],
            'turno' => $validated['shift'],
            'ciclo_escolar' => $validated['school_year'],
        ]);

        AuditLog::log('WRITE', 'grupos', $group->id, "Grupo actualizado: {$group->codigo_grupo}");

        return redirect()->route('admin.groups.index')->with('success', "Grupo {$group->codigo_grupo} actualizado.");
    }

    public function destroy(Grupo $group)
    {
        $code = $group->codigo_grupo;
        $group->delete();
        AuditLog::log('WRITE', 'grupos', null, "Grupo eliminado: {$code}");

        return redirect()->route('admin.groups.index')->with('success', "Grupo {$code} eliminado.");
    }
}
