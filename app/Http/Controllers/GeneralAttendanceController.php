<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Grupo;
use App\Models\Estudiante;
use App\Models\Asistencia;
use App\Models\AsistenciaDocente;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GeneralAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $canViewStudents = $user->canViewAllStudentAttendance();
        $canViewTeachers = $user->canViewAllTeacherAttendance();

        if (!$canViewStudents && !$canViewTeachers) {
            abort(403, 'No tiene permisos para acceder al control general de asistencias.');
        }

        // Active tab parameter (default to students if allowed, else teachers)
        $tab = $request->input('tab');
        if ($tab === 'teachers' && !$canViewTeachers) {
            $tab = 'students';
        }
        if (!$tab || ($tab === 'students' && !$canViewStudents)) {
            $tab = $canViewStudents ? 'students' : 'teachers';
        }

        // Selected Date (defaults to today)
        $refDateInput = $request->input('date');
        $carbonDate = $refDateInput ? Carbon::parse($refDateInput, 'America/Mexico_City') : Carbon::today('America/Mexico_City');
        $date = $carbonDate->format('Y-m-d');

        $search = trim($request->input('search', ''));
        $statusFilter = $request->input('status', 'all');

        $groups = Grupo::orderBy('codigo_grupo')->get();
        $selectedGroupId = $request->input('group_id', 'all');

        // Data for Student Attendance
        $studentList = collect();
        $studentMetrics = ['total' => 0, 'presentes' => 0, 'retardos' => 0, 'faltas' => 0, 'justificados' => 0];

        if ($canViewStudents) {
            $studentsQuery = Estudiante::with(['user', 'grupo'])
                ->where('is_active', true);

            if ($selectedGroupId !== 'all' && !empty($selectedGroupId)) {
                $studentsQuery->where('grupo_id', $selectedGroupId);
            }

            if ($search !== '') {
                $studentsQuery->where(function ($q) use ($search) {
                    $q->where('matricula', 'like', "%{$search}%")
                      ->orWhereHas('user', function ($u) use ($search) {
                          $u->where('nombre', 'like', "%{$search}%")
                            ->orWhere('apellido_paterno', 'like', "%{$search}%")
                            ->orWhere('apellido_materno', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                      });
                });
            }

            $students = $studentsQuery->get()->sort(function ($a, $b) {
                $comp = strnatcasecmp($a->user->apellido_paterno ?? '', $b->user->apellido_paterno ?? '');
                if ($comp !== 0) return $comp;
                $comp = strnatcasecmp($a->user->apellido_materno ?? '', $b->user->apellido_materno ?? '');
                if ($comp !== 0) return $comp;
                return strnatcasecmp($a->user->nombre ?? '', $b->user->nombre ?? '');
            });

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

                if ($statusFilter !== 'all' && $statusFilter !== '' && $status !== $statusFilter) {
                    continue;
                }

                $studentList->push((object)[
                    'student' => $student,
                    'attendance' => $att,
                    'status' => $status,
                    'check_in_time' => $att?->hora_entrada,
                    'check_out_time' => $att?->hora_salida,
                    'notes' => $att?->observaciones,
                ]);

                $studentMetrics['total']++;
                $metricKey = $statusMap[$status] ?? 'faltas';
                $studentMetrics[$metricKey]++;
            }
        }

        // Data for Teacher Attendance
        $teacherList = collect();
        $teacherMetrics = ['total' => 0, 'presentes' => 0, 'retardos' => 0, 'faltas' => 0, 'justificados' => 0];

        if ($canViewTeachers) {
            $teachersQuery = User::whereIn('role', ['teacher', 'docente'])
                ->where('is_approved', true);

            if ($search !== '') {
                $teachersQuery->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                      ->orWhere('apellido_paterno', 'like', "%{$search}%")
                      ->orWhere('apellido_materno', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $teachers = $teachersQuery->get()->sort(function ($a, $b) {
                $comp = strnatcasecmp($a->apellido_paterno ?? '', $b->apellido_paterno ?? '');
                if ($comp !== 0) return $comp;
                $comp = strnatcasecmp($a->apellido_materno ?? '', $b->apellido_materno ?? '');
                if ($comp !== 0) return $comp;
                return strnatcasecmp($a->nombre ?? '', $b->nombre ?? '');
            });

            $teacherAttendancesToday = AsistenciaDocente::whereIn('docente_id', $teachers->pluck('id'))
                ->whereDate('fecha', $date)
                ->get()
                ->keyBy('docente_id');

            $statusMap = [
                'presente' => 'presentes',
                'retardo' => 'retardos',
                'falta' => 'faltas',
                'justificado' => 'justificados',
            ];

            foreach ($teachers as $teacher) {
                $att = $teacherAttendancesToday->get($teacher->id);
                $status = $att ? $att->estado : 'falta';

                if ($statusFilter !== 'all' && $statusFilter !== '' && $status !== $statusFilter) {
                    continue;
                }

                $teacherList->push((object)[
                    'teacher' => $teacher,
                    'attendance' => $att,
                    'status' => $status,
                    'check_in_time' => $att?->hora_entrada,
                    'check_out_time' => $att?->hora_salida,
                    'notes' => $att?->observaciones,
                ]);

                $teacherMetrics['total']++;
                $metricKey = $statusMap[$status] ?? 'faltas';
                $teacherMetrics[$metricKey]++;
            }
        }

        AuditLog::log('READ', 'asistencias', null, "Consulta de control general de asistencias (Tab: {$tab}, Fecha: {$date})");

        return view('attendance.overview', compact(
            'tab',
            'date',
            'search',
            'statusFilter',
            'groups',
            'selectedGroupId',
            'studentList',
            'studentMetrics',
            'teacherList',
            'teacherMetrics',
            'canViewStudents',
            'canViewTeachers'
        ));
    }

    public function updateStudentStatus(Request $request)
    {
        $user = auth()->user();
        if (!$user->canViewAllStudentAttendance() && !$user->isTeacher()) {
            abort(403, 'No tiene permiso para actualizar asistencias de estudiantes.');
        }

        $validated = $request->validate([
            'student_id' => 'required|exists:estudiantes,id',
            'date' => 'required|date',
            'status' => 'required|in:presente,retardo,falta,justificado',
            'notes' => 'nullable|string|max:255',
            'check_in_time' => 'nullable|string',
            'check_out_time' => 'nullable|string',
        ]);

        $attendance = Asistencia::updateOrCreate(
            [
                'estudiante_id' => $validated['student_id'],
                'fecha' => $validated['date'],
            ],
            [
                'estado' => $validated['status'],
                'observaciones' => $validated['notes'] ?? null,
                'metodo_escaneo' => 'manual_admin',
                'hora_entrada' => $validated['status'] !== 'falta' ? ($request->check_in_time ?: Carbon::now('America/Mexico_City')->format('H:i:s')) : null,
                'hora_salida' => $request->check_out_time ?: null,
            ]
        );

        AuditLog::log('WRITE', 'asistencias', $attendance->id, "Actualización manual de asistencia alumno ID {$validated['student_id']}");

        return redirect()->back()->with('success', 'Asistencia del alumno actualizada correctamente.');
    }

    public function updateTeacherStatus(Request $request)
    {
        $user = auth()->user();
        if (!$user->canViewAllTeacherAttendance()) {
            abort(403, 'No tiene permiso para actualizar asistencias de docentes.');
        }

        $validated = $request->validate([
            'docente_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'status' => 'required|in:presente,retardo,falta,justificado',
            'notes' => 'nullable|string|max:255',
            'check_in_time' => 'nullable|string',
            'check_out_time' => 'nullable|string',
        ]);

        $attendance = AsistenciaDocente::updateOrCreate(
            [
                'docente_id' => $validated['docente_id'],
                'fecha' => $validated['date'],
            ],
            [
                'estado' => $validated['status'],
                'observaciones' => $validated['notes'] ?? null,
                'metodo_escaneo' => 'manual_admin',
                'hora_entrada' => $validated['status'] !== 'falta' ? ($request->check_in_time ?: Carbon::now('America/Mexico_City')->format('H:i:s')) : null,
                'hora_salida' => $request->check_out_time ?: null,
            ]
        );

        AuditLog::log('WRITE', 'asistencias_docentes', $attendance->id, "Actualización manual de asistencia docente ID {$validated['docente_id']}");

        return redirect()->back()->with('success', 'Asistencia del docente actualizada correctamente.');
    }

    public function exportStudentCsv(Request $request)
    {
        $user = auth()->user();
        if (!$user->canViewAllStudentAttendance()) {
            abort(403);
        }

        $dateInput = $request->input('date');
        $refDate = $dateInput ? Carbon::parse($dateInput, 'America/Mexico_City') : Carbon::today('America/Mexico_City');
        $date = $refDate->format('Y-m-d');
        $groupId = $request->input('group_id', 'all');

        $query = Estudiante::with(['user', 'grupo'])->where('is_active', true);
        if ($groupId !== 'all' && !empty($groupId)) {
            $query->where('grupo_id', $groupId);
        }

        $students = $query->get()->sort(function ($a, $b) {
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

        $filename = "Asistencias_Alumnos_{$date}.csv";

        AuditLog::log('EXPORT', 'asistencias', null, "Exportación CSV asistencias alumnos para la fecha {$date}");

        $response = new StreamedResponse(function () use ($students, $attendances, $date) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM
            fputcsv($handle, ['Matrícula', 'Nombre Alumno', 'Grupo', 'Fecha', 'Hora Entrada', 'Hora Salida', 'Estado', 'Notas']);

            foreach ($students as $student) {
                $att = $attendances->get($student->id);
                fputcsv($handle, [
                    $student->matricula,
                    $student->nombre_formateado,
                    $student->grupo?->codigo_grupo ?? 'Sin grupo',
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

    public function exportTeacherCsv(Request $request)
    {
        $user = auth()->user();
        if (!$user->canViewAllTeacherAttendance()) {
            abort(403);
        }

        $dateInput = $request->input('date');
        $refDate = $dateInput ? Carbon::parse($dateInput, 'America/Mexico_City') : Carbon::today('America/Mexico_City');
        $date = $refDate->format('Y-m-d');

        $teachers = User::whereIn('role', ['teacher', 'docente'])
            ->where('is_approved', true)
            ->get()
            ->sort(function ($a, $b) {
                $comp = strnatcasecmp($a->apellido_paterno ?? '', $b->apellido_paterno ?? '');
                if ($comp !== 0) return $comp;
                $comp = strnatcasecmp($a->apellido_materno ?? '', $b->apellido_materno ?? '');
                if ($comp !== 0) return $comp;
                return strnatcasecmp($a->nombre ?? '', $b->nombre ?? '');
            });

        $attendances = AsistenciaDocente::whereIn('docente_id', $teachers->pluck('id'))
            ->whereDate('fecha', $date)
            ->get()
            ->keyBy('docente_id');

        $filename = "Asistencias_Docentes_{$date}.csv";

        AuditLog::log('EXPORT', 'asistencias_docentes', null, "Exportación CSV asistencias docentes para la fecha {$date}");

        $response = new StreamedResponse(function () use ($teachers, $attendances, $date) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['ID Docente', 'Nombre Completo', 'Correo Electrónico', 'Teléfono', 'Fecha', 'Hora Entrada', 'Hora Salida', 'Estado', 'Notas']);

            foreach ($teachers as $teacher) {
                $att = $attendances->get($teacher->id);
                fputcsv($handle, [
                    "DOC-{$teacher->id}",
                    $teacher->nombre_completo,
                    $teacher->email,
                    $teacher->phone ?? 'N/A',
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
