<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Tutor;
use App\Models\Estudiante;
use App\Models\Asistencia;
use App\Models\Consentimiento;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ParentPortalController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Retrieve guardian profile
        $guardian = Tutor::where('user_id', $user->id)->first();

        if (!$guardian && !$user->isAdmin()) {
            abort(403, 'No se encontró un perfil de tutor asociado a su cuenta.');
        }

        // If admin viewing portal, fallback to first guardian or demo
        if ($user->isAdmin() && !$guardian) {
            $guardian = Tutor::first();
        }

        $students = $guardian ? $guardian->estudiantes()->with(['user', 'grupo'])->get() : collect();
        $selectedStudentId = $request->input('student_id', $students->first()?->id);
        $selectedStudent = $students->firstWhere('id', $selectedStudentId);

        $today = Carbon::today('America/Mexico_City');
        $todayAttendance = null;
        $monthlyCalendar = [];
        $month = $request->input('month', Carbon::now('America/Mexico_City')->month);
        $year = $request->input('year', Carbon::now('America/Mexico_City')->year);

        if ($selectedStudent) {
            // Today's status
            $todayAttendance = Asistencia::where('estudiante_id', $selectedStudent->id)
                ->whereDate('fecha', $today)
                ->first();

            // Monthly heatmap data
            $startDate = Carbon::createFromDate($year, $month, 1, 'America/Mexico_City')->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            $attendancesMonth = Asistencia::where('estudiante_id', $selectedStudent->id)
                ->whereBetween('fecha', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->get()
                ->keyBy(function ($item) {
                    return $item->fecha->format('Y-m-d');
                });

            // Build calendar array
            $currentDay = $startDate->copy();
            while ($currentDay->lte($endDate)) {
                $dateKey = $currentDay->format('Y-m-d');
                $att = $attendancesMonth->get($dateKey);

                $monthlyCalendar[] = (object)[
                    'date' => $currentDay->copy(),
                    'day_number' => $currentDay->day,
                    'is_weekend' => $currentDay->isWeekend(),
                    'status' => $att ? $att->estado : ($currentDay->isPast() && !$currentDay->isToday() && !$currentDay->isWeekend() ? 'falta' : 'sin_registro'),
                    'check_in' => $att?->hora_entrada,
                    'check_out' => $att?->hora_salida,
                ];

                $currentDay->addDay();
            }
        }

        $consent = $guardian && $selectedStudent 
            ? Consentimiento::where('tutor_id', $guardian->id)->where('estudiante_id', $selectedStudent->id)->first() 
            : null;

        AuditLog::log('READ', 'estudiantes', $selectedStudentId, "Padre/Tutor consulta historial de asistencia del estudiante ID {$selectedStudentId}");

        return view('parent.dashboard', compact(
            'guardian',
            'students',
            'selectedStudent',
            'selectedStudentId',
            'todayAttendance',
            'monthlyCalendar',
            'month',
            'year',
            'consent'
        ));
    }

    public function updateAlerts(Request $request)
    {
        $user = auth()->user();
        $guardian = Tutor::where('user_id', $user->id)->firstOrFail();

        $validated = $request->validate([
            'notification_email' => 'required|email',
            'email_alerts_enabled' => 'required|boolean',
        ], [
            'notification_email.required' => 'El correo electrónico para notificaciones es obligatorio.',
            'notification_email.email' => 'Ingrese una dirección de correo válida.',
            'email_alerts_enabled.required' => 'Debe indicar si desea activar las alertas por correo.',
        ]);

        $guardian->update([
            'correo_notificaciones' => $validated['notification_email'],
            'alertas_correo_activadas' => $validated['email_alerts_enabled'],
        ]);

        AuditLog::log('WRITE', 'tutores', $guardian->id, "Tutor actualizó preferencias de notificaciones por correo");

        return redirect()->back()->with('success', 'Preferencias de notificaciones por correo actualizadas.');
    }

    public function submitArcoRequest(Request $request)
    {
        $validated = $request->validate([
            'request_type' => 'required|in:acceso,rectificacion,cancelacion,oposicion',
            'details' => 'required|string|max:500',
        ], [
            'request_type.required' => 'Debe seleccionar un tipo de derecho ARCO.',
            'request_type.in' => 'El tipo de solicitud ARCO seleccionado no es válido.',
            'details.required' => 'Los detalles o justificación de la solicitud son obligatorios.',
            'details.max' => 'Los detalles de la solicitud no deben superar los 500 caracteres.',
        ]);

        AuditLog::log('ARCO_REQUEST', 'consentimientos', null, "Solicitud ARCO registrada ({$validated['request_type']}): {$validated['details']}");

        return redirect()->back()->with('success', 'Su solicitud de derechos ARCO ha sido registrada. La dirección escolar se pondrá en contacto en un plazo máximo de 5 días hábiles.');
    }
}
