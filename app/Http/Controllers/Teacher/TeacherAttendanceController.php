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

        // 1. Get assigned class schedules for teacher (or all if admin)
        $query = DocenteGrupo::with(['grupo', 'materia', 'docente']);

        if (!$user->isAdmin()) {
            $query->where('docente_id', $user->id);
        }

        $allSchedules = $query->get();

        // 2. Date selected by user (defaults to today)
        $refDateInput = $request->input('date');
        $carbonDate = $refDateInput ? Carbon::parse($refDateInput, 'America/Mexico_City') : Carbon::today('America/Mexico_City');
        $date = $carbonDate->format('Y-m-d');

        // Map Carbon dayOfWeek (0 = Domingo, 1 = Lunes, ...) to Spanish string as stored in DB
        $daysMap = [
            1 => 'lunes',
            2 => 'martes',
            3 => 'miercoles',
            4 => 'jueves',
            5 => 'viernes',
            6 => 'sabado',
            0 => 'domingo',
        ];

        $dayOfWeekName = $daysMap[$carbonDate->dayOfWeek] ?? 'lunes';

        // 3. Filter schedules for the day of the week of the selected date
        $classSchedules = $allSchedules->filter(function ($sched) use ($dayOfWeekName) {
            return strtolower($sched->dia_semana) === $dayOfWeekName;
        })->sort(function ($a, $b) {
            $groupComp = strcmp($a->grupo->codigo_grupo ?? '', $b->grupo->codigo_grupo ?? '');
            if ($groupComp !== 0) return $groupComp;
            return strcmp($a->hora_inicio ?? '', $b->hora_inicio ?? '');
        });

        // 4. Selected schedule (default to first assigned class for that day)
        $selectedScheduleId = $request->input('schedule_id', $classSchedules->first()?->id);
        $selectedSchedule = $classSchedules->firstWhere('id', $selectedScheduleId);

        if (!$selectedSchedule && $classSchedules->isNotEmpty()) {
            $selectedSchedule = $classSchedules->first();
            $selectedScheduleId = $selectedSchedule->id;
        }

        $selectedGroup = $selectedSchedule?->grupo;
        $attendanceList = collect();
        $metrics = ['total' => 0, 'presentes' => 0, 'retardos' => 0, 'faltas' => 0, 'justificados' => 0];

        if ($selectedSchedule && $selectedGroup) {
            // Get all active students in group ordered alphabetically by Apellido Paterno, Materno, Nombre
            $students = Estudiante::with('user')
                ->where('grupo_id', $selectedGroup->id)
                ->where('is_active', true)
                ->get()
                ->sort(function ($a, $b) {
                    $comp = strnatcasecmp($a->user->apellido_paterno ?? '', $b->user->apellido_paterno ?? '');
                    if ($comp !== 0) return $comp;
                    $comp = strnatcasecmp($a->user->apellido_materno ?? '', $b->user->apellido_materno ?? '');
                    if ($comp !== 0) return $comp;
                    return strnatcasecmp($a->user->nombre ?? '', $b->user->nombre ?? '');
                });

            // Get attendances ONLY for the calculated date corresponding to this class schedule's day of week
            $attendancesToday = Asistencia::whereIn('estudiante_id', $students->pluck('id'))
                ->whereDate('fecha', $date)
                ->get()
                ->keyBy('estudiante_id');

            $statusMap = [
                'presente' => 'presentes',
                'retardo' => 'retardos',
                'falta' => 'faltas',
                'justificado' => 'justificados',
            ];

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
                $metricKey = $statusMap[$status] ?? 'faltas';
                $metrics[$metricKey]++;
            }
        }

        AuditLog::log('READ', 'asistencias', null, "Docente consulta asistencia de clase ID {$selectedScheduleId} para fecha {$date}");

        return view('teacher.attendance.index', compact(
            'classSchedules',
            'selectedSchedule',
            'selectedScheduleId',
            'selectedGroup',
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
        ], [
            'student_id.required' => 'Debe seleccionar un estudiante.',
            'student_id.exists' => 'El estudiante seleccionado no existe.',
            'date.required' => 'La fecha es obligatoria.',
            'status.required' => 'El estado de asistencia es obligatorio.',
            'status.in' => 'El estado de asistencia seleccionado no es válido.',
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
                'hora_entrada' => $validated['status'] !== 'falta' ? ($request->check_in_time ?? Carbon::now('America/Mexico_City')->format('H:i:s')) : null,
            ]
        );

        AuditLog::log('WRITE', 'asistencias', $attendance->id, "Justificación/Modificación manual de asistencia para alumno ID {$validated['student_id']}");

        return redirect()->back()->with('success', 'Asistencia actualizada correctamente.');
    }

    public function exportCsv(Request $request)
    {
        $scheduleId = $request->input('schedule_id');
        $groupId = $request->input('group_id');
        $dateInput = $request->input('date');

        $refDate = $dateInput ? Carbon::parse($dateInput, 'America/Mexico_City') : Carbon::today('America/Mexico_City');
        $date = $refDate->format('Y-m-d');

        if ($scheduleId) {
            $schedule = DocenteGrupo::with(['grupo', 'materia'])->findOrFail($scheduleId);
            $group = $schedule->grupo;
            $materiaClave = $schedule->materia?->clave ?? 'MATERIA';
        } else {
            $group = Grupo::findOrFail($groupId);
            $materiaClave = 'GRUPO';
        }

        $students = Estudiante::with('user')
            ->where('grupo_id', $group->id)
            ->get()
            ->sort(function ($a, $b) {
                $comp = strnatcasecmp($a->user->apellido_paterno ?? '', $b->user->apellido_paterno ?? '');
                if ($comp !== 0) return $comp;
                $comp = strnatcasecmp($a->user->apellido_materno ?? '', $b->user->apellido_materno ?? '');
                if ($comp !== 0) return $comp;
                return strnatcasecmp($a->user->nombre ?? '', $b->user->nombre ?? '');
            });

        $attendances = Asistencia::whereIn('estudiante_id', $students->pluck('id'))
            ->whereDate('fecha', $date)
            ->get()
            ->keyBy('estudiante_id');

        $filename = "Asistencia_{$materiaClave}_Grupo_{$group->codigo_grupo}_{$date}.csv";

        AuditLog::log('EXPORT', 'asistencias', null, "Exportación CSV de asistencia para grupo {$group->codigo_grupo}");

        $response = new StreamedResponse(function () use ($students, $attendances, $date) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Microsoft Excel compatibility
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Matrícula', 'Nombre Alumno', 'Fecha', 'Hora Entrada', 'Hora Salida', 'Estado', 'Notas']);

            foreach ($students as $student) {
                $att = $attendances->get($student->id);
                fputcsv($handle, [
                    $student->matricula,
                    $student->nombre_formateado,
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
