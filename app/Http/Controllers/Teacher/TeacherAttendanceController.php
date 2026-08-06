<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Grupo;
use App\Models\Estudiante;
use App\Models\Asistencia;
use App\Models\DocenteGrupo;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeacherAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));

        // 1. Get assigned groups for teacher (or all groups if admin)
        if ($user->isAdmin()) {
            $assignedGroupIds = Grupo::pluck('id')->toArray();
        } else {
            $assignedGroupIds = DocenteGrupo::where('docente_id', $user->id)
                ->pluck('grupo_id')
                ->unique()
                ->toArray();
        }

        $groups = Grupo::whereIn('id', $assignedGroupIds)->orderBy('codigo_grupo')->get();

        // Selected group (default to first assigned group)
        $selectedGroupId = $request->input('group_id', $groups->first()?->id);
        $selectedGroup = $groups->firstWhere('id', $selectedGroupId);

        $attendanceList = collect();
        $metrics = ['total' => 0, 'presentes' => 0, 'retardos' => 0, 'faltas' => 0, 'justificados' => 0];

        if ($selectedGroup) {
            // Get all students in selected group
            $students = Estudiante::with('user')
                ->where('grupo_id', $selectedGroup->id)
                ->where('is_active', true)
                ->get()
                ->sortBy(function ($s) {
                    return $s->user->apellido_paterno;
                });

            // Get attendances for selected date
            $attendancesToday = Asistencia::whereIn('estudiante_id', $students->pluck('id'))
                ->whereDate('fecha', $date)
                ->get()
                ->keyBy('estudiante_id');

            foreach ($students as $student) {
                $att = $attendancesToday->get($student->id);
                $status = $att ? $att->estado : 'falta';

                $attendanceList->push((object)[
                    'student' => $student,
                    'attendance' => $att,
                    'status' => $status,
                    'check_in_time' => $att?->hora_entrada,
                    'check_out_time' => $att?->hora_salida,
                    'notes' => $att?->observaciones,
                ]);

                $metrics['total']++;
                if (isset($metrics[$status])) {
                    $metrics[$status]++;
                }
            }
        }

        AuditLog::log('READ', 'asistencias', null, "Docente consulta asistencia de grupo ID {$selectedGroupId} para fecha {$date}");

        return view('teacher.attendance.index', compact(
            'groups',
            'selectedGroup',
            'selectedGroupId',
            'date',
            'attendanceList',
            'metrics'
        ));
    }

    public function updateStatus(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:estudiantes,id',
            'date' => 'required|date',
            'status' => 'required|in:presente,retardo,falta,justificado',
            'notes' => 'nullable|string|max:255',
        ]);

        $attendance = Asistencia::updateOrCreate(
            [
                'estudiante_id' => $validated['student_id'],
                'fecha' => $validated['date'],
            ],
            [
                'estado' => $validated['status'],
                'observaciones' => $validated['notes'],
                'metodo_escaneo' => 'manual_admin',
                'hora_entrada' => $validated['status'] !== 'falta' ? ($request->check_in_time ?? Carbon::now()->format('H:i:s')) : null,
            ]
        );

        AuditLog::log('WRITE', 'asistencias', $attendance->id, "Justificación/Modificación manual de asistencia para alumno ID {$validated['student_id']}");

        return redirect()->back()->with('success', 'Asistencia actualizada correctamente.');
    }

    public function exportCsv(Request $request)
    {
        $groupId = $request->input('group_id');
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));

        $group = Grupo::findOrFail($groupId);
        $students = Estudiante::with('user')->where('grupo_id', $group->id)->get()->sortBy(function($s) {
            return $s->user->apellido_paterno;
        });
        $attendances = Asistencia::whereIn('estudiante_id', $students->pluck('id'))
            ->whereDate('fecha', $date)
            ->get()
            ->keyBy('estudiante_id');

        $filename = "Asistencia_Grupo_{$group->codigo_grupo}_{$date}.csv";

        AuditLog::log('EXPORT', 'asistencias', null, "Exportación CSV de asistencia para grupo {$group->codigo_grupo}");

        $response = new StreamedResponse(function () use ($students, $attendances, $date) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Matricula', 'Nombre Alumno', 'Fecha', 'Hora Entrada', 'Hora Salida', 'Estado', 'Notas']);

            foreach ($students as $student) {
                $att = $attendances->get($student->id);
                fputcsv($handle, [
                    $student->matricula,
                    $student->nombre_completo,
                    $date,
                    $att?->hora_entrada ?? 'N/A',
                    $att?->hora_salida ?? 'N/A',
                    strtoupper($att?->estado ?? 'falta'),
                    $att?->observaciones ?? '',
                ]);
            }
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', "attachment; filename=\"{$filename}\"");

        return $response;
    }
}
