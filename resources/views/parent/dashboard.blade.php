@extends('layouts.app')

@section('header')
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 font-weight-bold text-dark mb-0">
                <i class="bi bi-house-heart text-success me-2"></i> Portal de Padres y Tutores
            </h2>
            <span class="badge bg-success px-3 py-2">
                <i class="bi bi-shield-check me-1"></i> Cumplimiento LFPDPPP México
            </span>
        </div>
@endsection

@section('content')

    @if($students->count() > 0)
        <!-- Child Selector Bar (if parent has multiple children) -->
        <div class="card card-custom p-3 bg-white mb-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold text-muted small text-uppercase">Estudiante Seleccionado:</span>
                    @foreach($students as $studentItem)
                        <a href="{{ route('parent.dashboard', ['student_id' => $studentItem->id]) }}" class="btn btn-sm {{ $selectedStudentId == $studentItem->id ? 'btn-primary font-weight-bold' : 'btn-outline-secondary' }}">
                            <i class="bi bi-person-fill me-1"></i> {{ $studentItem->nombre_completo }} (Grupo {{ $studentItem->grupo->codigo_grupo }})
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        @if($selectedStudent)
            <!-- Today's Status Box -->
            <div class="row g-4 mb-4">
                <div class="col-md-7">
                    <div class="card card-custom p-4 bg-white h-100">
                        <h5 class="fw-bold mb-3"><i class="bi bi-clock-history text-primary me-2"></i> Asistencia de Hoy ({{ date('d/m/Y') }})</h5>
                        
                        @if($todayAttendance)
                            <div class="alert {{ $todayAttendance->estado === 'presente' ? 'alert-success' : ($todayAttendance->estado === 'retardo' ? 'alert-warning' : 'alert-info') }} border p-4 rounded-4 mb-0">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle p-3 me-3 text-white {{ $todayAttendance->estado === 'presente' ? 'bg-success' : ($todayAttendance->estado === 'retardo' ? 'bg-warning' : 'bg-info') }}" style="font-size: 2rem;">
                                        <i class="bi {{ $todayAttendance->estado === 'presente' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' }}"></i>
                                    </div>
                                    <div>
                                        <h4 class="fw-bold mb-1">{{ strtoupper($todayAttendance->estado) }}</h4>
                                        <div class="fs-6">
                                            Entrada registrada a las <strong>{{ $todayAttendance->hora_entrada }}</strong>
                                            @if($todayAttendance->hora_salida)
                                                | Salida a las <strong>{{ $todayAttendance->hora_salida }}</strong>
                                            @endif
                                        </div>
                                        <small class="text-muted d-block mt-1">Escaneado vía: {{ strtoupper($todayAttendance->metodo_escaneo) }}</small>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-secondary border p-4 rounded-4 text-center mb-0">
                                <i class="bi bi-clock fs-2 d-block mb-2 text-muted"></i>
                                <h6 class="fw-bold text-dark mb-1">Sin Registro de Entrada Aún</h6>
                                <p class="small text-muted mb-0">El estudiante no ha escaneado su credencial el día de hoy.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Child Quick Profile Card -->
                <div class="col-md-5">
                    <div class="card card-custom p-4 bg-white h-100">
                        <h5 class="fw-bold mb-3"><i class="bi bi-person-badge text-warning me-2"></i> Datos del Alumno</h5>
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-3 me-3" style="font-size: 2rem;">
                                <i class="bi bi-mortarboard-fill"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0 text-dark">{{ $selectedStudent->nombre_completo }}</h5>
                                <div class="text-muted small">Matrícula: <strong class="font-monospace text-dark">{{ $selectedStudent->matricula }}</strong></div>
                                <span class="badge bg-primary mt-1">Grupo {{ $selectedStudent->grupo->codigo_grupo }} - Turno {{ ucfirst($selectedStudent->grupo->turno) }}</span>
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between align-items-center small">
                            <span class="text-muted">Aviso LFPDPPP Aceptado:</span>
                            @if($consent)
                                <span class="badge bg-success"><i class="bi bi-shield-check"></i> Firmado ({{ $consent->fecha_otorgado ? $consent->fecha_otorgado->format('d/m/Y') : 'Sí' }})</span>
                            @else
                                <span class="badge bg-warning text-dark">Pendiente</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Attendance Heatmap Calendar -->
            <div class="card card-custom p-4 bg-white mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-calendar-week text-success me-2"></i> Calendario Mensual de Asistencia</h5>
                    <div class="d-flex gap-2 align-items-center">
                        <span class="badge bg-success">■ Presente</span>
                        <span class="badge bg-warning text-dark">■ Retardo</span>
                        <span class="badge bg-danger">■ Falta</span>
                        <span class="badge bg-secondary">■ Fin de semana</span>
                    </div>
                </div>

                <div class="row row-cols-7 g-2 text-center">
                    @foreach($monthlyCalendar as $dayItem)
                        <div class="col">
                            <div class="p-3 rounded-3 border {{ $dayItem->is_weekend ? 'bg-light text-muted' : ($dayItem->status === 'presente' ? 'bg-success bg-opacity-15 border-success text-success fw-bold' : ($dayItem->status === 'retardo' ? 'bg-warning bg-opacity-15 border-warning text-dark fw-bold' : ($dayItem->status === 'falta' ? 'bg-danger bg-opacity-15 border-danger text-danger fw-bold' : 'bg-white'))) }}" style="min-height: 80px;">
                                <div class="fs-5">{{ $dayItem->day_number }}</div>
                                <small style="font-size: 0.7rem;">{{ $dayItem->date->translatedFormat('D') }}</small>
                                @if($dayItem->check_in)
                                    <div style="font-size: 0.65rem;" class="mt-1">{{ $dayItem->check_in }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Notification Settings & ARCO Rights Tab -->
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card card-custom p-4 bg-white h-100">
                        <h5 class="fw-bold mb-3"><i class="bi bi-envelope-at text-info me-2"></i> Configuración de Alertas por Correo</h5>
                        <form action="{{ route('parent.alerts.update') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Correo de Notificación Instantánea</label>
                                <input type="email" class="form-control bg-light" value="{{ $guardian->correo_notificaciones ?? $guardian->user->email }}" readonly disabled>
                                <small class="text-muted d-block mt-1"><i class="bi bi-info-circle me-1"></i> Para modificar la dirección de correo registrada acuda con la dirección escolar.</small>
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="email_alerts_enabled" value="1" id="alertSwitch" {{ ($guardian->alertas_correo_activadas ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="alertSwitch">Recibir correo electrónico inmediato cuando mi hijo(a) escanee su entrada/salida</label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-info text-white btn-sm fw-semibold">Guardar Preferencias</button>
                        </form>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card card-custom p-4 bg-white h-100">
                        <h5 class="fw-bold mb-3"><i class="bi bi-shield-lock text-danger me-2"></i> Ejercicio de Derechos ARCO (LFPDPPP)</h5>
                        <p class="small text-muted mb-3">Conforme a la ley de protección de datos en México, usted puede solicitar el Acceso, Rectificación, Cancelación u Oposición del tratamiento de datos de su representado.</p>
                        <form action="{{ route('parent.arco.submit') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Tipo de Solicitud</label>
                                <select name="request_type" class="form-select" required>
                                    <option value="acceso">Acceso (Consultar datos almacenados)</option>
                                    <option value="rectificacion">Rectificación (Corregir datos del menor)</option>
                                    <option value="cancelacion">Cancelación (Eliminar registros históricos)</option>
                                    <option value="oposicion">Oposición (Oponerse a notificaciones secundarias)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Detalle o Motivo de la Solicitud</label>
                                <textarea name="details" class="form-control" rows="2" placeholder="Describa brevemente la solicitud ARCO..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-outline-danger btn-sm font-weight-bold">Enviar Solicitud ARCO</button>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @else
        <div class="alert alert-warning card-custom text-center py-5">
            <i class="bi bi-shield-x fs-1 text-warning d-block mb-3"></i>
            <h5 class="fw-bold">No tiene estudiantes vinculados a su cuenta de tutor</h5>
            <p class="text-muted">Por favor acuda a la dirección escolar para vincular a su hijo(a) y firmar el Aviso de Privacidad LFPDPPP.</p>
        </div>
    @endif
@endsection
