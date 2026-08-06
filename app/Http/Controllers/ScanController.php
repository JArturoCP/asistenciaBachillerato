<?php

namespace App\Http\Controllers;

use App\Models\Estudiante;
use App\Models\Asistencia;
use App\Models\AuditLog;
use App\Mail\AttendanceRecordedMail;
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
        ]);

        $qrCode = trim($request->qr_code);
        $today = Carbon::today();
        $now = Carbon::now();

        // 1. Find active student by UUID or matricula
        $student = Estudiante::with(['user', 'grupo', 'tutores.user'])
            ->where('is_active', true)
            ->where(function ($q) use ($qrCode) {
                $q->where('uuid', $qrCode)
                  ->orWhere('matricula', $qrCode);
            })->first();

        if (!$student) {
            AuditLog::log('READ', 'estudiantes', null, "Intento de escaneo con código no reconocido: {$qrCode}");
            return response()->json([
                'status' => 'error',
                'title' => 'Código no Reconocido',
                'message' => 'El código QR no corresponde a ningún estudiante activo en el sistema.',
            ], 404);
        }

        // 2. Check existing attendance for today
        $attendance = Asistencia::where('estudiante_id', $student->id)
            ->whereDate('fecha', $today)
            ->first();

        // Anti-duplicate / Debounce logic (5 minutes)
        if ($attendance && $attendance->hora_entrada) {
            $checkInDateTime = Carbon::parse($attendance->fecha->format('Y-m-d') . ' ' . $attendance->hora_entrada);
            
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

            // If already checked in and 5+ minutes passed, register Check-Out (Salida)
            if (!$attendance->hora_salida) {
                $attendance->update([
                    'hora_salida' => $now->format('H:i:s'),
                ]);

                AuditLog::log('WRITE', 'asistencias', $attendance->id, "Registro de SALIDA para estudiante: {$student->nombre_completo}");

                $this->sendEmailNotification($student, $attendance);

                return response()->json([
                    'status' => 'info',
                    'title' => 'Salida Registrada',
                    'message' => "Salida escolar registrada correctamente para {$student->nombre_completo}.",
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
        // Late arrival threshold: 07:15:00 AM
        $lateThreshold = Carbon::createFromTime(7, 15, 0);
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
}
