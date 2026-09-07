@extends('layouts.app')

@section('header')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 class="h4 font-weight-bold text-dark mb-0">
                <i class="bi bi-person-check-fill text-primary me-2"></i> Control General de Asistencias
            </h2>
            <p class="text-muted small mb-0">Consulta y gestión de registros de entrada y salida de alumnos y personal docente</p>
        </div>
        <div class="d-flex gap-2">
            @if($tab === 'students' && $canViewStudents)
                <a href="{{ route('attendance.overview.export-students', ['date' => $date, 'group_id' => $selectedGroupId]) }}" class="btn btn-outline-success btn-sm font-weight-bold">
                    <i class="bi bi-file-earmark-spreadsheet-fill me-1"></i> Exportar CSV Alumnos
                </a>
            @elseif($tab === 'teachers' && $canViewTeachers)
                <a href="{{ route('attendance.overview.export-teachers', ['date' => $date]) }}" class="btn btn-outline-success btn-sm font-weight-bold">
                    <i class="bi bi-file-earmark-spreadsheet-fill me-1"></i> Exportar CSV Docentes
                </a>
            @endif
        </div>
    </div>
@endsection

@section('content')

    <!-- Tabs Navigation -->
    <ul class="nav nav-pills mb-4 card-custom bg-white p-2 border shadow-sm">
        @if($canViewStudents)
            <li class="nav-item">
                <a class="nav-link fw-bold px-4 py-2 {{ $tab === 'students' ? 'active bg-primary text-white' : 'text-dark' }}" 
                   href="{{ route('attendance.overview.index', ['tab' => 'students', 'date' => $date, 'group_id' => $selectedGroupId, 'status' => $statusFilter, 'search' => $search]) }}">
                    <i class="bi bi-mortarboard-fill me-2"></i> Asistencia de Alumnos
                </a>
            </li>
        @endif

        @if($canViewTeachers)
            <li class="nav-item">
                <a class="nav-link fw-bold px-4 py-2 {{ $tab === 'teachers' ? 'active bg-primary text-white' : 'text-dark' }}" 
                   href="{{ route('attendance.overview.index', ['tab' => 'teachers', 'date' => $date, 'status' => $statusFilter, 'search' => $search]) }}">
                    <i class="bi bi-person-badge-fill me-2"></i> Asistencia de Docentes
                </a>
            </li>
        @endif
    </ul>

    <!-- Filter Bar -->
    <div class="card card-custom p-4 bg-white mb-4 shadow-sm">
        <form method="GET" action="{{ route('attendance.overview.index') }}" class="row g-3 align-items-end">
            <input type="hidden" name="tab" value="{{ $tab }}">

            <div class="col-md-3">
                <label class="form-label fw-semibold text-dark"><i class="bi bi-calendar-date text-primary me-1"></i> Fecha de Consulta</label>
                <input type="date" name="date" class="form-control" value="{{ $date }}" onchange="this.form.submit()">
            </div>

            @if($tab === 'students')
                <div class="col-md-3">
                    <label class="form-label fw-semibold text-dark"><i class="bi bi-people-fill text-primary me-1"></i> Grupo / Sección</label>
                    <select name="group_id" class="form-select" onchange="this.form.submit()">
                        <option value="all" {{ $selectedGroupId === 'all' ? 'selected' : '' }}>Todos los grupos</option>
                        @foreach($groups as $grp)
                            <option value="{{ $grp->id }}" {{ $selectedGroupId == $grp->id ? 'selected' : '' }}>
                                Grupo {{ $grp->codigo_grupo }} ({{ ucfirst($grp->turno) }})
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="col-md-3">
                <label class="form-label fw-semibold text-dark"><i class="bi bi-funnel-fill text-primary me-1"></i> Estado de Asistencia</label>
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>Todos los estados</option>
                    <option value="presente" {{ $statusFilter === 'presente' ? 'selected' : '' }}>Presentes</option>
                    <option value="retardo" {{ $statusFilter === 'retardo' ? 'selected' : '' }}>Retardos</option>
                    <option value="falta" {{ $statusFilter === 'falta' ? 'selected' : '' }}>Faltas</option>
                    <option value="justificado" {{ $statusFilter === 'justificado' ? 'selected' : '' }}>Justificados</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold text-dark"><i class="bi bi-search text-primary me-1"></i> Buscar</label>
                <div class="input-group">
                    <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="{{ $tab === 'students' ? 'Nombre o Matrícula' : 'Nombre o Email' }}">
                    <button type="submit" class="btn btn-primary fw-semibold"><i class="bi bi-search"></i></button>
                </div>
            </div>
        </form>
    </div>

    <!-- Summary Metrics -->
    @php
        $metrics = $tab === 'students' ? $studentMetrics : $teacherMetrics;
    @endphp

    <div class="row g-3 mb-4">
        <div class="col">
            <div class="card card-custom p-3 bg-white text-center border-start border-primary border-4 shadow-sm">
                <small class="text-uppercase text-muted fw-bold">Total Registrados</small>
                <div class="fs-4 fw-bold text-dark">{{ $metrics['total'] }}</div>
            </div>
        </div>
        <div class="col">
            <div class="card card-custom p-3 bg-white text-center border-start border-success border-4 shadow-sm">
                <small class="text-uppercase text-muted fw-bold">Presentes</small>
                <div class="fs-4 fw-bold text-success">{{ $metrics['presentes'] }}</div>
            </div>
        </div>
        <div class="col">
            <div class="card card-custom p-3 bg-white text-center border-start border-warning border-4 shadow-sm">
                <small class="text-uppercase text-muted fw-bold">Retardos</small>
                <div class="fs-4 fw-bold text-warning">{{ $metrics['retardos'] }}</div>
            </div>
        </div>
        <div class="col">
            <div class="card card-custom p-3 bg-white text-center border-start border-danger border-4 shadow-sm">
                <small class="text-uppercase text-muted fw-bold">Faltas</small>
                <div class="fs-4 fw-bold text-danger">{{ $metrics['faltas'] }}</div>
            </div>
        </div>
        <div class="col">
            <div class="card card-custom p-3 bg-white text-center border-start border-info border-4 shadow-sm">
                <small class="text-uppercase text-muted fw-bold">Justificados</small>
                <div class="fs-4 fw-bold text-info">{{ $metrics['justificados'] }}</div>
            </div>
        </div>
    </div>

    <!-- Content Table -->
    @if($tab === 'students' && $canViewStudents)
        <div class="card card-custom p-4 bg-white shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Matrícula</th>
                            <th>Estudiante</th>
                            <th>Grupo</th>
                            <th>Hora Entrada</th>
                            <th>Hora Salida</th>
                            <th>Estado</th>
                            <th>Observaciones</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($studentList as $row)
                            <tr>
                                <td><span class="font-monospace fw-bold">{{ $row->student->matricula }}</span></td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $row->student->nombre_formateado }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $row->student->grupo?->codigo_grupo ?? 'Sin asignación' }}</span>
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
                                    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalStudent-{{ $row->student->id }}">
                                        <i class="bi bi-pencil-square"></i> Modificar
                                    </button>

                                    <!-- Modal Override Student -->
                                    <div class="modal fade text-start" id="modalStudent-{{ $row->student->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content card-custom">
                                                <form action="{{ route('attendance.overview.update-student') }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="student_id" value="{{ $row->student->id }}">
                                                    <input type="hidden" name="date" value="{{ $date }}">
                                                    
                                                    <div class="modal-header">
                                                        <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i> Cambiar Asistencia Estudiante</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">Estudiante</label>
                                                            <input type="text" class="form-control" value="{{ $row->student->nombre_formateado }} ({{ $row->student->grupo?->codigo_grupo }})" disabled>
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
                                                        <div class="row">
                                                            <div class="col-6 mb-3">
                                                                <label class="form-label fw-semibold">Hora Entrada</label>
                                                                <input type="time" name="check_in_time" class="form-control" value="{{ $row->check_in_time }}">
                                                            </div>
                                                            <div class="col-6 mb-3">
                                                                <label class="form-label fw-semibold">Hora Salida</label>
                                                                <input type="time" name="check_out_time" class="form-control" value="{{ $row->check_out_time }}">
                                                            </div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">Observaciones / Motivo</label>
                                                            <input type="text" name="notes" class="form-control" value="{{ $row->notes }}" placeholder="Ej. Cita médica, justificante entregado">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" class="btn btn-primary btn-sm">Guardar Cambios</button>
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
                                    <i class="bi bi-info-circle me-1"></i> No se encontraron registros de estudiantes con los filtros seleccionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @elseif($tab === 'teachers' && $canViewTeachers)
        <div class="card card-custom p-4 bg-white shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Docente</th>
                            <th>Correo / Contacto</th>
                            <th>Hora Entrada</th>
                            <th>Hora Salida</th>
                            <th>Estado</th>
                            <th>Observaciones</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($teacherList as $row)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if($row->teacher->foto)
                                            <img src="{{ asset('storage/' . $row->teacher->foto) }}" alt="Foto de {{ $row->teacher->nombre_completo }}" class="rounded-circle border object-fit-cover shadow-sm" style="width: 38px; height: 38px; flex-shrink: 0;">
                                        @else
                                            <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center fw-bold" style="width: 38px; height: 38px; flex-shrink: 0;">
                                                <i class="bi bi-person-badge"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <div class="fw-bold text-dark">{{ $row->teacher->nombre_completo }}</div>
                                            <small class="text-muted">DOC-{{ $row->teacher->id }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div><i class="bi bi-envelope me-1 text-muted"></i> {{ $row->teacher->email }}</div>
                                    @if($row->teacher->phone)
                                        <small class="text-muted"><i class="bi bi-telephone me-1"></i> {{ $row->teacher->phone }}</small>
                                    @endif
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
                                    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTeacher-{{ $row->teacher->id }}">
                                        <i class="bi bi-pencil-square"></i> Modificar
                                    </button>

                                    <!-- Modal Override Teacher -->
                                    <div class="modal fade text-start" id="modalTeacher-{{ $row->teacher->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content card-custom">
                                                <form action="{{ route('attendance.overview.update-teacher') }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="docente_id" value="{{ $row->teacher->id }}">
                                                    <input type="hidden" name="date" value="{{ $date }}">
                                                    
                                                    <div class="modal-header">
                                                        <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i> Cambiar Asistencia Docente</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">Docente</label>
                                                            <input type="text" class="form-control" value="{{ $row->teacher->nombre_completo }}" disabled>
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
                                                        <div class="row">
                                                            <div class="col-6 mb-3">
                                                                <label class="form-label fw-semibold">Hora Entrada</label>
                                                                <input type="time" name="check_in_time" class="form-control" value="{{ $row->check_in_time }}">
                                                            </div>
                                                            <div class="col-6 mb-3">
                                                                <label class="form-label fw-semibold">Hora Salida</label>
                                                                <input type="time" name="check_out_time" class="form-control" value="{{ $row->check_out_time }}">
                                                            </div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">Observaciones / Motivo</label>
                                                            <input type="text" name="notes" class="form-control" value="{{ $row->notes }}" placeholder="Ej. Permiso de dirección, comisión">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" class="btn btn-primary btn-sm">Guardar Cambios</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bi bi-info-circle me-1"></i> No se encontraron registros de docentes con los filtros seleccionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

@endsection
