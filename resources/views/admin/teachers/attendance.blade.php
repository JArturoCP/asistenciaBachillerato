@extends('layouts.app')

@section('header')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h2 class="h4 font-weight-bold text-dark mb-0">
            <i class="bi bi-person-check text-primary me-2"></i> Control de Asistencia del Personal Docente
        </h2>
        <a href="{{ route('admin.teacher-attendance.export', ['date' => $date]) }}" class="btn btn-outline-success font-weight-bold">
            <i class="bi bi-file-earmark-spreadsheet-fill me-1"></i> Exportar Reporte CSV
        </a>
    </div>
@endsection

@section('content')

    <!-- Date Selection Filter -->
    <div class="card card-custom p-4 bg-white mb-4 shadow-sm">
        <form method="GET" action="{{ route('admin.teacher-attendance.index') }}" class="row g-3 align-items-end">
            <div class="col-md-6 col-lg-5">
                <label class="form-label fw-semibold text-dark">
                    <i class="bi bi-calendar-date text-primary me-1"></i> Seleccionar Fecha de Asistencia
                </label>
                <input type="date" name="date" class="form-control form-control-lg" value="{{ $date }}" onchange="this.form.submit()">
            </div>
            <div class="col-md-4 col-lg-3">
                <button type="submit" class="btn btn-primary btn-lg w-100 fw-semibold">
                    <i class="bi bi-search me-1"></i> Filtrar Fecha
                </button>
            </div>
            <div class="col-md-2 col-lg-4 text-md-end text-muted small">
                <i class="bi bi-info-circle me-1"></i> Mostrando asistencias del <strong>{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</strong>
            </div>
        </form>
    </div>

    <!-- KPI Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card card-custom p-3 bg-white text-center border-start border-primary border-4 shadow-sm">
                <div class="text-muted small fw-semibold uppercase">Total Docentes</div>
                <div class="fs-2 fw-bold text-dark">{{ $metrics['total'] }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-custom p-3 bg-white text-center border-start border-success border-4 shadow-sm">
                <div class="text-muted small fw-semibold uppercase">Presentes</div>
                <div class="fs-2 fw-bold text-success">{{ $metrics['presentes'] }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-custom p-3 bg-white text-center border-start border-warning border-4 shadow-sm">
                <div class="text-muted small fw-semibold uppercase">Retardos</div>
                <div class="fs-2 fw-bold text-warning">{{ $metrics['retardos'] }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-custom p-3 bg-white text-center border-start border-danger border-4 shadow-sm">
                <div class="text-muted small fw-semibold uppercase">Faltas / Ausentes</div>
                <div class="fs-2 fw-bold text-danger">{{ $metrics['faltas'] }}</div>
            </div>
        </div>
    </div>

    <!-- Attendance Table Card -->
    <div class="card card-custom p-4 bg-white shadow-sm">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="bi bi-list-check text-primary me-2"></i> Registro de Asistencia General de Docentes
            </h5>
            <span class="badge bg-secondary px-3 py-2 fs-6">
                {{ $teacherList->count() }} Docentes Registrados
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Clave / ID Kiosco</th>
                        <th>Docente</th>
                        <th>Correo / Teléfono</th>
                        <th>Hora Entrada</th>
                        <th>Hora Salida</th>
                        <th>Estado</th>
                        <th>Observaciones</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($teacherList as $item)
                        <tr>
                            <td>
                                <span class="font-monospace fw-bold text-primary">DOC-{{ $item->teacher->id }}</span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $item->teacher->nombre_completo }}</div>
                                <div class="text-muted small">Rol: {{ ucfirst($item->teacher->role) }}</div>
                            </td>
                            <td>
                                <div class="small"><i class="bi bi-envelope me-1 text-muted"></i>{{ $item->teacher->email }}</div>
                                @if($item->teacher->phone)
                                    <div class="small text-muted"><i class="bi bi-telephone me-1"></i>{{ $item->teacher->phone }}</div>
                                @endif
                            </td>
                            <td>
                                @if($item->check_in_time)
                                    <span class="badge bg-light text-dark border font-monospace">
                                        <i class="bi bi-box-arrow-in-right text-success me-1"></i>{{ substr($item->check_in_time, 0, 5) }} hrs
                                    </span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td>
                                @if($item->check_out_time)
                                    <span class="badge bg-light text-dark border font-monospace">
                                        <i class="bi bi-box-arrow-left text-danger me-1"></i>{{ substr($item->check_out_time, 0, 5) }} hrs
                                    </span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td>
                                @if($item->status === 'presente')
                                    <span class="badge bg-success px-3 py-1.5"><i class="bi bi-check-circle me-1"></i>Presente</span>
                                @elseif($item->status === 'retardo')
                                    <span class="badge bg-warning text-dark px-3 py-1.5"><i class="bi bi-clock-history me-1"></i>Retardo</span>
                                @elseif($item->status === 'justificado')
                                    <span class="badge bg-info text-dark px-3 py-1.5"><i class="bi bi-file-earmark-check me-1"></i>Justificado</span>
                                @else
                                    <span class="badge bg-danger px-3 py-1.5"><i class="bi bi-x-circle me-1"></i>Falta</span>
                                @endif
                            </td>
                            <td>
                                <small class="text-muted">{{ $item->notes ?: '—' }}</small>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#editModal{{ $item->teacher->id }}">
                                    <i class="bi bi-pencil-square me-1"></i> Modificar
                                </button>

                                <!-- Edit Modal -->
                                <div class="modal fade text-start" id="editModal{{ $item->teacher->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST" action="{{ route('admin.teacher-attendance.update') }}">
                                                @csrf
                                                <input type="hidden" name="docente_id" value="{{ $item->teacher->id }}">
                                                <input type="hidden" name="date" value="{{ $date }}">
                                                
                                                <div class="modal-header bg-primary text-white">
                                                    <h5 class="modal-title fs-6 fw-bold">
                                                        <i class="bi bi-person-gear me-1"></i> Asistencia: {{ $item->teacher->nombre_completo }}
                                                    </h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold small">Estado de Asistencia</label>
                                                        <select name="status" class="form-select" required>
                                                            <option value="presente" {{ $item->status === 'presente' ? 'selected' : '' }}>Presente</option>
                                                            <option value="retardo" {{ $item->status === 'retardo' ? 'selected' : '' }}>Retardo</option>
                                                            <option value="justificado" {{ $item->status === 'justificado' ? 'selected' : '' }}>Justificado</option>
                                                            <option value="falta" {{ $item->status === 'falta' ? 'selected' : '' }}>Falta / Ausente</option>
                                                        </select>
                                                    </div>

                                                    <div class="row g-2 mb-3">
                                                        <div class="col-6">
                                                            <label class="form-label fw-bold small">Hora Entrada</label>
                                                            <input type="time" name="check_in_time" class="form-control" value="{{ $item->check_in_time ? substr($item->check_in_time, 0, 5) : '' }}">
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="form-label fw-bold small">Hora Salida</label>
                                                            <input type="time" name="check_out_time" class="form-control" value="{{ $item->check_out_time ? substr($item->check_out_time, 0, 5) : '' }}">
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold small">Observaciones / Motivo de Justificación</label>
                                                        <textarea name="notes" class="form-control" rows="2" placeholder="Ej. Comisión académica, permiso previo, etc.">{{ $item->notes }}</textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" class="btn btn-primary btn-sm fw-bold">Guardar Cambios</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-person-x fs-1 d-block mb-2 text-secondary"></i>
                                No hay docentes registrados o aprobados en el sistema.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection
