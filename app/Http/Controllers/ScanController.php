<?php

namespace App\Http\Controllers;

use App\Models\Estudiante;
use App\Models\Asistencia;
use App\Models\AsistenciaDocente;
use App\Models\User;
use App\Models\AuditLog;
use App\Mail\AttendanceRecordedMail;
use App\Jobs\SendWhatsAppNotificationJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class ScanController extends Controller
{
    public function index()
    {
        return view('scan.index');
    }

    public function process(Request $request)
    {
        $request->validate([
            'qr_code' => 'required|string',
            'scan_method' => 'required|in:qr_camera,qr_usb',
        ], [
            'qr_code.required' => 'El código QR de la credencial es obligatorio.',
            'scan_method.required' => 'El método de escaneo es obligatorio.',
            'scan_method.in' => 'El método de escaneo seleccionado no es válido.',
        ]);

        $qrCode = trim($request->qr_code);

        // Enforce Mexican Local Time (America/Mexico_City)
        $now = Carbon::now('America/Mexico_City');
        $today = Carbon::today('America/Mexico_City');

        // 1. Find active student by UUID or matricula
        $student = Estudiante::with(['user', 'grupo', 'tutores.user'])
            ->where('is_active', true)
            ->where(function ($q) use ($qrCode) {
                $q->where('uuid', $qrCode)
                  ->orWhere('matricula', $qrCode);
            })->first();

        if (!$student) {
            // Check if QR code matches a Teacher user
            $teacher = User::whereIn('role', ['teacher', 'docente'])
                ->where('is_approved', true)
                ->where(function ($q) use ($qrCode) {
                    $q->where('uuid', $qrCode)
                      ->orWhere('id', $qrCode)
                      ->orWhere('email', $qrCode)
                      ->orWhere('phone', $qrCode)
                      ->orWhere('name', $qrCode);
                })->first();

            if ($teacher) {
                return $this->processTeacherAttendance($teacher, $request, $now, $today);
            }

            AuditLog::log('READ', 'estudiantes', null, "Intento de escaneo con código no reconocido: {$qrCode}");
            return response()->json([
                'status' => 'error',
                'title' => 'Código no Reconocido',
                'message' => 'El código QR no corresponde a ningún estudiante ni docente activo en el sistema.',
            ], 404);
        }

        // 2. Check existing attendance for today
        $attendance = Asistencia::where('estudiante_id', $student->id)
            ->whereDate('fecha', $today)
            ->first();

        // Anti-duplicate / Debounce logic & Departure time rules
        if ($attendance && $attendance->hora_entrada) {
            $checkInDateTime = Carbon::parse($attendance->fecha->format('Y-m-d') . ' ' . $attendance->hora_entrada, 'America/Mexico_City');
            
            // If scanned again within 5 minutes of check-in, reject as accidental duplicate
            if ($checkInDateTime->diffInMinutes($now) < 5 && !$attendance->hora_salida) {
                return response()->json([
                    'status' => 'warning',
                    'title' => 'Escaneo Duplicado Detectado',
                    'message' => "La entrada de {$student->nombre_completo} ya fue registrada hace menos de 5 minutos ({$attendance->hora_entrada}).",
                    'student' => [
                        'name' => $student->nombre_completo,
                        'matricula' => $student->matricula,
                        'group' => $student->grupo->codigo_grupo,
                    ],
                    'attendance' => [
                        'check_in' => $attendance->hora_entrada,
                        'status' => $attendance->estado,
                    ]
                ], 422);
            }

            // Check-out (Salida) is only allowed starting at 08:50 AM
            $checkoutAllowedTime = Carbon::createFromTime(8, 50, 0, 'America/Mexico_City');
            if ($now->lessThan($checkoutAllowedTime) && !$attendance->hora_salida) {
                return response()->json([
                    'status' => 'warning',
                    'title' => 'Salida No Permitida Aún',
                    'message' => "El registro de salidas está permitido a partir de las 08:50 AM. La entrada de {$student->nombre_completo} ya fue registrada a las {$attendance->hora_entrada}.",
                    'student' => [
                        'name' => $student->nombre_completo,
                        'matricula' => $student->matricula,
                        'group' => $student->grupo->codigo_grupo,
                    ],
                    'attendance' => [
                        'check_in' => $attendance->hora_entrada,
                        'status' => $attendance->estado,
                    ]
                ], 422);
            }

            // If already checked in and 08:50 AM or later, register Check-Out (Salida)
            if (!$attendance->hora_salida) {
                $attendance->update([
                    'hora_salida' => $now->format('H:i:s'),
                ]);

                AuditLog::log('WRITE', 'asistencias', $attendance->id, "Registro de SALIDA para estudiante: {$student->nombre_completo}");

                $this->sendEmailNotification($student, $attendance);
                $this->sendWhatsAppNotification($student, $attendance, 'salida');

                return response()->json([
                    'status' => 'info',
                    'title' => 'Salida Registrada',
                    'message' => "Salida escolar registrada correctamente para {$student->nombre_completo} a las {$now->format('H:i:s')} hrs.",
                    'student' => [
                        'name' => $student->nombre_completo,
                        'matricula' => $student->matricula,
                        'group' => $student->grupo->codigo_grupo,
                    ],
                    'attendance' => [
                        'type' => 'checkout',
                        'check_in' => $attendance->hora_entrada,
                        'check_out' => $attendance->hora_salida,
                        'status' => $attendance->estado,
                    ]
                ]);
            }

            // Already has both check-in and check-out
            return response()->json([
                'status' => 'warning',
                'title' => 'Entrada y Salida Ya Registradas',
                'message' => "El estudiante {$student->nombre_completo} ya cuenta con registro completo de entrada y salida el día de hoy.",
                'student' => [
                    'name' => $student->nombre_completo,
                    'matricula' => $student->matricula,
                    'group' => $student->grupo->codigo_grupo,
                ],
                'attendance' => [
                    'check_in' => $attendance->hora_entrada,
                    'check_out' => $attendance->hora_salida,
                    'status' => $attendance->estado,
                ]
            ], 422);
        }

        // 3. Register Check-In (Entrada)
        // Punctual entry threshold: up to 08:00:00 AM. After 08:00:00 AM is late (retardo).
        $lateThreshold = Carbon::createFromTime(8, 0, 0, 'America/Mexico_City');
        $isLate = $now->greaterThan($lateThreshold);
        $attendanceStatus = $isLate ? 'retardo' : 'presente';

        $attendance = Asistencia::create([
            'estudiante_id' => $student->id,
            'fecha' => $today->format('Y-m-d'),
            'hora_entrada' => $now->format('H:i:s'),
            'estado' => $attendanceStatus,
            'metodo_escaneo' => $request->scan_method,
        ]);

        AuditLog::log('WRITE', 'asistencias', $attendance->id, "Registro de ENTRADA ({$attendanceStatus}) para estudiante: {$student->nombre_completo}");

        $this->sendEmailNotification($student, $attendance);
        $this->sendWhatsAppNotification($student, $attendance, 'entrada');

        return response()->json([
            'status' => $isLate ? 'warning' : 'success',
            'title' => $isLate ? 'Entrada Registrada (RETARDO)' : 'Entrada Registrada',
            'message' => $isLate 
                ? "Entrada registrada con retardo a las {$now->format('H:i:s')} para {$student->nombre_completo}."
                : "Entrada a tiempo registrada a las {$now->format('H:i:s')} para {$student->nombre_completo}.",
            'student' => [
                'name' => $student->nombre_completo,
                'matricula' => $student->matricula,
                'group' => $student->grupo->codigo_grupo,
            ],
            'attendance' => [
                'type' => 'checkin',
                'check_in' => $attendance->hora_entrada,
                'status' => $attendance->estado,
            ]
        ]);
    }

    private function sendEmailNotification(Estudiante $student, Asistencia $attendance): void
    {
        try {
            foreach ($student->tutores as $guardian) {
                if ($guardian->alertas_correo_activadas && !empty($guardian->correo_notificaciones)) {
                    Mail::to($guardian->correo_notificaciones)->send(new AttendanceRecordedMail($student, $attendance));
                }
            }
        } catch (\Throwable $e) {
            logger()->error("Error enviando correo de asistencia: " . $e->getMessage());
        }
    }

    private function sendWhatsAppNotification(Estudiante $student, Asistencia $attendance, string $type): void
    {
        try {
            foreach ($student->tutores as $guardian) {
                $whatsappEnabled = $guardian->alertas_whatsapp_activadas ?? true;
                $phone = $guardian->telefono ?? $guardian->user?->phone;

                if ($whatsappEnabled && !empty($phone)) {
                    $tutorName = $guardian->user?->nombre_completo ?? 'Tutor';
                    $studentName = $student->nombre_completo;
                    $group = $student->grupo->codigo_grupo ?? 'N/A';
                    $date = Carbon::parse($attendance->fecha)->format('d/m/Y');
                    $time = $type === 'salida' ? $attendance->hora_salida : $attendance->hora_entrada;

                    if ($type === 'salida') {
                        $message = "🏫 *SIGO 112 Alerta Escolar*\n\nHola {$tutorName},\nLe informamos que su hijo(a) *{$studentName}* (Grupo {$group}) ha registrado su *SALIDA* del plantel hoy {$date} a las {$time} hrs.\n\n_Control de Asistencia Escolar_";
                    } else {
                        $statusText = $attendance->estado === 'retardo' ? 'ENTRADA CON RETARDO' : 'ENTRADA';
                        $message = "🏫 *SIGO 112 Alerta Escolar*\n\nHola {$tutorName},\nLe informamos que su hijo(a) *{$studentName}* (Grupo {$group}) ha registrado su *{$statusText}* en el plantel hoy {$date} a las {$time} hrs.\n\n_Control de Asistencia Escolar_";
                    }

                    // Dispatch job with random delay (between 3 and 15 seconds) to avoid rate-limiting/blocking
                    $delaySeconds = rand(3, 15);
                    SendWhatsAppNotificationJob::dispatch($phone, $message)
                        ->delay(now()->addSeconds($delaySeconds));
                }
            }
        } catch (\Throwable $e) {
            logger()->error("Error en sendWhatsAppNotification: " . $e->getMessage());
        }
    }

    private function processTeacherAttendance(User $teacher, Request $request, Carbon $now, Carbon $today)
    {
        $attendance = AsistenciaDocente::where('docente_id', $teacher->id)
            ->whereDate('fecha', $today)
            ->first();

        if ($attendance && $attendance->hora_entrada) {
            $checkInDateTime = Carbon::parse($attendance->fecha->format('Y-m-d') . ' ' . $attendance->hora_entrada, 'America/Mexico_City');
            
            if ($checkInDateTime->diffInMinutes($now) < 5 && !$attendance->hora_salida) {
                return response()->json([
                    'status' => 'warning',
                    'title' => 'Escaneo Duplicado (Docente)',
                    'message' => "La entrada de la/del docente {$teacher->nombre_completo} ya fue registrada hace menos de 5 minutos ({$attendance->hora_entrada}).",
                    'student' => [
                        'name' => "Docente: {$teacher->nombre_completo}",
                        'matricula' => "DOC-{$teacher->id}",
                        'group' => 'Personal Docente',
                    ],
                    'attendance' => [
                        'check_in' => $attendance->hora_entrada,
                        'status' => $attendance->estado,
                    ]
                ], 422);
            }

            $checkoutAllowedTime = Carbon::createFromTime(8, 50, 0, 'America/Mexico_City');
            if ($now->lessThan($checkoutAllowedTime) && !$attendance->hora_salida) {
                return response()->json([
                    'status' => 'warning',
                    'title' => 'Salida No Permitida Aún',
                    'message' => "El registro de salidas para docentes está permitido a partir de las 08:50 AM. La entrada de {$teacher->nombre_completo} fue registrada a las {$attendance->hora_entrada}.",
                    'student' => [
                        'name' => "Docente: {$teacher->nombre_completo}",
                        'matricula' => "DOC-{$teacher->id}",
                        'group' => 'Personal Docente',
                    ],
                    'attendance' => [
                        'check_in' => $attendance->hora_entrada,
                        'status' => $attendance->estado,
                    ]
                ], 422);
            }

            if (!$attendance->hora_salida) {
                $attendance->update([
                    'hora_salida' => $now->format('H:i:s'),
                ]);

                AuditLog::log('WRITE', 'asistencias_docentes', $attendance->id, "Registro de SALIDA para docente: {$teacher->nombre_completo}");

                return response()->json([
                    'status' => 'info',
                    'title' => 'Salida Registrada (Docente)',
                    'message' => "Salida registrada correctamente para la/del docente {$teacher->nombre_completo} a las {$now->format('H:i:s')} hrs.",
                    'student' => [
                        'name' => "Docente: {$teacher->nombre_completo}",
                        'matricula' => "DOC-{$teacher->id}",
                        'group' => 'Personal Docente',
                    ],
                    'attendance' => [
                        'type' => 'checkout',
                        'check_in' => $attendance->hora_entrada,
                        'check_out' => $attendance->hora_salida,
                        'status' => $attendance->estado,
                    ]
                ]);
            }

            return response()->json([
                'status' => 'warning',
                'title' => 'Entrada y Salida Ya Registradas',
                'message' => "La/El docente {$teacher->nombre_completo} ya cuenta con registro completo de entrada y salida hoy.",
                'student' => [
                    'name' => "Docente: {$teacher->nombre_completo}",
                    'matricula' => "DOC-{$teacher->id}",
                    'group' => 'Personal Docente',
                ],
                'attendance' => [
                    'check_in' => $attendance->hora_entrada,
                    'check_out' => $attendance->hora_salida,
                    'status' => $attendance->estado,
                ]
            ], 422);
        }

        $lateThreshold = Carbon::createFromTime(8, 0, 0, 'America/Mexico_City');
        $isLate = $now->greaterThan($lateThreshold);
        $attendanceStatus = $isLate ? 'retardo' : 'presente';

        $attendance = AsistenciaDocente::create([
            'docente_id' => $teacher->id,
            'fecha' => $today->format('Y-m-d'),
            'hora_entrada' => $now->format('H:i:s'),
            'estado' => $attendanceStatus,
            'metodo_escaneo' => $request->scan_method,
        ]);

        AuditLog::log('WRITE', 'asistencias_docentes', $attendance->id, "Registro de ENTRADA ({$attendanceStatus}) para docente: {$teacher->nombre_completo}");

        return response()->json([
            'status' => $isLate ? 'warning' : 'success',
            'title' => $isLate ? 'Entrada Registrada (RETARDO Docente)' : 'Entrada Registrada (Docente)',
            'message' => $isLate 
                ? "Entrada registrada con retardo a las {$now->format('H:i:s')} para la/del docente {$teacher->nombre_completo}."
                : "Entrada a tiempo registrada a las {$now->format('H:i:s')} para la/del docente {$teacher->nombre_completo}.",
            'student' => [
                'name' => "Docente: {$teacher->nombre_completo}",
                'matricula' => "DOC-{$teacher->id}",
                'group' => 'Personal Docente',
            ],
            'attendance' => [
                'type' => 'checkin',
                'check_in' => $attendance->hora_entrada,
                'status' => $attendance->estado,
            ]
        ]);
    }
}
