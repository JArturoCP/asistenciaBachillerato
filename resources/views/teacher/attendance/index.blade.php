@extends('layouts.app')

@section('header')
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 font-weight-bold text-dark mb-0">
                <i class="bi bi-journal-check text-primary me-2"></i> Reporte de Asistencia por Clase / Grupo
            </h2>
            @if($selectedSchedule)
                <a href="{{ route('teacher.attendance.export', ['schedule_id' => $selectedSchedule->id, 'date' => $date]) }}" class="btn btn-outline-success btn-sm font-weight-bold">
                    <i class="bi bi-file-earmark-spreadsheet-fill me-1"></i> Exportar CSV (Excel)
                </a>
            @endif
        </div>
@endsection

@section('content')

    <!-- Class Schedule Filter Bar -->
    <div class="card card-custom p-4 bg-white mb-4">
        <form method="GET" action="{{ route('teacher.attendance.index') }}" class="row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label fw-semibold text-dark"><i class="bi bi-book-half text-primary me-1"></i> Seleccionar Asignatura / Grupo / Horario</label>
                <select name="schedule_id" class="form-select" onchange="this.form.submit()">
                    @forelse($classSchedules as $sched)
                        <option value="{{ $sched->id }}" {{ $selectedScheduleId == $sched->id ? 'selected' : '' }}>
                            📚 Grupo {{ $sched->grupo?->codigo_grupo }} — {{ $sched->materia?->nombre }} | {{ ucfirst($sched->dia_semana) }} ({{ substr($sched->hora_inicio, 0, 5) }} - {{ substr($sched->hora_fin, 0, 5) }}) {{ $sched->aula ? '['.$sched->aula.']' : '' }} {{ $sched->docente?->nombre_completo ? '— Prof. '.$sched->docente->nombre_completo : '' }}
                        </option>
                    @empty
                        <option value="">No hay clases asignadas para este día de la semana</option>
                    @endforelse
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold text-dark"><i class="bi bi-calendar-date text-primary me-1"></i> Fecha de Consulta</label>
                <input type="date" name="date" class="form-control" value="{{ $date }}" onchange="this.form.submit()">
            </div>

            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100 fw-semibold">
                    <i class="bi bi-search me-1"></i> Consultar
                </button>
            </div>
        </form>
    </div>

    @if($selectedSchedule)
        <!-- Selected Class Info Card -->
        <div class="alert alert-light border card-custom p-3 mb-4 shadow-sm">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold text-primary mb-1">
                        <i class="bi bi-mortarboard-fill me-2"></i> {{ $selectedSchedule->materia?->nombre }} 
                        <span class="badge bg-primary ms-2">{{ $selectedSchedule->materia?->clave }}</span>
                    </h5>
                    <div class="text-muted small">
                        <strong>Grupo:</strong> {{ $selectedSchedule->grupo?->codigo_grupo }} (Turno {{ ucfirst($selectedSchedule->grupo?->turno) }}) | 
                        <strong>Día:</strong> {{ ucfirst($selectedSchedule->dia_semana) }} | 
                        <strong>Horario:</strong> {{ substr($selectedSchedule->hora_inicio, 0, 5) }} - {{ substr($selectedSchedule->hora_fin, 0, 5) }} hrs
                        @if($selectedSchedule->aula)
                            | <strong>Aula:</strong> {{ $selectedSchedule->aula }}
                        @endif
                    </div>
                </div>
                <span class="badge bg-dark px-3 py-2 fs-6">
                    <i class="bi bi-person-workspace me-1"></i> Prof. {{ auth()->user()->nombre_formateado }}
                </span>
            </div>
        </div>

        <!-- Summary Metric Cards -->
        <div class="row g-3 mb-4">
            <div class="col">
                <div class="card card-custom p-3 bg-white text-center border-start border-primary border-4">
                    <small class="text-uppercase text-muted fw-bold">Total Grupo</small>
                    <div class="fs-4 fw-bold text-dark">{{ $metrics['total'] }}</div>
                </div>
            </div>
            <div class="col">
                <div class="card card-custom p-3 bg-white text-center border-start border-success border-4">
                    <small class="text-uppercase text-muted fw-bold">Presentes</small>
                    <div class="fs-4 fw-bold text-success">{{ $metrics['presentes'] }}</div>
                </div>
            </div>
            <div class="col">
                <div class="card card-custom p-3 bg-white text-center border-start border-warning border-4">
                    <small class="text-uppercase text-muted fw-bold">Retardos</small>
                    <div class="fs-4 fw-bold text-warning">{{ $metrics['retardos'] }}</div>
                </div>
            </div>
            <div class="col">
                <div class="card card-custom p-3 bg-white text-center border-start border-danger border-4">
                    <small class="text-uppercase text-muted fw-bold">Faltas</small>
                    <div class="fs-4 fw-bold text-danger">{{ $metrics['faltas'] }}</div>
                </div>
            </div>
            <div class="col">
                <div class="card card-custom p-3 bg-white text-center border-start border-info border-4">
                    <small class="text-uppercase text-muted fw-bold">Justificados</small>
                    <div class="fs-4 fw-bold text-info">{{ $metrics['justificados'] }}</div>
                </div>
            </div>
        </div>

        <!-- Attendance List Table -->
        <div class="card card-custom p-4 bg-white">
            <div class="table-responsive">
                <table class="table table-hover align-middle table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Matrícula</th>
                            <th>Estudiante</th>
                            <th>Hora Entrada</th>
                            <th>Hora Salida</th>
                            <th>Estado</th>
                            <th>Notas / Justificación</th>
                            <th class="text-end">Modificar / Justificar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendanceList as $row)
                            <tr>
                                <td><span class="font-monospace fw-bold">{{ $row->student->matricula }}</span></td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $row->student->nombre_formateado }}</div>
                                </td>
                                <td>{{ $row->check_in_time ?? '--:--' }}</td>
                                <td>{{ $row->check_out_time ?? '--:--' }}</td>
                                <td>
                                    @if($row->status === 'presente')
                                        <span class="badge bg-success px-3 py-1"><i class="bi bi-check-circle me-1"></i> PRESENTE</span>
                                    @elseif($row->status === 'retardo')
                                        <span class="badge bg-warning text-dark px-3 py-1"><i class="bi bi-clock-history me-1"></i> RETARDO</span>
                                    @elseif($row->status === 'justificado')
                                        <span class="badge bg-info px-3 py-1"><i class="bi bi-file-earmark-check me-1"></i> JUSTIFICADO</span>
                                    @else
                                        <span class="badge bg-danger px-3 py-1"><i class="bi bi-x-circle me-1"></i> FALTA</span>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted">{{ $row->notes ?? 'Sin observaciones' }}</small>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalOverride-{{ $row->student->id }}">
                                        <i class="bi bi-pencil-square"></i> Cambiar
                                    </button>

                                    <!-- Modal Override Status -->
                                    <div class="modal fade text-start" id="modalOverride-{{ $row->student->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content card-custom">
                                                <form action="{{ route('teacher.attendance.update') }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="student_id" value="{{ $row->student->id }}">
                                                    <input type="hidden" name="date" value="{{ $date }}">
                                                    
                                                    <div class="modal-header">
                                                        <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i> Cambiar Estado de Asistencia</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">Estudiante</label>
                                                            <input type="text" class="form-control" value="{{ $row->student->nombre_formateado }}" disabled>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">Estado de Asistencia</label>
                                                            <select name="status" class="form-select" required>
                                                                <option value="presente" {{ $row->status === 'presente' ? 'selected' : '' }}>Presente</option>
                                                                <option value="retardo" {{ $row->status === 'retardo' ? 'selected' : '' }}>Retardo</option>
                                                                <option value="justificado" {{ $row->status === 'justificado' ? 'selected' : '' }}>Justificado</option>
                                                                <option value="falta" {{ $row->status === 'falta' ? 'selected' : '' }}>Falta</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">Observaciones / Motivo Justificación</label>
                                                            <input type="text" name="notes" class="form-control" value="{{ $row->notes }}" placeholder="Ej. Cita médica, justificante entregado">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" class="btn btn-primary btn-sm">Actualizar Estado</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No se encontraron estudiantes en este grupo.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="alert alert-warning card-custom text-center py-4">
            <i class="bi bi-calendar-x fs-3 d-block mb-2"></i> No tiene clases ni horarios asignados para el día seleccionado ({{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}).
        </div>
    @endif
@endsection
