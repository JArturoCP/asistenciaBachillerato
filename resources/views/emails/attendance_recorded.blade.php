<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Notificación de Asistencia Escolar</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f6f9; color: #333333; margin: 0; padding: 20px; }
        .email-container { max-width: 580px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; }
        .header { background: #1e293b; color: #ffffff; padding: 24px; text-align: center; }
        .header h2 { margin: 0; font-size: 20px; color: #f59e0b; }
        .content { padding: 24px; }
        .status-badge { display: inline-block; padding: 6px 14px; font-weight: bold; border-radius: 20px; text-transform: uppercase; font-size: 13px; margin-bottom: 15px; }
        .status-presente { background: #d1fae5; color: #065f46; }
        .status-retardo { background: #fef3c7; color: #92400e; }
        .status-checkout { background: #dbeafe; color: #1e40af; }
        .info-box { background: #f8fafc; border-left: 4px solid #3b82f6; padding: 16px; margin: 15px 0; border-radius: 4px; }
        .footer { background: #f1f5f9; padding: 16px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h2>Escuela Preparatoria Oficial número 112</h2>
            <div style="font-size: 13px; color: #cbd5e1;">Sistema de Control de Asistencia Escolar</div>
        </div>

        <div class="content">
            <p>Estimado(a) Padre, Madre o Tutor Legal,</p>
            <p>Le informamos que el día de hoy se ha registrado la siguiente actividad de asistencia para su representado(a):</p>

            <div class="info-box">
                <h3 style="margin-top: 0; color: #1e293b;">{{ $student->nombre_completo }}</h3>
                <div>Matrícula: <strong>{{ $student->matricula }}</strong></div>
                <div>Grupo: <strong>Grupo {{ $student->grupo->codigo_grupo }}</strong></div>
                <div>Fecha: <strong>{{ $attendance->fecha ? $attendance->fecha->format('d/m/Y') : date('d/m/Y') }}</strong></div>
                <div>Hora Entrada: <strong>{{ $attendance->hora_entrada }}</strong></div>
                @if($attendance->hora_salida)
                    <div>Hora Salida: <strong>{{ $attendance->hora_salida }}</strong></div>
                @endif
            </div>

            <div style="text-align: center;">
                <span class="status-badge {{ $attendance->estado === 'presente' ? 'status-presente' : ($attendance->estado === 'retardo' ? 'status-retardo' : 'status-checkout') }}">
                    Estado: {{ strtoupper($attendance->estado) }}
                </span>
            </div>

            <p style="font-size: 13px; color: #64748b; margin-top: 20px;">
                Puede consultar el historial completo de asistencias ingresando a su <a href="{{ config('app.url') }}/parent/dashboard">Portal de Padres de Familia</a>.
            </p>
        </div>

        <div class="footer">
            Usted recibe esta notificación automática en cumplimiento de las finalidades primarias del Aviso de Privacidad LFPDPPP autorizado para el seguimiento de la seguridad del menor.
        </div>
    </div>
</body>
</html>
