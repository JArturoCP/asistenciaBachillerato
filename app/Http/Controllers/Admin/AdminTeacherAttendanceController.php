<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AsistenciaDocente;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminTeacherAttendanceController extends Controller
{
    public function index(Request $request)
    {
        // 1. Date filter (defaults to today in America/Mexico_City)
        $refDateInput = $request->input('date');
        $carbonDate = $refDateInput ? Carbon::parse($refDateInput, 'America/Mexico_City') : Carbon::today('America/Mexico_City');
        $date = $carbonDate->format('Y-m-d');

        // 2. Fetch all approved teachers ordered alphabetically by Apellido Paterno, Materno, Nombre
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

        // 3. Get teacher attendances for the selected date
        $attendances = AsistenciaDocente::whereIn('docente_id', $teachers->pluck('id'))
            ->whereDate('fecha', $date)
            ->get()
            ->keyBy('docente_id');

        $metrics = [
            'total' => $teachers->count(),
            'presentes' => 0,
            'retardos' => 0,
            'faltas' => 0,
            'justificados' => 0,
        ];

        $statusMap = [
            'presente' => 'presentes',
            'retardo' => 'retardos',
            'falta' => 'faltas',
            'justificado' => 'justificados',
        ];

        $teacherList = collect();

        foreach ($teachers as $teacher) {
            $att = $attendances->get($teacher->id);
            $status = $att ? $att->estado : 'falta';

            $teacherList->push((object)[
                'teacher' => $teacher,
                'attendance' => $att,
                'status' => $status,
                'check_in_time' => $att?->hora_entrada,
                'check_out_time' => $att?->hora_salida,
                'notes' => $att?->observaciones,
            ]);

            $metricKey = $statusMap[$status] ?? 'faltas';
            $metrics[$metricKey]++;
        }

        AuditLog::log('READ', 'asistencias_docentes', null, "Admin consulta asistencia general de docentes para la fecha {$date}");

        return view('admin.teachers.attendance', compact('teacherList', 'date', 'metrics'));
    }

    public function updateStatus(Request $request)
    {
        $validated = $request->validate([
            'docente_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'status' => 'required|in:presente,retardo,falta,justificado',
            'notes' => 'nullable|string|max:255',
            'check_in_time' => 'nullable|string',
            'check_out_time' => 'nullable|string',
        ], [
            'docente_id.required' => 'Debe seleccionar un docente.',
            'docente_id.exists' => 'El docente seleccionado no existe.',
            'date.required' => 'La fecha es obligatoria.',
            'status.required' => 'El estado de asistencia es obligatorio.',
            'status.in' => 'El estado de asistencia no es válido.',
        ]);

        $attendance = AsistenciaDocente::updateOrCreate(
            [
                'docente_id' => $validated['docente_id'],
                'fecha' => $validated['date'],
            ],
            [
                'estado' => $validated['status'],
                'observaciones' => $validated['notes'],
                'metodo_escaneo' => 'manual_admin',
                'hora_entrada' => $validated['status'] !== 'falta' ? ($request->check_in_time ?: Carbon::now('America/Mexico_City')->format('H:i:s')) : null,
                'hora_salida' => $request->check_out_time ?: null,
            ]
        );

        AuditLog::log('WRITE', 'asistencias_docentes', $attendance->id, "Justificación/Modificación manual de asistencia para docente ID {$validated['docente_id']}");

        return redirect()->back()->with('success', 'Asistencia del docente actualizada correctamente.');
    }

    public function exportCsv(Request $request)
    {
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

        $filename = "Asistencia_Docentes_{$date}.csv";

        AuditLog::log('EXPORT', 'asistencias_docentes', null, "Exportación CSV de asistencia de docentes para la fecha {$date}");

        $response = new StreamedResponse(function () use ($teachers, $attendances, $date) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM
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
